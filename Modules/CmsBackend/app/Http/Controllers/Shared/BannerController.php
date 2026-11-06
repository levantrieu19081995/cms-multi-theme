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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BannerController extends AbstractPostController
{
    private $user;
    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider)
    {
        $this->middleware(function ($request, $next) use ($postDataProvider, $taxonomyDataProvider, $tagDataProvider) {
            $this->user = Auth::user();
            // dd($this->user);



            $permissionsBase = [
                'canView' =>   $this->user->hasAnyPermission([PermissionsEnum::BANNER_VIEW->label()]),
                'canApprove' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_APPROVE->label()]),
                'canReject' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_APPROVE->label()]),
                'canUpdate' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_UPDATE->label()]),
                'canSaveDraft' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_CREATE->label()]),
                'canSubmitForApproval' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_CREATE->label()]),
                'canPublishNow' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_PUBLISHING->label()]),
                'canStopPublishing' => $this->user->hasAnyPermission([PermissionsEnum::BANNER_PUBLISHING->label()]),
            ];

            $listPer = [
                PermissionsEnum::BANNER_APPROVE->label(),
                PermissionsEnum::BANNER_PUBLISHING->label(),
                PermissionsEnum::BANNER_MANAGER->label(),
            ];
            $isEditor = false;

            foreach ($listPer as $permission) {
                if ($this->user->can($permission)) {
                    $isEditor = true;
                    break; // Dừng lại ngay khi phát hiện người dùng có quyền
                }
            }
            $isAuthor = $this->user->can(PermissionsEnum::BANNER_CREATE->label());
            parent::__construct(
                $postDataProvider,
                $taxonomyDataProvider,
                $tagDataProvider,
                PostType::BANNER,
                '_partials.banner.table-banner',
                __('banner'),
                'pages.banner',
                'banner.index',
                $permissionsBase,
                $isEditor,
                $isAuthor
            );



            return $next($request);
        });

        $this->middleware('cms.check.permission:' . implode(',', [
            PermissionsEnum::BANNER_VIEW->label(),
            PermissionsEnum::BANNER_CREATE->label(),
            PermissionsEnum::BANNER_UPDATE->label(),
            PermissionsEnum::BANNER_APPROVE->label(),
            PermissionsEnum::BANNER_PUBLISHING->label()
        ]))->only(['index']);

        $this->middleware('cms.check.permission:' . PermissionsEnum::BANNER_CREATE->label())
            ->only(['create', 'store']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::BANNER_UPDATE->label())
            ->only(['edit', 'update']);
        $this->middleware('cms.check.permission:' . PermissionsEnum::BANNER_DELETE->label())
            ->only(['destroy']);
    }

    private $permissionsBase = [
        'canView' => false,
        'canApprove' => false,
        'canReject' => false,
        'canUpdate' => false,
        'canSaveDraft' => false,
        'canSubmitForApproval' => false,
        'canPublishNow' => false,
        'canStopPublishing' => false,
    ];

    private function checkPermission($post)
    {
        // Kiểm tra điều kiện hiển thị nút Duyệt
        $user = auth()->user();
        //Kiểm tra quyền xem
        if ((($user->can(PermissionsEnum::POST_VIEW->label()) || $user->can(PermissionsEnum::POST_CREATE->label())) && $post->author_id == $user->id)
            || ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label()))
        ) {
            $this->permissionsBase['canView'] = true;
        }
        //Đối với trạng thái Nháp
        if ($post->status == PostStatus::DRAFT->value || $post->status == null) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && ($post->author_id == $user->id || $post->author_id == null)
            ) {
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canSubmitForApproval'] = true;
            }
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canPublishNow'] = true;
            }
        }
        //Đối với trạng thái Chờ duyệt
        elseif ($post->status == PostStatus::PENDING_APPROVAL->value) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                $this->permissionsBase['canSaveDraft'] = false;
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canApprove'] = true;
                $this->permissionsBase['canReject'] = true;
            }
        }
        //Đối với trạng thái Đã duyệt
        elseif ($post->status == PostStatus::APPROVED->value) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = false;
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canPublishNow'] = true;
            }
        }
        //Đối với trạng thái Đã xuất bản
        elseif ($post->status == PostStatus::PUBLISHED->value) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && $post->author_id == $user->id
            ) {
                //Không thể thao tác khi bài viết đã được xuất bản
            } //Biên tập viên
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canStopPublishing'] = true;
            }
        }
        //Đối với trạng thái Không duyệt
        elseif ($post->status == PostStatus::NOT_APPROVED->value) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                //    $permissions['canUpdate'] = true;
                //    $permissions['canStopPublishing'] = true;
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = false;
            }
        }
        //Đối với trạng thái dừng xuất bản
        elseif ($post->status == PostStatus::STOP_PUBLISHING->value) {
            //Tác giả của bài viết đó
            if (
                $user->can(PermissionsEnum::POST_CREATE->label())
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canPublishNow'] = true;
            }
        }
    }

    public function store(PostUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $post = $this->save($request);
            if ($request->has('ref_id')) {
                //Kiểm tra bài viết
                //$baiVietLink = $this->postDataProvider->findByIdSimple($request->ref_id);
                $post->content = $request->input('ref_id');
                $post->save();
            }
        });

        return redirect()->route('banner.index');
    }

    public function edit($id)
    {
        $parentEdit = parent::edit($id);

        $banner = $parentEdit->getData();
        $idPost = intval($banner['post']['content']);
        try {
            $post =  $this->postDataProvider->findByIdSimple($idPost, PostType::POST->value);
            $ref_id = $post->content;
            $title = $post->title;
        } catch (\Exception) {
            $ref_id = null;
            $title = null;
        }

        return view('pages.banner.edit', array_merge($parentEdit->getData(), compact('ref_id', 'title')));
    }

    public function update(PostUpdateRequest $request, $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id) {
            $parentUpdate = parent::update($request, $id);
            $post =  $this->postDataProvider->findByIdSimple($id, PostType::BANNER->value);
            if ($request->has('ref_id')) {
                //Kiểm tra bài viết 
                //$baiVietLink = $this->postDataProvider->findByIdSimple($request->ref_id);
                $post->content = $request->input('ref_id');
                $post->save();
            }
        });
        return redirect()->back();
    }
    public function viewBanner($postId)
    {

        $post = $this->postDataProvider->findByIdSimple($postId);
        if ($post) {
            return redirect()->route('tin-tuc.chiTietTinTuc', $post->slug);
        } else {
            return redirect()->route('frontpage.home');
        }
    }

    // use PostHelper;
    // protected ImageLibraryDataProvider $imageLibraryDataProvider;
    // public function __construct(ImageLibraryDataProvider $imageLibraryDataProvider)
    // {
    //     $this->imageLibraryDataProvider = $imageLibraryDataProvider;
    // }

    // private $permissionsBase = [
    //     'canView' => false,
    //     'canApprove' => false,
    //     'canReject' => false,
    //     'canUpdate' => false,
    //     'canSaveDraft' => false,
    //     'canSubmitForApproval' => false,
    //     'canPublishNow' => false,
    //     'canStopPublishing' => false,
    // ];
    // private function checkPermission($post)
    // {
    //     // Kiểm tra điều kiện hiển thị nút Duyệt
    //     $user = auth()->user();
    //     //Kiểm tra quyền xem
    //     if ((($user->can(PermissionsEnum::POST_VIEW->label()) || $user->can(PermissionsEnum::POST_CREATE->label())) && $post->author_id == $user->id)
    //         || ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label()))
    //     ) {
    //         $this->permissionsBase['canView'] = true;
    //     }
    //     //Đối với trạng thái Nháp
    //     if ($post->status == PostStatus::DRAFT->value || $post->status == null) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && ($post->author_id == $user->id || $post->author_id == null)
    //         ) {
    //             $this->permissionsBase['canSaveDraft'] = true;
    //             $this->permissionsBase['canSubmitForApproval'] = true;
    //         }
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             $this->permissionsBase['canSubmitForApproval'] = false;
    //             $this->permissionsBase['canSaveDraft'] = true;
    //             $this->permissionsBase['canPublishNow'] = true;
    //         }
    //     }
    //     //Đối với trạng thái Chờ duyệt
    //     elseif ($post->status == PostStatus::PENDING_APPROVAL->value) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && $post->author_id == $user->id
    //         ) {
    //             $this->permissionsBase['canUpdate'] = true;
    //             $this->permissionsBase['canSaveDraft'] = true;
    //         } //Biên tập viên
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             $this->permissionsBase['canSaveDraft'] = false;
    //             $this->permissionsBase['canUpdate'] = true;
    //             $this->permissionsBase['canApprove'] = true;
    //             $this->permissionsBase['canReject'] = true;
    //         }
    //     }
    //     //Đối với trạng thái Đã duyệt
    //     elseif ($post->status == PostStatus::APPROVED->value) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && $post->author_id == $user->id
    //         ) {
    //             $this->permissionsBase['canSubmitForApproval'] = true;
    //             $this->permissionsBase['canSaveDraft'] = true;
    //         } //Biên tập viên
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             $this->permissionsBase['canSubmitForApproval'] = false;
    //             $this->permissionsBase['canSaveDraft'] = false;
    //             $this->permissionsBase['canUpdate'] = true;
    //             $this->permissionsBase['canPublishNow'] = true;
    //         }
    //     }
    //     //Đối với trạng thái Đã xuất bản
    //     elseif ($post->status == PostStatus::PUBLISHED->value) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && $post->author_id == $user->id
    //         ) {
    //             //Không thể thao tác khi bài viết đã được xuất bản
    //         } //Biên tập viên
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             $this->permissionsBase['canUpdate'] = true;
    //             $this->permissionsBase['canStopPublishing'] = true;
    //         }
    //     }
    //     //Đối với trạng thái Không duyệt
    //     elseif ($post->status == PostStatus::NOT_APPROVED->value) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && $post->author_id == $user->id
    //         ) {
    //             $this->permissionsBase['canSubmitForApproval'] = true;
    //             $this->permissionsBase['canSaveDraft'] = true;
    //         } //Biên tập viên
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             //    $permissions['canUpdate'] = true;
    //             //    $permissions['canStopPublishing'] = true;
    //             $this->permissionsBase['canSubmitForApproval'] = false;
    //             $this->permissionsBase['canSaveDraft'] = false;
    //         }
    //     }
    //     //Đối với trạng thái dừng xuất bản
    //     elseif ($post->status == PostStatus::STOP_PUBLISHING->value) {
    //         //Tác giả của bài viết đó
    //         if (
    //             $user->can(PermissionsEnum::POST_CREATE->label())
    //             && $post->author_id == $user->id
    //         ) {
    //             $this->permissionsBase['canSubmitForApproval'] = true;
    //             $this->permissionsBase['canSaveDraft'] = true;
    //         } //Biên tập viên
    //         if ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label())) {
    //             $this->permissionsBase['canUpdate'] = true;
    //             $this->permissionsBase['canPublishNow'] = true;
    //         }
    //     }
    // }
    // /**
    //  * Display a listing of the resource.
    //  */
    // public function index(Request $request)
    // {
    //     $pageSize = $request->pageSize ?? 20;
    //     $sortBy = $request->sortBy ?? 'id';
    //     $order = $request->order ?? 'desc';
    //     $filterPostStatuses = $request->getBase64Param('postStatuses') ?? [];
    //     $filterAuthorIds = [];
    //     $filter = trim(strip_tags($request->query('filter')));
    //     $filterAuthorIds = $my_array = array(auth()->user()->id);
    //     $imgs = $this->imageLibraryDataProvider->paginate($filterPostStatuses, $filterAuthorIds, $filter, $sortBy, $order, $pageSize);
    //     $imgs->appends($request->all());
    //     $filterPostStatuses = collect($filterPostStatuses);
    //     $postStatuses = [];
    //     foreach (PostStatus::cases() as $postStatus) {
    //         $postStatuses[] = [
    //             'enum' => $postStatus,
    //             'selected' => $filterPostStatuses->some(function ($status) use ($postStatus) {
    //                 return $status == $postStatus->value;
    //             })
    //         ];
    //     }

    //     if ($request->ajax()) {
    //         return view('_partials.thu-vien-hinh-anh.table-image', compact(['imgs', 'filter', 'postStatuses']));
    //     }
    //     return view('pages.thu-vien-hinh-anh.index', compact(['imgs',  'postStatuses']));
    // }

    // /**
    //  * Show the form for creating a new resource.
    //  */
    // public function create()
    // {
    //     //Lấy loại xuất bản
    //     $publishType = PublishType::toObject();

    //     $postStatus = PostStatus::toObject();

    //     $post = new Post();
    //     $user = auth()->user();
    //     $this->checkPermission($post);
    //     $permissions = $this->permissionsBase;
    //     // $u = $user->givePermissionTo('Thêm bài viết');
    //     $post->status = PostStatus::DRAFT->value;
    //     $post->post_type = PostType::IMAGE->value;
    //     $post->format = PostFormat::IMAGE->value;
    //     $post->order = 0;
    //     $titleName = __('Thêm mới thư viện hình ảnh');
    //     $pages = $this->imageLibraryDataProvider->getPageHierarchy(0);
    //     $pageOptions = $this->renderPageOptions($pages);
    //     return view('pages.bai-viet.edit', compact(['postStatus', 'publishType', 'post', 'user', 'permissions', 'titleName', 'pageOptions']));
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(ImageLibraryUpdateRequest $request)
    // {

    //     DB::transaction(function () use ($request) {
    //         // Lưu bài viết vào cơ sở dữ liệu
    //         $post = new Post;
    //         $post->fill($request->all());
    //         $post->post_type = PostType::IMAGE->value;
    //         //render slug theo tiêu đề
    //         $slug = $this->checkUniqueSlug($post->slug);
    //         $post->slug = $slug;
    //         $post->author_id = auth()->user()->id;
    //         $post->format =PostFormat::IMAGE->value;
    //         $post->show_home_page = $this->showHomePageConvert($request->show_home_page);
    //         //Kiểm tra loại xuất bản
    //         if ($post->publish_type != PublishType::SCHEDULE_PUBLICATION->value) {
    //             $post->published_at = null;
    //             $post->publication_date_expires = null;
    //         }
    //         $now_ = now();

    //         $this->checkPermission($post);
    //         if ($request->input('action') === 'submit_for_approval') {
    //             if ($this->permissionsBase['canSubmitForApproval']) {
    //                 $post->status = PostStatus::PENDING_APPROVAL->value;
    //                 $post->save();
    //                 Toastr::success(__('Trình duyệt thành công'));
    //             }
    //         } else if ($request->input('action') === 'save_draft') {
    //             if ($this->permissionsBase['canSaveDraft']) {
    //                 $post->status = PostStatus::DRAFT->value;
    //                 $post->save();

    //                 Toastr::success(__('Lưu thành công'));
    //             }
    //         } else if ($request->input('action') === 'publish_now') {
    //             if ($this->permissionsBase['canPublishNow']) {
    //                 $post->status = PostStatus::PUBLISHED->value;
    //                 $post->published_at = $now_;
    //                 $post->publisher_id = auth()->user()->id;
    //                 $post->save();
    //                 Toastr::success(__('Xuất bản thành công'));
    //             } else {
    //                 //Không có quyền xuất bản
    //             }
    //         } elseif ($request->input('action') === 'submit') {
    //             if ($this->permissionsBase['canUpdate']) {
    //                 $post->save();
    //                 Toastr::success(__('Lưu thành công'));
    //             } else {
    //                 //Không có quyền lưu
    //             }
    //         }
    //         if ($request->has('images')) {
    //             // Lưu các ảnh vào Medialibrary
    //             $imagePaths = explode(',', $request->images); // Dữ liệu từ input hidden 'thumbnail'
    //             foreach ($imagePaths as $imagePath) {
    //                 if ($imagePath !== '') {
    //                     $post->addMediaFromUrl($imagePath) // Đường dẫn file
    //                         ->toMediaCollection('images'); // Lưu vào bộ sưu tập 'images'
    //                 }
    //             }
    //         }
    //     });
    //     return redirect()->route('hinh-anh.index');
    // }
    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit($id)
    // {

    //     $post = $this->imageLibraryDataProvider->findById($id);
    //     $this->checkPermission($post);
    //     $permissions = $this->permissionsBase;
    //     if ($permissions['canView'] == false) {
    //         return view('pages.misc.pages-misc-not-authorized');
    //     }
    //     $publishType = PublishType::toObject();
    //     $postStatus = PostStatus::toObject();
    //     // Lấy feedback mới nhất
    //     $feedback = null;
    //     if (auth()->user()->id == $post->author->id) {
    //         $feedback = Feedback::where('post_id', $post->id)->whereNull('deleted_at')->latest()->first();
    //     }
    //     $titleName = __('Cập nhật thư viện hình ảnh');
    //     $pages = $this->imageLibraryDataProvider->getPageHierarchy($post->id);
    //     $pageOptions = $this->renderPageOptions($pages, '', $post->post_parent);

    //     $thumb = implode(',', $post->getMedia('images')->map(fn($media) => $media->getUrl())->toArray());
    //     return view('pages.bai-viet.edit', compact(['post', 'postStatus', 'publishType', 'permissions', 'feedback', 'titleName', 'pageOptions', 'thumb']));
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(ImageLibraryUpdateRequest $request, $id): RedirectResponse
    // {
    //     DB::transaction(function () use ($request, $id) {
    //         $post =  $this->imageLibraryDataProvider->findById($id);
    //         //dd($request);
    //         $post->fill($request->all());
    //         //KHởi tại lại slug
    //         $slug = $this->checkUniqueSlug($post->slug, $id);
    //         $post->slug = $slug;
    //         //Kiểm tra loại xuất bản
    //         if ($post->publish_type != PublishType::SCHEDULE_PUBLICATION->value) {
    //             $post->published_at = null;
    //             $post->publication_date_expires = null;
    //         }
    //         $now_ = now();
    //         $post->show_home_page = $this->showHomePageConvert($request->show_home_page);
    //         $this->checkPermission($post);
    //         Toastr::success(__('Lưu thành công'));
    //         if ($request->input('action') === 'approved') {
    //             if ($this->permissionsBase['canApprove']) {
    //                 $now_ =  now();
    //                 $post->status = PostStatus::APPROVED->value;
    //                 $post->approved_at =  $now_;
    //                 if ($post->publish_type == PublishType::PUBLISH_NOW->value) {
    //                     $post->published_at = $now_;
    //                 }
    //                 $post->publisher_id = auth()->user()->id;
    //                 $post->save();
    //                 Toastr::success(__('Phê duyệt thành công'));
    //             } else {
    //                 //Không có quyền duyệt
    //             }
    //         } else if ($request->input('action') === 'reject') {
    //             if ($this->permissionsBase['canReject']) {
    //                 $post->status = PostStatus::NOT_APPROVED->value;
    //                 $post->rejected_at =  $now_;
    //                 $post->save();
    //                 // Lưu feedback vào cơ sở dữ liệu
    //                 Feedback::create([
    //                     'post_id' => $post->id,
    //                     'feedback' => $request->input('feedback'),
    //                     'user_id' => auth()->user()->id,  // Lưu thông tin người gửi feedback
    //                 ]);
    //                 Toastr::success(__('Không duyệt & gửi feedback thành công'));
    //             }
    //         } else if ($request->input('action') === 'submit_for_approval') {
    //             if ($this->permissionsBase['canSubmitForApproval']) {
    //                 $post->status = PostStatus::PENDING_APPROVAL->value;
    //                 $post->save();
    //                 $fb = Feedback::where('post_id', $post->id)->latest()->first();
    //                 if ($fb) {
    //                     $fb->deleted_at = $now_;
    //                     $fb->save();
    //                 }
    //                 Toastr::success(__('Trình duyệt thành công'));
    //             }
    //         } else if ($request->input('action') === 'save_draft') {
    //             if ($this->permissionsBase['canSaveDraft']) {
    //                 $post->status = PostStatus::DRAFT->value;
    //                 $post->save();
    //                 $fb = Feedback::where('post_id', $post->id)->latest()->first();
    //                 if ($fb) {
    //                     $fb->deleted_at = $now_;
    //                     $fb->save();
    //                 }
    //                 Toastr::success(__('Lưu thành công'));
    //             }
    //         } else if ($request->input('action') === 'publish_now') {
    //             if ($this->permissionsBase['canPublishNow']) {
    //                 $post->status = PostStatus::PUBLISHED->value;
    //                 $post->published_at = $now_;
    //                 $post->publisher_id = auth()->user()->id;
    //                 $post->save();
    //                 Toastr::success(__('Xuất bản thành công'));
    //             } else {
    //                 //Không có quyền xuất bản
    //             }
    //         } elseif ($request->input('action') === 'submit') {
    //             if ($this->permissionsBase['canUpdate']) {
    //                 $post->save();
    //                 Toastr::success(__('Lưu thành công'));
    //             } else {
    //                 //Không có quyền lưu
    //             }
    //         } elseif ($request->input('action') === 'stop_publishing') {
    //             if ($this->permissionsBase['canStopPublishing']) {
    //                 $post->status = PostStatus::STOP_PUBLISHING->value;
    //                 $post->save();
    //                 Toastr::success(__('Lưu thành công'));
    //             } else {
    //                 //Không có quyền lưu
    //             }
    //         }
    //         if ($request->input('action') !== 'reject') {
    //             if ($request->has('images')) {
    //                 $imagePaths = explode(',', $request->images); // Dữ liệu từ input hidden 'thumbnail'
    //                 // Xóa các hình ảnh cũ nếu không còn trong danh sách mới
    //                 $allMedia = $post->getMedia('images');
    //                 // Lọc các media mà URL của chúng có trong $imagePaths
    //                 $mediaToKeep = $allMedia->filter(function ($media) use ($imagePaths) {
    //                     return in_array($media->getUrl(), $imagePaths);
    //                 });
    //                 // Chuyển $mediaToKeep thành mảng để truyền vào clearMediaCollectionExcept
    //                 $post->clearMediaCollectionExcept('images', $mediaToKeep->toArray());
    //                 // Thêm các hình ảnh mới vào nếu chưa tồn tại trong collection
    //                 foreach ($imagePaths as $imagePath) {
    //                     // Kiểm tra nếu media với URL cụ thể chưa tồn tại
    //                     if (!$mediaToKeep->contains(function ($media) use ($imagePath) {
    //                         return $media->getUrl() === $imagePath;
    //                     })) {
    //                         // Thêm ảnh mới vào collection 'images'
    //                         $post->addMediaFromUrl($imagePath)->toMediaCollection('images');
    //                     }
    //                 }
    //             }
    //         }
    //     });
    //     return redirect()->back();
    // }
    // public function destroy($id)
    // {
    //     $post = $this->imageLibraryDataProvider->findById($id);
    //     $post->deleted_at = now();
    //     $post->save();
    //     return response()->json([
    //         'status' => 1,
    //         'message' => 'Xóa thành công'
    //     ]);
    // }

    // private function renderPageOptions($pages, $indent = '', $selectedParentId = -1)
    // {
    //     $options = '';
    //     foreach ($pages as $page) {
    //         // Determine if this page is the selected parent
    //         $selected = ($page->id === $selectedParentId) ? 'selected' : '';

    //         // Add the option for the current page
    //         $options .= '<option value="' . $page->id . '" ' . $selected . '>' . $indent . htmlspecialchars($page->title) . '</option>';
    //         // Check if the page has children and recursively render their options
    //         if (!empty($page->children)) {
    //             // Recursively render child pages with increased indentation
    //             $options .= $this->renderPageOptions($page->children, $indent . '&nbsp;&nbsp;&nbsp;', $selectedParentId);
    //         }
    //     }
    //     return $options;
    // }
}
