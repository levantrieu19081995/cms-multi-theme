<?php

declare(strict_types=1);

namespace Tests\Feature\Login;

use App\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Modules\CmsBackend\Http\Controllers\DeveloperLoginController;
use Modules\CmsBackend\Services\LoginDesigns;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Login designs: the sign-in page is a folder under Modules/CmsFrontend/resources/views/auth/designs, chosen per project. Resolution,
 * fallbacks, transition, the Developer save, and the page that is really rendered. SQLite :memory: only (never the development database).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Login
 */
final class LoginDesignsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }

        config(['activitylog.enabled' => false, 'cmsbackend.editorial_database' => ':memory:', 'cmsbackend.projects.key' => 'nam-binh-duong']);
        (require base_path('database/migrations/2024_10_16_083323_create_options_table.php'))->up();
        $this->assertTrue(Schema::hasTable('options'));
        $this->useBuild(fn () => true);
    }

    /** Every design counts as built (or not), instead of reading the Vite manifest of this machine. */
    private function useBuild(\Closure $built): void
    {
        $this->app->instance(LoginDesigns::class, new LoginDesigns($built));
        request()->attributes->remove('_cms_login_designs_map');
    }

    private function designs(): LoginDesigns
    {
        return app(LoginDesigns::class);
    }

    private function forProject(string $project): void
    {
        config(['cmsbackend.projects.key' => $project]);
        request()->attributes->remove('_cms_login_designs_map');
    }

    public function test_the_existing_designs_are_found_with_their_transition(): void
    {
        $all = $this->designs()->all();

        $this->assertArrayHasKey('default', $all);
        $this->assertArrayHasKey('nam-binh-duong', $all);
        $this->assertArrayHasKey('ban-de-an', $all);
        $this->assertSame('push-horizontal', $all['default']['transition']);
        $this->assertNull($all['nam-binh-duong']['transition']);
        $this->assertTrue($all['default']['has_transition']);
        $this->assertSame('Modules/CmsFrontend/resources/views/auth/designs/ban-de-an/auth.css', $all['ban-de-an']['entry']);
        // A folder name starting with "_" (the template) is never listed.
        $this->assertArrayNotHasKey('_template', $all);
    }

    public function test_a_project_without_a_choice_gets_the_design_of_its_theme(): void
    {
        foreach (['nam-binh-duong' => 'nam-binh-duong', 'ban-de-an' => 'ban-de-an', 'khuyen-nong' => 'default'] as $project => $design) {
            $this->forProject($project);

            $this->assertSame($design, $this->designs()->key(), $project);
            $this->assertSame('cmsfrontend::auth.designs.'.$design.'.login', $this->designs()->view());
        }
    }

    public function test_the_choice_is_stored_per_project_and_wins(): void
    {
        $this->designs()->assign('nam-binh-duong', 'default', '127.0.0.1');

        $this->forProject('nam-binh-duong');
        $this->assertSame('default', $this->designs()->key());
        $this->forProject('ban-de-an');
        $this->assertSame('ban-de-an', $this->designs()->key());

        $this->designs()->assign('ban-de-an', 'nam-binh-duong', null);
        $this->forProject('ban-de-an');
        $this->assertSame('nam-binh-duong', $this->designs()->key());
        $this->assertSame(['nam-binh-duong' => 'default', 'ban-de-an' => 'nam-binh-duong'], json_decode((string) Option::query()->where('key', LoginDesigns::OPTION)->value('value'), true));
    }

    public function test_removing_the_choice_gives_the_project_back_to_its_theme(): void
    {
        $this->designs()->assign('nam-binh-duong', 'default', null);
        $this->designs()->assign('nam-binh-duong', null, null);

        $this->forProject('nam-binh-duong');
        $this->assertSame('nam-binh-duong', $this->designs()->key());
        $this->assertNull($this->designs()->assigned('nam-binh-duong'));
    }

    public function test_a_wrong_stored_value_never_breaks_the_page(): void
    {
        foreach (['not json', '[1,2]', '{"nam-binh-duong":"../../etc/passwd"}', '{"nam-binh-duong":"does-not-exist"}', '{"nam-binh-duong":42}'] as $stored) {
            Option::query()->updateOrCreate(['key' => LoginDesigns::OPTION], ['value' => $stored, 'autoload' => 1]);
            $this->forProject('nam-binh-duong');

            $this->assertSame('nam-binh-duong', $this->designs()->key(), $stored);
        }
    }

    public function test_a_design_that_is_not_in_the_build_is_not_used(): void
    {
        $this->designs()->assign('nam-binh-duong', 'default', null);

        // The server was built without the CSS of "default": the saved choice is ignored and the theme's design is used.
        $this->useBuild(fn (string $entry) => ! str_contains($entry, '/designs/default/'));
        $this->forProject('nam-binh-duong');

        $this->assertSame('nam-binh-duong', $this->designs()->key());
        $this->assertArrayNotHasKey('default', $this->designs()->choices());
        $this->assertFalse($this->designs()->all()['default']['built']);
    }

    public function test_when_nothing_is_built_the_page_still_resolves_to_a_design(): void
    {
        $this->useBuild(fn () => false);

        $this->assertSame('default', $this->designs()->key());
    }

    public function test_the_transition_belongs_to_the_design(): void
    {
        $this->forProject('nam-binh-duong');
        $this->assertNull($this->designs()->transition());

        $this->designs()->assign('nam-binh-duong', 'default', null);
        $this->forProject('nam-binh-duong');
        $this->assertSame('push-horizontal', $this->designs()->transition());

        $this->forProject('khuyen-nong');
        $this->assertSame('push-horizontal', $this->designs()->transition());
    }

    public function test_assign_refuses_unknown_designs_and_projects_and_writes_nothing(): void
    {
        foreach ([['nam-binh-duong', 'nope'], ['nam-binh-duong', '../default'], ['no-such-project', 'default']] as [$project, $design]) {
            try {
                $this->designs()->assign($project, $design, null);
                $this->fail('Expected a refusal for '.$project.' / '.$design);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(0, Option::query()->where('key', LoginDesigns::OPTION)->count());
    }

    public function test_the_page_shows_the_design_of_the_project(): void
    {
        $this->withoutVite();

        // Before any choice: the two-column design of the theme (image panel).
        $this->get('/cms/login')->assertOk()->assertSee('cms-login-visual', false)->assertDontSee('data-auth-layout="card"', false);

        $this->designs()->assign('nam-binh-duong', 'default', null);
        request()->attributes->remove('_cms_login_designs_map');

        $this->get('/cms/login')->assertOk()->assertSee('data-auth-layout="card"', false)->assertSee('data-auth-transition="push-horizontal"', false);
    }

    public function test_the_register_page_uses_the_same_design(): void
    {
        $this->withoutVite();
        $this->designs()->assign('nam-binh-duong', 'default', null);
        request()->attributes->remove('_cms_login_designs_map');

        $this->get('/register')->assertOk()->assertSee('data-auth-layout="card"', false)->assertSee('data-auth-mode="register"', false);
    }

    public function test_the_developer_preview_shows_one_design_without_storing_anything(): void
    {
        $this->withoutVite();
        view()->share('errors', new ViewErrorBag);

        $html = app(DeveloperLoginController::class)->preview('default')->getContent();

        $this->assertStringContainsString('data-auth-layout="card"', $html);
        $this->assertSame(0, Option::query()->where('key', LoginDesigns::OPTION)->count());
    }

    public function test_the_developer_preview_of_an_unknown_design_is_a_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(DeveloperLoginController::class)->preview('nope');
    }

    public function test_only_the_developer_session_can_save(): void
    {
        $this->post('/cms/developer-settings/login-designs', ['designs' => ['nam-binh-duong' => 'default']]);

        $this->assertSame(0, Option::query()->where('key', LoginDesigns::OPTION)->count());
    }
}
