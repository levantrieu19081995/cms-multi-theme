<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Services;

use App\DataProviders\AdsDataProvider;
use App\DataProviders\MenuDataProvider;
use App\DataProviders\MenuItemDataProvider;
use Modules\CmsBackend\Support\ModuleUrl;

class EditorialLayout
{
    /** @return array<int, array<string, mixed>> */
    public function mainMenu(): array
    {
        $configured = (new MenuDataProvider(new MenuItemDataProvider()))->getMenu('main_menu') ?? collect();

        return $this->navigation($configured);
    }

    public function data(): array
    {
        $configured = (new MenuDataProvider(new MenuItemDataProvider()))->getMenu('main_menu') ?? collect();
        $configuredNavigation = $this->navigation($configured);
        $introduction = $configured->first(fn ($item) => mb_strtolower(trim(strip_tags($item->title))) === 'giới thiệu');
        $navigation = $configuredNavigation ?: [
            ['title' => __('pages.trang_chu'), 'url' => ModuleUrl::route('cms.trang-chu')],
            ['title' => __('pages.gioi_thieu'), 'url' => $introduction ? $this->url($introduction) : null,
                'target' => $introduction->target ?? null,
                'children' => $introduction ? $this->items($introduction->childs ?? collect()) : []],
            ['title' => __('pages.tin_tuc'), 'url' => ModuleUrl::route('cms.tin-tuc.index')],
            ['title' => __('pages.dich_vu'), 'url' => ModuleUrl::route('cms.frontend.dichvu.index')],
            ['title' => __('pages.su_kien'), 'url' => ModuleUrl::route('cms.su-kien')],
            ['title' => __('pages.van_ban'), 'url' => ModuleUrl::route('cms.frontend.vanban.index')],
            ['title' => __('pages.library'), 'url' => ModuleUrl::route('cms.hinh-anh.danh-sach'), 'children' => [
                ['title' => __('pages.thu_vien_hinh_anh'), 'url' => ModuleUrl::route('cms.hinh-anh.danh-sach'), 'children' => []],
                ['title' => __('pages.video_khuyen_nong'), 'url' => ModuleUrl::route('cms.video.danh-sach'), 'children' => []],
            ]],
            ['title' => __('pages.hoi_dap'), 'url' => ModuleUrl::route('cms.frontend.hoidap.index')],
        ];
        // Liên hệ chỉ có trong menu mặc định khi chức năng Liên hệ đang bật (Feature Registry).
        if (! $configuredNavigation && app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact')) {
            $navigation[] = ['title' => __('pages.lien_he'), 'url' => ModuleUrl::route('cms.lien-he')];
        }
        // Website-link records have no translation table yet. Do not leak their Vietnamese title into English footers.
        $websiteLinks = app(\App\Services\LocaleManager::class)->publicLocale() !== null
            ? collect()
            : (new AdsDataProvider())->paginate('', 10)->getCollection();
        if ($websiteLinks->isNotEmpty()) {
            $websiteLinks->load('media');
        }

        return ['cmsNavigation' => $navigation, 'cmsConfiguredNavigation' => $this->items($configured), 'cmsWebsiteLinks' => $websiteLinks]
            + $this->footerData();
    }

    /** PLHĐ footer: quick links (icons), total visits and visitors online. */
    private function footerData(): array
    {
        return ['cmsFooterQuickLinks' => $this->footerQuickLinks(), 'cmsVisitStats' => $this->visitStats()];
    }

    /**
     * Footer icon links: the admin menu named in options.cms_footer_menu (Menu screen), or the defaults
     * (Dịch vụ, Sự kiện, Hỏi đáp) moved out of the main menu. Icons are chosen by URL in config.
     */
    private function footerQuickLinks(): array
    {
        $menuId = (int) (\App\Models\Option::where('key', \Modules\CmsBackend\Support\HomeSettings::FOOTER_MENU)->value('value') ?? 0);
        $items = $menuId ? $this->navigation((new MenuItemDataProvider())->getMenuItemByMenuId($menuId, true)) : [];
        if (!$items) {
            $items = [
                ['title' => __('pages.dich_vu'), 'url' => ModuleUrl::route('cms.frontend.dichvu.index')],
                ['title' => __('pages.su_kien'), 'url' => ModuleUrl::route('cms.su-kien')],
                ['title' => __('pages.hoi_dap'), 'url' => ModuleUrl::route('cms.frontend.hoidap.index')],
            ];
        }
        $icons = config('cmsfrontend.footer_quick_icons', []);
        return collect($items)->filter(fn ($item) => !empty($item['url']))->map(function ($item) use ($icons) {
            $path = trim((string) parse_url($item['url'], PHP_URL_PATH), '/');
            $icon = collect($icons)->first(fn ($icon, $segment) => $segment !== 'default' && str_contains('/' . $path . '/', '/' . $segment . '/'))
                ?? ($icons['default'] ?? 'ri-links-line');
            return ['title' => $item['title'], 'url' => $item['url'], 'target' => $item['target'] ?? null, 'icon' => $icon];
        })->values()->all();
    }

    /** Cached for a minute so the footer does not query on every request. */
    private function visitStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('cms.footer.visit-stats', 60, function () {
            $total = null;
            $online = null;
            try {
                $total = (int) visits(new \App\Models\SiteVisitStub())->count();
            } catch (\Throwable) {
                // Counter unavailable: the footer hides the number.
            }
            try {
                $online = (int) \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                    ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())->count();
            } catch (\Throwable) {
                // Non-database session driver: "online" is not measurable.
            }
            return ['total' => $total, 'online' => $online];
        });
    }

    /**
     * Whole main menu from admin data (Menu screen → options.main_menu). Items whose target no longer exists
     * (deleted page, category or document group) are hidden, and so are empty non-link parents. Unpublished
     * pages stay visible; their URL shows a "content being updated" notice (TinTucController::show).
     */
    private function navigation($configured): array
    {
        if ($configured->isEmpty()) return [];

        $build = function ($items) use (&$build) {
            $result = [];
            foreach ($items as $item) {
                if (! $this->availableInLanguage($item)) continue;
                $children = $build($item->childs ?? collect());
                if ($item->type === 'feature') {
                    // Chức năng hệ thống: URL sinh từ route trong Feature Registry. Chức năng tắt/thiếu target → ẩn mục (không xóa).
                    $featureUrl = app(\Modules\CmsBackend\Features\FeatureRegistry::class)->url((string) ($item->feature_key ?? ''), (string) ($item->feature_target ?? ''));
                    if ($featureUrl === null) continue; // ẩn cả nhánh con
                    $result[] = [
                        'title' => strip_tags($item->title), 'url' => $featureUrl,
                        'target' => in_array($item->target ?? null, ['_blank', '_self'], true) ? $item->target : null,
                        'icon' => preg_match('/^ri-[a-z0-9-]+$/D', (string) ($item->icon ?? '')) ? $item->icon : null,
                        'children' => $children,
                    ];
                    continue;
                }
                if ($item->type !== 'url' && empty($item->ref)) continue;
                $url = $item->type === 'url' && in_array(trim((string) $item->url), ['/', ''], true) && empty($item->childs)
                    ? ModuleUrl::route('cms.trang-chu') : $this->url($item);
                if (!$url && !$children) continue;
                $result[] = [
                    'title' => strip_tags($item->title), 'url' => $url,
                    'target' => in_array($item->target ?? null, ['_blank', '_self'], true) ? $item->target : null,
                    'icon' => preg_match('/^ri-[a-z0-9-]+$/D', (string) ($item->icon ?? '')) ? $item->icon : null,
                    'children' => $children,
                ];
            }
            return $result;
        };

        return $build($configured);
    }

    private function items($items): array
    {
        // Footer/configured navigation follows the exact same availability rules as the header.
        // In particular, an English menu label must not expose the stale Vietnamese URL when the
        // linked post/category itself has no English translation.
        return $this->navigation(collect($items));
    }

    /**
     * On a page in another language an item is shown only when it can be shown in that language. Items without a title in it are
     * already gone (PublicLocaleScope); the rest of the rule is here: document groups have no translations, and a plain link without
     * an address of its own in the language is kept only when it is not a path of this (Vietnamese) site.
     */
    private function availableInLanguage($item): bool
    {
        if (app(\App\Services\LocaleManager::class)->publicLocale() === null) {
            return true;
        }
        if (($item->type ?? null) === 'docs') {
            return false;
        }
        if (($item->type ?? null) === 'url' && empty($item->url_localized)) {
            $url = trim((string) ($item->url ?? ''));
            $external = (bool) preg_match('~^(https?:)?//|^(mailto|tel):~i', $url);

            return $external || in_array($url, ['', '/', '#'], true);
        }

        return true;
    }

    private function url($item): ?string
    {
        $slug = $item->ref->slug ?? null;
        if ($slug) {
            $route = match ($item->type) {
                'post', 'page' => 'cms.tin-tuc.show',
                'category', 'tag' => 'cms.tin-tuc.index',
                'image' => 'cms.hinh-anh.show',
                'video' => 'cms.video.show',
                default => null,
            };
            if ($route) return ModuleUrl::route($route, $slug);
        }
        if ($item->type === 'docs' && !empty($item->ref->nvb_slug)) {
            return ModuleUrl::route('cms.frontend.vanban.index', $item->ref->nvb_slug);
        }
        $url = trim((string) ($item->url ?? ''));
        return $url !== '' && $url !== '#' && !preg_match('~^(javascript|data):~i', $url) ? ModuleUrl::url($url) : null;
    }
}
