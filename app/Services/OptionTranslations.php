<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OptionTranslation;
use Illuminate\Support\Facades\Cache;

/**
 * Reads administrator-entered texts that live in option_translations (homepage labels, unit information...).
 * The language-independent value and the default language stay in `options`. For another language the value is
 * returned only when it was entered: there is never a fallback to the default language.
 */
final class OptionTranslations
{
    public function __construct(private LocaleManager $locales) {}

    /** The text in a language, or null when none was entered (or the language is the default one, which lives in `options`). */
    public function get(string $key, ?string $locale = null): ?string
    {
        $locale ??= $this->locales->current();
        if ($this->locales->isDefault($locale)) {
            return null;
        }
        $value = trim((string) ($this->forKey($key)[$locale] ?? ''));

        return $value === '' ? null : $value;
    }

    /** @return array<string, string> locale => text, only the languages that have one */
    public function forKey(string $key): array
    {
        return $this->loadAll()[$key] ?? [];
    }

    /** One query per request for every option translation (the table stays small: texts of the homepage and unit information). */
    private function loadAll(): array
    {
        static $memo = [];
        $version = $this->locales->cacheVersion();
        if (! isset($memo[$version])) {
            $memo = [$version => Cache::remember('cms.i18n.options.v'.$version, 300, function () {
                $all = [];
                foreach (OptionTranslation::query()->get(['option_key', 'locale', 'value']) as $row) {
                    $all[$row->option_key][$row->locale] = (string) $row->value;
                }

                return $all;
            })];
        }

        return $memo[$version];
    }
}
