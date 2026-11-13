<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

use Modules\CmsFrontend\Homepage\Contracts\HomepageThemeLayout;

/** Khung chung của Theme Layout: helper khai báo cụm gọn. Mỗi theme tự khai báo, không kế thừa khai báo của theme khác. */
abstract class AbstractThemeLayout implements HomepageThemeLayout
{
    /**
     * @param  string[]  $zones  zone hợp lệ của cụm
     * @param  array<string, array{renderer: string, effects?: string[]}>  $variants  variant => renderer (partial dưới components/home) và danh sách KEY effect preset (EffectCatalog)
     * @param  string|null  $splitFrom  instance cũ mà cụm này được tách ra: bố cục đã cài chưa có dòng của cụm thì cụm đứng ngay sau cụm cũ, cùng zone
     *                                  và trạng thái bật/tắt; cấu hình nội dung chưa lưu riêng thì lấy từ cấu hình đã lưu của cụm cũ (xem HomepageLayoutService::rows())
     * @return array<string, mixed>
     */
    protected static function block(string $definition, array $zones, array $variants, ?string $default = null, ?string $splitFrom = null): array
    {
        return ['definition' => $definition, 'zones' => $zones, 'variants' => $variants, 'default_variant' => $default ?? (string) array_key_first($variants)]
            + ($splitFrom !== null ? ['split_from' => $splitFrom] : []);
    }

    /**
     * Một variant duy nhất `standard` dùng partial hiện có của theme.
     *
     * @param  string[]  $effects  KEY preset trong EffectCatalog
     * @return array<string, array{renderer: string, effects?: string[]}>
     */
    protected static function standard(string $renderer, array $effects = []): array
    {
        return ['standard' => ['renderer' => $renderer, 'effects' => $effects]];
    }

    /**
     * Danh sách bố cục mặc định từ map zone => [instance...] (theo thứ tự hiển thị), mọi cụm bật, variant mặc định của chúng.
     *
     * @param  array<string, string[]>  $map
     * @return list<array{instance: string, zone: string, variant: string, enabled: bool}>
     */
    protected function layoutFrom(array $map): array
    {
        $blocks = $this->blocks();
        $rows = [];
        foreach ($map as $zone => $instances) {
            foreach ($instances as $instance) {
                $rows[] = ['instance' => $instance, 'zone' => $zone, 'variant' => (string) $blocks[$instance]['default_variant'], 'enabled' => true];
            }
        }

        return $rows;
    }
}
