<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\DataProviders\TaxonomyDataProvider;
use App\DataProviders\ImageLibraryDataProvider;
use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\Enums\PermissionsEnum;
use App\Enums\PostFormat;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use App\Facades\Toastr;
use App\Http\Requests\ImageLibrary\ImageLibraryUpdateRequest;
use App\Http\Requests\Post\PostUpdateRequest;
use App\Models\Feedback;
use App\Models\Post;
use App\Traits\PostHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImageLibraryController extends AbstractPostController
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
            parent::__construct(
                $postDataProvider,
                $taxonomyDataProvider,
                $tagDataProvider,
                PostType::IMAGE,
                '_partials.thu-vien-hinh-anh.table-image',
                __('admin.resource.image_library'),
                'pages.thu-vien-hinh-anh',
                'hinh-anh.index',
                $permissionsBase
            );
            return $next($request);
        });
    }


    public function store(PostUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $post = $this->save($request);
        });

        return redirect()->route('hinh-anh.index');
    }
}
