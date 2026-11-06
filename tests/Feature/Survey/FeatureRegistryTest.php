<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use App\DataProviders\MenuItemDataProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Features\Contracts\FeatureDefinition;
use Modules\CmsBackend\Features\Definitions\SurveyFeature;
use Modules\CmsBackend\Features\FeatureRegistry;
use Modules\CmsBackend\Services\Developer\PermissionAvailability;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Services\EditorialLayout;

/** Feature Registry: định nghĩa tự nhận, trạng thái bật/tắt, cổng route, trang danh sách công khai, menu "Chức năng hệ thống". */
final class FeatureRegistryTest extends SurveySqliteTestCase
{
    private function registry(): FeatureRegistry
    {
        return app(FeatureRegistry::class);
    }

    private function survey(string $slug, string $name, SurveyStatus $status = SurveyStatus::Open, array $info = []): KhaoSat
    {
        $svc = app(SurveyService::class);
        $survey = $svc->create($info + ['ks_ten' => $name, 'ks_ma' => $slug, 'ks_mo_ta_ngan' => "Mô tả $slug"], 1);
        $svc->saveStructure($survey, [['name' => 'P', 'questions' => [['type' => 'text', 'text' => 'Q']]]], 1);
        if ($status !== SurveyStatus::Draft) {
            $survey = $svc->transition($survey->fresh(), SurveyStatus::Open, 1);
            if ($status === SurveyStatus::Paused || $status === SurveyStatus::Ended) {
                $survey = $svc->transition($survey->fresh(), $status, 1);
            }
        }

        return $survey->fresh();
    }

    // ------------------------------------------------------------------ definition

    public function test_registry_discovers_the_survey_definition_from_the_definitions_folder(): void
    {
        $all = $this->registry()->all();
        $this->assertArrayHasKey('survey', $all);
        $d = $all['survey'];
        $this->assertInstanceOf(SurveyFeature::class, $d);
        $this->assertSame('Khảo sát', $d->name());
        $this->assertSame('ri-survey-line', $d->icon());
        $this->assertSame('cms.khao-sat', $d->adminRoute());
        $this->assertSame([['key' => 'index', 'name' => 'Danh sách khảo sát', 'route' => 'cms.frontend.khaosat.index']], $d->publicTargets());
        $this->assertTrue($d->enabledByDefault());
        $this->assertSame([
            'khaosat-manager', 'khaosat-view', 'khaosat-create', 'khaosat-update', 'khaosat-publish', 'khaosat-delete',
            'khaosat-response-view', 'khaosat-response-delete', 'khaosat-statistics-view', 'khaosat-export',
        ], $d->permissions());
        $this->assertSame([], $this->registry()->errors());
    }

    public function test_only_classes_implementing_the_contract_are_accepted_and_duplicate_keys_are_reported(): void
    {
        $dir = sys_get_temp_dir().'/ks_features_'.uniqid();
        mkdir($dir);
        $ns = 'Tests\\Feature\\Survey\\Tmp'.uniqid();
        $make = fn (string $class, string $key, string $impl = 'implements \\'.FeatureDefinition::class) => file_put_contents("$dir/$class.php", "<?php\nnamespace $ns;\nfinal class $class $impl {\n"
            .($impl === '' ? '' : "public function key(): string { return '$key'; } public function name(): string { return 'N'; } public function description(): string { return 'D'; } public function icon(): string { return 'ri-x'; } public function adminRoute(): ?string { return null; } public function publicTargets(): array { return []; } public function permissions(): array { return []; } public function enabledByDefault(): bool { return false; }")
            ."}\n");
        $make('AlphaFeature', 'alpha');
        $make('BetaFeature', 'beta');
        $make('DuplicateFeature', 'alpha');       // trùng key với AlphaFeature
        $make('NotAFeature', 'ignored', '');       // không triển khai FeatureDefinition
        $make('BadKeyFeature', 'Bad Key');         // key sai định dạng

        $found = (new FeatureRegistry($dir, $ns))->discover();
        $this->assertSame(['alpha', 'beta'], array_keys($found['classes']));
        $this->assertSame($ns.'\\AlphaFeature', $found['classes']['alpha'], 'trùng key giữ bản đầu');
        $this->assertCount(2, $found['errors']);
        $this->assertStringContainsString('Trùng mã chức năng "alpha"', implode(' ', $found['errors']));
        $this->assertStringContainsString('Bad Key', implode(' ', $found['errors']));
        array_map('unlink', glob("$dir/*.php"));
        rmdir($dir);
    }

    public function test_definitions_are_cached_and_refresh_when_application_cache_is_cleared(): void
    {
        $this->registry()->all();
        $this->assertTrue(Cache::has('cms.features.definitions'), 'danh sách definition được cache');

        // Registry mới đọc cache, không quét lại thư mục: sửa cache thì thấy ngay.
        Cache::forever('cms.features.definitions', ['classes' => [], 'errors' => []] + Cache::get('cms.features.definitions'));
        $this->assertSame([], (new FeatureRegistry)->all());

        // Xóa cache ứng dụng → quét lại.
        Cache::flush();
        $this->assertArrayHasKey('survey', (new FeatureRegistry)->all());
    }

    // ------------------------------------------------------------------ trạng thái

    public function test_state_defaults_to_the_definition_and_database_overrides_it(): void
    {
        $r = $this->registry();
        $this->assertTrue($r->isEnabled('survey'));
        $this->assertFalse($r->hasOverride('survey'));
        $this->assertFalse($r->isEnabled('khong-ton-tai'), 'không có definition = không có chức năng');

        $r->setEnabled('survey', false, 7);
        $this->assertFalse($r->isEnabled('survey'));
        $row = DB::table('feature_settings')->where('feature_key', 'survey')->first();
        $this->assertSame(0, (int) $row->enabled);
        $this->assertSame(7, (int) $row->updated_by);
        // process mới (cache/memory sạch) vẫn đọc đúng từ DB
        $this->assertFalse((new FeatureRegistry)->isEnabled('survey'));

        $r->setEnabled('survey', true);
        $this->assertTrue($r->isEnabled('survey'));
        $this->assertSame(1, DB::table('feature_settings')->where('feature_key', 'survey')->count(), 'cập nhật, không thêm dòng');

        $this->expectException(\InvalidArgumentException::class);
        $r->setEnabled('khong-ton-tai', true);
    }

    public function test_missing_settings_table_falls_back_to_defaults(): void
    {
        Schema::drop('feature_settings');
        Cache::flush();
        $this->assertTrue((new FeatureRegistry)->isEnabled('survey'));
    }

    public function test_permissions_of_a_disabled_feature_are_unavailable(): void
    {
        $r = $this->registry();
        $this->assertSame('survey', $r->featureOfPermission('khaosat-export'));
        $this->assertNull($r->featureOfPermission('dichvu-manager'));
        $this->assertFalse($r->permissionBlocked('khaosat-export'));
        $availability = app(PermissionAvailability::class);
        $this->assertTrue($availability->isAvailableSlug('khaosat-export'));

        $r->setEnabled('survey', false);
        $this->assertTrue($r->permissionBlocked('khaosat-export'));
        $this->assertFalse($availability->isAvailableSlug('khaosat-export'));
        $this->assertTrue($availability->isAvailableSlug('dichvu-view'), 'quyền chức năng khác không bị ảnh hưởng');
    }

    public function test_route_gate_returns_404_when_off_and_keeps_the_data(): void
    {
        $survey = $this->survey('thu', 'Khảo sát thử');
        $before = [DB::table('ks_khao_sat')->count(), DB::table('ks_cau_hoi')->count()];
        $this->registry()->setEnabled('survey', false);
        $this->get('/khao-sat')->assertNotFound();
        $this->get('/khao-sat/thu')->assertNotFound();
        $this->get('/khao-sat/thu/lam-bai')->assertNotFound();
        $this->post('/khao-sat/thu/bat-dau')->assertNotFound();
        $this->actingAs($this->user(array_map(fn ($c) => $c->label(), \Modules\CmsBackend\Enums\PermissionsEnum::cases())));
        $this->get('/cms/khao-sat')->assertNotFound();
        $this->get("/cms/khao-sat/{$survey->ks_id}/chinh-sua")->assertNotFound();
        $this->assertSame($before, [DB::table('ks_khao_sat')->count(), DB::table('ks_cau_hoi')->count()], 'tắt chức năng không xóa dữ liệu');

        $this->registry()->setEnabled('survey', true);
        $this->get('/khao-sat/thu')->assertOk();
        $this->get('/cms/khao-sat')->assertOk();
    }

    public function test_every_survey_route_is_behind_the_feature_gate(): void
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $isSurvey = str_starts_with($route->uri(), 'khao-sat') || str_starts_with($route->uri(), 'cms/khao-sat');
            if ($isSurvey) {
                $this->assertContains('cms.feature:survey', $route->gatherMiddleware(), $route->uri().' phải qua cms.feature:survey');
            }
        }
        $this->assertFileDoesNotExist(base_path('Modules/CmsBackend/config/features.php'), 'không giữ nguồn bật/tắt thứ hai theo theme');
        $this->assertFileDoesNotExist(base_path('Modules/CmsBackend/app/Support/ThemeFeatures.php'));
    }

    // ------------------------------------------------------------------ /khao-sat

    public function test_public_index_lists_only_surveys_that_are_open_right_now(): void
    {
        $this->survey('dang-mo', 'Đang mở', info: ['ks_ket_thuc' => now()->addDays(3)->format('Y-m-d H:i:s')]);
        $this->survey('khong-han', 'Không giới hạn');
        $this->survey('nhap', 'Bản nháp', SurveyStatus::Draft);
        $this->survey('tam-dung', 'Tạm dừng', SurveyStatus::Paused);
        $this->survey('ket-thuc', 'Đã kết thúc', SurveyStatus::Ended);
        $future = $this->survey('chua-toi-gio', 'Chưa tới giờ');
        DB::table('ks_khao_sat')->where('ks_id', $future->ks_id)->update(['ks_bat_dau' => now()->addDay()]);
        $expired = $this->survey('het-han', 'Hết hạn');
        DB::table('ks_khao_sat')->where('ks_id', $expired->ks_id)->update(['ks_ket_thuc' => now()->subMinute()]);
        $deleted = $this->survey('da-xoa', 'Đã xóa');
        DB::table('ks_khao_sat')->where('ks_id', $deleted->ks_id)->update(['ks_deleted_at' => now()]);

        $html = $this->get('/khao-sat')->assertOk()->getContent();
        foreach (['Đang mở', 'Không giới hạn'] as $shown) {
            $this->assertStringContainsString($shown, $html);
        }
        foreach (['Bản nháp', 'Tạm dừng', 'Đã kết thúc', 'Chưa tới giờ', 'Hết hạn', 'Đã xóa'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, "$hidden không được hiện");
        }
        $this->assertStringContainsString('Mô tả dang-mo', $html);
        $this->assertStringContainsString('/khao-sat/dang-mo', $html);
        $this->assertMatchesRegularExpression('/Hạn tham gia: \d{2}:\d{2} \d{2}\/\d{2}\/\d{4}/', $html);
        // khảo sát không có hạn không in dòng "Hạn tham gia" thừa
        $this->assertSame(1, substr_count($html, 'Hạn tham gia'));
    }

    public function test_public_index_is_declared_before_the_slug_route_and_has_an_empty_state(): void
    {
        $names = array_values(array_filter(array_map(fn ($r) => $r->getName(), iterator_to_array(\Illuminate\Support\Facades\Route::getRoutes()))));
        $this->assertLessThan(array_search('cms.frontend.khaosat.show', $names, true), array_search('cms.frontend.khaosat.index', $names, true));
        $this->assertSame('/khao-sat', parse_url(route('cms.frontend.khaosat.index'), PHP_URL_PATH));
        $this->get('/khao-sat')->assertOk()->assertSee('Hiện chưa có khảo sát nào đang mở.');
        $this->survey('mot', 'Một');
        $this->get('/khao-sat/mot')->assertOk(); // /khao-sat/{slug} vẫn hoạt động
    }

    public function test_public_index_follows_the_theme_system_with_default_fallback(): void
    {
        $this->survey('mot', 'Một');
        foreach (['default', 'nam-binh-duong', 'ban-de-an'] as $theme) {
            $this->theme($theme);
            $this->get('/khao-sat')->assertOk()->assertSee('Một')->assertSee('Khảo sát đang mở');
        }
    }

    // ------------------------------------------------------------------ menu "Chức năng hệ thống"

    private function menuTables(): void
    {
        Schema::create('menus', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('description')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('menu_items', function ($t) {
            $t->integer('menu_id');
            $t->id();
            $t->string('title');
            $t->integer('parent_id')->default(0);
            $t->string('treepath');
            $t->string('url');
            $t->string('type')->default('url');
            $t->integer('ref_id');
            $t->string('target')->default('_self');
            $t->string('icon', 100)->nullable();
            $t->integer('ordinal')->default(0);
            $t->string('feature_key', 100)->nullable();
            $t->string('feature_target', 100)->nullable();
            $t->timestamps();
        });
        Schema::create('menu_item_translations', function ($t) {
            $t->id();
            $t->unsignedBigInteger('menu_item_id');
            $t->string('locale', 10);
            $t->string('title')->nullable();
            $t->string('custom_url')->nullable();
            $t->timestamps();
        });
        DB::table('menus')->insert(['id' => 1, 'name' => 'Menu thử']);
    }

    private function featureNode(array $over = []): array
    {
        return $over + ['id' => -1, 'title' => 'Khảo sát', 'type' => 'feature', 'url' => 'Khảo sát › Danh sách khảo sát', 'target' => '_self', 'icon' => 'ri-survey-line', 'ref_id' => 0, 'feature_key' => 'survey', 'feature_target' => 'index'];
    }

    /** @return array<int, array{title: string, url: ?string}> */
    private function publicMenu(): array
    {
        $items = (new MenuItemDataProvider)->getMenuItemByMenuId(1, true);
        $navigation = new \ReflectionMethod(EditorialLayout::class, 'navigation');

        return $navigation->invoke(app(EditorialLayout::class), $items);
    }

    public function test_feature_menu_stores_only_a_reference_and_url_comes_from_the_registry(): void
    {
        $this->menuTables();
        $provider = new MenuItemDataProvider;
        $provider->saveAll(1, [
            ['id' => -10, 'title' => 'Trang chủ', 'type' => 'url', 'url' => '/', 'target' => '_self', 'ref_id' => 0],
            $this->featureNode(),
        ]);

        $row = DB::table('menu_items')->where('type', 'feature')->first();
        $this->assertSame('survey', $row->feature_key);
        $this->assertSame('index', $row->feature_target);
        $this->assertStringNotContainsString('/khao-sat', $row->url, 'URL không được lưu làm nguồn chính');
        $old = DB::table('menu_items')->where('type', 'url')->first();
        $this->assertNull($old->feature_key, 'menu cũ không bị gắn tham chiếu chức năng');
        $this->assertSame('/', $old->url);

        $menu = $this->publicMenu();
        $this->assertSame(['Trang chủ', 'Khảo sát'], array_column($menu, 'title'));
        $this->assertSame('/khao-sat', parse_url((string) $menu[1]['url'], PHP_URL_PATH));
        $this->assertSame('ri-survey-line', $menu[1]['icon']);
    }

    public function test_feature_menu_hides_when_off_keeps_the_item_warns_in_admin_and_returns_when_on(): void
    {
        $this->menuTables();
        $provider = new MenuItemDataProvider;
        $provider->saveAll(1, [['id' => -10, 'title' => 'Trang chủ', 'type' => 'url', 'url' => '/', 'target' => '_self', 'ref_id' => 0], $this->featureNode()]);

        $this->registry()->setEnabled('survey', false);
        $this->assertSame(['Trang chủ'], array_column($this->publicMenu(), 'title'), 'ngoài website: ẩn');
        $this->assertSame(2, DB::table('menu_items')->count(), 'không tự xóa menu');
        $admin = collect($provider->getMenuItemByMenuId(1, false, false))->firstWhere('type', 'feature');
        $this->assertSame('disabled', $admin->feature_status, 'quản trị: cảnh báo chức năng đang tắt');

        // lưu lại menu khi chức năng đang tắt vẫn được (tham chiếu giữ nguyên)
        $provider->saveAll(1, $provider->getMenuItemByMenuId(1)->map(fn ($i) => (array) $i)->all());
        $this->assertSame(2, DB::table('menu_items')->count());

        $this->registry()->setEnabled('survey', true);
        $this->assertSame(['Trang chủ', 'Khảo sát'], array_column($this->publicMenu(), 'title'), 'bật lại: hiện lại');
        $this->assertSame('ok', collect($provider->getMenuItemByMenuId(1))->firstWhere('type', 'feature')->feature_status);
    }

    public function test_feature_menu_with_a_missing_definition_or_target_is_hidden_but_kept(): void
    {
        $this->menuTables();
        DB::table('menu_items')->insert([
            ['menu_id' => 1, 'title' => 'Mất chức năng', 'parent_id' => 0, 'treepath' => '/1', 'url' => 'x', 'type' => 'feature', 'ref_id' => 0, 'target' => '_self', 'feature_key' => 'da-go-bo', 'feature_target' => 'index', 'ordinal' => 0],
            ['menu_id' => 1, 'title' => 'Mất điểm đến', 'parent_id' => 0, 'treepath' => '/2', 'url' => 'x', 'type' => 'feature', 'ref_id' => 0, 'target' => '_self', 'feature_key' => 'survey', 'feature_target' => 'khong-co', 'ordinal' => 1],
        ]);
        $this->assertSame([], $this->publicMenu());
        $statuses = collect((new MenuItemDataProvider)->getMenuItemByMenuId(1))->pluck('feature_status')->all();
        $this->assertSame(['missing', 'missing'], $statuses);
        // lưu lại không đổi tham chiếu thì không bị chặn và không bị xóa
        $provider = new MenuItemDataProvider;
        $provider->saveAll(1, $provider->getMenuItemByMenuId(1)->map(fn ($i) => (array) $i)->all());
        $this->assertSame(2, DB::table('menu_items')->count());
    }

    public function test_feature_menu_rejects_unknown_feature_or_target_when_created_or_changed(): void
    {
        $this->menuTables();
        $provider = new MenuItemDataProvider;
        foreach ([['feature_key' => 'khong-co'], ['feature_target' => 'khong-co'], ['feature_key' => '']] as $bad) {
            try {
                $provider->saveAll(1, [$this->featureNode($bad)]);
                $this->fail('phải từ chối '.json_encode($bad));
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('structure', $e->errors());
            }
        }
        $this->assertSame(0, DB::table('menu_items')->count());
    }

    public function test_existing_menu_types_still_work_and_non_feature_items_carry_no_reference(): void
    {
        $this->menuTables();
        $provider = new MenuItemDataProvider;
        $provider->saveAll(1, [
            ['id' => -1, 'title' => 'Liên kết', 'type' => 'url', 'url' => 'https://example.test/x', 'target' => '_blank', 'ref_id' => 0, 'feature_key' => 'survey', 'feature_target' => 'index'],
            ['id' => -2, 'title' => 'Cha', 'type' => 'url', 'url' => '#', 'target' => '_self', 'ref_id' => 0, 'childs' => [$this->featureNode(['id' => -3])]],
        ]);
        $this->assertNull(DB::table('menu_items')->where('title', 'Liên kết')->value('feature_key'), 'trường chức năng bị loại khi type không phải feature');
        $menu = $this->publicMenu();
        $this->assertSame('https://example.test/x', $menu[0]['url']);
        $this->assertSame('_blank', $menu[0]['target']);
        $this->assertSame('Khảo sát', $menu[1]['children'][0]['title'], 'menu con loại chức năng hoạt động');
        $this->registry()->setEnabled('survey', false);
        $this->assertSame([], $this->publicMenu()[1]['children'] ?? [], 'tắt chức năng: mục con ẩn');
    }

    public function test_feature_parent_with_children_is_hidden_with_its_whole_branch_when_feature_is_off(): void
    {
        $this->menuTables();
        (new MenuItemDataProvider)->saveAll(1, [
            $this->featureNode(['id' => -1, 'childs' => [
                ['id' => -2, 'title' => 'Con 1', 'type' => 'url', 'url' => 'https://example.test/a', 'target' => '_self', 'ref_id' => 0],
                ['id' => -3, 'title' => 'Con 2', 'type' => 'url', 'url' => 'https://example.test/b', 'target' => '_self', 'ref_id' => 0],
            ]]),
            ['id' => -4, 'title' => 'Liên kết khác', 'type' => 'url', 'url' => 'https://example.test/x', 'target' => '_self', 'ref_id' => 0],
        ]);
        $menu = $this->publicMenu();
        $this->assertSame('Khảo sát', $menu[0]['title']);
        $this->assertCount(2, $menu[0]['children'], 'chức năng bật: hiện item cùng nhánh con');

        $this->registry()->setEnabled('survey', false);
        $menu = $this->publicMenu();
        $this->assertSame(['Liên kết khác'], array_column($menu, 'title'), 'tắt: ẩn cả item cha lẫn nhánh con');
        $this->assertSame(3, DB::table('menu_items')->whereIn('title', ['Khảo sát', 'Con 1', 'Con 2'])->count(), 'dữ liệu menu không bị xóa');

        $this->registry()->setEnabled('survey', true);
        $menu = $this->publicMenu();
        $this->assertSame('Khảo sát', $menu[0]['title']);
        $this->assertCount(2, $menu[0]['children'], 'bật lại: nhánh con xuất hiện lại');

        DB::table('menu_items')->where('title', 'Khảo sát')->update(['feature_target' => 'khong-co']);
        $this->assertSame(['Liên kết khác'], array_column($this->publicMenu(), 'title'), 'target không tồn tại: ẩn cả nhánh');
    }

    public function test_feature_and_target_names_follow_the_current_locale(): void
    {
        $original = app()->getLocale();
        try {
            foreach (['vi' => ['Khảo sát', 'Danh sách khảo sát'], 'en' => ['Survey', 'Survey list']] as $locale => [$name, $target]) {
                app()->setLocale($locale);
                $definition = $this->registry()->find('survey');
                $this->assertSame($name, $definition->name(), "tên feature ($locale)");
                $this->assertNotSame('', $definition->description());
                $this->assertStringNotContainsString('features.survey', $definition->description(), 'mô tả phải được dịch');
                $this->assertSame($target, $definition->publicTargets()[0]['name'], "tên target ($locale)");

                $choice = collect($this->registry()->menuChoices())->firstWhere('key', 'survey');
                $this->assertSame($name, $choice['name'], "bộ chọn menu ($locale)");
                $this->assertSame($target, $choice['targets'][0]['name']);
                $report = collect($this->registry()->report())->firstWhere('key', 'survey');
                $this->assertSame($name, $report['name']);
                $this->assertSame($target, $report['targets'][0]['name']);
            }
        } finally {
            app()->setLocale($original);
        }
    }

    public function test_menu_choices_and_report_are_generic(): void
    {
        $this->menuTables();
        DB::table('menu_items')->insert(['menu_id' => 1, 'title' => 'K', 'parent_id' => 0, 'treepath' => '/1', 'url' => 'x', 'type' => 'feature', 'ref_id' => 0, 'target' => '_self', 'feature_key' => 'survey', 'feature_target' => 'index', 'ordinal' => 0]);
        $choices = $this->registry()->menuChoices();
        $survey = collect($choices)->firstWhere('key', 'survey');
        $this->assertSame([['key' => 'index', 'name' => 'Danh sách khảo sát']], $survey['targets']);
        $report = collect($this->registry()->report())->firstWhere('key', 'survey');
        $this->assertSame(10, $report['permissions_declared']);
        $this->assertSame([], $report['permissions_missing_code']);
        $this->assertTrue($report['routes_ok']);
        $this->assertSame(1, $report['menu_count']);
        $this->assertSame('/cms/khao-sat', parse_url($report['admin_url'], PHP_URL_PATH));
        $this->assertStringNotContainsString("'survey'", (string) file_get_contents(base_path('Modules/CmsBackend/app/Features/FeatureRegistry.php')), 'Registry không có điều kiện riêng cho Survey');
    }

    // ------------------------------------------------------------------ Developer Settings

    private function developerSession(bool $unlocked): void
    {
        config(['cmsbackend.developer.token_hash' => Hash::make('mat-khau-dev')]);
        if ($unlocked) {
            $this->withSession(['developer_unlocked' => true]);
        }
        Schema::create('activity_log', function ($t) {
            $t->id();
            $t->string('log_name')->nullable();
            $t->text('description');
            $t->nullableMorphs('subject', 'subject');
            $t->string('event')->nullable();
            $t->nullableMorphs('causer', 'causer');
            $t->json('properties')->nullable();
            $t->uuid('batch_uuid')->nullable();
            $t->timestamps();
        });
    }

    public function test_only_an_unlocked_developer_session_can_toggle_a_feature(): void
    {
        $this->developerSession(false);
        // đã đăng nhập CMS với mọi quyền vẫn không thay đổi được khi chưa mở khóa Developer
        $this->actingAs($this->user(array_map(fn ($c) => $c->label(), \Modules\CmsBackend\Enums\PermissionsEnum::cases())));
        $this->post('/cms/developer-settings/features/survey', ['enabled' => 0]);
        $this->assertTrue($this->registry()->isEnabled('survey'));
        $this->assertSame(0, DB::table('feature_settings')->count());
        $this->assertSame(0, DB::table('activity_log')->count());
    }

    public function test_unlocked_developer_toggles_with_audit_and_validation(): void
    {
        $this->developerSession(true);
        $this->post('/cms/developer-settings/features/survey', ['enabled' => 0])->assertRedirect();
        $this->assertFalse((new FeatureRegistry)->isEnabled('survey'));
        $log = DB::table('activity_log')->first();
        $this->assertStringContainsString('tắt chức năng survey', $log->description);
        $props = json_decode($log->properties, true);
        $this->assertSame('developer_session', $props['actor']);
        $this->assertTrue($props['before']);
        $this->assertFalse($props['after']);
        $this->assertNull($log->causer_id, 'không gắn với tài khoản CMS');

        $this->post('/cms/developer-settings/features/survey', ['enabled' => 1])->assertRedirect();
        $this->assertTrue((new FeatureRegistry)->isEnabled('survey'));
        $this->assertSame(2, DB::table('activity_log')->count());

        // đặt lại đúng trạng thái hiện tại: không ghi audit thừa
        $this->post('/cms/developer-settings/features/survey', ['enabled' => 1])->assertRedirect();
        $this->assertSame(2, DB::table('activity_log')->count());

        // giá trị hoặc khóa không hợp lệ: không đổi gì
        $this->post('/cms/developer-settings/features/survey', ['enabled' => 'abc'])->assertRedirect();
        $this->post('/cms/developer-settings/features/khong-co', ['enabled' => 0])->assertRedirect();
        $this->assertSame(1, DB::table('feature_settings')->count());
        $this->assertSame(2, DB::table('activity_log')->count());
    }

    public function test_feature_toggle_route_has_csrf_throttle_and_developer_gate(): void
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('cms.dashboard.developer-settings.features.save');
        $middleware = $route->gatherMiddleware();
        $this->assertContains('web', $middleware, 'nhóm web = CSRF + session');
        $this->assertContains('cms.developer', $middleware);
        $this->assertContains('cms.developer.enabled', $middleware);
        $this->assertContains('throttle:10,1', $middleware);
        $this->assertSame(['POST'], $route->methods() === ['POST'] ? ['POST'] : $route->methods());
    }

    /** Chỉ báo cáo của Khảo sát: các feature khác (vd. Liên hệ) có công tắc riêng nên không lẫn vào kiểm tra trạng thái tắt. */
    private function surveyReport(): array
    {
        return array_values(array_filter($this->registry()->report(), fn (array $row) => $row['key'] === 'survey'));
    }

    public function test_features_tab_partial_renders_the_registry_report(): void
    {
        $html = view('cmsbackend::screens.pages.developer-settings.partials.features', [
            'features' => $this->surveyReport(), 'featureErrors' => ['Trùng mã chức năng "x"'],
        ])->render();
        foreach (['Khảo sát', 'survey', 'ri-survey-line', '/cms/khao-sat', '/khao-sat', 'Hợp lệ', '10 đã khai báo', 'dev-feature-switch', 'Trùng mã chức năng'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->registry()->setEnabled('survey', false);
        $off = view('cmsbackend::screens.pages.developer-settings.partials.features', ['features' => $this->surveyReport(), 'featureErrors' => []])->render();
        $this->assertStringContainsString('Đang tắt', $off);
        $this->assertDoesNotMatchRegularExpression('/dev-feature-switch"[^>]*checked/', $off);
    }
}
