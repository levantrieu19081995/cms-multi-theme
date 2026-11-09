<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Models\VanBan;
use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsFrontend\Http\Controllers\HomeController;
use Tests\TestCase;

final class SbutThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('cms_workspace', DB::connection()->getDatabaseName());
        DB::beginTransaction();
        // Bật/tắt chức năng trên database dev do Developer đặt (có thể đang tắt): test tự bật Liên hệ trong transaction và hoàn lại khi kết thúc.
        app(\Modules\CmsBackend\Features\FeatureRegistry::class)->setEnabled('contact', true, null);
        Option::query()->updateOrCreate(['key' => 'cms_public_theme'], ['value' => 'nam-binh-duong', 'autoload' => 1]);
    }

    protected function tearDown(): void
    {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        app(\Modules\CmsBackend\Features\FeatureRegistry::class)->forgetStates();
        parent::tearDown();
    }

    public function test_theme_is_registered_only_for_public_and_preserves_default(): void
    {
        $screens = app(ScreenTheme::class);
        $this->assertArrayHasKey('nam-binh-duong', $screens->choices('public'));
        $this->assertArrayHasKey('default', $screens->choices('public'));
        $this->assertArrayNotHasKey('nam-binh-duong', $screens->choices('admin'));
        $this->assertSame('default', $screens->key('admin'));
        $this->assertStringContainsString('nam-binh-duong', $screens->path('public', 'home'));
        $this->assertStringContainsString('default', $screens->path('public', 'components.home.story-card'));
        $this->assertGreaterThanOrEqual(2, config('cmsbackend.screens.themes.nam-binh-duong.version'));
    }

    public function test_home_has_sbut_identity_and_every_existing_feature_group(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertSee('data-public-theme="nam-binh-duong"', false)
            ->assertSee('Trường Đại học Công nghệ Nam Bình Dương')
            ->assertSee('Khai phóng – Ưu tú – Cống hiến')
            ->assertSee('sbut-hero-title', false)
            ->assertSee('Khám phá trên trang')
            ->assertSee('class="section-shortcuts"', false)
            ->assertSee('<dialog id="sbut-site-search"', false)
            ->assertSee('Bạn đang tìm điều gì?')
            ->assertSee('aria-label="Đóng tìm kiếm"', false)
            ->assertSee('class="sbut-footer-top"', false)
            ->assertDontSee('class="footer-top"', false)
            ->assertDontSee('cms-masthead-image', false)
            ->assertDontSee('Trang thông tin Khuyến nông Cần Thơ');
        foreach (['trang-chu', 'gioi-thieu', 'dao-tao', 'con-so-noi-bat', 'nghien-cuu', 'tin-tuc', 'tuyen-sinh', 'lien-ket-nhanh', 'video-gioi-thieu', 'doi-tac'] as $section) {
            $response->assertSee('id="' . $section . '"', false);
        }
        $response->assertSee('Chương trình đào tạo tiêu biểu.')->assertSee('Thành tựu nghiên cứu.')->assertSee('Video giới thiệu trường.');
        foreach (['cms.tin-tuc.index', 'cms.su-kien', 'cms.frontend.vanban.index', 'cms.frontend.dichvu.index', 'cms.hinh-anh.danh-sach', 'cms.video.danh-sach', 'cms.frontend.hoidap.index', 'cms.lien-he'] as $route) {
            $response->assertSee('href="' . route($route) . '"', false);
        }
    }

    public function test_public_lists_forms_and_search_share_the_new_theme(): void
    {
        foreach (['/tin-tuc', '/tin-tuc?query=SBUT', '/su-kien', '/van-ban', '/thu-vien-hinh-anh', '/thu-vien-video', '/lien-he', '/hoi-dap', '/hoi-dap/dat-cau-hoi', '/dich-vu'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-public-theme="nam-binh-duong"', false)->assertSee('sbut-footer', false);
        }
        $this->get('/lien-he')->assertSee('name="_token"', false)->assertSee('name="message"', false)->assertSee('action="' . route('cms.lien-he.store') . '"', false);
        $this->get('/hoi-dap/dat-cau-hoi')->assertSee('action="' . route('cms.frontend.hoidap.store') . '"', false);
    }

    public function test_existing_content_details_render_without_new_controllers(): void
    {
        $data = app(HomeController::class)->index()->getData();
        $post = $data['firstPost']->first();
        $this->assertNotNull($post);
        $this->get(route('cms.tin-tuc.show', $post->slug))->assertOk()->assertSee($post->title)->assertSee('data-public-theme="nam-binh-duong"', false);
        foreach ($data['events']->take(1) as $event) {
            $this->get(route('cms.su-kien.chi-tiet', $event->slug))->assertOk()->assertSee($event->title);
        }
        foreach ($data['libraryImages']->take(1) as $album) {
            $this->get(route('cms.hinh-anh.show', $album->slug))->assertOk()->assertSee($album->title);
        }
        foreach ($data['links']->take(1) as $partner) {
            $this->get('/')->assertOk()->assertSee($partner->title)->assertSee($partner->excerpt);
            $this->get(route('cms.doi-tac.show', $partner->slug))->assertOk()->assertSee($partner->title);
        }
        foreach ($data['guongTieuBieu']->take(1) as $example) {
            $this->get(route('cms.guong-tieu-bieu.detail-by-id', $example->url_key))->assertOk()->assertSee($example->title);
        }
        $video = collect($data['videos'])->first(fn($item) => ! empty($item['slug']));
        if ($video !== null) {
            $this->get(route('cms.video.show', $video['slug']))->assertOk()->assertSee('data-public-theme="nam-binh-duong"', false);
        }
        $document = VanBan::query()->first();
        if ($document !== null) {
            $this->get(route('cms.frontend.vanban.show', $document->getKey()))->assertOk();
        }
    }

    public function test_theme_can_switch_back_without_changing_brand_or_admin(): void
    {
        $unit = Option::query()->where('key', 'cms_unit_name')->value('value');
        $posts = DB::table('posts')->count();
        $admin = User::query()->whereHas('roles', fn($query) => $query->where('name', 'Quản trị'))->firstOrFail();
        // The theme is now managed in Developer Settings; the old General settings endpoint answers 403 and changes nothing.
        $this->actingAs($admin)->post(route('cms.setting.themes.update'), ['cms_public_theme' => 'default'])->assertForbidden();
        $this->assertSame('nam-binh-duong', Option::query()->where('key', 'cms_public_theme')->value('value'));
        $this->get('/')->assertOk()->assertSee('data-public-theme="nam-binh-duong"', false);
        Option::query()->where('key', 'cms_public_theme')->update(['value' => 'default']);
        $this->get('/')->assertOk()->assertDontSee('data-public-theme="nam-binh-duong"', false);
        Option::query()->where('key', 'cms_public_theme')->update(['value' => 'nam-binh-duong']);
        $this->get('/')->assertOk()->assertSee('data-public-theme="nam-binh-duong"', false);
        $this->assertSame($unit, Option::query()->where('key', 'cms_unit_name')->value('value'));
        $this->assertSame($posts, DB::table('posts')->count());
    }

    public function test_public_form_validation_remains_active(): void
    {
        $this->postJson(route('cms.lien-he.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone', 'address', 'subject', 'message']);
        $this->postJson(route('cms.frontend.hoidap.store'), [])->assertUnprocessable();
    }

    public function test_school_sections_use_public_cms_posts_and_refresh_the_fragment_stamp(): void
    {
        $source = \App\Models\Post::query()->where('post_type', 'post')->where('status', 'published')->whereNull('deleted_at')
            ->whereHas('taxonomies', fn($query) => $query->where('taxonomy', 'category')->where('loai_chuyen_muc', '!=', 'noi_bo'))
            ->with('taxonomies.term')->firstOrFail();
        $category = $source->taxonomies->first(fn($item) => $item->taxonomy === 'category' && $item->loai_chuyen_muc !== 'noi_bo');
        $this->assertNotNull($category);
        config(['cmsbackend.screens.themes.nam-binh-duong.home_sections' => ['programs' => $category->term->slug]]);
        $created = [];
        foreach (['published', 'draft', 'future', 'expired'] as $kind) {
            $post = $source->replicate()->unsetRelations();
            $post->deleted_at = null;
            $post->title = 'Theme visibility ' . $kind;
            $post->slug = 'theme-workflow-visibility-' . $kind . '-' . bin2hex(random_bytes(4));
            $post->status = $kind === 'draft' ? 'draft' : 'published';
            $post->published_at = $kind === 'future' ? now()->addDay() : now()->subMinute();
            $post->publication_date_expires = $kind === 'expired' ? now()->subSeconds(30) : null;
            $post->save();
            $post->taxonomies()->sync([$category->term_taxonomy_id]);
            // Public lists only show posts that have a translation for the public locale.
            DB::table('post_translations')->insert([
                'post_id' => $post->id, 'locale' => 'vi', 'title' => $post->title, 'slug' => $post->slug,
                'excerpt' => 'Theme visibility fixture', 'content' => '<p>Theme visibility fixture</p>',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $created[$kind] = $post;
        }
        $blocks = app(\Modules\CmsFrontend\Services\HomeBlocks::class);
        $sections = $blocks->data()['homeSections'];
        $ids = $sections['programs']['items']->modelKeys();
        $this->assertContains($created['published']->id, $ids);
        foreach (['draft', 'future', 'expired'] as $kind) {
            $this->assertNotContains($created[$kind]->id, $ids);
        }
        $response = $this->get('/')->assertOk()->assertSee('Theme visibility published');
        foreach (['draft', 'future', 'expired'] as $kind) {
            $response->assertDontSee('Theme visibility ' . $kind);
        }
        $presentation = app(\Modules\CmsFrontend\Services\HomePresentation::class);
        $before = $presentation->cacheKey($presentation->prepare(['homeSections' => $sections]));
        $created['published']->updated_at = now()->addMinute();
        $created['published']->save();
        $after = $presentation->cacheKey($presentation->prepare(['homeSections' => $blocks->data()['homeSections']]));
        $this->assertNotSame($before, $after);
        $category->loai_chuyen_muc = 'noi_bo';
        $category->save();
        $hidden = $blocks->data()['homeSections']['programs'];
        $this->assertCount(0, $hidden['items']);
        $this->assertNull($hidden['url']);
    }

    public function test_missing_school_data_does_not_display_mock_statistics_or_fake_links(): void
    {
        config(['cmsbackend.screens.themes.nam-binh-duong.home_sections' => ['programs' => 'theme-not-existing-category']]);
        $this->get('/')->assertOk()->assertSee('Thông tin đang được cập nhật.')
            ->assertDontSee('6.000+')->assertDontSee('Hồ sơ minh họa')
            ->assertSee('aria-disabled="true"', false)->assertDontSee('?page=program', false);
    }
    public function test_home_shortcuts_follow_admin_menu_and_invalidate_the_fragment_cache(): void
    {
        $menu = \App\Models\Menu::withoutEvents(fn() => \App\Models\Menu::create(['name' => 'SBUT shortcut fixture']));
        Option::query()->updateOrCreate(['key' => 'main_menu'], ['value' => (string) $menu->id, 'autoload' => 1]);
        $defaults = ['menu_id' => $menu->id, 'parent_id' => 0, 'treepath' => '', 'type' => 'url', 'ref_id' => 0, 'target' => '_self'];
        $items = [];
        foreach (range(1, 5) as $index) {
            $items[] = \App\Models\MenuItem::create($defaults + ['title' => 'Shortcut fixture ' . $index, 'url' => '/hoi-dap?shortcut=' . $index, 'ordinal' => $index]);
        }
        $parent = \App\Models\MenuItem::create($defaults + ['title' => 'Shortcut parent', 'url' => '#', 'ordinal' => 6]);
        \App\Models\MenuItem::create(array_replace($defaults, ['parent_id' => $parent->id, 'title' => 'Shortcut child', 'url' => 'https://example.org/sbut', 'target' => '_blank', 'ordinal' => 1]));
        \App\Models\MenuItem::create($defaults + ['title' => 'Unsafe shortcut', 'url' => 'javascript:alert(1)', 'ordinal' => 7]);
        foreach (range(2, 5) as $index) {
            \App\Models\MenuItem::create(array_replace($defaults, ['parent_id' => $parent->id, 'title' => 'Shortcut child ' . $index, 'url' => '/hoi-dap?child=' . $index, 'ordinal' => $index]));
        }
        $presentation = app(\Modules\CmsFrontend\Services\HomePresentation::class);
        $before = $presentation->prepare(app(HomeController::class)->index()->getData());
        $response = $this->get('/')->assertOk();
        $this->assertSame(1, preg_match('~<nav class="quick-links" aria-label="Truy cập các chức năng">(.*?)</nav>~s', $response->getContent(), $matches));
        $html = $matches[1];
        $this->assertStringContainsString('Shortcut fixture 5', $html);
        $this->assertStringNotContainsString('<small', $html);
        $this->assertStringContainsString('sbut-shortcut-dropdown', $html);
        $this->assertStringContainsString('aria-label="Đóng menu con"', $html);
        $this->assertStringContainsString('sbut-shortcut-head', $html);
        $this->assertStringContainsString('sbut-shortcut-list--columns', $html);
        $this->assertStringContainsString('--sbut-menu-rows:3', $html);
        $this->assertStringContainsString('Shortcut child', $html);
        $this->assertStringContainsString('href="https://example.org/sbut"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringNotContainsString('Unsafe shortcut', $html);
        $this->assertLessThan(strpos($html, 'Shortcut fixture 5'), strpos($html, 'Shortcut fixture 1'));
        $parent->update(['url' => '/tin-tuc']);
        $this->get('/')->assertOk()->assertSee('Xem tất cả');
        $items[0]->update(['title' => 'Shortcut renamed', 'url' => '/lien-he']);
        $after = $presentation->prepare(app(HomeController::class)->index()->getData());
        $this->assertNotSame($presentation->cacheKey($before), $presentation->cacheKey($after));
        $this->get('/')->assertOk()->assertSee('Shortcut renamed')->assertDontSee('Shortcut fixture 1');
        Option::query()->where('key', 'main_menu')->update(['value' => '0']);
        $this->get('/')->assertOk()->assertDontSee('aria-label="Truy cập các chức năng"', false);
    }

    public function test_section_navigation_sits_below_admin_menu_instead_of_the_header(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, preg_match('~<header class="header sbut-header">(.*?)</header>~s', $html, $header));
        $this->assertStringNotContainsString('id="mainNav"', $header[1]);
        $this->assertStringNotContainsString('id="menuBtn"', $header[1]);
        $this->assertStringContainsString('id="searchBtn"', $header[1]);
        $this->assertSame(1, preg_match('~<nav id="mainNav" class="section-shortcuts".*?</nav>~s', $html, $navigation));
        $this->assertSame(6, substr_count($navigation[0], 'data-sbut-section='));
        foreach (['trang-chu', 'gioi-thieu', 'dao-tao', 'nghien-cuu', 'tin-tuc', 'tuyen-sinh'] as $section) {
            $this->assertStringContainsString('href="' . route('cms.trang-chu') . '#' . $section . '"', $navigation[0]);
        }
        $this->assertLessThan(strpos($html, 'id="mainNav"'), strpos($html, 'sbut-shortcut-inner'));
        $this->assertLessThan(strpos($html, 'class="admission-ticker"'), strpos($html, 'id="mainNav"'));
    }

    public function test_empty_route_prefix_can_serve_a_subdirectory_mount_without_a_redirect_loop(): void
    {
        config(['cmsbackend.route_prefix' => '']);
        $provider = new \Modules\CmsFrontend\Providers\RouteServiceProvider(app());
        $method = new \ReflectionMethod($provider, 'mapWebRoutes');
        $method->invoke($provider);
        app('router')->getRoutes()->refreshNameLookups();
        $this->get('/')->assertOk()->assertSee('data-public-theme="nam-binh-duong"', false);
        $this->get('/login')->assertOk();
    }
}
