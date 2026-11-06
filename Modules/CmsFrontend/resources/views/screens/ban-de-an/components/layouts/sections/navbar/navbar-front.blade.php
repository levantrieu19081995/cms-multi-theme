@php
    // Top-level icon: first keyword (config menu_icons) found in the item's slugged title.
    $cmsMenuIcon = function (string $title): string {
        $slug = \Illuminate\Support\Str::slug(strip_tags($title));
        foreach (config('cmsfrontend.menu_icons', []) as $keyword => $icon) {
            if ($keyword !== 'default' && str_contains($slug, $keyword)) return $icon;
        }
        return config('cmsfrontend.menu_icons.default', 'ri-arrow-right-s-line');
    };
    // Active top-level item: the current page is the item, one of its submenu links, or a sub-path of one of them
    // (e.g. /tin-tuc/{category}, /thu-vien-hinh-anh/{album}). The home link only matches itself.
    $cmsPath = fn (?string $url) => $url ? rtrim((string) parse_url($url, PHP_URL_PATH), '/') : null;
    $cmsCurrentPath = $cmsPath(url()->current());
    $cmsHomePath = $cmsPath(\Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu'));
    // Score = length of the longest matching menu path (-1 = no match); only the best-scoring top-level item is
    // active, so /tin-tuc/thong-bao-su-kien does not also light up the parent "/tin-tuc" item.
    $cmsScore = function (array $item) use (&$cmsScore, $cmsPath, $cmsCurrentPath, $cmsHomePath): int {
        $best = -1;
        $path = $cmsPath($item['url'] ?? null);
        if ($path !== null && ($path === $cmsCurrentPath || ($path !== $cmsHomePath && str_starts_with($cmsCurrentPath . '/', $path . '/')))) $best = strlen($path) + ($path === $cmsCurrentPath ? 1 : 0);
        foreach ($item['children'] ?? [] as $child) {
            $best = max($best, $cmsScore($child));
        }
        return $best;
    };
    $cmsScores = array_map($cmsScore, $cmsNavigation);
    $cmsTop = $cmsScores ? max($cmsScores) : -1;
    $cmsActive = array_map(fn ($score) => $cmsTop >= 0 && $score === $cmsTop, $cmsScores);
    if (($firstActive = array_search(true, $cmsActive, true)) !== false) {
        $cmsActive = array_map(fn ($i) => $i === $firstActive, array_keys($cmsActive));
    }
    // Article pages (/{slug}.htm) have no menu path: fall back to the item that links to the news list.
    if (!in_array(true, $cmsActive, true) && request()->routeIs('cms.tin-tuc.show')) {
        $cmsNewsPath = $cmsPath(\Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index'));
        foreach ($cmsNavigation as $index => $item) {
            if ($cmsPath($item['url'] ?? null) === $cmsNewsPath) { $cmsActive[$index] = true; break; }
        }
    }
@endphp
<nav class="cms-nav" aria-label="{{ __('frontend.ui.dieu_huong_chinh') }}"><div class="cms-shell-container cms-nav-inner">
    <button class="cms-menu-toggle" type="button" aria-expanded="false" aria-controls="cms-main-menu">☰ <span>{{ __('frontend.ui.danh_muc') }}</span></button>
    <ul id="cms-main-menu" class="cms-menu">
        @foreach ($cmsNavigation as $index => $item)
            @php($itemActive = $cmsActive[$index] ?? false)
            <li class="cms-menu-item @if($itemActive) is-active @endif">
                @if (!empty($item['children']))
                    @php($childCount = count($item['children']))
                    <details class="cms-submenu"><summary @if($itemActive) aria-current="true" @endif><i class="{{ $item['icon'] ?? $cmsMenuIcon($item['title']) }}" aria-hidden="true"></i>{{ $item['title'] }}</summary>
                        {{-- Popup panel: one column up to 4 items, two columns beyond that. --}}
                        <div class="cms-submenu-panel @if($childCount > 4) cms-submenu-panel--two @endif" style="--cms-submenu-rows: {{ $childCount > 4 ? (int) ceil($childCount / 2) : $childCount }}">
                            <div class="cms-submenu-head">
                                <span class="cms-submenu-eyebrow">{{ $item['title'] }}</span>
                                @if($item['url'])<a class="cms-submenu-all" href="{{ $item['url'] }}" @if(($item['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif>{{ __('frontend.ui.xem_tat_ca') }} </a>@endif
                            </div>
                            <ul class="cms-submenu-list">
                                @include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item', ['items' => $item['children']])
                            </ul>
                        </div>
                    </details>
                @elseif ($item['url'])
                    <a href="{{ $item['url'] }}" @if($itemActive) aria-current="page" @endif><i class="{{ $item['icon'] ?? $cmsMenuIcon($item['title']) }}" aria-hidden="true"></i>{{ $item['title'] }}</a>
                @else
                    <span class="cms-menu-unavailable" title="{{ __('frontend.ui.chua_co_noi_dung_gioi_thieu') }}"><i class="{{ $item['icon'] ?? $cmsMenuIcon($item['title']) }}" aria-hidden="true"></i>{{ $item['title'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div></nav>
