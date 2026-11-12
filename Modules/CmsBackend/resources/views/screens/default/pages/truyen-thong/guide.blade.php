@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $guide['title'])

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $rawUrl = \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'guides.raw', ['scope' => $guide['scope'], 'file' => $guide['file']]);
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.guides.show') }}

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 text-break">{{ $guide['title'] }}</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'guides.index') }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1" aria-hidden="true"></i>{{ __('admin.social.guide_back') }}
                </a>
                <a href="{{ $rawUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="ri-external-link-line me-1" aria-hidden="true"></i>{{ __('admin.social.guide_open_tab') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- The document is shown as it is, in a frame that cannot run scripts; its links open in a new window. --}}
            <iframe src="{{ $rawUrl }}" title="{{ $guide['title'] }}" sandbox="allow-popups allow-popups-to-escape-sandbox"
                referrerpolicy="no-referrer" style="display:block;width:100%;height:78vh;min-height:480px;border:0;background:#fff"></iframe>
        </div>
    </div>
@endsection
