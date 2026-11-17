@php
    $bd2Home = \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu');
@endphp
{{-- Đầu trang theo thiết kế bannerSBUT: một thanh kem gồm logo + tên trường (hai dòng), tìm kiếm, đổi ngôn ngữ, nút Đăng nhập. --}}
<div class="proposal-progress" aria-hidden="true"></div>
<header class="header sbut-header bd2-header"><div class="bd2-header-inner"><a class="bd2-brand" href="{{ $bd2Home }}" aria-label="{{ __('frontend.ui.ban_de_an_trang_chu') }}"><img src="{{ \Modules\CmsBackend\Support\SiteLogo::urlOr('assets/img/themes/bandean-v2/brand-logo.png') }}" alt="" width="240" height="275" aria-hidden="true"><span class="bd2-brand-text"><b>{{ __('frontend.ui.truong_dai_hoc_cong_nghe_nam_binh_duong') }}</b><small>{{ __('frontend.ui.bd2_ten_tieng_anh') }}</small></span></a><div class="header-actions bd2-header-actions"><button type="button" class="icon-btn" id="searchBtn" aria-label="{{ __('frontend.ui.mo_tim_kiem') }}" aria-expanded="false" aria-controls="sbut-site-search"><i class="ri-search-line" aria-hidden="true"></i></button>@include('cmsfrontend::screens.components.layouts.sections.language-switcher')@auth<a class="bd2-login" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}" title="{{ __('frontend.ui.vao_trang_quan_tri') }}">{{ auth()->user()->name }}</a>@else<a class="bd2-login" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.login') }}">{{ __('frontend.ui.dang_nhap_2') }}</a>@endauth</div></div></header>
<dialog id="sbut-site-search" class="sbut-search-dialog" aria-labelledby="sbut-search-title" aria-describedby="sbut-search-hint">
    <div class="sbut-search-heading">
        <div><div class="eyebrow">{{ __('frontend.ui.tra_cuu_nhanh_ctrl_k') }}</div><h2 id="sbut-search-title">{{ __('frontend.ui.ban_dang_tim_dieu_gi') }}</h2></div>
        <button type="button" class="sbut-search-close" aria-label="{{ __('frontend.ui.dong_tim_kiem') }}">×</button>
    </div>
    <form role="search" method="GET" action="{{ route('cms.tin-tuc.index') }}">
        <label class="cms-visually-hidden" for="sbut-search">{{ __('frontend.ui.tim_kiem_thong_tin') }}</label>
        <div class="sbut-search-field"><i class="ri-search-line" aria-hidden="true"></i><input id="sbut-search" type="search" name="query" value="{{ request('query') }}" placeholder="{{ __('frontend.ui.nganh_hoc_tin_tuc_tuyen_sinh') }}" maxlength="200" autocomplete="off"><button type="submit" aria-label="{{ __('frontend.ui.xem_tat_ca_ket_qua_tim_kiem') }}">↗</button></div>
        <p id="sbut-search-hint">{{ __('frontend.ui.goi_y_cong_nghe_thong_tin_campus_hoc_bong_sinh_v') }}</p>
        <div class="sbut-search-results" aria-label="{{ __('frontend.ui.goi_y_noi_dung_tren_trang') }}"></div>
        <p class="sbut-search-empty" hidden>{{ __('frontend.ui.khong_co_goi_y_tren_trang_nay_nhan_enter_de_tim_') }}</p>
        <button class="sbut-search-all" type="submit" hidden>{{ __('frontend.ui.xem_tat_ca_ket_qua') }}</button>
    </form>
</dialog>
