@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp
@extends('layouts/layoutMaster')

@section('title', __('auth.forgot_password'))

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
{{-- @vite(Theme::asset([
  'assets/js/pages-auth.js'
])) --}}
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">

      <!-- Logo -->
      <div class="card p-md-7 p-1">
        <!-- Forgot Password -->
        <x-application-logo />
        <!-- /Logo -->
        <div class="card-body mt-1">
          <h4 class="mb-1">{{ __('auth.forgot_password') }}</h4>
          <p class="mb-5">{{ __('auth.forgot_password_info') }}</p>
          <form method="POST" class="mb-5" action="{{ route('password.email') }}">
            @csrf
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input 
              id="email" 
              type="email" 
              name="email" 
              :value="old('email')" 
              placeholder="{{ __('auth.email_placeholder') }}" 
              required 
              autofocus 
              oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Vui lòng nhập email.' : 'Email không hợp lệ. Ví dụ: ten@domain.com')"
              oninput="this.setCustomValidity('')"
              />
              <label>{{ __('auth.email') }}</label>
              {{-- <x-input-error :messages="$errors->get('email')" class="mt-2" /> --}}
              <x-input-error
                :messages="$errors->has('email') ? ['Vui lòng nhập email hợp lệ.'] : []"
                class="mt-2 text-danger"
              />
            </div>
            <button class="btn btn-primary d-grid w-100">{{ __('auth.btn_sent_reset_link') }}</button>
          </form>
          <div class="text-center">
            <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
              <i class="ri-arrow-left-s-line scaleX-n1-rtl ri-20px me-1_5"></i>
              {{ __('auth.btn_back_to_login') }}
            </a>
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-forgot-password-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-forgot-password-mask-light.png" data-app-dark-img="illustrations/auth-basic-forgot-password-mask-dark.png" />
    </div>
  </div>
</div>
@endsection
