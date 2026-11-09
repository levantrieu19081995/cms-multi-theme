@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@section('title', __('career.admin.title'))
@php
    $permMap = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user());
    $canWrite = (bool) ($permMap['khaosat-update'] ?? false);
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
    $editing = $editing ?? null;
    $formAction = $editing ? $route('cms.khao-sat.career.major.update', $editing->cm_id) : $route('cms.khao-sat.career.major.store');
    $val = fn (string $field, string $column, mixed $default = '') => old($field, $editing?->{$column} ?? $default);
@endphp
@section('content')
<div class="cms-native-record">
    <div class="mb-3">
        <a href="{{ $route('cms.khao-sat') }}" class="text-muted"><i class="ri-arrow-left-line" aria-hidden="true"></i> {{ __('career.admin.back') }}</a>
        <h3 class="mb-1 mt-1">{{ __('career.admin.title') }}</h3>
        <p class="text-muted mb-0">{{ __('career.admin.intro') }}</p>
    </div>

    <section class="card mb-4">
        <div class="card-body">
            <h5>{{ __('career.admin.groups_title') }}</h5>
            <p class="text-muted small">{{ __('career.admin.groups_hint') }}</p>
            @error('groups')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
            <form method="post" action="{{ $route('cms.khao-sat.career.groups') }}">
                @csrf
                @foreach ($groups as $letter => $group)
                    <div class="row g-2 align-items-start mb-3">
                        <div class="col-auto"><span class="badge bg-primary fs-5" style="min-width:2.4rem" aria-hidden="true">{{ $letter }}</span></div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1" for="g-{{ $letter }}-name">{{ __('career.admin.group_name') }}</label>
                            <input class="form-control" id="g-{{ $letter }}-name" name="groups[{{ $letter }}][name]" maxlength="{{ \Modules\CmsBackend\Services\CareerService::MAX_NAME }}"
                                   value="{{ old('groups.'.$letter.'.name', $group['custom_name']) }}" placeholder="{{ __('career.groups.'.$letter.'.name') }}" @disabled(! $canWrite)>
                            @error('groups.'.$letter.'.name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col">
                            <label class="form-label small mb-1" for="g-{{ $letter }}-desc">{{ __('career.admin.group_description') }}</label>
                            <textarea class="form-control" id="g-{{ $letter }}-desc" rows="2" name="groups[{{ $letter }}][description]" maxlength="{{ \Modules\CmsBackend\Services\CareerService::MAX_DESCRIPTION }}"
                                      placeholder="{{ __('career.groups.'.$letter.'.description') }}" @disabled(! $canWrite)>{{ old('groups.'.$letter.'.description', $group['custom_description']) }}</textarea>
                            @error('groups.'.$letter.'.description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                @endforeach
                @if ($canWrite)<button class="btn btn-primary" type="submit"><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('career.admin.groups_save') }}</button>@endif
            </form>
        </div>
    </section>

    <section class="card mb-4">
        <div class="card-body">
            <h5>{{ __('career.admin.majors_title') }}</h5>
            <p class="text-muted small">{{ __('career.admin.majors_hint') }}</p>
            @if ($majors->isEmpty())
                <p class="text-muted">{{ __('career.admin.majors_empty') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr>
                            <th style="width:70px">{{ __('career.admin.major_order') }}</th>
                            <th>{{ __('career.admin.major_name') }}</th>
                            <th style="width:110px">{{ __('career.admin.major_code') }}</th>
                            <th style="width:100px"></th>
                            @if ($canWrite)<th style="width:170px">{{ __('career.admin.actions') }}</th>@endif
                        </tr></thead>
                        <tbody>
                        @foreach ($majors as $major)
                            <tr>
                                <td>{{ $major->cm_thu_tu }}</td>
                                <td>{{ $major->cm_ten }}@if ($major->cm_mo_ta)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($major->cm_mo_ta, 140) }}</div>@endif</td>
                                <td><span class="badge bg-label-primary">{{ $major->cm_ma_riasec }}</span></td>
                                <td>@unless ($major->cm_trang_thai)<span class="badge bg-label-secondary">{{ __('career.admin.major_inactive') }}</span>@endunless</td>
                                @if ($canWrite)
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ $route('cms.khao-sat.career') }}?sua={{ $major->cm_id }}#major-form">{{ __('career.admin.edit') }}</a>
                                        <form method="post" action="{{ $route('cms.khao-sat.career.major.destroy', $major->cm_id) }}" class="d-inline" onsubmit="return confirm(@js(__('career.admin.major_confirm_delete')))">
                                            @csrf <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('career.admin.delete') }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($canWrite)
                <hr>
                <h6 id="major-form">{{ $editing ? __('career.admin.major_edit') : __('career.admin.major_add') }}</h6>
                <form method="post" action="{{ $formAction }}" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label" for="m-name">{{ __('career.admin.major_name') }} <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="m-name" name="name" maxlength="255" required value="{{ $val('name', 'cm_ten') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="m-code">{{ __('career.admin.major_code') }} <span class="text-danger">*</span></label>
                        <input class="form-control text-uppercase @error('code') is-invalid @enderror" id="m-code" name="code" maxlength="10" required value="{{ $val('code', 'cm_ma_riasec') }}" placeholder="IRA">
                        <div class="form-text">{{ __('career.admin.major_code_hint') }}</div>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="m-order">{{ __('career.admin.major_order') }}</label>
                        <input class="form-control @error('order') is-invalid @enderror" type="number" min="0" max="100000" id="m-order" name="order" value="{{ $val('order', 'cm_thu_tu', 0) }}">
                        @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="m-active" name="active" value="1" @checked(old('_token') ? old('active') : ($editing ? $editing->cm_trang_thai : true))>
                            <label class="form-check-label" for="m-active">{{ __('career.admin.major_active') }}</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="m-desc">{{ __('career.admin.major_description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="m-desc" name="description" rows="2" maxlength="2000">{{ $val('description', 'cm_mo_ta') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="m-url">{{ __('career.admin.major_url') }}</label>
                        <input class="form-control @error('url') is-invalid @enderror" id="m-url" name="url" type="url" maxlength="500" value="{{ $val('url', 'cm_url') }}" placeholder="https://">
                        @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit"><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('career.admin.major_save') }}</button>
                        @if ($editing)<a class="btn btn-outline-secondary" href="{{ $route('cms.khao-sat.career') }}">{{ __('career.admin.cancel') }}</a>@endif
                    </div>
                </form>
            @endif
        </div>
    </section>
</div>
@endsection
