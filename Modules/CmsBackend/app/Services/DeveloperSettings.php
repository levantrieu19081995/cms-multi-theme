<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use InvalidArgumentException;
use Modules\CmsBackend\Services\Developer\DeveloperAudit;
use Modules\CmsBackend\Support\AssetProfile;
use Modules\CmsBackend\Support\ProjectContext;
use Throwable;

/**
 * Theme facts and the theme switch of Developer Settings (permissions: see Developer\PermissionAvailability). The theme is
 * stored where it always was (option cms_public_theme, written by ScreenTheme::save); this class only decides whether a
 * switch is allowed. Nothing returned is a secret: theme keys/labels and public build facts.
 */
final class DeveloperSettings
{
    /**
     * Why theme switching is read only now (a lang key under admin.developer.theme_ro_), or null when a switch is allowed.
     * Switching needs a known "all themes" build, a project that is not fixed by CMS_PROJECT_KEY, and at least two themes.
     */
    public function readOnlyReason(): ?string
    {
        if (! AssetProfile::known()) {
            return 'unknown';
        }
        if (AssetProfile::locked()) {
            return 'single';
        }
        if (ProjectContext::fixed()) {
            return 'fixed';
        }

        return count(AssetProfile::selectable()) < 2 ? 'one' : null;
    }

    /**
     * Registered public themes of this build and the facts known about this deployment.
     *
     * @return array<string, mixed>
     */
    public function themes(): array
    {
        $active = app(ScreenTheme::class)->key('public');
        $profile = AssetProfile::resolve();
        $selectable = AssetProfile::selectable();
        $info = AssetProfile::buildInfo();
        $known = AssetProfile::known();

        $themes = [];
        foreach ((array) config('cmsbackend.screens.themes', []) as $key => $theme) {
            $key = (string) $key;
            if (! in_array('public', (array) ($theme['surfaces'] ?? []), true) || preg_match('/^[a-z][a-z0-9-]*$/D', $key) !== 1) {
                continue;
            }
            $isSelectable = in_array($key, $selectable, true);
            $isDependency = ! $isSelectable && in_array($key, $info['dependencies'], true);
            // With nothing known about the build every registered theme is listed; otherwise only what the build contains.
            if ($known && ! $isSelectable && ! $isDependency) {
                continue;
            }
            $project = ProjectContext::projectOfTheme($key);
            $themes[] = [
                'key' => $key,
                'label' => (string) ($theme['label'] ?? $key),
                'brand' => (string) ($theme['brand']['name'] ?? ''),
                'tagline' => (string) ($theme['brand']['tagline'] ?? ''),
                'version' => $theme['version'] ?? null,
                'project' => $project !== null ? ProjectContext::label($project) : null,
                'preview' => $this->preview((string) ($theme['login']['logo'] ?? '')),
                'active' => $key === $active,
                'default' => $key === 'default',
                'selectable' => $isSelectable,
                'dependency' => $isDependency,
                'in_build' => $known ? ($isSelectable || $isDependency) : null,
            ];
        }

        $reason = $this->readOnlyReason();

        return [
            'active' => $active,
            'default' => 'default',
            'mode' => $profile['mode'],
            'locked_theme' => $profile['theme'],
            'source' => $profile['source'],
            'known' => $known,
            'generated_at' => $info['generated_at'],
            'project_fixed' => ProjectContext::fixed(),
            'project' => ProjectContext::label(),
            'project_key' => ProjectContext::key(),
            'read_only_reason' => $reason,
            'can_switch' => $reason === null,
            'themes' => $themes,
        ];
    }

    /**
     * Change the public theme. Every condition is checked here on the server; nothing the browser sent is trusted.
     *
     * @return array{old: string, new: string}
     *
     * @throws InvalidArgumentException message = lang key under admin.developer.
     */
    public function switchTheme(string $theme, ?string $ip): array
    {
        $registered = (array) config('cmsbackend.screens.themes', []);
        if (preg_match('/^[a-z][a-z0-9-]*$/D', $theme) !== 1
            || ! isset($registered[$theme])
            || ! in_array('public', (array) ($registered[$theme]['surfaces'] ?? []), true)) {
            throw new InvalidArgumentException('theme_error_unknown');
        }
        if ($this->readOnlyReason() !== null) {
            throw new InvalidArgumentException('theme_error_locked');
        }
        if (! in_array($theme, AssetProfile::selectable(), true)) {
            throw new InvalidArgumentException('theme_error_not_in_build');
        }
        if (ProjectContext::projectOfTheme($theme) === null) {
            throw new InvalidArgumentException('theme_error_no_project');
        }
        $screens = app(ScreenTheme::class);
        $old = $screens->key('public');
        if ($old === $theme) {
            throw new InvalidArgumentException('theme_error_unchanged');
        }
        $oldProject = ProjectContext::key();

        try {
            $screens->save([ScreenTheme::OPTIONS['public'] => $theme]); // only cms_public_theme is written
        } catch (Throwable) {
            throw new InvalidArgumentException('theme_error_save');
        }

        // Home-page fragments are keyed by theme, version and content, so nothing stale can be served; no flush is needed.
        DeveloperAudit::record([
            'action' => 'theme',
            'project_key' => $oldProject,
            'new_project_key' => ProjectContext::key(),
            'old_theme' => $old,
            'new_theme' => $theme,
            'build_mode' => AssetProfile::resolve()['mode'],
            'ip' => $ip,
        ], __('admin.developer.log_theme'));

        return ['old' => $old, 'new' => $theme];
    }

    /** Public URL of an image under public/assets, only when the file really exists. */
    private function preview(string $path): ?string
    {
        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, 'assets/') || ! is_file(public_path($path))) {
            return null;
        }

        return asset($path);
    }
}
