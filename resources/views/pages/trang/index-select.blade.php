{{-- @php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
@vite(Theme::asset([
  'assets/vendor/libs/select2/select2.scss',
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
  'assets/vendor/libs/select2/select2.js',
  'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
  'assets/vendor/libs/tagify/tagify.js',
]))
@endsection

 @section('page-script')
  @include('pages.trang.partials.index_pagescript')
@endsection  


@section('page-style')
@vite([
  'resources/assets/css/bai-viet-be.css'
])
@endsection
@section('content')
<div class="card">
  <h5 class="card-header">{{ __('label.list_page') }}</h5>
  <div class="card-body">
    <div>
      <form id="searchForm" action="" method="get">
        <div class="input-group input-group-merge mb-6">
          <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
          <input type="text" id="filter" name="filter"  class="form-control" placeholder="Tìm kiếm..."
             aria-label="Tìm kiếm..." value="{{ request()->filter }}">
          <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect" type="button"
            data-bs-toggle="collapse">Nâng cao</a>
        </div>
        <div class="collapse row @if (request()->postStatuses || request()->userStatuses) show @endif"
          id="advanced-filter">
          <div class="col-md-6 mb-6">
            <div class="form-floating form-floating-outline">
              <select id="postStatus" name="postStatuses[]" class="select2 form-select form-select-lg"
                placeholder="__('label.Chọn trạng thái')" data-allow-clear="true" multiple>
                @foreach ($postStatuses as $status)
                <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}" @if ($status['selected'])
                  selected @endif>
                  {{ $status['enum']->label() }}</option>
                @endforeach
              </select>
              <label for="postStatus">Trạng thái</label>
            </div>
          </div>
          <div class="col-md-6 mb-6">
            <div class="form-floating form-floating-outline">
              <select id="category" name="categories[]" class="select2 form-select form-select-lg"
                data-allow-clear="true" multiple>
                @foreach ($categories as $cate)
                <option value="{{ Illuminate\Support\Str::toBase64($cate->term_id) }}" @if ($cate['selected']) selected
                  @endif>
                  {{ $cate->term->name }}
                </option>
                @endforeach
              </select>
              <label for="category">Chuyên mục</label>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="mb-6">
            <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
          </div>
        </div>
      </form>
    </div>

     <div class="table-responsive text-nowrap" id="table-bai-viet-container">
        @include('_partials.bai-viet.table-bai-viet', 
        [
          'mode' => 'select', 
          'type' => \App\Enums\PostType::PAGE->value
        ])
    </div>  
  </div>
</div>

@endsection --}}

@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts/layoutMaster')

@section('vendor-style')
  @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
  @vite(Theme::asset([
    'assets/vendor/libs/select2/select2.js',
    'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
    'assets/vendor/libs/tagify/tagify.js',
  ]))
@endsection

@section('page-style')
  @vite(['resources/assets/css/bai-viet-be.css'])
@endsection

@section('content')
<div class="card">
  <h5 class="card-header">{{ __('label.list_page') }}</h5>
  <div class="card-body">
    <form id="searchForm" method="get">
      {{-- GIỮ CHẾ ĐỘ SELECT cho mọi lần submit/paginate --}}
      <input type="hidden" name="mode" value="select">
      {{-- nếu có state thì giữ luôn --}}
      @if(request()->has('state'))
        <input type="hidden" name="state" value="{{ request('state') }}">
      @endif

      <div class="input-group input-group-merge mb-6">
        <span class="input-group-text"><i class="ri-search-line"></i></span>
        <input type="text" id="filter" name="filter" class="form-control"
               placeholder="Tìm kiếm..." value="{{ request('filter') }}">
        <a href="#advanced-filter" class="btn btn-outline-secondary" data-bs-toggle="collapse">Nâng cao</a>
      </div>

      <div class="collapse row @if(request()->postStatuses || request()->categories) show @endif" id="advanced-filter">
        <div class="col-md-6 mb-6">
          <div class="form-floating form-floating-outline">
            <select id="postStatus" name="postStatuses[]" class="select2 form-select form-select-lg" multiple data-allow-clear="true">
              @foreach ($postStatuses as $status)
                <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                        @selected($status['selected'])>
                  {{ $status['enum']->label() }}
                </option>
              @endforeach
            </select>
            <label for="postStatus">Trạng thái</label>
          </div>
        </div>

        <div class="col-md-6 mb-6">
          <div class="form-floating form-floating-outline">
            <select id="category" name="categories[]" class="select2 form-select form-select-lg" multiple data-allow-clear="true">
              @foreach ($categories as $cate)
                <option value="{{ Illuminate\Support\Str::toBase64($cate->term_id) }}"
                        @selected($cate['selected'])>
                  {{ $cate->term->name }}
                </option>
              @endforeach
            </select>
            <label for="category">Chuyên mục</label>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary mt-3 mb-5">Tìm</button>
    </form>

    {{-- BẢNG --}}
    <div id="table-bai-viet-container">
      @include('_partials.bai-viet.table-bai-viet', [
        'mode' => 'select',
        'type' => \App\Enums\PostType::PAGE->value
      ])
    </div>
  </div>
</div>
@endsection

