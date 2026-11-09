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

        $parent = str_starts_with($permission->value, 'nghenghiep-') ? PermissionsEnum::NGHENGHIEP_MANAGER : PermissionsEnum::KHAOSAT_MANAGER;

        return $user->can($permission->label()) || $user->can($parent->label());
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

    /**
     * Khóa luôn là giá trị quyền của cụm Khảo sát (vd. 'khaosat-update') để các view dùng chung không phải đổi; giá trị là quyền TƯƠNG ỨNG
     * của cụm đang chạy (cụm Định hướng nghề nghiệp thì kiểm tra nghenghiep-*).
     *
     * @return array<string, bool>
     */
    public static function map(?User $user, ?string $cluster = null): array
    {
        $cluster ??= SurveyCluster::current();
        $map = [];
        foreach (PermissionsEnum::cases() as $case) {
            if (str_starts_with($case->value, 'khaosat-') && $case !== PermissionsEnum::KHAOSAT_MANAGER) {
                $suffix = strtoupper(str_replace('-', '_', substr($case->value, strlen('khaosat-'))));
                $map[$case->value] = self::can($user, SurveyCluster::permission($suffix, $cluster));
            }
        }

        return $map;
    }
}
