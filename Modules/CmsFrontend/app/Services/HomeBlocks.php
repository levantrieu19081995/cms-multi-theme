<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Services;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\Taxonomy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Models\PostsBanners;
use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Data for the PLHĐ homepage blocks: work schedules (Department leaders, Board of Directors)
 * and the two traceability portals. Categories and links come from Settings (HomeSettings).
 */
class HomeBlocks
{
    private const SCHEDULES = [
        HomeSettings::SCHEDULE_DEPARTMENT => 'schedule_department',
        HomeSettings::SCHEDULE_BOARD => 'schedule_board',
    ];

    /** Default block titles (shown when no custom title is set); keyed like SCHEDULES. */
    public const SCHEDULE_TITLES = [
        HomeSettings::SCHEDULE_DEPARTMENT => 'Lịch làm việc Lãnh đạo Sở NN&MT',
        HomeSettings::SCHEDULE_BOARD => 'Lịch làm việc Ban giám đốc',
    ];

    /** Default title/caption of the two traceability tiles. */
    public const TRACE_TEXTS = [
        'market' => ['title' => 'Sàn giao dịch truy xuất nguồn gốc nông sản', 'caption' => 'Tra cứu, giao dịch nông sản có truy xuất nguồn gốc'],
        'portal' => ['title' => 'Cổng thông tin truy xuất nguồn gốc xuất xứ', 'caption' => 'Truy xuất xuất xứ một số sản phẩm chủ lực'],
    ];

    /**
     * Dữ liệu nền của trang chủ. `$needs` (HomepageDataNeeds::forPlan) giới hạn các nhóm cần truy vấn; null = tất cả.
     *
     * @param  array<string, true>|null  $needs
     */
    public function data(?array $needs = null): array
    {
        $need = fn (string $group): bool => $needs === null || isset($needs[$group]);
        $settings = $need('schedules') ? HomeSettings::all() : [];

        return [
            'homeSchedules' => $need('schedules') ? $this->schedules($settings) : [],
            'homeTraceLinks' => $need('schedules') ? $this->traceLinks($settings) : [],
            'homeCategoryTabs' => $need('tabs') ? $this->categoryTabs() : [],
            'homeBanners' => $need('banners') ? $this->banners() : [],
            'homeTheme' => app(HomeTheme::class),
            'homeSections' => $need('sections') ? $this->sections() : [],
            'homeStatistics' => HomeSettings::statistics((array) config('cmsbackend.screens.themes.nam-binh-duong.statistics', [])),
        ];
    }

    /**
     * "Chuyên mục nổi bật": one tab per category chosen in Settings (HomeSettings::FEATURED_CATEGORIES),
     * each with the 4 latest public posts. Tabs without posts are dropped.
     *
     * @return array<int, array{id: int, title: string, url: string, stories: \Illuminate\Support\Collection}>
     */
    public function categoryTabs(?array $ids = null): array
    {
        $ids ??= HomeSettings::featuredCategories();
        if (! $ids) {
            return [];
        }
        $categories = Taxonomy::with('term')->whereIn('term_taxonomy_id', $ids)->where('taxonomy', 'category')->get()
            ->filter(fn ($category) => filled($category->term?->slug))->keyBy('term_taxonomy_id');

        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = $categories->keys()->map(fn ($id) => (int) $id)->values()->all();
        $postMorphClass = (new Post)->getMorphClass();
        $now = now();

        // Rank all selected categories in one query. Running one whereHas query per category is very
        // expensive on installations whose legacy term_relationships table has no database indexes.
        $hiddenPostIds = DB::table('term_relationships as hidden_rel')
            ->join('term_taxonomy as hidden_tax', 'hidden_tax.term_taxonomy_id', '=', 'hidden_rel.term_taxonomy_id')
            ->where('hidden_rel.object_type', $postMorphClass)
            ->where('hidden_tax.loai_chuyen_muc', 'noi_bo')
            ->pluck('hidden_rel.object_id');

        $ranked = DB::table('term_relationships as featured_rel')
            ->join('posts as featured_post', 'featured_post.id', '=', 'featured_rel.object_id')
            ->where('featured_rel.object_type', $postMorphClass)
            ->whereIn('featured_rel.term_taxonomy_id', $categoryIds)
            ->where('featured_post.post_type', PostType::POST->value)
            ->where('featured_post.status', PostStatus::PUBLISHED->value)
            ->whereNull('featured_post.deleted_at')
            ->where('featured_post.published_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('featured_post.publication_date_expires')
                    ->orWhere('featured_post.publication_date_expires', '>=', $now);
            })
            ->when($hiddenPostIds->isNotEmpty(), fn ($query) => $query->whereNotIn('featured_post.id', $hiddenPostIds))
            ->when(app(\App\Services\LocaleManager::class)->publicLocale(), fn ($query, $locale) => $query
                ->join('post_translations as featured_translation', function ($join) use ($locale) {
                    $join->on('featured_translation.post_id', '=', 'featured_post.id')->where('featured_translation.locale', '=', $locale);
                })
                ->whereNotNull('featured_translation.title')->where('featured_translation.title', '!=', ''))
            ->select([
                'featured_rel.term_taxonomy_id as category_id',
                'featured_post.id as post_id',
            ])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY featured_rel.term_taxonomy_id ORDER BY featured_post.published_at DESC, featured_post.id DESC) AS category_rank');

        $rankedRows = DB::query()->fromSub($ranked, 'ranked_posts')
            ->where('category_rank', '<=', 4)
            ->orderBy('category_id')
            ->orderBy('category_rank')
            ->get();

        $storiesById = Post::with(['media', 'taxonomies.term'])
            ->whereIn('id', $rankedRows->pluck('post_id')->unique())
            ->get()
            ->keyBy('id');

        $rowsByCategory = $rankedRows->groupBy(fn ($row) => (int) $row->category_id);
        $tabs = [];
        foreach ($ids as $id) {
            $category = $categories->get($id);
            if (! $category) {
                continue;
            }
            $stories = collect($rowsByCategory->get((int) $id, []))
                ->map(fn ($row) => $storiesById->get((int) $row->post_id))
                ->filter()
                ->values();
            if ($stories->isEmpty()) {
                continue;
            }
            $tabs[] = [
                'id' => (int) $id, 'title' => $category->term->name, 'stories' => $stories,
                'url' => ModuleUrl::route('cms.tin-tuc.index', ['category' => $category->term->slug]),
            ];
        }

        return $tabs;
    }

    /**
     * Homepage hero slides from the Banner admin screen: published banners marked "Hiển thị trang chủ", newest first.
     * Title = banner title, text = "Thông tin mô tả", button = "Nhãn nút" linking to the post chosen in "Link đến bài viết".
     *
     * @return array<int, array{title: string, description: ?string, image: string, url: ?string, button: string, width: int, height: int}>
     */
    private function banners(): array
    {
        $banners = Post::with('media')
            ->where('post_type', PostType::BANNER->value)->where('status', PostStatus::PUBLISHED->value)
            ->where('show_home_page', 1)->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', now()))
            ->orderByDesc('created_at')->limit(6)->get();
        if ($banners->isEmpty()) {
            return [];
        }
        $settings = PostsBanners::whereIn('post_id', $banners->pluck('id'))->get()->keyBy('post_id');
        // Hydrate the models instead of plucking directly: on an English request the public locale scope
        // replaces the slug after hydration. A direct pluck would keep the Vietnamese slug and create a 404 link.
        $targets = Post::whereIn('id', $settings->where('redirect', 1)->pluck('ref_id')->filter())
            ->where('status', PostStatus::PUBLISHED->value)->whereNull('deleted_at')
            ->get(['id', 'slug'])->pluck('slug', 'id');

        $localized = app(\App\Services\LocaleManager::class)->publicLocale() !== null;

        return $banners->map(function (Post $banner) use ($settings, $targets, $localized) {
            $media = $banner->getFirstMedia('avatars');
            $image = trim((string) $media?->getUrl());
            if ($image === '') {
                return null;
            }
            $size = is_file($media->getPath()) ? @getimagesize($media->getPath()) : false;
            $setting = $settings->get($banner->id);
            $slug = $setting?->redirect ? $targets->get((int) $setting->ref_id) : null;

            return [
                'title' => (string) $banner->title,
                // Cột show_text có thể chưa tồn tại (chưa migrate) hoặc NULL: coi như hiện chữ như trước.
                'show_text' => (bool) ($setting->show_text ?? true),
                // posts_banners has no translation table. On another language use only the translated Post fields,
                // never its Vietnamese-only description/button text.
                'description' => $localized
                    ? ($banner->excerpt ?: null)
                    : (filled($setting?->description) ? $setting->description : ($banner->excerpt ?: null)),
                'image' => $image,
                'url' => $slug ? ModuleUrl::route('cms.tin-tuc.show', ['slug' => $slug]) : null,
                'button' => $localized
                    ? __('frontend.ui.tim_hieu')
                    : (filled($setting?->btn_text) ? $setting->btn_text : __('frontend.ui.tim_hieu')),
                'width' => $size[0] ?? 1200, 'height' => $size[1] ?? 400,
            ];
        })->filter()->values()->all();
    }

    /** @return array<int, array{key: string, title: string, url: ?string, items: array}> */
    public function schedules(array $settings, array $titles = []): array
    {
        $blocks = [];
        foreach (self::SCHEDULES as $key => $section) {
            $title = filled($titles[$key] ?? null) ? (string) $titles[$key] : self::SCHEDULE_TITLES[$key];
            $taxonomyId = (int) ($settings[$key] ?? 0);
            $taxonomy = $taxonomyId ? Taxonomy::with('term')->find($taxonomyId) : null;
            if (! $taxonomy?->term?->slug) {
                continue;
            }
            $items = Post::select(['id', 'title', 'slug', 'published_at'])
                ->where('post_type', PostType::POST->value)->where('status', PostStatus::PUBLISHED->value)
                ->whereNull('deleted_at')->where('published_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', now()))
                ->whereHas('taxonomies', fn ($query) => $query->where('term_taxonomy.term_taxonomy_id', $taxonomyId))
                ->orderByDesc('published_at')->limit(5)->get()
                ->map(fn (Post $post) => [
                    'title' => $post->title,
                    'url' => ModuleUrl::route('cms.tin-tuc.show', ['slug' => $post->slug]),
                    'date' => $post->published_at ? Carbon::parse($post->published_at)->format('d/m/Y') : null,
                ])->all();
            $blocks[] = ['key' => $section, 'title' => $title, 'url' => ModuleUrl::route('cms.tin-tuc.index', ['category' => $taxonomy->term->slug]), 'items' => $items];
        }

        return $blocks;
    }

    /** Links open in a new tab; without a URL the tile is hidden. */
    public function traceLinks(array $settings, array $texts = []): array
    {
        $text = fn (string $tile, string $part) => filled($texts[$tile][$part] ?? null) ? (string) $texts[$tile][$part] : self::TRACE_TEXTS[$tile][$part];
        $links = [
            ['title' => $text('market', 'title'), 'caption' => $text('market', 'caption'), 'icon' => 'ri-store-2-line',
                'url' => $settings[HomeSettings::TRACE_MARKET_URL], 'image' => $settings[HomeSettings::TRACE_MARKET_IMAGE]],
            ['title' => $text('portal', 'title'), 'caption' => $text('portal', 'caption'), 'icon' => 'ri-qr-scan-2-line',
                'url' => $settings[HomeSettings::TRACE_PORTAL_URL], 'image' => $settings[HomeSettings::TRACE_PORTAL_IMAGE]],
        ];

        return collect($links)
            ->map(fn ($link) => ['url' => filled($link['url']) ? $link['url'] : null, 'image' => filled($link['image']) ? ModuleUrl::url($link['image']) : null] + $link)
            ->filter(fn ($link) => $link['url'])
            ->values()->all();
    }

    /** @return array<string, array{items: \Illuminate\Support\Collection, url: ?string}> */
    private function sections(): array
    {
        $theme = app(\Modules\CmsBackend\Services\ScreenTheme::class)->key('public');
        $slugs = config('cmsbackend.screens.themes.'.$theme.'.home_sections', []);
        if ($slugs === []) {
            return [];
        }
        $categories = Taxonomy::query()->with('term')->where('taxonomy', 'category')
            ->whereHas('term', fn ($query) => $query->whereIn('slug', array_values($slugs)))
            ->get()->keyBy(fn ($category) => $category->term->getRawOriginal('slug'));
        $sections = [];
        foreach ($slugs as $section => $slug) {
            $sections[$section] = $this->sectionOf($categories->get($slug));
        }

        return $sections;
    }

    private const SECTION_MEMO = '_hlm_sections';

    /**
     * Cụm theo chuyên mục do Homepage Layout Manager chọn (cùng quy tắc hiển thị công khai như các cụm theo slug trong screens.php).
     * Kết quả nhớ theo request; nhiều instance dùng `sectionsByCategories()` để nạp một lần.
     *
     * @return array{items: \Illuminate\Support\Collection, url: ?string}
     */
    public function sectionByCategory(int $taxonomyId): array
    {
        return $this->sectionsByCategories([$taxonomyId])[$taxonomyId];
    }

    /**
     * Nạp bài của nhiều chuyên mục bằng số truy vấn cố định (chuyên mục, xếp hạng bài, bài + media), không phụ thuộc số chuyên mục.
     *
     * @param  int[]  $ids
     * @return array<int, array{items: \Illuminate\Support\Collection, url: ?string}>
     */
    public function sectionsByCategories(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        $memo = (array) request()->attributes->get(self::SECTION_MEMO, []);
        $missing = array_values(array_diff($ids, array_keys($memo)));
        if ($missing !== []) {
            $categories = Taxonomy::query()->with('term')->where('taxonomy', 'category')->whereIn('term_taxonomy_id', $missing)->get()->keyBy('term_taxonomy_id');
            $eligible = $categories->filter(fn ($category) => $category->loai_chuyen_muc !== 'noi_bo')->keys()->map(fn ($id) => (int) $id)->all();
            $storiesById = collect();
            $rowsByCategory = collect();
            if ($eligible !== []) {
                $postMorphClass = (new Post)->getMorphClass();
                $now = now();
                $hiddenPostIds = DB::table('term_relationships as hidden_rel')
                    ->join('term_taxonomy as hidden_tax', 'hidden_tax.term_taxonomy_id', '=', 'hidden_rel.term_taxonomy_id')
                    ->where('hidden_rel.object_type', $postMorphClass)->where('hidden_tax.loai_chuyen_muc', 'noi_bo')->pluck('hidden_rel.object_id');
                $ranked = DB::table('term_relationships as section_rel')
                    ->join('posts as section_post', 'section_post.id', '=', 'section_rel.object_id')
                    ->where('section_rel.object_type', $postMorphClass)->whereIn('section_rel.term_taxonomy_id', $eligible)
                    ->whereIn('section_post.post_type', [PostType::POST->value, PostType::PAGE->value])
                    ->where('section_post.status', PostStatus::PUBLISHED->value)->whereNull('section_post.deleted_at')
                    ->where('section_post.published_at', '<=', $now)
                    ->where(fn ($query) => $query->whereNull('section_post.publication_date_expires')->orWhere('section_post.publication_date_expires', '>=', $now))
                    ->when($hiddenPostIds->isNotEmpty(), fn ($query) => $query->whereNotIn('section_post.id', $hiddenPostIds))
                    ->when(app(\App\Services\LocaleManager::class)->publicLocale(), fn ($query, $locale) => $query
                        ->join('post_translations as section_translation', function ($join) use ($locale) {
                            $join->on('section_translation.post_id', '=', 'section_post.id')->where('section_translation.locale', '=', $locale);
                        })
                        ->whereNotNull('section_translation.title')->where('section_translation.title', '!=', ''))
                    ->select(['section_rel.term_taxonomy_id as category_id', 'section_post.id as post_id'])
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY section_rel.term_taxonomy_id ORDER BY section_post.published_at DESC, section_post.id DESC) AS category_rank');
                $rows = DB::query()->fromSub($ranked, 'ranked_section_posts')->where('category_rank', '<=', 4)->orderBy('category_id')->orderBy('category_rank')->get();
                $storiesById = Post::query()->with(['media', 'taxonomies.term'])->whereIn('id', $rows->pluck('post_id')->unique())->get()->keyBy('id');
                $rowsByCategory = $rows->groupBy(fn ($row) => (int) $row->category_id);
            }
            foreach ($missing as $id) {
                $category = $categories->get($id);
                $items = collect($rowsByCategory->get($id, []))->map(fn ($row) => $storiesById->get((int) $row->post_id))->filter()->values();
                $memo[$id] = [
                    'items' => $items,
                    'url' => $category !== null && $category->loai_chuyen_muc !== 'noi_bo' && filled($category->term?->slug)
                        ? ModuleUrl::route('cms.tin-tuc.index', ['category' => $category->term->slug]) : null,
                ];
            }
            request()->attributes->set(self::SECTION_MEMO, $memo);
        }

        return array_intersect_key($memo, array_flip($ids));
    }

    /**
     * Bài thông báo của một chuyên mục (cụm Thông báo): bài bật "Hiển thị trang chủ", công khai, mới nhất trước. Cùng quy tắc với HomeController::index().
     *
     * @return array{info: ?Taxonomy, posts: \Illuminate\Support\Collection}
     */
    public function noticePosts(int $taxonomyId): array
    {
        $memo = (array) request()->attributes->get('_hlm_notices', []);
        if (! isset($memo[$taxonomyId])) {
            $info = $taxonomyId > 0 ? Taxonomy::query()->with('term')->find($taxonomyId) : null;
            $posts = collect();
            if ($info?->term?->slug) {
                $now = now();
                $posts = Post::with(['author', 'media', 'taxonomies.term'])
                    ->where('post_type', PostType::POST->value)->where('status', PostStatus::PUBLISHED->value)
                    ->whereNull('deleted_at')->where('published_at', '<=', $now)
                    ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', $now))
                    ->whereDoesntHave('taxonomies', fn ($query) => $query->where('loai_chuyen_muc', 'noi_bo'))
                    ->where('show_home_page', 1)
                    ->whereHas('taxonomies', fn ($query) => $query->where('term_taxonomy.term_taxonomy_id', $taxonomyId))
                    ->orderByDesc('published_at')->limit(4)->get();
            }
            $memo[$taxonomyId] = ['info' => $info, 'posts' => $posts];
            request()->attributes->set('_hlm_notices', $memo);
        }

        return $memo[$taxonomyId];
    }

    /**
     * Văn bản mới nhất, có thể lọc theo loại/nhóm (cùng quy tắc với HomeController::index()).
     *
     * @param  array{column: string, id: int}|null  $filter
     */
    public function documents(?array $filter): \Illuminate\Support\Collection
    {
        return \Modules\CmsBackend\Models\VanBan::with(['loaiVanBan', 'nhomVanBan'])
            ->whereDate('vb_ngay_cong_bo', '<=', today())
            ->when($filter, fn ($query, $f) => $query->where($f['column'], $f['id']))
            ->orderByDesc('vb_ngay_cong_bo')->limit(4)->get();
    }

    /** @return array{items: \Illuminate\Support\Collection, url: ?string} */
    private function sectionOf(?Taxonomy $category): array
    {
        $items = collect();
        if ($category !== null && $category->loai_chuyen_muc !== 'noi_bo') {
            $items = Post::query()->with(['media', 'taxonomies.term'])
                ->whereIn('post_type', [PostType::POST->value, PostType::PAGE->value])
                ->where('status', PostStatus::PUBLISHED->value)->whereNull('deleted_at')
                ->where('published_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', now()))
                ->whereDoesntHave('taxonomies', fn ($query) => $query->where('loai_chuyen_muc', 'noi_bo'))
                ->whereHas('taxonomies', fn ($query) => $query->where('term_taxonomy.term_taxonomy_id', $category->term_taxonomy_id))
                ->latest('published_at')->limit(4)->get();
        }

        return [
            'items' => $items,
            'url' => $category !== null && $category->loai_chuyen_muc !== 'noi_bo'
                // On a page in another language the link uses that language's slug; a category without one has no link.
                ? (filled($category->term?->slug) ? ModuleUrl::route('cms.tin-tuc.index', ['category' => $category->term->slug]) : null) : null,
        ];
    }
}
