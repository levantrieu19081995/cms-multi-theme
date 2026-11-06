<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features\Definitions;

use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\Contracts\FeatureDefinition;

/**
 * Liên hệ – Góp ý. Dữ liệu `lienhe` không gắn theme/dự án; theme chỉ trình bày giao diện, bật/tắt do Feature Registry quyết định.
 * Tên và mô tả lấy từ lang/{locale}/features.php.
 */
final class ContactFeature implements FeatureDefinition
{
    public function key(): string
    {
        return 'contact';
    }

    public function name(): string
    {
        return (string) __('features.contact.name');
    }

    public function description(): string
    {
        return (string) __('features.contact.description');
    }

    public function icon(): string
    {
        return 'ri-customer-service-2-line';
    }

    public function adminRoute(): ?string
    {
        return 'cms.dashboard.lien-he.index';
    }

    public function publicTargets(): array
    {
        return [
            ['key' => 'index', 'name' => (string) __('features.contact.targets.index'), 'route' => 'cms.lien-he'],
        ];
    }

    public function permissions(): array
    {
        return array_map(fn (PermissionsEnum $case) => $case->value, [
            PermissionsEnum::LIENHE_MANAGER,
            PermissionsEnum::LIENHE_VIEW,
            PermissionsEnum::LIENHE_DELETE,
        ]);
    }

    public function enabledByDefault(): bool
    {
        return true;
    }
}
