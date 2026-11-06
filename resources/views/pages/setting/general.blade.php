@php
    $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Cài đặt')
@section('vendor-style')
    {{-- @vite(Theme::asset(['resources/assets/vendor/?.css'])) --}}
@endsection

@section('page-style')
    <!-- Page -->
    {{-- @vite(Theme::asset(['resources/assets/vendor/scss/?.css'])) --}}
@endsection

@section('vendor-script')
    {{-- @vite(Theme::asset(['resources/assets/vendor/?.js'])) --}}
@endsection

@section('page-script')
    {{-- @vite(Theme::asset(['resources/assets/js/?.js'])) --}}
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

                    // set the value of the desired input to image url
                    target_input.value = file_path;
                    target_input.dispatchEvent(new Event('change'));

                    // clear previous preview
                    target_preview.innerHTML = '';

                    // set or change the preview image src
                    items.forEach(function(item) {
                        let img = document.createElement('img')
                        img.setAttribute('style', 'height: 5rem')
                        img.setAttribute('src', item.thumb_url)
                        target_preview.appendChild(img);
                    });

                    // trigger change event
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
        <!-- Form controls -->
        <div class="col-md-6">
            <div class="card mb-6">
                <div class="card-header bg-primary d-flex flex-column justify-content-center">
                    <h5 class="text-white text-uppercase mb-0">Cài đặt chung
                    </h5>
                </div>
                <div class="card-body mt-5">
                    <form action="{{ route('setting.general.update') }}" method="post">
                        @csrf
                        @method('put')
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="basic-default-name">Tiêu đề trang web</label>
                            <div class="col-sm-9">
                                <input type="text" name="web_title" class="form-control" id="basic-default-name"
                                    placeholder="Tiêu đề trang web" autocomplete="off"
                                    value="{{ old('web_title') ?? $option->web_title }}">
                                <x-input-error :messages="$errors->get('web_title')" class="mt-2" />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="basic-default-name">Dòng mô tả</label>
                            <div class="col-sm-9">
                                <input type="text" name="web_description" class="form-control" id="basic-default-name"
                                    placeholder="Dòng mô tả" autocomplete="off"
                                    value="{{ old('web_description') ?? $option->web_description }}">
                                <x-input-error :messages="$errors->get('web_description')" class="mt-2" />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold">Biểu tượng trang web</label>
                            <div class="col-sm-9">
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Hình ảnh preview nằm bên trái -->
                                    <div id="holder" style="max-height: 100px;">
                                        @if (old('web_logo') ?? $option->web_logo)
                                            <img src="{{ old('web_logo') ?? $option->web_logo }}" style="height: 5rem;">
                                        @endif
                                    </div>

                                    <!-- Nút chọn ảnh nằm bên phải -->
                                    <div>
                                        <button type="button" id="lfm" data-input="thumbnail" data-preview="holder"
                                            class="btn btn-outline-secondary">
                                            <i class="fa fa-picture-o"></i> Chọn ảnh
                                        </button>
                                        <input id="thumbnail" name="web_logo" type="hidden"
                                            value="{{ old('web_logo') ?? $option->web_logo }}">
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('web_logo')" class="mt-2" />
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-bold" for="web_title_color">Màu tiêu đề</label>
                            <div class="col-sm-9">
                                <input type="color" name="web_title_color" id="web_title_color"
                                    class="form-control form-control-color w-100"
                                    value="{{ old('web_title_color', $option->web_title_color ?? '#000000') }}">
                                <x-input-error :messages="$errors->get('web_title_color')" class="mt-2" />
                            </div>
                        </div>

                        {{-- <div class="row mb-4">
                            <label class="col-sm-3 col-form-label" for="basic-default-name">Cho phép đăng ký</label>
                            <div class="col-sm-9">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="enable_guest_register"
                                        value="1" @if ((old('enable_guest_register') ?? $option->enable_guest_register) == '1') checked="" @endif>
                                    <label class="form-check-label">
                                        Ai cũng có thể đăng ký
                                    </label>
                                </div>
                            </div>
                        </div> --}}
                        {{-- <div class="row mb-4">
                            <label class="col-sm-3 col-form-label" for="basic-default-name">Chuyên mục mặc định</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="category_default">
                                    <option value="0" @if (old('category_default') ?? $option->category_default == 0) selected @endif>Chưa phân loại
                                    </option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->term->term_id }}"
                                            @if ((old('category_default') ?? $category->term->term_id) == $option->category_default) selected @endif>{{ $category->term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        {{-- <div class="row mb-4">
                            <label class="col-sm-3 col-form-label" for="basic-default-name">Định dạng bài viết mặc
                                định</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="post_format_default">
                                    @foreach ($postFormats as $format)
                                        <option value="{{ $format->value }}"
                                            @if (old('post_format_default') ?? $option->post_format_default == $format->value) selected @endif>{{ $format->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="row mb-4">
                            <div class="col-sm-3">
                            </div>
                            <div class="col-sm-9 text-end">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Lưu</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Form controls -->
        <div class="col-md-6">
            <div class="card mb-6">
                <h5 class="card-header">Trung tâm điều khiển</h5>
                <div class="card-body">
                    <a href="{{ route('setting.general.clear-cache') }}"
                        class="btn btn-primary waves-effect waves-light">Xóa bộ nhớ đệm</a>
                </div>
            </div>
        </div>
    </div>
@endsection
