@php
    $configData = Helper::appClasses();
    $isFront = true;
@endphp
@extends('cmsfrontend::screens.components.layouts.commonMaster')
@section('layoutContent')
    <a class="skip-link" href="#main-content">{{ __('frontend.ui.den_noi_dung_chinh') }}</a>
    @include('cmsfrontend::screens.components.layouts.sections.header.header')
    <div id="main-content" class="sbut-page" tabindex="-1">@yield('content')</div>
    @include('cmsfrontend::screens.components.layouts.sections.footer.footer-front')
@endsection
