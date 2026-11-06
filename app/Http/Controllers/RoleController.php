<?php

namespace App\Http\Controllers;

use App\DataProviders\RoleDataProvider;
use App\Events\UpdateRole;
use App\Facades\Toastr;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Enums\PermissionsEnum;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{

    protected RoleDataProvider $roleDataProvider;

    public function __construct(RoleDataProvider $roleDataProvider)
    {
        $this->roleDataProvider = $roleDataProvider;

        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('check.permission:' . PermissionsEnum::ROLE_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    private function availability(): \Modules\CmsBackend\Services\Developer\PermissionAvailability
    {
        return app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->filter;
        $roles = $this->roleDataProvider->paginate($filter, 20);
        $roles->appends($request->all());

        $permissionsByRole = [];
        foreach ($roles as $role) {
            // Permissions the Developer has not made available stay in the database but are not shown.
            $permissions = $this->availability()->filter($role->permissions); // Lấy quyền theo role_id
            $parentPermissions = $permissions->where('parent_id', -1);
            $groupedPermissions = $permissions->groupBy('parent_id');
            $parentDetails = Permission::whereIn('id', $groupedPermissions->keys())->get()->keyBy('id');

            $permissionsByRole[$role->id] = [
                'parentPermissions' => $parentPermissions,
                'groupedPermissions' => $groupedPermissions,
                'parentDetails' => $parentDetails
            ];
        }
        return view('pages.role.role-list', compact(['roles', 'permissionsByRole']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $role = new Role;
        $permissions = $this->availability()->filter(Permission::all());

        return view('pages.role.role-edit', compact(['role', 'permissions']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRoleRequest $request)
    {
        $role = new Role;
        $role->name = $request->name;
        $role->save();

        if ($request->permissions) {
            $permissionIds = [];
            foreach ($request->permissions as $permissionIdEncrypted) {
                $permissionIds[] = Crypt::decryptString($permissionIdEncrypted);
            }
            // Refuses permissions outside the deployment's scope and keeps the hidden ones the role already has.
            $this->availability()->syncRole($role, Permission::whereIn('id', $permissionIds)->get());
        }

        Toastr::success(__('Thêm mới thành công'));

        return redirect()->route('roles.edit', $role);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = $this->availability()->filter(Permission::all());
        foreach ($permissions as $permission) {
            $permission->assigned = $role->permissions->some(function ($rolePermission) use ($permission) {
                return $rolePermission->id === $permission->id;
            });
        }
        return view('pages.role.role-edit', compact(['role', 'permissions']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();

        if ($request->permissions) {
            $permissionIds = [];
            foreach ($request->permissions as $permissionIdEncrypted) {
                $permissionIds[] = Crypt::decryptString($permissionIdEncrypted);
            }
            // Refuses permissions outside the deployment's scope and keeps the hidden ones the role already has.
            $this->availability()->syncRole($role, Permission::whereIn('id', $permissionIds)->get());
        }

        Toastr::success(__('Cập nhật thành công'));

        event(new UpdateRole($role));

        return redirect()->route('roles.edit', $role);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     dd('xóa vai trò');
    //     // $role = Role::findOrFail($id);
    //     // $role->delete();
    //     // return response()->json([
    //     //     'status' => 1,
    //     //     'message' => 'Xóa thành công'
    //     // ]);

    //     // Lấy vai trò từ ID
    //     $role = Role::findOrFail($id);

    //     // Kiểm tra nếu người dùng đang cố gắng xóa vai trò của chính mình (để bảo vệ)
    //     if ($role->id == Auth::user()->id) {
    //         return response()->json([
    //             'status' => 0,
    //             'message' => 'Không thể xóa vai trò của chính bạn.'
    //         ], 400);
    //     }

    //     // Xóa vai trò
    //     $role->delete();

    //     // Trả về phản hồi thành công
    //     return response()->json([
    //         'status' => 1,
    //         'message' => 'Xóa vai trò thành công.'
    //     ]);
    // }
    // public function destroy($id)
    // {
    //     // Lấy vai trò từ ID
    //     $role = Role::findOrFail($id);

    //     // Kiểm tra nếu người dùng đang cố gắng xóa vai trò của chính mình (để bảo vệ)
    //     if ($role->id == Auth::user()->id) {
    //         // return response()->json([
    //         //     'status' => 0,
    //         //     'message' => 'Không thể xóa vai trò của chính bạn.'
    //         // ], 400);
    //         Toastr::error(__('Không thể xóa vai trò của chính bạn'));
    //         return redirect()->route('roles.index'); // Quay lại trang danh sách vai trò
    //     }

    //     // Xóa vai trò
    //     $role->delete();

    //     Toastr::success(__('Xóa vai trò thành công.'));
    //     return redirect()->route('roles.index'); // Quay lại trang danh sách vai trò
    // }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Nếu role đang được gán cho user nào đó thì không cho xóa
        if ($role->users()->exists()) {
            Toastr::warning(__('Vai trò đang được gán cho người dùng, không thể xóa.'));
            return redirect()->route('roles.index');
        }

        $role->delete();

        Toastr::success(__('Xóa vai trò thành công.'));
        return redirect()->route('roles.index');
    }
}
