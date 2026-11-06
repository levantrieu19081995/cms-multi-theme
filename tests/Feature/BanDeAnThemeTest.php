<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Services\ScreenTheme;
use Tests\TestCase;

final class BanDeAnThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('cms_workspace', DB::connection()->getDatabaseName());
        DB::beginTransaction();
        Option::query()->updateOrCreate(['key' => 'cms_public_theme'], ['value' => 'ban-de-an', 'autoload' => 1]);
    }

    protected function tearDown(): void
    {
        while (DB::connection()->transactionLevel() > 0) DB::rollBack();
        parent::tearDown();
    }

    public function test_theme_is_registered_for_public_only(): void
    {
        $screens = app(ScreenTheme::class);
        $this->assertSame('Ban Đề án', $screens->choices('public')['ban-de-an']);
        $this->assertArrayNotHasKey('ban-de-an', $screens->choices('admin'));
        $this->assertStringContainsString('ban-de-an', $screens->path('public', 'home'));
        $this->assertSame(1, config('cmsbackend.screens.themes.ban-de-an.version'));
    }

    public function test_home_uses_proposal_layout_and_real_feature_routes(): void
    {
        $response = $this->get('/cms')->assertOk()
            ->assertSee('data-public-theme="ban-de-an"', false)
            ->assertSee('Kết nối tầm nhìn.')
            ->assertSee('Đề án – Dự án')
            ->assertSee('Tin tức & Hoạt động', false)
            ->assertSee('Văn bản – Tài liệu')
            ->assertSee('Kết nối cùng')
            ->assertDontSee('BẢN THIẾT KẾ MẪU')
            ->assertDontSee('Nội dung minh họa');
        foreach (['tin-tuc', 'thong-bao', 'de-an', 'gioi-thieu', 'van-ban', 'lien-he'] as $section) {
            $response->assertSee('id="'.$section.'"', false);
        }
        foreach (['cms.tin-tuc.index', 'cms.frontend.vanban.index', 'cms.lien-he'] as $route) {
            $response->assertSee('href="'.route($route), false);
        }
    }

    public function test_public_feature_pages_keep_the_selected_theme(): void
    {
        foreach (['/cms/tin-tuc', '/cms/su-kien', '/cms/van-ban', '/cms/thu-vien-hinh-anh', '/cms/thu-vien-video', '/cms/lien-he', '/cms/hoi-dap', '/cms/dich-vu'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-public-theme="ban-de-an"', false)->assertSee('proposal-footer', false);
        }
    }
}
