<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Tests\TestCase;

/**
 * Shared view layer: the campus themes keep what they have in common in screens/_campus instead of two identical copies. A view is
 * searched in the theme's own folder, then in the layer it extends, then in `default`. SQLite :memory: only (no real database).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/ScreenThemeLayerTest.php
 */
final class ScreenThemeLayerTest extends TestCase
{
    private const SCREENS = 'Modules/CmsFrontend/resources/views/screens';

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }
    }

    private function resolve(string $project, string $screen): string
    {
        config(['cmsbackend.projects.key' => $project]);
        request()->attributes->remove('_cms_screen_paths');

        return str_replace('\\', '/', (string) app(ScreenTheme::class)->path('public', $screen));
    }

    public function test_the_lookup_order_is_the_theme_then_its_layer_then_default(): void
    {
        $screens = app(ScreenTheme::class);

        $this->assertSame(['nam-binh-duong', '_campus', 'default'], $screens->lookup('nam-binh-duong'));
        $this->assertSame(['ban-de-an', '_campus', 'default'], $screens->lookup('ban-de-an'));
        $this->assertSame(['default'], $screens->lookup('default'));
    }

    public function test_a_declared_layer_must_be_a_safe_folder_name(): void
    {
        foreach (['../default', '_', '_Campus', 'campus', '_a/b', ''] as $bad) {
            config(['cmsbackend.screens.themes.ban-de-an.extends' => $bad]);

            $this->assertSame(['ban-de-an', 'default'], app(ScreenTheme::class)->lookup('ban-de-an'), var_export($bad, true));
        }
    }

    public function test_a_shared_view_comes_from_the_layer_for_both_campus_themes(): void
    {
        foreach (['nam-binh-duong', 'ban-de-an'] as $project) {
            $this->assertStringEndsWith(self::SCREENS.'/_campus/components/home/news.blade.php', $this->resolve($project, 'components.home.news'), $project);
        }
    }

    public function test_a_view_of_the_theme_wins_over_the_layer_and_default(): void
    {
        $this->assertStringEndsWith(self::SCREENS.'/nam-binh-duong/layout.blade.php', $this->resolve('nam-binh-duong', 'layout'));
        $this->assertStringEndsWith(self::SCREENS.'/ban-de-an/layout.blade.php', $this->resolve('ban-de-an', 'layout'));
    }

    public function test_a_view_nobody_overrides_falls_back_to_default(): void
    {
        // These were identical to the default ones and were removed from both campus themes.
        foreach (['nam-binh-duong', 'ban-de-an'] as $project) {
            $this->assertStringEndsWith(self::SCREENS.'/default/pages/frontend/hoi-dap/show.blade.php', $this->resolve($project, 'pages.frontend.hoi-dap.show'), $project);
        }
    }

    public function test_the_default_theme_never_reads_the_campus_layer(): void
    {
        $path = $this->resolve('khuyen-nong', 'components.home.news');

        $this->assertStringNotContainsString('_campus', $path);
        $this->assertStringEndsWith(self::SCREENS.'/default/components/home/news.blade.php', $path);
    }

    public function test_a_layer_is_not_a_theme(): void
    {
        $choices = app(ScreenTheme::class)->choices('public');

        $this->assertArrayNotHasKey('_campus', $choices);
        $this->assertArrayHasKey('nam-binh-duong', $choices);
    }

    public function test_the_homepage_renderers_of_the_layer_belong_to_the_themes_that_extend_it(): void
    {
        $registry = app(HomepageBlockRegistry::class);

        $this->assertTrue($registry->rendererExists('nam-binh-duong', 'news'));
        $this->assertTrue($registry->rendererExists('ban-de-an', 'news'));
        $this->assertStringContainsString('/_campus/', str_replace('\\', '/', (string) $registry->rendererPath('ban-de-an', 'news')));
        // A theme's own renderer is still its own, and a theme never borrows another one's renderer through the layer.
        $this->assertStringContainsString('/ban-de-an/', str_replace('\\', '/', (string) $registry->rendererPath('ban-de-an', 'blocks.contact')));
        $this->assertStringNotContainsString('_campus', (string) $registry->rendererPath('default', 'news'));
    }

    public function test_every_declared_layer_exists_and_holds_no_copy_of_a_theme_file(): void
    {
        $root = base_path(self::SCREENS);
        foreach (config('cmsbackend.screens.themes') as $key => $theme) {
            $layer = $theme['extends'] ?? null;
            if ($layer === null) {
                continue;
            }
            $this->assertDirectoryExists($root.'/'.$layer, $key.' extends a layer that does not exist');
            // A file may be in the theme as well as in the layer only when the theme has really diverged (the theme's own file wins).
            // An IDENTICAL twin is a leftover of the move.
            foreach (glob($root.'/'.$layer.'/{*,*/*,*/*/*,*/*/*/*,*/*/*/*/*}.blade.php', GLOB_BRACE) as $file) {
                $relative = substr(str_replace('\\', '/', $file), strlen(str_replace('\\', '/', $root.'/'.$layer)) + 1);
                $twin = $root.'/'.$key.'/'.$relative;
                if (is_file($twin)) {
                    $this->assertNotSame(file_get_contents($file), file_get_contents($twin), $key.' still has an identical copy of '.$relative);
                }
            }
        }
    }
}
