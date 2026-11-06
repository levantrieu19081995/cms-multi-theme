<div class="utility"><div class="wrap utility-inner"><span>{{ __('frontend.ui.khai_phong_uu_tu_cong_hien_3') }}</span><div><a href="{{ route('cms.trang-chu') }}#sinh-vien">{{ __('frontend.ui.sinh_vien') }}</a><a href="{{ route('cms.trang-chu') }}#giang-vien">{{ __('frontend.ui.giang_vien') }}</a><a href="{{ route('cms.trang-chu') }}#tuyen-sinh">{{ __('frontend.ui.thi_sinh_phu_huynh') }}</a><span class="utility-divider"></span>@auth<a href="{{ route('cms.dashboard') }}">{{ __('frontend.ui.quan_tri') }}</a>@else<a href="{{ route('cms.login') }}">{{ __('frontend.ui.dang_nhap_2') }}</a>@endauth</div></div></div>
<header class="header sbut-header"><div class="wrap header-inner">@php($schoolName = __('frontend.brand.name'))<a href="{{ route('cms.trang-chu') }}" class="brand brand--nbdut brand--mark" aria-label="{{ $schoolName }} — {{ __('frontend.ui.trang_chu') }}"><img src="{{ \Modules\CmsBackend\Support\SiteLogo::urlOr('assets/img/themes/nam-binh-duong/nbdut-favicon.png') }}" alt="" width="512" height="512" aria-hidden="true"><b class="brand-name">{{ $schoolName }}</b></a><div class="header-actions">@include('cmsfrontend::screens.components.layouts.sections.language-switcher')<button type="button" class="icon-btn" id="searchBtn" aria-label="{{ __('frontend.ui.mo_tim_kiem') }}" aria-expanded="false" aria-controls="sbut-site-search"><i class="ri-search-line" aria-hidden="true"></i></button></div></div></header>
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
