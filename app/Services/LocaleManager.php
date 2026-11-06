<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the public-site locale: which locales exist, which one is active, and how a URL
 * is rewritten for another locale. Themes never decide anything about language themselves.
 */
final class LocaleManager
{
    /**
     * Ngôn ngữ đang dùng: toàn bộ danh sách cấu hình, hoặc chỉ ngôn ngữ mặc định khi chức năng "multilingual" bị tắt trong
     * Developer Settings. Mọi nút chọn, tab/ô nhập bản dịch, hreflang, sitemap và ghi bản dịch đều đi qua đây nên cùng ẩn theo.
     *
     * @return array<string, string> code => label
     */
    public function supported(): array
    {
        $configured = $this->configured();
        if ($this->multilingualEnabled()) {
            return $configured;
        }
        $default = $this->default();

        return [$default => $configured[$default] ?? $default];
    }

    /** @return array<string, string> danh sách cấu hình, bất kể chức năng đa ngôn ngữ đang bật hay tắt */
    public function configured(): array
    {
        return (array) config('locales.supported', ['vi' => 'Tiếng Việt']);
    }

    /**
     * Ngôn ngữ phụ để ĐĂNG KÝ route /{ngôn ngữ}. Luôn đủ danh sách cấu hình (route có thể bị cache); khi chức năng tắt,
     * các route này bị chặn 404 bằng middleware cms.feature:multilingual thay vì biến mất.
     *
     * @return string[]
     */
    public function configuredAlternates(): array
    {
        return array_values(array_diff(array_keys($this->configured()), [$this->default()]));
    }

    public function multilingualEnabled(): bool
    {
        $class = \Modules\CmsBackend\Features\FeatureRegistry::class;

        // Không có Feature Registry (module tắt): giữ hành vi cũ, đa ngôn ngữ theo cấu hình.
        return ! class_exists($class) || app($class)->isEnabled('multilingual');
    }

    public function default(): string
    {
        return (string) config('locales.default', 'vi');
    }

    /** @return string[] supported locales other than the default (they carry a URL prefix) */
    public function alternates(): array
    {
        return array_values(array_diff(array_keys($this->supported()), [$this->default()]));
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->supported());
    }

    public function current(): string
    {
        $locale = app()->getLocale();

        return $this->isSupported($locale) ? $locale : $this->default();
    }

    public function isDefault(?string $locale = null): bool
    {
        return ($locale ?? $this->current()) === $this->default();
    }

    /**
     * The non-default locale of the public page being served (e.g. "en"), or null for Vietnamese pages, the
     * administration, console and queue contexts. Every "read the translation instead of the original columns" rule
     * (scopes, overlays, option texts, URL generation) keys off this single answer.
     */
    public function publicLocale(): ?string
    {
        $request = app()->bound('request') ? request() : null;
        if ($request === null || $request->route() === null) {
            return null;
        }
        $locale = $this->current();
        if ($this->isDefault($locale)) {
            return null;
        }
        $cached = $request->attributes->get('_cms_public_locale');
        if ($cached === null) {
            $cached = $this->isPublicRequest($request) ? $locale : false;
            $request->attributes->set('_cms_public_locale', $cached);
        }

        return $cached ?: null;
    }

    /**
     * The language whose translation rows the models of this request read: the non-default language of a public page
     * (publicLocale()), and, when `locales.read_default_from_translations` is on, the default language of a public page as well.
     * Null when the original columns are the source (administration, console, and default-language pages while the flag is off).
     */
    public function overlayLocale(): ?string
    {
        $locale = $this->publicLocale();
        if ($locale !== null) {
            return $locale;
        }
        if (! (bool) config('locales.read_default_from_translations', false)) {
            return null;
        }
        $request = app()->bound('request') ? request() : null;
        if ($request === null || $request->route() === null || ! $this->isDefault($this->current())) {
            return null;
        }
        $cached = $request->attributes->get('_cms_overlay_default');
        if ($cached === null) {
            $cached = $this->isPublicRequest($request);
            $request->attributes->set('_cms_overlay_default', $cached);
        }

        return $cached ? $this->default() : null;
    }

    /**
     * Version number appended to every cache key that holds language-dependent output. Saving any translation bumps it,
     * so cached homepages, menus and option texts of every language are rebuilt (plan: Phase 14).
     */
    public function cacheVersion(): int
    {
        return (int) Cache::rememberForever('cms.i18n.version', fn () => 1);
    }

    public function bumpCacheVersion(): void
    {
        Cache::add('cms.i18n.version', 1);
        Cache::increment('cms.i18n.version');
    }

    /** Route prefix of the PUBLIC website ("" = the root of the domain), without slashes. */
    public function routePrefix(): string
    {
        return trim((string) config('cmsbackend.public_route_prefix', ''), '/');
    }

    /** Non-default locale named by the URL (e.g. /en/...), or null for the default locale. */
    public function fromRequest(Request $request): ?string
    {
        $segments = $this->segments($request->path());
        $first = $segments[0] ?? null;

        return $first !== null && in_array($first, $this->alternates(), true) ? $first : null;
    }

    /** Public pages are the ones served by the CmsFrontend module; the administration keeps its own (session) locale. */
    public function isPublicRequest(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getActionName(), 'Modules\\CmsFrontend\\');
    }

    /** Path prefix for a locale: "" for the default locale, "/en" for English. */
    public function prefixFor(string $locale): string
    {
        $parts = array_filter([$this->routePrefix(), $this->isDefault($locale) ? null : $locale]);

        return $parts === [] ? '' : '/'.implode('/', $parts);
    }

    /**
     * Same page in another locale, by path only. Pages whose slug differs per language are resolved by the
     * object-aware resolver of Phase 11; until then this is exact for locale-neutral pages such as the home page.
     */
    public function localizedUrl(string $locale, ?Request $request = null): string
    {
        $request ??= request();
        $segments = $this->segments($request->path());
        if (isset($segments[0]) && in_array($segments[0], $this->alternates(), true)) {
            array_shift($segments);
        }
        $url = $this->prefixFor($locale).($segments === [] ? '' : '/'.implode('/', $segments));
        $query = $request->getQueryString();

        return ($url === '' ? '/' : $url).($query ? '?'.$query : '');
    }

    /** @return string[] path segments with the CMS route prefix removed */
    private function segments(string $path): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($segment) => $segment !== ''));
        $prefix = $this->routePrefix();
        if ($prefix !== '' && ($segments[0] ?? null) === $prefix) {
            array_shift($segments);
        }

        return $segments;
    }
}
