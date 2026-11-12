<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;
use Modules\CmsBackend\Http\Requests\SocialDirectPublicationRequest;
use Modules\CmsBackend\Http\Requests\SocialPublicationRequest;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Create, list, view, edit, archive, publish, retry, remove and synchronize social publications. The calls to a platform are made
 * by SocialPublicationService (publishing in a queued job); this controller only checks the permission and reports the outcome.
 */
class SocialPublicationController extends SocialBaseController
{
    private const ROUTE = 'cms.dashboard.truyen-thong.';

    public function __construct(SocialPublicationDataProvider $data, private SocialPublicationService $publications)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_VIEW))->only(['index', 'show']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_CREATE))
            ->only(['create', 'store', 'storeDirect', 'edit', 'update', 'archive', 'restore']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH))->only(['publish']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_RETRY))->only(['retry']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_REMOVE))->only(['remove']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_STATISTICS_VIEW))->only(['sync']);
        $this->ensureInstalled();
    }

    /** Step 1 (choose a post) while no post is chosen, steps 2-4 (content, channels, preview) once one is. */
    public function create(Request $request)
    {
        $postId = (int) $request->query('post_id');
        if ($postId <= 0) {
            $categoryId = (int) $request->query('category') ?: null;

            return view('cmsbackend::screens.pages.truyen-thong.create', [
                'step' => 'select',
                'publication' => null,
                'posts' => $this->data->paginateEligiblePosts(trim((string) $request->query('search')), $categoryId),
                'categories' => $this->data->categories(),
                'search' => trim((string) $request->query('search')),
                'category' => $categoryId,
                // The drawer "Tạo trực tiếp": a fresh key per page load, so a double submit never creates two publications.
                'availability' => $this->publications->channelAvailability(),
                'idempotencyKey' => (string) Str::uuid(),
            ]);
        }

        try {
            $prepared = $this->publications->prepare($postId, $request->query('locale'));
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            return redirect(ModuleUrl::route(self::ROUTE.'create'));
        }

        return view('cmsbackend::screens.pages.truyen-thong.create', $prepared + [
            'step' => 'compose',
            'publication' => null,
            'availability' => $this->publications->channelAvailability(),
            'idempotencyKey' => (string) Str::uuid(),
            'projectKey' => $this->publications->projectKey(),
        ]);
    }

    public function store(SocialPublicationRequest $request): RedirectResponse
    {
        try {
            [$publication, $created] = $this->publications->createFromPost($request->user(), $request->validated());
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            return back()->withInput();
        }

        Toastr::success(__($created ? 'admin.social.saved' : 'admin.social.already_saved'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $publication->id));
    }

    /** A publication written directly in the drawer of the create page (no source post). */
    public function storeDirect(SocialDirectPublicationRequest $request): RedirectResponse
    {
        try {
            [$publication, $created] = $this->publications->createDirect($request->user(), $request->validated());
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            // The form carries direct=1, so the page reopens the drawer with what was typed.
            return redirect(ModuleUrl::route(self::ROUTE.'create'))->withInput();
        }

        Toastr::success(__($created ? 'admin.social.saved' : 'admin.social.already_saved'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $publication->id));
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status');
        $channel = (string) $request->query('channel');
        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => SocialPublicationStatus::tryFrom($status)?->value ?? '',
            'channel' => SocialChannel::tryFrom($channel)?->value ?? '',
        ];

        return view('cmsbackend::screens.pages.truyen-thong.index', [
            'publications' => $this->data->paginatePublications($filters),
            'filters' => $filters,
            'can' => $this->abilities(),
        ]);
    }

    public function show(int $id)
    {
        return view('cmsbackend::screens.pages.truyen-thong.show', [
            'publication' => $this->findOrFail($id),
            'can' => $this->abilities(),
        ]);
    }

    public function edit(int $id)
    {
        $publication = $this->findOrFail($id);
        if (! $publication->status->editable()) {
            $this->flashError(__('admin.social.error_not_editable'));

            return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
        }

        return view('cmsbackend::screens.pages.truyen-thong.create', [
            'step' => 'compose',
            'publication' => $publication,
            'post' => $publication->post,
            'locale' => $publication->locale,
            'locales' => [$publication->locale],
            'snapshot' => [
                'title' => $publication->title, 'message' => $publication->message,
                'image_url' => $publication->image_url, 'public_url' => $publication->public_url,
                'media' => array_map(fn (array $item) => $item['url'], $publication->mediaItems()),
            ],
            'availability' => $this->publications->channelAvailability(),
            'idempotencyKey' => null,
            'projectKey' => $publication->project_key,
        ]);
    }

    public function update(SocialPublicationRequest $request, int $id): RedirectResponse
    {
        $publication = $this->findOrFail($id);

        try {
            $this->publications->updateDraft($request->user(), $publication, $request->validated());
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            return back()->withInput();
        }

        Toastr::success(__('admin.social.updated'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
    }

    public function archive(Request $request, int $id): RedirectResponse
    {
        return $this->transition($id, fn (SocialPublication $publication) => $this->publications->archive($request->user(), $publication), 'admin.social.archived');
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        return $this->transition($id, fn (SocialPublication $publication) => $this->publications->restore($request->user(), $publication), 'admin.social.restored');
    }

    public function publish(Request $request, int $id): RedirectResponse
    {
        return $this->transition($id, fn (SocialPublication $publication) => $this->publications->publish($request->user(), $publication), 'admin.social.publish_queued');
    }

    public function retry(Request $request, int $id): RedirectResponse
    {
        return $this->transition($id, fn (SocialPublication $publication) => $this->publications->retry($request->user(), $publication), 'admin.social.retry_queued');
    }

    public function remove(Request $request, int $id): RedirectResponse
    {
        return $this->report($id, fn (SocialPublication $publication) => $this->publications->remove($request->user(), $publication), 'removed', 'admin.social.removed_count');
    }

    public function sync(Request $request, int $id): RedirectResponse
    {
        return $this->report($id, fn (SocialPublication $publication) => $this->publications->sync($request->user(), $publication), 'synced', 'admin.social.synced_count');
    }

    /**
     * Run an action that answers with a count and a list of per-channel problems: success toast for the count, one error toast
     * per problem.
     */
    private function report(int $id, \Closure $action, string $countKey, string $successKey): RedirectResponse
    {
        try {
            $result = $action($this->findOrFail($id));
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
        }

        if ((int) $result[$countKey] > 0) {
            Toastr::success(__($successKey, ['count' => (int) $result[$countKey]]), __('admin.message.thanh_cong'));
        }
        foreach ($result['errors'] as $message) {
            $this->flashError($message);
        }

        return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
    }

    private function transition(int $id, \Closure $action, string $successKey): RedirectResponse
    {
        try {
            $action($this->findOrFail($id));
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
        }

        Toastr::success(__($successKey), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'publications.show', $id));
    }

    /** A publication of the active project, or 404. */
    private function findOrFail(int $id): SocialPublication
    {
        return $this->data->find($id) ?? abort(404);
    }
}
