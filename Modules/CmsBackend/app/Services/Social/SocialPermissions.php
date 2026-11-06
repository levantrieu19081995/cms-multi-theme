<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use App\Models\User;
use Modules\CmsBackend\Enums\PermissionsEnum;

/**
 * Permission check of the multi-channel feature. Permission names in the database are the enum labels.
 *
 * Preparing content (view, create, statistics, accounts) is also granted by the parent "Quản lý truyền thông đa kênh", like
 * every other module. Publishing, removing and retrying act on the outside world, so the parent never grants them: each must
 * be given to the role explicitly. The route middleware follows the same rule because those three have no parent in
 * PermissionsEnum::parentChildren().
 */
final class SocialPermissions
{
    /** Actions on a social platform: only an explicit permission allows them, never the parent. */
    public const EXPLICIT_ONLY = [
        PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH,
        PermissionsEnum::SOCIAL_PUBLICATION_REMOVE,
        PermissionsEnum::SOCIAL_PUBLICATION_RETRY,
    ];

    public static function can(?User $user, PermissionsEnum $permission): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user->can($permission->label())) {
            return true;
        }

        return ! in_array($permission, self::EXPLICIT_ONLY, true) && $user->can(PermissionsEnum::SOCIAL_MANAGER->label());
    }

    /** @return array<string, bool> keyed by the permission value, for views */
    public static function map(?User $user): array
    {
        $map = [];
        foreach ([
            PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_PUBLICATION_CREATE,
            PermissionsEnum::SOCIAL_PUBLICATION_PUBLISH, PermissionsEnum::SOCIAL_PUBLICATION_REMOVE,
            PermissionsEnum::SOCIAL_PUBLICATION_RETRY, PermissionsEnum::SOCIAL_ACCOUNT_MANAGE,
            PermissionsEnum::SOCIAL_STATISTICS_VIEW,
        ] as $permission) {
            $map[$permission->value] = self::can($user, $permission);
        }

        return $map;
    }
}
