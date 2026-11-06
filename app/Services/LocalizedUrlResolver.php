<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Term;
use App\Models\TermTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Where does the language switcher send a visitor? To the same thing in the other language:
 *
 *  - an article, event, album or video -> the same record's translation (its slug in that language);
 *  - a category list -> the same category's translated slug;
 *  - any other page that exists in both languages -> the same route of the other language;
 *  - anything that has no version in the target language (no translation, or a page of content that has no translation tables yet)
 *    -> for another language the "content is being updated" notice, for the default language the home page.
 *
 * The Vietnamese text is never offered as an English page (and the other way round): when no counterpart exists the visitor
 * lands on a notice, not on the wrong language. counterpart() answers null in exactly that case, which is what the hreflang links
 * and the sitemap need (they list real alternates only).
 *
 * The slug in a route parameter is always the original one here: ResolveLocalizedSlugs rewrites the slug of another language back
 * before the controller (and this resolver) run.
 */
final class LocalizedUrlResolver
{
    private const POST_ROUTES = ['tin-tuc.show', 'su-kien.chi-tiet', 'hinh-anh.show', 'video.show'];

    public function __construct(private LocaleManager $locales) {}

    /** Link for the language switcher: the counterpart, else the notice (another language) / the home page (default language). */
    public function urlFor(string $target, ?Request $request = null): string
    {
        return $this->counterpart($target, $request) ?? $this->notice($target);
    }

    /** URL of the same page in the language, or null when the page has no version in that language. */
    public function counterpart(string $target, ?Request $request = null): ?string
    {
        $request ??= request();
        $route = $request->route();
        $base = $route ? $this->baseName((string) $route->getName()) : null;
        if ($route === null || $base === null) {
            return null;
        }

        $targetRoute = Route::getRoutes()->getByName($this->routeName($base, $target));
        if ($targetRoute === null) {
            return null;
        }

        $parameters = $route->parameters();
        if (in_array($base, self::POST_ROUTES, true)) {
            $slug = $this->postSlug((string) ($parameters['slug'] ?? ''), $target);
            if ($slug === null) {
                return null;
            }
            $parameters['slug'] = $slug;
        } elseif ($base === 'tin-tuc.index' && ! empty($parameters['category'])) {
            $slug = $this->termSlug((string) $parameters['category'], $target);
            if ($slug === null) {
                return null;
            }
            $parameters['category'] = $slug;
        }

        $query = $request->query();
        unset($query['page']);
        if (! empty($query['category'])) {
            $slug = $this->termSlug((string) $query['category'], $target);
            if ($slug === null) {
                unset($query['category']);
            } else {
                $query['category'] = $slug;
            }
        }

        // toRoute() builds the URL of exactly this route: the generator's own language mapping must not run again.
        return app('url')->toRoute($targetRoute, $parameters + $query, true);
    }

    /** The language of the page being served, as the alternate of itself. */
    public function currentUrl(?Request $request = null): string
    {
        $request ??= request();

        return $request->url();
    }

    /** Route name without "cms." and without the language segment, e.g. cms.en.tin-tuc.show -> tin-tuc.show; null for other areas. */
    private function baseName(string $name): ?string
    {
        if (! str_starts_with($name, 'cms.') || str_starts_with($name, 'cms.dashboard') || str_starts_with($name, 'cms.setting')) {
            return null;
        }
        $rest = substr($name, 4);
        foreach ($this->locales->alternates() as $locale) {
            if (str_starts_with($rest, $locale.'.')) {
                return substr($rest, strlen($locale) + 1);
            }
        }

        return $rest;
    }

    private function routeName(string $base, string $locale): string
    {
        return $this->locales->isDefault($locale) ? 'cms.'.$base : 'cms.'.$locale.'.'.$base;
    }

    private function home(string $locale): string
    {
        return app('url')->toRoute(Route::getRoutes()->getByName($this->routeName('trang-chu', $locale)), [], true);
    }

    private function notice(string $locale): string
    {
        $name = $this->routeName('content-updating', $locale);

        return Route::has($name) ? app('url')->toRoute(Route::getRoutes()->getByName($name), [], true) : $this->home($locale);
    }

    /** Slug of the same post in the language, or null when that language has no filled translation of it. */
    private function postSlug(string $originalSlug, string $locale): ?string
    {
        if ($this->locales->isDefault($locale)) {
            return $originalSlug;
        }
        $postId = Post::withoutGlobalScopes()->where('slug', $originalSlug)->value('id');
        if (! $postId) {
            return null;
        }
        $slug = PostTranslation::query()->where('post_id', $postId)->where('locale', $locale)
            ->whereNotNull('title')->where('title', '!=', '')->value('slug');

        return filled($slug) ? (string) $slug : null;
    }

    private function termSlug(string $originalSlug, string $locale): ?string
    {
        if ($this->locales->isDefault($locale)) {
            return $originalSlug;
        }
        $termId = Term::withoutGlobalScopes()->where('slug', $originalSlug)->value('term_id');
        if (! $termId) {
            return null;
        }
        $slug = TermTranslation::query()->where('term_id', $termId)->where('locale', $locale)
            ->whereNotNull('name')->where('name', '!=', '')->value('slug');

        return filled($slug) ? (string) $slug : null;
    }
}
