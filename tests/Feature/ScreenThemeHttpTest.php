<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Post;
use App\Models\Taxonomy;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Models\EditorialMeta;
use Modules\CmsBackend\Models\VanBan;
use Modules\CmsFrontend\Http\Controllers\HomeController;
use Tests\TestCase;

final class ScreenThemeHttpTest extends TestCase
{
    private array $fixtureRoots = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('cms_workspace', DB::connection()->getDatabaseName());
        DB::beginTransaction();
        $this->admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Quản trị'))->firstOrFail();
        config(['cmsbackend.screens.themes.cms-test-http' => ['label' => 'HTTP probe', 'surfaces' => ['admin', 'public']]]);
        foreach (['CmsBackend', 'CmsFrontend'] as $module) {
            $directory = module_path($module, 'resources/views/screens/cms-test-http');
            $this->assertDirectoryDoesNotExist($directory);
            $relative = $module === 'CmsBackend' ? 'components/shell' : 'components/home';
            (new Filesystem)->ensureDirectoryExists($directory.'/'.$relative);
            $this->fixtureRoots[] = [$module, $directory];
            if ($module === 'CmsBackend') {
                file_put_contents($directory.'/components/shell/header.blade.php', '<!-- ADMIN_THEME_PROBE -->'.file_get_contents(module_path($module, 'resources/views/screens/default/components/shell/header.blade.php')));
            } else {
                file_put_contents($directory.'/components/home/home-content.blade.php', '<main id="main-content">PUBLIC_THEME_PROBE</main>');
            }
        }
    }

    protected function tearDown(): void
    {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($this->fixtureRoots as [$module, $directory]) {
            $real = realpath($directory);
            $base = realpath(module_path($module, 'resources/views/screens'));
            if ($real !== false && $base !== false && str_replace('\\', '/', $real) === str_replace('\\', '/', $base).'/cms-test-http') {
                (new Filesystem)->deleteDirectory($real);
            }
        }
        parent::tearDown();
    }

    private function store(string $key, string $value): void
    {
        Option::query()->updateOrCreate(['key' => $key], ['value' => $value, 'autoload' => 1]);
    }

    public function test_only_general_settings_offer_a_public_theme_selector(): void
    {
        $this->actingAs($this->admin)->get(route('setting.general'))
            ->assertOk()->assertSee('Giao diện trang công khai')->assertDontSee('name="cms_admin_theme"', false)->assertSee('name="cms_public_theme"', false);
        $this->get(route('cms.setting.trang-chu'))->assertOk()->assertDontSee('cms_home_theme')->assertDontSee('Giao diện trang chủ')->assertSee('Thông tin hiển thị trang chủ');
    }

    public function test_admin_is_fixed_while_public_theme_supports_page_fallback(): void
    {
        $this->store('cms_admin_theme', 'cms-test-http');
        $this->store('cms_public_theme', 'default');
        $this->actingAs($this->admin)->get(route('cms.dashboard.cdn.bai-viet.index'))->assertOk()->assertDontSee('ADMIN_THEME_PROBE', false);
        $this->get('/cms')->assertOk()->assertDontSee('PUBLIC_THEME_PROBE');
        $this->store('cms_public_theme', 'cms-test-http');
        $this->get('/cms')->assertOk()->assertSee('PUBLIC_THEME_PROBE');
        $this->get('/cms/tin-tuc')->assertOk()->assertDontSee('PUBLIC_THEME_PROBE');
        $this->get(route('cms.dashboard.cdn.bai-viet.create'))->assertOk()->assertDontSee('ADMIN_THEME_PROBE', false);
        $this->store('cms_admin_theme', 'default');
        $this->get(route('cms.dashboard.cdn.bai-viet.index'))->assertOk()->assertDontSee('ADMIN_THEME_PROBE');
        $this->get('/cms')->assertOk()->assertSee('PUBLIC_THEME_PROBE');
    }

    public function test_theme_settings_validate_and_preserve_the_existing_home_variant(): void
    {
        $variant = Option::query()->where('key', 'cms_home_theme')->value('value');
        $unit = Option::query()->where('key', 'cms_unit_name')->value('value');
        $this->actingAs($this->admin)->post(route('cms.setting.themes.update'), ['cms_public_theme' => 'cms-test-http'])->assertRedirect(route('setting.general'))->assertSessionHasNoErrors();
        $this->assertSame('cms-test-http', Option::query()->where('key', 'cms_public_theme')->value('value'));
        $this->assertSame($variant, Option::query()->where('key', 'cms_home_theme')->value('value'));
        $this->assertSame($unit, Option::query()->where('key', 'cms_unit_name')->value('value'));
        $this->postJson(route('cms.setting.themes.update'), ['cms_admin_theme' => '../outside', 'cms_public_theme' => 'default'])->assertUnprocessable()->assertJsonValidationErrors('cms_admin_theme');
        $this->postJson(route('cms.setting.themes.update'), ['cms_public_theme' => '../outside'])->assertUnprocessable()->assertJsonValidationErrors('cms_public_theme');
        $this->assertSame('cms-test-http', Option::query()->where('key', 'cms_public_theme')->value('value'));
    }

    public function test_guest_and_user_without_settings_permission_cannot_change_themes(): void
    {
        $this->post(route('cms.setting.themes.update'), ['cms_public_theme' => 'default'])->assertRedirect(route('cms.login'));
        $user = User::factory()->make(['id' => PHP_INT_MAX]);
        $this->actingAs($user)->postJson(route('cms.setting.themes.update'), ['cms_public_theme' => 'default'])->assertForbidden();
    }

    public function test_news_workflow_stays_the_same_with_legacy_admin_theme_option(): void
    {
        $this->actingAs($this->admin);
        $slug = 'theme-workflow-'.bin2hex(random_bytes(5));
        $category = Taxonomy::query()->where('taxonomy', 'category')->whereHas('term')->value('term_taxonomy_id');
        $payload = ['action' => 'save_draft', 'title' => 'Bài kiểm thử theme', 'slug' => $slug, 'content' => '<p>Nội dung kiểm thử quy trình.</p>', 'excerpt' => 'Tóm tắt kiểm thử.', 'author_names' => 'Người kiểm thử', 'category_ids' => [$category], 'tag_ids' => []];
        $this->post(route('cms.dashboard.cdn.bai-viet.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $post = Post::query()->where('slug', $slug)->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->store('cms_admin_theme', 'cms-test-http');
        $this->get(route('cms.dashboard.cdn.bai-viet.edit', $post->id))->assertOk()->assertDontSee('ADMIN_THEME_PROBE', false)->assertSee('Bài kiểm thử theme');
        foreach ([['submit_for_approval', 'pending_approval'], ['approved', 'approved'], ['publish', 'published'], ['stop_publishing', 'stop_publishing']] as [$action, $expected]) {
            $data = $action === 'submit_for_approval' ? $payload : [];
            $data = array_merge($data, ['action' => $action, 'revision' => EditorialMeta::query()->findOrFail($post->id)->revision]);
            if ($action === 'publish') {
                $data['publish_type'] = 'publish_now';
            }
            $this->post(route('cms.dashboard.cdn.bai-viet.update-post', $post->id), $data)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($expected, $post->fresh()->status);
        }
        $this->delete(route('cms.dashboard.cdn.bai-viet.destroy', $post->id), ['revision' => EditorialMeta::query()->findOrFail($post->id)->revision])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($post->fresh()->deleted_at);
        $this->get(route('cms.dashboard.cdn.bai-viet.index', ['trash' => 1]))->assertOk()->assertSee('Bài kiểm thử theme')->assertSee('Xóa vĩnh viễn');
        $this->post(route('cms.dashboard.cdn.bai-viet.purge', $post->id), ['revision' => EditorialMeta::query()->findOrFail($post->id)->revision, 'confirmation' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse(Post::query()->whereKey($post->id)->exists());
    }

    public function test_public_lists_details_and_document_viewer_render_after_migration(): void
    {
        foreach (['/cms/tin-tuc', '/cms/su-kien', '/cms/van-ban', '/cms/thu-vien-hinh-anh', '/cms/thu-vien-video', '/cms/lien-he', '/cms/hoi-dap', '/cms/dich-vu'] as $url) {
            $this->get($url)->assertOk();
        }
        $data = app(HomeController::class)->index()->getData();
        $post = $data['firstPost']->first();
        $this->assertNotNull($post);
        $this->get(route('cms.tin-tuc.show', $post->slug))->assertOk()->assertSee($post->title);
        $document = VanBan::query()->first();
        if ($document !== null) {
            $this->get(route('cms.frontend.vanban.show', $document->getKey()))->assertOk();
        }
    }
}
