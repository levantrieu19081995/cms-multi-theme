@extends('cmsbackend::screens.components.shared.layouts.layoutMaster')
@php
    $exists = $survey->exists;
    $status = $survey->status();
    $user = auth()->user();
    $permMap = \Modules\CmsBackend\Support\SurveyPermissions::map(auth()->user());
    $perm = fn (string $slug) => (bool) ($permMap[$slug] ?? false);
    $canUpdate = $perm('khaosat-update');
    $canPublish = $perm('khaosat-publish');
    $canResponses = $perm('khaosat-response-view') || $perm('khaosat-view');
    $canStats = $perm('khaosat-statistics-view');
    $route = fn (string $name, mixed $params = []) => \Modules\CmsBackend\Support\ModuleUrl::route($name, $params);
    $infoEditable = $exists ? ($caps['edit_info'] && $canUpdate) : true;
    $structureEditable = $exists && $caps['edit_structure'] && $canUpdate;
    $collectionLocked = $exists && ($caps['collection_locked'] ?? false);
    $tab = session('tab', $errors->has('structure') ? 'builder' : 'info');
    $draft = session('structure_draft');
    $initial = is_array($draft) ? $draft : $structure;
    $dt = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d\TH:i') : '';
    $isCareer = $survey->ks_loai === \Modules\CmsBackend\Services\SurveyService::CAREER;
    $careerGroups = $isCareer ? app(\Modules\CmsBackend\Services\CareerService::class)->groups() : [];
    $builderConfig = [
        'initial' => $initial,
        'readonly' => ! $structureEditable,
        'types' => \Modules\CmsBackend\Http\Controllers\KhaoSatController::questionTypes($isCareer),
        'i18n' => __('admin.survey.builder'),
        'career' => $isCareer,
        'riasec' => array_values(array_map(fn ($g) => ['value' => $g['code'], 'label' => $g['code'].' — '.$g['name']], $careerGroups)),
    ];
@endphp
@section('title', $exists ? $survey->ks_ten : __('admin.survey.create'))
@section('content')
<div class="cms-native-record">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ $route('cms.khao-sat') }}" class="text-muted"><i class="ri-arrow-left-line" aria-hidden="true"></i> {{ __('admin.survey.title') }}</a>
            <h3 class="mb-0 mt-1">{{ $exists ? $survey->ks_ten : __('admin.survey.create') }}</h3>
        </div>
        @if ($exists)
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-{{ $status->badge() }} fs-6">{{ $status->label() }}</span>
                @if ($canPublish)
                    @foreach ($caps['transitions'] as $next)
                        <form method="post" action="{{ $route('cms.khao-sat.status', $survey->ks_id) }}" class="d-inline"
                              @if ($next === \Modules\CmsBackend\Enums\SurveyStatus::Ended) onsubmit="return confirm(@js(__('admin.survey.confirm_end')))" @endif>
                            @csrf <input type="hidden" name="status" value="{{ $next->value }}">
                            <button class="btn btn-sm {{ $next === \Modules\CmsBackend\Enums\SurveyStatus::Open ? 'btn-success' : 'btn-outline-secondary' }}" type="submit">{{ __('admin.survey.action.'.$next->value) }}</button>
                        </form>
                    @endforeach
                @endif
                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ $route('cms.khao-sat.preview', $survey->ks_id) }}"><i class="ri-eye-line me-1" aria-hidden="true"></i>{{ __('admin.survey.preview') }}</a>
                @if ($survey->ks_ma)
                    @include('cmsbackend::screens.pages.khao-sat._copy-link', [
                        'url' => $route('cms.frontend.khaosat.show', $survey->ks_ma),
                    ])
                @endif
                @if ($canResponses && \Illuminate\Support\Facades\Route::has('cms.khao-sat.responses'))
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $route('cms.khao-sat.responses', $survey->ks_id) }}">{{ __('admin.survey.responses') }}</a>
                @endif
                @if ($canStats && \Illuminate\Support\Facades\Route::has('cms.khao-sat.statistics'))
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $route('cms.khao-sat.statistics', $survey->ks_id) }}">{{ __('admin.survey.statistics') }}</a>
                @endif
            </div>
        @endif
    </div>

    @if ($exists && ! $caps['edit_structure'])
        <div class="alert alert-{{ $status === \Modules\CmsBackend\Enums\SurveyStatus::Ended ? 'secondary' : 'warning' }}" role="status">
            {{ $status === \Modules\CmsBackend\Enums\SurveyStatus::Ended ? __('admin.survey.notice_ended') : __('admin.survey.notice_locked') }}
            @if ($perm('khaosat-create'))
                <form method="post" action="{{ $route('cms.khao-sat.duplicate', $survey->ks_id) }}" class="d-inline">@csrf
                    <button class="btn btn-sm btn-link p-0 align-baseline" type="submit">{{ __('admin.survey.duplicate_to_edit') }}</button></form>
            @endif
        </div>
    @endif

    @if ($collectionLocked)
        <div class="alert alert-info" role="status">{{ __('admin.survey.notice_collection_locked') }}</div>
    @endif

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link {{ $tab === 'info' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#sv-tab-info" type="button" role="tab">{{ __('admin.survey.tab_info') }}</button></li>
        <li class="nav-item"><button class="nav-link {{ $tab === 'builder' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#sv-tab-builder" type="button" role="tab" @disabled(! $exists)>{{ __('admin.survey.tab_builder') }} <small id="survey-builder-count" class="text-muted"></small></button></li>
    </ul>

    <div class="tab-content p-0">
        <div class="tab-pane fade {{ $tab === 'info' ? 'show active' : '' }}" id="sv-tab-info" role="tabpanel">
            <form method="post" action="{{ $exists ? $route('cms.khao-sat.update', $survey->ks_id) : $route('cms.khao-sat.store') }}" class="card card-body">
                @csrf
                <fieldset @disabled(! $infoEditable)>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold" for="ks_ten">{{ __('admin.survey.field.name') }} <span class="text-danger">*</span></label>
                            <input class="form-control @error('ks_ten') is-invalid @enderror" id="ks_ten" name="ks_ten" maxlength="500" required value="{{ old('ks_ten', $survey->ks_ten) }}">
                            @error('ks_ten')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="ks_ma">{{ __('admin.survey.field.slug') }}</label>
                            <input class="form-control @error('ks_ma') is-invalid @enderror" id="ks_ma" name="ks_ma" maxlength="100" value="{{ old('ks_ma', $survey->ks_ma) }}" @disabled($exists && ! $caps['edit_slug']) placeholder="{{ __('admin.survey.slug_hint') }}">
                            @error('ks_ma')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold" for="ks_mo_ta_ngan">{{ __('admin.survey.field.summary') }}</label>
                            <input class="form-control" id="ks_mo_ta_ngan" name="ks_mo_ta_ngan" maxlength="500" value="{{ old('ks_mo_ta_ngan', $survey->ks_mo_ta_ngan) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold" for="ks_gioi_thieu">{{ __('admin.survey.field.intro') }}</label>
                            <textarea class="form-control" id="ks_gioi_thieu" name="ks_gioi_thieu" rows="6">{{ old('ks_gioi_thieu', $survey->ks_gioi_thieu) }}</textarea>
                            <div class="form-text">{{ __('admin.survey.intro_hint') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="ks_loai">{{ __('admin.survey.field.type') }}</label>
                            @if ($collectionLocked)<input type="hidden" name="ks_loai" value="{{ $survey->ks_loai ?: 'general' }}">@endif
                            <select class="form-select" id="ks_loai" @unless ($collectionLocked) name="ks_loai" @endunless @disabled($collectionLocked)>
                                @foreach (['general', 'satisfaction', 'feedback', 'research', 'career'] as $kind)
                                    <option value="{{ $kind }}" @selected(old('ks_loai', $survey->ks_loai) === $kind)>{{ __('admin.survey.kind.'.$kind) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="ks_bat_dau">{{ __('admin.survey.field.start') }}</label>
                            @if ($collectionLocked)<input type="hidden" name="ks_bat_dau" value="{{ $dt($survey->ks_bat_dau) }}">@endif
                            <input type="datetime-local" class="form-control" id="ks_bat_dau" @unless ($collectionLocked) name="ks_bat_dau" @endunless value="{{ $collectionLocked ? $dt($survey->ks_bat_dau) : old('ks_bat_dau', $dt($survey->ks_bat_dau)) }}" @disabled($collectionLocked)>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="ks_ket_thuc">{{ __('admin.survey.field.end') }}</label>
                            <input type="datetime-local" class="form-control @error('ks_ket_thuc') is-invalid @enderror" id="ks_ket_thuc" name="ks_ket_thuc" value="{{ old('ks_ket_thuc', $dt($survey->ks_ket_thuc)) }}">
                            @error('ks_ket_thuc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="ks_so_lan_gui">{{ __('admin.survey.field.submit_limit') }}</label>
                            @if ($collectionLocked)<input type="hidden" name="ks_so_lan_gui" value="{{ (int) $survey->ks_so_lan_gui }}">@endif
                            <input type="number" min="0" max="100" class="form-control" id="ks_so_lan_gui" @unless ($collectionLocked) name="ks_so_lan_gui" @endunless value="{{ $collectionLocked ? (int) $survey->ks_so_lan_gui : old('ks_so_lan_gui', $survey->ks_so_lan_gui) }}" @disabled($collectionLocked)>
                            <div class="form-text">{{ __('admin.survey.submit_limit_hint') }}</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="ks_cho_phep_an_danh" value="{{ $collectionLocked ? (int) (bool) $survey->ks_cho_phep_an_danh : 0 }}">
                                <input class="form-check-input" type="checkbox" id="ks_cho_phep_an_danh" @unless ($collectionLocked) name="ks_cho_phep_an_danh" @endunless value="1" @checked($collectionLocked ? $survey->ks_cho_phep_an_danh : old('ks_cho_phep_an_danh', $survey->ks_cho_phep_an_danh)) @disabled($collectionLocked)>
                                <label class="form-check-label" for="ks_cho_phep_an_danh">{{ __('admin.survey.field.anonymous') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="ks_yeu_cau_thong_tin" value="{{ $collectionLocked ? (int) (bool) $survey->ks_yeu_cau_thong_tin : 0 }}">
                                <input class="form-check-input" type="checkbox" id="ks_yeu_cau_thong_tin" @unless ($collectionLocked) name="ks_yeu_cau_thong_tin" @endunless value="1" @checked($collectionLocked ? $survey->ks_yeu_cau_thong_tin : old('ks_yeu_cau_thong_tin', $survey->ks_yeu_cau_thong_tin)) @disabled($collectionLocked)>
                                <label class="form-check-label" for="ks_yeu_cau_thong_tin">{{ __('admin.survey.field.require_identity') }}</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold" for="ks_noi_dung_hoan_thanh">{{ __('admin.survey.field.thanks') }}</label>
                            <textarea class="form-control" id="ks_noi_dung_hoan_thanh" name="ks_noi_dung_hoan_thanh" rows="3" maxlength="5000">{{ old('ks_noi_dung_hoan_thanh', $survey->ks_noi_dung_hoan_thanh) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold" for="ks_ghichu">{{ __('admin.survey.field.note') }}</label>
                            <textarea class="form-control" id="ks_ghichu" name="ks_ghichu" rows="2" maxlength="5000">{{ old('ks_ghichu', $survey->ks_ghichu) }}</textarea>
                            <div class="form-text">{{ __('admin.survey.note_hint') }}</div>
                        </div>
                    </div>
                </fieldset>
                @if ($infoEditable)
                    <div class="mt-4"><button class="btn btn-primary" type="submit"><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('admin.survey.save') }}</button></div>
                @endif
            </form>
        </div>

        <div class="tab-pane fade {{ $tab === 'builder' ? 'show active' : '' }}" id="sv-tab-builder" role="tabpanel">
            @if ($exists)
                @error('structure')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                <form method="post" id="survey-structure-form" action="{{ $route('cms.khao-sat.structure', $survey->ks_id) }}">
                    @csrf
                    <input type="hidden" name="structure" id="survey-structure-input" value="">
                    <div id="survey-builder" aria-live="polite"></div>
                    @if ($structureEditable)
                        <div class="sticky-bottom bg-body py-3 mt-3 border-top"><button class="btn btn-primary" type="submit"><i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('admin.survey.save_structure') }}</button></div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</div>
<style>
    .sv-handle { cursor: grab; } .sv-handle:active { cursor: grabbing; }
    .sv-disabled { opacity: .65; }
    .sv-question.sortable-ghost, .sv-group.sortable-ghost, .sv-option.sortable-ghost { opacity: .4; }
</style>
@endsection
@section('page-script')
    @if ($exists)
        <script type="application/json" id="survey-builder-config">@json($builderConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)</script>
        <script src="{{ asset('assets/plugin/sortable/sortable.min.js') }}"></script>
        <script src="{{ asset('assets/js/survey/builder.js') }}?v={{ @filemtime(public_path('assets/js/survey/builder.js')) }}"></script>
    @endif
@endsection
