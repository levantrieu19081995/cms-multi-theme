<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use App\Models\Option;

/**
 * Site icon chosen on "Cài đặt chung" (option web_logo). The admin header shows it when set and falls back to the
 * bundled logo otherwise.
 */
final class SiteLogo
{
    public static function url(): ?string
    {
        if (! request()->attributes->has('_cms_site_logo')) {
            $value = trim((string) Option::where('key', 'web_logo')->value('value'));
            // The file manager stores an absolute URL; keep only the path so a changed host/port does not break it.
            $path = $value !== '' ? parse_url($value, PHP_URL_PATH) : null;
            request()->attributes->set('_cms_site_logo', is_string($path) && $path !== '' ? $path : null);
        }

        return request()->attributes->get('_cms_site_logo');
    }

    /** Configured icon, or asset($fallback) (a path under public/) when none was chosen. */
    public static function urlOr(string $fallback): string
    {
        return self::url() ?? asset($fallback);
    }
}
