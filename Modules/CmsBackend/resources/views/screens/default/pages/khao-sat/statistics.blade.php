@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('admin.survey_report.statistics_title'))
@php
    $user = auth()->user();
    $canExport = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user())['khaosat-export'];
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
    $o = $stats['overview'];
    $exportQuery = array_filter($filters, fn ($v) => $v !== null && $v !== '');
@endphp
@section('content')
<div class="cms-native-record">
    @include('cmsbackend::screens.pages.khao-sat._nav', ['active' => 'statistics'])
    <div class="card mb-3"><div class="card-body pb-0">
        @include('cmsbackend::screens.pages.khao-sat._filters', ['action' => $route('cms.khao-sat.statistics', $survey->ks_id), 'full' => false])
    </div></div>

    <div class="row g-3 mb-3">
        @foreach ([[__('admin.survey_report.stat_started'), $o['started']], [__('admin.survey_report.stat_completed'), $o['completed']], [__('admin.survey_report.stat_rate'), $o['rate'].'%'], [__('admin.survey_report.stat_avg'), $o['avg_label']]] as [$label, $value])
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-3 fw-bold">{{ $value }}</div>
            </div></div></div>
        @endforeach
    </div>

    @if ($canExport)
        <p><a class="btn btn-sm btn-outline-success" href="{{ $route('cms.khao-sat.export', $survey->ks_id) }}?{{ http_build_query($exportQuery + ['type' => 'summary']) }}">{{ __('admin.survey_report.export_summary') }}</a></p>
    @endif

    <section class="card mb-3"><div class="card-body">
        <h5>{{ __('admin.survey_report.daily') }}</h5>
        @if ($stats['daily'] === [])
            <p class="text-muted mb-0">{{ __('admin.survey_report.no_data') }}</p>
        @else
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th style="width:130px">{{ __('admin.survey_report.day') }}</th><th class="text-end" style="width:90px">{{ __('admin.survey_report.stat_started') }}</th><th class="text-end" style="width:90px">{{ __('admin.survey_report.stat_completed') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($stats['daily'] as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d['day'])->format('d/m/Y') }}</td>
                        <td class="text-end">{{ $d['started'] }}</td>
                        <td class="text-end">{{ $d['completed'] }}</td>
                        <td aria-hidden="true">
                            <div class="progress mb-1" style="height:6px"><div class="progress-bar bg-secondary" style="width: {{ $d['started_pct'] }}%"></div></div>
                            <div class="progress" style="height:6px"><div class="progress-bar bg-success" style="width: {{ $d['completed_pct'] }}%"></div></div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div></section>

    <h5 class="mt-4">{{ __('admin.survey_report.per_question') }}</h5>
    @forelse ($stats['questions'] as $q)
        <section class="card mb-3"><div class="card-body">
            @if ($q['group'])<div class="small text-muted">{{ $q['group'] }}</div>@endif
            <strong>{{ $q['text'] }}</strong>
            <span class="badge bg-label-secondary ms-1">{{ $q['type_label'] }}</span>
            @if ($q['retired'])<span class="badge bg-label-warning">{{ __('admin.survey_report.retired') }}</span>@endif
            <div class="text-muted small mb-2">{{ $q['respondents'] }} {{ __('admin.survey_report.respondents') }}@if ($q['average'] !== null) · {{ __('admin.survey_report.average') }}: <strong>{{ $q['average'] }}</strong>@endif</div>
            @if ($q['rows'] !== [])
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('admin.survey_report.option') }}</th><th class="text-end" style="width:90px">{{ __('admin.survey_report.count') }}</th><th class="text-end" style="width:90px">{{ __('admin.survey_report.percent') }}</th><th style="width:35%"></th></tr></thead>
                    <tbody>
                    @foreach ($q['rows'] as $r)
                        <tr>
                            <td>{{ $r['label'] }} @if ($r['retired'])<span class="badge bg-label-warning">{{ __('admin.survey_report.retired') }}</span>@endif</td>
                            <td class="text-end">{{ $r['count'] }}</td>
                            <td class="text-end">{{ $r['percent'] }}%</td>
                            <td aria-hidden="true"><div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ round($r['count'] / $q['max'] * 100) }}%"></div></div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @else
                <p class="mb-0 text-muted">{{ $q['text_count'] }} {{ __('admin.survey_report.text_answers') }}</p>
            @endif
        </div></section>
    @empty
        <p class="text-muted">{{ __('admin.survey_report.no_data') }}</p>
    @endforelse
</div>
@endsection
