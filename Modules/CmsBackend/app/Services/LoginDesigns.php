<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Option;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CmsBackend\Services\Developer\DeveloperAudit;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Login designs: the look of the sign-in page, independent of the public theme. The controllers are the same for every project; only
 * the view that is shown changes.
 *
 * A design is one folder, Modules/CmsFrontend/resources/views/auth/designs/<key>/ with
 *   login.blade.php  the layout (extends auth.shared.auth-shell and uses the shared partials)
 *   auth.css         its styles (a Vite entry, found by scripts/theme-profiles.mjs: every build carries every design)
 *   design.php       returns ['label' => ..., 'description' => ..., 'transition' => 'push-horizontal'|null]
 * and is found by scanning the folder: adding a design needs no registration (see docs/login-designs/HUONG-DAN-THEM-GIAO-DIEN.md).
 *
 * Which design a project uses is stored per project in the option `cms_login_designs` ({"<project key>": "<design key>"}). A project
 * without a valid choice uses the design its public theme goes with (`login.design`, else the one named like the theme), which is what the sign-in page showed before designs existed.
 * Whatever is stored or missing, the page always resolves to a design that exists and is built: signing in must never fail here.
 */
final class LoginDesigns
{
    public const OPTION = 'cms_login_designs';

    private const REQUEST_MAP = '_cms_login_designs_map';

    private const PREVIEW = '_login_preview_design';

    private const KEY_PATTERN = '/^[a-z][a-z0-9-]*$/D';

    /** @var array<string, array<string, mixed>>|null folder scan, once per process */
    private static ?array $found = null;

    /** @var array{key: string, mtime: int, entries: array<string, true>}|null */
    private static ?array $manifest = null;

    /** @param  Closure|null  $built  fn (string $entry): bool, replaces the manifest check (tests) */
    public function __construct(private ?Closure $built = null) {}

    /**
     * Every design whose folder is complete, with its description and whether its CSS is part of this build.
     *
     * @return array<string, array{key: string, label: string, description: string, transition: ?string, has_transition: bool, entry: string, built: bool}>
     */
    public function all(): array
    {
        $designs = [];
        foreach ($this->scan() as $key => $meta) {
            $designs[$key] = $meta + ['built' => $this->isBuilt($meta['entry'])];
        }

        return $designs;
    }

    /** @return array<string, string> key => label of the designs that can be chosen (complete and built) */
    public function choices(): array
    {
        return array_map(fn (array $design) => $design['label'], array_filter($this->all(), fn (array $design) => $design['built']));
    }

    public function usable(string $key): bool
    {
        $scan = $this->scan();

        return isset($scan[$key]) && $this->isBuilt($scan[$key]['entry']);
    }

    /** The choice stored for a project (null = none, or one that is no longer usable). */
    public function assigned(?string $project = null): ?string
    {
        try {
            $project ??= ProjectContext::key();
        } catch (\Throwable) {
            return null;
        }
        $key = $this->map()[$project] ?? null;

        return is_string($key) && $this->usable($key) ? $key : null;
    }

    /** Design of the current request: preview (Developer), the project's choice, the theme's own design, `default`, any usable one. */
    public function key(): string
    {
        $preview = request()->attributes->get(self::PREVIEW);
        if (is_string($preview) && $this->usable($preview)) {
            return $preview;
        }
        if (($assigned = $this->assigned()) !== null) {
            return $assigned;
        }

        $theme = null;
        try {
            $theme = app(ScreenTheme::class)->key('public');
        } catch (\Throwable) {
            // No database or request yet: the fallbacks below still give a design.
        }
        // A theme may name the login design it goes with (`login.design` in screens.php), for a theme without a design of its own name.
        $themeDesign = $theme !== null ? config('cmsbackend.screens.themes.'.$theme.'.login.design') : null;
        foreach ([$themeDesign, $theme, 'default'] as $candidate) {
            if (is_string($candidate) && $this->usable($candidate)) {
                return $candidate;
            }
        }
        $usable = array_keys($this->choices());

        return $usable[0] ?? 'default';
    }

    /** Blade view of the design of the current request. */
    public function view(): string
    {
        return 'cmsfrontend::auth.designs.'.$this->key().'.login';
    }

    /**
     * Page transition between sign-in and the dashboard. A design that does not say anything about it keeps the theme's setting.
     */
    public function transition(): ?string
    {
        $design = $this->scan()[$this->key()] ?? null;
        if ($design !== null && $design['has_transition']) {
            return $design['transition'];
        }
        $theme = null;
        try {
            $theme = app(ScreenTheme::class)->key('public');
        } catch (\Throwable) {
        }
        $value = $theme !== null ? config('cmsbackend.screens.themes.'.$theme.'.login.transition') : null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** Developer session only (the controller is behind cms.developer): preview one design on this request. */
    public function preview(string $key): void
    {
        if (! $this->usable($key)) {
            throw new InvalidArgumentException('login_error_unknown');
        }
        request()->attributes->set(self::PREVIEW, $key);
    }

    /**
     * Store the design of a project; null removes the choice (the project follows its theme again).
     *
     * @throws InvalidArgumentException with a language key suffix
     */
    public function assign(string $project, ?string $design, ?string $ip): void
    {
        if (! ProjectContext::exists($project)) {
            throw new InvalidArgumentException('login_error_project');
        }
        if ($design !== null && ! $this->usable($design)) {
            throw new InvalidArgumentException('login_error_unknown');
        }
        app(EditorialWorkflow::class)->assertDatabase();

        $old = $this->map()[$project] ?? null;
        DB::transaction(function () use ($project, $design): void {
            $map = $this->map();
            if ($design === null) {
                unset($map[$project]);
            } else {
                $map[$project] = $design;
            }
            Option::query()->updateOrCreate(['key' => self::OPTION], ['value' => json_encode($map, JSON_UNESCAPED_SLASHES), 'autoload' => 1]);
        });
        request()->attributes->remove(self::REQUEST_MAP);

        DeveloperAudit::record([
            'action' => 'login_design',
            'project_key' => $project,
            'old_design' => $old,
            'new_design' => $design,
            'ip' => $ip,
        ], __('admin.developer.log_login_design'));
    }

    /**
     * What the Developer Settings tab shows: every project with its choice and the design it really gets.
     *
     * @return array{designs: array<string, mixed>, projects: list<array<string, mixed>>}
     */
    public function panel(): array
    {
        $designs = $this->all();
        $projects = [];
        foreach (ProjectContext::projects() as $key => $project) {
            $assigned = $this->assigned((string) $key);
            $themeDesign = isset($project['theme']) && $this->usable((string) $project['theme']) ? (string) $project['theme'] : (array_key_first($this->choices()) ?? 'default');
            $projects[] = [
                'key' => (string) $key,
                'label' => (string) ($project['label'] ?? $key),
                'assigned' => $assigned,
                'effective' => $assigned ?? $themeDesign,
                'current' => $this->isCurrentProject((string) $key),
            ];
        }

        return ['designs' => $designs, 'projects' => $projects];
    }

    // ------------------------------------------------------------------ internals

    private function isCurrentProject(string $key): bool
    {
        try {
            return ProjectContext::key() === $key;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string, string> project key => design key, as stored */
    private function map(): array
    {
        $request = request();
        if (! $request->attributes->has(self::REQUEST_MAP)) {
            $map = [];
            try {
                $decoded = json_decode((string) Option::query()->where('key', self::OPTION)->value('value'), true);
                foreach (is_array($decoded) ? $decoded : [] as $project => $design) {
                    if (is_string($project) && is_string($design) && preg_match(self::KEY_PATTERN, $design) === 1) {
                        $map[$project] = $design;
                    }
                }
            } catch (\Throwable) {
                // No database / no options table: nobody has chosen anything.
            }
            $request->attributes->set(self::REQUEST_MAP, $map);
        }

        return $request->attributes->get(self::REQUEST_MAP);
    }

    /** @return array<string, array{key: string, label: string, description: string, transition: ?string, has_transition: bool, entry: string}> */
    private function scan(): array
    {
        if (self::$found !== null) {
            return self::$found;
        }

        $root = module_path('CmsFrontend', 'resources/views/auth/designs');
        $found = [];
        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $key = basename($dir);
            // `_template` and the like are for people, not for the page; a key is a safe view-name segment (lowercase, digits, hyphens).
            if (preg_match(self::KEY_PATTERN, $key) !== 1 || ! is_file($dir.'/login.blade.php') || ! is_file($dir.'/auth.css') || ! is_file($dir.'/design.php')) {
                continue;
            }
            try {
                $meta = (static fn (string $file): mixed => include $file)($dir.'/design.php');
            } catch (\Throwable) {
                Log::warning('Login design "'.$key.'" has an unreadable design.php and is ignored.');

                continue;
            }
            if (! is_array($meta)) {
                continue;
            }
            $transition = $meta['transition'] ?? null;
            $found[$key] = [
                'key' => $key,
                'label' => is_string($meta['label'] ?? null) && $meta['label'] !== '' ? $meta['label'] : $key,
                'description' => is_string($meta['description'] ?? null) ? $meta['description'] : '',
                'transition' => is_string($transition) && $transition !== '' ? $transition : null,
                'has_transition' => array_key_exists('transition', $meta),
                'entry' => 'Modules/CmsFrontend/resources/views/auth/designs/'.$key.'/auth.css',
            ];
        }
        ksort($found);

        return self::$found = $found;
    }

    /**
     * The CSS of the design is part of this build (Vite manifest), or Vite runs in dev (public/hot). Without a manifest at all nothing
     * can be checked and the design is allowed: the page then behaves exactly as before.
     */
    private function isBuilt(string $entry): bool
    {
        if ($this->built !== null) {
            return (bool) ($this->built)($entry);
        }
        if (is_file(public_path('hot'))) {
            return true;
        }
        $file = public_path('build/manifest.json');
        if (! is_file($file)) {
            return true;
        }
        $mtime = (int) filemtime($file);
        if (self::$manifest === null || self::$manifest['key'] !== $file || self::$manifest['mtime'] !== $mtime) {
            $decoded = json_decode((string) file_get_contents($file), true);
            self::$manifest = ['key' => $file, 'mtime' => $mtime, 'entries' => is_array($decoded) ? array_fill_keys(array_keys($decoded), true) : []];
        }

        return isset(self::$manifest['entries'][$entry]);
    }
}
