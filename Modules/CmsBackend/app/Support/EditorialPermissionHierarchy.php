<?php

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class EditorialPermissionHierarchy
{
    /**
     * Link the CMS workflow permissions to their visible permission groups and
     * remove workflow-role duplicates from administrators. Existing permission
     * selections on roles are deliberately preserved.
     *
     * @return array{linked:int, detached:int}
     */
    public static function repair(): array
    {
        return DB::transaction(function (): array {
            $linked = 0;
            $detached = 0;

            if (Schema::hasColumn('permissions', 'parent_id')) {
                foreach (EditorialPermissions::definitions() as $definition) {
                    $permission = Permission::query()
                        ->where('guard_name', 'web')
                        ->where('name', $definition['name'])
                        ->first();
                    $parent = Permission::query()
                        ->where('guard_name', 'web')
                        ->where('name', $definition['parent'])
                        ->first();

                    if ($permission && $parent && (int) $permission->parent_id !== (int) $parent->id) {
                        $permission->parent_id = $parent->id;
                        $permission->save();
                        $linked++;
                    }
                }
            }

            $administrator = Role::query()
                ->where('guard_name', 'web')
                ->where('name', 'Quản trị')
                ->first();
            $workflowRoles = Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', [EditorialPermissions::AUTHOR_ROLE, EditorialPermissions::EDITOR_ROLE])
                ->get();

            if ($administrator && $workflowRoles->isNotEmpty()) {
                foreach ($administrator->users()->get() as $user) {
                    foreach ($workflowRoles as $workflowRole) {
                        if ($user->hasRole($workflowRole)) {
                            $user->removeRole($workflowRole);
                            $detached++;
                        }
                    }
                }
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return compact('linked', 'detached');
        });
    }
}
