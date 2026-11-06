<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Http\Middleware;

use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Term;
use App\Models\TermTranslation;
use App\Services\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routes of a non-default language ({route_prefix}/en/...) carry the slug of that language, e.g. /cms/en/article-title.htm.
 * The controllers and queries of the application work with the original (default-language) slug, so this middleware
 * translates the URL slug back to it before the controller runs. The record is then found through the normal query, where
 * PublicLocaleScope keeps only records that have a translation and shows the translated texts.
 *
 * An unknown slug of that language is a 404: the Vietnamese slug is deliberately not accepted under /en, so the Vietnamese
 * article never answers on an English URL.
 */
final class ResolveLocalizedSlugs
{
    /** Route names (without the cms.{locale}. prefix) whose {slug} parameter names a post, event, album or video. */
    private const POST_ROUTES = ['tin-tuc.show', 'binh-luan-bai-viet.store', 'su-kien.chi-tiet', 'hinh-anh.show', 'video.show'];

    public function handle(Request $request, Closure $next): Response
    {
        $locales = app(LocaleManager::class);
        $locale = $locales->fromRequest($request);
        $route = $request->route();
        if ($locale === null || $route === null) {
            return $next($request);
        }

        $name = preg_replace('/^cms\.'.preg_quote($locale, '/').'\./', '', (string) $route->getName());

        if (in_array($name, self::POST_ROUTES, true) && $route->hasParameter('slug')) {
            $postId = PostTranslation::query()->where('locale', $locale)->where('slug', (string) $route->parameter('slug'))
                ->whereNotNull('title')->where('title', '!=', '')->value('post_id');
            $original = $postId ? Post::withoutGlobalScopes()->whereKey($postId)->value('slug') : null;
            abort_if($original === null, 404);
            $route->setParameter('slug', $original);
            $request->attributes->set('localized_post_id', (int) $postId);
        }

        if ($name === 'tin-tuc.index') {
            if ($route->hasParameter('category') && $route->parameter('category') !== null) {
                $route->setParameter('category', $this->termSlug($locale, (string) $route->parameter('category')) ?? abort(404));
            }
            // The search form sends the category as ?category=slug.
            $queryCategory = trim((string) $request->query('category'));
            if ($queryCategory !== '') {
                $request->query->set('category', $this->termSlug($locale, $queryCategory) ?? '__unknown__');
            }
        }

        return $next($request);
    }

    private function termSlug(string $locale, string $slug): ?string
    {
        $termId = TermTranslation::query()->where('locale', $locale)->where('slug', $slug)->whereNotNull('name')->where('name', '!=', '')->value('term_id');

        return $termId ? Term::withoutGlobalScopes()->whereKey($termId)->value('slug') : null;
    }
}
