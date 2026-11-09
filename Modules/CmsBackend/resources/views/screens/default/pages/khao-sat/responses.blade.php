@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('admin.survey_report.responses_title'))
@php
    $user = auth()->user();
    $permMap = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user());
    $perm = fn (string $slug) => (bool) ($permMap[$slug] ?? false);
    $canDelete = $perm('khaosat-response-delete');
    $canExport = $perm('khaosat-export');
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
    $base = $route('cms.khao-sat.responses', $survey->ks_id);
    $exportQuery = array_filter($filters, fn ($v) => $v !== null && $v !== '');
@endphp
@section('content')
<div class="cms-native-record">
    @include('cmsbackend::screens.pages.khao-sat._nav', ['active' => 'responses'])

    <div class="card">
        <div class="card-body">
            @include('cmsbackend::screens.pages.khao-sat._filters', ['action' => $base, 'full' => true])

            @if ($canExport)
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="fw-bold">{{ __('admin.survey_report.export_button') }}:</span>
                    <a class="btn btn-sm btn-outline-success" href="{{ $route('cms.khao-sat.export', $survey->ks_id) }}?{{ http_build_query($exportQuery + ['type' => 'summary']) }}">{{ __('admin.survey_report.export_summary') }}</a>
                    <a class="btn btn-sm btn-outline-success" href="{{ $route('cms.khao-sat.export', $survey->ks_id) }}?{{ http_build_query($exportQuery + ['type' => 'detail']) }}">{{ __('admin.survey_report.export_detail') }}</a>
                    <a class="btn btn-sm btn-outline-warning" href="{{ $route('cms.khao-sat.export', $survey->ks_id) }}?{{ http_build_query($exportQuery + ['type' => 'detail', 'contact' => 1]) }}">{{ __('admin.survey_report.export_detail') }} + {{ __('admin.survey_report.export_contact') }}</a>
                    <span class="text-muted small">{{ __('admin.survey_report.export_hint') }}</span>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('admin.survey_report.code') }}</th>
                            <th>{{ __('admin.survey_report.participant') }}</th>
                            <th>{{ __('admin.survey_report.started_at') }}</th>
                            <th>{{ __('admin.survey_report.completed_at') }}</th>
                            <th>{{ __('admin.survey_report.duration') }}</th>
                            <th>{{ __('admin.survey_report.status') }}</th>
                            @if ($survey->ks_loai === \Modules\CmsBackend\Services\SurveyService::CAREER)<th>{{ __('career.stats.result_column') }}</th>@endif
                            <th style="width:1%">{{ __('admin.ui.hanh_dong') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($participations as $p)
                        <tr>
                            <td><code>{{ \Illuminate\Support\Str::limit($p->ksu_ma, 8, '') }}</code></td>
                            <td>{{ \Modules\CmsBackend\Services\SurveyReportService::displayName($p) }}</td>
                            <td>{{ $p->ksu_created_at?->format('H:i d/m/Y') }}</td>
                            <td>{{ $p->ksu_hoan_thanh_at?->format('H:i d/m/Y') ?? '—' }}</td>
                            <td>{{ \Modules\CmsBackend\Services\SurveyReportService::duration($p->ksu_thoi_luong) }}</td>
                            <td><span class="badge bg-{{ $p->isCompleted() ? 'success' : 'secondary' }}">{{ $p->isCompleted() ? __('admin.survey_report.completed') : __('admin.survey_report.in_progress') }}</span></td>
                            @if ($survey->ks_loai === \Modules\CmsBackend\Services\SurveyService::CAREER)<td>@if ($p->ksu_ket_qua)<strong>{{ $p->ksu_ket_qua }}</strong>@else—@endif</td>@endif
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ $route('cms.khao-sat.show', $p->ksu_id) }}" title="{{ __('admin.survey_report.view') }}"><i class="ri-eye-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey_report.view') }}</span></a>
                                @if ($canDelete)
                                    <form method="post" action="{{ $route('cms.khao-sat.responses.destroy', $p->ksu_id) }}" class="d-inline" onsubmit="return confirm(@js(__('admin.survey_report.confirm_delete')))">@csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('admin.survey_report.delete') }}"><i class="ri-delete-bin-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey_report.delete') }}</span></button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('admin.survey_report.empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $participations->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}</div>
        </div>
    </div>
</div>
@endsection
