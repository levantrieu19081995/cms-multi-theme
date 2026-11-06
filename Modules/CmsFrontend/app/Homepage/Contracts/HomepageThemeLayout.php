<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Contracts;

/**
 * Bố cục trang chủ mà MỘT theme hỗ trợ: zone hợp lệ, cụm theme có renderer, variant và bố cục mặc định (phản ánh trang chủ hiện tại).
 * Theme không phụ thuộc theme khác.
 */
interface HomepageThemeLayout
{
    public function themeKey(): string;

    /**
     * zone key => lang key tên zone.
     *
     * @return array<string, string>
     */
    public function zones(): array;

    /**
     * Cụm theme có khả năng hiển thị, theo instance key:
     * ['definition' => key, 'zones' => [zone hợp lệ], 'variants' => [variant => ['renderer' => 'tên partial dưới components/home', 'effects' => 'mô tả hook effect (thông tin)']], 'default_variant' => key]
     *
     * @return array<string, array<string, mixed>>
     */
    public function blocks(): array;

    /**
     * Bố cục mặc định = trang chủ hiện tại, theo thứ tự hiển thị.
     *
     * @return list<array{instance: string, zone: string, variant: string, enabled: bool}>
     */
    public function defaultLayout(): array;
}
