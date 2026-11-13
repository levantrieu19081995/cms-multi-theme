<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsFrontend\Services\HomeBlocks;

/** Hai cổng truy xuất nguồn gốc (chợ nông sản, cổng truy xuất): tiêu đề, chú thích, đường dẫn và ảnh của từng ô. */
final class TraceBlock extends AbstractBlockDefinition
{
    protected string $key = 'trace';

    protected string $icon = 'ri-qr-scan-2-line';

    protected array $binding = [
        'trace_market_url' => 'cms_trace_market_url', 'trace_market_image' => 'cms_trace_market_image',
        'trace_portal_url' => 'cms_trace_portal_url', 'trace_portal_image' => 'cms_trace_portal_image',
    ];

    public function __construct()
    {
        $this->schema = [
            'trace_market_title' => ['type' => 'text', 'label' => 'homepage.fields.trace_market_title', 'default' => HomeBlocks::TRACE_TEXTS['market']['title']],
            'trace_market_caption' => ['type' => 'text', 'label' => 'homepage.fields.trace_market_caption', 'default' => HomeBlocks::TRACE_TEXTS['market']['caption']],
            'trace_market_url' => ['type' => 'text', 'format' => 'url', 'label' => 'homepage.fields.trace_market_url'],
            'trace_market_image' => ['type' => 'text', 'format' => 'image_path', 'label' => 'homepage.fields.trace_market_image'],
            'trace_portal_title' => ['type' => 'text', 'label' => 'homepage.fields.trace_portal_title', 'default' => HomeBlocks::TRACE_TEXTS['portal']['title']],
            'trace_portal_caption' => ['type' => 'text', 'label' => 'homepage.fields.trace_portal_caption', 'default' => HomeBlocks::TRACE_TEXTS['portal']['caption']],
            'trace_portal_url' => ['type' => 'text', 'format' => 'url', 'label' => 'homepage.fields.trace_portal_url'],
            'trace_portal_image' => ['type' => 'text', 'format' => 'image_path', 'label' => 'homepage.fields.trace_portal_image'],
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        if (self::translatedPage()) {
            return ['homeTraceLinks' => []];
        }
        $values = [
            HomeSettings::TRACE_MARKET_URL => (string) ($settings['trace_market_url'] ?? ''), HomeSettings::TRACE_MARKET_IMAGE => (string) ($settings['trace_market_image'] ?? ''),
            HomeSettings::TRACE_PORTAL_URL => (string) ($settings['trace_portal_url'] ?? ''), HomeSettings::TRACE_PORTAL_IMAGE => (string) ($settings['trace_portal_image'] ?? ''),
        ];
        $texts = [
            'market' => ['title' => $settings['trace_market_title'] ?? null, 'caption' => $settings['trace_market_caption'] ?? null],
            'portal' => ['title' => $settings['trace_portal_title'] ?? null, 'caption' => $settings['trace_portal_caption'] ?? null],
        ];

        return ['homeTraceLinks' => app(HomeBlocks::class)->traceLinks($values, $texts)];
    }
}
