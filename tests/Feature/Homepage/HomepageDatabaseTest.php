<?php

declare(strict_types=1);

namespace Tests\Feature\Homepage;

use App\Models\Option;
use App\Models\Taxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\CmsFrontend\Homepage\EffectCatalog;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;
use Modules\CmsFrontend\Homepage\HomepageSettingsService;
use Modules\CmsFrontend\Homepage\Models\HomepageBlockInstance;
use Modules\CmsFrontend\Homepage\Models\HomepageBlockSetting;
use Modules\CmsFrontend\Homepage\Models\HomepageThemeBlock;
use Modules\CmsFrontend\Services\HomeBlocks;
use Tests\TestCase;

/**
 * Test cần bảng thật. KHÔNG BAO GIỜ chạy trên database dev: chỉ chạy khi đặt biến môi trường HLM_TEST_DATABASE = tên một database THỬ
 * (bản sao schema đầy đủ của CMS, CHƯA có 3 bảng của Homepage Layout Manager; test tự chạy migration up/down). Không đặt biến thì test bị bỏ qua.
 *
 *   HLM_TEST_DATABASE=cms_hlm_test vendor/bin/phpunit tests/Feature/Homepage
 */
final class HomepageDatabaseTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_11_02_100001_create_homepage_layout_manager_tables.php';

    private const EFFECT_MIGRATION = 'database/migrations/2026_11_02_200001_add_effect_key_to_homepage_theme_blocks.php';

    private ?Migration $migration = null;

    private ?Migration $effectMigration = null;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) getenv('HLM_TEST_DATABASE');
        if ($database === '') {
            $this->markTestSkipped('Đặt HLM_TEST_DATABASE=<database thử> để chạy test cần bảng thật (không chạy trên database dev).');
        }
        // Chặn kép: tên phải chứa "test" và không được là database dev/đang cấu hình trong .env.
        if (! str_contains(strtolower($database), 'test') || in_array($database, ['cms_workspace', 'cms_phpunit_blocked'], true)) {
            $this->fail('HLM_TEST_DATABASE ('.$database.') không phải database thử (tên phải chứa "test" và khác database dev): từ chối chạy.');
        }
        config(['database.connections.'.config('database.default').'.database' => $database]);
        DB::purge();
        $this->assertSame($database, DB::connection()->getDatabaseName(), 'phải đang dùng database thử');
        $this->assertFalse(Schema::hasTable('homepage_theme_blocks'), 'database thử phải chưa có bảng của chức năng');

        HomepageLayoutService::fakeReady(null);
        EffectCatalog::fakeManifest(null);
        $this->migration = require base_path(self::MIGRATION);
        $this->migration->up();
        $this->effectMigration = require base_path(self::EFFECT_MIGRATION);
        $this->effectMigration->up();
        $this->assertTrue(Schema::hasColumn('homepage_theme_blocks', 'effect_key'));
        HomepageLayoutService::fakeReady(null);
    }

    protected function tearDown(): void
    {
        if ($this->migration !== null) {
            $this->effectMigration?->down();
            $this->migration->down();
        }
        HomepageLayoutService::fakeReady(null);
        EffectCatalog::fakeManifest(false);
        parent::tearDown();
    }

    private function service(): HomepageLayoutService
    {
        $registry = new HomepageBlockRegistry;

        return new HomepageLayoutService($registry, new HomepageSettingsService($registry));
    }

    /** @return int[] */
    private function categories(int $minimum): array
    {
        $ids = Taxonomy::query()->where('taxonomy', 'category')->orderBy('term_taxonomy_id')->limit(4)->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
        if (count($ids) < $minimum) {
            $this->markTestSkipped('Database thử cần ít nhất '.$minimum.' chuyên mục.');
        }

        return $ids;
    }

    public function test_migration_creates_tables_indexes_and_foreign_keys_and_down_removes_only_them(): void
    {
        foreach (['homepage_block_instances', 'homepage_theme_blocks', 'homepage_block_settings'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $indexes = fn (string $table) => array_column(Schema::getIndexes($table), 'name');
        $this->assertContains('homepage_theme_blocks_unique', $indexes('homepage_theme_blocks'));
        $this->assertContains('homepage_theme_blocks_order', $indexes('homepage_theme_blocks'));
        $this->assertContains('homepage_block_settings_unique', $indexes('homepage_block_settings'));

        $instance = HomepageBlockInstance::create(['instance_key' => 'fk-test', 'definition_key' => 'hero', 'name_vi' => 'x']);
        HomepageThemeBlock::create(['theme_key' => 'default', 'block_instance_id' => $instance->id, 'zone_key' => 'main', 'enabled' => true, 'sort_order' => 1]);
        HomepageBlockSetting::create(['theme_key' => 'default', 'block_instance_id' => $instance->id, 'settings' => ['a' => 1]]);
        $instance->delete();
        $this->assertSame(0, HomepageThemeBlock::count(), 'FK cascade theme_blocks');
        $this->assertSame(0, HomepageBlockSetting::count(), 'FK cascade settings');

        $this->migration->down();
        $this->assertFalse(Schema::hasTable('homepage_block_instances'));
        $this->assertTrue(Schema::hasTable('options'), 'down không đụng bảng nghiệp vụ');
        $this->migration->up(); // chạy lại phải an toàn (hasTable guard)
        $this->migration->up();
        $this->assertTrue(Schema::hasTable('homepage_theme_blocks'));
    }

    public function test_install_defaults_twice_creates_no_duplicates(): void
    {
        $service = $this->service();
        $registry = new HomepageBlockRegistry;
        $expected = array_sum(array_map(fn ($layout) => count($layout->blocks()), $registry->layouts()));

        $this->assertSame($expected, $service->installDefaults());
        $this->assertSame(0, $service->installDefaults(), 'lần hai không thêm gì');
        $this->assertSame($expected, HomepageThemeBlock::count());
        $this->assertSame(count($registry->instanceKeys()), HomepageBlockInstance::count());
            }

    public function test_install_does_not_overwrite_a_layout_the_developer_already_changed(): void
    {
        $service = $this->service();
        $service->installDefaults();
        $rows = $service->rows('default');
        $changed = array_map(fn ($row) => $row['instance'] === 'news' ? ['enabled' => false] + $row : $row, $rows);
        $service->apply('default', $changed);
        $service->installDefaults();

        $news = collect($service->rows('default'))->firstWhere('instance', 'news');
        $this->assertFalse($news['enabled']);
    }

    public function test_preview_leaves_live_untouched_and_apply_writes_and_rejects_invalid_input_atomically(): void
    {
        $service = $this->service();
        $service->installDefaults();
        $live = $service->rows('default');

        $edited = array_map(fn ($row) => $row['instance'] === 'news' ? ['enabled' => false] + $row : $row, $live);
        $token = $service->createSnapshot('default', $service->validate('default', $edited)['rows'], 'session-a');
        $this->assertNotNull($service->snapshot($token, 'session-a'));
        $this->assertSame($live, $service->rows('default'), 'preview không đổi Live');

        $bad = $edited;
        $bad[0]['zone'] = 'does-not-exist';
        try {
            $service->apply('default', $bad);
            $this->fail('phải từ chối cấu hình sai');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('invalid_zone', $e->getMessage());
        }
        $this->assertSame($live, $service->rows('default'), 'cấu hình sai không ghi gì');

        $service->apply('default', $edited);
        $this->assertFalse(collect($service->rows('default'))->firstWhere('instance', 'news')['enabled']);
    }

    public function test_homepage_layout_follows_the_installed_rows_and_falls_back_to_the_code_default(): void
    {
        $service = $this->service();
        $this->assertSame('hero', $service->live('default')['zones']['hero'][0]['instance'], 'chưa cài: dùng bố cục mặc định trong code');

        $service->installDefaults();
        $rows = array_map(fn ($row) => $row['instance'] === 'news' ? ['enabled' => false] + $row : $row, $service->rows('default'));
        $service->apply('default', $rows);

        $instances = array_column($service->live('default')['zones']['main'], 'instance');
        $this->assertNotContains('news', $instances, 'cụm tắt không có trong kế hoạch render');
        $this->assertContains('information', $instances);
    }

    public function test_settings_are_separate_per_theme_and_never_touch_legacy_options(): void
    {
        [$first, $second] = $this->categories(2);
        $service = $this->service();
        $service->installDefaults();
        $settings = new HomepageSettingsService(new HomepageBlockRegistry);
        $before = Option::where('key', 'web_category_home')->value('value');

        // `introduction` có ở cả hai theme; mỗi theme chọn một chuyên mục khác nhau.
        $settings->save('nam-binh-duong', 'introduction', ['category_id' => $first]);
        request()->attributes->remove('_homepage_settings_nam-binh-duong');
        $settings->save('ban-de-an', 'introduction', ['category_id' => $second]);
        request()->attributes->remove('_homepage_settings_ban-de-an');

        $this->assertSame($first, $settings->read('nam-binh-duong', 'introduction')['category_id']);
        $this->assertSame($second, $settings->read('ban-de-an', 'introduction')['category_id']);

        // `information` (default) có binding option cũ: lưu không được ghi option toàn cục và theme khác không đọc ra giá trị này.
        $settings->save('default', 'information', ['notice_category' => $first, 'document_filter' => '']);
        $this->assertSame($before, Option::where('key', 'web_category_home')->value('value'), 'không mirror về option cũ');
        $this->assertSame($first, $settings->read('default', 'information')['notice_category']);
    }

    public function test_install_seeds_settings_once_from_legacy_options(): void
    {
        [$first, $second] = $this->categories(2);
        Option::updateOrCreate(['key' => 'web_category_home'], ['value' => (string) $first, 'autoload' => 1]);
        $service = $this->service();
        $service->installDefaults();
        $settings = new HomepageSettingsService(new HomepageBlockRegistry);
        $this->assertSame($first, $settings->read('default', 'information')['notice_category'], 'khởi tạo từ option cũ');

        Option::updateOrCreate(['key' => 'web_category_home'], ['value' => (string) $second, 'autoload' => 1]);
        $service->installDefaults();
        request()->attributes->remove('_homepage_settings_default');
        $this->assertSame($first, $settings->read('default', 'information')['notice_category'], 'đổi option cũ sau đó không ghi đè settings của theme');
    }

    public function test_disabling_a_block_keeps_its_settings_and_enabling_restores_them(): void
    {
        [$first] = $this->categories(1);
        $service = $this->service();
        $service->installDefaults();
        $settings = new HomepageSettingsService(new HomepageBlockRegistry);
        $settings->save('default', 'information', ['notice_category' => $first, 'document_filter' => '']);

        $rows = $service->rows('default');
        $off = array_map(fn ($row) => $row['instance'] === 'information' ? ['enabled' => false] + $row : $row, $rows);
        $service->apply('default', $off);
        $this->assertSame(1, HomepageBlockSetting::whereHas('instance', fn ($q) => $q->where('instance_key', 'information'))->where('theme_key', 'default')->count());

        $service->apply('default', $rows);
        request()->attributes->remove('_homepage_settings_default');
        $this->assertSame($first, $settings->read('default', 'information')['notice_category']);
    }

    public function test_section_queries_do_not_grow_with_the_number_of_instances(): void
    {
        $ids = $this->categories(2);
        $count = function (array $categoryIds): int {
            request()->attributes->remove('_hlm_sections');
            $queries = 0;
            DB::listen(function () use (&$queries) {
                $queries++;
            });
            app(HomeBlocks::class)->sectionsByCategories($categoryIds);

            return $queries;
        };
        $one = $count([$ids[0]]);
        $many = $count($ids);
        $this->assertSame($one, $many, 'số truy vấn không tăng theo số chuyên mục/instance');

        request()->attributes->remove('_hlm_sections');
        app(HomeBlocks::class)->sectionsByCategories($ids);
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        foreach ($ids as $id) {
            app(HomeBlocks::class)->sectionByCategory($id);
        }
        $this->assertSame(0, $queries, 'đã nạp trước thì render từng instance không truy vấn thêm');
    }

    public function test_chosen_effect_is_stored_per_theme_and_default_stays_null(): void
    {
        $service = $this->service();
        $service->installDefaults();
        $this->assertNull(collect($service->rows('nam-binh-duong'))->firstWhere('instance', 'news')['effect'], 'mặc định: chưa chọn');

        $rows = array_map(fn ($row) => $row['instance'] === 'news' ? ['effect' => 'none'] + $row : $row, $service->rows('nam-binh-duong'));
        $service->apply('nam-binh-duong', $rows);

        $this->assertSame('none', collect($service->rows('nam-binh-duong'))->firstWhere('instance', 'news')['effect']);
        $this->assertSame('none', collect($service->live('nam-binh-duong')['zones']['main'])->firstWhere('instance', 'news')['effect']);
        $this->assertNull(collect($service->rows('ban-de-an'))->firstWhere('instance', 'news')['effect'], 'theme khác không đổi');

        $service->apply('nam-binh-duong', array_map(fn ($row) => $row['instance'] === 'news' ? ['effect' => ''] + $row : $row, $service->rows('nam-binh-duong')));
        $this->assertNull(collect($service->rows('nam-binh-duong'))->firstWhere('instance', 'news')['effect'], 'chọn lại mặc định');
    }
}
