@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.statistics_title'))

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.truyen-thong.statistics') }}
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge')

    <div class="alert alert-info" role="note">{{ __('admin.social.statistics_internal_note') }}</div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <h5 class="card-header text-uppercase">{{ __('admin.social.stats_by_status') }}</h5>
                <div class="card-body">
                    <p class="h3 mb-3">{{ number_format($stats['total']) }} <small class="text-muted fs-6">{{ __('admin.social.stat_total') }}</small></p>
                    <ul class="list-unstyled mb-0">
                        @foreach ($stats['statuses'] as $row)
                            <li class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge {{ $row['status']->badge() }}">{{ $row['status']->label() }}</span>
                                <strong>{{ number_format($row['total']) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <h5 class="card-header text-uppercase">{{ __('admin.social.stats_by_channel') }}</h5>
                <div class="card-body">
                    @foreach ($stats['channels'] as $row)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-medium"><i class="{{ $row['channel']->icon() }} me-1" aria-hidden="true"></i>{{ $row['channel']->label() }}</span>
                                <strong>{{ number_format($row['total']) }}</strong>
                            </div>
                            <div class="small text-muted">
                                {{ __('admin.social.channel_status_published') }}: {{ number_format($row['published']) }} ·
                                {{ __('admin.social.channel_status_failed') }}: {{ number_format($row['failed']) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <h5 class="card-header text-uppercase">{{ __('admin.social.stats_by_creator') }}</h5>
                <div class="card-body">
                    @forelse ($stats['creators'] as $row)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $row->name ?? __('admin.social.unknown_user') }}</span>
                            <strong>{{ number_format($row->total) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ __('admin.social.empty_title') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header text-uppercase">{{ __('admin.social.stats_interaction') }}</h5>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($stats['metrics'] as $metric)
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted"><i class="{{ $metric['channel']->icon() }} me-1" aria-hidden="true"></i>{{ $metric['channel']->label() }}</div>
                            <div class="fw-medium">{{ __('admin.social.metric_'.$metric['key']) }}</div>
                            @if ($metric['available'])
                                <div class="text-muted">—</div>
                            @else
                                <div class="small text-muted">{{ __('admin.social.metric_after_connect', ['channel' => $metric['channel']->label()]) }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
