@php
    use Illuminate\Support\Facades\Crypt;
    $configData = Helper::appClasses();

@endphp
@extends('layouts/layoutMaster')

@section('title', ($role->id ? 'Cập nhật' : 'Thêm mới') . ' vai trò')
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset([
        'assets/vendor/libs/select2/select2.js',
        'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
    ]))
@endsection


@section('page-script')
    @vite(['resources/assets/js/forms-selects.js', 'resources/assets/js/role-permission-editor.js'])
@endsection

@section('content')
    {{ $role->id ? Breadcrumbs::render('roles.edit', '') : Breadcrumbs::render('roles.create', '') }}
    <div class="row">
        <!-- Form controls -->
        <div class="col-md-6">
            <form id="role-form" action="" method="POST" data-index-url="{{ route('roles.index') }}">
                @csrf
                <div class="card mb-6">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $role->id ? 'Cập nhật' : 'Thêm mới' }} vai trò
                        </h4>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-4 mt-6">1. Thông tin vai trò</h5>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Nhập tên vai trò" autocomplete="off" value="{{ old('name') ?? $role->name }}">
                            <label for="name">Tên vai trò</label>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <div id="name-error" class="text-danger mt-2" style="font-size: 0.875em;"></div>
                        </div>
                        <h5 class="mb-4">2. Phân quyền vào vai trò</h5>
                        <div class="form-floating form-floating-outline">
                            <div class="cms-permission-toolbar" aria-label="Công cụ tìm và chọn quyền">
                                <div class="cms-permission-search">
                                    <i class="ri-search-line" aria-hidden="true"></i>
                                    <input type="search" id="permission-search" class="form-control" placeholder="Tìm theo tên quyền..." autocomplete="off">
                                </div>
                                <select id="permission-group-filter" class="form-select" aria-label="Lọc theo cụm quyền">
                                    <option value="">Tất cả cụm quyền</option>
                                </select>
                                <div class="cms-permission-summary" id="permission-selection-summary" aria-live="polite">Đang tải quyền...</div>
                                <div class="cms-permission-actions" role="group" aria-label="Thao tác nhanh với quyền">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="permission-expand-all"><i class="ri-expand-up-down-line me-1" aria-hidden="true"></i>Mở tất cả</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="permission-collapse-all"><i class="ri-collapse-diagonal-line me-1" aria-hidden="true"></i>Thu gọn</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="permission-check-all"><i class="ri-checkbox-multiple-line me-1" aria-hidden="true"></i>Chọn tất cả</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="permission-uncheck-all"><i class="ri-checkbox-blank-line me-1" aria-hidden="true"></i>Bỏ chọn</button>
                                </div>
                            </div>
                            <div id="permission-error" class="text-danger mt-2" style="font-size: 0.875em;"></div>
                            <div id="permission-tree-status" class="cms-permission-tree-status" role="status">Đang tải danh sách quyền...</div>
                            <div id="permission-groups" data-user-id="{{ $role->id }}" aria-live="polite">
                            </div>
                        </div>
                    </div>
                    <hr class="m-0">
                    <div class="card-body d-md-flex justify-content-md-end">
                        <a href="{{ route('roles.index') }}" type="button"
                            class="btn btn-outline-secondary waves-effect text-none me-2">Trở về</a>
                        <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit"
                            value="cc">Lưu</button>
                    </div>
                </div>
            </form>
        </div>
    @endsection
