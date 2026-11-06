@php
$configData = Helper::appClasses();
$isFront = true;
@endphp

@section('layoutContent')

@extends('cmsbackend::shared.layouts/commonMaster')

@include('cmsbackend::shared.layouts/sections/navbar/navbar-front')

<!-- Sections:Start -->
@yield('content')
<!-- / Sections:End -->

@include('cmsbackend::shared.layouts/sections/footer/footer-front')
@endsection
