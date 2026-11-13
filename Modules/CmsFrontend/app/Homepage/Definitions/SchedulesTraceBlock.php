<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsFrontend\Services\HomeBlocks;

/**
 * Lịch làm việc (hai chuyên mục). Mã `schedules-trace` giữ từ khi cụm còn ghép hai cổng truy xuất nguồn gốc; nay là cụm `trace`
 * tách từ cụm này (đường dẫn/ảnh truy xuất đã lưu ở đây vẫn được cụm trace đọc cho tới khi lưu riêng).
 */
final class SchedulesTraceBlock extends AbstractBlockDefinition
{
    protected string $key = 'schedules-trace';

    protected string $icon = 'ri-calendar-schedule-line';

    protected array $binding = ['schedule_department' => 'cms_home_category_lich_so', 'schedule_board' => 'cms_home_category_lich_bgd'];

    public function __construct()
    {
        $this->schema = [
            'schedule_department' => ['type' => 'category', 'label' => 'homepage.fields.schedule_department', 'needed' => true],
            'schedule_department_title' => ['type' => 'text', 'label' => 'homepage.fields.schedule_department_title', 'default' => HomeBlocks::SCHEDULE_TITLES[HomeSettings::SCHEDULE_DEPARTMENT]],
            'schedule_board' => ['type' => 'category', 'label' => 'homepage.fields.schedule_board', 'needed' => true],
            'schedule_board_title' => ['type' => 'text', 'label' => 'homepage.fields.schedule_board_title', 'default' => HomeBlocks::SCHEDULE_TITLES[HomeSettings::SCHEDULE_BOARD]],
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        if (self::translatedPage()) {
            return ['homeSchedules' => []];
        }
        $values = [
            HomeSettings::SCHEDULE_DEPARTMENT => (string) ($settings['schedule_department'] ?? ''),
            HomeSettings::SCHEDULE_BOARD => (string) ($settings['schedule_board'] ?? ''),
        ];
        $titles = [
            HomeSettings::SCHEDULE_DEPARTMENT => $settings['schedule_department_title'] ?? null,
            HomeSettings::SCHEDULE_BOARD => $settings['schedule_board_title'] ?? null,
        ];

        return ['homeSchedules' => app(HomeBlocks::class)->schedules($values, $titles)];
    }
}
