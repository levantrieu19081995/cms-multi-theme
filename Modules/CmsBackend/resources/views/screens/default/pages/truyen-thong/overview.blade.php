@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.social.overview_stats_title'))

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
    @include('cmsbackend::screens.pages.truyen-thong.partials.project-badge', ['hideGuideLink' => true])

    {{-- Overview: figures, connection of each channel (needs the publication view permission). --}}
    @if ($overview !== null)
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
    @endif

    {{-- Statistics: by status, channel and creator, and the engagement read back from each network (needs the statistics permission). --}}
    @if ($stats !== null)
        <h6 class="text-uppercase text-muted d-flex align-items-center gap-2 mb-3">
            <i class="ri-bar-chart-2-line" aria-hidden="true"></i><span>{{ __('admin.social.stats_section_title') }}</span>
        </h6>
        <div class="alert alert-info" role="note">{{ __('admin.social.statistics_internal_note') }}</div>

        @php
            // Presentation only: the figures of the statistics, shaped for the charts (colours = tone of the badge of the status).
            $statusColors = [
                'draft' => '#adb5bd', 'ready' => '#7cc4fa', 'queued' => '#f6c26b', 'processing' => '#f4a261',
                'partial' => '#ffe08a', 'published' => '#4caf50', 'failed' => '#ef6f61', 'archived' => '#ced4da',
            ];
            $channelColors = ['facebook' => '#2f6fed', 'zalo' => '#7cc4fa'];
            $chartText = [
                'quantity' => __('admin.social.chart_quantity'),
                'noData' => __('admin.social.chart_no_data'),
                'center' => __('admin.social.chart_center_label'),
            ];
            $statusChart = [
                'labels' => array_map(fn ($row) => $row['status']->label(), $stats['statuses']),
                'series' => array_map(fn ($row) => (int) $row['total'], $stats['statuses']),
                'colors' => array_map(fn ($row) => $statusColors[$row['status']->value] ?? '#adb5bd', $stats['statuses']),
                'i18n' => $chartText,
            ];
            $channelChart = [
                'labels' => array_map(fn ($row) => $row['channel']->label(), $stats['channels']),
                'series' => array_map(fn ($row) => (int) $row['total'], $stats['channels']),
                'colors' => array_map(fn ($row) => $channelColors[$row['channel']->value] ?? '#adb5bd', $stats['channels']),
                'i18n' => $chartText,
            ];
            $creators = collect($stats['creators']);
            $creatorChart = [
                'labels' => $creators->map(fn ($row) => $row->name ?? __('admin.social.unknown_user'))->values()->all(),
                'series' => $creators->map(fn ($row) => (int) $row->total)->values()->all(),
                'colors' => ['#5b6cf0'],
                'i18n' => $chartText,
            ];
        @endphp

        <div class="row g-4 mb-4">
            {{-- By status: total, then one bar per status --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar-initial rounded bg-label-primary p-2" aria-hidden="true"><i class="ri-stack-line ri-24px"></i></span>
                            <h5 class="mb-0">{{ __('admin.social.stats_by_status') }}</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3 rounded bg-label-primary px-3 py-2 mb-3">
                            <span class="h3 mb-0">{{ number_format($stats['total']) }}</span>
                            <span>{{ __('admin.social.stat_total') }}</span>
                        </div>
                        <div id="socialStatusChart" role="img" aria-label="{{ __('admin.social.stats_by_status') }}" data-chart="{{ json_encode($statusChart) }}"></div>
                    </div>
                </div>
            </div>

            {{-- By channel: donut with the total in the middle, and the figures of each channel beside it --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar-initial rounded bg-label-info p-2" aria-hidden="true"><i class="ri-broadcast-line ri-24px"></i></span>
                            <h5 class="mb-0">{{ __('admin.social.stats_by_channel') }}</h5>
                        </div>
                        <div id="socialChannelChart" role="img" aria-label="{{ __('admin.social.stats_by_channel') }}" data-chart="{{ json_encode($channelChart) }}"></div>
                        <div class="mt-3">
                            @foreach ($stats['channels'] as $row)
                                <div class="d-flex align-items-center justify-content-between gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <div>
                                        <span class="fw-medium"><i class="{{ $row['channel']->icon() }} me-1" aria-hidden="true"></i>{{ $row['channel']->label() }}</span>
                                        <div class="small text-muted">
                                            {{ __('admin.social.channel_status_published') }}: {{ number_format($row['published']) }} ·
                                            {{ __('admin.social.channel_status_failed') }}: {{ number_format($row['failed']) }}
                                        </div>
                                    </div>
                                    <strong class="h5 mb-0">{{ number_format($row['total']) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- By creator: area chart, then the list --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar-initial rounded bg-label-secondary p-2" aria-hidden="true"><i class="ri-user-line ri-24px"></i></span>
                            <h5 class="mb-0">{{ __('admin.social.stats_by_creator') }}</h5>
                        </div>
                        <div id="socialCreatorChart" role="img" aria-label="{{ __('admin.social.stats_by_creator') }}" data-chart="{{ json_encode($creatorChart) }}"></div>
                        <div class="mt-3">
                            @forelse ($stats['creators'] as $row)
                                <div class="d-flex justify-content-between align-items-center rounded bg-label-secondary px-3 py-2 mb-2">
                                    <span><i class="ri-user-line me-1" aria-hidden="true"></i>{{ $row->name ?? __('admin.social.unknown_user') }}</span>
                                    <strong>{{ number_format($row->total) }}</strong>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ __('admin.social.empty_title') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <h5 class="card-header text-uppercase">{{ __('admin.social.stats_interaction') }}</h5>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($stats['metrics'] as $metric)
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted"><i class="{{ $metric['channel']->icon() }} me-1" aria-hidden="true"></i>{{ $metric['channel']->label() }}</div>
                                <div class="fw-medium">{{ __('admin.social.metric_'.$metric['key']) }}</div>
                                @include('cmsbackend::screens.pages.truyen-thong.partials.metric-value', ['metric' => $metric])
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Recent requests (publication view permission). --}}
    @if ($recent !== null)
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
    @endif
@endsection

@section('page-script')
    @if ($stats !== null)
        @vite('Modules/CmsBackend/resources/assets/js/social-stats.js')
    @endif
@endsection
