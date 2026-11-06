@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Thêm mới chuyên mục')
@section('vendor-style')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.scss',
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
'assets/vendor/libs/select2/select2.js',
'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
]))
@endsection

@section('page-script')
@vite(Theme::asset([
'assets/vendor/libs/forms-selects.js',
]))
@endsection

@section('content')
<div class="row">
  <!-- Form controls -->
  <div class="col-md-6">
    <div class="card mb-6">
      <form action="{{ route('chuyen-muc.store') }}" method="post">
        @csrf


        <h5 class="card-header">Thêm mới chuyên mục</h5>
        <div class="card-body">
          <div class="form-floating form-floating-outline mb-6">
            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Tên chuyên mục') }}" autocomplete="off" value="{{ old('name') }}" required">
            <label for="name">{{ __('Tên chuyên mục') }}</label>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
          </div>
          <div class="form-floating form-floating-outline  mb-6">
            <select id="parent_id" name="parent_id" class="select2 form-select form-select-lg" data-allow-clear="true">
              <option value>---</option>
              @foreach ($categories as $category)
              <option value="{{ $category->term_taxonomy_id }}">{{ $category->term->name }}</option>
              @endforeach
            </select>
            <label for="parent_id">Chuyên mục cha</label>
          </div>
          <div class="form-floating form-floating-outline mb-6">
            <textarea name="description" class="form-control h-px-100" id="description">{{ old('description') }}</textarea>
            <label for="description">Mô tả</label>
          </div>
          {{-- <div class="form-floating form-floating-outline mb-6 form-password-toggle">
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
                <x-theme::text-input type="password" id="password_confirmation" name="password_confirmation"
                  placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                  autocomplete="off" />
                <label for="password_confirmation">Nhập lại mật khẩu</label>
              </div>
              <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
          </div>
          <h5 class="mb-4">2. Phân quyền người dùng</h5>
          <div class="form-floating form-floating-outline">
            <small class="text-light fw-medium d-block">Trạng thái</small>
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" id="status"
                name="status"
                value="1"
                @if (old('status', $user->status) || (old('submit') === null && !$user->id)) checked="" @endif>
              <label class="form-check-label" for="status">Hoạt động</label>
            </div>
          </div>
          <div class="form-floating form-floating-outline">
            <small class="text-light fw-medium d-block">Vai trò</small>
            @foreach ($roles as $rIndex => $role)
            <div class="form-check form-check-primary mt-4">
              <input class="form-check-input" type="checkbox"
                  name="roles[]"
                  value="{{ Illuminate\Support\Facades\Crypt::encryptString($role->id) }}"
          id="roleid_{{ $rIndex }}"
          @if ($role->assigned) checked="" @endif>
          <label class="form-check-label" for="roleid_{{ $rIndex }}">{{ $role->name }}</label>
        </div>
        @endforeach
    </div>
  </div> --}}
  <hr class="m-0">
  <div class="card-body">
    <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit" value="cc">Lưu</button>
    <a href="{{ route('chuyen-muc.index') }}" type="button" class="btn btn-outline-secondary waves-effect">Hủy bỏ</a>
  </div>
  </form>
</div>
</div>
</div>
@endsection