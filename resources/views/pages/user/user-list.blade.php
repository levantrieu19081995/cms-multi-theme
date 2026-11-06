@extends('layouts/layoutMaster')

@section('title', 'Người dùng')

@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js']))
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js', 'resources/assets/js/table-confirm-delete.js'])

<!-- Đảm bảo load SweetAlert2 trước -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<!-- Đặt mã JavaScript sau khi SweetAlert2 đã load -->
<script>
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-default'
        },
        buttonsStyling: false
    });

    function confirmDelete(userId) {
        var formId = 'form-delete-' + userId;
        var form = document.getElementById(formId);

        swalWithBootstrapButtons
            .fire({
                title: 'Bạn có chắc muốn xóa?',
                text: 'Dữ liệu đã xóa sẽ không thể phục hồi!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                reverseButtons: false
            })
            .then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
    }
</script>


@endsection

@section('content')

    @if (isset($totalUser))
        <div class="row g-6 mb-6">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-1">{{ __('label.users') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-1 me-2">{{ $totalUser }}</h4>
                                    <p class="text-success mb-1">(100%)</p>
                                </div>
                                <small class="mb-0">{{ __('label.total_users') }}</small>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded-3">
                                    <div class="ri-user-line ri-26px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-1">{{ __('label.verified_users') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-1 me-1">{{ $verified }}</h4>
                                    <p class="text-success mb-1">(+95%)</p>
                                </div>
                                <small class="mb-0">{{ __('label.recent_analytics') }}</small>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded-3">
                                    <div class="ri-user-follow-line ri-26px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-1">{{ __('label.duplicate_users') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-1 me-1">{{ $userDuplicates }}</h4>
                                    <p class="text-danger mb-1">(0%)</p>
                                </div>
                                <small class="mb-0">{{ __('label.recent_analytics') }}</small>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded-3">
                                    <div class="ri-group-line ri-26px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-1">{{ __('label.verification_pending') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-1 me-1">{{ $notVerified }}</h4>
                                    <p class="text-success mb-1">(+6%)</p>
                                </div>
                                <small class="mb-0">{{ __('label.recent_analytics') }}</small>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded-3">
                                    <div class="ri-user-unfollow-line ri-26px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Users List Table -->
    {{ Breadcrumbs::render('users.index', '') }}
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">{{ __('Người dùng') }}</h5>
        <div class="card-body mt-5">
            <form action="" method="get">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                    <div class="input-group input-group-merge bg-white">
                        <span class="input-group-text" id="basic-addon-search31">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="search" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..."
                            aria-label="Tìm kiếm..." value="{{ request()->filter }}">
                        <a href="#advanced-filter" class="btn btn-outline-secondary waves-effect bg-white text-none"
                            type="button" data-bs-toggle="collapse" id="toggleFilter">Nâng cao</a>
                    </div>


                    <button type="submit" class="btn btn-outline-secondary waves-effect waves-light bg-white">
                        <span class="tf-icons ri-search-line ri-16px me-2"></span>Tìm
                    </button>
                </div>
                <div class="collapse row @if (request()->roles || request()->userStatuses) show @endif" id="advanced-filter">
                    <div class="col-md-6 mb-5">
                        <div class="form-floating form-floating-outline">
                            <select id="roles" name="roles[]" class="select2 form-select form-select-lg"
                                data-allow-clear="true" multiple>
                                @foreach ($roles as $role)
                                    <option value="{{ Illuminate\Support\Str::toBase64($role->id) }}"
                                        @if ($role['selected']) selected @endif>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <label for="roles">Vai trò</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-5">
                        <div class="form-floating form-floating-outline">
                            <select id="userStatus" name="userStatuses[]" class="select2 form-select form-select-lg"
                                data-allow-clear="true" multiple>
                                @foreach ($userStatuses as $status)
                                    <option value="{{ Illuminate\Support\Str::toBase64($status['enum']->value) }}"
                                        @if ($status['selected']) selected @endif>
                                        {{ $status['enum']->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="userStatus">Trạng thái</label>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap" id="table-trang-container">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        {{-- @can(PermissionsEnum::THEM_MON_HOC->label()) --}}
                        <a href="{{ route('users.create') }}"
                            type="button"class="btn btn-primary waves-effect text-none">
                            <span class="tf-icons ri-add-line ri-16px me-2"></span>Thêm mới
                        </a>
                        {{-- @endcan --}}
                    </div>
                </div>

            </form>
            <div class="table-responsive text-nowrap">
                <table id="main-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%">STT</th>
                            <th>Tên người dùng</th>
                            <th>Tên đăng nhập</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th style="width: 100px">Trạng thái</th>
                            <th style="width: 80px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->index + 1 }}</td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center user-name">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm me-3">
                                                <x-theme::avatar alt="Avatar" class="rounded-circle"
                                                    :user="$user" />
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="#" class="text-truncate text-heading">
                                                <span class="fw-medium">{{ $user->name }}</span>
                                            </a>
                                            {{-- <small>{{ $user->email }}</small> --}}
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ join(', ', $user->roles->pluck('name')->toArray()) }}</td>
                                <td>
                                    @if ($user->status)
                                        <span
                                            class="badge rounded-pill bg-label-success">{{ App\Enums\UserStatusEnum::tryFrom($user->status)->label() }}</span>
                                    @else
                                        <span
                                            class="badge rounded-pill bg-label-secondary">{{ App\Enums\UserStatusEnum::tryFrom($user->status)->label() }}</span>
                                    @endif
                                </td>
                                <td style=" display: flex; text-align: center;justify-content: center">
                                     <div class="d-flex align-items-center gap-50">
                                      {{-- <a href="{{ route('users.edit', $user) }}"
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
                                            {{-- <a href="{{ route('users.edit', $user) }}" class="dropdown-item">
                                        <i class="ri-eye-line me-2"></i><span>Xem</span>
                                      </a > --}}
                                            {{-- <a href="{{ route('users.edit', $user) }}" class="dropdown-item">
                                                <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                            </a> --}}
                                            <a href="{{ route('users.edit', $user) }}" class="dropdown-item" 
                                                data-reload-table="main-table">
                                                <i class="ri-edit-box-line me-2"></i><span>sửa</span>
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item" onclick="confirmDelete({{ $user->id }})">
                                                <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a>

                                            {{-- <a href="javascript:void(0)" class="dropdown-item delete-record"
                                                data-action="action-delete" data-id="{{ $user->id }}"
                                                data-route="{{ route('users.destroy', $user) }}"
                                                data-reload-table="main-table">
                                                <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a> --}}
                                        </div> 
                                    </div> 
                                    {{-- <div class="d-flex align-items-center gap-50">
                                        <a href="{{ route('users.edit', $user) }}"
                                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                        data-bs-toggle="tooltip"
                                        title="Sửa">
                                            <i class="ri-edit-box-line ri-20px"></i>
                                        </a>
                                    </div> --}}
                                     
                                    {{-- <form method="post" action="{{ route('users.destroy', $user) }}"
                                        id="form-delete-{{ $user->id }}">
                                        @csrf
                                        @method('delete')
                                    </form> --}}

                                    <form method="post" action="{{ route('users.destroy', $user) }}" id="form-delete-{{ $user->id }}" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>


                                </td>
                            </tr>
                        @endforeach
                        @if ($users->isEmpty())
                            <tr>
                                <td colspan="100">Không tìm thấy dữ liệu</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px;margin-left: 20px;">
                {{ $users->onEachSide(2)->links('layouts.paginate') }}
            </div>
        </div>
    </div>
@endsection
