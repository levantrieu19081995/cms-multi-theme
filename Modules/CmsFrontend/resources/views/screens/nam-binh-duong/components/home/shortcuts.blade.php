@php
    $shortcutIcons = ['tuyen-sinh'=>'ri-graduation-cap-line','dao-tao'=>'ri-book-open-line','lien-ket'=>'ri-team-line','nghien-cuu'=>'ri-flask-line'] + config('cmsfrontend.menu_icons', []);
@endphp
@if(!empty($homeShortcutNavigation))
<nav class="quick-links" aria-label="{{ __('frontend.ui.truy_cap_cac_chuc_nang') }}"><button class="sbut-shortcut-backdrop" type="button" tabindex="-1" aria-label="{{ __('frontend.ui.dong_menu_con') }}"></button><div class="wrap quick-inner sbut-shortcut-inner">
@foreach($homeShortcutNavigation as $item)
    @php
        $titleSlug = \Illuminate\Support\Str::slug($item['title']);
        $icon = collect($shortcutIcons)->first(fn ($value,$keyword) => $keyword !== 'default' && str_contains($titleSlug,$keyword)) ?? 'ri-links-line';
    @endphp
    <div class="sbut-shortcut-item">
    @if(!empty($item['children']))
        <details class="sbut-shortcut-dropdown"><summary class="sbut-shortcut-trigger"><i class="{{ $icon }}" aria-hidden="true"></i><span><strong>{{ $item['title'] }}</strong></span></summary>
            <div class="sbut-shortcut-panel">
                <div class="sbut-shortcut-head"><span>{{ $item['title'] }}</span>@if($item['url'])<a class="sbut-shortcut-all" href="{{ $item['url'] }}" @if(($item['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif>{{ __('frontend.ui.xem_tat_ca') }}</a>@endif</div>
                <ul @class(['sbut-shortcut-list','sbut-shortcut-list--columns'=>count($item['children'])>4]) style="--sbut-menu-rows:{{ (int) ceil(count($item['children'])/2) }}">
                    @include('cmsfrontend::screens.components.layouts.sections.navbar.menu-item',['items'=>$item['children']])
                </ul>
            </div>
        </details>
    @else
        <a class="sbut-shortcut-trigger" href="{{ $item['url'] }}" @if(($item['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif><i class="{{ $icon }}" aria-hidden="true"></i><span><strong>{{ $item['title'] }}</strong></span></a>
    @endif
    </div>
@endforeach
</div></nav>
@endif
<nav id="mainNav" class="section-shortcuts" aria-label="{{ __('frontend.ui.dieu_huong_tren_trang') }}"><div class="wrap"><span>{{ __('frontend.ui.kham_pha_tren_trang') }}</span>@foreach(['trang-chu'=>__('pages.trang_chu'),'gioi-thieu'=>__('pages.gioi_thieu'),'dao-tao'=>__('pages.dao_tao'),'nghien-cuu'=>__('pages.nghien_cuu'),'tin-tuc'=>__('pages.tin_tuc'),'tuyen-sinh'=>__('pages.tuyen_sinh')] as $id=>$label)<a href="{{ route('cms.trang-chu') }}#{{ $id }}" data-sbut-section="{{ $id }}">{{ $label }}</a>@endforeach</div></nav>
<nav class="sbut-section-rail" data-sbut-rail aria-label="{{ __('frontend.ui.muc_tren_trang') }}">@foreach(['gioi-thieu'=>__('pages.gioi_thieu'),'dao-tao'=>__('pages.dao_tao'),'nghien-cuu'=>__('pages.nghien_cuu'),'tin-tuc'=>__('pages.tin_tuc'),'tuyen-sinh'=>__('pages.tuyen_sinh')] as $id=>$label)<a href="#{{ $id }}" data-sbut-rail-target="{{ $id }}">{{ $label }}</a>@endforeach<a class="sbut-rail-top" href="#trang-chu" data-sbut-rail-target="top">{{ __('frontend.ui.len_dau_trang') }}</a></nav>
<div class="admission-ticker"><div class="wrap ticker-inner"><span class="ticker-label"><span></span>{{ __('frontend.ui.goc_tuyen_sinh') }}</span><a href="#tuyen-sinh">{{ __('frontend.ui.tim_hieu_chuong_trinh_dao_tao_va_thong_tin_danh_') }}</a><a href="#tuyen-sinh" class="text-button">{{ __('frontend.ui.xem_thong_tin') }}</a></div></div>
