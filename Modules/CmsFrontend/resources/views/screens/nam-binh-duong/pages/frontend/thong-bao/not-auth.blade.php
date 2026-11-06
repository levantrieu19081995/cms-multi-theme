@extends('cmsfrontend::screens.components.layouts.blankLayout')

{{-- @section('page-style')
    @vite(['Modules/NhaCaMauFrontPage/resources/assets/css/home.css'])
@endsection --}}

@section('title', __('pages.thong_bao'))
@section('description', __('pages.trang_thong_bao_yeu_cau_dang_nhap_de_xem_noi_dun'))

@section('content')
    <div class="container py-5 text-center">
        <h1 class="mb-4 text-danger">{{ __('frontend.ui.thong_bao') }}</h1>
        @if (session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
            </div>
        @endif

        <p class="lead">{{ __('frontend.ui.day_la_thong_tin_noi_bo_ban_can') }} <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('login') }}">{{ __('frontend.ui.dang_nhap') }}</a> {{ __('frontend.ui.de_xem_noi_dung_nay') }}
        </p>
        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('login') }}" class="btn btn-primary mt-3">{{ __('frontend.ui.dang_nhap_2') }}</a>
    </div>
@endsection
