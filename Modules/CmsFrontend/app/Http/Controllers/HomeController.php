<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Http\Controllers;

use App\DataProviders\OptionDataProvider;
use App\DataProviders\PostDataProvider;
use App\DataProviders\TaxonomyDataProvider;
use App\Enums\OptionEnum;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\CDNBannerService;
use Modules\CmsBackend\Services\DichVuService;
use Modules\CmsBackend\Services\DoiTacService;
use Modules\CmsBackend\Services\GuongTieuBieuService;
use Modules\CmsFrontend\DataProviders\ImageLibraryDataProvider;
use Modules\CmsFrontend\DataProviders\VideoDataProvider;
use Modules\CmsFrontend\Services\BannerService;
use Modules\CmsFrontend\Services\TinTucService;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected DichVuService $DichVuService;

    protected DoiTacService $doiTacService;

    protected GuongTieuBieuService $guongTieuBieuService;

    protected TinTucService $tintucService;

    protected OptionDataProvider $optionDataProvider;

    protected TaxonomyDataProvider $taxonomyDataProvider;

    protected ImageLibraryDataProvider $imageLibraryDataProvider;

    protected VideoDataProvider $videoDataProvider;

    protected BannerService $bannerService;

    protected CDNBannerService $cdnBannerService;

    public function __construct(
        DichVuService $DichVuService,
        DoiTacService $doiTacService,
        GuongTieuBieuService $guongTieuBieuService,
        TinTucService $tintucService,
        OptionDataProvider $optionDataProvider,
        TaxonomyDataProvider $taxonomyDataProvider,
        ImageLibraryDataProvider $imageLibraryDataProvider,
        VideoDataProvider $videoDataProvider,
        BannerService $bannerService,
        CDNBannerService $cdnBannerService

    ) {

        $this->DichVuService = $DichVuService;
        $this->doiTacService = $doiTacService;
        $this->guongTieuBieuService = $guongTieuBieuService;
        $this->tintucService = $tintucService;
        $this->optionDataProvider = $optionDataProvider;
        $this->taxonomyDataProvider = $taxonomyDataProvider;
        $this->imageLibraryDataProvider = $imageLibraryDataProvider;
        $this->videoDataProvider = $videoDataProvider;
        $this->bannerService = $bannerService;
        $this->cdnBannerService = $cdnBannerService;
    }

    public function index()
    {
        // Dữ liệu nền chỉ truy vấn cho các nhóm mà cụm đang bật của bố cục cần (HomepageDataNeeds); cụm còn lại ghi đè theo cấu hình ở HomepageDataResolver.
        $managed = app(\Modules\CmsFrontend\Homepage\HomepageRenderer::class)->active(app(\Modules\CmsBackend\Services\ScreenTheme::class)->key('public'));
        $needs = \Modules\CmsFrontend\Homepage\HomepageDataNeeds::forPlan($managed['plan'] ?? null);
        $need = fn (string $group): bool => isset($needs[$group]);

        // Lấy 10 sự kiện mới nhất
        $events = ! $need('events') ? collect() : $this->publicHomePosts(PostType::EVENT->value)
            ->where('post_type', PostType::EVENT->value)
            ->where('show_home_page', 1)
            ->where('status', PostStatus::PUBLISHED->value)
            ->whereNull('deleted_at')
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get();

        $postlist = ! $need('news') ? collect() : $this->publicHomePosts(PostType::POST->value)
            ->where('show_home_page', 1)->orderByDesc('published_at')->limit(11)->get();
        $firstPost = $postlist->take(1); // mảng chứa 1 phần tử đầu tiên
        $posts = $postlist->skip(1); // mảng chứa 5 phần tử tiếp theo

        // Lấy danh sách dịch vụ
        $dichvus = $need('services') ? $this->DichVuService->getAllActive() : collect();
        $links = $need('links') ? $this->doiTacService->getShowOnHome()->loadMissing('media') : collect();
        $guongTieuBieu = $need('examples') ? $this->guongTieuBieuService->getShowOnHome()->loadMissing('media') : collect();

        // Lấy danh sách bài biết từ chuyên mục cụ thể cho partial thông tin

        // Khởi tạo biến mặc định
        $categoryInfo = null;
        $categoryPosts = collect();

        if ($need('notices')) {
            // Lấy ID chuyên mục từ cấu hình
            $categoryOption = $this->optionDataProvider->getOptionByKey(OptionEnum::WEB_CATEGORY_HOME->value);
            $categoryId = (int) $categoryOption?->value;

            if ($categoryId) {
                $categoryInfo = $this->taxonomyDataProvider->findByIdIn([$categoryId])->first();
                if ($categoryInfo?->term?->slug) {
                    $categoryPosts = $this->publicHomePosts(PostType::POST->value)
                        ->where('show_home_page', 1)
                        ->whereHas('taxonomies', fn ($query) => $query->where('term_taxonomy.term_taxonomy_id', $categoryId))
                        ->orderByDesc('published_at')->limit(4)->get();
                }
            }
        }

        // Cụm "Văn bản, tài liệu": lọc theo loại/nhóm văn bản chọn ở Cài đặt trang chủ (HomeSettings::DOCUMENT_FILTER); chưa chọn = mới nhất.
        $homepageDocuments = collect();
        if ($need('documents')) {
            $documentFilter = \Modules\CmsBackend\Support\HomeSettings::documentFilter();
            $homepageDocuments = \Modules\CmsBackend\Models\VanBan::with(['loaiVanBan', 'nhomVanBan'])
                ->whereDate('vb_ngay_cong_bo', '<=', today())
                ->when($documentFilter, fn ($query, $filter) => $query->where($filter['column'], $filter['id']))
                ->orderByDesc('vb_ngay_cong_bo')->limit(4)->get();
        }

        // lấy danh sách hình ảnh hình ảnh
        $libraryImages = collect();
        if ($need('library')) {
            $libraryImages = $this->imageLibraryDataProvider->getThuVienHinhAnhTrangChuList();
            $libraryImages = $libraryImages->loadMissing(['media', 'taxonomies.term'])->reject(
                fn ($post) => $post->taxonomies->contains(fn ($taxonomy) => $taxonomy->loai_chuyen_muc === 'noi_bo')
            );
        }

        $videos = collect();
        if ($need('videos')) {
            $raw = $this->videoDataProvider->getDanhSachVideoTrangChu();
            $raw = $raw->load('taxonomies')->reject(
                fn ($post) => $post->taxonomies->contains(fn ($taxonomy) => $taxonomy->loai_chuyen_muc === 'noi_bo')
            );
            $videos = collect($raw)->map(function ($v) {
                $source = \Modules\CmsFrontend\Support\VideoSource::of($v);

                return [
                    'id' => $v->id, 'slug' => $v->slug, 'title' => $v->title ?? '', 'poster' => $source['poster'] ?? '',
                    'type' => in_array($source['type'], ['file', 'youtube'], true) ? $source['type'] : 'url',
                    'embed_url' => $source['url'] ?? '',
                ];
            })
                ->filter(fn ($x) => ! empty($x['embed_url'])) // bỏ phần tử trống
                ->values();
        }

        // The editorial masthead replaces the homepage banner carousel.
        $banners = collect();

        return view(
            'cmsfrontend::screens.home',
            compact(
                'events',
                'dichvus',
                'links',
                'guongTieuBieu',
                'firstPost',
                'posts',
                'categoryInfo',
                'categoryPosts',
                'libraryImages',
                'videos',
                'banners',
                'homepageDocuments'
            ) + app(\Modules\CmsFrontend\Services\HomeBlocks::class)->data($needs)
        );
    }

    private function publicHomePosts(string $type): \Illuminate\Database\Eloquent\Builder
    {
        return Post::with(['author', 'media', 'taxonomies.term'])
            ->where('post_type', $type)->where('status', PostStatus::PUBLISHED->value)
            ->whereNull('deleted_at')->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', now()))
            ->whereDoesntHave('taxonomies', fn ($query) => $query->where('loai_chuyen_muc', 'noi_bo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cmsfrontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cmsfrontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cmsfrontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function searchPostByName(string $name)
    {
        $postDataProvider = new PostDataProvider;

        $posts = $postDataProvider->paginate(
            'post',          // postTypes
            ['published'],   // postStatuses
            null,            // authorId
            null,            // categoryIds
            null,            // tagIds
            $name,           // filter: Tìm trong cột title
            'id',            // sortBy
            'desc'           // order
        );

        if (! $posts || $posts->isEmpty()) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Không tìm thấy bài viết.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Danh sách bài viết tìm thấy.',
            'data' => $posts->values(),
        ]);
    }
}
