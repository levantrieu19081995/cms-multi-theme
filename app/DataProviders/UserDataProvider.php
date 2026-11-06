<?php

namespace App\DataProviders;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypesEnum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Spatie\Permission\Models\Role;

class UserDataProvider
{
    /**
     * Undocumented function
     *
     * @param array UserTypesEnum $userType or []
     * @param array UserStatusEnum $userStatus or []
     * @param array Role $role or []
     * @param string $filter
     * @param string $sortBy
     * @param string $order
     * @param integer $pageSize
     * @return void
     */
    public function paginate(
        $userTypes,
        $userStatuses,
        $roleIds,
        string $filter,
        string $sortBy,
        string $order,
        int $pageSize = 20
    ): LengthAwarePaginator {
        $users = User::with(['roles'])
            ->where(function ($builder) use ($roleIds, $userTypes, $userStatuses) {
                if ($roleIds) {
                    $builder->whereHas('roles', function ($builder) use ($roleIds) {
                        if ($roleIds) {
                            $builder->whereIn('id', $roleIds);
                        }
                    });
                }
                if ($userTypes) {
                    $builder->whereIn('type', $userTypes);
                }
                if ($userStatuses) {
                    $builder->whereIn('status', $userStatuses);
                }
            })
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('name', 'like', "%$filter%")
                        ->orWhere('email', 'like', "%$filter%");
                }
            })
            ->orderBy($sortBy, $order)
            ->paginate($pageSize);

        return $users;
    }
}
