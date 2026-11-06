<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;


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
use App\Enums\FileTypeEnum;
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
use Illuminate\Support\Facades\Http;
use Modules\CmsBackend\DataProviders\UserTermPermissionDataProvider;
use Carbon\Carbon;
// use Modules\CmsBackend\Enums\PermissionsEnum;
use App\Enums\PermissionsEnum;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PostsExport;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Database\Eloquent\Builder;

use App\Services\ActivityLogger;

abstract class AbstractPostController extends Controller
{

    use PostHelper;
    private $postType;
    private string $partialName;
    private string $formName;
    private string $viewName;
    private string $routeName;
    protected PostDataProvider $postDataProvider;
    protected TaxonomyDataProvider $taxonomyDataProvider;
    protected TagDataProvider $tagDataProvider;
    private $permissionsBase = [];
    private $isEditor;
    private $isAuthor;
    /** Name of the content type shown in screen titles (translated; formName stays the Vietnamese label used by the activity log). */
    private function displayName(): string
    {
        $key = match ($this->postType) {
            PostType::POST => 'news',
            PostType::EVENT => 'event',
            PostType::PAGE => 'page',
            PostType::BANNER => 'banner',
            PostType::IMAGE => 'image_library',
            default => null,
        };

        return $key ? __('admin.resource.'.$key) : $this->formName;
    }

    public function __construct(PostDataProvider $postDataProvider, TaxonomyDataProvider $taxonomyDataProvider, TagDataProvider $tagDataProvider, PostType $postType, string $partialName, string $formName, string $viewName, string $routeName, $permission = [], $isEditor =  false, $isAuthor = false)
    {
        $this->postDataProvider = $postDataProvider;
        $this->taxonomyDataProvider = $taxonomyDataProvider;
        $this->tagDataProvider = $tagDataProvider;
        $this->postType = $postType;
        $this->partialName = $partialName;
        $this->formName = $formName;
        $this->viewName = $viewName;
        $this->routeName = \Modules\CmsBackend\Support\ModuleUrl::name($routeName);
        $this->permissionsBase = $permission;
        $this->isEditor = $isEditor;
        $this->isAuthor = $isAuthor;
    }



    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pageSize = $request->pageSize ?? 20;
        $page = $request->page ?? 1;
        // $sortBy = $request->sortBy ?? 'id';
        // $order = $request->order ?? 'DESC';
        $sortBy = $request->get('sortBy', 'id'); // tên cột để sắp xếp, vd: order, title, created_at
        $direction = strtolower($request->get('direction', 'desc')); // hướng sắp xếp: asc/desc

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $filterPostStatuses = $request->getBase64Param('postStatuses') ?? [];
        $filterCategoryId = $request->getBase64Param('categories') ?? [];
        $filterTagId = $request->getBase64Param('tags') ?? [];
        // UserType App\Enums\UserTypesEnum;
        $filterPostType = trim(strip_tags($request->query('postType')));
        $filterAuthorIds = $request->getBase64Param('author') ?? [];
        $filter = trim(strip_tags($request->query('filter')));
        // Mode is: [select, normal or else]
        $mode = $request->mode;
        // $this->checkPermission();
        // if(!$this->permissionsBase['canView']){
        //     return view('pages.misc.pages-misc-not-authorized');
        // }

        //NẾu là người viết bài thì chi lây bài viết của người đó

        if (!$this->isEditor && $this->isAuthor) {
            $filterAuthorIds = $my_array = array(auth()->user()->id);
        }


        // ==== Lọc bài viết theo chuyên mục (gồm cha con) ====
        $user = auth()->user();
        $userTermPermission = UserTermPermissionDataProvider::getTermIdsByUserId($user->id);

        // Lấy danh sách chuyên mục theo cha con
        $category = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
        $cate = $this->filterCategoryByPermission($category, $userTermPermission);
        //dd($cate);
        $permissions = $this->permissionsBase;
        $posts = $this->postDataProvider->paginate(
            $this->postType->value,
            $filterPostStatuses,
            $filterAuthorIds,
            $filterCategoryId,
            $filterTagId,
            $filter,
            $sortBy,
            // $order,
            $direction,
            $pageSize,
            $page,
            null,
            $cate
        );
        $posts->appends($request->all());
        $filterPostStatuses = collect($filterPostStatuses);
        $filterCategoryId = collect($filterCategoryId);

        $filterTagId = collect($filterTagId);
        $postStatuses = [];
        foreach (PostStatus::cases() as $postStatus) {
            $postStatuses[] = [
                'enum' => $postStatus,
                'selected' => $filterPostStatuses->some(function ($status) use ($postStatus) {
                    return $status == $postStatus->value;
                })
            ];
        }
        // Lấy danh sách các chuyên mục
        $categories = Taxonomy::where('taxonomy', EnumsTaxonomy::CATEGORY->value)
            ->whereIn('term_taxonomy_id', $cate->pluck('term_taxonomy_id')->toArray())
            ->with('term')->get();
        foreach ($categories as $cate) {
            $cate->selected = $filterCategoryId->some(function ($id) use ($cate) {
                return $id == $cate->term_id;
            });
        }

        // Lấy danh sách các thẻ
        $tags = Taxonomy::where('taxonomy', EnumsTaxonomy::POSTTAG->value)->with('term')->get();
        foreach ($tags as $t) {
            $t->selected = $filterTagId->some(function ($id) use ($t) {
                return $id == $t->term_id;
            });
        }
        if ($request->ajax()) {
            return view($this->partialName, compact(['categories', 'posts', 'filter']));
        }

        if ($mode == 'select') {
            return view($this->viewName . '.index-select', compact(['categories', 'tags', 'posts', 'postStatuses']));
        }



        // dd($permissions);
        // dd($permissions);

        return view($this->viewName . '.index', compact(['categories', 'tags', 'posts', 'postStatuses', 'permissions']));
    }

    // Hàm lấy tất cả term_id từ cây chuyên mục (bao gồm cha và con)
    private function getAllTermIdsFromCategory($category)
    {
        $termIds = [];
        foreach ($category as $cate) {
            // Lấy term_id của chuyên mục hiện tại
            $termIds[] = $cate->term_id;

            // Nếu có con, đệ quy lấy các term_id từ con
            if (!empty($cate->children)) {
                $termIds = array_merge($termIds, $this->getAllTermIdsFromCategory($cate->children));
            }
        }
        return array_unique($termIds);  // Trả về mảng term_id không trùng lặp
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // dd('mmmmmmmmmmmm');
        $user = auth()->user();
        $userTermPermission = UserTermPermissionDataProvider::getTermIdsByUserId($user->id);
        //Lấy danh sách chuyên mục theo cha con
        $category = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
        // dd([
        //     'raw' => $category->toArray(),
        //     'filtered' => $this->filterCategoryByPermission($category, $userTermPermission)->toArray()
        // ]);
        $cate = $this->filterCategoryByPermission($category, $userTermPermission);
        //Lấy danh sách 10 chuyên mục được sử dụng nhiều
        $topCategory = $this->taxonomyDataProvider->getCategoryTopUsed();
        $topCate = $this->filterFlatTermsByUser($topCategory, $userTermPermission);

        //Lấy tag
        $tags = $this->tagDataProvider->getTag();
        //Lấy loại xuất bản
        $publishType = PublishType::toObject();

        $postStatus = PostStatus::toObject();
        $post = new Post;
        $permissions = $this->permissionsBase;
        // $u = $user->givePermissionTo('Thêm bài viết');
        $post->status = PostStatus::DRAFT->value;
        $titleName = __('admin.ui.add_new') . ' ' . Str::lower($this->displayName());
        $pages = null;
        $pageOptions = null;
        $post->post_type = $this->postType->value;
        //Lấy file đính kèm
        $mediaItems = $post->getMedia('fileDinhKem');
        $mediaList = $mediaItems->map(function ($media) {
            return $media->getUrl();
        })->values();
        $post->fileDinhKem = $mediaList;
        //tùy chọn view file
        $fileTypeViews = [];
        foreach (FileTypeEnum::cases() as $fileTypeView) {
            $fileTypeViews[] = [
                'enum' => $fileTypeView,
                'selected' => true
            ];
        }


        $pages = $this->postDataProvider->getPageHierarchy(0, $this->postType->value);
        $pageOptions = $this->renderPageOptions($pages);
        return view($this->viewName . '.edit', compact(['cate', 'topCate', 'postStatus', 'publishType', 'tags', 'post', 'user', 'permissions', 'titleName', 'pages', 'pageOptions', 'fileTypeViews']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostUpdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            //$post = $this->save($request);
        });

        return redirect()->route($this->routeName);
    }

    public function save(PostUpdateRequest $request)
    {

        // Lưu bài viết vào cơ sở dữ liệu
        $post = new Post;
        // $post->fill($request->all());
        $input = $request->all();
        foreach (['published_at', 'publication_date_expires'] as $f) {
            if (!empty($input[$f])) {
                try {
                    $input[$f] = Carbon::createFromFormat('d-m-Y H:i', $input[$f])
                        ->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    unset($input[$f]); // hoặc $input[$f] = null;
                }
            }
        }

        $post->fill($input);
        $post->post_type = $this->postType->value;
        //render slug theo tiêu đề
        $slug = $this->checkUniqueSlug($post->slug);
        $post->slug = $slug;
        $post->author_id = auth()->user()->id;
        $post->format = PostFormat::DEFAULT->value;
        //Lưu định dạng file view
        if ($request->has('render_file_in_view')) {
            $arrFileType = $request->input('render_file_in_view');
            $arrFileType = collect($arrFileType);
        }
        // if ($request->has('show_home_page')) {
        //     $post->show_home_page = $this->showHomePageConvert($request->show_home_page);
        // } else {
        //     $post->show_home_page = $this->showHomePageConvert('off');
        // }
        // if ($request->has('comment_status')) {
        //     $post->comment_status = $this->showCommentPageConvert($request->comment_status);
        // } else {
        //     $post->comment_status = $this->showCommentPageConvert('off');
        // }

        $post->show_home_page = $request->boolean('show_home_page'); // true/false
        $post->comment_status = $request->input('comment_status') === 'published'
            ? 'published'
            : 'disabled';

        if (!$request->input('publish_type')) {
            $post->publish_type = PublishType::PUBLISH_NOW->value;
        }

        // dd($post);
        $this->checkPermission($post);
        $now_ = now();
        if ($request->input('action') === 'submit_for_approval') {
            if ($this->permissionsBase['canSubmitForApproval']) {
                $post->status = PostStatus::PENDING_APPROVAL->value;
                $post->save();
                $fb = Feedback::where('post_id', $post->id)->latest()->first();
                if ($fb) {
                    $fb->deleted_at = $now_;
                    $fb->save();
                }
                Toastr::success(__('admin.message.trinh_duyet_thanh_cong'));
            }
        } else if ($request->input('action') === 'save_draft') {
            if ($this->permissionsBase['canSaveDraft']) {
                $post->status = PostStatus::DRAFT->value;
                $post->save();
                $fb = Feedback::where('post_id', $post->id)->latest()->first();
                if ($fb) {
                    $fb->deleted_at = $now_;
                    $fb->save();
                }
                Toastr::success(__('admin.message.luu_thanh_cong'));
            }
        } else if ($request->input('action') === 'publish_now') {
            if ($this->permissionsBase['canPublishNow']) {
                $post->status = PostStatus::PUBLISHED->value;
                if ($this->postType->value !== PostType::EVENT->value) {
                    $post->published_at = $now_;
                }
                $post->publisher_id = auth()->user()->id;

                $post->save();
                Toastr::success(__('admin.message.xuat_ban_thanh_cong'));
            } else {
                //Không có quyền xuất bản
            }
        } elseif ($request->input('action') === 'submit') {
            if ($this->permissionsBase['canUpdate']) {
                $post->save();
                Toastr::success(__('admin.message.luu_thanh_cong'));
            } else {
                //Không có quyền lưu
            }
        }

        if ($request->has('category_ids')) {
            $categoryIds = array_unique($request->input('category_ids'));
            foreach ($categoryIds as $categoryId) {
                // Lưu vào bảng cms_term_relationships
                $post->taxonomies()->attach($categoryId);
                // Tăng số lượng cho term_taxonomy tương ứng
                $this->incrementTermTaxonomyCount($categoryId);
            }
        }

        if ($request->has('tag_ids')) {
            $tag_ids = array_unique($request->input('tag_ids'));
            foreach ($tag_ids as $tag_id) {
                // Lưu vào bảng cms_term_relationships
                $post->taxonomies()->attach($tag_id);
                // Tăng số lượng cho term_taxonomy tương ứng
                $this->incrementTermTaxonomyCount($tag_id);
            }
        }
        // Nếu có avatar, lưu avatar
        if ($request->has('avatar')) {
            if ($request->avatar != null || $request->avatar != '') {

                //$post->addMediaFromUrl($request->avatar)->toMediaCollection('avatars');
                $post->addMediaFromUrl($request->avatar)->toMediaCollection('avatars');
            }
        }
        if ($request->has('images')) {
            // Lưu các ảnh vào Medialibrary

            $imagePaths = json_decode($request->input('images'));
            foreach ($imagePaths as $imagePath) {
                if ($imagePath->url !== '') {
                    $post->addMediaFromUrl($imagePath->url) // Đường dẫn file
                        ->withCustomProperties(['description' => $imagePath->description])
                        ->toMediaCollection('images'); // Lưu vào bộ sưu tập 'images'
                }
            }
        }
        if ($request->has('videos') && $request->videos != "" && $request->videos !== null) {
            dd('videos', $request);
            $post->addMediaFromUrl($request->videos) // Đường dẫn file
                ->toMediaCollection('videos'); // Lưu vào bộ sưu tập 'images'
        }
        if ($request->has('fileDinhKem')) {
            $files = json_decode($request->input('fileDinhKem') ?? '[]');
            $allMedia = $post->getMedia('fileDinhKem');
            $mediaToKeep = $allMedia->filter(fn($media) => in_array($media->getUrl(), $files));
            $post->clearMediaCollectionExcept('fileDinhKem', $mediaToKeep->toArray());

            foreach ($files as $file) {
                $existingMedia = $mediaToKeep->first(fn($media) => $media->getUrl() === $file);
                if (!$existingMedia) {
                    $post->addMediaFromUrl($file)->toMediaCollection('fileDinhKem');
                }
            }
        }

        // Bilingual content (Phase 4): same transaction as the post row, only when the chosen action really saved content.
        $this->saveTranslations($post, $request);

        Toastr::success(__('admin.message.them_moi_thanh_cong'));
        return $post;
    }
    /**
     * Show the specified resource.
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = auth()->user();
        $post = $this->postDataProvider->findByIdSimple($id, $this->postType->value);

        // dd($post->toArray());
        $this->checkPermission($post);
        $permissions = $this->permissionsBase;
        // dd($permissions);
        if ($permissions['canView'] == false) {
            return view('pages.misc.pages-misc-not-authorized');
        }
        //Lấy danh sách chuyên mục theo cha con
        $userTermPermission = UserTermPermissionDataProvider::getTermIdsByUserId($user->id);
        //Lấy danh sách chuyên mục theo cha con
        $category = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
        $cate = $this->filterCategoryByPermission($category, $userTermPermission);
        //Lấy danh sách 10 chuyên mục được sử dụng nhiều
        $topCategory = $this->taxonomyDataProvider->getCategoryTopUsed();
        $topCate = $this->filterFlatTermsByUser($topCategory, $userTermPermission);
        //Lấy tag
        $tags = $this->tagDataProvider->getTag();
        $publishType = PublishType::toObject();
        $postStatus = PostStatus::toObject();
        // Lấy feedback mới nhất
        $feedback = null;
        if (auth()->user()->id == $post->author->id) {
            $feedback = Feedback::where('post_id', $post->id)->whereNull('deleted_at')->latest()->first();
        }

        $titleName = __('admin.ui.cap_nhat') . ' ' . Str::lower($this->displayName());
        $videos = $post->getFirstMediaUrl('videos');
        //Lấy file đính kèm
        $mediaItems = $post->getMedia('fileDinhKem');
        $mediaList = $mediaItems->map(function ($media) {
            return $media->getUrl();
        })->values();
        $post->fileDinhKem = $mediaList;
        $breadcrumbParam = $post;

        if ($post->published_at) {
            $post->published_at = \Carbon\Carbon::parse($post->published_at)->format('d-m-Y H:i');
        }
        if ($post->publication_date_expires) {
            $post->publication_date_expires = \Carbon\Carbon::parse($post->publication_date_expires)->format('d-m-Y H:i');
        }
        $arrFileType =  json_decode($post->render_file_in_view, true) ?? [];
        //tùy chọn view file
        $fileTypeViews = [];
        foreach (FileTypeEnum::cases() as $fileTypeView) {
            $fileTypeViews[] = [
                'enum' => $fileTypeView,
                'selected' => in_array($fileTypeView->value, $arrFileType, true),
            ];
        }
        // dd('Sua bai viet $post  ', $post->toArray());
        $pages = null;
        $pageOptions = null;
        $pages = $this->postDataProvider->getPageHierarchy($post->id, $this->postType->value);
        $pageOptions = $this->renderPageOptions($pages, '', $post->post_parent);

        // dd($post->toArray());
        return view($this->viewName . '.edit', compact(['cate', 'post', 'postStatus', 'publishType', 'tags', 'topCate', 'permissions', 'feedback', 'titleName', 'pages', 'pageOptions', 'videos', 'breadcrumbParam', 'fileTypeViews']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostUpdateRequest $request, $id): RedirectResponse
    {
        // dd($request->toArray());
        DB::transaction(function () use ($request, $id) {
            $post = $this->postDataProvider->findByIdSimple($id, $this->postType->value);
            //dd($request);


            // $post->fill($request->all());

            $input = $request->all();

            foreach (['published_at', 'publication_date_expires'] as $f) {
                if (!empty($input[$f])) {
                    try {
                        $input[$f] = \Carbon\Carbon::createFromFormat('d-m-Y H:i', $input[$f])
                            ->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        unset($input[$f]); // hoặc $input[$f] = null; tuỳ ý
                    }
                }
            }

            $post->fill($input);



            //KHởi tại lại slug
            $slug = $this->checkUniqueSlug($post->slug, $post->id);
            $post->slug = $slug;
            //Kiểm tra loại xuất bản
            if ($post->publish_type != PublishType::SCHEDULE_PUBLICATION->value && $this->postType->value !== PostType::EVENT->value) {
                // $post->published_at = null;
                $post->publication_date_expires = null;
            }
            $now_ = now();
            // $post->show_home_page = $this->showHomePageConvert($request->show_home_page);
            // $post->comment_status = $this->showCommentPageConvert($request->comment_status);
            $post->show_home_page = $request->boolean('show_home_page');
            $post->comment_status = $request->input('comment_status') === 'published'
                ? 'published'
                : 'disabled';

            //Lưu định dạng file view
            if ($request->has('fileTypeViews')) {
                $arrFileType = $request->getBase64Param('fileTypeViews') ?? [];
                $arrFileType = collect($arrFileType);
                $post->render_file_in_view = json_encode($arrFileType, JSON_UNESCAPED_UNICODE);
            }
            $this->checkPermission($post);
            Toastr::success(__('admin.message.luu_thanh_cong'));
            if ($request->input('action') === 'approved') {
                if ($this->permissionsBase['canApprove']) {
                    $now_ = now();
                    $post->status = PostStatus::APPROVED->value;
                    $post->approved_at = $now_;
                    if ($post->publish_type == PublishType::PUBLISH_NOW->value) {
                        if ($this->postType->value !== PostType::EVENT->value) {
                            $post->published_at = $now_;
                        }
                    }
                    $post->publisher_id = auth()->user()->id;
                    $post->save();
                    Toastr::success(__('admin.message.phe_duyet_thanh_cong'));
                } else {
                    //Không có quyền duyệt
                }
            } else if ($request->input('action') === 'reject') {
                if ($this->permissionsBase['canReject']) {
                    $post->status = PostStatus::NOT_APPROVED->value;
                    $post->rejected_at = $now_;
                    $post->save();
                    // Lưu feedback vào cơ sở dữ liệu
                    Feedback::create([
                        'post_id' => $post->id,
                        'feedback' => $request->input('feedback'),
                        'user_id' => auth()->user()->id,  // Lưu thông tin người gửi feedback
                    ]);
                    Toastr::success(__('admin.message.khong_duyet_gui_feedback_thanh_cong'));
                }
            } else if ($request->input('action') === 'submit_for_approval') {
                if ($this->permissionsBase['canSubmitForApproval']) {
                    $post->status = PostStatus::PENDING_APPROVAL->value;
                    $post->save();
                    $fb = Feedback::where('post_id', $post->id)->latest()->first();
                    if ($fb) {
                        $fb->deleted_at = $now_;
                        $fb->save();
                    }
                    Toastr::success(__('admin.message.trinh_duyet_thanh_cong'));
                }
            } else if ($request->input('action') === 'save_draft') {
                if ($this->permissionsBase['canSaveDraft']) {
                    $post->status = PostStatus::DRAFT->value;
                    $post->save();
                    $fb = Feedback::where('post_id', $post->id)->latest()->first();
                    if ($fb) {
                        $fb->deleted_at = $now_;
                        $fb->save();
                    }
                    Toastr::success(__('admin.message.luu_thanh_cong'));
                }
            } else if ($request->input('action') === 'publish_now') {
                if ($this->permissionsBase['canPublishNow']) {
                    $post->status = PostStatus::PUBLISHED->value;
                    if ($this->postType->value !== PostType::EVENT->value) {
                        $post->published_at = $now_;
                    }
                    $post->publisher_id = auth()->user()->id;
                    $post->save();
                    Toastr::success(__('admin.message.xuat_ban_thanh_cong'));
                } else {
                    //Không có quyền xuất bản
                }
            } elseif ($request->input('action') === 'submit') {
                if ($this->permissionsBase['canUpdate']) {
                    // $post->published_at = $post->published_at;
                    // $post->approved_at = $post->approved_at;
                    // $post->save();
                    // Toastr::success(__('Lưu thành công'));
                    if ($request->filled('published_at')) {
                        try {
                            $post->published_at = Carbon::createFromFormat('d-m-Y H:i', $request->input('published_at'))->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            $post->published_at = null; // hoặc throw lỗi tùy bạn muốn xử lý
                        }
                    }

                    if ($request->filled('publication_date_expires')) {
                        try {
                            $post->publication_date_expires = Carbon::createFromFormat('d-m-Y H:i', $request->input('publication_date_expires'))->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            $post->publication_date_expires = null;
                        }
                    }

                    $post->approved_at = $post->approved_at;
                    $post->save();
                    if ($post->status == PostStatus::PUBLISHED->value && $this->userMayEditPublished(auth()->user())) {
                        // Truy vết: ai sửa trực tiếp bài đang đăng, lúc nào.
                        activity()->performedOn($post)->causedBy(auth()->user())->withProperties(['ip' => $request->ip(), 'post_id' => $post->id])
                            ->log('Sửa trực tiếp bài đang đăng #'.$post->id);
                    }
                    Toastr::success(__('admin.message.luu_thanh_cong'));
                } else {
                    //Không có quyền lưu
                }
            } elseif ($request->input('action') === 'stop_publishing') {
                if ($this->permissionsBase['canStopPublishing']) {
                    $post->status = PostStatus::STOP_PUBLISHING->value;
                    $post->save();
                    Toastr::success(__('admin.message.luu_thanh_cong'));
                } else {
                    //Không có quyền lưu
                }
            }
            $this->saveTranslations($post, $request);
            if ($request->input('action') !== 'reject') {
                $post->taxonomies()->detach();
                // Gán lại các mối quan hệ mới
                if ($request->has('category_ids')) {
                    $category_ids = array_unique($request->input('category_ids'));
                    $post->taxonomies()->attach($category_ids, ['term_order' => 0]); // Gán term_order nếu cần
                }

                if ($request->has('tag_ids')) {
                    $tag_ids = array_unique($request->input('tag_ids'));
                    $post->taxonomies()->attach($tag_ids, ['term_order' => 0]); // Gán term_order nếu cần
                }
                if ($request->has('avatar')) {

                    if (($request->avatar !== $post->getFirstMediaUrl('avatars')) && $request->avatar != null && $request->avatar != '') {
                        $post->clearMediaCollection('avatars');
                        $post->addMediaFromUrl($request->avatar)->toMediaCollection('avatars');
                    }
                }
                if ($request->has('images')) {
                    $imagePaths = json_decode($request->input('images'));
                    // Lấy tất cả media hiện tại của post
                    $allMedia = $post->getMedia('images');
                    // Lọc để giữ lại những media vẫn còn trong danh sách
                    $mediaToKeep = $allMedia->filter(function ($media) use ($imagePaths) {
                        return in_array($media->getUrl(), array_column($imagePaths, 'url'));
                    });
                    // Chuyển thành mảng để truyền vào clearMediaCollectionExcept
                    $post->clearMediaCollectionExcept('images', $mediaToKeep->toArray());
                    // Duyệt qua $imageData để thêm hoặc cập nhật media và description
                    foreach ($imagePaths as $image) {
                        $imagePath = $image->url;
                        $description = $image->description ?? '';
                        $index = $image->index ?? '';

                        // Kiểm tra xem media với URL này có tồn tại hay không
                        $existingMedia = $mediaToKeep->first(function ($media) use ($imagePath) {
                            return $media->getUrl() === $imagePath;
                        });

                        if ($existingMedia) {
                            // Cập nhật mô tả nếu media đã tồn tại
                            $existingMedia->setCustomProperty('description', $description);
                            $existingMedia->setCustomProperty('index', $index);
                            $existingMedia->save();
                        } else {
                            // Nếu media không tồn tại, thêm vào và thiết lập mô tả
                            $post->addMediaFromUrl($imagePath)
                                ->withCustomProperties(['description' => $description, 'index' => $index])
                                ->toMediaCollection('images');
                        }
                    }
                }
                if ($request->has('videos')) {
                    if ($request->videos !== $post->getFirstMediaUrl('videos') && $request->videos !== "" && $request->videos !== null) {
                        $post->clearMediaCollection('videos');
                        $post->addMediaFromUrl($request->videos) // Đường dẫn file
                            ->toMediaCollection('videos'); // Lưu vào bộ sưu tập 'images'
                    } elseif ($request->videos === "" || $request->videos === null) {
                        $post->clearMediaCollection('videos');
                    }
                }
                if ($request->has('fileDinhKem')) {
                    $files = json_decode($request->input('fileDinhKem') ?? '[]');
                    $allMedia = $post->getMedia('fileDinhKem');
                    $mediaToKeep = $allMedia->filter(fn($media) => in_array($media->getUrl(), $files));
                    $post->clearMediaCollectionExcept('fileDinhKem', $mediaToKeep->toArray());

                    foreach ($files as $file) {
                        $existingMedia = $mediaToKeep->first(fn($media) => $media->getUrl() === $file);
                        if (!$existingMedia) {
                            $post->addMediaFromUrl($file)->toMediaCollection('fileDinhKem');
                        }
                    }
                }
            }
        });
        // dd($this->viewName);
        // dd($this->routeName);
        // return redirect()->back();
        // return redirect()->route($this->viewName); // Đảm bảo 'posts.index' là route hợp lệ của bạn
        // return view($this->viewName . '.index', ['post' => $post]); 
        return redirect()->route($this->routeName);  // Ensure $this->routeName is defined correctly.

    }

    // Không duyệt duyệt bài viết
    public function reject(Request $request, $id): RedirectResponse
    {

        // dd('REJECT', $id, $request->all());

        if (auth()->user()->can('Duyệt bài viết')) {
            $post = $this->postDataProvider->findByIdSimple($id, $this->postType->value);
            $now_ = now();
            $post->status = PostStatus::NOT_APPROVED->value;
            $post->rejected_at = $now_;
            $post->save();
            // Lưu feedback vào cơ sở dữ liệu
            Feedback::create([
                'post_id' => $post->id,
                'feedback' => $request->input('feedback'),
                'user_id' => auth()->user()->id,  // Lưu thông tin người gửi feedback
            ]);
            Toastr::success(__('admin.message.khong_duyet_gui_feedback_thanh_cong'));
        }
        return redirect()->back();
    }
    // Phê duyệt bài viết
    public function approve(PostUpdateRequest $request, $id): RedirectResponse
    {

        $post = Post::findOrFail($id);
        // Kiểm tra quyền biên tập viên
        if (auth()->user()->can('Duyệt bài viết')) {
            $post->update(['status' => PostStatus::APPROVED->value]); // Cập nhật trạng thái bài viết thành 'approved'
            Toastr::success(__('admin.message.phe_duyet_thanh_cong'));
        }
        // else
        // {
        //     Toastr::success(__('Bạn không có quyền phê duyệt'));
        // }

        return redirect()->back();
    }

    // P
    public function submit(PostUpdateRequest $request, $id)
    {

        // if ($request->input('action') === 'approved') {
        //     // Xử lý dữ liệu và điều hướng đến Route 1 với dữ liệu từ form

        //     $this->approve($request, $id);
        // } elseif ($request->input('action') === 'submit') {
        //     // Xử lý dữ liệu và điều hướng đến Route 2 với dữ liệu từ form
        //     $this->update($request, $id);
        // }

        // // Nếu không có nút nào được chọn, có thể xử lý lỗi hoặc điều hướng về form
        // return back()->withErrors(['message' => 'Invalid action.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $post = $this->postDataProvider->findByIdSimple($id, $this->postType->value);
        $this->checkPermission($post);
        // Kiểm tra quyền
        if (!$this->permissionsBase['canDelete']) {
            return response()->json([
                'status' => 0,
                'message' => __('admin.message.ban_khong_co_quyen_xoa_bai_viet_nay')
            ], 403);
        }
        $post->deleted_at = now();
        $post->save();
        return response()->json([
            'status' => 1,
            'message' => __('admin.message.xoa_thanh_cong')
        ]);
    }

    /**
     * Writes the Vietnamese mirror and the other languages into post_translations. Runs only for the actions whose
     * permission flag allowed the content save above, so it can never write more than the legacy columns were allowed to.
     */
    private function saveTranslations(Post $post, Request $request): void
    {
        $allowed = match ((string) $request->input('action')) {
            'approved' => $this->permissionsBase['canApprove'] ?? false,
            'submit_for_approval' => $this->permissionsBase['canSubmitForApproval'] ?? false,
            'save_draft' => $this->permissionsBase['canSaveDraft'] ?? false,
            'publish_now' => $this->permissionsBase['canPublishNow'] ?? false,
            'submit' => $this->permissionsBase['canUpdate'] ?? false,
            default => false,
        };
        if (! $allowed || ! $post->exists) {
            return;
        }
        $writer = app(\Modules\CmsBackend\Services\PostTranslationWriter::class);
        $writer->validate($request);
        $writer->save($post, $request);
    }

    /** Cờ users.can_edit_published do Developer bật cho từng tài khoản; cột chưa có (chưa migrate) thì không ai có cờ. */
    private function userMayEditPublished($user): bool
    {
        return (bool) ($user->can_edit_published ?? false);
    }

    private function checkPermission($post = null)
    {

        // Kiểm tra điều kiện hiển thị nút Duyệt
        $user = auth()->user();
        //Kiểm tra quyền xem
        if (
            (($user->can(PermissionsEnum::POST_VIEW->label()) || $user->can(PermissionsEnum::POST_CREATE->label())))
            || ($user->can(PermissionsEnum::POST_APPROVE->label()) || $user->can(PermissionsEnum::POST_PUBLISHING->label()))
            || ($user->hasPermissionTo(PermissionsEnum::POST_VIEW->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label()))
        ) {
            $this->permissionsBase['canView'] = true;
        }
        if ($post == null) {
            return;
        }
        //Đối với trạng thái Nháp
        if ($post->status == PostStatus::DRAFT->value || $post->status == null) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && ($post->author_id == $user->id || $post->author_id == null)
            ) {
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canPublishNow'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
        }
        //Đối với trạng thái Chờ duyệt
        elseif ($post->status == PostStatus::PENDING_APPROVAL->value) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                $this->permissionsBase['canSaveDraft'] = false;
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canApprove'] = true;
                $this->permissionsBase['canReject'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
        }
        //Đối với trạng thái Đã duyệt
        elseif ($post->status == PostStatus::APPROVED->value) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
            } //Biên tập viên
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = false;
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canPublishNow'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
        }
        //Đối với trạng thái Đã xuất bản
        elseif ($post->status == PostStatus::PUBLISHED->value) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && $post->author_id == $user->id
            ) {
                //Không thể thao tác khi bài viết đã được xuất bản
            } //Biên tập viên
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canStopPublishing'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
            // Tài khoản được Developer bật "sửa trực tiếp bài đã đăng" (Developer Settings → Tài khoản): chỉ được lưu chỉnh sửa,
            // bài vẫn giữ trạng thái xuất bản. Không kèm quyền gỡ đăng, xóa hay duyệt.
            if ($this->userMayEditPublished($user)) {
                $this->permissionsBase['canView'] = true;
                $this->permissionsBase['canUpdate'] = true;
                // Cờ riêng cho nút "Lưu" của bài đang đăng: nút này vốn đòi cả quyền duyệt và xuất bản, mà nhánh này không cấp hai quyền đó.
                $this->permissionsBase['canEditPublished'] = true;
            }
        }
        //Đối với trạng thái Không duyệt
        elseif ($post->status == PostStatus::NOT_APPROVED->value) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canDelete'] = true;
            } //Biên tập viên
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                //    $permissions['canUpdate'] = true;
                //    $permissions['canStopPublishing'] = true;
                $this->permissionsBase['canSubmitForApproval'] = false;
                $this->permissionsBase['canSaveDraft'] = false;
                $this->permissionsBase['canDelete'] = true;
            }
        }
        //Đối với trạng thái dừng xuất bản
        elseif ($post->status == PostStatus::STOP_PUBLISHING->value) {
            //Tác giả của bài viết đó
            if (
                ($user->can(PermissionsEnum::POST_CREATE->label()) || $user->hasPermissionTo(PermissionsEnum::POST_CREATE->label()))
                && $post->author_id == $user->id
            ) {
                $this->permissionsBase['canSubmitForApproval'] = true;
                $this->permissionsBase['canSaveDraft'] = true;
                $this->permissionsBase['canDelete'] = true;
            } //Biên tập viên
            if (
                $user->can(PermissionsEnum::POST_APPROVE->label())
                || $user->can(PermissionsEnum::POST_PUBLISHING->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_APPROVE->label())
                || $user->hasPermissionTo(PermissionsEnum::POST_PUBLISHING->label())
            ) {
                $this->permissionsBase['canUpdate'] = true;
                $this->permissionsBase['canPublishNow'] = true;
                $this->permissionsBase['canDelete'] = true;
            }
        }
    }


    /**
     * Hàm tăng số lượng của term_taxonomy khi được sử dụng
     */
    protected function incrementTermTaxonomyCount($termTaxonomyId)
    {
        $termTaxonomy = Taxonomy::find($termTaxonomyId);

        if ($termTaxonomy) {
            $termTaxonomy->count += 1; // Tăng số lượng
            $termTaxonomy->save(); // Lưu lại thay đổi
        }
    }
    /**
     * Hàm giảm số lượng của term_taxonomy khi không còn được sử dụng
     */
    protected function decrementTermTaxonomyCount($termTaxonomyId)
    {
        $termTaxonomy = Taxonomy::find($termTaxonomyId);

        if ($termTaxonomy && $termTaxonomy->count > 0) {
            $termTaxonomy->count -= 1; // Giảm số lượng
            $termTaxonomy->save(); // Lưu lại thay đổi
        }
    }

    private function renderPageOptions($pages, $indent = '', $selectedParentId = -1)
    {
        $options = '';
        foreach ($pages as $page) {
            // Determine if this page is the selected parent
            $selected = ($page->id === $selectedParentId) ? 'selected' : '';

            // Add the option for the current page
            $options .= '<option value="' . $page->id . '" ' . $selected . '>' . $indent . htmlspecialchars($page->title) . '</option>';
            // Check if the page has children and recursively render their options
            if (!empty($page->children)) {
                // Recursively render child pages with increased indentation
                $options .= $this->renderPageOptions($page->children, $indent . '&nbsp;&nbsp;&nbsp;', $selectedParentId);
            }
        }
        return $options;
    }

    public function getMediaList($postId)
    {
        $mediaItems = Post::find($postId)->getMedia('images'); // Lấy media từ collection 'images'

        $mediaList = $mediaItems->map(function ($media) {
            return [
                'url' => $media->getUrl(),
                'description' => $media->getCustomProperty('description') ?? '',
                'index' => $media->getCustomProperty('index') ?? 0
            ];
        })->sortBy('index')->values();

        return response()->json($mediaList);
    }

    public function addMediaFromUrlWithSSLBypass($model, $url)
    {
        // Tải tệp từ URL và bỏ qua SSL
        $response = Http::withoutVerifying()->get($url);

        if ($response->successful()) {
            // Lưu tệp vào MediaLibrary
            $model->addMediaFromUrl($url)->toMediaCollection();
        } else {
            // Xử lý khi tải tệp không thành công
            throw new \Exception("Failed to retrieve file from URL.");
        }
    }

    public function changeShowHomePage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:posts,id',
        ]);
        $post = $this->postDataProvider->findByIdSimple($request->id);
        $show = $post->show_home_page;
        $post->show_home_page = $show == 1 ? 0 : 1;
        $post->save();
        return response()->json(['message' => __('admin.js.cap_nhat_thanh_cong')]);
    }

    public function changeAllowCommentPage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:posts,id',
        ]);
        $post = $this->postDataProvider->findByIdSimple($request->id);
        $comment_status = $post->comment_status;
        $post->comment_status = $comment_status == 'published' ? 'published_stop' : 'published';
        $post->save();
        return response()->json(['message' => __('admin.message.cap_nhat_thanh_cong')]);
    }

    protected function filterCategoryByPermission($categories, $allowedTermIds)
    {
        return $categories->flatMap(function ($category) use ($allowedTermIds) {
            // Đệ quy lọc children trước
            $children = $this->filterCategoryByPermission($category->children, $allowedTermIds);

            // Nếu category này nằm trong allowedTermIds => giữ lại
            if (in_array($category->term_id, $allowedTermIds)) {
                // Gắn children đã lọc (nếu có)
                $category->children = $children;
                return collect([$category]);
            }

            // Nếu category KHÔNG được phép nhưng có children được phép => chỉ trả về các children
            return $children;
        });
    }

    protected function filterFlatTermsByUser($taxonomies, $allowedTermIds)
    {
        return $taxonomies->filter(function ($taxonomy) use ($allowedTermIds) {
            return in_array($taxonomy->term_id, $allowedTermIds);
        })->values();
    }


    // public function export(Request $request)
    // {

    //     $allowedSort = ['id', 'title', 'slug', 'created_at', 'published_at', 'views'];
    //     $sortBy = $request->get('sortBy', 'id');
    //     if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'id';

    //     $direction = strtolower($request->get('direction', 'desc'));
    //     if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';

    //     // --- lấy bộ lọc giống index() ---
    //     $filterPostStatuses = $request->getBase64Param('postStatuses') ?? [];
    //     // Trên UI bạn gửi term_id -> cần đổi sang term_taxonomy_id
    //     $filterCategoryTermIds = $request->getBase64Param('categories') ?? [];
    //     $filterTagTermIds      = $request->getBase64Param('tags') ?? [];
    //     $filterAuthorIds       = $request->getBase64Param('author') ?? [];
    //     $filterText            = trim(strip_tags($request->query('filter', '')));

    //     // Nếu là tác giả (không phải editor) thì chỉ xuất bài của mình
    //     if (!$this->isEditor && $this->isAuthor) {
    //         $filterAuthorIds = [auth()->id()];
    //     }

    //     // Quyền theo chuyên mục người dùng được phép
    //     $user = auth()->user();
    //     $userTermPermission = \Modules\CmsBackend\DataProviders\UserTermPermissionDataProvider
    //         ::getTermIdsByUserId($user->id);

    //     $categoryTree  = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
    //     $allowedCates  = $this->filterCategoryByPermission($categoryTree, $userTermPermission);
    //     $allowedCateTaxonomyIds = $allowedCates->pluck('term_taxonomy_id')->toArray();

    //     // Đổi term_id -> term_taxonomy_id để lọc theo đúng cột
    //     $filterCategoryTaxonomyIds = !empty($filterCategoryTermIds)
    //         ? Taxonomy::whereIn('term_id', $filterCategoryTermIds)->pluck('term_taxonomy_id')->all()
    //         : [];

    //     $filterTagTaxonomyIds = !empty($filterTagTermIds)
    //         ? Taxonomy::whereIn('term_id', $filterTagTermIds)->pluck('term_taxonomy_id')->all()
    //         : [];

    //     // --- Query ---
    //     $q = Post::query()
    //         ->where('post_type', $this->postType->value)
    //         ->when(!empty($filterPostStatuses), fn($qq) => $qq->whereIn('status', $filterPostStatuses))
    //         ->when(!empty($filterAuthorIds),   fn($qq) => $qq->whereIn('author_id', $filterAuthorIds))
    //         ->when($filterText !== '', function ($qq) use ($filterText) {
    //             $qq->where(function ($s) use ($filterText) {
    //                 $s->where('title', 'like', "%{$filterText}%")
    //                     ->orWhere('slug', 'like', "%{$filterText}%")
    //                     ->orWhere('excerpt', 'like', "%{$filterText}%");
    //             });
    //         })
    //         // Chỉ các chuyên mục user được phép
    //         ->when(!empty($allowedCateTaxonomyIds), function ($qq) use ($allowedCateTaxonomyIds) {
    //             $qq->whereHas('taxonomies', function ($tq) use ($allowedCateTaxonomyIds) {
    //                 $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::CATEGORY->value)
    //                     ->whereIn('term_taxonomy.term_taxonomy_id', $allowedCateTaxonomyIds);
    //             });
    //         })
    //         // Lọc theo chuyên mục được chọn
    //         ->when(!empty($filterCategoryTaxonomyIds), function ($qq) use ($filterCategoryTaxonomyIds) {
    //             $qq->whereHas('taxonomies', function ($tq) use ($filterCategoryTaxonomyIds) {
    //                 $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::CATEGORY->value)
    //                     ->whereIn('term_taxonomy.term_taxonomy_id', $filterCategoryTaxonomyIds);
    //             });
    //         })
    //         // Lọc theo tag được chọn
    //         ->when(!empty($filterTagTaxonomyIds), function ($qq) use ($filterTagTaxonomyIds) {
    //             $qq->whereHas('taxonomies', function ($tq) use ($filterTagTaxonomyIds) {
    //                 $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::POSTTAG->value)
    //                     ->whereIn('term_taxonomy.term_taxonomy_id', $filterTagTaxonomyIds);
    //             });
    //         })
    //         ->with(['author', 'taxonomies.term'])
    //         ->orderBy($sortBy, $direction);

    //     $posts = $q->get();

    //     $fileName = 'bai_viet_' . now()->format('Ymd_His') . '.xlsx';
    //     return Excel::download(new PostsExport($posts), $fileName);
    // }

    public function export(Request $request)
    {
        // Dùng chung query V2 (đúng filter + join author/publisher + time_mode + export_key)
        $q = $this->buildExportQueryV2($request);

        // Lấy full data để export (không limit)
        $posts = $q->get();

        $fileName = 'bai_viet_' . now()->format('Ymd_His') . '.xlsx';
        // truyền callback mapRow để PostsExport không lệch
        return Excel::download(new PostsExport($posts, fn($p, $i) => $this->mapExportRowV2($p, $i)), $fileName);
    }


    // xem trước danh sách xuất  
    /**
     * Preview danh sách export (JSON) - dùng cho modal "Xem trước"
     * Không ảnh hưởng export() cũ.
     */
    public function exportPreview(Request $request)
    {
        // limit preview
        $limit = (int) $request->get('limit', 20);
        if ($limit <= 0) $limit = 20;
        if ($limit > 200) $limit = 200;

        $q = $this->buildExportQueryV2($request);

        $total = (clone $q)->count();
        $posts = $q->limit($limit)->get();

        // map ra đúng 8 cột UI (dùng chung 1 nguồn với export)
        $data = [];
        $i = 1;

        foreach ($posts as $p) {
            $row = $this->mapExportRowV2($p, $i);

            $data[] = [
                'tt'             => $row[0],
                'title'          => $row[1],
                'type'           => $row[2],
                'category'       => $row[3],
                'author_name'    => $row[4],
                'publisher_name' => $row[5],
                'published_at'   => $row[6],
                'link'           => $row[7],
            ];

            $i++;
        }


        return response()->json([
            'total' => $total,
            'data'  => $data,
        ]);
    }


    /**
     * Build query cho export/preview phiên bản mới (8 cột + lọc theo time_mode, export_key)
     * Không đụng vào paginate/index logic cũ.
     */
    protected function buildExportQueryV2(Request $request): Builder
    {
        $allowedSort = ['id', 'title', 'slug', 'created_at', 'published_at', 'views'];
        $sortBy = $request->get('sortBy', 'id');
        if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'id';

        $direction = strtolower($request->get('direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';

        // filter giống export() cũ
        $filterPostStatuses = $request->getBase64Param('postStatuses') ?? [];

        $filterCategoryTermIds = $request->getBase64Param('categories') ?? [];
        $filterTagTermIds      = $request->getBase64Param('tags') ?? [];
        $filterAuthorIds       = $request->getBase64Param('author') ?? [];

        // NOTE: modal dùng export_key (khác filter)
        $exportKey = trim(strip_tags((string) $request->query('export_key', '')));
        $filterText = trim(strip_tags((string) $request->query('filter', '')));

        // ưu tiên export_key nếu có, fallback filter
        $keyword = $exportKey !== '' ? $exportKey : $filterText;

        // Nếu là tác giả (không phải editor) thì chỉ xuất bài của mình
        if (!$this->isEditor && $this->isAuthor) {
            $filterAuthorIds = [auth()->id()];
        }

        // quyền chuyên mục user được phép
        $user = auth()->user();
        $userTermPermission = UserTermPermissionDataProvider::getTermIdsByUserId($user->id);
        $categoryTree  = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
        $allowedCates  = $this->filterCategoryByPermission($categoryTree, $userTermPermission);
        $allowedCateTaxonomyIds = $allowedCates->pluck('term_taxonomy_id')->toArray();

        // đổi term_id -> term_taxonomy_id
        $filterCategoryTaxonomyIds = !empty($filterCategoryTermIds)
            ? Taxonomy::whereIn('term_id', $filterCategoryTermIds)->pluck('term_taxonomy_id')->all()
            : [];

        $filterTagTaxonomyIds = !empty($filterTagTermIds)
            ? Taxonomy::whereIn('term_id', $filterTagTermIds)->pluck('term_taxonomy_id')->all()
            : [];

        // ===== Query chính =====
        $q = Post::query()
            ->select('posts.*')
            ->leftJoin('users as au', 'au.id', '=', 'posts.author_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'posts.publisher_id')
            ->addSelect(DB::raw('au.name as author_name'))
            ->addSelect(DB::raw('pu.name as publisher_name'))
            ->where('posts.post_type', $this->postType->value)
            ->when(!empty($filterPostStatuses), fn($qq) => $qq->whereIn('posts.status', $filterPostStatuses))
            ->when(!empty($filterAuthorIds),   fn($qq) => $qq->whereIn('posts.author_id', $filterAuthorIds))
            ->when($keyword !== '', function ($qq) use ($keyword) {
                $qq->where(function ($s) use ($keyword) {
                    $s->where('posts.title', 'like', "%{$keyword}%")
                        ->orWhere('posts.slug', 'like', "%{$keyword}%")
                        ->orWhere('posts.excerpt', 'like', "%{$keyword}%");
                });
            })
            // chỉ chuyên mục user được phép
            ->when(!empty($allowedCateTaxonomyIds), function ($qq) use ($allowedCateTaxonomyIds) {
                $qq->whereHas('taxonomies', function ($tq) use ($allowedCateTaxonomyIds) {
                    $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::CATEGORY->value)
                        ->whereIn('term_taxonomy.term_taxonomy_id', $allowedCateTaxonomyIds);
                });
            })
            // lọc theo chuyên mục chọn
            ->when(!empty($filterCategoryTaxonomyIds), function ($qq) use ($filterCategoryTaxonomyIds) {
                $qq->whereHas('taxonomies', function ($tq) use ($filterCategoryTaxonomyIds) {
                    $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::CATEGORY->value)
                        ->whereIn('term_taxonomy.term_taxonomy_id', $filterCategoryTaxonomyIds);
                });
            })
            // lọc theo tag chọn
            ->when(!empty($filterTagTaxonomyIds), function ($qq) use ($filterTagTaxonomyIds) {
                $qq->whereHas('taxonomies', function ($tq) use ($filterTagTaxonomyIds) {
                    $tq->where('term_taxonomy.taxonomy', EnumsTaxonomy::POSTTAG->value)
                        ->whereIn('term_taxonomy.term_taxonomy_id', $filterTagTaxonomyIds);
                });
            })
            // load taxonomy/term để lấy danh mục
            ->with(['taxonomies.term']);

        // ===== Lọc thời gian theo modal =====
        // “Ngày đăng” ưu tiên published_at, fallback created_at:
        // filter theo COALESCE(published_at, created_at)
        $timeMode = (string) $request->get('time_mode', 'range');

        if ($timeMode === 'range') {
            $from = $request->get('from_date');
            $to   = $request->get('to_date');

            if ($from || $to) {
                $fromDt = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
                $toDt   = $to   ? Carbon::parse($to)->endOfDay()   : Carbon::maxValue();

                $q->whereRaw('COALESCE(posts.published_at, posts.created_at) BETWEEN ? AND ?', [
                    $fromDt->toDateTimeString(),
                    $toDt->toDateTimeString(),
                ]);
            }
        } elseif ($timeMode === 'quarter') {
            $quarter = (int) $request->get('quarter', 1);
            $year    = (int) $request->get('year_quarter', (int) now()->format('Y'));
            $quarter = max(1, min(4, $quarter));

            $startMonth = ($quarter - 1) * 3 + 1;
            $fromDt = Carbon::create($year, $startMonth, 1)->startOfDay();
            $toDt   = (clone $fromDt)->addMonths(3)->subSecond();

            $q->whereRaw('COALESCE(posts.published_at, posts.created_at) BETWEEN ? AND ?', [
                $fromDt->toDateTimeString(),
                $toDt->toDateTimeString(),
            ]);
        } elseif ($timeMode === 'year') {
            $year = (int) $request->get('year', (int) now()->format('Y'));
            $fromDt = Carbon::create($year, 1, 1)->startOfDay();
            $toDt   = Carbon::create($year, 12, 31)->endOfDay();

            $q->whereRaw('COALESCE(posts.published_at, posts.created_at) BETWEEN ? AND ?', [
                $fromDt->toDateTimeString(),
                $toDt->toDateTimeString(),
            ]);
        }

        // sort
        $q->orderBy('posts.' . $sortBy, $direction);

        return $q;
    }


    protected function mapExportRowV2($p, int $stt): array
    {
        $categoryNames = collect($p->taxonomies ?? [])
            ->filter(fn($tx) => ($tx->taxonomy ?? null) === EnumsTaxonomy::CATEGORY->value)
            ->map(fn($tx) => $tx->term->name ?? null)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $publishedAt = $p->published_at ?: $p->created_at;
        $publishedAtStr = $publishedAt ? Carbon::parse($publishedAt)->format('d/m/Y H:i') : '';

        $loaiTin = $p->post_type ?? '';

        // $link = $p->slug ? rtrim(config('app.url'), '/') . '/' . ltrim($p->slug, '/') : '';
        $link = $p->slug ? '/' . trim(config('cmsbackend.public_route_prefix', '') . '/' . $p->slug, '/') : '';

        return [
            $stt,
            (string)($p->title ?? ''),
            (string)$loaiTin,
            (string)$categoryNames,
            (string)($p->author_name ?? ''),
            (string)($p->publisher_name ?? ''),
            (string)$publishedAtStr,
            (string)$link,
        ];
    }


    protected function logPostAction(string $action, ?Post $post = null, array $extra = []): void
    {
        // module name: ưu tiên $this->formName (vd: "Sự kiện", "Banner"...)
        $module = $this->formName ?? 'Bài viết';

        // map action từ UI -> action chuẩn để log
        $key = match ($action) {
            'publish_now'         => 'publish',
            'save_draft'          => 'draft',
            'submit_for_approval' => 'submit_for_approval',
            'approved'            => 'approve',
            'reject'              => 'reject',
            'stop_publishing'     => 'stop_publishing',
            'submit'              => 'update',
            default               => 'update',
        };

        ActivityLogger::log($key, $module, $post, array_merge([
            'post_id'   => $post?->id,
            'post_type' => $post?->post_type ?? ($this->postType->value ?? null),
            'title'     => $post?->title,
            'action_ui' => $action,
        ], $extra));
    }
}
