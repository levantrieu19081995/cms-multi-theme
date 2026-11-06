@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $guongtieubieu->id !== null ? __('admin.ui.cap_nhat_guong_tieu_bieu') : __('admin.ui.them_moi_guong_tieu_bieu'))

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
    @vite(['Modules/CmsBackend/resources/assets/js/post.js', 'Modules/CmsBackend/resources/assets/js/custom-forms-pickers.js'])
    @yield('_page-script')

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const titleInput = document.getElementById('title');
        const submitBtn = document.querySelector('#postCreate button[type="submit"]');
        const errorDiv = document.getElementById('title-error');

        if (titleInput && submitBtn && errorDiv) {
            titleInput.addEventListener('input', function () {
                const maxLength = 255;
                const currentLength = titleInput.value.length;

                if (currentLength > maxLength) {
                    errorDiv.innerText = @json(__('admin.js.max_length_exceeded')).replace(':max', maxLength).replace(':current', currentLength);
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    errorDiv.innerText = '';
                    errorDiv.style.display = 'none';
                    submitBtn.disabled = false;
                }
            });

            titleInput.dispatchEvent(new Event('input'));
        }
    });
</script>

@endsection
@php
    $slugValue = old('slug') ?? ($guongtieubieu->slug ?? '');
@endphp
@php
    $actionRoute = $guongtieubieu->id
        ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.update', $guongtieubieu)
        : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.store');
@endphp
@section('content')
    {{ Breadcrumbs::render($guongtieubieu->id ? 'cms.dashboard.guong-tieu-bieu.edit' : 'cms.dashboard.guong-tieu-bieu.create', $guongtieubieu->id ?? '') }}
    <div class="card">
        <div class="card-header bg-primary d-flex flex-column justify-content-center">
            <h4 class="text-white text-uppercase mb-0">
                {{ $guongtieubieu->id !== null ? __('admin.ui.cap_nhat_guong_tieu_bieu') : __('admin.ui.them_moi_guong_tieu_bieu') }}
            </h4>
        </div>
        <div class="card-body">
            <form id="postCreate" action="{{ $actionRoute }}" method="POST" onsubmit="updateViewImage()">
                @csrf
                @if ($guongtieubieu->id)
                    <input type="hidden" name="_method" value="PUT">
                @endif
                <div class="app-ecommerce"> 
                    <div class="row">
                        <div class='col-12 my-5'>
                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.guong-tieu-bieu.index') }}"
                                    class="btn btn-outline-secondary waves-effect text-none">
                                    {{ __('admin.ui.tro_ve') }}
                                </a>
                                <button class="btn btn-primary waves-effect text-none"
                                    type="submit">{{ $guongtieubieu->id !== null ? __('admin.ui.cap_nhat') : __('admin.ui.them') }}</button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="card ">
                                <div class="card-body">
                                    <div class="form-floating form-floating-outline mb-5">
                                        <input type="text" class="form-control" id="title" name="title"
                                            value="{{ old('title', $guongtieubieu->title ?? '') }}"
                                            placeholder="{{ __('admin.ui.nhap_ten_tam_guong_tieu_bieu') }}"
                                            aria-describedby="defaultFormControlHelp" oninput="generateSlug()">
                                        <label for="title">{{ __('admin.ui.ten_tam_guong_tieu_bieu') }}</label>
                                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                            <div id="title-error" class="text-danger mt-1" style="display: none;"></div>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-5">
                                        <div class="col-lg-12 col-md-12 col-sm-12">
                                            {{-- <input
                                                placeholder="{{ __('Nhập mức đánh giá: Từ 0 đến 5 (có thể dùng số lẻ như 4.5)') }}"
                                                type="number" name="rating" class="form-control" min="0"
                                                max="5" step="1"
                                                value="{{ old('rating') ?? $guongtieubieu->rating }}"> --}}
                                             <input type="number" name="rating" class="form-control"
                                                placeholder="{{ __('admin.ui.nhap_muc_danh_gia_tu_0_den_5_co_the_dung_so_le_n') }}"
                                                min="0" max="5" step="0.1"
                                                value="{{ old('rating', $guongtieubieu->rating) }}">
                                            <x-input-error :messages="$errors->get('rating')" class="mt-2" />

                                        </div>
                                    </div>

                                    <div class="form-floating form-floating-outline mb-5">
                                        <div class="col-lg-12 col-md-12 col-sm-12">
                                            {{-- <input
                                                placeholder="{{ __('Nhập vai trò Ví dụ: Lập trình viên, Giảng viên tiêu biểu...') }}"
                                                type="text" name="role" class="form-control"
                                                value="{{ old('role') ?? $guongtieubieu->role }}"> --}}
                                                <input
                                                    placeholder="{{ __('admin.ui.nhap_vai_tro_vi_du_lap_trinh_vien_giang_vien_tie') }}"
                                                    type="text" name="role" class="form-control" maxlength="255"
                                                    value="{{ old('role', $guongtieubieu->role) }}">
                                                <x-input-error :messages="$errors->get('role')" class="mt-2" />

                                        </div>
                                    </div>



                                    <div class="form-floating form-floating-outline mb-5">
                                        <textarea class="form-control h-px-100" id="excerpt" placeholder="{{ __('admin.ui.gioi_thieu_ngan_gon') }}" name="excerpt">{{ old('excerpt') ?? $guongtieubieu->excerpt }}</textarea>
                                        <label for="excerpt">{{ __('admin.ui.gioi_thieu_ngan_gon') }}</label>
                                    </div>
                                    <x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
                                    <h5 class="mb-0 card-title">{{ __('admin.ui.thong_tin_tam_guong_tieu_bieu') }}</h5>
                                    {{-- <textarea name="content" id="ckeditor5">{{ old('content') ?? $guongtieubieu->content }}</textarea> --}}
                                    <textarea name="content" id="ckeditor5">{{ old('content', $guongtieubieu->content) }}</textarea>
                                    <x-input-error :messages="$errors->get('content')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card mb-6">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('admin.ui.thong_tin_hien_thi') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2 loai-xuat-ban">
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <b>{{ __('admin.ui.trang_thai_2') }}</b>
                                        </div> 
                                        <div class="col-lg-8 col-md-12 col-sm-12">
                                            {{-- <input type="hidden" name="show_home_page" value="0">
                                            <label class="switch switch-success">
                                                <input type="checkbox" name="show_home_page" value="1"
                                                    class="switch-input"
                                                    {{ old('show_home_page', $guongtieubieu->show_home_page ?? null) == 1 ? 'checked' : '' }} />
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"><i class="ri-check-line"></i></span>
                                                    <span class="switch-off"><i class="ri-close-line"></i></span>
                                                </span>
                                            </label> --}}

                                            <input type="hidden" name="show_home_page" value="0">
                                            <label class="switch switch-success">
                                            <input type="checkbox" name="show_home_page" value="1" class="switch-input"
                                                    {{ old('show_home_page', $guongtieubieu->show_home_page ?? 0) ? 'checked' : '' }}>
                                            <span class="switch-toggle-slider"> … </span>
                                            </label>


                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="accordion mt-4 mb-6" id="accordionWithIcon">
                                <div class="accordion-item active">
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
                                                
                                                {{-- <input id="thumbnail" class="form-control" type="hidden" name="avatar"
                                                    value="{{ old('avatar') ?? $guongtieubieu->getFirstMediaUrl('avatars') }}"> --}}
                                                <input id="thumbnail" type="hidden" name="avatar"
                                                    value="{{ old('avatar', $guongtieubieu->getFirstMediaUrl('avatars')) }}">
                                                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />

                                                <div id="holder" style="margin-top:15px;max-height:400px"
                                                    class="img-avt">
                                                    <img
                                                        src="{{ old('avatar') ?? $guongtieubieu->getFirstMediaUrl('avatars') }}">
                                                </div>
                                                {{-- <x-input-error :messages="$errors->get('avatar')" class="mt-2" /> --}}
                                            </div>
                                            <div class="row">
                                                <button type="button" id="lfm" data-input="thumbnail"
                                                    data-preview="holder" class="btn btn-primary">
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

