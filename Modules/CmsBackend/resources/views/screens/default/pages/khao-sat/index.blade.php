@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('admin.survey.title'))
@section('cms-list-title', __('admin.survey.title'))
@section('cms-list-note', __('admin.survey.list_note'))
@php
    $permMap = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user());
    $can = fn (string $slug) => (bool) ($permMap[$slug] ?? false);
    $canCreate = $can('khaosat-create');
    $canUpdate = $can('khaosat-update');
    $canPublish = $can('khaosat-publish');
    $canDelete = $can('khaosat-delete');
    $canView = $can('khaosat-view') || $can('khaosat-update');
    $canResponses = $can('khaosat-response-view') || $can('khaosat-view');
    $canExport = $can('khaosat-export');
    $canStats = $can('khaosat-statistics-view');
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
@endphp
@section('cms-list-create')
<div class="cms-page-actions">
@if ($canView)<a class="btn btn-outline-secondary" href="{{ $route('cms.khao-sat.career') }}"><i class="ri-compass-3-line" aria-hidden="true"></i>{{ __('career.admin.menu_link') }}</a>@endif
@if ($canCreate)<a class="btn btn-primary" href="{{ $route('cms.khao-sat.create') }}"><i class="ri-add-line" aria-hidden="true"></i>{{ __('admin.survey.create') }}</a>@endif
</div>
@endsection
@section('content')
<style>
.cms-editorial-admin .cms-survey-table{width:100%;min-width:980px}
.cms-editorial-admin .cms-survey-table th,.cms-editorial-admin .cms-survey-table td{padding:14px 16px;vertical-align:middle}
.cms-editorial-admin .cms-survey-table td{line-height:1.45}
.cms-survey-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center;min-width:280px}
.cms-survey-actions form{display:inline-flex;margin:0}
.cms-survey-actions .btn{display:inline-flex;align-items:center;white-space:nowrap}
</style>
<div class="card">
        <form method="get" class="cms-filter-panel" role="search">
            <label>{{ __('admin.survey.search') }}<input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="{{ __('admin.survey.search_placeholder') }}"></label>
            <label>{{ __('admin.survey.field.status') }}<select name="status" class="form-select">
                <option value="">{{ __('admin.survey.all_status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
                @if ($canDelete)
                    <option value="trashed" @selected($filters['status'] === 'trashed')>{{ __('admin.survey.trashed') }}</option>
                @endif
            </select></label>
            <button type="submit" class="btn btn-outline-primary align-self-end">{{ __('admin.ui.loc') }}</button>
        </form>

        <div class="table-responsive">
            <table class="table cms-survey-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.survey.field.name') }}</th>
                        <th style="width:120px">{{ __('admin.survey.field.status') }}</th>
                        <th class="text-center" style="width:90px">{{ __('admin.survey.questions') }}</th>
                        <th class="text-center" style="width:140px">{{ __('admin.survey.responses') }}</th>
                        <th style="width:190px">{{ __('admin.survey.period') }}</th>
                        <th style="width:1%">{{ __('admin.ui.hanh_dong') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($surveys as $survey)
                    @php $status = $survey->status(); @endphp
                    <tr>
                        <td>
                            <strong>{{ $survey->ks_ten }}</strong>
                            <div class="small text-muted">{{ $survey->ks_ma }}</div>
                        </td>
                        <td><span class="badge bg-{{ $status->badge() }}">{{ $status->label() }}</span>
                            @if ($survey->trashed()) <span class="badge bg-danger">{{ __('admin.survey.trashed') }}</span> @endif
                        </td>
                        <td class="text-center">{{ $survey->so_cau_hoi }}</td>
                        <td class="text-center">
                            @if ($canResponses && ! $survey->trashed())
                                <a href="{{ $route('cms.khao-sat.responses', $survey->ks_id) }}">{{ $survey->so_hoan_thanh }}</a> / {{ $survey->so_luot }}
                            @else
                                {{ $survey->so_hoan_thanh }} / {{ $survey->so_luot }}
                            @endif
                        </td>
                        <td class="small">
                            {{ $survey->ks_bat_dau?->format('d/m/Y H:i') ?? '—' }}<br>
                            {{ $survey->ks_ket_thuc?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td>
                            <div class="cms-survey-actions">
                            @if ($survey->trashed())
                                @if ($canDelete)
                                    <form method="post" action="{{ $route('cms.khao-sat.restore', $survey->ks_id) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('admin.survey.restore') }}</button></form>
                                @endif
                            @else
                                @if ($canView)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $route('cms.khao-sat.edit', $survey->ks_id) }}" title="{{ $canUpdate ? __('admin.survey.edit') : __('admin.survey.view') }}"><i class="ri-edit-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey.edit') }}</span></a>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ $route('cms.khao-sat.preview', $survey->ks_id) }}" title="{{ __('admin.survey.preview') }}"><i class="ri-eye-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey.preview') }}</span></a>
                                @endif
                                @if ($survey->ks_ma)
                                    @include('cmsbackend::screens.pages.khao-sat._copy-link', [
                                        'url' => $route('cms.frontend.khaosat.show', $survey->ks_ma),
                                        'iconOnly' => true,
                                    ])
                                @endif
                                @if ($canPublish)
                                    @foreach ($status->next() as $next)
                                        <form method="post" action="{{ $route('cms.khao-sat.status', $survey->ks_id) }}" class="d-inline" @if ($next === \Modules\CmsBackend\Enums\SurveyStatus::Ended) onsubmit="return confirm(@js(__('admin.survey.confirm_end')))" @endif>@csrf
                                            <input type="hidden" name="status" value="{{ $next->value }}">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('admin.survey.action.'.$next->value) }}</button></form>
                                    @endforeach
                                @endif
                                @if ($canExport)
                                    <a class="btn btn-sm btn-outline-success" href="{{ $route('cms.khao-sat.export', $survey->ks_id) }}?type=summary">{{ __('admin.survey_report.export_summary') }}</a>
                                @endif
                                @if ($canStats && \Illuminate\Support\Facades\Route::has('cms.khao-sat.statistics'))
                                    <a class="btn btn-sm btn-outline-info" href="{{ $route('cms.khao-sat.statistics', $survey->ks_id) }}" title="{{ __('admin.survey.statistics') }}"><i class="ri-bar-chart-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey.statistics') }}</span></a>
                                @endif
                                @if ($canCreate)
                                    <form method="post" action="{{ $route('cms.khao-sat.duplicate', $survey->ks_id) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" title="{{ __('admin.survey.duplicate') }}"><i class="ri-file-copy-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey.duplicate') }}</span></button></form>
                                @endif
                                @if ($canDelete && $status !== \Modules\CmsBackend\Enums\SurveyStatus::Open)
                                    <form method="post" action="{{ $route('cms.khao-sat.destroy', $survey->ks_id) }}" class="d-inline" onsubmit="return confirm(@js(__('admin.survey.confirm_delete')))">@csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('admin.survey.delete') }}"><i class="ri-delete-bin-line" aria-hidden="true"></i><span class="visually-hidden">{{ __('admin.survey.delete') }}</span></button></form>
                                @endif
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('admin.survey.empty') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="cms-list-bottom">{{ $surveys->onEachSide(2)->links('cmsbackend::screens.components.shared.layouts.paginate') }}</div>
</div>
@endsection
