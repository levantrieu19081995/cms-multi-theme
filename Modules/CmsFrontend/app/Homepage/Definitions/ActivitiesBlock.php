<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsFrontend\Services\HomeBlocks;

/**
 * Chuyên mục nổi bật: mỗi chuyên mục chọn ở Cài đặt trang chủ là một hàng bài. Mã `activities` giữ từ khi cụm còn ghép cả Sự kiện,
 * Gương tiêu biểu và Dịch vụ (nay là các cụm events, examples, services tách từ cụm này).
 */
final class ActivitiesBlock extends AbstractBlockDefinition
{
    protected string $key = 'activities';

    protected string $icon = 'ri-layout-row-line';

    protected array $binding = ['featured_categories' => '@featured'];

    public function __construct()
    {
        $this->schema = [
            'featured_categories' => ['type' => 'categories', 'label' => 'homepage.fields.featured_categories', 'default' => [], 'needed' => true],
        ];
    }

    public function resolve(array $settings, array $data): array
    {
        return ['homeCategoryTabs' => app(HomeBlocks::class)->categoryTabs(array_values(array_map('intval', (array) ($settings['featured_categories'] ?? []))))];
    }
}
