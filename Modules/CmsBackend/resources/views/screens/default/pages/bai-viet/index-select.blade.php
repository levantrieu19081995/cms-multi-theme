@php
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('cmsbackend::shared.layouts/layoutMaster')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @vite(['Modules/CmsBackend/resources/assets/js/app.js', 'Modules/CmsBackend/resources/assets/js/confirm-delete.js', 'resources/assets/js/forms-selects.js', 'Modules/CmsBackend/resources/assets/js/bai-viet.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection
@section('content')
    <div class="card">
        <h5 class="card-header">{{ __('label.list_post') }}</h5>
        <div class="card-body">
            <div>
                <form id="searchForm" action="" method="get">
                    {{-- Giữ chế độ chọn cho mọi lần tìm kiếm/phân trang --}}
                    <input type="hidden" name="mode" value="select">
                    @if (request()->has('state'))
                        <input type="hidden" name="state" value="{{ request('state') }}">
                    @endif
                    <div class="input-group input-group-merge mb-6">
                        <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
                        <input type="text" id="filter" name="filter" class="form-control" placeholder="{{ __('admin.ui.search_placeholder') }}"
                            aria-label="{{ __('admin.ui.search_placeholder') }}" value="{{ request()->filter }}">
                        <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect" type="button"
                            data-bs-toggle="collapse">{{ __('admin.ui.nang_cao') }}</a>
                    </div>
                    <div class="collapse row @if (request()->postStatuses || request()->userStatuses) show @endif" id="advanced-filter">
                        <div class="col-md-6 mb-6">
                            <div class="form-floating form-floating-outline">
                                <select id="postStatus" name="postStatuses[]" class="select2 form-select form-select-lg"
                                    placeholder="{{ __('admin.ui.select_status') }}" data-allow-clear="true" multiple>
                                    @foreach ($postStatuses as $status)
                                        <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                                            @if ($status['selected']) selected @endif>
                                            {{ $status['enum']->label() }}</option>
                                    @endforeach
                                </select>
                                <label for="postStatus">{{ __('admin.ui.trang_thai') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-6">
                            <div class="form-floating form-floating-outline">
                                <select id="category" name="categories[]" class="select2 form-select form-select-lg"
                                    data-allow-clear="true" multiple>
                                    @foreach ($categories as $cate)
                                        <option value="{{ Illuminate\Support\Str::toBase64($cate->term_id) }}"
                                            @if ($cate['selected']) selected @endif>
                                            {{ $cate->term->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="category">{{ __('admin.ui.chuyen_muc') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-6">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('admin.ui.search') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap" id="table-bai-viet-container">
                @include('cmsbackend::screens.components.partials.post.table-post', [
                    'mode' => 'select',
                    'type' => \App\Enums\PostType::POST->value,
                ])
            </div>
        </div>
    </div>
@endsection
