<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Enums\PermissionsEnum;
use App\Enums\PostType;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Support\ModuleUrl;

/** Video library (post type "video"). Same address and route names (/cms/video, video.*) as the old shared-framework screen. */
class VideoController extends AbstractPostController
{
    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider)
    {
        $this->middleware(function ($request, $next) use ($postDataProvider, $taxonomyDataProvider, $tagDataProvider) {
            $user = Auth::user();
            $permissionsBase = [
                'canView' => $user->hasAnyPermission([PermissionsEnum::POST_VIEW->label()]),
                'canApprove' => $user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canReject' => $user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canUpdate' => $user->hasAnyPermission([PermissionsEnum::POST_UPDATE->label()]),
                'canSaveDraft' => $user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canSubmitForApproval' => $user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canPublishNow' => $user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
                'canStopPublishing' => $user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
            ];
            parent::__construct($postDataProvider, $taxonomyDataProvider, $tagDataProvider, PostType::VIDEO, '_partials.video.table-video', __('video'), 'pages.video', 'video.index', $permissionsBase);

            return $next($request);
        });
    }

    public function store(PostUpdateRequest $request)
    {
        // Same title check as before: a video with an existing title is refused.
        if (Post::where('title', $request->title)->exists()) {
            return back()->withErrors(['title' => __('Tiêu đề bài viết đã tồn tại')]);
        }

        DB::transaction(function () use ($request) {
            $this->save($request);
        });

        return redirect()->to(ModuleUrl::route('video.index'));
    }
}
