<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::transaction(function () {
        // Begin transaction
        foreach (RolesEnum::cases() as $role) {
          Role::findOrCreate($role->label());
        }

        $rolePermissionsJson = file_get_contents(base_path('database/data/roles.json'));
        $rolePermissionsData = json_decode($rolePermissionsJson);

        // Convert name to label
        foreach ($rolePermissionsData->roles as $role) {
          foreach ($role->permissions as $pIndex => $permission) {
            $role->permissions[$pIndex] = PermissionsEnum::tryFrom($permission)->label();
          }
        }

        $modules = Module::allEnabled();
        foreach ($modules as $module) {
          $moduleRoleDataPath = $module->getPath() . '/database/data/roles.json';
          if (File::exists($moduleRoleDataPath)) {
            $enumClass = 'Modules\\' . $module->getName() . '\\Enums\\PermissionsEnum';
            if (!enum_exists($enumClass)) {
              throw new \Exception($enumClass . ' class not found');
            }

            $moduleRolePermissionsJson = file_get_contents($moduleRoleDataPath);
            $moduleRolePermissionsData = json_decode($moduleRolePermissionsJson);

            // Convert name to label
            foreach ($moduleRolePermissionsData->roles as $role) {
              foreach ($role->permissions as $pIndex => $permission) {
                $role->permissions[$pIndex] = $enumClass::tryFrom($permission)->label();
              }
            }

            if ($moduleRolePermissionsData->roles) {
              foreach ($rolePermissionsData->roles as $role) {
                foreach ($moduleRolePermissionsData->roles as $moduleRole) {
                  if ($role->name == $moduleRole->name) {
                    $role->permissions = array_merge($role->permissions, $moduleRole->permissions);
                  }
                }
              }
            }
          }
        }

        foreach ($rolePermissionsData->roles as $roleConfig) {
          $role = Role::findByName(RolesEnum::tryFrom($roleConfig->name)->label());
          $role->syncPermissions($roleConfig->permissions);
        }

        // Commit transaction
      });
    }
}
