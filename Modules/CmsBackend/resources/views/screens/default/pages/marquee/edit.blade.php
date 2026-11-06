@php
    $configData = Helper::appClasses();
@endphp
@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', $item->id ? __('admin.ui.cap_nhat_marquee') : __('admin.ui.them_moi_marquee'))

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection
@section('page-style')
    @vite(['resources/assets/css/ckeditor.css', 'resources/assets/css/bai-viet-be.css', 'Modules/CmsBackend/resources/assets/sass/app.scss'])
@endsection
@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js', 'resources/assets/js/table-confirm-delete.js'])
    {{-- import chọn ảnh   --}}
    @vite(['Modules/CmsBackend/resources/assets/js/post.js', 'Modules/CmsBackend/resources/assets/js/custom-forms-pickers.js'])
@endsection

@section('content')
    {{ $item->id ? Breadcrumbs::render('cms.dashboard.cdn.marquee.edit', '') : Breadcrumbs::render('cms.dashboard.cdn.marquee.create', '') }}
    <div class="row">
        <!-- Form controls -->
        <div class="col-12 col-md-6">
            <form
                action="{{ $item->id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.update', $item) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.store') }}"
                method="post">
                @csrf
                @if ($item->id)
                    @method('put')
                @endif
                <div class="card">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $item->id ? __('admin.ui.cap_nhat') : __('admin.ui.add_new') }} marquee
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="form-floating form-floating-outline mt-6 mb-6">
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="{{ __('admin.ui.nhap_ten_marquee') }}" autocomplete="off" value="{{ old('name') ?? $item->name }}">
                            <label for="name">{{ __('admin.ui.ten_marquee') }}</label>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="form-floating form-floating-outline">
                            <textarea type="text" class="form-control h-px-100" id="content" name="content" placeholder="{{ __('admin.ui.nhap_noi_dung_marquee') }}"
                                autocomplete="off">{{ old('content') ?? $item->content }}</textarea>
                            <label for="content">{{ __('admin.ui.noi_dung_marquee') }}</label>
                            <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        </div>


                        {{-- Ảnh marquee --}}
                        <div class="accordion mt-4 mb-6" id="accordionWithIcon">
                            <div class="accordion-item active">
                                <h2 class="accordion-header d-flex align-items-center">
                                    <button type="button" class="accordion-button accordion-header-font-size"
                                        data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2"
                                        aria-expanded="true">
                                        {{ __('admin.ui.anh_marquee') }}
                                    </button>
                                </h2>

                                <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <input id="thumbnail" name="avatar" class="form-control" type="hidden"
                                                name="filepath"
                                                value="{{ old('avatar') ?? $item->getFirstMediaUrl('avatars') }}">
                                            <div id="holder" style="margin-top:15px;max-height:400px" class="img-avt">
                                                <img src="{{ old('avatar') ?? $item->getFirstMediaUrl('avatars') }}">
                                            </div>
                                            <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                                        </div>
                                        <div class="row mt-2">
                                            <button type="button" id="lfm" data-input="thumbnail"
                                                data-preview="holder" class="btn btn-primary text-none">
                                                <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                                            </button>
                                        </div>
                                        <p class="fst-italic d-flex align-items-center mt-2"><span
                                                class='text-danger'>*</span>
                                            {{ __('admin.ui.chu_y_nen_chon_anh_co_kich_thuoc_phu_hop_chieu_c') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="mt-6 d-flex justify-content-end gap-2">
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.marquee.index') }}" type="button"
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
