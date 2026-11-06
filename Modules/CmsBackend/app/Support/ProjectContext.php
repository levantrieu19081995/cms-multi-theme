<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\Log;
use Modules\CmsBackend\Services\ScreenTheme;
use RuntimeException;

/**
 * The one place that answers "which project is this?". Config: config/projects.php.
 *
 *  - fixed (production): CMS_PROJECT_KEY names the project; the public theme is that project's theme.
 *  - follows the theme (local, all themes): the project of the public theme selected in the administration. Changing the
 *    theme therefore changes project, theme, posts, history and accounts together.
 *
 * Code outside this class (the social module, services, queries) must use key(); it never reads a theme or a title.
 */
final class ProjectContext
{
    /** @return array<string, array{label: string, theme: string}> */
    public static function projects(): array
    {
        return (array) config('cmsbackend.projects.projects', []);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::projects());
    }

    /** CMS_PROJECT_KEY is set: the project cannot change while the application runs. */
    public static function fixed(): bool
    {
        return trim((string) config('cmsbackend.projects.key', '')) !== '';
    }

    /**
     * Current project key.
     *
     * @throws RuntimeException when CMS_PROJECT_KEY names a project that is not registered (data must never be written
     *                          under a guessed project)
     */
    public static function key(): string
    {
        if (self::fixed()) {
            $key = trim((string) config('cmsbackend.projects.key'));
            if (! self::exists($key)) {
                throw new RuntimeException('CMS_PROJECT_KEY "'.$key.'" is not a registered project (config/projects.php).');
            }

            return $key;
        }

        $theme = null;
        try {
            $theme = app(ScreenTheme::class)->key('public');
        } catch (\Throwable) {
            // No request or no database yet: use the default project below.
        }

        return ($theme !== null ? self::projectOfTheme($theme) : null) ?? (string) config('cmsbackend.projects.default', 'khuyen-nong');
    }

    public static function label(?string $key = null): string
    {
        $key ??= self::key();

        return (string) (self::projects()[$key]['label'] ?? $key);
    }

    /** Public theme of a project (the current one by default). */
    public static function theme(?string $key = null): ?string
    {
        $theme = self::projects()[$key ?? self::key()]['theme'] ?? null;

        return is_string($theme) ? $theme : null;
    }

    public static function projectOfTheme(string $theme): ?string
    {
        foreach (self::projects() as $key => $project) {
            if (($project['theme'] ?? null) === $theme) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * Theme the public site must use when the project is fixed, else null (the stored administration choice applies).
     * Never throws: an invalid CMS_PROJECT_KEY is logged and the public site keeps its stored theme.
     */
    public static function fixedTheme(): ?string
    {
        if (! self::fixed()) {
            return null;
        }
        $key = trim((string) config('cmsbackend.projects.key'));
        $theme = self::exists($key) ? self::theme($key) : null;
        if ($theme === null) {
            self::warnOnce('invalid:'.$key, 'CMS_PROJECT_KEY "'.$key.'" is not a registered project; the stored public theme is used.');

            return null;
        }
        // The built assets must be those of the project (single-theme build, see AssetProfile).
        if (AssetProfile::locked() && AssetProfile::theme() !== $theme) {
            self::warnOnce('assets:'.$key, 'Project "'.$key.'" uses theme "'.$theme.'" but this build only has "'.AssetProfile::theme().'".');
        }

        return $theme;
    }

    /** @var array<string, true> */
    private static array $warned = [];

    private static function warnOnce(string $id, string $message): void
    {
        if (! isset(self::$warned[$id])) {
            self::$warned[$id] = true;
            Log::warning($message);
        }
    }
}
