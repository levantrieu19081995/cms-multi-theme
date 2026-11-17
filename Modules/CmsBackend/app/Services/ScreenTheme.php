<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CmsBackend\Support\AdminTheme;
use Modules\CmsBackend\Support\AssetProfile;
use Modules\CmsBackend\Support\ProjectContext;

final class ScreenTheme
{
    public const OPTIONS = ['public' => 'cms_public_theme'];

    private const REQUEST_OPTIONS = '_cms_screen_options';

    private const REQUEST_PATHS = '_cms_screen_paths';

    /** @return array<string, string> */
    public function choices(string $surface): array
    {
        $this->assertSurface($surface);
        if ($surface === 'admin') {
            return ['default' => 'Mặc định (giao diện hiện tại)'];
        }
        $choices = [];
        foreach (config('cmsbackend.screens.themes', []) as $key => $theme) {
            // A single-theme build only has the assets of that theme (AssetProfile): the others cannot be chosen.
            if (preg_match('/^[a-z][a-z0-9-]*$/D', (string) $key) && in_array($surface, $theme['surfaces'] ?? [], true) && AssetProfile::allows((string) $key)) {
                $choices[$key] = $theme['label'] ?? $key;
            }
        }

        return $choices;
    }

    public function key(string $surface): string
    {
        $this->assertSurface($surface);
        if ($surface === 'admin') {
            return 'default';
        }
        // Homepage Layout Manager preview (Developer session only, set by HomepageLayoutController::frame): render one registered theme.
        $preview = request()->attributes->get('_homepage_preview_theme');
        if (is_string($preview) && array_key_exists($preview, $this->choices($surface))) {
            return $preview;
        }
        // A fixed project (CMS_PROJECT_KEY) owns its theme: the stored administration choice is neither read nor changed.
        $request = request();
        $options = function () use ($request): array {
            if (! $request->attributes->has(self::REQUEST_OPTIONS)) {
                $request->attributes->set(self::REQUEST_OPTIONS, Option::query()->whereIn('key', array_values(self::OPTIONS))->pluck('value', 'key')->all());
            }

            return $request->attributes->get(self::REQUEST_OPTIONS);
        };
        $projectTheme = ProjectContext::fixedTheme();
        if ($projectTheme !== null) {
            // ...except that it may offer more than one theme over the same data (ProjectContext::fixedThemeChoices): then the
            // stored choice counts when it is one of those themes and is built here.
            $stored = null;
            if (count(ProjectContext::fixedThemeChoices()) > 1) {
                try {
                    $stored = $options()[self::OPTIONS[$surface]] ?? null;
                } catch (\Throwable) {
                    // No database (yet): the project's own theme below.
                }
            }
            if (is_string($stored) && $stored !== $projectTheme
                && in_array($stored, ProjectContext::fixedThemeChoices(), true) && array_key_exists($stored, $this->choices($surface))) {
                return $stored;
            }
            if (array_key_exists($projectTheme, $this->choices($surface))) {
                return $projectTheme;
            }
        }
        $key = $options()[self::OPTIONS[$surface]] ?? 'default';
        if (array_key_exists($key, $this->choices($surface))) {
            return $key;
        }

        // Stored theme unknown, or not built in this deployment: use the built theme. The stored option is never rewritten.
        $selectable = AssetProfile::selectable();
        $fallback = AssetProfile::theme() ?? (in_array('default', $selectable, true) ? 'default' : ($selectable[0] ?? 'default'));
        if (AssetProfile::locked() && ! $request->attributes->has('_cms_theme_mismatch_logged')) {
            $request->attributes->set('_cms_theme_mismatch_logged', true);
            Log::warning('Stored public theme "'.$key.'" has no assets in this build; using "'.$fallback.'" (source: '.AssetProfile::resolve()['source'].'). The database was not changed.');
        }

        return $fallback;
    }

    private function assertSurface(string $surface): void
    {
        if (! in_array($surface, ['admin', 'public'], true)) {
            throw new InvalidArgumentException('Unknown screen surface.');
        }
    }

    public function view(string $surface, string $screen): string
    {
        $this->assertSurface($surface);
        if (! preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/D', $screen)) {
            throw new InvalidArgumentException('Invalid screen name.');
        }

        return ($surface === 'admin' ? 'cmsbackend' : 'cmsfrontend').'::screens.'.$screen;
    }

    /**
     * Folders under screens/ searched for a view of a theme, in order: the theme itself, the shared layer it declares (`extends`, a
     * folder named _<name> that holds the files its themes have in common), then `default`. A layer is never a theme: it cannot be
     * chosen, and a theme can only name one that really exists.
     *
     * @return list<string>
     */
    public function lookup(string $theme): array
    {
        $chain = [$theme];
        $layer = config('cmsbackend.screens.themes.'.$theme.'.extends');
        if (is_string($layer) && preg_match('/^_[a-z][a-z0-9-]*$/D', $layer) === 1) {
            $chain[] = $layer;
        }
        $chain[] = 'default';

        return array_values(array_unique($chain));
    }

    public function path(string $surface, string $screen, bool $required = true): ?string
    {
        $this->view($surface, $screen);
        $key = $this->key($surface);
        $cacheKey = $surface.'|'.$key.'|'.$screen;
        $cache = request()->attributes->get(self::REQUEST_PATHS, []);
        if (! array_key_exists($cacheKey, $cache)) {
            $module = $surface === 'admin' ? 'CmsBackend' : 'CmsFrontend';
            $relative = str_replace('.', '/', $screen).'.blade.php';
            $base = module_path($module, 'resources/views/screens');
            $found = null;
            foreach ($this->lookup($key) as $theme) {
                $root = realpath($base.'/'.$theme);
                $file = realpath($base.'/'.$theme.'/'.$relative);
                if ($root !== false && $file !== false && is_file($file) && str_starts_with(strtolower(str_replace('\\', '/', $file)), strtolower(str_replace('\\', '/', $root)).'/')) {
                    $found = $file;
                    break;
                }
            }
            $cache[$cacheKey] = $found;
            request()->attributes->set(self::REQUEST_PATHS, $cache);
        }
        if ($required && $cache[$cacheKey] === null) {
            throw new InvalidArgumentException('Screen not found: '.$surface.'.'.$screen);
        }

        return $cache[$cacheKey];
    }

    public function pathForView(string $name): ?string
    {
        $name = str_replace('/', '.', trim($name));
        foreach (['cmsbackend' => 'admin', 'cmsfrontend' => 'public'] as $namespace => $surface) {
            if (! str_starts_with($name, $namespace.'::')) {
                continue;
            }
            $suffix = substr($name, strlen($namespace.'::'));
            if (str_starts_with($suffix, 'screens.')) {
                return $this->path($surface, substr($suffix, strlen('screens.')));
            }
            $logical = config('cmsbackend.screens.view_map')[$name] ?? null;

            return $logical !== null ? $this->path($surface, $logical) : null;
        }
        if (! str_contains($name, '::') && preg_match('/^[a-zA-Z0-9_.-]+$/D', $name) && AdminTheme::sharedRequest(request())) {
            $logical = config('cmsbackend.screens.shared_views')[$name] ?? 'pages.core.'.$name;

            return $this->path('admin', $logical, false);
        }

        return null;
    }

    /** @param array<string, string> $values */
    public function save(array $values): void
    {
        app(EditorialWorkflow::class)->assertDatabase();
        DB::transaction(function () use ($values): void {
            foreach (self::OPTIONS as $surface => $option) {
                if (! isset($values[$option]) || ! array_key_exists($values[$option], $this->choices($surface))) {
                    throw new InvalidArgumentException('Theme is not registered for this surface.');
                }
                Option::query()->updateOrCreate(['key' => $option], ['value' => $values[$option], 'autoload' => 1]);
            }
        });
        request()->attributes->remove(self::REQUEST_OPTIONS);
        request()->attributes->remove(self::REQUEST_PATHS);
    }
}
