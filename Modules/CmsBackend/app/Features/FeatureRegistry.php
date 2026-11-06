<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Features;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Features\Contracts\FeatureDefinition;
use Modules\CmsBackend\Support\ModuleUrl;
use ReflectionClass;
use Throwable;

/**
 * Nơi duy nhất biết hệ thống có những chức năng nào và chức năng nào đang bật.
 *
 *  - Code definition (Features/Definitions/*.php) = hệ thống CÓ KHẢ NĂNG gì. Tự nhận mọi class triển khai FeatureDefinition.
 *  - Bảng feature_settings = bản triển khai HIỆN TẠI đang bật gì. Chưa có bản ghi thì dùng enabledByDefault() của definition.
 *
 * Danh sách definition được cache (chỉ lưu tên class, không quét lại thư mục mỗi request) và tự làm mới khi cache ứng dụng bị xóa.
 * Không có điều kiện riêng cho chức năng cụ thể nào ở đây: thêm chức năng chỉ cần thêm một definition.
 */
final class FeatureRegistry
{
    public const STATUS_OK = 'ok';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_MISSING = 'missing';

    private const CACHE_DEFINITIONS = 'cms.features.definitions';

    private const CACHE_STATES = 'cms.features.states';

    /** @var array<string, FeatureDefinition>|null */
    private ?array $definitions = null;

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, bool>|null enabled theo feature_key, chỉ những khóa có bản ghi */
    private ?array $states = null;

    /** @var array<string, string>|null slug quyền → feature_key */
    private ?array $permissionOwners = null;

    public function __construct(
        private ?string $directory = null,
        private ?string $namespace = null,
    ) {
        $this->directory ??= __DIR__.'/Definitions';
        $this->namespace ??= __NAMESPACE__.'\\Definitions';
    }

    // ------------------------------------------------------------------ definition

    /** @return array<string, FeatureDefinition> theo key */
    public function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }
        $classes = $this->cachedClasses();
        $this->definitions = [];
        foreach ($classes as $key => $class) {
            $this->definitions[$key] = new $class;
        }

        return $this->definitions;
    }

    public function find(string $key): ?FeatureDefinition
    {
        return $this->all()[$key] ?? null;
    }

    /** Lỗi phát hiện khi nhận definition (trùng key, key sai định dạng…). */
    public function errors(): array
    {
        $this->all();

        return $this->errors;
    }

    private function signature(): string
    {
        $parts = [];
        foreach (glob(rtrim((string) $this->directory, '/\\').DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
            $parts[] = basename($file).':'.filemtime($file);
        }
        sort($parts);

        return md5(implode('|', $parts));
    }

    /** @return array<string, class-string<FeatureDefinition>> */
    private function cachedClasses(): array
    {
        try {
            $cached = Cache::get(self::CACHE_DEFINITIONS);
            // Chữ ký thư mục: thêm/xóa/sửa file Definition thì tự quét lại, không cần xóa cache khi triển khai.
            if (is_array($cached) && isset($cached['classes']) && ($cached['signature'] ?? null) === $this->signature()) {
                $this->errors = $cached['errors'];

                return $cached['classes'];
            }
        } catch (Throwable) {
            // cache không dùng được: quét trực tiếp
        }
        $found = $this->discover();
        $this->errors = $found['errors'];
        $found['signature'] = $this->signature();
        try {
            Cache::forever(self::CACHE_DEFINITIONS, $found);
        } catch (Throwable) {
            // không cache được vẫn chạy đúng
        }
        foreach ($found['errors'] as $error) {
            Log::error('FeatureRegistry: '.$error);
        }

        return $found['classes'];
    }

    /**
     * Quét thư mục Definitions. Chỉ nhận class triển khai FeatureDefinition; trùng key giữ bản đầu và ghi nhận lỗi.
     *
     * @return array{classes: array<string, class-string<FeatureDefinition>>, errors: list<string>}
     */
    public function discover(): array
    {
        $classes = [];
        $errors = [];
        $files = glob(rtrim((string) $this->directory, '/\\').DIRECTORY_SEPARATOR.'*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $class = $this->namespace.'\\'.basename($file, '.php');
            if (! class_exists($class)) {
                require_once $file; // thư mục thử nghiệm ngoài autoload
            }
            if (! class_exists($class) || ! is_subclass_of($class, FeatureDefinition::class) || ! (new ReflectionClass($class))->isInstantiable()) {
                continue;
            }
            $key = (new $class)->key();
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $key) !== 1) {
                $errors[] = "Mã chức năng \"$key\" của $class không hợp lệ (chỉ chữ thường, số, gạch ngang).";

                continue;
            }
            if (isset($classes[$key])) {
                $errors[] = "Trùng mã chức năng \"$key\": {$classes[$key]} và $class.";

                continue;
            }
            $classes[$key] = $class;
        }

        return ['classes' => $classes, 'errors' => $errors];
    }

    // ------------------------------------------------------------------ trạng thái bật/tắt

    public function isEnabled(string $key): bool
    {
        $definition = $this->find($key);
        if ($definition === null) {
            return false; // không có definition = không có chức năng này
        }

        return $this->states()[$key] ?? $definition->enabledByDefault();
    }

    /** Có bản ghi cấu hình riêng trong DB (khác với dùng mặc định)? */
    public function hasOverride(string $key): bool
    {
        return array_key_exists($key, $this->states());
    }

    public function setEnabled(string $key, bool $enabled, ?int $updatedBy = null): void
    {
        if ($this->find($key) === null) {
            throw new \InvalidArgumentException("Chức năng \"$key\" không tồn tại.");
        }
        $now = now();
        $row = DB::table('feature_settings')->where('feature_key', $key);
        if ($row->exists()) {
            $row->update(['enabled' => $enabled, 'updated_by' => $updatedBy, 'updated_at' => $now]);
        } else {
            DB::table('feature_settings')->insert(['feature_key' => $key, 'enabled' => $enabled, 'updated_by' => $updatedBy, 'created_at' => $now, 'updated_at' => $now]);
        }
        $this->forgetStates();
    }

    public function forgetStates(): void
    {
        $this->states = null;
        try {
            Cache::forget(self::CACHE_STATES);
        } catch (Throwable) {
            // bỏ qua
        }
    }

    /** @return array<string, bool> */
    private function states(): array
    {
        if ($this->states !== null) {
            return $this->states;
        }
        try {
            $cached = Cache::get(self::CACHE_STATES);
            if (is_array($cached)) {
                return $this->states = $cached;
            }
        } catch (Throwable) {
            // đọc DB trực tiếp
        }
        try {
            $states = DB::table('feature_settings')->pluck('enabled', 'feature_key')->map(fn ($v) => (bool) $v)->all();
        } catch (Throwable) {
            // Chưa có bảng (chưa migrate): dùng mặc định của definition, không cache để nhận bảng ngay khi được tạo.
            return $this->states = [];
        }
        try {
            Cache::forever(self::CACHE_STATES, $states);
        } catch (Throwable) {
            // bỏ qua
        }

        return $this->states = $states;
    }

    // ------------------------------------------------------------------ quyền

    public function featureOfPermission(string $slug): ?string
    {
        if ($this->permissionOwners === null) {
            $this->permissionOwners = [];
            foreach ($this->all() as $key => $definition) {
                foreach ($definition->permissions() as $permission) {
                    $this->permissionOwners[$permission] ??= $key;
                }
            }
        }

        return $this->permissionOwners[$slug] ?? null;
    }

    /** Quyền thuộc một chức năng đang tắt thì không khả dụng. Quyền không thuộc chức năng nào: không bị chặn. */
    public function permissionBlocked(string $slug): bool
    {
        $feature = $this->featureOfPermission($slug);

        return $feature !== null && ! $this->isEnabled($feature);
    }

    // ------------------------------------------------------------------ route / URL

    /** @return array{key: string, name: string, route: string}|null */
    public function target(string $key, string $targetKey): ?array
    {
        foreach ($this->find($key)?->publicTargets() ?? [] as $target) {
            if ($target['key'] === $targetKey) {
                return $target;
            }
        }

        return null;
    }

    private function routeExists(?string $name): bool
    {
        return $name !== null && $name !== '' && Route::has(ModuleUrl::name($name));
    }

    public function adminUrl(string $key): ?string
    {
        $route = $this->find($key)?->adminRoute();

        return $this->routeExists($route) ? ModuleUrl::route((string) $route) : null;
    }

    /**
     * Phân giải điểm đến công khai của một chức năng cho menu: tìm chức năng → kiểm tra bật → tìm target → kiểm tra route → sinh URL.
     *
     * @return array{status: string, url: ?string, target: ?array{key: string, name: string, route: string}}
     */
    public function resolve(string $key, string $targetKey): array
    {
        if ($this->find($key) === null) {
            return ['status' => self::STATUS_MISSING, 'url' => null, 'target' => null];
        }
        $target = $this->target($key, $targetKey);
        if ($target === null || ! $this->routeExists($target['route'])) {
            return ['status' => self::STATUS_MISSING, 'url' => null, 'target' => $target];
        }
        if (! $this->isEnabled($key)) {
            return ['status' => self::STATUS_DISABLED, 'url' => null, 'target' => $target];
        }

        return ['status' => self::STATUS_OK, 'url' => ModuleUrl::route($target['route']), 'target' => $target];
    }

    public function url(string $key, string $targetKey): ?string
    {
        return $this->resolve($key, $targetKey)['url'];
    }

    // ------------------------------------------------------------------ báo cáo (Developer Settings, quản lý menu)

    /** Số mục menu đang dùng từng chức năng. @return array<string, int> */
    public function menuUsage(): array
    {
        try {
            if (! Schema::hasColumn('menu_items', 'feature_key')) {
                return [];
            }

            return DB::table('menu_items')->where('type', 'feature')->whereNotNull('feature_key')
                ->select('feature_key', DB::raw('COUNT(*) as total'))->groupBy('feature_key')->pluck('total', 'feature_key')->map(fn ($n) => (int) $n)->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function report(): array
    {
        $usage = $this->menuUsage();
        $enumSlugs = array_map(fn (PermissionsEnum $c) => $c->value, PermissionsEnum::cases());
        $seeded = $this->seededPermissionSlugs();
        $rows = [];
        foreach ($this->all() as $key => $definition) {
            $adminOk = $definition->adminRoute() === null || $this->routeExists($definition->adminRoute());
            $targets = array_map(fn (array $t) => $t + ['ok' => $this->routeExists($t['route']), 'url' => $this->routeExists($t['route']) ? ModuleUrl::route($t['route']) : null], $definition->publicTargets());
            $declared = $definition->permissions();
            $rows[] = [
                'key' => $key,
                'name' => $definition->name(),
                'description' => $definition->description(),
                'icon' => $definition->icon(),
                'enabled' => $this->isEnabled($key),
                'default_enabled' => $definition->enabledByDefault(),
                'overridden' => $this->hasOverride($key),
                'admin_route' => $definition->adminRoute(),
                'admin_url' => $this->adminUrl($key),
                'admin_ok' => $adminOk,
                'targets' => $targets,
                'permissions_declared' => count($declared),
                'permissions_missing_code' => array_values(array_diff($declared, $enumSlugs)),
                'permissions_missing_db' => $seeded === null ? [] : array_values(array_diff(array_intersect($declared, $enumSlugs), $seeded)),
                'routes_ok' => $adminOk && ! in_array(false, array_column($targets, 'ok'), true),
                'menu_count' => $usage[$key] ?? 0,
            ];
        }

        return $rows;
    }

    /** @return list<string>|null slug các quyền đã có trong DB; null nếu không đọc được */
    private function seededPermissionSlugs(): ?array
    {
        try {
            if (! Schema::hasColumn('permissions', 'slug')) {
                return null;
            }

            return DB::table('permissions')->whereNotNull('slug')->pluck('slug')->all();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Dữ liệu cho bộ chọn của quản lý menu: mọi chức năng có điểm đến công khai (kể cả đang tắt, kèm cờ).
     *
     * @return list<array{key: string, name: string, icon: string, enabled: bool, targets: list<array{key: string, name: string}>}>
     */
    public function menuChoices(): array
    {
        $choices = [];
        foreach ($this->all() as $key => $definition) {
            $targets = array_map(fn (array $t) => ['key' => $t['key'], 'name' => $t['name']], $definition->publicTargets());
            if ($targets === []) {
                continue;
            }
            $choices[] = ['key' => $key, 'name' => $definition->name(), 'icon' => $definition->icon(), 'enabled' => $this->isEnabled($key), 'targets' => $targets];
        }

        return $choices;
    }
}
