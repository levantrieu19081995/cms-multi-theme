@php
    use Modules\DaoTaoLaiXe\Models\Permission;
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Vai trò')

@section('vendor-style')
@endsection

@section('vendor-script')


@endsection

@section('page-script')
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-default'
        },
        buttonsStyling: false
    });

    // Hàm xác nhận xóa
    function confirmDelete(roleId) {
        var formId = 'form-delete-' + roleId;
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
                    // Nếu người dùng đồng ý, gửi form xóa
                    form.submit();
                }
            });
    }
</script>


 @endsection


@section('content')
    {{ Breadcrumbs::render('roles.index', '') }}
    <!-- Roles List Table -->
    <div class="card">
        <h5 class="card-header bg-primary text-white text-uppercase">Vai trò</h5>
        <div class="card-body mt-6">
            <div>
                <form action="" method="get">
                    <div class="input-group input-group-merge mb-6">
                        <span class="input-group-text" id="basic-addon-search31"><i class="ri-search-line"></i></span>
                        <input type="text" id="filter" name="filter" class="form-control" placeholder="Tìm kiếm..."
                            aria-label="Tìm kiếm..." value="{{ request()->filter }}">
                    </div>
                    <div class="row">
                        <div class="mb-6 d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-outline-secondary waves-effect waves-light">Tìm</button>
                            <a href="{{ route('roles.create') }}" type="button"
                                class="btn btn-primary waves-effect text-none"><span
                                    class="tf-icons ri-add-line ri-16px me-2"></span>Thêm mới</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive ">
                <table id="role-table" class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 1%">STT</th>
                            <th>Vai trò</th>
                            <th>Người dùng</th>
                            <th style="width: 60%">Quyền</th>
                            <th style="width: 80px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="text-center">
                                    {{ ($roles->currentPage() - 1) * $roles->perPage() + $loop->index + 1 }}</td>
                                <td>{{ $role->name }}</td>
                                <td>
                                    <div class="d-flex avatar-group">
                                        @foreach ($role->users as $user)
                                            <div class="avatar">
                                                <x-theme::avatar alt="Avatar" class="rounded-circle" :user="$user" />
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <ul>
                                        @if ($permissionsByRole[$role->id]['parentPermissions']->count())
                                            @foreach ($permissionsByRole[$role->id]['parentPermissions'] as $parent)
                                                @php
                                                    $childPermissions = $permissionsByRole[$role->id][
                                                        'groupedPermissions'
                                                    ]
                                                        ->get($parent->id, collect())
                                                        ->pluck('name')
                                                        ->toArray();
                                                @endphp

                                                <li class="mb-2">
                                                    <span class="text-primary"
                                                        style="font-weight: 500">{{ $parent->name }}</span>
                                                    @if (!empty($childPermissions))
                                                        <span class="text-dark">: {{ implode(', ', $childPermissions) }}
                                                    @endif
                                                </li>
                                            @endforeach

                                            @foreach ($permissionsByRole[$role->id]['groupedPermissions'] as $parentId => $childPermissions)
                                                @if (!isset($permissionsByRole[$role->id]['parentDetails'][$parentId]))
                                                    @continue
                                                @endif

                                                @if (!$permissionsByRole[$role->id]['parentPermissions']->contains('id', $parentId))
                                                    <li class="mb-2">
                                                        <span class="text-primary"
                                                            style="font-weight: 500">{{ $permissionsByRole[$role->id]['parentDetails'][$parentId]->name }}</span>
                                                        <span class="text-dark"> :
                                                            {{ implode(', ', $childPermissions->pluck('name')->toArray()) }}</span>
                                                    </li>
                                                @endif
                                            @endforeach
                                        @elseif(isset($permissionsByRole[$role->id]['groupedPermissions']['']) &&
                                                $permissionsByRole[$role->id]['groupedPermissions']['']->count())
                                            <li>
                                                <span class="text-dark">
                                                    {{ implode(', ', $permissionsByRole[$role->id]['groupedPermissions']['']->pluck('name')->toArray()) }}
                                                </span>
                                            </li>
                                        @elseif($role->permissions->count())
                                            <li>
                                                <span class="text-dark">
                                                    {{ implode(', ', $role->permissions->pluck('name')->toArray()) }}
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-50">
                                        <button
                                            class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-2-line ri-20px"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end m-0" style="">
                                             <a href="{{ route('roles.edit', $role) }}" class="dropdown-item">
                                                <i class="ri-edit-box-line me-2"></i><span>Sửa</span>
                                            </a>
                                            {{--<a href="javascript:void(0)" class="dropdown-item delete-record"
                                                data-action="role-delete" data-id="{{ $role->id }}"
                                                data-route="{{ route('roles.destroy', $role) }}">
                                                <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a> --}}
                                            {{-- <a href="{{ route('roles.destroy', $role) }}" class="dropdown-item" 
                                            onclick="event.preventDefault(); document.getElementById('form-delete-{{ $role->id }}').submit();">
                                            <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a> --}}
                                            <a href="javascript:void(0)" class="dropdown-item" onclick="confirmDelete({{ $role->id }})">
                                                <i class="ri-delete-bin-7-line me-2"></i><span>Xóa</span>
                                            </a>
                                        
                                            <form id="form-delete-{{ $role->id }}" action="{{ route('roles.destroy', $role) }}" method="POST" style="display:none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>


                                        </div>
                                    </div>
                                    {{-- <form method="post" action="{{ route('roles.destroy', $role) }}"
                                        id="form-delete-{{ $role->id }}">
                                        @csrf
                                        @method('delete')
                                    </form> --}}
                                    {{-- <form method="post" action="{{ route('roles.destroy', $role) }}"
                                        id="form-delete-{{ $role->id }}">
                                        @csrf
                                        @method('delete')
                                    </form> --}}
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
