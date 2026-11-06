@extends('layouts/layoutMaster')

@section('title', 'Menu')

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
    @vite(['resources/assets/js/table-confirm-delete.js'])
@endsection

@section('content')
    {{ Breadcrumbs::render('menus.index', '') }}
    <!-- Users List Table -->
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Menu') }}</h5>
        <div class="card-body mt-5">
            <div>
                <form action="" method="get">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
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
            <div class="table-responsive text-nowrap" id="table-trang-container">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    {{-- @can(PermissionsEnum::THEM_MON_HOC->label()) --}}
                    <a href="{{ route('menus.create') }}" type="button"class="btn btn-primary waves-effect text-none">
                        <span class="tf-icons ri-add-line ri-16px me-2"></span>Thêm mới
                    </a>
                    {{-- @endcan --}}
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table id="main-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%">STT</th>
                            <th>Tên menu</th>
                            <th>Mô tả</th>
                            <th>Vị trí hiển thị</th>
                            <th style="width: 180px">Ngày cập nhật</th>
                            <th style="width: 80px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menus as $menu)
                            <tr>
                                <td>{{ ($menus->currentPage() - 1) * $menus->perPage() + $loop->index + 1 }}</td>
                                <td>{{ $menu->name }}</td>
                                <td>{{ $menu->description }}</td>
                                <td>
                                    @if ($menu->position)
                                        {{ join(', ', $menu->position->toArray()) }}
                                    @endif
                                </td>
                                <td>{{ $menu->updated_at->format('H:i d/m/Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-50">
                                        {{-- <a href="{{ route('menus.edit', $menu) }}"
                                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                      data-bs-toggle="tooltip"
                                      title="Xem">
                                      <i class="ri-eye-line ri-20px"></i>
                                    </a> --}}
                                        <button
                                            class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-line ri-20px"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end m-0" style="">
                                            <a href="{{ route('menus.edit', $menu) }}" class="dropdown-item">
                                                <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item delete-record"
                                                data-action="action-delete" data-id="{{ $menu->id }}"
                                                data-route="{{ route('menus.destroy', $menu) }}"
                                                data-reload-table="main-table">
                                                <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if ($menus->isEmpty())
                            <tr>
                                <td colspan="100">Không tìm thấy dữ liệu</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $menus->onEachSide(2)->links('layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
