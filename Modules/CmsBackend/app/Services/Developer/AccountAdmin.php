<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

use App\Enums\RolesEnum;
use App\Events\UpdateRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Quản lý tài khoản trong Developer Settings (tab "Tài khoản"): danh sách mọi tài khoản, đặt lại mật khẩu, bật/tắt, gán vai trò, và cấu hình quyền
 * của vai trò. Chỉ phiên Developer đã mở khóa mới tới được (middleware cms.developer); mọi thao tác đều ghi nhật ký (không bao giờ ghi mật khẩu).
 * Quyền của vai trò đi qua PermissionAvailability::syncRole(): quyền Developer chưa cho phép ở dự án này không gán được và không bị gỡ nhầm.
 */
final class AccountAdmin
{
    private const MAX_USERS = 1000;

    public function __construct(private PermissionAvailability $availability) {}

    /**
     * Dữ liệu cho tab.
     *
     * @return array{users: list<array<string, mixed>>, roles: list<array<string, mixed>>, groups: list<array<string, mixed>>, truncated: bool, edit_published_ready: bool}
     */
    public function panel(): array
    {
        $total = User::query()->count();
        $users = User::query()->with('roles:id,name')->orderBy('name')->limit(self::MAX_USERS)->get()->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => (string) $user->name,
            'username' => (string) ($user->username ?? ''),
            'email' => (string) $user->email,
            'active' => (int) $user->status === 1,
            'edit_published' => (bool) ($user->can_edit_published ?? false),
            'roles' => $user->roles->map(fn ($role): array => ['id' => $role->id, 'label' => self::roleLabel($role->name)])->all(),
            'role_ids' => $user->roles->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'created_at' => optional($user->created_at)->format('d/m/Y'),
        ])->all();

        $roles = Role::query()->withCount('users')->with('permissions:id')->orderBy('id')->get()->map(fn ($role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'label' => self::roleLabel($role->name),
            'users' => (int) $role->users_count,
            'permission_ids' => $role->permissions->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'permission_count' => $role->permissions->filter(fn ($permission) => $this->availability->isAvailable($permission))->count(),
        ])->all();

        return ['users' => $users, 'roles' => $roles, 'groups' => $this->permissionGroups(), 'truncated' => $total > self::MAX_USERS, 'edit_published_ready' => $this->editPublishedReady()];
    }

    /** Quyền đang khả dụng, nhóm theo quyền cha (cùng cách vai trò đang hiển thị ở quản lý vai trò). */
    private function permissionGroups(): array
    {
        $permissions = $this->availability->filter(Permission::query()->orderBy('id')->get());
        $children = $permissions->filter(fn ($permission) => $permission->parent_id !== null && (int) $permission->parent_id > 0)->groupBy('parent_id');
        $groups = [];
        foreach ($permissions->filter(fn ($permission) => $permission->parent_id === null || (int) $permission->parent_id <= 0) as $parent) {
            $groups[] = [
                'id' => (int) $parent->id,
                'name' => (string) $parent->name,
                'children' => $children->get($parent->id, collect())->map(fn ($child): array => ['id' => (int) $child->id, 'name' => (string) $child->name])->values()->all(),
            ];
        }

        return $groups;
    }

    public function resetPassword(int $userId, string $password, string $confirmation, ?string $ip): void
    {
        if (mb_strlen($password) < 8 || mb_strlen($password) > 128) {
            throw new InvalidArgumentException('password_length');
        }
        if (! hash_equals($password, $confirmation)) {
            throw new InvalidArgumentException('password_mismatch');
        }
        $user = $this->user($userId);
        $user->password = $password; // cast `hashed` của User băm một lần
        $user->save();
        // Như màn sửa người dùng: đổi mật khẩu thì đăng xuất mọi thiết bị.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DeveloperAudit::record(['user_id' => $user->id, 'username' => $user->username, 'ip' => $ip], 'Developer Settings: đặt lại mật khẩu tài khoản #'.$user->id);
    }

    public function setStatus(int $userId, bool $active, ?string $ip): void
    {
        $user = $this->user($userId);
        $before = (int) $user->status === 1;
        if ($before === $active) {
            return;
        }
        $user->status = $active ? 1 : 0;
        $user->save();
        if (! $active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        DeveloperAudit::record(['user_id' => $user->id, 'before' => $before, 'after' => $active, 'ip' => $ip], 'Developer Settings: '.($active ? 'kích hoạt' : 'khóa').' tài khoản #'.$user->id);
    }

    /** Cột users.can_edit_published có chưa (đã migrate): chưa có thì công tắc bị khóa và không ghi gì. */
    public function editPublishedReady(): bool
    {
        return Schema::hasColumn('users', 'can_edit_published');
    }

    /** Bật/tắt cho một tài khoản quyền sửa trực tiếp bài đã đăng (chỉ qua Developer Settings, không nằm trong vai trò). */
    public function setEditPublished(int $userId, bool $enabled, ?string $ip): void
    {
        if (! $this->editPublishedReady()) {
            throw new InvalidArgumentException('edit_published_unavailable');
        }
        $user = $this->user($userId);
        $before = (bool) $user->can_edit_published;
        if ($before === $enabled) {
            return;
        }
        // Ghi thẳng bằng truy vấn: cờ không nằm trong $fillable và không được đi qua form người dùng thường.
        DB::table('users')->where('id', $user->id)->update(['can_edit_published' => $enabled]);
        DeveloperAudit::record(['user_id' => $user->id, 'before' => $before, 'after' => $enabled, 'ip' => $ip], 'Developer Settings: '.($enabled ? 'cho phép' : 'thu hồi quyền').' sửa trực tiếp bài đã đăng của tài khoản #'.$user->id);
    }

    /** @param list<mixed> $roleIds */
    public function syncUserRoles(int $userId, array $roleIds, ?string $ip): void
    {
        $user = $this->user($userId);
        $ids = array_values(array_unique(array_map('intval', $roleIds)));
        $roles = Role::query()->whereIn('id', $ids)->get();
        if ($roles->count() !== count($ids)) {
            throw new InvalidArgumentException('role_unknown');
        }
        $before = $user->roles()->pluck('roles.id')->all();
        $user->syncRoles($roles);
        DeveloperAudit::record(['user_id' => $user->id, 'before' => $before, 'after' => $ids, 'ip' => $ip], 'Developer Settings: đổi vai trò tài khoản #'.$user->id);
    }

    /** @param list<mixed> $permissionIds */
    public function saveRolePermissions(int $roleId, array $permissionIds, ?string $ip): void
    {
        $role = Role::query()->find($roleId) ?? throw new InvalidArgumentException('role_unknown');
        $ids = array_values(array_unique(array_map('intval', $permissionIds)));
        $found = Permission::query()->whereIn('id', $ids)->get();
        if ($found->count() !== count($ids)) {
            throw new InvalidArgumentException('permission_unknown');
        }
        $requested = $this->availability->filter($found);
        $before = $role->permissions()->pluck('permissions.id')->all();
        $this->availability->syncRole($role, $requested);
        event(new UpdateRole($role));
        DeveloperAudit::record(['role_id' => $role->id, 'role' => $role->name, 'before' => $before, 'after' => $role->permissions()->pluck('permissions.id')->all(), 'ip' => $ip], 'Developer Settings: cấu hình quyền vai trò '.$role->name);
    }

    private function user(int $id): User
    {
        return User::query()->find($id) ?? throw new InvalidArgumentException('user_unknown');
    }

    private static function roleLabel(string $name): string
    {
        return RolesEnum::tryFrom($name)?->label() ?? $name;
    }
}
