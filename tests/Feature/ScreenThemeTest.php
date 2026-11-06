<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsFrontend\Services\HomePresentation;
use Modules\CmsFrontend\Services\HomeTheme;
use Modules\CmsFrontend\Support\Brand;
use Tests\TestCase;

final class ScreenThemeTest extends TestCase
{
    private array $fixtureRoots = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cmsbackend.editorial_database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('options', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('autoload')->default(true);
            $table->timestamps();
        });
        config(['cmsbackend.screens.themes' => [
            'default' => ['label' => 'Default', 'surfaces' => ['admin', 'public']],
            'cms-test-a' => ['label' => 'A', 'surfaces' => ['admin', 'public']],
            'cms-test-b' => ['label' => 'B', 'surfaces' => ['public']],
        ]]);
        foreach (['cms-test-a' => 'A', 'cms-test-b' => 'B'] as $theme => $marker) {
            $directory = module_path('CmsFrontend', 'resources/views/screens/'.$theme);
            $this->assertDirectoryDoesNotExist($directory);
            (new Filesystem)->ensureDirectoryExists($directory.'/components/home');
            $this->fixtureRoots[] = $directory;
            file_put_contents($directory.'/components/probe.blade.php', '<p>theme-'.$marker.'</p>');
            file_put_contents($directory.'/components/home/home-content.blade.php', '<main>fragment-'.$marker.' {{ count($homeNews) }}</main>');
        }
        $this->newRequest();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureRoots as $directory) {
            $real = realpath($directory);
            $base = realpath(module_path('CmsFrontend', 'resources/views/screens'));
            if ($real !== false && $base !== false && str_starts_with(str_replace('\\', '/', $real), str_replace('\\', '/', $base).'/cms-test-')) {
                (new Filesystem)->deleteDirectory($real);
            }
        }
        parent::tearDown();
    }

    private function newRequest(): void
    {
        $this->app->instance('request', Request::create('http://localhost/cms?theme=cms-test-b'));
    }

    private function store(string $key, string $value): void
    {
        Option::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function test_fixed_admin_and_public_selection_use_one_query_per_request(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $screens = app(ScreenTheme::class);
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('default', $screens->key('admin'));
            $this->assertSame('default', $screens->key('public'));
        }
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_admin_ignores_legacy_selection_and_public_ignores_query_parameters(): void
    {
        $this->store('cms_admin_theme', 'cms-test-a');
        $this->store('cms_public_theme', 'cms-test-b');
        $screens = app(ScreenTheme::class);
        $this->assertSame('default', $screens->key('admin'));
        $this->assertSame('cms-test-b', $screens->key('public'));
        $this->assertSame(['default' => 'Mặc định (giao diện hiện tại)'], $screens->choices('admin'));
    }

    public function test_unknown_incompatible_and_unsafe_theme_keys_fall_back_to_default(): void
    {
        $screens = app(ScreenTheme::class);
        foreach (['not-installed', '../outside', 'cms-test-b'] as $key) {
            $this->store('cms_admin_theme', $key);
            $this->newRequest();
            $this->assertSame('default', $screens->key('admin'));
        }
        $this->assertSame('default', $screens->key('public'));
    }

    public function test_override_and_missing_component_fallback_use_the_same_logical_screens(): void
    {
        $this->store('cms_public_theme', 'cms-test-a');
        $screens = app(ScreenTheme::class);
        $this->assertStringContainsString('cms-test-a', $screens->path('public', 'components.probe'));
        $this->assertSame(realpath(module_path('CmsFrontend', 'resources/views/screens/default/layout.blade.php')), $screens->path('public', 'layout'));
    }

    public function test_all_legacy_view_names_resolve_to_migrated_default_files(): void
    {
        $screens = app(ScreenTheme::class);
        $map = config('cmsbackend.screens.view_map');
        $this->assertCount(159, $map);
        foreach ($map as $old => $logical) {
            $this->assertFileExists($screens->pathForView($old));
            $this->assertSame($screens->pathForView($old), view()->getFinder()->find($old));
        }
    }

    public function test_view_finder_does_not_leak_selection_between_requests(): void
    {
        $screens = app(ScreenTheme::class);
        $this->store('cms_public_theme', 'cms-test-a');
        $first = view()->getFinder()->find($screens->view('public', 'components.probe'));
        $this->store('cms_public_theme', 'cms-test-b');
        $this->assertSame('cms-test-a', $screens->key('public'));
        $this->newRequest();
        $second = view()->getFinder()->find($screens->view('public', 'components.probe'));
        $this->assertNotSame($first, $second);
        $this->assertStringContainsString('cms-test-b', $second);
    }

    public function test_saving_selections_preserves_home_variant_and_unit_data(): void
    {
        $this->store('cms_home_theme', 'hien-dai');
        $this->store('cms_unit_name', 'Nam Bình Dương');
        app(ScreenTheme::class)->save(['cms_public_theme' => 'cms-test-a']);
        $this->assertSame('cms-test-a', app(ScreenTheme::class)->key('public'));
        $this->assertSame('hien-dai', Option::query()->where('key', 'cms_home_theme')->value('value'));
        $this->assertSame('Nam Bình Dương', Option::query()->where('key', 'cms_unit_name')->value('value'));
        $this->assertStringContainsString('variants', app(HomeTheme::class)->view('gallery'));
        $this->assertSame('bao-chi', app(HomeTheme::class)->key());
        HomeSettings::save(['cms_home_theme' => 'hien-dai']);
        $this->assertNotContains('cms_home_theme', HomeSettings::KEYS);
        $this->assertSame('hien-dai', Option::query()->where('key', 'cms_home_theme')->value('value'));
        $this->assertSame('bao-chi', app(HomeTheme::class)->key());
    }

    public function test_invalid_public_theme_save_preserves_options(): void
    {
        $this->store('cms_admin_theme', 'default');
        $this->store('cms_public_theme', 'default');
        try {
            app(ScreenTheme::class)->save(['cms_admin_theme' => 'cms-test-a', 'cms_public_theme' => '../outside']);
            $this->fail('Invalid theme must be rejected');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('not registered', $error->getMessage());
        }
        $this->assertSame('default', Option::query()->where('key', 'cms_admin_theme')->value('value'));
        $this->assertSame('default', Option::query()->where('key', 'cms_public_theme')->value('value'));
    }

    public function test_fragment_cache_is_separated_by_theme(): void
    {
        config(['app.debug' => false, 'cache.default' => 'array']);
        $this->store('cms_public_theme', 'cms-test-a');
        $presentation = app(HomePresentation::class);
        $data = $presentation->prepare([]);
        $firstKey = $presentation->cacheKey($data);
        $this->assertStringContainsString('fragment-A', $presentation->render($data));
        $this->store('cms_public_theme', 'cms-test-b');
        $this->newRequest();
        $secondKey = $presentation->cacheKey($data);
        $this->assertNotSame($firstKey, $secondKey);
        $this->assertStringContainsString('fragment-B', $presentation->render($data));
        $this->assertStringContainsString('fragment-A', Cache::get($firstKey));
    }

    public function test_brand_options_are_scoped_to_each_request(): void
    {
        $this->store('web_title', 'First unit');
        $this->assertSame('First unit', Brand::name());
        $this->store('web_title', 'Second unit');
        $this->newRequest();
        $this->assertSame('Second unit', Brand::name());
    }

    public function test_screen_path_traversal_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ScreenTheme::class)->path('public', '../outside');
    }
}
