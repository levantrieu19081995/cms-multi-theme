<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Http\Controllers;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\LocaleManager;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * sitemap.xml with the URLs of every language (plan: Phase 13).
 *
 * Each published, public record gets one <url> per language in which it really exists: the default language always, another
 * language only when its translation has a title and a slug. Every <url> lists the other versions as xhtml:link alternates, so
 * an English page that was never entered is neither listed nor advertised. The visibility rules are the public ones: published,
 * not scheduled for later, not expired, not deleted, not in an internal category.
 */
class SitemapController extends Controller
{
    /** post_type => route name (without cms.) that shows it */
    private const ROUTES = [
        'post' => 'tin-tuc.show',
        'page' => 'tin-tuc.show',
        'event' => 'su-kien.chi-tiet',
        'image' => 'hinh-anh.show',
        'video' => 'video.show',
    ];

    public function index(LocaleManager $locales): Response
    {
        $default = $locales->default();
        $entries = [];

        // Pages that exist in every language.
        $pages = ['trang-chu', 'tin-tuc.index', 'su-kien', 'hinh-anh.danh-sach', 'video.danh-sach'];
        if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact')) {
            $pages[] = 'lien-he';
        }
        foreach ($pages as $name) {
            $urls = [];
            foreach ($locales->supported() as $code => $label) {
                $route = Route::getRoutes()->getByName($this->routeName($locales, $name, $code));
                if ($route !== null) {
                    $urls[$code] = app('url')->toRoute($route, [], true);
                }
            }
            $entries[] = ['urls' => $urls, 'lastmod' => null];
        }

        $now = now();
        Post::withoutGlobalScopes()
            ->whereIn('post_type', array_keys(self::ROUTES))
            ->where('status', PostStatus::PUBLISHED->value)->whereNull('deleted_at')
            ->where('published_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('publication_date_expires')->orWhere('publication_date_expires', '>=', $now))
            ->whereDoesntHave('taxonomies', fn ($query) => $query->where('loai_chuyen_muc', 'noi_bo'))
            ->with('translations')
            ->orderBy('id')
            ->chunkById(500, function ($posts) use (&$entries, $locales, $default) {
                foreach ($posts as $post) {
                    $name = self::ROUTES[$post->post_type];
                    $urls = [];
                    foreach ($locales->supported() as $code => $label) {
                        $route = Route::getRoutes()->getByName($this->routeName($locales, $name, $code));
                        if ($route === null) {
                            continue;
                        }
                        if ($code === $default) {
                            $slug = $post->getRawOriginal('slug');
                        } else {
                            $translation = $post->translations->firstWhere('locale', $code);
                            $slug = filled($translation?->title) ? $translation?->slug : null;
                        }
                        if (filled($slug)) {
                            $urls[$code] = app('url')->toRoute($route, ['slug' => $slug], true);
                        }
                    }
                    if ($urls !== []) {
                        $entries[] = ['urls' => $urls, 'lastmod' => $post->updated_at];
                    }
                }
            });

        return response($this->xml($entries, $locales), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function routeName(LocaleManager $locales, string $name, string $code): string
    {
        return $locales->isDefault($code) ? 'cms.'.$name : 'cms.'.$code.'.'.$name;
    }

    /** @param array<int, array{urls: array<string, string>, lastmod: mixed}> $entries */
    private function xml(array $entries, LocaleManager $locales): string
    {
        $e = fn (string $text) => htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($entries as $entry) {
            // One <url> per language version; each lists all versions (including itself) as alternates.
            foreach ($entry['urls'] as $code => $url) {
                $out .= '  <url><loc>'.$e($url).'</loc>';
                if ($entry['lastmod']) {
                    $out .= '<lastmod>'.$e($entry['lastmod']->toAtomString()).'</lastmod>';
                }
                if (count($entry['urls']) > 1) {
                    foreach ($entry['urls'] as $altCode => $altUrl) {
                        $out .= '<xhtml:link rel="alternate" hreflang="'.$e((string) config('locales.html_lang.'.$altCode, $altCode)).'" href="'.$e($altUrl).'"/>';
                    }
                    $defaultUrl = $entry['urls'][$locales->default()] ?? null;
                    if ($defaultUrl !== null) {
                        $out .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.$e($defaultUrl).'"/>';
                    }
                }
                $out .= "</url>\n";
            }
        }

        return $out.'</urlset>'."\n";
    }
}
