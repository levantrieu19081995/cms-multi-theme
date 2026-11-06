{{-- @extends('pages.bai-viet.edit_layout', ['resource' => 'video']) --}}
@extends('cmsbackend::layouts.post-layout.create_edit_layout', ['resource' => 'video'])

@section('_page-script')
    @include('pages.video.partials.edit_videotype_pagescript')
@endsection

@section('_excerpt')
    {{-- Nội dung hiển thị Card gốc trên bên trái, dưới URL --}}
@endsection

@section('_content')
    <div class="card-header">
        <div class="nav-align-top">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link d-flex flex-column gap-1 waves-effect waves-light active"
                        role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-within-card-active"
                        aria-controls="navs-pills-within-card-active" aria-selected="true"><i
                            class="tf-icons ri-links-line"></i>{{ __('Đường dẫn Youtube') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link d-flex flex-column gap-1 waves-effect waves-light" role="tab"
                        data-bs-toggle="tab" data-bs-target="#navs-pills-within-card-link"
                        aria-controls="navs-pills-within-card-link" aria-selected="false" tabindex="-1"><i
                            class="tf-icons ri-video-upload-line"></i>{{ __('Tải lên video') }}</button>
                </li>

            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="tab-content p-0">
            <div class="tab-pane fade active show" id="navs-pills-within-card-active" role="tabpanel">
                <div class="form-floating form-floating-outline mb-6">
                    <input type="link" oninput="videoChange()" name="content"
                        value="{{ old('content') ?? $post->content }}" class="form-control" id="linkVideo"
                        placeholder="https://video">
                    <label for="exampleFormControlInput1">Đường dẫn video</label>
                </div>
            </div>
            <div class="tab-pane fade" id="navs-pills-within-card-link" role="tabpanel">
                <button type="button" id="lfm" data-input="thumbnailVideo" data-type="video" data-preview="holder"
                    class="btn btn-primary waves-effect waves-light"> <i class="ri-video-add-fill"></i>Chọn
                    video</button>
                <input id="thumbnailVideo" name="videos" onchange="videoUploadChange()" class="form-control" type="hidden"
                    @if (isset($videos)) value="{{ $videos }}" @else value="{{ old('videos') }}" @endif>
            </div>
        </div>
    </div>
    <div class="p-5 pt-0">

        <div class="cursor-pointer">
            @include('_partials.video.youtube-iframe')
        </div>
    </div>
@endsection

@section('_infos')
    {{-- Nội dung hiển thị ở Card gốc trên bên phải --}}
    <div class="row mb-2">
        <div class="col-lg-4 col-md-12 col-sm-12">
            <b>Hiển thị ở trang chủ:</b>
        </div>
        <div class="col-lg-8 col-md-12 col-sm-12">
            <label class="switch switch-success">
                {{-- <input type="checkbox" name="show_home_page" class="switch-input"
                    {{ $post->show_home_page === '1' ? 'checked' : '' }} /> --}}
                     <input type="checkbox" name="show_home_page" value="1" class="switch-input"
    {{ old('show_home_page', $post->show_home_page) == '1' ? 'checked' : '' }} />

                <span class="switch-toggle-slider">
                    <span class="switch-on">
                        <i class="ri-check-line"></i>
                    </span>
                    <span class="switch-off">
                        <i class="ri-close-line"></i>
                    </span>
                </span>

            </label>
        </div>
    </div>
@endsection

@section('_attributes')
    {{-- Nội dung hiển thị ở Card gốc dưới bên phải --}}
@endsection
