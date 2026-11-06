@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = Helper::appClasses();
    $isFront = true;
    $customizerHidden = $customizerHidden ?? '';

@endphp
@extends('cmsfrontend::screens.components.layouts.commonMaster')
{{-- @extends('cmsbackend::shared.layouts/commonMaster') --}}

@section('layoutContent')

        @include('cmsfrontend::screens.components.layouts.sections.header.header')
        @include('cmsfrontend::screens.components.layouts.sections.navbar.navbar-front')


        @yield('content')
        @include('cmsfrontend::screens.components.layouts.sections.footer.footer-front')
@endsection
