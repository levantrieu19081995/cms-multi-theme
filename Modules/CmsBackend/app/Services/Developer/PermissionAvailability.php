<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * The single place that decides which permissions this deployment may use ("available"). Middleware, the User model, the
 * menu, the role screens and Developer Settings all ask this class; none of them reads the manifest itself.
 *
 * Rules
 *  - No manifest: every existing permission is available (behaviour before this feature).
 *  - Valid manifest: only the listed permission slugs are available. A permission missing from the list (for example one
 *    added to the database after the manifest was written) is NOT available until the Developer enables it.
 *  - Invalid manifest (bad structure, signature, project, or no key): protective mode, only the core permissions are
 *    available. The file is never rewritten automatically.
 *  - A child permission is available only while its parent is available.
 *  - A permission owned by a feature that is switched off in the Feature Registry is NOT available.
 *  - The core permissions are always available (they keep user and role administration reachable).
 *  - Unknown permissions (not in the permissions table) are left to the normal checks.
 */
final class PermissionAvailability
{
    /**
     * Permissions that can never be switched off: user administration and role administration ("Quản lý vai trò" is stored
     * under the slug category-manager). Developer Settings itself does not depend on any permission (sign-in + role + key).
     */
    public const CORE = ['user-manager', 'category-manager'];

    /** @var array{state: string, reason: ?string, allowed: ?string[], updated_at: ?string}|null */
    private ?array $manifest = null;

    /** @var array{bySlug: array<string, array{id: int, name: string, parent: ?string}>, byName: array<string, string>, byId: array<int, string>}|null */
    private ?array $index = null;

    private bool $warned = false;

    public function __construct(private PermissionManifest $files) {}

    // ------------------------------------------------------------------ decisions

    public function mode(): string
    {
        return $this->manifest()['state'];
    }

    /** @return array{state: string, reason: ?string, allowed: ?string[], updated_at: ?string} */
    public function manifest(): array
    {
        if ($this->manifest === null) {
            $this->manifest = $this->files->read();
            if ($this->manifest['state'] === PermissionManifest::INVALID && ! $this->warned) {
                $this->warned = true;
                Log::warning('Permission manifest is invalid ('.$this->manifest['reason'].'); protective mode: only core permissions are available.');
            }
        }

        return $this->manifest;
    }

    /** @param  Model|string|int  $permission a Permission model, a name (label), a slug or an id */
    public function isAvailable(mixed $permission): bool
    {
        $slug = $this->slugOf($permission);

        return $slug === null ? true : $this->isAvailableSlug($slug);
    }

    public function isAvailableSlug(string $slug): bool
    {
        if (in_array($slug, self::CORE, true)) {
            return true;
        }
        // Chức năng đang tắt trong Feature Registry: quyền nghiệp vụ của nó không khả dụng.
        if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->permissionBlocked($slug)) {
            return false;
        }
        $manifest = $this->manifest();
        if ($manifest['state'] === PermissionManifest::ABSENT) {
            return true;
        }
        if ($manifest['state'] === PermissionManifest::INVALID) {
            return false;
        }
        $allowed = (array) $manifest['allowed'];
        if (! in_array($slug, $allowed, true)) {
            return false;
        }
        $parent = $this->index()['bySlug'][$slug]['parent'] ?? null;

        return $parent === null || in_array($parent, self::CORE, true) || in_array($parent, $allowed, true);
    }

    /**
     * Keep only the available permissions of a collection of Permission models.
     *
     * @param  Collection<int, Model>  $permissions
     * @return Collection<int, Model>
     */
    public function filter(Collection $permissions): Collection
    {
        return $permissions->filter(fn ($permission) => $this->isAvailable($permission))->values();
    }

    /** @return int[] ids of the permissions that are not available now */
    public function unavailableIds(): array
    {
        $out = [];
        foreach ($this->index()['bySlug'] as $slug => $row) {
            if (! $this->isAvailableSlug($slug)) {
                $out[] = $row['id'];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ role screens

    /**
     * Replace the permissions of a role with the requested ones, refusing unavailable permissions and keeping the ones that
     * are hidden at the moment (the pivot rows of unavailable permissions are never removed by an Admin's edit).
     *
     * @param  Collection<int, Permission>  $requested
     */
    public function syncRole(Role $role, Collection $requested): void
    {
        if ($requested->contains(fn ($permission) => ! $this->isAvailable($permission))) {
            abort(403, __('admin.developer.permission_not_available'));
        }
        $hidden = $role->permissions()->get()->reject(fn ($permission) => $this->isAvailable($permission));
        $role->syncPermissions($requested->concat($hidden)->unique('id')->values());
    }

    // ------------------------------------------------------------------ developer screen

    /**
     * Rows for Developer Settings: every permission with its parent and its own (manifest) and effective availability.
     *
     * @return array{total: int, available: int, disabled: int, groups: array<int, array<string, mixed>>}
     */
    public function overview(): array
    {
        $rows = $this->index()['bySlug'];
        $manifest = $this->manifest();
        // Switch positions: the manifest's list; nothing but core in protective mode; everything when there is no manifest.
        $listed = match ($manifest['state']) {
            PermissionManifest::VALID => (array) $manifest['allowed'],
            PermissionManifest::INVALID => [],
            default => null,
        };
        $groups = [];
        $available = 0;
        foreach ($rows as $slug => $row) {
            if ($row['parent'] !== null) {
                continue;
            }
            $items = [$this->item($slug, $row, true, $listed)];
            foreach ($rows as $childSlug => $child) {
                if ($child['parent'] === $slug) {
                    $items[] = $this->item($childSlug, $child, false, $listed);
                }
            }
            foreach ($items as $item) {
                $available += $item['effective'] ? 1 : 0;
            }
            // Chức năng hệ thống (Feature Registry) sở hữu nhóm quyền này, để Developer thấy quyền nào thuộc chức năng nào.
            $registry = app(\Modules\CmsBackend\Features\FeatureRegistry::class);
            $owner = $registry->featureOfPermission($slug);
            $feature = $owner === null ? null : ['key' => $owner, 'name' => $registry->find($owner)?->name() ?? $owner, 'icon' => $registry->find($owner)?->icon() ?? 'ri-apps-2-line'];
            $groups[] = ['id' => $row['id'], 'code' => $slug, 'name' => $row['name'], 'core' => in_array($slug, self::CORE, true), 'feature' => $feature, 'permissions' => $items];
        }

        return ['total' => count($rows), 'available' => $available, 'disabled' => count($rows) - $available, 'groups' => $groups];
    }

    /**
     * Validate and store the list of available permission slugs.
     *
     * @param  string[]  $slugs
     * @return array{before: int, after: int, enabled: string[], disabled: string[]}
     *
     * @throws InvalidArgumentException message = lang key under admin.developer.
     */
    public function save(array $slugs, ?string $ip): array
    {
        $known = $this->index()['bySlug'];
        $slugs = array_values(array_unique(array_map('strval', $slugs)));
        foreach ($slugs as $slug) {
            if (! isset($known[$slug])) {
                throw new InvalidArgumentException('error_unknown_permission');
            }
        }
        $chosen = array_values(array_unique(array_merge($slugs, self::CORE)));
        foreach ($chosen as $slug) {
            $parent = $known[$slug]['parent'] ?? null;
            if ($parent !== null && ! in_array($parent, $chosen, true)) {
                throw new InvalidArgumentException('error_parent_off');
            }
        }
        if (! $this->files->hasKey()) {
            throw new InvalidArgumentException('error_no_manifest_key');
        }

        $before = $this->effectiveSlugs();
        $this->files->write($chosen);
        $this->forget();
        $after = $this->effectiveSlugs();

        return $this->recorded('save', $before, $after, $ip);
    }

    /** @return array{before: int, after: int, enabled: string[], disabled: string[]} */
    public function restore(?string $ip): array
    {
        $before = $this->effectiveSlugs();
        try {
            $this->files->restore();
        } catch (Throwable) {
            throw new InvalidArgumentException('error_no_backup');
        }
        $this->forget();
        $after = $this->effectiveSlugs();

        return $this->recorded('restore', $before, $after, $ip);
    }

    /** @return string[] */
    public function effectiveSlugs(): array
    {
        return array_values(array_filter(array_keys($this->index()['bySlug']), fn (string $slug) => $this->isAvailableSlug($slug)));
    }

    /** Forget what was read in this request (after a save) and the menu caches that were built from permissions. */
    public function forget(): void
    {
        $this->manifest = null;
        $this->index = null;
        $this->warned = false;
    }

    // ------------------------------------------------------------------ internals

    /** @param  string[]  $before @param  string[]  $after */
    private function recorded(string $action, array $before, array $after, ?string $ip): array
    {
        // Menu caches only hold the unfiltered menu; they are dropped anyway so nothing built from permissions survives.
        foreach (['cms_twin_menu', 'twin_menu'] as $key) {
            Cache::forget($key);
        }
        $result = ['before' => count($before), 'after' => count($after), 'enabled' => array_values(array_diff($after, $before)), 'disabled' => array_values(array_diff($before, $after))];
        // Actor (developer session), project, address, time (the log row), counts, the permissions switched. No password, hash or key.
        DeveloperAudit::record([
            'action' => $action,
            'project_key' => $this->files->projectKey(),
            'ip' => $ip,
            'before' => $result['before'],
            'after' => $result['after'],
            'enabled' => $result['enabled'],
            'disabled' => $result['disabled'],
        ], __('admin.developer.log_'.$action));

        return $result;
    }

    /** @param  ?string[]  $listed */
    private function item(string $slug, array $row, bool $isParent, ?array $listed): array
    {
        $core = in_array($slug, self::CORE, true);

        return [
            'code' => $slug,
            'name' => $row['name'],
            'description' => null,
            'is_parent' => $isParent,
            'core' => $core,
            // own switch: what the manifest lists (everything when there is no manifest)
            'listed' => $core || $listed === null || in_array($slug, $listed, true),
            // what is really in force (also needs the parent, and protective mode)
            'effective' => $this->isAvailableSlug($slug),
            // switched off because the owning feature is off in the Feature Registry (not a manifest decision)
            'feature_blocked' => app(\Modules\CmsBackend\Features\FeatureRegistry::class)->permissionBlocked($slug),
            'parent' => $row['parent'],
        ];
    }

    private function slugOf(mixed $permission): ?string
    {
        $index = $this->index();
        if ($permission instanceof \BackedEnum) {
            $permission = $permission->value;
        }
        if (! $permission instanceof Model && ! is_string($permission) && ! is_int($permission)) {
            return null;
        }
        if ($permission instanceof Model) {
            $slug = $permission->getAttribute('slug');
            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
            $permission = (string) $permission->getAttribute('name');
        }
        if (is_int($permission)) {
            return $index['byId'][$permission] ?? null;
        }

        return $index['byName'][$permission] ?? (isset($index['bySlug'][$permission]) ? $permission : null);
    }

    /** @return array{bySlug: array<string, array{id: int, name: string, parent: ?string}>, byName: array<string, string>, byId: array<int, string>} */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }
        $index = ['bySlug' => [], 'byName' => [], 'byId' => []];
        try {
            $all = Permission::query()->orderBy('id')->get(['id', 'parent_id', 'name', 'slug']);
            $slugById = $all->pluck('slug', 'id');
            foreach ($all as $permission) {
                $slug = (string) $permission->slug;
                $index['bySlug'][$slug] = ['id' => (int) $permission->id, 'name' => (string) $permission->name, 'parent' => $permission->parent_id !== null ? ($slugById[$permission->parent_id] ?? null) : null];
                $index['byName'][(string) $permission->name] = $slug;
                $index['byId'][(int) $permission->id] = $slug;
            }
        } catch (Throwable) {
            // No permissions table yet (before the migrations): nothing is restricted.
        }

        return $this->index = $index;
    }
}
