<?php

namespace App\Http\Controllers;

use App\DataProviders\PostDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Enums\PostStatus;
use App\Enums\PublishType;
use App\Models\Post;
use Illuminate\Support\Str;
use App\DataProviders\TaxonomyDataProvider;
use App\DataProviders\TagDataProvider;
use App\Enums\PermissionsEnum;
use App\Enums\PostFormat;
use App\Enums\PostType;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Facades\Toastr;
use App\Http\Requests\Post\PostCreateRequest;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Feedback;
use App\Models\Taxonomies;
use App\Models\Taxonomy;
use App\Models\Terms;
use App\Traits\PostHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PostController extends AbstractPostController
{
    private $user;
    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider)
    {
        $this->middleware(function ($request, $next) use ($postDataProvider, $taxonomyDataProvider, $tagDataProvider) {
            $this->user = Auth::user();
            $permissionsBase = [
                'canView' =>   $this->user->hasAnyPermission([PermissionsEnum::POST_VIEW->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canReject' => $this->user->hasAnyPermission([PermissionsEnum::POST_APPROVE->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::POST_UPDATE->label()]),
                'canSaveDraft' => $this->user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canSubmitForApproval' => $this->user->hasAnyPermission([PermissionsEnum::POST_CREATE->label()]),
                'canPublishNow' => $this->user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
                'canStopPublishing' => $this->user->hasAnyPermission([PermissionsEnum::POST_PUBLISHING->label()]),
            ];
            $isEditor = $this->user->can(PermissionsEnum::POST_APPROVE->label());
            $isAuthor = $this->user->can(PermissionsEnum::POST_CREATE->label());
            parent::__construct(
                $postDataProvider,
                $taxonomyDataProvider,
                $tagDataProvider,
                PostType::POST,
                '_partials.bai-viet.table-bai-viet',
                __('bài viết'),
                'pages.bai-viet',
                'bai-viet.index',
                $permissionsBase,
                $isEditor,
                $isAuthor
            );

            return $next($request);
        });
        $this->middleware('check.permission:' . implode(',', [
            PermissionsEnum::POST_VIEW->label(),
            PermissionsEnum::POST_CREATE->label(),
            PermissionsEnum::POST_UPDATE->label(),
            PermissionsEnum::POST_APPROVE->label(),
            PermissionsEnum::POST_PUBLISHING->label()
        ]))->only(['index']);

        $this->middleware('check.permission:' . PermissionsEnum::POST_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('check.permission:' . PermissionsEnum::POST_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('check.permission:' . PermissionsEnum::POST_DELETE->label())
            ->only(['destroy']);
    }
    public function store(PostUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $post = $this->save($request);
        });

        return redirect()->route('bai-viet.index');
    }
}
