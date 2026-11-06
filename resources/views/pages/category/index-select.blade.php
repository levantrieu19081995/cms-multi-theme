@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Chuyên mục')

@section('vendor-style')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.scss',
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.js',
'assets/vendor/libs/bootstrap-select/bootstrap-select.js'
]))
@endsection

@section('page-script')
@include('pages.category.partials.index_pagescript')
@endsection

@section('page-style')
@endsection
@section('content')

<div class="card">
  <h5 class="card-header">{{ __('Quản lý chuyên mục') }}</h5>
  <div class="card-body">
    <div>
      <form action="" method="get">
        <div class="input-group input-group-merge mb-6">
          <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
          <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..."
            aria-label="Tìm kiếm..." value="{{ request()->filter }}">
          {{-- <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect" type="button"
            data-bs-toggle="collapse">Nâng cao</a> --}}
        </div>

        <div class="row">
          <div class="mb-6">
            <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
            <a href="{{ route('chuyen-muc.create') }}" type="button" class="btn btn-outline-secondary waves-effect">Thêm
              mới</a>
          </div>
        </div>
      </form>
    </div>
    @include('pages.category.partials.category-table', ['mode' => 'select'])
  </div>
</div>

@endsection
