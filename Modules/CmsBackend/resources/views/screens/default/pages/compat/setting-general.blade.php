{{-- CMS replacement for the shared "Cài đặt chung" view (pages.setting.general), selected by the module provider
     only for Khuyến nông requests. Same core fields and actions; plus contact, social links and map (saved by
     the cms.settings.common middleware). Theme and homepage information category are on "Cài đặt trang chủ". --}}
@php
    $configData = Helper::appClasses();
    $cmsCommon = $cmsCommon ?? [];
    $cmsValue = fn (string $key) => old($key, $cmsCommon[$key] ?? '');
@endphp
@extends('layouts/layoutMaster')

@section('title', __('admin.menu.cai_dat'))

@section('page-script')
    <script src="/vendor/laravel-filemanager/js/stand-alone-button.js" type="module"></script>
    <script type="module">
        var lfm = function(id, type, options) {
            let button = document.getElementById(id);

            button.addEventListener('click', function() {
                var route_prefix = (options && options.prefix) ? options.prefix : '/laravel-filemanager';
                var target_input = document.getElementById(button.getAttribute('data-input'));
                var target_preview = document.getElementById(button.getAttribute('data-preview'));

                let popupWinWidth = 900;
                let popupWinHeight = 600;
                let left = (screen.width - popupWinWidth) / 2;
                let top = (screen.height - popupWinHeight) / 4;
                window.open(route_prefix + '?type=' + type || 'file', 'FileManager', 'width=' + popupWinWidth +
                    ',height=' + popupWinHeight + ',left=' + left + ',top=' + top);
                window.SetUrl = function(items) {
                    var file_path = items.map(function(item) {
                        return item.url;
                    }).join(',');
                    target_input.value = file_path;
                    target_input.dispatchEvent(new Event('change'));
                    target_preview.innerHTML = '';
                    items.forEach(function(item) {
                        let img = document.createElement('img')
                        img.setAttribute('style', 'height: 5rem')
                        img.setAttribute('src', item.thumb_url)
                        target_preview.appendChild(img);
                    });
                    target_preview.dispatchEvent(new Event('change'));
                };
            });
        };

        lfm('lfm', 'image', {
            prefix: '/laravel-filemanager'
        });
    </script>
@endsection

@section('content')
    {{ Breadcrumbs::render('setting.general', '') }}
    <div class="row">
        <div class="col-12">
            <form action="{{ route('setting.general.update') }}" method="post">
            @csrf
            @method('put')

            <div class="card mb-6">
                <h5 class="card-header">{{ __('admin.ui.giao_dien_trang_cong_khai') }}</h5>
                <div class="card-body">
                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    {{-- Read only: the theme is chosen by the Developer. No selector, no save button, no link to Developer Settings. --}}
                    <div class="row">
                        <span class="col-sm-3 col-form-label fw-bold">{{ __('admin.ui.theme_trang_cong_khai') }}</span>
                        <div class="col-sm-9">
                            <div class="form-control-plaintext">{{ $screenTheme->choices('public')[$screenTheme->key('public')] ?? $screenTheme->key('public') }}</div>
                            <p class="small text-muted mb-0">{{ __('admin.ui.theme_managed_by_developer_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <h5 class="card-header">{{ __('admin.menu.cai_dat_chung') }}</h5>
                <div class="card-body mt-5">
                    <div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="web_title">{{ __('admin.ui.tieu_de_trang_web') }}</label>
                            <div class="col-sm-9">
                                <input type="text" name="web_title" class="form-control" id="web_title" placeholder="{{ __('admin.ui.tieu_de_trang_web') }}" autocomplete="off" value="{{ old('web_title') ?? $option->web_title }}">
                                @include('cmsbackend::screens.components.translation.option-alternate-input', ['key' => 'web_title', 'label' => 'Site title'])
                                <x-input-error :messages="$errors->get('web_title')" class="mt-2" />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="web_description">{{ __('admin.ui.dong_mo_ta') }}</label>
                            <div class="col-sm-9">
                                <input type="text" name="web_description" class="form-control" id="web_description" placeholder="{{ __('admin.ui.dong_mo_ta') }}" autocomplete="off" value="{{ old('web_description') ?? $option->web_description }}">
                                @include('cmsbackend::screens.components.translation.option-alternate-input', ['key' => 'web_description', 'label' => 'Tagline'])
                                <x-input-error :messages="$errors->get('web_description')" class="mt-2" />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold">{{ __('admin.ui.bieu_tuong_trang_web') }}</label>
                            <div class="col-sm-9">
                                <div class="d-flex align-items-center gap-3 cms-logo-field">
                                    <div id="holder" class="cms-logo-preview">
                                        @if (old('web_logo') ?? $option->web_logo)
                                            <img src="{{ old('web_logo') ?? $option->web_logo }}" style="height: 5rem;">
                                        @endif
                                    </div>
                                    <div>
                                        <button type="button" id="lfm" data-input="thumbnail" data-preview="holder" class="btn btn-outline-secondary">
                                            <i class="fa fa-picture-o"></i> {{ __('admin.ui.chon_anh') }}
                                        </button>
                                        <input id="thumbnail" name="web_logo" type="hidden" value="{{ old('web_logo') ?? $option->web_logo }}">
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('web_logo')" class="mt-2" />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="web_title_color">{{ __('admin.ui.mau_tieu_de') }}</label>
                            <div class="col-sm-9">
                                <input type="color" name="web_title_color" id="web_title_color" class="form-control form-control-color w-100" value="{{ old('web_title_color', $option->web_title_color ?? '#000000') }}">
                                <x-input-error :messages="$errors->get('web_title_color')" class="mt-2" />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
     
            <div class="card mb-6">
                <div class="card-body mt-5">
                    <div>

                        <div class="cms-setting-heading">
                            <h6>{{ __('admin.ui.thong_tin_lien_he') }}</h6>
                        </div>
                        @foreach (\Modules\CmsBackend\Support\CommonSettings::CONTACT as $field => $contactLabel)
                            @php($label = __('admin.ui.'.$field))
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label fw-bold" for="{{ $field }}">{{ $label }}</label>
                                <div class="col-sm-9">
                                    <input type="text" name="{{ $field }}" class="form-control" id="{{ $field }}" placeholder="{{ $label }}" autocomplete="off" value="{{ $cmsValue($field) }}">
                                    @if (in_array($field, \Modules\CmsBackend\Support\CommonSettings::TRANSLATABLE, true))
                                        @include('cmsbackend::screens.components.translation.option-alternate-input', ['key' => $field, 'label' => $label])
                                    @endif
                                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                                </div>
                            </div>
                        @endforeach
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="web_location">Map link (Google)</label>
                            <div class="col-sm-9">
                                <input type="text" name="web_location" class="form-control" id="web_location" placeholder="Map link" autocomplete="off" value="{{ $cmsValue('web_location') }}">
                                <x-input-error :messages="$errors->get('web_location')" class="mt-2" />
                            </div>
                        </div>

                        <div class="cms-setting-heading">
                            <h6>{{ __('admin.ui.mang_xa_hoi') }}</h6>
                        </div>
                        @foreach (\Modules\CmsBackend\Support\CommonSettings::SOCIAL as $field => [$label])
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label fw-bold" for="{{ $field }}">{{ $label }}</label>
                                <div class="col-sm-9">
                                    <input type="text" name="{{ $field }}" class="form-control" id="{{ $field }}" placeholder="{{ __('admin.ui.nhap_duong_dan_label', ['label' => $label]) }}" autocomplete="off" value="{{ $cmsValue($field) }}">
                                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                                </div>
                            </div>
                        @endforeach

                        <div class="cms-setting-heading">
                            <h6>{{ __('admin.ui.footer_settings') }}</h6>
                            <p>{{ __('admin.ui.footer_settings_note') }}</p>
                        </div>
                        @foreach (\Modules\CmsBackend\Support\CommonSettings::FOOTER as $field => $maxLength)
                            @php($label = __('admin.ui.'.$field))
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label fw-bold" for="{{ $field }}">{{ $label }}</label>
                                <div class="col-sm-9">
                                    <input type="text" name="{{ $field }}" class="form-control" id="{{ $field }}" maxlength="{{ $maxLength }}" placeholder="{{ $label }}" autocomplete="off" value="{{ $cmsValue($field) }}">
                                    @if (in_array($field, \Modules\CmsBackend\Support\CommonSettings::TRANSLATABLE, true))
                                        @include('cmsbackend::screens.components.translation.option-alternate-input', ['key' => $field, 'label' => $label])
                                    @endif
                                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                                </div>
                            </div>
                        @endforeach

                        <div class="row mb-4">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-9 text-end">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('admin.ui.luu') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        </div>
        <div class="col-12">
            <div class="card mb-6 cms-control-center">
                <h5 class="card-header">{{ __('admin.ui.trung_tam_dieu_khien') }}</h5>
                <div class="card-body">
                    <p class="cms-control-note">{{ __('admin.ui.xoa_du_lieu_tam_cache_ma_he_thong_luu_de_tai_tra') }}</p>
                    <a href="{{ route('setting.general.clear-cache') }}" class="btn btn-primary waves-effect waves-light">{{ __('admin.ui.xoa_bo_nho_dem') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
