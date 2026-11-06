<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\LocaleManager;
use Illuminate\Routing\UrlGenerator;

/**
 * route('cms.tin-tuc.show', $slug) on a public page in another language produces the URL of the same route under that
 * language's prefix (cms.en.tin-tuc.show -> {route_prefix}/en/{slug}.htm), so themes, controllers and menus keep calling
 * route() with the plain name and never mention a language. A name without a counterpart for the language (login, pages of
 * content that has no translation tables yet) is generated unchanged.
 */
final class LocalizedUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true)
    {
        if (is_string($name) && str_starts_with($name, 'cms.') && ! str_starts_with($name, 'cms.dashboard')) {
            $locale = app(LocaleManager::class)->publicLocale();
            if ($locale !== null) {
                $localized = 'cms.'.$locale.'.'.substr($name, 4);
                if ($this->routes->hasNamedRoute($localized)) {
                    $name = $localized;
                } elseif ($this->isDefaultOnly($name) && $this->routes->hasNamedRoute('cms.'.$locale.'.content-updating')) {
                    // No version of this page in the language: link to the notice, never to the Vietnamese page.
                    $name = 'cms.'.$locale.'.content-updating';
                    $parameters = [];
                }
            }
        }

        return parent::route($name, $parameters, $absolute);
    }

    private function isDefaultOnly(string $name): bool
    {
        foreach ((array) config('locales.default_only_routes', []) as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
