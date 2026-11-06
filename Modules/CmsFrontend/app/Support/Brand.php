<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Support;

use App\Models\Option;
use App\Services\LocaleManager;
use App\Services\OptionTranslations;

/**
 * Site name and unit line for the public pages. Admins edit them on "Cài đặt chung" (web_title / web_description
 * in the CMS database); the module config values are the fallback when those are empty.
 *
 * On a page in another language the text comes from option_translations only (entered on the same screen); when none
 * was entered the text is empty (the theme supplies its own fixed text from the language file). The Vietnamese text is never shown on a non-Vietnamese page.
 */
final class Brand
{
    public static function name(): string
    {
        if ($locale = app(LocaleManager::class)->publicLocale()) {
            return app(OptionTranslations::class)->get('web_title', $locale) ?? '';
        }

        return self::option('web_title') ?: (string) config('cmsfrontend.brand.name');
    }

    public static function unit(): string
    {
        if ($locale = app(LocaleManager::class)->publicLocale()) {
            return app(OptionTranslations::class)->get('web_description', $locale) ?? '';
        }

        return self::option('web_description') ?: (string) config('cmsfrontend.brand.unit');
    }

    /** Contact address text of the current language (web_address); null when none exists in that language. */
    public static function address(): ?string
    {
        if ($locale = app(LocaleManager::class)->publicLocale()) {
            return app(OptionTranslations::class)->get('web_address', $locale);
        }
        $value = self::option('web_address');

        return $value === '' ? null : $value;
    }

    /**
     * Text of a "Cài đặt chung" option (contact, social links, footer fields). Fields that exist per language read only the
     * option_translations text on a non-default-language page (the Vietnamese text is never shown there); null when empty,
     * so the theme falls back to its own default text.
     */
    public static function setting(string $key): ?string
    {
        if (in_array($key, \Modules\CmsBackend\Support\CommonSettings::TRANSLATABLE, true) && ($locale = app(LocaleManager::class)->publicLocale())) {
            $text = app(OptionTranslations::class)->get($key, $locale);

            return filled($text) ? trim($text) : null;
        }
        $value = self::option($key);

        return $value === '' ? null : $value;
    }

    /** Title color chosen on "Cài đặt chung" (web_title_color, #rrggbb); null when none is saved so the theme keeps its own colors. */
    public static function titleColor(): ?string
    {
        $value = self::option('web_title_color');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : null;
    }

    private static function option(string $key): string
    {
        if (! request()->attributes->has('_cms_brand_options')) {
            request()->attributes->set('_cms_brand_options', Option::whereIn('key', array_merge(['web_title', 'web_description', 'web_address', 'web_title_color'], \Modules\CmsBackend\Support\CommonSettings::keys()))->pluck('value', 'key')->all());
        }

        return trim((string) (request()->attributes->get('_cms_brand_options')[$key] ?? ''));
    }
}
