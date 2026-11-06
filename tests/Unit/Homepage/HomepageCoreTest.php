<?php

declare(strict_types=1);

namespace Tests\Unit\Homepage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\CmsFrontend\Homepage\Definitions\AbstractBlockDefinition;
use Modules\CmsFrontend\Homepage\Definitions\SchedulesTraceBlock;
use Modules\CmsFrontend\Homepage\EffectCatalog;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Modules\CmsFrontend\Homepage\HomepageDataResolver;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;
use Modules\CmsFrontend\Homepage\HomepageSettingsService;
use Modules\CmsFrontend\Homepage\Themes\AbstractThemeLayout;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Kiểm tra lõi Homepage Layout Manager KHÔNG cần database: Registry, EffectCatalog, validation bố cục, kế hoạch render,
 * ownership/hết hạn của preview, resolver theo theme. (Test cần bảng thật nằm ở tests/Feature/Homepage.)
 */
final class HomepageCoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        HomepageLayoutService::fakeReady(false); // không chạm database
        EffectCatalog::fakeManifest(null);
    }

    protected function tearDown(): void
    {
        HomepageLayoutService::fakeReady(null);
        EffectCatalog::fakeManifest(false);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function registry(): HomepageBlockRegistry
    {
        return new HomepageBlockRegistry;
    }

    private function service(?HomepageBlockRegistry $registry = null): HomepageLayoutService
    {
        $registry ??= $this->registry();

        return new HomepageLayoutService($registry, new HomepageSettingsService($registry));
    }

    /** Registry mà theme `default` được thay bằng Theme Layout giả với các cụm tùy ý (renderer vẫn lấy từ thư mục thật của theme default). */
    private function stubRegistry(array $blocks, array $definitions = []): HomepageBlockRegistry
    {
        $registry = $this->registry();
        $layout = new class($blocks) extends AbstractThemeLayout
        {
            public function __construct(private array $declared) {}

            public function themeKey(): string
            {
                return 'default';
            }

            public function zones(): array
            {
                return ['main' => 'homepage.zones.main', 'after-main' => 'homepage.zones.after_main'];
            }

            public function blocks(): array
            {
                return $this->declared;
            }

            public function defaultLayout(): array
            {
                return [];
            }
        };
        $layouts = new ReflectionProperty($registry, 'layouts');
        $layouts->setValue($registry, ['default' => $layout]);
        if ($definitions !== []) {
            $property = new ReflectionProperty($registry, 'definitions');
            $property->setValue($registry, $registry->definitions() + $definitions);
        }

        return $registry;
    }

    /** Chặn truy vấn settings (đã "lưu" rỗng cho mọi instance) để plan() không chạm database. */
    private function presetSettings(string $theme, array $perInstance = []): void
    {
        $registry = $this->registry();
        $all = array_fill_keys(array_keys($registry->layout($theme)?->blocks() ?? []), []);
        request()->attributes->set('_homepage_settings_'.$theme, $perInstance + $all);
    }

    public function test_registry_is_consistent(): void
    {
        $registry = $this->registry();
        $this->assertSame([], $registry->selfCheck());
        $this->assertCount(16, $registry->definitions());
        $this->assertSame(['default', 'nam-binh-duong', 'ban-de-an'], array_keys($registry->layouts()));
    }

    public function test_every_declared_renderer_and_effect_is_available_for_its_theme(): void
    {
        $registry = $this->registry();
        foreach ($registry->layouts() as $theme => $layout) {
            foreach ($layout->blocks() as $instance => $declaration) {
                foreach ($declaration['variants'] as $variant => $info) {
                    $this->assertTrue($registry->rendererExists($theme, $info['renderer']), "$theme/$instance/$variant renderer {$info['renderer']}");
                    $this->assertSame([], $registry->effectProblems($theme, $instance, $variant), "$theme/$instance/$variant effect");
                    $this->assertIsArray($info['effects'] ?? [], 'effects là danh sách KEY preset, không phải chuỗi tự do');
                }
            }
        }
    }

    public function test_default_layout_plan_follows_declared_order_for_every_theme(): void
    {
        $registry = $this->registry();
        $service = $this->service($registry);
        foreach ($registry->layouts() as $theme => $layout) {
            $this->presetSettings($theme);
            $plan = $service->plan($theme, $service->defaultRows($theme));
            $this->assertSame([], $plan['issues'], $theme);
            $expected = [];
            foreach ($layout->defaultLayout() as $row) {
                $expected[$row['zone']][] = $row['instance'];
            }
            $actual = array_filter(array_map(fn ($blocks) => array_column($blocks, 'instance'), $plan['zones']));
            $this->assertSame($expected, $actual, "$theme: thứ tự và zone mặc định phải bằng bố cục khai báo");
        }
    }

    public function test_theme_layouts_are_independent(): void
    {
        $source = file_get_contents(base_path('Modules/CmsFrontend/app/Homepage/Themes/BanDeAnHomepageLayout.php'));
        $this->assertStringNotContainsString('NamBinhDuong', $source);
    }

    public function test_layout_validation_rejects_bad_zone_variant_duplicate_and_unknown(): void
    {
        $service = $this->service();
        $row = ['instance' => 'news', 'zone' => 'main', 'variant' => 'standard', 'enabled' => true];
        $codes = fn (array $rows) => array_column($service->validate('default', $rows)['errors'], 'code');

        $this->assertSame([], $codes([$row]));
        $this->assertSame(['invalid_zone'], $codes([['zone' => 'hero'] + $row]), 'news không được đặt vào zone hero');
        $this->assertSame(['invalid_zone'], $codes([['zone' => 'nope'] + $row]));
        $this->assertSame(['invalid_variant'], $codes([['variant' => 'fancy'] + $row]));
        $this->assertSame(['duplicate'], $codes([$row, $row]));
        $this->assertSame(['unknown_instance'], $codes([['instance' => 'not-a-block'] + $row]));
        $this->assertSame(['theme_unknown'], array_column($service->validate('ghost', [$row])['errors'], 'code'));
    }

    public function test_blocks_missing_from_the_payload_keep_their_default_and_are_not_deleted(): void
    {
        $result = $this->service()->validate('default', [['instance' => 'news', 'zone' => 'main', 'variant' => 'standard', 'enabled' => false]]);
        $this->assertSame([], $result['errors']);
        $instances = array_column($result['rows'], 'instance');
        $this->assertEqualsCanonicalizing(array_keys($this->registry()->layout('default')->blocks()), $instances);
    }

    public function test_enabling_a_block_without_renderer_or_effect_is_blocked_but_disabling_is_allowed(): void
    {
        $registry = $this->stubRegistry([
            'no-renderer' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'does-not-exist', 'effects' => []]]],
            'bad-effect' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'hero-banner', 'effects' => ['gallery-orbit']]]],
            'unknown-effect' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'hero-banner', 'effects' => ['made-up']]]],
        ]);
        EffectCatalog::fakeManifest([]); // build không chứa entry của gallery-orbit
        $service = $this->service($registry);
        $row = fn (string $instance, bool $enabled) => ['instance' => $instance, 'zone' => 'main', 'variant' => 'standard', 'enabled' => $enabled];

        $errors = $service->validate('default', [$row('no-renderer', true), $row('bad-effect', true), $row('unknown-effect', true)])['errors'];
        $this->assertSame(['missing_renderer', 'missing_effect', 'missing_effect'], array_column($errors, 'code'));
        $this->assertSame([], $service->validate('default', [$row('no-renderer', false), $row('bad-effect', false)])['errors']);
    }

    public function test_plan_excludes_blocks_that_cannot_render_and_reports_why(): void
    {
        $registry = $this->stubRegistry([
            'ok' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'hero-banner', 'effects' => []]]],
            'no-renderer' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'does-not-exist', 'effects' => []]]],
            'bad-effect' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'hero-banner', 'effects' => ['gallery-orbit']]]],
            'off' => ['definition' => 'hero', 'zones' => ['main'], 'default_variant' => 'standard', 'variants' => ['standard' => ['renderer' => 'hero-banner', 'effects' => []]]],
        ]);
        EffectCatalog::fakeManifest([]);
        request()->attributes->set('_homepage_settings_default', ['ok' => [], 'no-renderer' => [], 'bad-effect' => [], 'off' => []]);
        $rows = array_map(fn ($i, $n) => ['instance' => $n, 'zone' => 'main', 'variant' => 'standard', 'enabled' => $n !== 'off', 'sort' => $i * 10], range(1, 4), ['ok', 'no-renderer', 'bad-effect', 'off']);
        $plan = $this->service($registry)->plan('default', $rows);

        $this->assertSame(['ok'], array_column($plan['zones']['main'], 'instance'), 'cụm tắt/lỗi không có trong DOM');
        $this->assertEqualsCanonicalizing(['missing_renderer', 'missing_effect'], array_column($plan['issues'], 'code'));
    }

    public function test_effect_catalog_is_shared_by_every_theme_and_checks_files_and_build_manifest(): void
    {
        $this->assertSame([], EffectCatalog::problems(['reveal', 'stagger', 'count-up', 'gallery-orbit', 'crossfade-slider']), 'effect thuộc khung: theme nào cũng dùng được');
        $this->assertSame(['unknown:made-up'], EffectCatalog::problems(['made-up']));

        $entries = [
            'Modules/CmsFrontend/resources/assets/effects/auto/gallery-orbit.js' => ['file' => 'a.js'],
            'Modules/CmsFrontend/resources/assets/effects/gallery-orbit/style.css' => ['file' => 'a.css'],
        ];
        EffectCatalog::fakeManifest([]); // build không chứa entry nào
        $this->assertSame([
            'build:Modules/CmsFrontend/resources/assets/effects/auto/gallery-orbit.js',
            'build:Modules/CmsFrontend/resources/assets/effects/gallery-orbit/style.css',
        ], EffectCatalog::problems(['gallery-orbit']));
        EffectCatalog::fakeManifest($entries);
        $this->assertSame([], EffectCatalog::problems(['gallery-orbit']));
    }

    public function test_page_loads_only_the_effect_entries_the_enabled_blocks_declare(): void
    {
        $plan = ['zones' => ['main' => [['effects' => ['stagger', 'count-up']], ['effects' => []]], 'hero' => [['effects' => ['crossfade-slider']]]]];
        $this->assertEqualsCanonicalizing(['reveal', 'crossfade-slider'], EffectCatalog::entriesForPlan($plan));
        $this->assertSame([], EffectCatalog::entriesForPlan(['zones' => ['main' => [['effects' => []]]]]));
    }

    public function test_homepage_always_has_a_layout_and_falls_back_to_the_code_default_when_nothing_is_installed(): void
    {
        $service = $this->service();
        $this->presetSettings('default');
        $plan = $service->live('default');

        $this->assertNotNull($plan);
        $this->assertSame(['notice-ticker'], array_column($plan['zones']['before-main'], 'instance'));
        $this->assertSame(['news', 'information', 'schedules-trace', 'activities', 'media', 'partners-support'], array_column($plan['zones']['main'], 'instance'));
        $this->assertNull($service->live('not-a-theme'));
    }

    public function test_apply_is_refused_when_tables_are_not_ready_and_preview_never_changes_live(): void
    {
        $service = $this->service();
        $rows = $service->defaultRows('default');
        $token = $service->createSnapshot('default', $rows, 'session-a');
        $this->assertNotNull($service->snapshot($token, 'session-a'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not_ready');
        $service->apply('default', $rows);
    }

    public function test_preview_snapshot_belongs_to_one_session_and_expires(): void
    {
        $service = $this->service();
        $rows = $service->defaultRows('default');
        $token = $service->createSnapshot('default', $rows, 'session-a');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $token);
        $this->assertNotNull($service->snapshot($token, 'session-a'));
        $this->assertNull($service->snapshot($token, 'session-b'), 'phiên khác không xem được');
        $this->assertNull($service->snapshot('not-a-token', 'session-a'));
        $this->assertNull($service->snapshot(str_repeat('a', 40), 'session-a'));

        Carbon::setTestNow(now()->addMinutes(31));
        $this->assertNull($service->snapshot($token, 'session-a'), 'hết hạn sau 30 phút');
    }

    public function test_forgotten_snapshot_cannot_be_used_again(): void
    {
        $service = $this->service();
        $token = $service->createSnapshot('default', $service->defaultRows('default'), 'session-a');
        $service->forgetSnapshot($token);
        $this->assertNull($service->snapshot($token, 'session-a'));
    }

    public function test_settings_of_a_theme_are_read_from_that_theme_only_without_touching_legacy_options(): void
    {
        $registry = $this->registry();
        $settings = new HomepageSettingsService($registry);
        request()->attributes->set('_homepage_settings_default', ['information' => ['notice_category' => 5, 'document_filter' => 'lvb:1']]);
        request()->attributes->set('_homepage_settings_ban-de-an', ['documents' => ['document_filter' => 'nvb:2']]);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->assertSame(5, $settings->read('default', 'information')['notice_category']);
        $this->assertSame('nvb:2', $settings->read('ban-de-an', 'documents')['document_filter']);
        $this->assertSame([], $settings->read('ban-de-an', 'not-here'));
        $this->assertSame(0, $queries, 'đã có bản lưu của theme thì không đọc option cũ toàn cục');
    }

    public function test_settings_validation_enforces_schema(): void
    {
        $settings = new HomepageSettingsService($this->registry());
        $definition = new SchedulesTraceBlock;

        $bad = $settings->validate($definition, ['trace_market_url' => 'javascript:alert(1)', 'trace_portal_image' => 'ftp://x']);
        $this->assertSame(['trace_market_url' => 'url_invalid', 'trace_portal_image' => 'url_invalid'], $bad['errors']);

        $good = $settings->validate($definition, ['trace_market_url' => 'https://example.com/a', 'trace_market_image' => '/storage/a.png', 'unknown_field' => 'x']);
        $this->assertSame([], $good['errors']);
        $this->assertArrayNotHasKey('unknown_field', $good['values'], 'chỉ nhận field có trong schema');
    }

    public function test_resolver_applies_blocks_in_order_batches_prefetch_and_passes_theme(): void
    {
        $calls = new \ArrayObject;
        $stub = new class($calls) extends AbstractBlockDefinition
        {
            protected string $key = 'stub-block';

            public function __construct(private \ArrayObject $calls) {}

            public function prefetch(array $allSettings): void
            {
                $this->calls['prefetch'][] = count($allSettings);
            }

            public function resolve(array $settings, array $data): array
            {
                $this->calls['resolve'][] = [$data['_theme'], $data['_instance']];

                return ['picked' => $settings['value'], 'trail' => array_merge($data['trail'] ?? [], [$data['_instance']])];
            }
        };
        $resolver = new HomepageDataResolver($this->stubRegistry([], ['stub-block' => $stub]));
        $block = fn (string $instance, string $value) => ['instance' => $instance, 'definition' => 'stub-block', 'settings' => ['value' => $value]];
        $plan = ['theme' => 'stub', 'zones' => ['main' => [$block('a', '1'), $block('b', '2')], 'after-main' => [$block('c', '3')]]];

        $data = $resolver->mergeAll($plan, ['keep' => true]);

        $this->assertSame([3], $calls['prefetch'], 'prefetch gọi MỘT lần cho cả ba instance');
        $this->assertSame([['stub', 'a'], ['stub', 'b'], ['stub', 'c']], $calls['resolve']);
        $this->assertSame('3', $data['picked'], 'cụm sau thay thế cụm trước theo thứ tự hiển thị');
        $this->assertSame(['a', 'b', 'c'], $data['trail']);
        $this->assertTrue($data['keep']);
        $this->assertArrayNotHasKey('_theme', $data);
        $this->assertArrayNotHasKey('_instance', $data);
    }

    public function test_trace_tile_texts_are_configurable_and_fall_back_to_the_original_text(): void
    {
        $blocks = app(\Modules\CmsFrontend\Services\HomeBlocks::class);
        $settings = [
            \Modules\CmsBackend\Support\HomeSettings::TRACE_MARKET_URL => 'https://example.com/m', \Modules\CmsBackend\Support\HomeSettings::TRACE_MARKET_IMAGE => '',
            \Modules\CmsBackend\Support\HomeSettings::TRACE_PORTAL_URL => 'https://example.com/p', \Modules\CmsBackend\Support\HomeSettings::TRACE_PORTAL_IMAGE => '',
        ];

        $default = $blocks->traceLinks($settings);
        $this->assertSame(\Modules\CmsFrontend\Services\HomeBlocks::TRACE_TEXTS['market']['title'], $default[0]['title'], 'không cấu hình thì giữ chữ cũ');

        $custom = $blocks->traceLinks($settings, ['market' => ['title' => 'Tiêu đề mới', 'caption' => ''], 'portal' => ['title' => null, 'caption' => 'Mô tả mới']]);
        $this->assertSame('Tiêu đề mới', $custom[0]['title']);
        $this->assertSame(\Modules\CmsFrontend\Services\HomeBlocks::TRACE_TEXTS['market']['caption'], $custom[0]['caption'], 'rỗng thì dùng chữ mặc định');
        $this->assertSame('Mô tả mới', $custom[1]['caption']);
    }

    public function test_preview_shows_a_notice_for_blocks_missing_a_required_source_but_not_for_complete_ones(): void
    {
        $renderer = app(\Modules\CmsFrontend\Homepage\HomepageRenderer::class);
        $notice = new \ReflectionMethod($renderer, 'missingSettingsNotice');
        $block = fn (string $definition, array $settings) => ['instance' => $definition, 'definition' => $definition, 'settings' => $settings];

        $this->assertStringContainsString('role="note"', $notice->invoke($renderer, $block('information', ['notice_category' => null])));
        $this->assertSame('', $notice->invoke($renderer, $block('information', ['notice_category' => 7])));
        $this->assertSame('', $notice->invoke($renderer, $block('news', [])), 'cụm không có field bắt buộc không báo');
    }

    public function test_effect_choice_edits_only_the_effect_attributes_of_the_block_html(): void
    {
        $html = '<section class="news" data-sbut-group="news"><div class="wrap" data-stagger-group=":scope > .s" data-stagger-offset="2"><h2 class="t" data-reveal-lines>Tin</h2><p>x</p></div></section>';
        $apply = fn (?string $choice) => \Modules\CmsFrontend\Homepage\BlockEffectApplier::apply($html, $choice);

        $this->assertSame($html, $apply(null), 'chưa chọn: giữ nguyên HTML hiện tại của theme');
        $this->assertSame($html, $apply(''));

        $none = $apply('none');
        $this->assertStringNotContainsString('data-reveal-lines', $none);
        $this->assertStringNotContainsString('data-stagger', $none);
        $this->assertStringContainsString('class="news" data-sbut-group="news"', $none, 'class và hook khác không bị đụng');
        $this->assertStringContainsString('<h2 class="t">Tin</h2>', $none);

        $reveal = $apply('reveal');
        $this->assertSame(1, substr_count($reveal, 'data-reveal-lines'));
        $this->assertStringContainsString('<h2 data-reveal-lines class="t">', $reveal);
        $this->assertStringNotContainsString('data-stagger', $reveal);

        $stagger = $apply('stagger');
        $this->assertStringStartsWith('<section data-stagger-group=":scope > *" class="news"', $stagger);
        $this->assertSame(1, substr_count($stagger, 'data-stagger-group'));
        $this->assertStringNotContainsString('data-reveal-lines', $stagger);

        $plain = '<div class="cms-ticker"><a>x</a></div>';
        $this->assertSame($plain, \Modules\CmsFrontend\Homepage\BlockEffectApplier::apply($plain, 'reveal'), 'không có tiêu đề thì không thêm gì');
        $this->assertSame('', \Modules\CmsFrontend\Homepage\BlockEffectApplier::apply('', 'stagger'));
    }

    public function test_layout_validation_accepts_only_allow_listed_effect_choices_and_keeps_the_stored_one(): void
    {
        $service = $this->service();
        $row = ['instance' => 'news', 'zone' => 'main', 'variant' => 'standard', 'enabled' => true];
        $result = fn (array $extra) => $service->validate('default', [$row + $extra]);
        $effectOf = fn (array $rows) => collect($rows)->firstWhere('instance', 'news')['effect'];

        $this->assertNull($effectOf($result([])['rows']), 'không gửi: giữ mặc định');
        $this->assertNull($effectOf($result(['effect' => ''])['rows']));
        $this->assertNull($effectOf($result(['effect' => 'default'])['rows']));
        foreach (['none', 'reveal', 'stagger'] as $choice) {
            $this->assertSame($choice, $effectOf($result(['effect' => $choice])['rows']));
            $this->assertSame([], $result(['effect' => $choice])['errors']);
        }
        foreach (['gallery-orbit', '<script>', 'crossfade-slider'] as $bad) {
            $this->assertSame(['invalid_effect'], array_column($result(['effect' => $bad])['errors'], 'code'), $bad);
        }
    }

    public function test_chosen_effect_decides_which_effect_assets_the_page_loads(): void
    {
        $registry = $this->registry();
        $service = $this->service($registry);
        $this->presetSettings('default');
        $rows = fn (string $instance, ?string $effect) => array_map(fn ($row) => ['effect' => $row['instance'] === $instance ? $effect : null] + $row, $service->defaultRows('default'));
        $entries = fn (string $instance, ?string $effect) => EffectCatalog::entriesForPlan($service->plan('default', $rows($instance, $effect)));

        $this->assertSame(['crossfade-slider'], $entries('news', null), 'mặc định Khuyến nông: chỉ slider banner');
        $this->assertSame(['crossfade-slider'], $entries('news', 'none'));
        $this->assertSame(['crossfade-slider'], $entries('hero', 'none'), 'hiệu ứng cấu trúc của banner không bị gỡ bằng lựa chọn');
        $this->assertEqualsCanonicalizing(['crossfade-slider', 'reveal'], $entries('news', 'reveal'));
        $this->assertSame('reveal', $service->plan('default', $rows('news', 'reveal'))['zones']['main'][0]['effect']);
    }
}
