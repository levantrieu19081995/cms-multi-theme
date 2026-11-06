@extends('layouts/layoutMaster')

@section('title', 'Quản lý thư viện hình ảnh')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @include('pages.thu-vien-hinh-anh.partials.index_pagescript')
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection
@section('content')
    <div class="card">
        <h5 class="card-header">{{ __('Danh sách thư viện hình ảnh') }}</h5>
        <div class="card-body">
            <div>
                <form id="searchFormTrang" action="" method="get">
                    <div class="input-group input-group-merge mb-6">
                        <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
                        <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..."
                            aria-label="Tìm kiếm..." value="{{ request()->filter }}">
                        <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect" type="button"
                            data-bs-toggle="collapse">Nâng cao</a>
                    </div>
                    <div class="collapse row @if (request()->postStatuses || request()->userStatuses) show @endif" id="advanced-filter">
                        <div class="col-md-6 mb-6">
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
                    <div class="row">
                        <div class="mb-6">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
                            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('thu-vien-hinh-anh.create') }}" type="button"
                                class="btn btn-outline-secondary waves-effect">Thêm
                                mới</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap" id="table-trang-container">
                @include('_partials.bai-viet.table-bai-viet', [
                    'mode' => 'normal',
                    'type' => 'image',
                    'route' => 'hinh-anh',
                ])
            </div>
        </div>
    </div>

@endsection
