@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reset Password Basic - Pages')

@section('vendor-style')
@vite(Theme::asset([
  'assets/vendor/libs/@form-validation/form-validation.scss'
]))
@endsection

@section('page-style')
@vite(Theme::asset([
  'assets/vendor/scss/pages/page-auth.scss'
]))
@endsection

@section('vendor-script')
@vite(Theme::asset([
  'assets/vendor/libs/@form-validation/popular.js',
  'assets/vendor/libs/@form-validation/bootstrap5.js',
  'assets/vendor/libs/@form-validation/auto-focus.js'
]))
@endsection

@section('page-script')
@vite(Theme::asset([
  'assets/js/pages-auth.js'
]))
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">

      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <x-application-logo />
        <!-- /Logo -->
        <!-- Reset Password -->
        <div class="card-body">
          <h4 class="mb-1">Reset Password 🔒</h4>
          <p class="mb-5">Your new password must be different from previously used passwords</p>
          <form method="POST" class="mb-5" action="{{ route('password.store') }}">
            @csrf
            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-5 form-password-toggle">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <x-theme::text-input readonly id="email" type="email" name="email" :value="old('email', $request->email)" required autocomplete="username" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                  <label for="password">Email</label>
                </div>
              </div>
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mb-5 form-password-toggle">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <x-theme::text-input id="password" type="password" name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password"
                    autofocus/>
                  <label for="password">New Password</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
              </div>
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mb-5 form-password-toggle">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <x-theme::text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                  <label for="confirm-password">Confirm Password</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
              </div>
              <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            <button class="btn btn-primary d-grid w-100 mb-5">
              Set new password
            </button>
            <div class="text-center">
              <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
                <i class="ri-arrow-left-s-line scaleX-n1-rtl ri-20px me-1_5"></i>
                Back to login
              </a>
            </div>
          </form>
        </div>
      </div>
      <!-- /Reset Password -->
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-reset-password-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-reset-password-mask-light.png" data-app-dark-img="illustrations/auth-basic-reset-password-mask-dark.png" />
    </div>
  </div>
</div>
@endsection
