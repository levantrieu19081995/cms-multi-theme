<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\CmsBackend\Services\LoginDesigns;
use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Support\ProjectContext;
use Tests\TestCase;

/**
 * `bandean-v2`: an independent copy of `ban-de-an` for the redesign, now a project of its own (`bandean-v2`) with its own views / CSS / JS / login design. SQLite :memory:
 * only (no real database).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/BandeanV2ThemeTest.php
 */
final class BandeanV2ThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }
    }

    private function useTheme(string $theme): void
    {
        $project = ProjectContext::projectOfTheme($theme) ?? 'ban-de-an';
        config(['cmsbackend.projects.key' => $project]);
        request()->attributes->remove('_cms_screen_paths');
    }

    public function test_the_theme_is_registered_and_can_be_chosen(): void
    {
        $this->assertArrayHasKey('bandean-v2', app(ScreenTheme::class)->choices('public'));
        $this->assertSame(['bandean-v2', '_campus', 'default'], app(ScreenTheme::class)->lookup('bandean-v2'));
    }

    public function test_it_is_a_site_of_its_own_that_works_on_the_data_of_ban_de_an(): void
    {
        // Own project key (theme, guides, permission manifest, login design) ...
        $this->assertSame('bandean-v2', ProjectContext::projectOfTheme('bandean-v2'));
        // ... but posts, publication history and social accounts are those of Ban Đề án.
        $this->assertSame('ban-de-an', ProjectContext::dataKey('bandean-v2'));
        $this->assertSame('ban-de-an', ProjectContext::dataKey('ban-de-an'));
        $this->assertSame('nam-binh-duong', ProjectContext::dataKey('nam-binh-duong'));

        config(['cmsbackend.projects.key' => 'bandean-v2']);
        $this->assertSame('bandean-v2', ProjectContext::key());
        $this->assertSame('ban-de-an', ProjectContext::dataKey());
        $this->assertSame('ban-de-an', app(SocialPublicationService::class)->projectKey());
    }

    public function test_a_fixed_project_may_switch_only_between_the_themes_over_its_own_data(): void
    {
        config(['cmsbackend.projects.key' => 'ban-de-an']);
        $this->assertSame(['ban-de-an', 'bandean-v2'], ProjectContext::fixedThemeChoices());
        config(['cmsbackend.projects.key' => 'bandean-v2']);
        $this->assertSame(['ban-de-an', 'bandean-v2'], ProjectContext::fixedThemeChoices());
        // Nam Bình Dương / Khuyến nông have nothing to switch to; an open (local) project has no restriction list.
        config(['cmsbackend.projects.key' => 'nam-binh-duong']);
        $this->assertSame(['nam-binh-duong'], ProjectContext::fixedThemeChoices());
        config(['cmsbackend.projects.key' => '']);
        $this->assertSame([], ProjectContext::fixedThemeChoices());
    }

    public function test_project_registry_of_the_theme(): void
    {
        $this->assertSame('bandean-v2', ProjectContext::projectOfTheme('bandean-v2'));
        $this->assertSame('ban-de-an', ProjectContext::projectOfTheme('ban-de-an'));
        $this->assertSame('bandean-v2', ProjectContext::theme('bandean-v2'));
        // ban-de-an keeps its own theme and no longer lists bandean-v2 as a further theme.
        $this->assertSame('ban-de-an', ProjectContext::theme('ban-de-an'));
        $this->assertArrayNotHasKey('other_themes', config('cmsbackend.projects.projects.ban-de-an'));
        $this->assertNull(ProjectContext::projectOfTheme('does-not-exist'));
    }

    public function test_the_project_uses_the_theme_it_is_given_and_its_own_views(): void
    {
        $this->useTheme('bandean-v2');
        $screens = app(ScreenTheme::class);

        $this->assertSame('bandean-v2', $screens->key('public'));
        $this->assertStringEndsWith('screens/bandean-v2/layout.blade.php', str_replace('\\', '/', (string) $screens->path('public', 'layout')));
        // A block the theme has rebuilt comes from the theme itself; the files it has not rebuilt still come from the layer.
        $this->assertStringEndsWith('screens/bandean-v2/components/home/news.blade.php', str_replace('\\', '/', (string) $screens->path('public', 'components.home.news')));
        $shared = collect(glob(base_path('Modules/CmsFrontend/resources/views/screens/_campus/components/home/*.blade.php')))
            ->map(fn (string $file) => basename($file, '.blade.php'))
            ->first(fn (string $name) => ! is_file(base_path('Modules/CmsFrontend/resources/views/screens/bandean-v2/components/home/'.$name.'.blade.php')));
        $this->assertNotNull($shared, 'every layer block has been rebuilt by the theme: nothing is left to share');
        $this->assertStringEndsWith('screens/_campus/components/home/'.$shared.'.blade.php', str_replace('\\', '/', (string) $screens->path('public', 'components.home.'.$shared)));
    }

    public function test_ban_de_an_itself_is_unchanged(): void
    {
        $this->useTheme('ban-de-an');

        $this->assertSame('ban-de-an', app(ScreenTheme::class)->key('public'));
        $this->assertStringEndsWith('screens/ban-de-an/layout.blade.php', str_replace('\\', '/', (string) app(ScreenTheme::class)->path('public', 'layout')));
    }

    public function test_the_sign_in_page_of_the_theme_uses_its_own_login_design(): void
    {
        $this->app->instance(LoginDesigns::class, new LoginDesigns(fn () => true));
        $this->useTheme('bandean-v2');

        $designs = app(LoginDesigns::class);
        $this->assertArrayHasKey('bandean-v2', $designs->all());
        $this->assertSame('bandean-v2', $designs->key());

        // ban-de-an keeps its own design: the new one is an addition, not a replacement.
        $this->useTheme('ban-de-an');
        $this->assertSame('ban-de-an', app(LoginDesigns::class)->key());
    }

    public function test_the_login_design_reads_its_images_from_the_public_folder_and_they_exist(): void
    {
        $blade = (string) file_get_contents(base_path('Modules/CmsFrontend/resources/views/auth/designs/bandean-v2/login.blade.php'));
        preg_match_all("#asset\\('([^']+)'\\)#", $blade, $matches);

        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $file) {
            $this->assertFileExists(public_path($file), $file);
        }
        $this->assertFileExists(public_path((string) config('cmsbackend.screens.themes.bandean-v2.login.logo')));
    }

    public function test_the_theme_has_its_own_assets_and_nothing_points_back_at_ban_de_an(): void
    {
        $views = base_path('Modules/CmsFrontend/resources/views/screens/bandean-v2');
        foreach (['components/layouts/commonMaster.blade.php', 'components/layouts/sections/header/header.blade.php'] as $file) {
            $html = (string) file_get_contents($views.'/'.$file);
            $this->assertStringNotContainsString('ban-de-an', $html, $file);
        }
        $this->assertFileExists(base_path('Modules/CmsFrontend/resources/assets/css/screens/bandean-v2/theme.css'));
        $this->assertFileExists(base_path('Modules/CmsFrontend/resources/assets/js/screens/bandean-v2/theme.js'));
        $this->assertStringContainsString('css/screens/bandean-v2/theme.css', (string) file_get_contents($views.'/components/layouts/commonMaster.blade.php'));
    }
}
