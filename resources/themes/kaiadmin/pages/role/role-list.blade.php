@extends('layouts/layoutMaster')

@section('title', 'Vai trò')

@section('vendor-style')
{{-- @vite(Theme::asset([
  'assets/vendor/libs/select2/select2.scss',
])) --}}
@endsection

@section('vendor-script')
{{-- @vite(Theme::asset([
  'assets/vendor/libs/select2/select2.js',
  'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
])) --}}
@endsection

@section('page-script')
{{-- @vite(Theme::asset([
  'assets/js/forms-selects.js',
  'assets/js/user/user-list.js',
])) --}}
@endsection

@section('content')
    <!-- Roles List Table -->
    <div class="card">
        <h5 class="card-header">Vai trò</h5>
        <div class="card-body">
            <div>
              <form action="" method="get">
                <div class="input-group input-group-merge mb-6">
                  <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
                  <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..." aria-label="Tìm kiếm..."
                    value="{{ request()->filter }}">
                </div>
                <div class="row">
                  <div class="mb-6">
                    <button type="submit" class="btn btn-primary waves-effect waves-light">Tìm</button>
                    <a href="{{ route('roles.create') }}" type="button" class="btn btn-outline-secondary waves-effect">Thêm mới</a>
                  </div>
                </div>
              </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%">STT</th>
                            <th>Vai trò</th>
                            <th>Người dùng</th>
                            <th>Quyền</th>
                            <th style="width: 80px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ (($roles->currentPage() - 1) * $roles->perPage()) + $loop->index + 1 }}</td>
                                <td>{{ $role->name }}</td>
                                <td>
                                  <div class="d-flex avatar-group">
                                    @foreach ($role->users as $user)
                                    <div class="avatar">
                                      <x-custom.avatar alt="Avatar" class="rounded-circle" :user="$user" />
                                    </div>
                                    @endforeach
                                  </div>
                                </td>
                                <td>
                                    {{ join(', ', $role->permissions->pluck('name')->toArray())}}
                                </td>
                                <td>
                                  <div class="d-flex align-items-center gap-50">
                                    <a href="{{ route('roles.edit', $role) }}"
                                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                      data-bs-toggle="tooltip"
                                      title="Xem">
                                      <i class="ri-eye-line ri-20px"></i>
                                    </a>
                                    <button
                                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                      data-bs-toggle="dropdown"
                                      aria-expanded="false">
                                      <i class="ri-more-2-line ri-20px"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end m-0" style="">
                                      <a href="{{ route('roles.edit', $role) }}" class="dropdown-item">
                                        <i class="ri-eye-line me-2"></i><span>Xem</span>
                                      </a >
                                      <a href="{{ route('roles.edit', $role) }}" class="dropdown-item">
                                        <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                      </a>
                                      <a href="javascript:;" class="dropdown-item delete-record"
                                          data-action="user-delete"
                                          data-user-id="{{ $role->id }}">
                                        <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                      </a>
                                    </div>
                                  </div>
                                  <form method="post" action="{{ route('roles.destroy', $role) }}" id="form-delete-{{ $role->id }}">
                                    @csrf
                                    @method('delete')
                                  </form>
                                </td>
                            </tr>
                        @endforeach
                        @if ($roles->isEmpty())
                          <tr>
                            <td colspan="100">Không tìm thấy dữ liệu</td>
                          </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
              {{ $roles->onEachSide(2)->links('layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
