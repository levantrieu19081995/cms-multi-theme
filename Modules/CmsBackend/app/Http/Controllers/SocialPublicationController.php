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
use Modules\CmsBackend\Http\Requests\SocialPublicationRequest;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Support\ModuleUrl;

/** Create, list, view, edit and archive social publications. Nothing here contacts a social platform (Phase 1). */
class SocialPublicationController extends SocialBaseController
{
    private const ROUTE = 'cms.dashboard.truyen-thong.';

    public function __construct(SocialPublicationDataProvider $data, private SocialPublicationService $publications)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_VIEW))->only(['index', 'show']);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_CREATE))
            ->only(['create', 'store', 'edit', 'update', 'archive', 'restore']);
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
