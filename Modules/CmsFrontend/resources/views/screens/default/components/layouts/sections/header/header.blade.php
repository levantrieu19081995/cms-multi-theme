<header class="cms-header">
    {{-- Masthead: rice-field photo (--cms-masthead-image) with the utility links laid over its top edge. --}}
    <div class="cms-masthead">
        <div class="cms-utility"><div class="cms-shell-container">
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}"><i class="ri-leaf-fill" aria-hidden="true"></i> {{ __('frontend.ui.cong_thong_tin_khuyen_nong_can_tho') }}</a>
            <div>@if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))<a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.lien-he') }}">{{ __('frontend.ui.lien_he') }}</a><span aria-hidden="true">|</span>@endif
                @auth
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}">{{ __('frontend.ui.quan_tri') }}</a>
                @else
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.login') }}">{{ __('frontend.ui.dang_nhap_2') }}</a>
                @endauth
            </div>
        </div></div>
        <div class="cms-shell-container cms-masthead-row">
            <a class="cms-brand" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}" aria-label="{{ __('frontend.ui.khuyen_nong_can_tho_trang_chu') }}">
                <span class="cms-brand-card">@if ($siteLogo = \Modules\CmsBackend\Support\SiteLogo::url())<img class="cms-brandmark cms-brandmark--site" src="{{ $siteLogo }}" alt="{{ __('frontend.ui.logo_khuyen_nong_can_tho') }}">@else<span class="cms-brandmark" role="img" aria-label="{{ __('frontend.ui.logo_khuyen_nong_can_tho') }}"></span>@endif</span>
                <span class="cms-brand-copy"@if ($titleColor = \Modules\CmsFrontend\Support\Brand::titleColor()) style="--cms-brand-title-color: {{ $titleColor }}"@endif><span class="cms-brand-title">{{ __('frontend.ui.khuyen_nong') }} <strong>{{ __('frontend.ui.can_tho') }}</strong></span>
                    <span class="cms-brand-unit">{{ \Modules\CmsFrontend\Support\Brand::unit() }}</span>
                </span>
            </a>
            {{-- Search dialog (behaviour: assets/shared/search-dialog.js). Same GET search as before: news index, ?query= --}}
            <dialog id="cms-site-search" class="cms-search-dialog" aria-labelledby="cms-search-title">
                <div class="cms-search-heading">
                    <div><div class="cms-search-eyebrow">{{ __('frontend.ui.tra_cuu_nhanh_ctrl_k') }}</div><h2 id="cms-search-title">{{ __('frontend.ui.ban_dang_tim_dieu_gi') }}</h2></div>
                    <button type="button" class="cms-search-close" aria-label="{{ __('frontend.ui.dong_tim_kiem') }}">×</button>
                </div>
                <form class="cms-search" role="search" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index') }}" method="GET">
                    <label class="cms-visually-hidden" for="cms-search-query">{{ __('frontend.ui.tim_kiem_bai_viet') }}</label>
                    <div class="cms-search-field"><i class="ri-search-line" aria-hidden="true"></i><input id="cms-search-query" name="query" type="search" placeholder="{{ __('frontend.ui.tim_kiem_bai_viet_2') }}" value="{{ request('query') }}" maxlength="200" autocomplete="off"><button type="submit" aria-label="{{ __('frontend.ui.tim_kiem') }}">↗</button></div>
                </form>
            </dialog>
            @include('cmsfrontend::screens.components.layouts.sections.language-switcher')
            <button type="button" class="cms-search-trigger" id="cms-search-open" aria-label="{{ __('frontend.ui.mo_tim_kiem') }}" aria-expanded="false" aria-controls="cms-site-search"><i class="ri-search-line" aria-hidden="true"></i></button>
        </div>
    </div>
</header>
