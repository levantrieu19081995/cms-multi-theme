@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $doitac->id !== null ? __('admin.ui.cap_nhat_doi_tac') : __('admin.ui.them_moi_doi_tac'))

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

    {{-- The module's post.js binds the existing picker after DOM ready. --}}
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/screens/default/editor.js', 'resources/assets/js/form-input-group.js', 'resources/assets/js/extended-ui-perfect-scrollbar.js', 'resources/assets/js/lightbox-import.js'])
    @vite(['Modules/CmsBackend/resources/assets/js/post.js', 'Modules/CmsBackend/resources/assets/js/custom-forms-pickers.js'])
    @yield('_page-script')
@endsection
@php
    $slugValue = old('slug') ?? ($doitac->slug ?? '');
@endphp
{{-- @dump($doitac) --}}
@section('content')
    {{ Breadcrumbs::render($doitac->id ? 'cms.dashboard.doi-tac.edit' : 'cms.dashboard.doi-tac.create', $doitac->id ?? '') }}
    <div class="card">
        <div class="card-header bg-primary d-flex flex-column justify-content-center">
            <h4 class="text-white text-uppercase mb-0">
                {{ $doitac->id !== null ? __('admin.ui.cap_nhat_doi_tac') : __('admin.ui.them_moi_doi_tac') }}
            </h4>
        </div>
        <div class="card-body">
            <form id="postCreate"
                action="{{ $doitac->id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.update', $doitac) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.store') }}"
                method="POST" onsubmit="updateViewImage()">
                @csrf
                @if ($doitac->id)
                    @method('put')
                @endif
                <div class="app-ecommerce">

                    <div class="row">
                        <div class='col-12 my-5'>
                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.index') }}"
                                    class="btn btn-outline-secondary waves-effect text-none">
                                    {{ __('admin.ui.tro_ve') }}
                                </a>
                                <button class="btn btn-primary waves-effect text-none"
                                    type="submit">{{ $doitac->id !== null ? __('admin.ui.cap_nhat') : __('admin.ui.them') }}</button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-floating form-floating-outline mb-5">
                                        <input type="text" class="form-control" id="title" name="title"
                                            value="{{ old('title') ?? $doitac->title }}"
                                            placeholder="{{ __('admin.ui.nhap_ten_doi_tac') }}"
                                            aria-describedby="defaultFormControlHelp" oninput="generateSlug()">
                                        <label for="title">{{ __('admin.ui.ten_doi_tac') }}</label>
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                    </div>
                                    <div class="input-group input-group-merge mb-5">
                                        <div class="form-floating form-floating-outline">
                                            <input data-default="{{ $slugValue }}" type="text" id="slug"
                                                name="slug" value="{{ $slugValue }}"
                                                class="form-control text-primary"
                                                placeholder="https://{{ request()->getHost() }}">
                                            <label for="basic-url14">{{ __('admin.ui.duong_dan') }}</label>
                                        </div>
                                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                                    </div>
                                    <div class="form-floating form-floating-outline mb-5">
                                        <textarea class="form-control h-px-100" id="excerpt" placeholder="{{ __('admin.ui.gioi_thieu_ngan_gon') }}" name="excerpt">{{ old('excerpt') ?? $doitac->excerpt }}</textarea>
                                        <label for="excerpt">{{ __('admin.ui.gioi_thieu_ngan_gon') }}</label>
                                    </div>
                                    <x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
                                    <h5 class="mb-0 card-title">{{ __('admin.ui.thong_tin_doi_tac') }}</h5>
                                    <textarea name="content" id="ckeditor5">{{ old('content') ?? $doitac->content }}</textarea>
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
                                            <select class="form-select select2" id="status_select" name="show_home_page"
                                                aria-label="Default select example">
                                                <option value=1
                                                    {{ old('show_home_page', $doitac->show_home_page ?? null) == 1 ? 'selected' : '' }}>
                                                    {{ __('admin.ui.hien_thi_ra_trang_chu') }}
                                                </option>
                                                <option value=0
                                                    {{ old('show_home_page', $doitac->show_home_page ?? null) == 0 ? 'selected' : '' }}>
                                                    {{ __('admin.ui.da_an') }}
                                                </option>


                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Thuộc tính của bài viết, hiển thị gốc dưới bên phải --}}
                            <div class="accordion mt-4 mb-6" id="accordionWithIcon">
                                {{-- <div class="accordion-item active">
                                    <h2 class="accordion-header d-flex align-items-center">
                                        <button type="button" class="accordion-button accordion-header-font-size"
                                            data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2"
                                            aria-expanded="true">
                                            {{ __('admin.ui.anh_dai_dien') }}
                                        </button>
                                    </h2>
                                    <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <input id="thumbnail" name="avatar" class="form-control" type="hidden"
                                                    value="{{ old('avatar') ?? $doitac->getFirstMediaUrl('avatars') }}">

                                                <div id="holder" style="margin-top:15px;max-height:400px"
                                                    class="img-avt">
                                                    <img src="{{ old('avatar') ?? $doitac->getFirstMediaUrl('avatars') }}">
                                                </div>
                                                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                                            </div>
                                            <div class="row">
                                                <button type="button" id="lfm" data-input="thumbnail"
                                                    data-preview="holder" class="btn btn-primary">
                                                    <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="accordion-item active">
                                    <h2 class="accordion-header d-flex align-items-center">
                                        <button type="button" class="accordion-button accordion-header-font-size"
                                            data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-Avatar"
                                            aria-expanded="true">
                                            <i class="ri-image-fill ri-20px me-2"></i>
                                            {{ __('admin.ui.anh_dai_dien_doi_tac') }}
                                        </button>
                                    </h2>
                                    <div id="accordionWithIcon-Avatar" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <div class="row">
                                                {{-- Hidden field để lưu URL avatar --}}
                                                <input id="thumbnail" name="avatar" class="form-control" type="hidden"
                                                    value="{{ old('avatar') ?? $doitac->getFirstMediaUrl('avatars') }}">

                                                {{-- Vùng hiển thị ảnh --}}
                                                <div id="holder" style="margin-top:15px;max-height:300px"
                                                    class="img-avt">
                                                    @if (old('avatar') ?? $doitac->getFirstMediaUrl('avatars'))
                                                        <img src="{{ old('avatar') ?? $doitac->getFirstMediaUrl('avatars') }}"
                                                            style="max-height: 200px;">
                                                    @endif
                                                </div>
                                                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                                            </div>

                                            {{-- Nút chọn ảnh --}}
                                            <div class="row mt-2">
                                                <button type="button" id="lfm" data-input="thumbnail"
                                                    data-preview="holder"
                                                    style="border: thin dashed #cfd0d6;height: 40px; width: 100%;">
                                                    <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


@endsection

