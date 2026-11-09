<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Definitions;

use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\Contracts\FeatureDefinition;

/**
 * Định hướng nghề nghiệp (RIASEC): cụm chức năng riêng (menu, địa chỉ /dinh-huong-nghe-nghiep, quyền nghenghiep-*, công tắc riêng) nhưng dùng
 * chung bộ máy khảo sát (dựng câu hỏi, trang làm bài, phản hồi, thống kê, xuất) nên KHÔNG cần chức năng Khảo sát đang bật.
 * Tên và mô tả lấy từ lang/{locale}/features.php.
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
        return 'cms.nghe-nghiep';
    }

    public function publicTargets(): array
    {
        return [
            ['key' => 'index', 'name' => (string) __('features.career.targets.index'), 'route' => 'cms.frontend.nghenghiep.index'],
        ];
    }

    public function permissions(): array
    {
        return array_map(fn (PermissionsEnum $case) => $case->value, [
            PermissionsEnum::NGHENGHIEP_MANAGER,
            PermissionsEnum::NGHENGHIEP_VIEW,
            PermissionsEnum::NGHENGHIEP_CREATE,
            PermissionsEnum::NGHENGHIEP_UPDATE,
            PermissionsEnum::NGHENGHIEP_PUBLISH,
            PermissionsEnum::NGHENGHIEP_DELETE,
            PermissionsEnum::NGHENGHIEP_RESPONSE_VIEW,
            PermissionsEnum::NGHENGHIEP_RESPONSE_DELETE,
            PermissionsEnum::NGHENGHIEP_STATISTICS_VIEW,
            PermissionsEnum::NGHENGHIEP_EXPORT,
        ]);
    }

    public function enabledByDefault(): bool
    {
        return true;
    }
}
