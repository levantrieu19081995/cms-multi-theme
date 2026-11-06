@php
    $configData = Helper::appClasses();

@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.menu.cai_dat_trang_chu'))
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('page-style')
    <!-- Page -->
    {{-- @vite(Theme::asset(['resources/assets/vendor/scss/?.css'])) --}}
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js', 'assets/vendor/libs/sortablejs/sortable.js']))
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js'])
@endsection
@section('content')
    {{ Breadcrumbs::render('cms.setting.caodangnghe', '') }}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <h5 class="card-header bg-primary text-white text-uppercase">{{ __('admin.menu.cai_dat_trang_chu') }}</h5>
                <div class="card-body mt-5">
                    <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.setting.trang-chu.update') }}" method="post">
                        @csrf
                        @method('put')
                        {{-- Fields come from the settings schema of the blocks the Developer enabled for the current theme. --}}
                        @include('cmsbackend::screens.pages.setting.partials.homepage-blocks')
                        <div class="row mb-4">
                            <div class="mt-5 d-flex justify-content-end gap-2">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard') }}"
                                    class="btn btn-outline-secondary waves-effect text-none">{{ __('admin.ui.tro_ve') }}</a>
                                <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('admin.ui.luu') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
