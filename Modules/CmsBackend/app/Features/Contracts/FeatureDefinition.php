<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Contracts;

/**
 * Định nghĩa một chức năng hệ thống: "hệ thống có khả năng gì". Chỉ mô tả, không chứa trạng thái bật/tắt (trạng thái nằm ở bảng
 * feature_settings của bản triển khai) và không đọc/ghi dữ liệu nghiệp vụ.
 *
 * Thêm chức năng mới = tạo một class trong Features/Definitions triển khai interface này; FeatureRegistry tự nhận.
 */
interface FeatureDefinition
{
    /** Mã ổn định, duy nhất (chữ thường, số, gạch ngang). Được lưu trong DB (feature_settings, menu_items) nên không đổi sau khi phát hành. */
    public function key(): string;

    public function name(): string;

    public function description(): string;

    /** Lớp icon Remix, ví dụ "ri-survey-line". */
    public function icon(): string;

    /** Tên route trang quản trị (không tham số), hoặc null nếu chức năng không có trang quản trị. */
    public function adminRoute(): ?string;

    /**
     * Các điểm đến công khai có thể gắn vào menu. Route phải không cần tham số.
     *
     * @return list<array{key: string, name: string, route: string}>
     */
    public function publicTargets(): array;

    /**
     * Slug các quyền của chức năng (giá trị PermissionsEnum, ví dụ "khaosat-manager").
     *
     * @return list<string>
     */
    public function permissions(): array;

    /** Trạng thái khi chưa có bản ghi trong feature_settings. */
    public function enabledByDefault(): bool;
}
