<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\DataProviders\TaxonomyDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Enums\PostType;
use Illuminate\Support\Facades\Auth;
use Modules\CmsFrontend\Services\TinTucService;

class TinTucController extends Controller
{
    protected $tintucService;
    protected TaxonomyDataProvider $taxonomyDataProvider;

    public function __construct(TinTucService $tintucService, TaxonomyDataProvider $taxonomyDataProvider)
    {
        $this->tintucService = $tintucService;
        $this->taxonomyDataProvider = $taxonomyDataProvider;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $category = null)
    {
        $filter = trim(strip_tags((string) $request->query('query')));
        // The search form sends the category as ?category=slug; /tin-tuc/{category} still works.
        $queryCategory = (string) $request->query('category');
        $category ??= preg_match('/^[a-zA-Z0-9-_]+$/', $queryCategory) ? $queryCategory : null;
        $page = $request->page ?? 1;
        $posts = $this->tintucService->paginateTinTuc(
            PostType::POST->value,
            $category,
            $filter,
            5,
            $page,
            null,
            ['published_at:desc']
        );



        $categories = [];
        $items = [];

        $newsList = []; // luôn khởi tạo

        foreach ($posts as $post) {
            // $title = ($category ? "$category - " : "") . $post->title;
            $title =  $post->title;

            $newsList[] = (object)[
                'title' => $title,
                'description' => $post->excerpt,
                'author' => optional($post->author)->name,
                'public' => $post->status ?? "published",
                'public_date' => $post->published_at,
                'image' => $post->getFirstMediaUrl('avatars'),
                'slug' => $post->slug,
                'author_names' => $post->author_names
            ];
        }

        $categoryInfo = $this->taxonomyDataProvider->findBySlug($category, 'category');
        // Internal categories stay hidden from the filter for guests.
        $categoryOptions = $this->taxonomyDataProvider->getCategoryAll()
            ->filter(fn ($taxonomy) => $taxonomy->term && filled($taxonomy->term->name) && (Auth::check() || $taxonomy->loai_chuyen_muc !== 'noi_bo'))
            ->sortBy(fn ($taxonomy) => $taxonomy->term->name)
            ->values();
        $items[] = (object)[
            'category' => $category,
            'list' => $newsList,
        ];
        // dd($posts->toArray());
        // dd($items);
        return view('cmsfrontend::screens.pages.frontend.tin-tuc.index', [
            // 'items' => $items,
            'items' => $items,
            'categories' => $categories,
            'category' => $category,
            'categoryInfo' => $categoryInfo,
            'posts' => $posts, // bạn có thể truyền thêm để dùng phân trang
            'categoryOptions' => $categoryOptions,
            'filter' => $filter,

        ]);
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
    public function show(Request $request)
    {
        $post = $this->tintucService->getChiTietTinTucBySlug($request->slug);
        // dd('Xem bài viếtpost ', $post->toArray());

        if (!$post) {
            // A page linked from the menu but not yet published shows a notice instead of 404. Only the
            // title is exposed (already public in the menu); unpublished news posts still return 404.
            $pendingPage = \App\Models\Post::where('slug', $request->slug)->where('post_type', PostType::PAGE->value)
                ->whereNull('deleted_at')->first(['id', 'title']);
            if ($pendingPage) {
                return response()->view('cmsfrontend::screens.pages.frontend.trang.dang-cap-nhat', ['title' => $pendingPage->title])
                    ->header('X-Robots-Tag', 'noindex');
            }
            abort(404, __('messages.article_missing'));
        }

        // Kiểm tra chuyên mục nội bộ
        $isNoiBo = $post->taxonomies->contains(fn($taxonomy) => $taxonomy->loai_chuyen_muc === 'noi_bo');

        if ($isNoiBo && !Auth::check()) {
            session()->put('url.intended', \Modules\CmsBackend\Support\ModuleUrl::url()->full());
            return redirect()->route('cms.yeu-cau-dang-nhap')
                ->with('warning', __('messages.login_required'));
        }

        // Tăng lượt xem nếu chưa xem
        $sessionKey = 'post_' . $post->id . '_viewed';
        if (!session()->has($sessionKey)) {
            $post->increment('views');
            session()->put($sessionKey, true);
        }

        // Nếu là bài viết
        if ($post->post_type == PostType::POST->value) {
            // Lấy chuyên mục đầu tiên nếu có
            $primaryTaxonomy = $post->taxonomies->first();
            $categorySlug = $primaryTaxonomy?->term?->slug;

            // Lấy bài liên quan
            $posts_lienquanQuery = $this->tintucService->getList(
                PostType::POST->value,
                $categorySlug,
                '',
                [$post->id],
                ['views:desc']
            );

            $posts_lienquan = $posts_lienquanQuery->take(10)->get();

            // Lấy bình luận
            $comments = $this->tintucService->getDanhSachBinhLuan($post->id);
            //Lấy file đính kèm
            $mediaItems = $post->getMedia('fileDinhKem');
            $mediaList = $mediaItems->map(function ($media) {
                return [
                    "name" => $media->name,
                    "url" => $media->getUrl(),
                    "type" => $media->mime_type,
                ];
            })->values();
            $post->fileDinhKem = $mediaList;
            $attachmentFiles = $mediaList;
            // dd('bài viết ', $post->toArray());
            return view('cmsfrontend::screens.pages.frontend.tin-tuc.chi-tiet-tin-tuc', compact(
                'post',
                'attachmentFiles',
                'posts_lienquan',
                'comments'
            ));
        }
        // dd('không bài viết ', $post->toArray());
        // Nếu không phải bài viết
        return view('cmsfrontend::screens.pages.frontend.tin-tuc.chi-tiet-onepage', compact('post'));
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


    public function canAccessNoiBo()
    {
        return view('cmsfrontend::screens.pages.frontend.thong-bao.not-auth');
    }

    /**
     * Shown when a visitor switches language on a page that has no translation in the target language
     * (see LocalizedUrlResolver). A notice, never the other language's content; not indexed.
     */
    public function contentUpdating()
    {
        return response()->view('cmsfrontend::screens.pages.frontend.trang.dang-cap-nhat', ['title' => __('pages.noi_dung_dang_duoc_cap_nhat')])
            ->header('X-Robots-Tag', 'noindex');
    }
}
