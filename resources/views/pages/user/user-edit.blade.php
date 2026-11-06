@php
    $configData = Helper::appClasses(); 
@endphp
@extends('layouts/layoutMaster')

@section('title', ($user->id ? 'Cập nhật' : 'Thêm mới') . ' người dùng')
@section('vendor-style')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.scss']))
@endsection

@section('vendor-script')
    @vite(Theme::asset(['assets/vendor/libs/select2/select2.js', 'assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'assets/js/forms-selects.js']))
@endsection

@section('page-script')
    @vite(Theme::asset([]))  
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkAllBtn = document.getElementById('checkAllBtn');
            const checkboxes = document.querySelectorAll('.parent-checkbox input[type="checkbox"]');

            // Toggle tất cả checkbox
            checkAllBtn.addEventListener('click', function () {
                const isAllChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !isAllChecked);
            });

            // Lắng nghe mọi thay đổi checkbox
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const isChild = this.closest('.child-checkboxes');
                    const parentDiv = isChild ? isChild.previousElementSibling : this.parentElement;
                    const parentCheckbox = isChild ? parentDiv.querySelector('input[type="checkbox"]') : null;
                    const childWrapper = this.closest('.child-checkboxes');

                    if (!isChild) {
                        // Nếu là cha → check/bo toàn bộ con
                        const childrenContainer = this.parentElement.nextElementSibling;
                        if (childrenContainer && childrenContainer.classList.contains('child-checkboxes')) {
                            const children = childrenContainer.querySelectorAll('input[type="checkbox"]');
                            children.forEach(child => child.checked = this.checked);
                        }
                    } else {
                        // Nếu là con → xử lý cha
                        const siblings = childWrapper.querySelectorAll('input[type="checkbox"]');
                        const anyChecked = Array.from(siblings).some(cb => cb.checked);
                        if (parentCheckbox) parentCheckbox.checked = anyChecked;
                    }
                });
            });
        });
    </script> 


@endsection



@section('content')
    {{ $user->id ? Breadcrumbs::render('users.edit', '') : Breadcrumbs::render('users.create', '') }}
    <div class="row">
        <!-- Form controls -->
        <div class="col-md-6">
            <form action="{{ $user->id ? route('users.update', $user) : route('users.store') }}" method="post">
                @csrf
                @if ($user->id)
                    @method('put')
                @endif
                <div class="card mb-6">
                    <div class="card-header bg-primary d-flex flex-column justify-content-center">
                        <h4 class="text-white text-uppercase mb-0">{{ $user->id ? 'Cập nhật' : 'Thêm mới' }} người dùng
                        </h4>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-4 mt-6">1. Thông tin tài khoản</h5>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Họ và tên"
                                autocomplete="off" value="{{ old('name') ?? $user->name }}">
                            <label for="name">Tên người dùng</label>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="text" class="form-control" id="username" name="username" 
                                placeholder="Tên đăng nhập" autocomplete="off"
                                value="{{ old('username', $user->username) }}">

                            <label for="username">Tên đăng nhập</label>
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6">
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Địa chỉ email" autocomplete="off" value="{{ old('email') ?? $user->email }}">

                            <label for="email">Email</label>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6 form-password-toggle">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline">
                                    <x-theme::text-input type="password" id="password" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        autocomplete="off" />
                                    <label for="password">Mật khẩu</label>
                                </div>
                                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div class="form-floating form-floating-outline mb-6 form-password-toggle">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline">
                                    <x-theme::text-input type="password" id="password_confirmation"
                                        name="password_confirmation"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        autocomplete="off" />
                                    <label for="password_confirmation">Nhập lại mật khẩu</label>
                                </div>
                                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                        

                        <hr class="m-0">
                        <br />

                        @php
                            use Illuminate\Support\Facades\Crypt;

                            // 1) Vai trò: giải mã mảng old('roles') về ID số
                            $oldRoleIds = collect(old('roles', []))
                                ->map(function ($v) {
                                    try { return (int) Crypt::decryptString($v); }
                                    catch (\Throwable $e) { return null; } // phòng lỗi chuỗi cũ
                                })
                                ->filter()
                                ->all();

                            // Nếu có old() thì ưu tiên, ngược lại lấy theo DB ($role->assigned)
                            $precheckedRoleIds = !empty($oldRoleIds)
                                ? $oldRoleIds
                                : collect($roles)->where('assigned', true)->pluck('id')->all();

                            // 2) Chuyên mục: old('category_ids') là ID thuần
                            $oldCategoryIds = array_map('intval', (array) old('category_ids', []));
                            $precheckedCategoryIds = !empty($oldCategoryIds)
                                ? $oldCategoryIds
                                : array_map('intval', array_keys($userTermPermissionMap ?? []));

                            // 3) Trạng thái
                            $precheckedStatus = old('status', $user->status) ? 1 : 0;

                            // (tuỳ chọn) hàm tiện để biết parent có con nào đã chọn không
                            $parentHasCheckedChild = function($category) use ($precheckedCategoryIds) {
                                return $category->children
                                    ? $category->children->contains(fn($child) => in_array((int)$child->term->term_id, $precheckedCategoryIds, true))
                                    : false;
                            };
                        @endphp


                        <h5 class="mb-4">2. Phân quyền người dùng</h5>
                        <div class="form-floating form-floating-outline">
                            <small class="text-light fw-medium d-block">Trạng thái</small>
                            <div class="form-check form-switch mt-4">
                   
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="status" 
                                    name="status" 
                                    value="1"
                                    @checked($precheckedStatus)>
                                <label class="form-check-label" for="status">Hoạt động</label>
                            </div>
                        </div>

                        <div class="form-floating form-floating-outline">
                            <small class="text-light fw-medium d-block">Vai trò</small>
               
                            @foreach ($roles as $rIndex => $role)
                                <div class="form-check form-check-primary mt-4">
                                    <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="roles[]"
                                    id="roleid_{{ $rIndex }}"
                                    value="{{ Crypt::encryptString($role->id) }}"  {{-- vẫn gửi chuỗi mã hoá --}}
                                    @checked(in_array($role->id, $precheckedRoleIds, true))
                                    >
                                    <label class="form-check-label" for="roleid_{{ $rIndex }}">{{ $role->name }}</label>
                                </div>
                            @endforeach

                        </div>
                        <br />
                        <hr class="m-0">
                        <br />

                        <h5 class="mb-4">3. Chọn chuyên mục</h5>  
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="checkAllBtn">Chọn / Bỏ chọn tất cả</button>
                        </div> 
                    <div class="parent-checkbox" style="max-height: 500px; overflow-y: auto;">
                  
                        @if (isset($categoryParenChild))
                            <x-category-tree 
                                :nodes="$categoryParenChild"
                                :precheckedCategoryIds="$precheckedCategoryIds"
                                :parentHasCheckedChild="$parentHasCheckedChild"
                                :level="0"
                            />
                        @endif
                    </div>


                    </div>
                    <hr class="m-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('users.index') }}" type="button"
                                class="btn btn-outline-secondary waves-effect text-none">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit"
                                value="cc">Lưu</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection
