<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\Log;

/**
 * Which public themes this deployment has assets for (see scripts/theme-profiles.mjs).
 *
 *  - all    : every registered theme is built; the administrator can switch theme (also when nothing says otherwise).
 *  - single : only one theme is built, so only that one can be used; the theme stored in the database is left alone.
 *
 * Sources, strongest first:
 *  1. Process environment CMS_ASSET_MODE / CMS_ASSET_THEME (local dev profiles; never stored in the database).
 *  2. public/hot present (Vite dev server serves everything) -> all, a stale build file must not lock a dev session.
 *  3. public/build/theme-build.json written by the build (the real content of the deployed assets, not .env).
 *  4. Nothing -> all (behaviour before profiles existed).
 */
final class AssetProfile
{
    public const METADATA = 'build/theme-build.json';

    /** @var array{mode: string, theme: ?string, source: string, themes: ?string[], dependencies: string[], generated_at: ?string}|null */
    private static ?array $resolved = null;

    /** @return array{mode: string, theme: ?string, source: string, themes: ?string[], dependencies: string[], generated_at: ?string} */
    public static function resolve(): array
    {
        return self::$resolved ??= self::detect();
    }

    public static function locked(): bool
    {
        return self::resolve()['mode'] === 'single';
    }

    /** The only usable theme in single mode, null in all mode. */
    public static function theme(): ?string
    {
        return self::resolve()['theme'];
    }

    public static function allows(string $theme): bool
    {
        $only = self::theme();
        if ($only !== null) {
            return $only === $theme;
        }

        // All mode: a theme can be chosen only when the build really contains it (theme-build.json lists it). Without a
        // metadata list (environment profile, Vite dev server, nothing known) every registered theme is allowed as before.
        $built = self::resolve()['themes'];

        return $built === null || in_array($theme, $built, true);
    }

    /**
     * True when the deployment's asset profile is really known: a CMS_ASSET_* variable, the Vite dev server, or a valid
     * theme-build.json. False = nothing known or the metadata is unreadable; theme switching must then stay read only.
     */
    public static function known(): bool
    {
        return self::resolve()['source'] !== 'default';
    }

    /**
     * Themes that can be chosen in this build: the one theme of a single build, the themes listed by theme-build.json, or
     * every registered theme when the profile comes from the environment / Vite dev. Dependencies are never included.
     *
     * @return string[]
     */
    public static function selectable(): array
    {
        $registered = array_keys((array) config('cmsbackend.screens.themes', []));
        $profile = self::resolve();
        if ($profile['theme'] !== null) {
            return [$profile['theme']];
        }

        return $profile['themes'] === null ? $registered : array_values(array_intersect($registered, $profile['themes']));
    }

    /** @return array{dependencies: string[], generated_at: ?string} facts from theme-build.json (empty when it is not the source) */
    public static function buildInfo(): array
    {
        $profile = self::resolve();

        return ['dependencies' => $profile['dependencies'], 'generated_at' => $profile['generated_at']];
    }

    public static function forget(): void
    {
        self::$resolved = null;
    }

    /** @return array{mode: string, theme: ?string, source: string, themes: ?string[], dependencies: string[], generated_at: ?string} */
    private static function detect(): array
    {
        return self::detectProfile() + ['themes' => null, 'dependencies' => [], 'generated_at' => null];
    }

    /** @return array<string, mixed> */
    private static function detectProfile(): array
    {
        $registered = array_keys((array) config('cmsbackend.screens.themes', []));
        $all = ['mode' => 'all', 'theme' => null, 'source' => 'default'];

        $mode = strtolower(trim((string) config('cmsbackend.screens.assets.mode', '')));
        if ($mode === 'all') {
            return ['mode' => 'all', 'theme' => null, 'source' => 'environment'];
        }
        if ($mode === 'single') {
            $theme = trim((string) config('cmsbackend.screens.assets.theme', ''));
            if (in_array($theme, $registered, true)) {
                return ['mode' => 'single', 'theme' => $theme, 'source' => 'environment'];
            }
            Log::warning('CMS_ASSET_MODE=single ignored: CMS_ASSET_THEME "'.$theme.'" is not a registered theme.');
        }

        if (is_file(public_path('hot'))) {
            return ['mode' => 'all', 'theme' => null, 'source' => 'vite-dev'];
        }

        $file = public_path(self::METADATA);
        if (! is_file($file)) {
            return $all;
        }
        $metadata = json_decode((string) file_get_contents($file), true);
        if (! is_array($metadata)) {
            Log::warning('public/'.self::METADATA.' is not valid JSON; all themes are assumed.');

            return $all;
        }
        $themes = array_values(array_filter((array) ($metadata['themes'] ?? []), 'is_string'));
        $extra = [
            'dependencies' => array_values(array_filter((array) ($metadata['dependencies'] ?? []), 'is_string')),
            'generated_at' => is_string($metadata['generatedAt'] ?? null) ? $metadata['generatedAt'] : null,
        ];
        if (($metadata['mode'] ?? 'all') === 'single' && count($themes) === 1 && in_array($themes[0], $registered, true)) {
            return ['mode' => 'single', 'theme' => $themes[0], 'source' => 'build', 'themes' => $themes] + $extra;
        }
        // An "all" build must list the themes it contains, otherwise the metadata cannot be trusted to name selectable themes.
        $listed = array_values(array_intersect($registered, $themes));
        if ($listed === []) {
            Log::warning('public/'.self::METADATA.' lists no registered theme; its metadata is ignored.');

            return $all;
        }

        return ['mode' => 'all', 'theme' => null, 'source' => 'build', 'themes' => $listed] + $extra;
    }
}
