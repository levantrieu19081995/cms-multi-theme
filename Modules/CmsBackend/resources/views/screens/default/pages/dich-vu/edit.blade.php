{{-- Param: --}}
{{-- + resource: tên resource để generate link --}}
{{-- + dichvu: đối tượng bài viết hiện tại --}}
{{-- + user: người dùng hiện tại --}}
{{-- + postStatus: danh sách trạng thái bài viết --}}
{{-- + publishType: danh sách loại xuất bản --}}
{{-- + permissions: danh sách quyền của người dùng hiện tại --}}
{{-- + titleName: tên tiêu đề của form, nếu không có thì sẽ hiển thị là "Thêm mới bài viết" --}}



@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.them_moi_dich_vu'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/quill/typography.scss', 'assets/vendor/libs/quill/katex.scss', 'assets/vendor/libs/quill/editor.scss', 'assets/vendor/libs/select2/select2.scss', 'assets/vendor/libs/tagify/tagify.scss', 'assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'assets/vendor/libs/typeahead-js/typeahead.scss', 'assets/vendor/libs/dropzone/dropzone.scss', 'assets/vendor/libs/flatpickr/flatpickr.scss', 'assets/vendor/libs/ckeditor5/ckeditor5.css', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'assets/vendor/libs/notiflix/notiflix-3.2.7.min.css', 'assets/vendor/libs/spinkit/spinkit.scss']))
    <link rel="stylesheet" href="{{ asset('plugins/dropzone/dropzone.min.css') }}">
@endsection
@section('page-style')
    @vite(['resources/assets/css/ckeditor.css', 'resources/assets/css/bai-viet-be.css', 'Modules/CmsBackend/resources/assets/sass/app.scss'])
@endsection
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/quill/katex.js', 'assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/typeahead-js/typeahead.js', 'assets/vendor/libs/dropzone/dropzone.js', 'assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'assets/vendor/libs/flatpickr/flatpickr.js', 'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'assets/vendor/libs/notiflix/notiflix-3.2.7.min.js', 'assets/vendor/libs/sortablejs/sortable.js', 'assets/js/forms-selects.js']))
    <script src="{{ asset('plugins/dropzone/dropzone.min.js') }}"></script>
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/screens/default/editor.js', 'resources/assets/js/form-input-group.js', 'resources/assets/js/extended-ui-perfect-scrollbar.js', 'resources/assets/js/lightbox-import.js'])
    @vite(['Modules/CmsBackend/resources/assets/js/service-icon-picker.js', 'Modules/CmsBackend/resources/assets/js/custom-forms-pickers.js'])
    @yield('_page-script')
@endsection
@php
    $icon = old('icon', $dichvu->icon);
@endphp
@section('content')
    {{ Breadcrumbs::render($dichvu->id ? 'cms.dashboard.dich-vu.edit' : 'cms.dashboard.dich-vu.create', $dichvu->id ?? '') }}

    <div class="card">
        <div class="card-header bg-primary d-flex flex-column justify-content-center">
            <h4 class="text-white text-uppercase mb-0">
                {{ $dichvu->link !== null && $dichvu->link !== '' ? __('admin.ui.cap_nhat_dich_vu') : __('admin.ui.them_moi_dich_vu') }}
            </h4>
        </div>
        <div class="card-body">
            <form id="postCreate"
                action="{{ $dichvu->id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.update', $dichvu) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.store') }}"
                method="POST">
                @csrf
                @if ($dichvu->id)
                    @method('put')
                @endif
                <div class="app-ecommerce">
                    {{-- <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                    </div> --}}
                    <div class="row">
                        <div class='col-12 my-5'>
                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.dich-vu.index') }}"
                                    class="btn btn-outline-secondary waves-effect text-none">
                                    {{ __('admin.ui.tro_ve') }}
                                </a>
                                <button class="btn btn-primary waves-effect text-none"
                                    type="submit">{{ $dichvu->link !== null && $dichvu->link !== '' ? __('admin.ui.cap_nhat') : __('admin.ui.them') }}</button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">

                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" class="form-control" id="title" name="title"
                                    value="{{ old('title') ?? $dichvu->title }}" placeholder="{{ __('admin.ui.nhap_ten_dich_vu') }}"
                                    aria-describedby="defaultFormControlHelp">
                                <label for="title">{{ __('admin.ui.tieu_de') }}</label>
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>
                            <div class="form-floating form-floating-outline mb-5">
                                <input type="text" class="form-control" id="link" name="link"
                                    value="{{ old('link') ?? $dichvu->link }}"
                                    placeholder="{{ __('admin.ui.nhap_duong_dan_dich_vu') }}"
                                    aria-describedby="defaultFormControlHelp">
                                <label for="title">{{ __('admin.ui.duong_dan') }}</label>
                                <x-input-error :messages="$errors->get('link')" class="mt-2" />
                            </div>

                            <button type="button"class="btn btn-primary waves-effect text-none" data-bs-toggle="collapse"
                                data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                                {{ __('admin.ui.chon_icon') }}
                            </button>
                            <x-input-error :messages="$errors->get('icon')" class="mt-2" />
                            <div id="icon-preview-wrapper" class="mt-2">
                                <div id="icon-preview" style="display: none;">
                                    <div class="icon-item p-3 border rounded-3 d-inline-block bg-primary"
                                        style="height: 50px; width: 50px;">
                                        <i class="{{ $icon ? 'ri-' . $icon : '' }}"
                                            style="color: #fff; font-size: 25px;"></i>
                                    </div>
                                    <div class="small">{{ $icon }}</div>
                                </div>
                            </div>
                            <input type="text" value="{{ old('icon') ?? $dichvu->icon }}" id="icon" name="icon"
                                class="form-control" readonly style="display: none">


                            <div class="collapse mt-5 border rounded-3" id="collapseExample">
                                <div class="overflow-auto" style="height: 600px">
                                    <div class="card card-body">
                                        @foreach ($iconGroups as $group)
                                            <h4 class="mb-0">{{ $group['group'] ?? '' }}</h4>
                                            <div class="row g-2 mb-3">
                                                @foreach ($group['list'] as $icon)
                                                    <div class="col-2 col-sm-1 text-center">
                                                        <div class="icon-item p-3 border rounded-3 h-100">
                                                            <i class="ri-{{ $icon }}{{ $group['group'] == 'Editor' ? '' : '-line' }} fs-3 icon-select"
                                                                style="cursor:pointer;"
                                                                data-icon="{{ $icon }}{{ $group['group'] == 'Editor' ? '' : '-line' }}"></i>
                                                            <div class="small">{{ $icon }}</div>
                                                        </div>

                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card mb-6">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('admin.ui.thong_tin_hien_thi') }}</h5>
                                </div>
                                <div class="card-body">
                                    {{-- / Thông tin khác của bài viết, hiển thị gốc trên bên phải --}}
                                    <div class="row mb-2 loai-xuat-ban">
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.trang_thai_2') }}</b>
                                        </div>
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            <select class="form-select select2" id="status_select" name="status"
                                                aria-label="Default select example">
                                                <option value=1
                                                    {{ old('status', $dichvu->status ?? null) == 1 ? 'selected' : '' }}>
                                                    {{ __('admin.ui.hien_thi') }}
                                                </option>
                                                <option value=0
                                                    {{ old('status', $dichvu->status ?? null) == 0 ? 'selected' : '' }}>
                                                    {{ __('admin.ui.da_an') }}
                                                </option>


                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Thuộc tính của bài viết, hiển thị gốc dưới bên phải --}}
                            <div class="accordion mt-4 mb-6" id="accordionWithIcon">
                                @yield('_attributes')
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


@endsection

