@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.guides_title'))

@php
    $routeBase = 'cms.dashboard.truyen-thong.';
    $groups = [
        ['title' => __('admin.social.guides_section_common'), 'icon' => 'ri-global-line', 'guides' => $common, 'show' => true],
        ['title' => __('admin.social.guides_section_project', ['project' => $projectLabel ?? '']), 'icon' => 'ri-building-line', 'guides' => $project, 'show' => $projectLabel !== null],
    ];
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.guides.index') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge', ['hideGuideLink' => true])

    {{-- No <table> here on purpose: the shared list layout (components/editorial/list-layout) turns a card that holds a table into a
         list card (title removed, no padding). This page is a list of links, so it is built as a list group. --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <i class="ri-book-open-line fs-4" aria-hidden="true"></i>
            <h5 class="mb-0 text-uppercase">{{ __('admin.social.guides_title') }}</h5>
        </div>
        <div class="card-body mt-4">
            <p class="text-muted mb-4">{{ __('admin.social.guides_intro') }}</p>

            @foreach ($groups as $group)
                @continue(! $group['show'])
                <h6 class="text-uppercase text-muted d-flex align-items-center gap-2 mt-4 mb-3">
                    <i class="{{ $group['icon'] }}" aria-hidden="true"></i><span>{{ $group['title'] }}</span>
                </h6>

                @if ($group['guides'] === [])
                    <div class="alert alert-secondary mb-0" role="note">{{ __('admin.social.guides_empty') }}</div>
                @else
                    <div class="list-group">
                        @foreach ($group['guides'] as $guide)
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route($routeBase.'guides.show', ['scope' => $guide['scope'], 'file' => $guide['file']]) }}"
                                class="list-group-item list-group-item-action d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                                <span class="d-flex align-items-center gap-3" style="min-width:0">
                                    <i class="ri-file-text-line fs-3 text-primary flex-shrink-0" aria-hidden="true"></i>
                                    <span style="min-width:0">
                                        <span class="fw-bold d-block text-break">{{ $guide['title'] }}</span>
                                        <span class="text-muted small d-block text-break">{{ $guide['file'] }}</span>
                                    </span>
                                </span>
                                <span class="text-muted small text-nowrap">{{ __('admin.social.guides_col_updated') }}: {{ $guide['modified']->format('d/m/Y H:i') }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection
