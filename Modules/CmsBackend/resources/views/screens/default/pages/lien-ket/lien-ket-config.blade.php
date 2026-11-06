@php
    $configData = Helper::appClasses();
@endphp

@extends('cmsbackend::shared.layouts/layoutMaster')

@section('title', __('admin.ui.lien_ket_website'))
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/dropzone/dropzone.scss']))
@endsection
@section('page-style')
    @vite(['resources/assets/css/menu-config.css'])
    <style>
        .img-avt img {
            width: 100%;
            max-height: 180px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background: #fafbfc;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: block;
            margin: 0 auto;
        }

        .aspect-ratio-16-9 {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            /* 16:9 ratio */
        }

        .aspect-ratio-16-9 img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/dropzone/dropzone.js']))
    <script type="module" src="/plugins/jquery.nestable/jquery.nestable.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-file-upload.js', 'Modules/CmsBackend/resources/assets/js/lien-ket-config.js'])
@endsection

@section('content')
    {{ $link->id ? Breadcrumbs::render('cms.dashboard.cdn.lien-ket.edit', '') : Breadcrumbs::render('cms.dashboard.cdn.lien-ket.create', '') }}
    <form action="{{ $link->id ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.update', $link) : \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.store') }}"
        method="post">
        @csrf
        @if ($link->id)
            @method('put')
        @endif
        <input type="hidden" name="id" value="{{ $link->id }}">
        <div id="app" class='row'>
            <div class='col-md-6 col-12'>
                <div class="card mb-6">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $link->id ? __('admin.ui.cap_nhat') : __('admin.ui.add_new') }}
                            {{ __('admin.resource.website_link') }}
                        </h4>
                    </div>
                    <div class='card-body'>
                        <div class="form-floating form-floating-outline mt-6 mb-6">
                            <input type="text" class="form-control" id="name" name="title"
                                placeholder="{{ __('admin.ui.nhap_vao_ten_website') }}" autocomplete="off"
                                value="{{ old('title') ?? $link->title }}">
                            <label for="title">{{ __('admin.ui.ten_website_lien_ket') }}</label>
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="link" class="form-control" id="redirect_url" name="redirect_url"
                                placeholder="https://" autocomplete="off"
                                value="{{ old('redirect_url') ?? $link->redirect_url }}">
                            <label for="redirect_url">{{ __('admin.ui.duong_dan_dich') }}</label>
                            <x-input-error :messages="$errors->get('redirect_url')" class="mt-2" />
                        </div>


                        <div class="row gap-2">
                            <div class="col-md-4 col-12 d-flex flex-md-column flex-row gap-2 gap-md-0">
                                <h5>{{ __('Logo website') }}</h5>
                                <button type="button" id="lfm" data-input="thumbnail" data-preview="holder"
                                    class='btn btn-primary text-none'>
                                    <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                                </button>
                            </div>
                            <div class="col-md-7 col-12">
                                <input id="thumbnail" name="anhQuangCao" class="form-control" type="hidden" name="filepath"
                                    value="{{ old('anhQuangCao') ?? $link->getFirstMediaUrl('anhQuangCaos') }}">
                                <div id="holder" style="" class="img-avt aspect-ratio-16-9">
                                    @if (old('anhQuangCao') || $link->getFirstMediaUrl('anhQuangCaos'))
                                        <img src="{{ old('anhQuangCao') ?? $link->getFirstMediaUrl('anhQuangCaos') }}"
                                            alt="{{ __('admin.ui.xem_truoc_quang_cao') }}">
                                    @endif
                                </div>
                                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
                            </div>
                        </div>
                        <hr class="m-0">
                        <div class="">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-6">
                                <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.lien-ket.index') }}" type="button"
                                    class="btn btn-outline-secondary waves-effect text-none">{{ __('admin.ui.huy_bo') }}</a>
                                <button type="submit" class="btn btn-primary waves-effect waves-light"
                                    name="submit">{{ __('admin.ui.luu') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
