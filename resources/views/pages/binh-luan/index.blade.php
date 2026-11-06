@extends('layouts/layoutMaster')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/vendor/libs/tagify/tagify.js']))
@endsection

@section('page-script')
    @include('pages.binh-luan.partials.index_pagescript')
@endsection

@section('page-style')
    @vite(['resources/assets/css/bai-viet-be.css'])
@endsection
@section('content')
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Danh sách bình luận') }}</h5>
        <div class="card-body mt-5">
            <div>
                <form id="searchForm" action="{{ route('binh-luan.index') }}" method="get">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-en mb-5">
                        <div class="input-group input-group-merge bg-white">
                            <span class="input-group-text" id="basic-addon-search31">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="search" id="filter" name="filter" class="form-control"
                                placeholder="Tìm kiếm..." aria-label="Tìm kiếm..." value="{{ request()->filter }}">

                        </div>

                        <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                            <span class="tf-icons ri-search-line ri-16px me-2"></span>Tìm
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap" id="table-bai-viet-container">
                @include('_partials.binh-luan.table-binh-luan')
            </div>

        </div>
    </div>
@endsection
