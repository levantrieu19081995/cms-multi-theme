@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', $categoryInfo->term->name ?? __('pages.danh_sach_tin_tuc'))
@section('description', __('pages.trang_danh_sach_tin_tuc'))
@section('keywords', 'tin tuc, tin tuc NBDUT, tin tuc soc trang, ' . ($categoryInfo->term->name ?? ''))
<!-- Vendor Styles -->
@section('vendor-style')
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
    @vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/su-kien/su-kien.css', 'Modules/CmsFrontend/resources/assets/css/guong-tieu-bieu/guong-tieu-bieu.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/js/helpers.js', 'assets/vendor/libs/jquery/jquery.js', 'assets/vendor/js/dropdown-hover.js', 'assets/vendor/js/mega-dropdown.js', 'assets/vendor/libs/popper/popper.js', 'assets/vendor/js/bootstrap.js', 'assets/vendor/libs/node-waves/node-waves.js', 'assets/vendor/libs/toastr/toastr.js', 'assets/vendor/libs/sweetalert2/sweetalert2.js', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js']))
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['Modules/CmsFrontend/resources/assets/js/listing.js'])
@endsection
@section('content')
    @if (is_array($categories) && count($categories) !== 0)
        @include('cmsfrontend::screens.components.partials.listingPartial')
    @else
        @include('cmsfrontend::screens.components.partials.categoryPartial')
    @endif
@endsection
