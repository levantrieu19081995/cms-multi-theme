@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.quan_ly_cac_con_so_noi_bat'))
@section('cms-list-create')
<div class="cms-page-actions">
    <button type="submit" form="cms-statistics-form" class="btn btn-primary">
        <i class="ri-save-line me-1" aria-hidden="true"></i>{{ __('admin.ui.luu_cac_con_so_noi_bat') }}
    </button>
</div>
@endsection

@section('content')
    {{ Breadcrumbs::render('cms.dashboard.con-so-noi-bat.index') }}

    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.ui.quan_ly_cac_con_so_noi_bat') }}</h5>
        <div class="card-body pt-4">
            <p class="text-body-secondary mb-4 px-4">
                {{ __('admin.ui.statistics_intro', ['max' => \Modules\CmsBackend\Support\HomeSettings::STATISTICS_MAX]) }}
            </p>

            <form id="cms-statistics-form" action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.con-so-noi-bat.update') }}" method="post">
                @csrf
                @method('put')

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 90px">{{ __('admin.ui.vi_tri') }}</th>
                                <th>{{ __('admin.ui.nhan_chi_so_tieng_viet') }}</th>
                                @foreach (app(\App\Services\LocaleManager::class)->alternates() as $statLocale)
                                    <th>Label ({{ app(\App\Services\LocaleManager::class)->supported()[$statLocale] }})</th>
                                @endforeach
                                <th style="width: 300px">{{ __('admin.ui.con_so_hien_thi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statistics as $index => $stat)
                                <tr>
                                    <td class="text-center fw-semibold">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <label class="visually-hidden" for="statistics-label-{{ $index }}">{{ __('admin.ui.metric_label_n', ['n' => $index + 1]) }}</label>
                                        <input
                                            type="text"
                                            name="statistics[{{ $index }}][label]"
                                            class="form-control"
                                            id="statistics-label-{{ $index }}"
                                            maxlength="80"
                                            placeholder="{{ __('admin.ui.vi_du_nguoi_hoc') }}"
                                            autocomplete="off"
                                            value="{{ old('statistics.'.$index.'.label', $stat['label']) }}">
                                        <x-input-error :messages="$errors->get('statistics.'.$index.'.label')" class="mt-2" />
                                    </td>
                                    @foreach (app(\App\Services\LocaleManager::class)->alternates() as $statLocale)
                                        <td>
                                            <label class="visually-hidden" for="statistics-label-{{ $statLocale }}-{{ $index }}">Label {{ $index + 1 }} ({{ $statLocale }})</label>
                                            <input type="text" name="statistics[{{ $index }}][label_{{ $statLocale }}]" class="form-control" id="statistics-label-{{ $statLocale }}-{{ $index }}" maxlength="80" placeholder="e.g. Students" autocomplete="off" lang="{{ config('locales.html_lang.'.$statLocale, $statLocale) }}" value="{{ old('statistics.'.$index.'.label_'.$statLocale, $stat['label_'.$statLocale] ?? '') }}">
                                            <x-input-error :messages="$errors->get('statistics.'.$index.'.label_'.$statLocale)" class="mt-2" />
                                        </td>
                                    @endforeach
                                    <td>
                                        <label class="visually-hidden" for="statistics-value-{{ $index }}">{{ __('admin.ui.metric_value_n', ['n' => $index + 1]) }}</label>
                                        <input
                                            type="text"
                                            name="statistics[{{ $index }}][value]"
                                            class="form-control"
                                            id="statistics-value-{{ $index }}"
                                            maxlength="30"
                                            placeholder="{{ __('admin.ui.vi_du_19_hoac_12_000') }}"
                                            autocomplete="off"
                                            value="{{ old('statistics.'.$index.'.value', $stat['value']) }}">
                                        <x-input-error :messages="$errors->get('statistics.'.$index.'.value')" class="mt-2" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
@endsection
