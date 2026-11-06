@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.title'))

@php
    $cards = [
        ['key' => 'total', 'label' => 'admin.social.stat_total', 'icon' => 'ri-stack-line', 'tone' => 'primary'],
        ['key' => 'draft', 'label' => 'admin.social.stat_draft', 'icon' => 'ri-draft-line', 'tone' => 'secondary'],
        ['key' => 'in_progress', 'label' => 'admin.social.stat_in_progress', 'icon' => 'ri-loader-4-line', 'tone' => 'warning'],
        ['key' => 'published', 'label' => 'admin.social.stat_published', 'icon' => 'ri-check-double-line', 'tone' => 'success'],
        ['key' => 'failed', 'label' => 'admin.social.stat_failed', 'icon' => 'ri-error-warning-line', 'tone' => 'danger'],
        ['key' => 'connected_accounts', 'label' => 'admin.social.stat_connected_accounts', 'icon' => 'ri-links-line', 'tone' => 'info'],
    ];
@endphp

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.index') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    <div class="row g-4 mb-4">
        @foreach ($cards as $card)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-{{ $card['tone'] }} p-2" aria-hidden="true"><i class="{{ $card['icon'] }} ri-24px"></i></span>
                        <div>
                            <div class="h4 mb-0">{{ number_format($overview[$card['key']]) }}</div>
                            <small class="text-muted">{{ __($card['label']) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @foreach ($overview['channels'] as $item)
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <i class="{{ $item['channel']->icon() }} ri-32px" aria-hidden="true"></i>
                            <div>
                                <h6 class="mb-0">{{ $item['channel']->label() }}</h6>
                                @if ($item['connected'])
                                    <small class="text-muted">{{ $item['account'] }}</small>
                                @endif
                            </div>
                        </div>
                        @if ($item['connected'])
                            <span class="badge bg-label-success">{{ __('admin.social.account_status_connected') }}</span>
                        @else
                            <span class="badge bg-label-secondary">{{ __('admin.social.not_connected') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-primary text-white">
            <h5 class="mb-0 text-white text-uppercase">{{ __('admin.social.recent_title') }}</h5>
            <div class="d-flex gap-2">
                @if ($can['social-publication-create'])
                    <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.create') }}" class="btn btn-sm btn-light"><i class="ri-add-line me-1" aria-hidden="true"></i>{{ __('admin.social.create_title') }}</a>
                @endif
                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.index') }}" class="btn btn-sm btn-outline-light">{{ __('admin.social.view_all') }}</a>
            </div>
        </div>
        <div class="card-body mt-4">
            @if ($recent->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="ri-share-forward-line ri-48px d-block mb-2" aria-hidden="true"></i>
                    <p class="mb-1 fw-medium">{{ __('admin.social.empty_title') }}</p>
                    <p class="mb-0 small">{{ __('admin.social.empty_text') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr class="text-nowrap">
                                <th>{{ __('admin.social.col_content') }}</th>
                                <th>{{ __('admin.social.col_channels') }}</th>
                                <th>{{ __('admin.social.col_status') }}</th>
                                <th>{{ __('admin.social.col_time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $publication)
                                <tr>
                                    <td>
                                        <a class="fw-bold" href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.truyen-thong.publications.show', $publication->id) }}">{{ $publication->title }}</a>
                                    </td>
                                    <td>@include('cmsbackend::screens.pages.truyen-thong.partials.deliveries', ['publication' => $publication])</td>
                                    <td><span class="badge {{ $publication->status->badge() }}">{{ $publication->status->label() }}</span></td>
                                    <td class="text-nowrap">{{ $publication->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
