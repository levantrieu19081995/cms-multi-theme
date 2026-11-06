<?php

namespace Modules\CmsBackend\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;

class CDNCheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, __('admin.message.ban_chua_dang_nhap'));
        }

        // Permission availability comes first: a permission this deployment does not offer grants nothing, not even
        // through its parent, and no role (including "Quản trị") bypasses it.
        $availability = app(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class);
        $requested = $permissions;
        $permissions = array_values(array_filter($permissions, fn ($permission) => $availability->isAvailable($permission)));
        if ($requested !== [] && $permissions === []) {
            return $this->deny($request);
        }

        $allPermissions = [];
        foreach ($permissions as $permission) {
            // Nếu là quyền cha, thêm quyền con
            if (isset(PermissionsEnum::parentChildren()[$permission])) {
                $allPermissions = array_merge($allPermissions, PermissionsEnum::parentChildren()[$permission]);
            }

            // Nếu là quyền con, thêm quyền cha vào danh sách
            $parentPermission = PermissionsEnum::getParent($permission);
            if ($parentPermission) {
                $allPermissions[] = $parentPermission;
            }

            // Thêm quyền gốc vào danh sách
            $allPermissions[] = $permission;
        }

        // Loại bỏ trùng lặp
        $allPermissions = array_unique($allPermissions);
        // Kiểm tra nếu user có ít nhất một quyền
        foreach ($allPermissions as $perm) {

            if ($user->can($perm)) {
                return $next($request);
            }
        }

        return $this->deny($request);
    }

    private function deny(Request $request)
    {
        if ($request->isMethod('DELETE')) {
            return response()->json([
                'status' => 0,
                'message' => __('admin.message.ban_khong_co_quyen_thuc_hien_xoa_du_lieu')
            ], 403);
        }

        return response()->view('pages.errors.403', [], 403);
    }
}
