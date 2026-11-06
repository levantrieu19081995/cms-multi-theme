@extends('cmsfrontend::screens.components.layouts.blankLayout')

@section('title', $title)
@section('description', __('pages.noi_dung_dang_duoc_cap_nhat'))

@section('content')
    <main class="cms-shell-container cms-pending-page">
        <nav class="cms-pending-crumb" aria-label="{{ __('frontend.ui.duong_dan') }}">
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}">{{ __('frontend.ui.trang_chu') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $title }}</span>
        </nav>
        <section class="cms-pending-card" aria-labelledby="cms-pending-title">
            <span class="cms-pending-icon" aria-hidden="true"><i class="ri-time-line"></i></span>
            <h1 id="cms-pending-title">{{ $title }}</h1>
            <p>{{ __('frontend.ui.noi_dung_dang_duoc_cap_nhat_vui_long_quay_lai_sa') }}</p>
            <a class="cms-pending-home" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu') }}">{{ __('frontend.ui.ve_trang_chu') }}</a>
        </section>
    </main>
@endsection
