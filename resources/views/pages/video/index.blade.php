@extends('layouts/layoutMaster')

@section('title', 'Quản lý video')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 'resources/assets/js/forms-selects.js', 'Modules/CmsBackend/resources/assets/js/bai-viet.js'])
    @include('pages.trang.partials.index_pagescript')
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection
@section('content')
    {{ Breadcrumbs::render('video.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Danh sách video') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form id="searchFormTrang" 
                action="{{ route('video.index') }}"
                method="get">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="Video Tìm kiếm..." aria-label="Tìm kiếm..." value="{{ request()->filter }}">
                            <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect bg-white text-none"
                                type="button" data-bs-toggle="collapse" id="toggleFilter">Nâng cao</a>
                        </div>


                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>Tìm
                        </button>
                    </div>

                    <div class="collapse row @if (request()->postStatuses || request()->userStatuses) show @endif" id="advanced-filter">
                        <div class="col-md-12 mb-5">
                            <div class="form-floating form-floating-outline">
                                <select id="postStatus" name="postStatuses[]" class="select2 form-select form-select-lg"
                                    placeholder="__('label.Chọn trạng thái')" data-allow-clear="true" multiple>
                                    @foreach ($postStatuses as $status)
                                        <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                                            @if ($status['selected']) selected @endif>
                                            {{ $status['enum']->label() }}</option>
                                    @endforeach
                                </select>
                                <label for="postStatus">Trạng thái</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap" id="table-trang-container">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    {{-- @can(PermissionsEnum::THEM_MON_HOC->label()) --}}
                    <a href="{{ route('video.create') }}" type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>Thêm mới
                    </a>
                    {{-- @endcan --}}
                </div>
                {{-- @include('_partials.bai-viet.table-bai-viet', [
                    'mode' => 'normal',
                    'type' => 'video',
                    'route' => 'video',
                ]) --}}
                @include('cmsbackend::_partials.post.table-post', [
                    'mode' => 'normal',
                    'type' => 'video',
                    'route' => 'video',
                    'tableId' => 'video',
                ])

            </div>
        </div>

    </div>

@endsection
