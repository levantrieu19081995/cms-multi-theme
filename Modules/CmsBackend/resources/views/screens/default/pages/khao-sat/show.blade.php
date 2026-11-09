@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('admin.survey_report.detail_title'))
@php
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route(\Modules\CmsBackend\Support\SurveyCluster::name($name), $params);
    $hasContact = ! $participation->ksu_an_danh && (trim((string) $participation->ksu_ho_ten) !== '' || trim((string) $participation->ksu_so_dien_thoai) !== '' || trim((string) $participation->ksu_email) !== '');
    $device = \Illuminate\Support\Str::limit((string) $participation->ksu_user_agent, 120);
@endphp
@section('content')
<div class="cms-native-record">
    <div class="mb-3">
        <a href="{{ $route('cms.khao-sat.responses', $participation->ks_id) }}" class="text-muted"><i class="ri-arrow-left-line" aria-hidden="true"></i> {{ __('admin.survey_report.back_to_list') }}</a>
        <h3 class="mb-0 mt-1">{{ __('admin.survey_report.detail_title') }} <code class="fs-6">{{ \Illuminate\Support\Str::limit($participation->ksu_ma, 8, '') }}</code></h3>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card mb-3"><div class="card-body">
                <h5>{{ __('admin.survey_report.survey_info') }}</h5>
                <p class="mb-1"><strong>{{ $survey?->ks_ten }}</strong></p>
                <p class="mb-1 text-muted">{{ __('admin.survey_report.started_at') }}: {{ $participation->ksu_created_at?->format('H:i d/m/Y') }}</p>
                <p class="mb-1 text-muted">{{ __('admin.survey_report.completed_at') }}: {{ $participation->ksu_hoan_thanh_at?->format('H:i d/m/Y') ?? '—' }}</p>
                <p class="mb-1 text-muted">{{ __('admin.survey_report.duration') }}: {{ \Modules\CmsBackend\Services\SurveyReportService::duration($participation->ksu_thoi_luong) }}</p>
                <span class="badge bg-{{ $participation->isCompleted() ? 'success' : 'secondary' }}">{{ $participation->isCompleted() ? __('admin.survey_report.completed') : __('admin.survey_report.in_progress') }}</span>
            </div></section>
            <section class="card mb-3"><div class="card-body">
                <h5>{{ __('admin.survey_report.participant_info') }}</h5>
                <p class="mb-1"><strong>{{ \Modules\CmsBackend\Services\SurveyReportService::displayName($participation) }}</strong></p>
                @if ($hasContact)
                    @if ($participation->ksu_so_dien_thoai)<p class="mb-1">{{ __('admin.survey_report.phone') }}: {{ $participation->ksu_so_dien_thoai }}</p>@endif
                    @if ($participation->ksu_email)<p class="mb-1">{{ __('admin.survey_report.email') }}: {{ $participation->ksu_email }}</p>@endif
                @else
                    <p class="text-muted mb-0">{{ __('admin.survey_report.no_contact') }}</p>
                @endif
            </div></section>
            <section class="card"><div class="card-body">
                <h5>{{ __('admin.survey_report.technical') }}</h5>
                @if ($device !== '')<p class="mb-1 small text-break">{{ __('admin.survey_report.device') }}: {{ $device }}</p>@endif
                <p class="mb-0 small text-muted">{{ __('admin.survey_report.technical_note') }}</p>
            </div></section>
        </div>
        <div class="col-lg-8">
            @if (! empty($career))
                <section class="card mb-3"><div class="card-body">
                    <h5>{{ __('career.result.title') }}</h5>
                    <p class="mb-2">{{ __('career.result.code') }}: @foreach (str_split($career['code']) as $letter)<span class="badge bg-primary fs-6 me-1">{{ $letter }}</span>@endforeach</p>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        @foreach ($career['scores'] as $row)
                            <tr><td style="width:40%"><strong>{{ $row['code'] }}</strong> {{ $row['name'] }}@if ($row['rank'])<span class="badge bg-label-primary ms-1">#{{ $row['rank'] }}</span>@endif</td>
                                <td aria-hidden="true"><div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ $row['pct'] ?? 0 }}%"></div></div></td>
                                <td class="text-end" style="width:70px">{{ $row['pct'] === null ? '—' : $row['pct'].'%' }}</td></tr>
                        @endforeach
                    </table></div>
                </div></section>
            @endif
            <section class="card"><div class="card-body">
                <h5>{{ __('admin.survey_report.answers') }}</h5>
                @forelse ($answers as $item)
                    <div class="mb-3 pb-3 border-bottom">
                        @if ($item['group'])<div class="small text-muted">{{ $item['group'] }}</div>@endif
                        <strong>{{ $item['question'] }}</strong>
                        <span class="badge bg-label-secondary ms-1">{{ $item['type'] }}</span>
                        @if ($item['retired'])<span class="badge bg-label-warning">{{ __('admin.survey_report.retired') }}</span>@endif
                        @if (count($item['values']) > 1)
                            <ul class="mb-0 mt-1">@foreach ($item['values'] as $v)<li>{{ $v }}</li>@endforeach</ul>
                        @else
                            <p class="mb-0 mt-1" style="white-space: pre-wrap">{{ $item['answer'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">{{ __('admin.survey_report.no_answers') }}</p>
                @endforelse
            </div></section>
        </div>
    </div>
</div>
@endsection
