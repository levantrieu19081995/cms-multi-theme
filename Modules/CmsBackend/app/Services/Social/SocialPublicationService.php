<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LocaleManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Business rules of the multi-channel publication. Phase 1 prepares publications (draft / ready) and never contacts a
 * social platform: there is no adapter, so publish() always answers "channel not connected".
 */
final class SocialPublicationService
{
    private const MODULE = 'Truyền thông đa kênh';

    public function __construct(
        private SocialPublicationDataProvider $data,
        private SocialSnapshotBuilder $snapshots,
        private SocialChannelRegistry $registry,
    ) {}

    /** Current project (ProjectContext). The social module never reads it from a theme or a title. */
    public function projectKey(): string
    {
        return ProjectContext::key();
    }

    /**
     * Server-side rule: everything an operation touches (source post, publication, account) belongs to the current project.
     *
     * @throws SocialException on any other project, or on a missing one
     */
    private function assertProject(?string ...$keys): void
    {
        $current = $this->projectKey();
        foreach ($keys as $key) {
            if ($key === null || $key !== $current) {
                throw SocialException::projectMismatch();
            }
        }
    }

    /**
     * Data of the create form: the post, the languages it can be shared in, and the snapshot of the chosen language.
     *
     * @return array{post: \App\Models\Post, locale: string, locales: string[], snapshot: array<string, mixed>}
     *
     * @throws SocialException
     */
    public function prepare(int $postId, ?string $locale): array
    {
        $post = $this->data->findEligiblePost($postId);
        if ($post === null) {
            throw new SocialException(__('admin.social.error_post_not_eligible'), 'post_not_eligible');
        }

        $locales = $this->snapshots->availableLocales($post);
        $locale = in_array($locale, $locales, true) ? $locale : app(LocaleManager::class)->default();

        return ['post' => $post, 'locale' => $locale, 'locales' => $locales, 'snapshot' => $this->snapshots->build($post, $locale)];
    }

    /** @return array<string, bool> channel value => the project has a connected account for it */
    public function channelAvailability(): array
    {
        $availability = [];
        foreach (SocialChannel::cases() as $channel) {
            $availability[$channel->value] = $this->data->connectedAccount($channel) !== null;
        }

        return $availability;
    }

    /**
     * Create a publication from a CMS post: snapshot of the content, the chosen channels and the first status, in one transaction.
     * Submitting the same form twice (same idempotency key) returns the first publication.
     *
     * @param  array{post_id: int, locale: string, title?: ?string, message?: ?string, image_url?: ?string, channels?: array, intent?: string, idempotency_key?: ?string}  $input
     * @return array{0: SocialPublication, 1: bool} the publication and whether this call created it
     */
    public function createFromPost(User $user, array $input): array
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_CREATE);

        $key = $this->cleanKey($input['idempotency_key'] ?? null);
        if ($key !== null && ($existing = $this->data->findByIdempotencyKey($key)) !== null) {
            return [$this->sameOwner($existing, $user), false];
        }

        $post = $this->data->findEligiblePost((int) $input['post_id']);
        if ($post === null) {
            throw new SocialException(__('admin.social.error_post_not_eligible'), 'post_not_eligible');
        }
        // The list already shows only this project's posts; check the ownership again from the source of truth.
        $this->assertProject($this->data->postProjectKey($post->id));

        $snapshot = $this->snapshots->build($post, (string) $input['locale']);
        $content = $this->content($input, $snapshot);
        $channels = $this->channels($input['channels'] ?? []);
        $status = $this->targetStatus($input['intent'] ?? 'draft', $content, $channels);

        try {
            $publication = DB::transaction(function () use ($user, $post, $input, $snapshot, $content, $channels, $status, $key) {
                $publication = SocialPublication::create([
                    'post_id' => $post->id,
                    'project_key' => $this->projectKey(),
                    'locale' => (string) $input['locale'],
                    'title' => $content['title'],
                    'message' => $content['message'],
                    'image_url' => $content['image_url'],
                    'public_url' => $snapshot['public_url'],
                    'status' => $status->value,
                    'idempotency_key' => $key,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $this->syncChannels($publication, $channels);
                $this->refreshStatus($publication);

                return $publication;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Two requests of the same form at the same time: the other one won the unique key.
            $existing = $key !== null ? $this->data->findByIdempotencyKey($key) : null;
            if ($existing === null) {
                throw $e;
            }

            return [$this->sameOwner($existing, $user), false];
        }

        $this->log('create', $publication, $user, __('admin.social.log_created'));

        return [$publication, true];
    }

    /**
     * Edit a publication that has not been sent. The snapshot of the post (language, address, source post) is fixed;
     * the administrator edits the share text, image and channels.
     *
     * @param  array{title?: ?string, message?: ?string, image_url?: ?string, channels?: array, intent?: string}  $input
     */
    public function updateDraft(User $user, SocialPublication $publication, array $input): SocialPublication
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_CREATE);

        $publication = DB::transaction(function () use ($user, $publication, $input) {
            $locked = SocialPublication::query()->lockForUpdate()->findOrFail($publication->getKey());
            $this->assertProject($locked->project_key);
            if (! $locked->status->editable()) {
                throw SocialException::notEditable();
            }

            $content = $this->content($input, ['title' => $locked->title, 'message' => $locked->message, 'image_url' => $locked->image_url]);
            $channels = $this->channels($input['channels'] ?? []);
            $status = $this->targetStatus($input['intent'] ?? 'draft', $content, $channels);

            $locked->update($content + ['status' => $status->value, 'updated_by' => $user->id]);
            $this->syncChannels($locked, $channels);
            $this->refreshStatus($locked);

            return $locked;
        });

        $this->log('update', $publication, $user, __('admin.social.log_updated'));

        return $publication;
    }

    /** Archive a publication that has not been sent. History is never deleted. */
    public function archive(User $user, SocialPublication $publication): SocialPublication
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_CREATE);

        $publication = DB::transaction(function () use ($user, $publication) {
            $locked = SocialPublication::query()->lockForUpdate()->findOrFail($publication->getKey());
            $this->assertProject($locked->project_key);
            if (! $locked->status->editable()) {
                throw SocialException::notEditable();
            }
            $locked->update(['status' => SocialPublicationStatus::ARCHIVED->value, 'archived_at' => now(), 'updated_by' => $user->id]);

            return $locked;
        });

        $this->log('archive', $publication, $user, __('admin.social.log_archived'));

        return $publication;
    }

    /** Bring an archived publication back as a draft. */
    public function restore(User $user, SocialPublication $publication): SocialPublication
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_CREATE);

        $publication = DB::transaction(function () use ($user, $publication) {
            $locked = SocialPublication::query()->lockForUpdate()->findOrFail($publication->getKey());
            $this->assertProject($locked->project_key);
            if ($locked->status !== SocialPublicationStatus::ARCHIVED) {
                throw SocialException::notEditable();
            }
            $locked->update(['status' => SocialPublicationStatus::DRAFT->value, 'archived_at' => null, 'updated_by' => $user->id]);

            return $locked;
        });

        $this->log('restore', $publication, $user, __('admin.social.log_restored'));

        return $publication;
    }

    /**
     * Sending is not available before Phase 2. Every check runs first, so a channel without an adapter or a connected
     * account is refused with a business error and nothing is changed or sent.
     *
     * @throws SocialException
     */
    public function publish(User $user, SocialPublication $publication): void
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH);

        // Source post, publication and every account must belong to the current project.
        $this->assertProject($publication->project_key);
        if ($publication->post_id !== null) {
            $this->assertProject($this->data->postProjectKey((int) $publication->post_id));
        }

        $publication->loadMissing('channels.account');
        if ($publication->status !== SocialPublicationStatus::READY || $publication->channels->isEmpty()) {
            throw new SocialException(__('admin.social.error_not_ready'), 'not_ready');
        }

        foreach ($publication->channels as $delivery) {
            if ($delivery->account !== null) {
                $this->assertProject($delivery->account->project_key);
            }
            $account = $this->data->connectedAccount($delivery->channel);
            if (! $this->registry->has($delivery->channel) || $account === null) {
                throw SocialException::channelNotConnected();
            }
            $this->assertProject($account->project_key);
        }

        // Posting is not built yet (an adapter exists since Phase 2A, but only to verify the connection): never a silent success.
        throw new SocialException(__('admin.social.error_not_implemented'), 'not_implemented');
    }

    /** Overall status derived from the status of its channels. A draft / ready publication stays as the user set it. */
    public function aggregateStatus(SocialPublication $publication): SocialPublicationStatus
    {
        $current = $publication->status;
        if ($current === SocialPublicationStatus::ARCHIVED) {
            return $current;
        }

        $statuses = $publication->channels()->pluck('status')
            ->map(fn ($status) => $status instanceof SocialChannelStatus ? $status : SocialChannelStatus::from($status))
            ->reject(fn (SocialChannelStatus $status) => $status === SocialChannelStatus::REMOVED);

        if ($statuses->isEmpty()) {
            return $current;
        }
        $count = fn (SocialChannelStatus $wanted) => $statuses->filter(fn ($status) => $status === $wanted)->count();

        return match (true) {
            $count(SocialChannelStatus::PROCESSING) > 0 => SocialPublicationStatus::PROCESSING,
            $count(SocialChannelStatus::QUEUED) > 0 => SocialPublicationStatus::QUEUED,
            $count(SocialChannelStatus::PUBLISHED) === $statuses->count() => SocialPublicationStatus::PUBLISHED,
            $count(SocialChannelStatus::PUBLISHED) > 0 => SocialPublicationStatus::PARTIAL,
            $count(SocialChannelStatus::FAILED) > 0 => SocialPublicationStatus::FAILED,
            default => $current, // only draft / unavailable deliveries: nothing has been sent
        };
    }

    public function refreshStatus(SocialPublication $publication): SocialPublication
    {
        $status = $this->aggregateStatus($publication);
        if ($status !== $publication->status) {
            $publication->update(['status' => $status->value]);
        }

        return $publication;
    }

    // ------------------------------------------------------------------ helpers

    private function authorize(User $user, PermissionsEnum $permission): void
    {
        if (! SocialPermissions::can($user, $permission)) {
            throw SocialException::forbidden();
        }
    }

    private function sameOwner(SocialPublication $publication, User $user): SocialPublication
    {
        if ((int) $publication->created_by !== (int) $user->id) {
            throw SocialException::forbidden();
        }

        return $publication;
    }

    private function cleanKey(mixed $key): ?string
    {
        $key = is_string($key) ? trim($key) : '';

        return preg_match('/^[A-Za-z0-9-]{16,64}$/', $key) === 1 ? $key : null;
    }

    /**
     * Text the administrator edited; a field that was not submitted keeps the snapshot value, a submitted empty one stays
     * empty (an emptied image removes the image). Plain text only.
     *
     * @return array{title: string, message: string, image_url: ?string}
     */
    private function content(array $input, array $fallback): array
    {
        $image = $fallback['image_url'];
        if (array_key_exists('image_url', $input)) {
            $image = trim((string) $input['image_url']);
            $image = $image === '' ? null : (str_starts_with($image, '/') ? url($image) : $image);
        }

        return [
            'title' => array_key_exists('title', $input) ? $this->snapshots->plainText($input['title']) : (string) $fallback['title'],
            'message' => array_key_exists('message', $input) ? $this->snapshots->plainText($input['message']) : (string) $fallback['message'],
            'image_url' => $image,
        ];
    }

    /** @return SocialChannel[] */
    private function channels(mixed $channels): array
    {
        $result = [];
        foreach ((array) $channels as $value) {
            $channel = is_string($value) ? SocialChannel::tryFrom($value) : null;
            if ($channel !== null) {
                $result[$channel->value] = $channel;
            }
        }

        return array_values($result);
    }

    /** @param array{title: string, message: string, image_url: ?string} $content */
    private function targetStatus(string $intent, array $content, array $channels): SocialPublicationStatus
    {
        if ($intent !== 'ready') {
            return SocialPublicationStatus::DRAFT;
        }
        if ($channels === []) {
            throw new SocialException(__('admin.social.error_ready_needs_channel'), 'ready_needs_channel');
        }
        if (trim($content['title']) === '' || trim($content['message']) === '') {
            throw new SocialException(__('admin.social.error_ready_needs_content'), 'ready_needs_content');
        }

        return SocialPublicationStatus::READY;
    }

    /**
     * Rows of an editable publication follow the chosen channels. A channel whose project has no connected account is
     * `unavailable` ("not connected"); nothing is sent either way. Deliveries beyond draft are never touched.
     *
     * @param  SocialChannel[]  $channels
     */
    private function syncChannels(SocialPublication $publication, array $channels): void
    {
        $values = array_map(fn (SocialChannel $channel) => $channel->value, $channels);
        $editable = [SocialChannelStatus::DRAFT->value, SocialChannelStatus::UNAVAILABLE->value];

        $publication->channels()->whereNotIn('channel', $values)->whereIn('status', $editable)->get()
            ->each(fn ($delivery) => $delivery->delete());

        foreach ($channels as $channel) {
            $account = $this->data->connectedAccount($channel);
            if ($account !== null) {
                $this->assertProject($publication->project_key, $account->project_key);
            }
            $attributes = [
                'social_account_id' => $account?->id,
                'status' => ($account ? SocialChannelStatus::DRAFT : SocialChannelStatus::UNAVAILABLE)->value,
            ];

            $delivery = $publication->channels()->where('channel', $channel->value)->first();
            if ($delivery === null) {
                $publication->channels()->create(['channel' => $channel->value] + $attributes);
            } elseif (in_array($delivery->status->value, $editable, true)) {
                $delivery->update($attributes);
            }
        }
    }

    private function log(string $action, SocialPublication $publication, User $user, string $message): void
    {
        // Ids and statuses only: a publication never carries a token, and none is ever written to the log.
        ActivityLogger::log($action, self::MODULE, $publication, [
            'publication_id' => $publication->id,
            'status' => $publication->status->value,
            'locale' => $publication->locale,
            'user_id' => $user->id,
        ], $message);
    }
}
