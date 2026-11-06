<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema; // <-- thêm dòng này
use Spatie\Permission\Models\Permission as SpatiePermission;


class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    DB::transaction(function () {
      // Begin transaction

      // Main project
      // foreach (PermissionsEnum::cases() as $permission) {
      //   Permission::findOrCreate($permission->label());
      // }
      $this->render(PermissionsEnum::class);

      // Module projects
      $modules = Module::allEnabled();
      foreach ($modules as $module) {
        $moduleName = $module->getName();
        $enumClass = 'Modules\\' . $moduleName . '\\Enums\\PermissionsEnum';
        if (!enum_exists($enumClass)) {
          // throw new \Exception($enumClass . ' class not found');
          continue;
        }
        $this->render($enumClass);
      }

      // Commit transaction
    });
  }

  // private function render($enumClass){
  //   foreach ($enumClass::cases() as $permission) {
  //     Permission::findOrCreate($permission->label());
  //   }

  //   if (method_exists($enumClass, 'parentChildren')) {
  //     $parentChildren = $enumClass::parentChildren();

  //     foreach ($parentChildren as $parentLabel => $childrenLabels) {
  //       // Tìm enum từ label
  //       $parentEnum = collect($enumClass::cases())->firstWhere(fn($case) => $case->label() === $parentLabel);

  //       if (!$parentEnum) {
  //         continue; // Nếu không tìm thấy enum từ label, bỏ qua
  //       }

  //       // Tạo hoặc tìm quyền cha
  //       $parentPermission = Permission::firstOrCreate([
  //         'name' => $parentEnum->label(),
  //         'guard_name' => 'web',
  //       ]);

  //       foreach ($childrenLabels as $childLabel) {
  //         // Tìm enum từ label cho quyền con
  //         $childEnum = collect($enumClass::cases())->firstWhere(fn($case) => $case->label() === $childLabel);

  //         if (!$childEnum) {
  //           continue;
  //         }

  //         // Tạo hoặc tìm quyền con
  //         $childPermission = Permission::firstOrCreate([
  //           'name' => $childEnum->label(),
  //           'guard_name' => 'web',
  //         ]);

  //         // Cập nhật parent_id cho quyền con
  //         if ($childPermission->parent_id !== $parentPermission->id) {
  //           $childPermission->parent_id = $parentPermission->id;
  //           $childPermission->save();
  //         }
  //       }
  //     }
  //   }
  // }

  private function render($enumClass)
  {
    // 1) Seed toàn bộ case
    foreach ($enumClass::cases() as $case) {
      $label = method_exists($case, 'label') ? $case->label() : $case->value; // name cũ
      $slug  = $case->value; // ví dụ: 'khaosat-manager'

      // name giữ nguyên = label
      $perm = SpatiePermission::firstOrCreate([
        'name'       => $label,
        'guard_name' => 'web',
      ]);

      // Bổ sung cập nhật cột 'slug' (nếu migration đã thêm)
      if (Schema::hasColumn($perm->getTable(), 'slug')) {
        if ($perm->slug !== $slug) {
          $perm->slug = $slug;
          $perm->save();
        }
      }
    }

    // 2) Seed quan hệ parent-children (giữ nguyên theo label), đồng thời set slug
    if (method_exists($enumClass, 'parentChildren')) {
      $parentChildren = $enumClass::parentChildren();

      foreach ($parentChildren as $parentLabel => $childrenLabels) {
        // tìm enum cha theo label
        $parentEnum = collect($enumClass::cases())
          ->firstWhere(fn($c) => (method_exists($c, 'label') ? $c->label() : $c->value) === $parentLabel);
        if (!$parentEnum) continue;

        $parent = SpatiePermission::firstOrCreate([
          'name'       => $parentLabel,
          'guard_name' => 'web',
        ]);

        // set slug cho cha nếu có cột
        if (Schema::hasColumn($parent->getTable(), 'slug')) {
          if ($parent->slug !== $parentEnum->value) {
            $parent->slug = $parentEnum->value;
            $parent->save();
          }
        }

        foreach ($childrenLabels as $childLabel) {
          $childEnum = collect($enumClass::cases())
            ->firstWhere(fn($c) => (method_exists($c, 'label') ? $c->label() : $c->value) === $childLabel);
          if (!$childEnum) continue;

          $child = SpatiePermission::firstOrCreate([
            'name'       => $childLabel,
            'guard_name' => 'web',
          ]);

          // set slug cho con nếu có cột
          if (Schema::hasColumn($child->getTable(), 'slug')) {
            if ($child->slug !== $childEnum->value) {
              $child->slug = $childEnum->value;
              $child->save();
            }
          }

          // Nếu có cột parent_id thì gán quan hệ (tuỳ DB của bạn)
          if (Schema::hasColumn($child->getTable(), 'parent_id')) {
            if ($child->parent_id !== $parent->id) {
              $child->parent_id = $parent->id;
              $child->save();
            }
          }
        }
      }
    }
  }
}
