<?php

declare(strict_types=1);

namespace Tests\Unit\Homepage;

use Modules\CmsFrontend\Homepage\EffectCatalog;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Modules\CmsFrontend\Homepage\HomepageDataNeeds;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;
use Modules\CmsFrontend\Homepage\HomepageSettingsService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Cụm tách từ cụm ghép cũ (`split_from`) của theme Khuyến nông (`default`): bố cục và cấu hình đã cài trên server phải giữ nguyên sau deploy,
 * mỗi nội dung bật/tắt được riêng. Không cần database.
 */
final class HomepageSplitBlocksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        HomepageLayoutService::fakeReady(false);
        EffectCatalog::fakeManifest(null);
    }

    protected function tearDown(): void
    {
        HomepageLayoutService::fakeReady(null);
        EffectCatalog::fakeManifest(false);
        parent::tearDown();
    }

    private function service(): HomepageLayoutService
    {
        $registry = new HomepageBlockRegistry;

        return new HomepageLayoutService($registry, new HomepageSettingsService($registry));
    }

    /** @param list<array<string, mixed>> $rows */
    private function withSplitBlocks(array $rows): array
    {
        $method = new ReflectionMethod(HomepageLayoutService::class, 'withSplitBlocks');

        return $method->invoke($this->service(), 'default', $rows);
    }

    private static function row(string $instance, int $sort, bool $enabled = true, string $zone = 'main', ?string $effect = null): array
    {
        return ['instance' => $instance, 'zone' => $zone, 'variant' => 'standard', 'effect' => $effect, 'enabled' => $enabled, 'sort' => $sort];
    }

    public function test_every_former_combined_block_of_the_default_theme_is_split_into_blocks_of_its_own(): void
    {
        $blocks = (new HomepageBlockRegistry)->layout('default')->blocks();

        $this->assertSame([
            'documents' => 'information', 'trace' => 'schedules-trace',
            'events' => 'activities', 'examples' => 'activities', 'services' => 'activities',
            'gallery' => 'media', 'videos' => 'media', 'partners' => 'partners-support', 'support' => 'partners-support',
        ], array_filter(array_map(fn (array $block) => $block['split_from'] ?? null, $blocks)));
        $this->assertArrayNotHasKey('media', $blocks, 'cụm ghép ảnh + video không còn trong theme default');
        $this->assertArrayNotHasKey('partners-support', $blocks, 'cụm ghép đối tác + hỗ trợ không còn trong theme default');
    }

    public function test_an_installed_layout_of_the_combined_blocks_keeps_its_place_and_state_after_the_split(): void
    {
        $rows = $this->withSplitBlocks([
            self::row('news', 10), self::row('information', 20), self::row('schedules-trace', 30, false),
            self::row('activities', 40, true, 'main', 'stagger'), self::row('media', 50, false), self::row('partners-support', 60),
        ]);
        $byInstance = array_column($rows, null, 'instance');

        $this->assertSame([
            'news', 'information', 'documents', 'schedules-trace', 'trace', 'activities', 'events', 'examples', 'services',
            'gallery', 'videos', 'partners', 'support',
        ], array_column($rows, 'instance'), 'cụm tách đứng ngay sau cụm cũ; cụm ghép theme không còn khai báo được thay bằng các cụm tách');
        $this->assertTrue($byInstance['documents']['enabled']);
        $this->assertFalse($byInstance['trace']['enabled'], 'Lịch làm việc đang tắt thì Truy xuất tách ra cũng tắt như trước');
        $this->assertTrue($byInstance['services']['enabled']);
        $this->assertSame('stagger', $byInstance['events']['effect'], 'giữ hiệu ứng đã chọn của cụm cũ');
        $this->assertSame([41, 42, 43], [$byInstance['events']['sort'], $byInstance['examples']['sort'], $byInstance['services']['sort']]);
        $this->assertFalse($byInstance['gallery']['enabled']);
        $this->assertFalse($byInstance['videos']['enabled']);
        $this->assertSame([50, 51], [$byInstance['gallery']['sort'], $byInstance['videos']['sort']]);
        $this->assertTrue($byInstance['partners']['enabled'] && $byInstance['support']['enabled']);
    }

    public function test_a_split_block_already_saved_in_the_layout_is_not_added_again_and_stale_combined_rows_are_dropped(): void
    {
        $rows = $this->withSplitBlocks([
            self::row('activities', 40), self::row('services', 45, false), self::row('media', 50), self::row('gallery', 52), self::row('videos', 53, false),
        ]);

        $this->assertSame(['activities', 'events', 'examples', 'services', 'gallery', 'videos'], array_column($rows, 'instance'));
        $this->assertFalse(array_column($rows, null, 'instance')['services']['enabled'], 'dòng đã lưu của cụm tách thắng dòng suy ra từ cụm cũ');
    }

    public function test_a_layout_without_the_combined_blocks_is_left_untouched(): void
    {
        $rows = [self::row('news', 10), self::row('information', 20)];
        $rows[] = self::row('documents', 30, false);

        $this->assertSame($rows, $this->withSplitBlocks($rows));
        $this->assertSame([], $this->withSplitBlocks([]));
    }

    public function test_a_split_block_reads_the_saved_settings_of_its_former_block_until_it_has_its_own(): void
    {
        $settings = new HomepageSettingsService(new HomepageBlockRegistry);
        request()->attributes->set('_homepage_settings_default', [
            'information' => ['notice_category' => 5, 'document_filter' => 'lvb:3'],
            'schedules-trace' => ['schedule_department' => 7, 'trace_market_url' => 'https://cho.example.vn', 'trace_portal_title' => 'Cổng truy xuất'],
            'activities' => ['featured_categories' => [3, 4]],
        ]);

        $this->assertSame('lvb:3', $settings->read('default', 'documents')['document_filter']);
        $this->assertSame('https://cho.example.vn', $settings->read('default', 'trace')['trace_market_url']);
        $this->assertSame('Cổng truy xuất', $settings->read('default', 'trace')['trace_portal_title']);
        $this->assertArrayNotHasKey('notice_category', $settings->read('default', 'documents'), 'chỉ lấy field của chính cụm');
        $this->assertSame([3, 4], $settings->read('default', 'activities')['featured_categories']);

        request()->attributes->set('_homepage_settings_default', [
            'schedules-trace' => ['trace_market_url' => 'https://cu.example.vn'],
            'trace' => ['trace_market_url' => 'https://moi.example.vn'],
        ]);
        $this->assertSame('https://moi.example.vn', $settings->read('default', 'trace')['trace_market_url'], 'bản lưu riêng của cụm tách thắng');
    }

    public function test_each_split_block_loads_only_its_own_homepage_data(): void
    {
        $plan = fn (string ...$definitions) => ['zones' => ['main' => array_map(fn (string $definition) => ['definition' => $definition], $definitions)]];

        $this->assertSame(['services'], array_keys(HomepageDataNeeds::forPlan($plan('services'))));
        $this->assertSame(['tabs'], array_keys(HomepageDataNeeds::forPlan($plan('activities'))), 'tắt Sự kiện/Gương tiêu biểu/Dịch vụ thì không truy vấn dữ liệu của chúng');
        $this->assertSame(['notices'], array_keys(HomepageDataNeeds::forPlan($plan('information'))));
        $this->assertEqualsCanonicalizing(['library', 'links'], array_keys(HomepageDataNeeds::forPlan($plan('gallery', 'partners', 'support'))));
    }
}
