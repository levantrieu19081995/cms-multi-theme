@extends('layouts/layoutMaster')
@section('title', 'Chuyên mục')
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @include('pages.category.partials.index_pagescript')
@endsection

@section('page-style')
@endsection
@section('content')

    @php
        use App\Enums\CategoryTypeEnum;
    @endphp


    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Quản lý chuyên mục') }}</h5>
        <div class="card-body mt-5">
            <div class="card shadow-none custom-card">
                <form action="" method="get">

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

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <a href="{{ route('chuyen-muc.create') }}" type="button"
                            class="btn btn-primary waves-effect text-none"><span
                                class="tf-icons ri-add-line ri-16px me-2"></span>Thêm mới</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-chuyen-muc" id="table-cm">
                    <thead>
                        <tr class='text-center'>
                            <th style="width:5%">STT</th>
                            <th>Tên chuyên mục</th>
                            <th>Chuyên mục cha</th>
                            <th>Loại chuyên mục</th>  
                            <th style="width:1%">{{ __('Hành động') }}</th>
                            {{-- <th>#</th> --}}
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @if ($categories)
                            @foreach ($categories as $index => $cate)
                                <tr>
                                    <th scope="row" class="text-center">
                                        {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->index + 1 }}
                                    </th>
                                    <td class='text-start'>
                                        {{ $cate->term->name }}
                                    </td>
                                    <td class='text-start'>
                                        @if ($cate->parent)
                                            {{ $cate->parent->term->name }}
                                        @endif
                                    </td>

                                    <td class='text-center'>
                                        {{ \App\Enums\CategoryTypeEnum::tryFrom($cate->loai_chuyen_muc)?->label() ?? 'Không xác định' }}
                                    </td>


                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-50 justify-content-center"> 
                                            <a href="{{ route('chuyen-muc.edit', $cate) }}" class="dropdown-item"
                                                data-bs-toggle="tooltip" title="{{ __('Sửa') }}">
                                                <i class="ri-edit-box-line me-2"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item delete-record text-danger"
                                                data-bs-toggle="tooltip" data-action="menu-delete"
                                                data-id="{{ $cate->id }}"
                                                data-route="{{ route('chuyen-muc.destroy', $cate) }}"
                                                title="{{ __('Xóa') }}">
                                                <i class="ri-delete-bin-7-line me-2"></i>
                                            </a>
                                            {{-- </div> --}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @if ($categories->isEmpty())
                            <tr>
                                <td colspan="100">Không tìm thấy dữ liệu</td>
                            </tr>
                        @endif

                    </tbody>
                </table>
                <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                    {{ $categories->onEachSide(2)->links('layouts.paginate') }}
                </div>
            </div>
        </div>
    </div>

@endsection
