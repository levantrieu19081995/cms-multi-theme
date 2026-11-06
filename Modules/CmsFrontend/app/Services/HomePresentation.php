<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Services;

use Illuminate\Support\Facades\Cache;
use Modules\CmsBackend\Services\ScreenTheme;

final class HomePresentation
{
    public function __construct(private ScreenTheme $screens) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function prepare(array $data): array
    {
        // Homepage Layout Manager: the blocks' content is replaced by the theme's own settings BEFORE the cache stamp
        // below is computed, so the fragment cache follows the theme's content.
        if ($managed = $this->managed()) {
            $data = app(\Modules\CmsFrontend\Homepage\HomepageDataResolver::class)->mergeAll($managed['plan'], $data);
            // Only the effect assets the enabled blocks declare are loaded by the page (see home.blade.php of each theme).
            $data['homepageEffectEntries'] = \Modules\CmsFrontend\Homepage\EffectCatalog::entriesForPlan($managed['plan']);
        }

        // Content kept in tables of its own (partners, services, role models, documents, schedules, traceability links) has no
        // translation table yet. On a page in another language it is left out, never shown in Vietnamese instead.
        if (app(\App\Services\LocaleManager::class)->publicLocale()) {
            foreach (['links', 'dichvus', 'guongTieuBieu', 'homepageDocuments', 'homeSchedules', 'homeTraceLinks'] as $untranslated) {
                $data[$untranslated] = [];
            }
        }

        extract(array_intersect_key($data, array_flip(['firstPost', 'posts', 'categoryPosts', 'homepageDocuments', 'events', 'guongTieuBieu', 'libraryImages', 'videos', 'homeTheme', 'dichvus', 'links', 'homeCategoryTabs', 'homeBanners', 'homeSchedules', 'homeTraceLinks'])), EXTR_SKIP);
        $categoryInfo = $data['categoryInfo'] ?? null;
        $categoryPosts = $data['categoryPosts'] ?? null;

        if (in_array($this->screens->key('public'), ['nam-binh-duong', 'ban-de-an'], true)) {
            $data['homeShortcutNavigation'] = app(EditorialLayout::class)->mainMenu();
        }

        $homeNews = collect($firstPost ?? [])->concat(collect($posts ?? []))->unique('id')->values();
        $homeNotices = ($categoryPosts instanceof \Illuminate\Pagination\AbstractPaginator ? $categoryPosts->getCollection() : collect($categoryPosts ?? []))->take(4);
        $homeDocuments = collect($homepageDocuments ?? [])->take(4);
        $homeEvents = collect($events ?? [])->take(3);
        $homeExamples = collect($guongTieuBieu ?? [])->take(3);
        $homeAlbums = collect($libraryImages ?? [])->take(9);
        $homeVideos = collect($videos ?? [])->take(9);
        $newsUrl = \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index');
        $noticeUrl = \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index', ['category' => $categoryInfo?->term?->slug]);

        $modelStamp = static fn ($items) => collect($items)->map(static fn ($item) => [
            $item->getKey(),
            optional($item->updated_at)->getTimestamp(),
        ])->all();
        $homeContentStamp = [
            'theme' => ($homeTheme ?? null)?->key(),
            'route_prefix' => config('cmsbackend.route_prefix'),
            'shortcut_navigation' => $data['homeShortcutNavigation'] ?? [],
            'news' => $modelStamp($homeNews),
            'notices' => $modelStamp($homeNotices),
            'documents' => $modelStamp($homeDocuments),
            'events' => $modelStamp($homeEvents),
            'examples' => $modelStamp($homeExamples),
            'albums' => $modelStamp($homeAlbums),
            'services' => $modelStamp($dichvus ?? []),
            'partners' => $modelStamp($links ?? []),
            'tabs' => collect($homeCategoryTabs ?? [])->map(static fn ($tab) => [
                'id' => $tab['id'],
                'title' => $tab['title'],
                'stories' => $modelStamp($tab['stories']),
            ])->all(),
            'banners' => $homeBanners ?? [],
            'statistics' => $data['homeStatistics'] ?? [],
            'schedules' => $homeSchedules ?? [],
            'trace_links' => $homeTraceLinks ?? [],
            'videos' => $homeVideos->all(),
            'sections' => collect($data['homeSections'] ?? [])->map(static fn ($section) => [
                'url' => $section['url'],
                'items' => $modelStamp($section['items']),
            ])->all(),
        ];

        return array_merge($data, compact('homeNews', 'homeNotices', 'homeDocuments', 'homeEvents', 'homeExamples', 'homeAlbums', 'homeVideos', 'newsUrl', 'noticeUrl', 'homeContentStamp'));
    }

    /** @param array<string, mixed> $data */
    public function cacheKey(array $data): string
    {
        return 'cms.home-content.screens.v1.'.hash('sha256', json_encode([
            'database' => config('database.connections.'.config('database.default').'.database'),
            'theme' => $this->screens->key('public'),
            'theme_version' => config('cmsbackend.screens.themes.'.$this->screens->key('public').'.version', 1),
            'origin' => request()->getSchemeAndHttpHost(),
            'locale' => app()->getLocale(),
            // Bumped whenever any translation is saved (TranslationService): English text edited alone must not leave a stale page.
            'i18n_version' => app(\App\Services\LocaleManager::class)->cacheVersion(),
            'content' => $data['homeContentStamp'],
            // Homepage Layout Manager: the fragment is cached per layout (zones, variants, settings, effects).
            'layout' => $this->managed()['plan']['stamp'] ?? null,
        ], JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $data */
    public function render(array $data): string
    {
        $managed = $this->managed();
        abort_if($managed === null, 500, 'Homepage layout is not available.');
        $render = fn (): string => view($this->screens->view('public', 'components.home.managed.layout'), $data + [
            'homepageZones' => app(\Modules\CmsFrontend\Homepage\HomepageRenderer::class)->zones($managed['plan'], $data, $managed['preview']),
        ])->render();

        // Preview is never cached: it must show the snapshot of this Developer session only.
        return config('app.debug') || $managed['preview'] ? $render() : Cache::remember($this->cacheKey($data), now()->addHour(), $render);
    }

    /**
     * Layout plan of the active theme (live layout or a Developer preview).
     *
     * @return array{plan: array<string, mixed>, preview: bool}|null
     */
    private function managed(): ?array
    {
        return app(\Modules\CmsFrontend\Homepage\HomepageRenderer::class)->active($this->screens->key('public'));
    }
}
