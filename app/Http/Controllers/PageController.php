<?php

namespace App\Http\Controllers;

use App\DataProviders\TaxonomyDataProvider;
use App\DataProviders\PageDataProvider;
use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\Enums\PermissionsEnum;
use App\Enums\PostFormat;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use App\Facades\Toastr;
use App\Http\Requests\Page\PageCreateRequest;
use App\Http\Requests\Page\PageUpdateRequest;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Feedback;
use App\Models\Post;
use App\Traits\PostHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PageController extends AbstractPostController
{
    private $user;
    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider)
    {
        $this->middleware(function ($request, $next) use ($postDataProvider, $taxonomyDataProvider, $tagDataProvider) {
            $this->user = Auth::user();
            $permissionsBase = [
                'canView' =>   $this->user->hasAnyPermission([PermissionsEnum::PAGE_VIEW->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_APPROVE->label()]),
                'canReject' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_APPROVE->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_UPDATE->label()]),
                'canSaveDraft' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_CREATE->label()]),
                'canSubmitForApproval' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_CREATE->label()]),
                'canPublishNow' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_PUBLISHING->label()]),
                'canStopPublishing' => $this->user->hasAnyPermission([PermissionsEnum::PAGE_PUBLISHING->label()]),
            ];

            $listPer = [
                PermissionsEnum::PAGE_APPROVE->label(),
                PermissionsEnum::PAGE_PUBLISHING->label(),
                PermissionsEnum::PAGE_MANAGER->label(),
            ];
            $isEditor = false;

            foreach ($listPer as $permission) {
                if ($this->user->can($permission)) {
                    $isEditor = true;
                    break; // Dừng lại ngay khi phát hiện người dùng có quyền
                }
            }
            $isAuthor = $this->user->can(PermissionsEnum::PAGE_CREATE->label());
            parent::__construct(
                $postDataProvider,
                $taxonomyDataProvider,
                $tagDataProvider,
                PostType::PAGE,
                '_partials.trang.table-trang',
                __('trang'),
                'pages.trang',
                'trang.index',
                $permissionsBase,
                $isEditor,
                $isAuthor
            );

            return $next($request);
        });
        $this->middleware('check.permission:' . implode(',', [
            PermissionsEnum::PAGE_VIEW->label(),
            PermissionsEnum::PAGE_CREATE->label(),
            PermissionsEnum::PAGE_UPDATE->label(),
            PermissionsEnum::PAGE_APPROVE->label(),
            PermissionsEnum::PAGE_PUBLISHING->label()
        ]))->only(['index']);

        $this->middleware('check.permission:' . PermissionsEnum::PAGE_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('check.permission:' . PermissionsEnum::PAGE_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('check.permission:' . PermissionsEnum::PAGE_DELETE->label())
            ->only(['destroy']);
    }


    public function store(PostUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $post = $this->save($request);
        });

        return redirect()->route('trang.index');
    }
}
