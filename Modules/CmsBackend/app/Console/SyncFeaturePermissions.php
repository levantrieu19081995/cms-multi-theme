<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\FeatureRegistry;
use Spatie\Permission\Models\Permission;

/**
 * Tạo các quyền mà Feature Registry khai báo cho từng chức năng nhưng chưa có trong bảng permissions (Developer Settings báo "chưa có trong DB").
 * Chỉ THÊM quyền còn thiếu (tên = nhãn của PermissionsEnum, cột slug, quan hệ cha–con theo PermissionsEnum::parentChildren); không gán cho vai trò nào,
 * không đổi quyền đã có. Quyền mới vẫn phải được bật trong Developer Settings > Quản lý phân quyền (manifest) rồi gán cho vai trò.
 */
class SyncFeaturePermissions extends Command
{
    protected $signature = 'features:sync-permissions {--dry-run : Chỉ liệt kê quyền sẽ tạo, không ghi database}';

    protected $description = 'Tạo các quyền của chức năng hệ thống (Feature Registry) còn thiếu trong bảng permissions.';

    public function handle(FeatureRegistry $registry): int
    {
        $dry = (bool) $this->option('dry-run');
        $cases = [];
        foreach (PermissionsEnum::cases() as $case) {
            $cases[$case->value] = $case;
        }
        $parentOf = [];
        foreach (PermissionsEnum::parentChildren() as $parentLabel => $children) {
            foreach ($children as $childLabel) {
                $parentOf[$childLabel] = $parentLabel;
            }
        }
        $hasSlug = Schema::hasColumn('permissions', 'slug');
        $hasParent = Schema::hasColumn('permissions', 'parent_id');
        $created = 0;

        foreach ($registry->all() as $key => $definition) {
            foreach ($definition->permissions() as $slug) {
                $case = $cases[$slug] ?? null;
                if ($case === null) {
                    $this->warn("[$key] $slug: không có trong PermissionsEnum, bỏ qua.");

                    continue;
                }
                $exists = Permission::query()->where('name', $case->label())->where('guard_name', 'web')->exists();
                if ($exists) {
                    continue;
                }
                $this->line(($dry ? '[thử] ' : '')."[$key] tạo quyền $slug (\"{$case->label()}\")");
                $created++;
                if ($dry) {
                    continue;
                }
                $attributes = ['name' => $case->label(), 'guard_name' => 'web'];
                if ($hasSlug) {
                    $attributes['slug'] = $slug;
                }
                $permission = Permission::query()->create($attributes);
                if ($hasParent && isset($parentOf[$case->label()])) {
                    $parent = Permission::query()->where('name', $parentOf[$case->label()])->where('guard_name', 'web')->first();
                    if ($parent !== null) {
                        $permission->parent_id = $parent->id;
                        $permission->save();
                    }
                }
            }
        }
        $this->info($created === 0 ? 'Mọi quyền của các chức năng đã có trong database.' : ($dry ? "Sẽ tạo $created quyền." : "Đã tạo $created quyền. Bật chúng trong Developer Settings > Quản lý phân quyền rồi gán cho vai trò."));

        return self::SUCCESS;
    }
}
