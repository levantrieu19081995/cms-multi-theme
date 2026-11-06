@php
    $configData = Helper::appClasses();
    $isFront = true;
@endphp

@extends('cmsfrontend::screens.components.layouts.commonMaster')

@section('layoutContent')

        @include('cmsfrontend::screens.components.layouts.sections.header.header')
        {{-- @excludeMinify --}}
            @include('cmsfrontend::screens.components.layouts.sections.navbar.navbar-front')
        {{-- @endExcludeMinify --}}
        {{-- @include('cmsfrontend::screens.components.layouts.sections.banner.banner') --}}
        @yield('content')
        @include('cmsfrontend::screens.components.layouts.sections.footer.footer-front')
@endsection
