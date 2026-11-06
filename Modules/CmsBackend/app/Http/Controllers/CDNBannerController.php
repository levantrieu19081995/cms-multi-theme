<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\DataProviders\PostDataProvider;
use App\DataProviders\TagDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Enums\PostType;
use Modules\CmsBackend\Http\Controllers\Shared\BannerController;
use App\Http\Requests\Post\PostUpdateRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\PermissionsEnum;


use Modules\CmsBackend\Services\CDNBannerService;

class CDNBannerController extends BannerController
{
    private $permissionsBase = [
        'canView' => true,
        'canApprove' => true,
        'canReject' => true,
        'canUpdate' => true,
        'canSaveDraft' => true,
        'canSubmitForApproval' => true,
        'canPublishNow' => true,
        'canStopPublishing' => true,
        'canDelete' => true
    ];


    protected $viewData = [];
    protected $cdnBannerService;


    public function __construct(
        PostDataProvider $postDataProvider,
        TaxonomyDataProvider $taxonomyDataProvider,
        TagDataProvider $tagDataProvider,
        CDNBannerService $cdnBannerService
    ) {


        parent::__construct(
            $postDataProvider,
            $taxonomyDataProvider,
            $tagDataProvider,
            PostType::BANNER,
            'cmsbackend::screens.components.partials.post.table-post',
            __('banner'),
            'pages.banner',
            'cdn.banner.index',
            $this->permissionsBase
        );
        $this->cdnBannerService = $cdnBannerService;

        // // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        // $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        // $this->middleware('cms.check.permission:' . PermissionsEnum::BANNER_MANAGER->label())
        //     ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);


    }

    public function index(Request $request)
    {
        // Gọi index() từ lớp cha để lấy dữ liệu
        $parentIndex = parent::index($request);

        // Lấy dữ liệu từ Response View của lớp cha
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CaoDangNgheBE
        return view('cmsbackend::screens.pages.banner.index', $data);
    }

    public function create()
    {
        // Gọi index() từ lớp cha để lấy dữ liệu
        $parentIndex = parent::create();

        // Lấy dữ liệu từ Response View của lớp cha
        $data = $parentIndex->getData();
        $this->viewData['postType'] = PostType::BANNER;
        $this->viewData = array_merge($this->viewData, $data);
        $this->viewData['permissions'] = $this->permissionsBase;
        return view('cmsbackend::screens.pages.banner.edit', $this->viewData);
    }

    public function edit($id)
    {
        $parentEdit = parent::edit($id);
        // Lấy dữ liệu banner từ bảng posts_banners
        $bannerData = $this->cdnBannerService->getBannerById($id);
        $data = $parentEdit->getData();
        $slug = $data['post']->slug;
        if (!Str::startsWith($slug, ['http://', 'https://'])) {
            $slug = request()->getSchemeAndHttpHost() . '/' . ltrim($slug, '/');
        }
        $data['post']->slug = $slug;
        $data['banner'] = $bannerData; // Thêm dữ liệu banner vào mảng dữ liệu
        $this->viewData['permissions'] = $this->permissionsBase;
        $this->viewData['postType'] = PostType::BANNER;
        $this->viewData['view'] = 'cmsbackend::screens.pages.banner.edit';
        $this->viewData['breadcrumbParam'] = $data['post'];
        return view($this->viewData['view'], $this->viewData, $data);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(PostUpdateRequest $request)
    {
        $post = null;

        DB::transaction(function () use ($request, &$post) {
            $post = $this->save($request);

            if ($request->has('ref_id')) {
                $post->content = $request->input('ref_id');
                $post->save();
            }

            // Thêm xử lý tùy chỉnh ở đây, không cần event
            if ($post->post_type === PostType::BANNER->value) {
                $this->cdnBannerService->savePostBanner(
                    $post->id,
                    $request->input('ref_id'),
                    $request->input('redirect'),
                    $request->input('description'),
                    $request->input('btn_text'),
                    $request->boolean('show_text', true)
                );
            }
        });

        // Lấy dữ liệu view sau khi lưu
        $parentIndex = parent::index($request);
        $data = $parentIndex->getData();

        $this->viewData['postType'] = PostType::BANNER;
        $this->viewData = array_merge($this->viewData, $data);
        $this->viewData['permissions'] = $this->permissionsBase;

        return redirect()->route('cms.dashboard.cdn.banner.index')->with('success', __('admin.message.them_moi_banner_thanh_cong'));
    }

    /**
     * Show the specified resource.
     */
    public function update(PostUpdateRequest $request, $id): RedirectResponse
    {
        // dd($request->all());
        parent::update($request, $id);
        $this->cdnBannerService->savePostBanner(
            $id,
            $request->input('ref_id'),
            $request->input('redirect'),
            $request->input('description'),
            $request->input('btn_text'),
            $request->boolean('show_text', true)
        );
        // Gọi index() từ lớp cha để lấy dữ liệu sau khi cập nhật
        $parentIndex = parent::index($request);
        // Lấy dữ liệu từ Response View của lớp cha
        $data = $parentIndex->getData();
        // Trả về view tùy chỉnh của module CaoDangNgheBE
        $this->viewData['postType'] = PostType::BANNER;
        $this->viewData = array_merge($this->viewData, $data);
        $this->viewData['permissions'] = $this->permissionsBase;
        return redirect()->route('cms.dashboard.cdn.banner.index')->with('success', __('admin.message.cap_nhat_banner_thanh_cong'));
    }

    public function destroy($id)
    {
        try {
            // Gọi hàm xóa của lớp cha
            parent::destroy($id);

            // Xóa banner liên quan
            $this->cdnBannerService->deletePostBanner($id);

            // Nếu là AJAX, trả về JSON
            if (request()->ajax()) {
                return response()->json(['message' => __('admin.message.xoa_thanh_cong')], 200);
            }

            // Nếu không phải AJAX, redirect như cũ
            return redirect()->route('cms.dashboard.cdn.banner.index')->with('success', __('admin.message.xoa_banner_thanh_cong'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => __('admin.message.khong_tim_thay_ban_ghi')], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => __('admin.message.ban_khong_co_quyen_xoa_ban_ghi_nay')], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => __('admin.message.loi_he_thong_sp') . $e->getMessage()], 500);
        }
    }
}
