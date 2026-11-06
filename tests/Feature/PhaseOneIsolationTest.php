<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Models\GuongTieuBieu;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

final class PhaseOneIsolationTest extends TestCase
{
    public function test_only_neutral_modules_are_enabled(): void
    {
        $names = array_map(fn ($module): string => $module->getName(), array_values(Module::allEnabled()));
        sort($names);
        $this->assertSame(['CmsBackend', 'CmsFrontend'], $names);
        $this->assertSame('cms_workspace', config('database.connections.mysql.database'));
        $this->assertSame('cms_workspace_session', config('session.cookie'));
        $this->assertSame('array', config('session.driver'));
        $this->assertFalse((bool) config('cmsbackend.register_migrations'));
    }

    public function test_unit_defaults_are_stored_in_the_new_database(): void
    {
        $this->assertSame('Nam Bình Dương', Option::query()->where('key', 'cms_unit_name')->value('value'));
        $this->assertSame('nam-binh-duong', Option::query()->where('key', 'cms_unit_key')->value('value'));
        $this->assertSame('education', Option::query()->where('key', 'cms_unit_type')->value('value'));
        $this->assertSame('Nam Bình Dương', config('cmsfrontend.brand.name'));
    }

    public function test_all_project_routes_resolve_to_existing_controllers(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getName() ?? '', 'cms.')) {
                continue;
            }
            $action = $route->getActionName();
            if ($action === 'Closure') {
                continue;
            }
            [$controller, $method] = array_pad(explode('@', $action), 2, '__invoke');
            $this->assertTrue(class_exists($controller), $action);
            $checked++;
        }
        $this->assertGreaterThan(50, $checked);
        $this->assertSame('/cms', rtrim(route('cms.trang-chu', [], false), '/'));
        $this->assertFalse(Route::has('knct.trang-chu'));
        $this->assertSame('cms.trang-chu', \Modules\CmsBackend\Support\ModuleUrl::name('cms.trang-chu'));
        $this->assertSame('cms.trang-chu', \Modules\CmsBackend\Support\ModuleUrl::name('trang-chu'));
    }

    public function test_media_uses_local_files_and_neutral_model_types(): void
    {
        $this->assertSame(public_path('media/cms'), config('filesystems.disks.cms_media.root'));
        $this->assertSame(GuongTieuBieu::class, (new GuongTieuBieu)->getMorphClass());
        foreach (DB::table('media')->get() as $media) {
            $this->assertTrue(class_exists($media->model_type), $media->model_type);
            $this->assertNotSame('knct_media', $media->disk);
            $this->assertSame('local', config('filesystems.disks.'.$media->disk.'.driver'));
            $root = config('filesystems.disks.'.$media->disk.'.root');
            $this->assertStringStartsWith(base_path(), $root);
            $this->assertFileExists($root.DIRECTORY_SEPARATOR.$media->id.DIRECTORY_SEPARATOR.$media->file_name);
        }
    }

    public function test_login_and_homepage_render_with_the_new_build(): void
    {
        $this->get('/cms/login')->assertOk();
        $this->get('/cms')->assertOk();
    }

    public function test_guest_cannot_access_administration(): void
    {
        $this->get(route('cms.dashboard', [], false))->assertRedirect();
    }

    public function test_administrator_can_open_existing_management_pages(): void
    {
        $user = \App\Models\User::query()->get()->sortByDesc(fn ($user): int => $user->getAllPermissions()->count())->first();
        $this->assertNotNull($user);
        $this->actingAs($user);
        $this->get(route('cms.dashboard.cdn.bai-viet.index', ['trash' => 1], false))->assertOk()->assertSee('Danh sách hiện có');
        foreach (['cms.dashboard', 'cms.dashboard.cdn.bai-viet.index', 'cms.dashboard.cdn.bai-viet.create', 'menus.index', 'roles.index', 'setting.general'] as $name) {
            $this->get(route($name, [], false))->assertOk();
        }
    }

    public function test_permanent_deletion_form_contains_confirmation_csrf_and_correct_route(): void
    {
        $post = \App\Models\Post::query()->where('post_type', 'post')->firstOrFail();
        $html = view('cmsbackend::editorial.purge-form', ['post' => $post, 'resource' => 'cms.dashboard.cdn.bai-viet', 'purgeRevision' => 0])->render();
        $this->assertStringContainsString('name="confirmation"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="revision"', $html);
        $this->assertStringContainsString(route('cms.dashboard.cdn.bai-viet.purge', $post->id), $html);
    }
}
