@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', ($role->id ? 'Cập nhật' : 'Thêm mới') . ' vai trò')
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
  'assets/js/forms-selects.js',
]))
@endsection

@section('content')
<div class="row">
  <!-- Form controls -->
  <div class="col-md-6">
    <div class="card mb-6">
      <form action="{{ $role->id ? route('roles.update', $role) : route('roles.store') }}" method="post">
        @csrf
        @if ($role->id)
          @method('put')
        @endif

        <h5 class="card-header">{{ $role->id ? 'Cập nhật' : 'Thêm mới' }} vai trò</h5>
        <div class="card-body">
          <h5 class="mb-4">1. Thông tin vai trò</h5>
          <div class="form-floating form-floating-outline mb-6">
            <input type="text" class="form-control" id="name" name="name" placeholder="Họ và tên" autocomplete="off" value="{{ old('name') ?? $role->name }}">
            <label for="name">Tên vai trò</label>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
          </div>
          <h5 class="mb-4">2. Phân quyền vai trò</h5>
          <div class="form-floating form-floating-outline">
            <small class="text-light fw-medium d-block">Vai trò</small>
            @foreach ($permissions as $pIndex => $permission)
            <div class="form-check form-check-primary mt-4">
              <input class="form-check-input" type="checkbox"
                  name="permissions[]"
                  value="{{ Illuminate\Support\Facades\Crypt::encryptString($permission->id) }}"
                  id="permissionid_{{ $pIndex }}"
                  @if ($permission->assigned) checked="" @endif>
              <label class="form-check-label" for="permissionid_{{ $pIndex }}">{{ $permission->name }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <hr class="m-0">
        <div class="card-body">
          <button type="submit" class="btn btn-primary waves-effect waves-light" name="submit" value="cc">Lưu</button>
          <a href="{{ route('roles.index') }}" type="button" class="btn btn-outline-secondary waves-effect">Hủy bỏ</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
