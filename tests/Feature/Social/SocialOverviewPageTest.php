<?php

declare(strict_types=1);

namespace Tests\Feature\Social;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Http\Controllers\SocialOverviewController;
use Modules\CmsBackend\Http\Controllers\SocialStatisticsController;
use Tests\TestCase;

/**
 * The page "Tổng quan - thống kê": overview and statistics in one page, each part prepared only for the user who holds its
 * permission. SQLite :memory: only (never the development database).
 *
 * Run:  DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Social
 */
final class SocialOverviewPageTest extends TestCase
{
    private const PROJECT = 'nam-binh-duong';

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Needs SQLite :memory: (see the header of this file); it never runs on a real database.');
        }

        config(['activitylog.enabled' => false, 'cmsbackend.projects.key' => self::PROJECT]);
        // The statistics by creator join the users table: a minimal one is enough here.
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
        });
        foreach ([
            '2026_10_30_000001_create_social_accounts_table.php',
            '2026_10_30_000002_create_social_publications_table.php',
            '2026_10_30_000003_create_social_publication_channels_table.php',
            '2026_11_11_000001_add_metrics_to_social_publication_channels_table.php',
        ] as $file) {
            (require base_path('database/migrations/'.$file))->up();
        }
    }

    /** @param  array<int, PermissionsEnum>  $allowed */
    private function userWith(array $allowed): User
    {
        $labels = array_map(fn (PermissionsEnum $permission) => $permission->label(), $allowed);
        $user = new class($labels) extends User
        {
            public function __construct(private array $labels = [])
            {
                parent::__construct();
            }

            public function can($abilities, $arguments = []): bool
            {
                return in_array($abilities, $this->labels, true);
            }
        };
        $user->forceFill(['id' => 7]);

        return $user;
    }

    /** The data the page receives for a user (the page itself is not rendered: no layout, no menu). */
    private function pageData(User $user): array
    {
        $request = request();
        $request->setUserResolver(fn () => $user);

        return app(SocialOverviewController::class)->index()->getData();
    }

    public function test_a_user_with_both_permissions_gets_the_overview_and_the_statistics(): void
    {
        $data = $this->pageData($this->userWith([PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_STATISTICS_VIEW]));

        $this->assertNotNull($data['overview']);
        $this->assertNotNull($data['recent']);
        $this->assertNotNull($data['stats']);
        $this->assertArrayHasKey('statuses', $data['stats']);
        $this->assertArrayHasKey('metrics', $data['stats']);
    }

    public function test_the_parent_permission_gives_both_parts(): void
    {
        $data = $this->pageData($this->userWith([PermissionsEnum::SOCIAL_MANAGER]));

        $this->assertNotNull($data['overview']);
        $this->assertNotNull($data['stats']);
    }

    public function test_without_the_statistics_permission_the_statistics_are_not_prepared(): void
    {
        $data = $this->pageData($this->userWith([PermissionsEnum::SOCIAL_PUBLICATION_VIEW]));

        $this->assertNotNull($data['overview']);
        $this->assertNull($data['stats'], 'Statistics must not reach the view of a user without the statistics permission.');
    }

    public function test_with_only_the_statistics_permission_the_publications_are_not_prepared(): void
    {
        $data = $this->pageData($this->userWith([PermissionsEnum::SOCIAL_STATISTICS_VIEW]));

        $this->assertNull($data['overview']);
        $this->assertNull($data['recent'], 'The recent requests need the publication view permission.');
        $this->assertNotNull($data['stats']);
    }

    public function test_the_old_statistics_address_goes_to_the_merged_page(): void
    {
        $response = app(SocialStatisticsController::class)->index();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/truyen-thong', $response->headers->get('Location'));
    }
}
