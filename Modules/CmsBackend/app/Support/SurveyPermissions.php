<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use App\Models\User;
use Modules\CmsBackend\Enums\PermissionsEnum;

/**
 * Kiểm tra quyền Khảo sát cho giao diện, cùng ngữ nghĩa với middleware `cms.check.permission`:
 * quyền con HOẶC quyền cha `khaosat-manager` (quyền cha bao trùm toàn bộ quyền con). Tên quyền trong DB là nhãn của enum.
 */
final class SurveyPermissions
{
    public static function can(?User $user, PermissionsEnum $permission): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->can($permission->label()) || $user->can(PermissionsEnum::KHAOSAT_MANAGER->label());
    }

    /** Một trong các quyền (ANY-of), như middleware nhiều quyền. */
    public static function canAny(?User $user, PermissionsEnum ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, bool> khóa là giá trị enum (vd. 'khaosat-update'), cho view */
    public static function map(?User $user): array
    {
        $map = [];
        foreach (PermissionsEnum::cases() as $case) {
            if (str_starts_with($case->value, 'khaosat-') && $case !== PermissionsEnum::KHAOSAT_MANAGER) {
                $map[$case->value] = self::can($user, $case);
            }
        }

        return $map;
    }
}
