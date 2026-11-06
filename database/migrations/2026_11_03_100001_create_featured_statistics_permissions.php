<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Quyền riêng cho "Quản lý số liệu nổi bật" (trước đây dùng chung homepage-manager): một quyền cha và hai quyền con, cùng cách
 * đặt tên/slug như các quyền khác (name = nhãn tiếng Việt, slug = mã trong PermissionsEnum).
 *
 * Giữ nguyên người dùng hiện có: mọi vai trò đang có homepage-manager được gán quyền cha mới, nên không ai mất quyền sau khi cập nhật.
 * Muốn tách hẳn, gỡ quyền này khỏi vai trò ở Developer Settings → Tài khoản (hoặc màn quản lý vai trò). Chạy lại nhiều lần không tạo trùng;
 * không xóa quyền hay vai trò nào.
 */
return new class extends Migration
{
    private const PARENT = ['name' => 'Quản lý số liệu nổi bật', 'slug' => 'sonoibat-manager'];

    private const CHILDREN = [
        ['name' => 'Xem số liệu nổi bật', 'slug' => 'sonoibat-view'],
        ['name' => 'Cập nhật số liệu nổi bật', 'slug' => 'sonoibat-update'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }
        $hasSlug = Schema::hasColumn('permissions', 'slug');
        $hasParent = Schema::hasColumn('permissions', 'parent_id');

        DB::transaction(function () use ($hasSlug, $hasParent) {
            $parentId = $this->ensure(self::PARENT, null, $hasSlug, $hasParent);
            foreach (self::CHILDREN as $child) {
                $this->ensure($child, $parentId, $hasSlug, $hasParent);
            }

            // Vai trò đang quản lý trang chủ giữ được quyền sửa số liệu như trước.
            $homepage = DB::table('permissions')->where('name', 'Quản lý trang chủ')->where('guard_name', 'web')->value('id');
            if ($homepage !== null) {
                $roleIds = DB::table('role_has_permissions')->where('permission_id', $homepage)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $parentId, 'role_id' => $roleId]);
                }
            }
        });

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /** Không xóa quyền khi lùi migration: vai trò đã gán có thể đang dùng. */
    public function down(): void
    {
    }

    private function ensure(array $permission, ?int $parentId, bool $hasSlug, bool $hasParent): int
    {
        $row = DB::table('permissions')->where('name', $permission['name'])->where('guard_name', 'web')->first();
        $values = [];
        if ($hasSlug) {
            $values['slug'] = $permission['slug'];
        }
        if ($hasParent && $parentId !== null) {
            $values['parent_id'] = $parentId;
        }
        if ($row === null) {
            return (int) DB::table('permissions')->insertGetId(['name' => $permission['name'], 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()] + $values);
        }
        if ($values !== []) {
            DB::table('permissions')->where('id', $row->id)->update($values + ['updated_at' => now()]);
        }

        return (int) $row->id;
    }
};
