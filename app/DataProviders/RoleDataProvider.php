<?php

namespace App\DataProviders;

use Spatie\Permission\Models\Role;

class RoleDataProvider
{
    public function paginate($filter, $pageSize = 20)
    {
        return Role::with(['permissions', 'users' => function ($builder) {
              $builder->limit(5);
            }])
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('name', 'like', "%$filter%");
                }
            })
            ->paginate($pageSize);
    }
}
