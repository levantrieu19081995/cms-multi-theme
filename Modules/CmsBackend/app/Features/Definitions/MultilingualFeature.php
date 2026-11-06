<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Definitions;

use Modules\CmsBackend\Features\Contracts\FeatureDefinition;

/**
 * Đa ngôn ngữ nội dung công khai (Việt + ngôn ngữ phụ như English). Không có trang quản trị, điểm đến công khai hay quyền riêng.
 * Tắt: LocaleManager chỉ còn ngôn ngữ mặc định, nên nút chọn ngôn ngữ, tab/ô nhập bản dịch, hreflang và các route /en đều biến mất;
 * dữ liệu bản dịch đã nhập vẫn nằm nguyên trong DB và hiện lại khi bật.
 */
final class MultilingualFeature implements FeatureDefinition
{
    public function key(): string
    {
        return 'multilingual';
    }

    public function name(): string
    {
        return (string) __('features.multilingual.name');
    }

    public function description(): string
    {
        return (string) __('features.multilingual.description');
    }

    public function icon(): string
    {
        return 'ri-translate-2';
    }

    public function adminRoute(): ?string
    {
        return null;
    }

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
