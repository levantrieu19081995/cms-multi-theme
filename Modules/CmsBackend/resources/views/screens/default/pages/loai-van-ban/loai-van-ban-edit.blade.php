@php
    $configData = Helper::appClasses();
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $item->lvb_id ? __('admin.ui.cap_nhat_loai_van_ban') : __('admin.ui.them_moi_loai_van_ban'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js', 'resources/assets/js/table-confirm-delete.js'])
@endsection

@section('content')
    {{ $item->lvb_id ? Breadcrumbs::render('cms.dashboard.cdn.loai-van-ban.edit', '') : Breadcrumbs::render('cms.dashboard.cdn.loai-van-ban.create', '') }}
    <div class="row">
        <!-- Form controls -->
        <div class="col-md-6">
            <form
                action="{{ $item->lvb_id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.loai-van-ban.update', $item) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.loai-van-ban.store') }}"
                method="post">
                @csrf
                @if ($item->lvb_id)
                    @method('put')
                @endif
                <div class="card">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $item->lvb_id ? __('admin.ui.cap_nhat') : __('admin.ui.add_new') }} {{ __('admin.resource.document_type') }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="form-floating form-floating-outline mt-6 mb-6">
                            <input type="text" class="form-control" id="lvb_ma" name="lvb_ma"
                                placeholder="{{ __('admin.ui.ma_loai_van_ban') }}" autocomplete="off"
                                value="{{ old('lvb_ma') ?? $item->lvb_ma }}">
                            <label for="lvb_ma">{{ __('admin.ui.ma_loai_van_ban') }}</label>
                            <x-input-error :messages="$errors->get('lvb_ma')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control" id="lvb_ten" name="lvb_ten"
                                placeholder="{{ __('admin.ui.ten_loai_van_ban') }}" autocomplete="off"
                                value="{{ old('lvb_ten') ?? $item->lvb_ten }}">
                            <label for="lvb_ten">{{ __('admin.ui.ten_loai_van_ban') }}</label>
                            <x-input-error :messages="$errors->get('lvb_ten')" class="mt-2" />
                        </div>

                        <hr class="m-0">
                        <div class="mt-6 d-flex justify-content-end gap-2">
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.loai-van-ban.index') }}" type="button"
                                class="btn btn-outline-secondary waves-effect text-none">{{ __('admin.ui.huy_bo') }}</a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit"
                                value="cc">{{ __('admin.ui.luu') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
