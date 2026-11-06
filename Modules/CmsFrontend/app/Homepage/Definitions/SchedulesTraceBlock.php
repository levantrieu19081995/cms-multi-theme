<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsFrontend\Services\HomeBlocks;

/** Lịch làm việc (hai chuyên mục) và hai cổng truy xuất nguồn gốc. */
final class SchedulesTraceBlock extends AbstractBlockDefinition
{
    protected string $key = 'schedules-trace';

    protected string $icon = 'ri-calendar-schedule-line';


    protected array $binding = [
        'schedule_department' => 'cms_home_category_lich_so', 'schedule_board' => 'cms_home_category_lich_bgd',
        'trace_market_url' => 'cms_trace_market_url', 'trace_market_image' => 'cms_trace_market_image',
        'trace_portal_url' => 'cms_trace_portal_url', 'trace_portal_image' => 'cms_trace_portal_image',
    ];

    public function __construct()
    {
        $this->schema = [
            'schedule_department' => ['type' => 'category', 'label' => 'homepage.fields.schedule_department', 'needed' => true],
            'schedule_department_title' => ['type' => 'text', 'label' => 'homepage.fields.schedule_department_title', 'default' => HomeBlocks::SCHEDULE_TITLES[HomeSettings::SCHEDULE_DEPARTMENT]],
            'schedule_board' => ['type' => 'category', 'label' => 'homepage.fields.schedule_board', 'needed' => true],
            'schedule_board_title' => ['type' => 'text', 'label' => 'homepage.fields.schedule_board_title', 'default' => HomeBlocks::SCHEDULE_TITLES[HomeSettings::SCHEDULE_BOARD]],
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
            return ['homeSchedules' => [], 'homeTraceLinks' => []];
        }
        $legacyKeys = [
            'schedule_department' => \Modules\CmsBackend\Support\HomeSettings::SCHEDULE_DEPARTMENT, 'schedule_board' => \Modules\CmsBackend\Support\HomeSettings::SCHEDULE_BOARD,
            'trace_market_url' => \Modules\CmsBackend\Support\HomeSettings::TRACE_MARKET_URL, 'trace_market_image' => \Modules\CmsBackend\Support\HomeSettings::TRACE_MARKET_IMAGE,
            'trace_portal_url' => \Modules\CmsBackend\Support\HomeSettings::TRACE_PORTAL_URL, 'trace_portal_image' => \Modules\CmsBackend\Support\HomeSettings::TRACE_PORTAL_IMAGE,
        ];
        $values = [];
        foreach ($legacyKeys as $field => $key) {
            $values[$key] = (string) ($settings[$field] ?? '');
        }
        $blocks = app(\Modules\CmsFrontend\Services\HomeBlocks::class);

        $titles = [
            \Modules\CmsBackend\Support\HomeSettings::SCHEDULE_DEPARTMENT => $settings['schedule_department_title'] ?? null,
            \Modules\CmsBackend\Support\HomeSettings::SCHEDULE_BOARD => $settings['schedule_board_title'] ?? null,
        ];
        $texts = [
            'market' => ['title' => $settings['trace_market_title'] ?? null, 'caption' => $settings['trace_market_caption'] ?? null],
            'portal' => ['title' => $settings['trace_portal_title'] ?? null, 'caption' => $settings['trace_portal_caption'] ?? null],
        ];

        return ['homeSchedules' => $blocks->schedules($values, $titles), 'homeTraceLinks' => $blocks->traceLinks($values, $texts)];
    }
}
