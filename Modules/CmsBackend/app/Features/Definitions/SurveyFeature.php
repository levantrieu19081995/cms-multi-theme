<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Definitions;

use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\Contracts\FeatureDefinition;

/**
 * Khảo sát dùng chung. Dữ liệu khảo sát không gắn với theme/dự án; theme chỉ trình bày giao diện, bật/tắt do Feature Registry quyết định.
 * Tên và mô tả lấy từ lang/{locale}/features.php theo locale hiện tại.
 */
final class SurveyFeature implements FeatureDefinition
{
    public function key(): string
    {
        return 'survey';
    }

    public function name(): string
    {
        return (string) __('features.survey.name');
    }

    public function description(): string
    {
        return (string) __('features.survey.description');
    }

    public function icon(): string
    {
        return 'ri-survey-line';
    }

    public function adminRoute(): ?string
    {
        return 'cms.khao-sat';
    }

    public function publicTargets(): array
    {
        return [
            ['key' => 'index', 'name' => (string) __('features.survey.targets.index'), 'route' => 'cms.frontend.khaosat.index'],
        ];
    }

    public function permissions(): array
    {
        return array_map(fn (PermissionsEnum $case) => $case->value, [
            PermissionsEnum::KHAOSAT_MANAGER,
            PermissionsEnum::KHAOSAT_VIEW,
            PermissionsEnum::KHAOSAT_CREATE,
            PermissionsEnum::KHAOSAT_UPDATE,
            PermissionsEnum::KHAOSAT_PUBLISH,
            PermissionsEnum::KHAOSAT_DELETE,
            PermissionsEnum::KHAOSAT_RESPONSE_VIEW,
            PermissionsEnum::KHAOSAT_RESPONSE_DELETE,
            PermissionsEnum::KHAOSAT_STATISTICS_VIEW,
            PermissionsEnum::KHAOSAT_EXPORT,
        ]);
    }

    public function enabledByDefault(): bool
    {
        return true;
    }
}
