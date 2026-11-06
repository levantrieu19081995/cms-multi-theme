<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Contracts;

/**
 * Loại cụm của trang chủ do CODE khai báo (không phải một dòng tùy ý trong database). `key()` là mã ổn định, không đổi sau khi phát hành.
 * Cách hiển thị (Blade, variant, effect) do từng HomepageThemeLayout quyết định, không nằm ở đây.
 */
interface HomepageBlockDefinition
{
    /** Mã ổn định, chữ thường/số/gạch ngang. */
    public function key(): string;

    /** Lang key tên cụm (VI/EN), vd `homepage.blocks.news.name`. */
    public function nameKey(): string;

    /** Lang key mô tả cụm (VI/EN). */
    public function descriptionKey(): string;

    /** Class icon (Remix Icon). */
    public function icon(): string;

    /**
     * Schema cấu hình nguồn nội dung: field => ['type' => category|categories|number|text|textarea|boolean|select|management_link, ...].
     * Các khóa khác: label (lang key), required, default, min, max, options, options_source, route (management_link), help.
     *
     * @return array<string, array<string, mixed>>
     */
    public function settingsSchema(): array;

    /**
     * Giá trị mặc định an toàn của từng field trong schema.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /** Tên route của chức năng quản lý nội dung riêng (nếu có), vd `cms.tin-tuc.index`. */
    public function managementRoute(): ?string;

    /**
     * Cấu hình cũ (option) tương ứng với field của schema: field => option key. CHỈ dùng để khởi tạo/migrate MỘT LẦN settings của theme
     * khi chưa có dòng trong homepage_block_settings; sau đó dữ liệu theo theme là nguồn duy nhất, option cũ không bị đọc hay ghi nữa.
     * Giá trị đặc biệt `@featured` = thứ tự chuyên mục nổi bật theo theme (HomeSettings::featuredCategories).
     *
     * @return array<string, string>
     */
    public function legacyBinding(): array;

    /**
     * Nạp trước dữ liệu cho TẤT CẢ instance đang bật của Definition này (gom truy vấn, tránh N+1 khi nhiều instance cùng loại).
     *
     * @param  list<array<string, mixed>>  $allSettings  settings của từng instance đang bật
     */
    public function prefetch(array $allSettings): void;

    /**
     * Dữ liệu bổ sung cho renderer ngoài bộ dữ liệu chung của trang chủ. Chỉ ĐỌC, không ghi dữ liệu nghiệp vụ.
     *
     * @param  array<string, mixed>  $settings  cấu hình đã validate của instance
     * @param  array<string, mixed>  $data  bộ dữ liệu trang chủ (HomeController) cộng `_theme` và `_instance`
     * @return array<string, mixed> các khóa dữ liệu trang chủ cần THAY THẾ (vd categoryPosts, homepageDocuments, homeSections) theo cấu hình của theme
     */
    public function resolve(array $settings, array $data): array;
}
