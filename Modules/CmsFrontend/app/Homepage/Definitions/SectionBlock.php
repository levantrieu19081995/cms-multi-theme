<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsFrontend\Services\HomeBlocks;

/**
 * Cụm hiển thị bài mới nhất của MỘT chuyên mục (Giới thiệu, Chương trình, Nghiên cứu, Tuyển sinh, Đề án...). Cùng một Definition cho nhiều
 * instance; mỗi instance chọn chuyên mục riêng. Chưa chọn thì dùng chuyên mục theo slug trong `screens.php` (home_sections) như hiện tại.
 */
final class SectionBlock extends AbstractBlockDefinition
{
    protected string $key = 'section';

    protected string $icon = 'ri-article-line';

    protected ?string $route = 'cms.dashboard.cdn.bai-viet.index';


    protected array $schema = [
        'category_id' => ['type' => 'category', 'label' => 'homepage.fields.section_category', 'help' => 'homepage.fields.section_category_help'],
    ];

    /** Một lần nạp cho mọi instance `section` đang bật (số truy vấn không tăng theo số instance). */
    public function prefetch(array $allSettings): void
    {
        $ids = array_values(array_filter(array_map(fn ($settings) => (int) ($settings['category_id'] ?? 0), $allSettings)));
        if ($ids !== []) {
            app(HomeBlocks::class)->sectionsByCategories($ids);
        }
    }

    public function resolve(array $settings, array $data): array
    {
        $category = (int) ($settings['category_id'] ?? 0);
        $instance = (string) ($data['_instance'] ?? '');
        if ($category <= 0 || $instance === '') {
            return [];
        }

        return ['homeSections' => array_merge((array) ($data['homeSections'] ?? []), [$instance => app(HomeBlocks::class)->sectionByCategory($category)])];
    }
}
