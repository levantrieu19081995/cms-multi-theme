<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

/**
 * Cấp dữ liệu cho các cụm theo CẤU HÌNH CỦA THEME. Bộ dữ liệu chung của trang chủ (HomeController) là mặc định; mỗi Definition thay thế phần
 * nội dung của nó theo settings của theme (chuyên mục thông báo, bộ lọc văn bản, tab nổi bật, lịch, chuyên mục từng cụm...). Việc này chạy MỘT lần
 * trước khi HomePresentation tính khóa cache, nên cache theo đúng nội dung của theme; các Definition cùng loại được `prefetch()` để số truy vấn
 * không tăng theo số instance.
 */
final class HomepageDataResolver
{
    public function __construct(private HomepageBlockRegistry $registry) {}

    /**
     * Dữ liệu trang chủ sau khi áp dụng cấu hình của mọi cụm đang bật theo thứ tự hiển thị.
     *
     * @param  array{theme: string, zones: array<string, list<array<string, mixed>>>}  $plan
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mergeAll(array $plan, array $data): array
    {
        $blocks = [];
        foreach ($plan['zones'] as $zoneBlocks) {
            foreach ($zoneBlocks as $block) {
                $blocks[] = $block;
            }
        }
        $byDefinition = [];
        foreach ($blocks as $block) {
            $byDefinition[$block['definition']][] = $block['settings'];
        }
        foreach ($byDefinition as $key => $allSettings) {
            $this->registry->definition($key)?->prefetch($allSettings);
        }
        foreach ($blocks as $block) {
            $extra = $this->registry->definition($block['definition'])?->resolve($block['settings'], $data + ['_theme' => $plan['theme'], '_instance' => $block['instance']]) ?? [];
            $data = array_merge($data, $extra);
        }

        return $data;
    }

    /**
     * Biến mô tả riêng cho Blade của một cụm (dữ liệu nội dung đã nằm trong bộ dữ liệu chung sau mergeAll()).
     *
     * @param  array<string, mixed>  $block  một phần tử của kế hoạch render
     * @return array<string, mixed>
     */
    public function forBlock(array $block): array
    {
        return ['homeBlock' => $block, 'homeBlockSettings' => $block['settings'], 'homeBlockVariant' => $block['variant']];
    }
}
