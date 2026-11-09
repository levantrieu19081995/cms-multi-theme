<?php

namespace Modules\CmsBackend\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use App\Models\Permission;
use Spatie\Permission\Models\Role;
use App\DataProviders\RoleDataProvider;
use App\Events\UpdateRole;
use App\Facades\Toastr;
use App\Http\Requests\Role\CreateRoleRequest;

class RoleController extends Controller
{
    public function getDsVaiTro()
    {
        // Only the permissions this deployment offers are listed to the role screen.
        $permissions = app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class)->filter(Permission::all());

        if ($permissions->isEmpty()) {
            return response()->json([
                'code'    => 404,
                'status'  => 'error',
                'message' => 'Không có vai trò nào!',
                'data'    => [],
            ], 404);
        }

        $encryptedPermissions = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'id_encrypt'          => Crypt::encryptString($permission->id),
                'name'        => $permission->name, // Giữ lại các thông tin khác
                'slug'        => $permission->slug,
                'parent_id' => $permission->parent_id
            ];
        });

        return response()->json([
            'code'    => 200,
            'status'  => 'success',
            'message' => 'Load danh sách vai trò thành công!',
            'data'    => $encryptedPermissions,
        ], 200);
    }

    public function getDsVaiTroByUserId($userId)
    {
        $permissionsIds = DB::table('role_has_permissions')
            ->where('role_id', $userId)
            ->pluck('permission_id'); // Lấy danh sách ID quyền
        // Hidden (unavailable) permissions stay assigned in the database but are not shown.
        $hidden = app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class)->unavailableIds();
        $permissionsIds = $permissionsIds->reject(fn ($id) => in_array((int) $id, $hidden, true))->values();

        $encryptPermissionsIds = $permissionsIds->map(function ($id) {
            return [
                'id' => $id,
                'id_encrypt' => Crypt::encryptString($id)
            ];
        });
        if ($encryptPermissionsIds) {
            return response()->json([
                'code'    => 200,
                'status'  => 'success',
                'message' => 'Load danh sách vai trò!',
                'data'    => $encryptPermissionsIds,
            ], 200);
        }
        return response()->json([
            'code'    => 500,
            'status'  => 'error',
            'message' => 'Load danh sách vai trò thất bại!',
            'data'    => [],
        ], 500);
    }



    public function save(UpdateRoleRequest $request, $id)
    {

        $isUpdate = !empty($id) && (int)$id > 0;
        if ($isUpdate) {
            $role = Role::findOrFail($id);
        } else {
            $role = new Role;
        };

        $role->name = $request->name;
        $role->save();
        // Decrypt and handle permissions
        if ($request->has('permissions')) {
            $permissions = json_decode($request->permissions);
            $permissionIds = [];

            // dd($permissions);
            foreach ($permissions as $permissionIdEncrypted) {
                $permissionIds[] = Crypt::decryptString($permissionIdEncrypted);
            }


            // Refuses permissions outside the deployment's scope (403) and keeps the hidden ones the role already has.
            app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class)->syncRole($role, Permission::whereIn('id', $permissionIds)->get());
        }

        event(new UpdateRole($role));

        return redirect()->route('roles.edit', $role);
    }
}
