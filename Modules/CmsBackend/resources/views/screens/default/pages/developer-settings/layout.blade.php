@php $configData = Helper::appClasses(); @endphp
@extends('cmsbackend::screens.components.shared.layouts.commonMaster')
@section('layoutContent')
    {{-- Developer Settings has its own page frame: no CMS menu, no account menu, nothing that needs a signed-in user. --}}
    <div class="container-xxl py-4">
        @yield('content')
    </div>
@endsection
