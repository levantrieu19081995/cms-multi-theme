<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Definitions;

use Modules\CmsBackend\Features\Contracts\FeatureDefinition;

/**
 * Định hướng nghề nghiệp (RIASEC). Là phần mở rộng của Khảo sát nên cần chức năng "survey" đang bật; công tắc riêng này chỉ bật/tắt
 * loại khảo sát "career", màn "Nhóm & ngành nghề" và trang kết quả. Dùng chung quyền `khaosat-*` của Khảo sát (không khai báo quyền
 * riêng ở đây để chức năng Khảo sát vẫn là chủ sở hữu của các quyền đó). Tên và mô tả lấy từ lang/{locale}/features.php.
 */
final class CareerFeature implements FeatureDefinition
{
    public function key(): string
    {
        return 'career';
    }

    public function name(): string
    {
        return (string) __('features.career.name');
    }

    public function description(): string
    {
        return (string) __('features.career.description');
    }

    public function icon(): string
    {
        return 'ri-compass-3-line';
    }

    public function adminRoute(): ?string
    {
        return 'cms.khao-sat.career';
    }

    /** Khảo sát nghề nghiệp được mở bằng địa chỉ riêng của từng khảo sát (có tham số), nên không có điểm đến gắn menu cố định. */
    public function publicTargets(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }

    public function enabledByDefault(): bool
    {
        return true;
    }
}
