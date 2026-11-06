<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Cụm ghép Sự kiện + Chuyên mục nổi bật (tab) + Gương tiêu biểu. Sự kiện bật "Hiển thị trang chủ" trong danh sách sự kiện. */
final class ActivitiesBlock extends AbstractBlockDefinition
{
    protected string $key = 'activities';

    protected string $icon = 'ri-calendar-event-line';

    protected ?string $route = 'cms.dashboard.su-kien.index';


    protected array $binding = ['featured_categories' => '@featured'];

    public function __construct()
    {
        $this->schema = [
            'featured_categories' => ['type' => 'categories', 'label' => 'homepage.fields.featured_categories', 'default' => [], 'needed' => true],
            'events_link' => self::link('cms.dashboard.su-kien.index', 'homepage.fields.events_link'),
            'examples_link' => self::link('cms.dashboard.guong-tieu-bieu.index', 'homepage.fields.examples_link'),
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        return ['homeCategoryTabs' => app(\Modules\CmsFrontend\Services\HomeBlocks::class)->categoryTabs(array_values(array_map('intval', (array) ($settings['featured_categories'] ?? []))))];
    }
}
