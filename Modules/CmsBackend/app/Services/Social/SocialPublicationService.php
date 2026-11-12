<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use App\Models\Post;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LocaleManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Jobs\SendSocialDeliveryJob;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Business rules of the multi-channel publication: prepare (draft / ready), publish, retry, remove and read back from the
 * platforms. A platform is only contacted through the SocialChannelAdapter of its channel; sending is done by
 * SendSocialDeliveryJob, one job per channel, so one failing platform never blocks another.
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
     * @return array{post: Post, locale: string, locales: string[], snapshot: array<string, mixed>}
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

        return $this->persist($user, $key, $post->id, (string) $input['locale'], $content, $snapshot['public_url'], $input);
    }

    /**
     * Create a publication written directly in the CMS (no source post): the text, the image and, optionally, the address the post
     * points to are typed by the administrator, in the default language. Same rules, statuses and idempotency as createFromPost.
     *
     * @param  array{title?: ?string, message?: ?string, image_url?: ?string, public_url?: ?string, channels?: array, intent?: string, idempotency_key?: ?string}  $input
     * @return array{0: SocialPublication, 1: bool} the publication and whether this call created it
     */
    public function createDirect(User $user, array $input): array
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_CREATE);

        $key = $this->cleanKey($input['idempotency_key'] ?? null);
        if ($key !== null && ($existing = $this->data->findByIdempotencyKey($key)) !== null) {
            return [$this->sameOwner($existing, $user), false];
        }

        $content = $this->content($input, ['title' => '', 'message' => '', 'image_url' => null]);

        return $this->persist($user, $key, null, app(LocaleManager::class)->default(), $content, $this->publicUrl($input['public_url'] ?? null), $input);
    }

    /**
     * The one write path of a new publication: content, chosen channels and first status in one transaction. A repeated submit
     * (same idempotency key) returns the first publication.
     *
     * @param  array{title: string, message: string, image_url: ?string}  $content
     * @return array{0: SocialPublication, 1: bool}
     */
    private function persist(User $user, ?string $key, ?int $postId, string $locale, array $content, string $publicUrl, array $input): array
    {
        $channels = $this->channels($input['channels'] ?? []);
        $this->assertMediaFits($content, $channels);
        $status = $this->targetStatus($input['intent'] ?? 'draft', $content, $channels);

        try {
            $publication = DB::transaction(function () use ($user, $postId, $locale, $publicUrl, $content, $channels, $status, $key) {
                $publication = SocialPublication::create($this->contentColumns($content) + [
                    'post_id' => $postId,
                    'project_key' => $this->projectKey(),
                    'locale' => $locale,
                    'public_url' => $publicUrl,
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

    /** Address typed for a direct publication: empty (none), or an absolute http(s) address; the column is not nullable. */
    private function publicUrl(mixed $value): string
    {
        $url = trim((string) $value);

        return preg_match('~^https?://[^\s]+$~i', $url) === 1 ? $url : '';
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

            $content = $this->content($input, [
                'title' => $locked->title, 'message' => $locked->message, 'image_url' => $locked->image_url,
                'media' => array_map(fn (array $item) => $item['url'], $locked->mediaItems()),
            ]);
            $channels = $this->channels($input['channels'] ?? []);
            $this->assertMediaFits($content, $channels);
            $status = $this->targetStatus($input['intent'] ?? 'draft', $content, $channels);

            // The address is fixed for a publication made from a post; one written directly may change it.
            $address = $locked->post_id === null && array_key_exists('public_url', $input)
                ? ['public_url' => $this->publicUrl($input['public_url'])] : [];

            $locked->update($this->contentColumns($content) + $address + ['status' => $status->value, 'updated_by' => $user->id]);
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
     * Queue every channel of a ready publication for sending. Every check runs first, so a channel without an adapter or a
     * connected account refuses the whole publication with a business error and nothing is queued. The sending itself is
     * done by SendSocialDeliveryJob (see deliver()).
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

        $ids = $this->queueDeliveries($publication, [SocialChannelStatus::DRAFT, SocialChannelStatus::UNAVAILABLE], $user);
        $this->log('update', $publication, $user, __('admin.social.log_published'));
        $this->dispatchDeliveries($ids);
    }

    /**
     * Send the deliveries that failed once more. Only a person asks for this: nothing is retried automatically, because a
     * failed call may still have created the post on the platform.
     *
     * @throws SocialException
     */
    public function retry(User $user, SocialPublication $publication): void
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_RETRY);
        $this->assertProject($publication->project_key);

        $publication->loadMissing('channels');
        foreach ($publication->channels->where('status', SocialChannelStatus::FAILED) as $delivery) {
            if (! $this->registry->has($delivery->channel) || $this->data->connectedAccount($delivery->channel) === null) {
                throw SocialException::channelNotConnected();
            }
        }

        $ids = $this->queueDeliveries($publication, [SocialChannelStatus::FAILED], $user, 'error_nothing_to_retry');
        $this->log('update', $publication, $user, __('admin.social.log_retried'));
        $this->dispatchDeliveries($ids);
    }

    /**
     * Take the published deliveries down from their platforms. A platform that refuses leaves its delivery published; the
     * reason is returned so the administrator can remove the post there by hand.
     *
     * @return array{removed: int, errors: string[]}
     *
     * @throws SocialException
     */
    public function remove(User $user, SocialPublication $publication): array
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_PUBLICATION_REMOVE);
        $this->assertProject($publication->project_key);

        $removed = 0;
        $errors = [];
        foreach ($publication->channels()->with('account')->where('status', SocialChannelStatus::PUBLISHED->value)->get() as $delivery) {
            $adapter = $this->registry->find($delivery->channel);
            if ($adapter === null || $delivery->account === null || ! $this->accountUsable($delivery->account, $publication)) {
                $errors[] = $delivery->channel->label().': '.__('admin.social.error_channel_not_connected');

                continue;
            }
            $result = $this->callAdapter(fn () => $adapter->remove($delivery, $delivery->account));
            if ($result->success) {
                $delivery->update(['status' => SocialChannelStatus::REMOVED->value, 'removed_at' => now(), 'error_code' => null, 'error_message' => null]);
                $removed++;
            } else {
                $errors[] = $delivery->channel->label().': '.$result->message;
            }
        }
        if ($removed === 0 && $errors === []) {
            throw new SocialException(__('admin.social.error_nothing_to_remove'), 'nothing_to_remove');
        }

        $this->refreshStatus($publication);
        $this->log('update', $publication, $user, __('admin.social.log_removed'));

        return ['removed' => $removed, 'errors' => $errors];
    }

    /**
     * Read back from the platforms whether each published post still exists and how many reactions, comments and shares it has.
     *
     * @return array{synced: int, errors: string[]}
     *
     * @throws SocialException
     */
    public function sync(User $user, SocialPublication $publication): array
    {
        $this->authorize($user, PermissionsEnum::SOCIAL_STATISTICS_VIEW);
        $this->assertProject($publication->project_key);

        $synced = 0;
        $errors = [];
        $canStore = $this->data->hasMetricsColumn();
        $resolved = false;
        // Published posts, and posts the platform was still processing last time (their final id is read here).
        $deliveries = $publication->channels()->with('account')
            ->whereIn('status', [SocialChannelStatus::PUBLISHED->value, SocialChannelStatus::PROCESSING->value])
            ->whereNotNull('remote_post_id')->get();
        foreach ($deliveries as $delivery) {
            $adapter = $this->registry->find($delivery->channel);
            if ($adapter === null || $delivery->account === null || ! $this->accountUsable($delivery->account, $publication)) {
                $errors[] = $delivery->channel->label().': '.__('admin.social.error_channel_not_connected');

                continue;
            }

            $status = $this->callAdapter(fn () => $adapter->syncStatus($delivery, $delivery->account));
            if ($status->pending) {
                // Still being processed by the platform: nothing to decide yet, ask again later.
                $delivery->update(['remote_post_id' => $status->remoteId ?? $delivery->remote_post_id, 'error_message' => $status->message, 'last_synced_at' => now()]);
                $errors[] = $delivery->channel->label().': '.$status->message;

                continue;
            }
            if (! $status->success) {
                // The delivery keeps its status; the reason is shown next to it. Nothing is guessed about a post we cannot read.
                $delivery->update(['error_code' => $status->errorCode, 'error_message' => $status->message, 'last_synced_at' => now()]);
                $errors[] = $delivery->channel->label().': '.$status->message;

                continue;
            }

            $attributes = ['last_synced_at' => now()];
            if ($delivery->status === SocialChannelStatus::PROCESSING) {
                // The platform finished: the post now has its final id and counts as published.
                $attributes += [
                    'status' => SocialChannelStatus::PUBLISHED->value,
                    'remote_post_id' => $status->remoteId ?? $delivery->remote_post_id,
                    'published_at' => now(),
                    'error_code' => null,
                    'error_message' => null,
                ];
                $delivery->fill($attributes)->save();
                $attributes = [];
                $resolved = true;
            } elseif ($delivery->error_code === 'remote_missing') {
                $attributes += ['error_code' => null, 'error_message' => null];
            }
            if ($status->remoteUrl !== null) {
                $attributes['remote_url'] = $status->remoteUrl;
            }
            $metrics = $this->callAdapter(fn () => $adapter->syncMetrics($delivery, $delivery->account));
            if ($metrics->success && $metrics->remoteUrl !== null) {
                // Some platforms (Zalo) give the public address of the post only with its figures.
                $attributes['remote_url'] = $metrics->remoteUrl;
            }
            if ($metrics->success && $canStore && $metrics->data !== []) {
                $attributes['metrics'] = array_map('intval', $metrics->data);
            } elseif (! $metrics->success) {
                $errors[] = $delivery->channel->label().': '.$metrics->message;
            }
            if ($attributes !== []) {
                $delivery->update($attributes);
            }
            $synced++;
        }
        if ($synced === 0 && $errors === []) {
            throw new SocialException(__('admin.social.error_nothing_to_sync'), 'nothing_to_sync');
        }

        if ($resolved) {
            $publication = $this->refreshStatus($publication);
            if ($publication->published_at === null && $publication->status === SocialPublicationStatus::PUBLISHED) {
                $publication->update(['published_at' => now()]);
            }
        }
        $this->log('update', $publication, $user, __('admin.social.log_synced'));

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Send one queued delivery. Called by SendSocialDeliveryJob. The row is claimed first (queued -> processing) inside a lock, so
     * two workers can never send the same delivery; the platform call is made outside the transaction.
     */
    public function deliver(int $deliveryId): void
    {
        $delivery = DB::transaction(function () use ($deliveryId) {
            $row = SocialPublicationChannel::query()->lockForUpdate()->find($deliveryId);
            if ($row === null || $row->status !== SocialChannelStatus::QUEUED) {
                return null;
            }
            $row->update(['status' => SocialChannelStatus::PROCESSING->value, 'attempts' => $row->attempts + 1]);

            return $row;
        });
        if ($delivery === null) {
            return;
        }

        $delivery->load(['publication', 'account']);
        $publication = $delivery->publication;
        $account = $delivery->account;
        $this->refreshStatus($publication);

        $adapter = $this->registry->find($delivery->channel);
        $result = ($adapter === null || $account === null || ! $this->accountUsable($account, $publication))
            ? SocialOperationResult::failed('channel_not_connected', __('admin.social.error_channel_not_connected'))
            : $this->callAdapter(fn () => $adapter->publish($delivery, $account));

        if ($result->success) {
            $delivery->update([
                'status' => SocialChannelStatus::PUBLISHED->value,
                'remote_post_id' => $result->remoteId,
                'remote_url' => $result->remoteUrl,
                'published_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ]);
        } elseif ($result->pending) {
            // Accepted by the platform but not finished (a Zalo article is built asynchronously). Never a failure: the post may
            // already exist, so it stays "processing" and the next synchronization resolves it.
            $delivery->update(['remote_post_id' => $result->remoteId, 'error_code' => null, 'error_message' => $result->message]);
        } else {
            $delivery->update([
                'status' => SocialChannelStatus::FAILED->value,
                'error_code' => $result->errorCode,
                'error_message' => $result->message,
            ]);
        }

        $publication = $this->refreshStatus($publication);
        if ($result->success && $publication->published_at === null) {
            $publication->update(['published_at' => now()]);
        }
    }

    /** Last resort of the job: a delivery stuck in "queued" or "processing" becomes failed, so a person can retry it. */
    public function markDeliveryFailed(int $deliveryId): void
    {
        $delivery = SocialPublicationChannel::query()->find($deliveryId);
        if ($delivery === null || ! in_array($delivery->status, [SocialChannelStatus::QUEUED, SocialChannelStatus::PROCESSING], true)) {
            return;
        }
        $delivery->update([
            'status' => SocialChannelStatus::FAILED->value,
            'error_code' => 'unexpected',
            'error_message' => __('admin.social.error_delivery_unexpected'),
        ]);
        if ($delivery->publication !== null) {
            $this->refreshStatus($delivery->publication);
        }
    }

    /**
     * Mark the eligible deliveries of the publication as queued, in one transaction, and return their ids.
     *
     * @param  SocialChannelStatus[]  $from
     * @return int[]
     *
     * @throws SocialException when no delivery is eligible
     */
    private function queueDeliveries(SocialPublication $publication, array $from, User $user, string $emptyKey = 'error_not_ready'): array
    {
        $ids = DB::transaction(function () use ($publication, $from, $user) {
            $ids = [];
            $fromValues = array_map(fn (SocialChannelStatus $status) => $status->value, $from);
            foreach ($publication->channels()->lockForUpdate()->get() as $delivery) {
                if (! in_array($delivery->status->value, $fromValues, true)) {
                    continue;
                }
                $account = $this->data->connectedAccount($delivery->channel);
                if ($account === null) {
                    throw SocialException::channelNotConnected();
                }
                $delivery->update([
                    'social_account_id' => $account->id,
                    'status' => SocialChannelStatus::QUEUED->value,
                    'error_code' => null,
                    'error_message' => null,
                ]);
                $ids[] = $delivery->id;
            }
            if ($ids !== []) {
                $publication->update(['status' => SocialPublicationStatus::QUEUED->value, 'updated_by' => $user->id]);
            }

            return $ids;
        });
        if ($ids === []) {
            throw new SocialException(__('admin.social.'.$emptyKey), $emptyKey);
        }

        return $ids;
    }

    /** @param int[] $ids */
    private function dispatchDeliveries(array $ids): void
    {
        foreach ($ids as $id) {
            SendSocialDeliveryJob::dispatch($id);
        }
    }

    /** The account must be connected, of the same platform and of the project of the publication. */
    private function accountUsable(SocialAccount $account, SocialPublication $publication): bool
    {
        return $account->isConnected() && $account->project_key === $publication->project_key;
    }

    /** An adapter must never throw; if one does, the cause is logged by class only and the caller gets a safe message. */
    private function callAdapter(\Closure $call): SocialOperationResult
    {
        try {
            return $call();
        } catch (\Throwable $e) {
            Log::error('Social adapter call failed', ['exception' => $e::class]);

            return SocialOperationResult::failed('unexpected', __('admin.social.error_delivery_unexpected'));
        }
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
        // Pictures / video: the list picked in the library; an older form sends a single image_url instead; neither = unchanged.
        if (array_key_exists('media', $input)) {
            $media = SocialMedia::normalize($input['media']);
        } elseif (array_key_exists('image_url', $input)) {
            $media = SocialMedia::normalize([$input['image_url']]);
        } else {
            $media = SocialMedia::normalize($fallback['media'] ?? (filled($fallback['image_url'] ?? null) ? [$fallback['image_url']] : []));
        }
        if (($problem = SocialMedia::violation($media)) !== null) {
            throw new SocialException(__('admin.social.'.$problem, ['max' => SocialMedia::MAX_IMAGES]), $problem);
        }

        return [
            'title' => array_key_exists('title', $input) ? $this->snapshots->plainText($input['title']) : (string) $fallback['title'],
            'message' => array_key_exists('message', $input) ? $this->snapshots->plainText($input['message']) : (string) $fallback['message'],
            // image_url stays the first picture: lists, overview and older rows read it.
            'image_url' => SocialMedia::urls($media, SocialMedia::IMAGE)[0] ?? null,
            'media' => $media,
        ];
    }

    /** What is written in the columns: the media list only once its column exists (before the migration only image_url is kept). */
    private function contentColumns(array $content): array
    {
        if (! $this->data->hasMediaColumn()) {
            unset($content['media']);
        }

        return $content;
    }

    /** Zalo articles are made here with pictures only: a video cannot go to it. */
    private function assertMediaFits(array $content, array $channels): void
    {
        $hasVideo = SocialMedia::urls($content['media'] ?? [], SocialMedia::VIDEO) !== [];
        if ($hasVideo && in_array(SocialChannel::ZALO, $channels, true)) {
            throw new SocialException(__('admin.social.error_media_zalo_video'), 'media_zalo_video');
        }
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
