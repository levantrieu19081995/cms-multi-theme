@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Forgot Password Basic - Pages')

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
          <h4 class="mb-1">Confirm Password? 🔒</h4>
          <p class="mb-5">This is a secure area of the application. Please confirm your password before continuing.</p>
          <form method="POST" class="mb-5" action="{{ route('password.confirm') }}">
            @csrf
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password" />
              <label>Password</label>
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <button class="btn btn-primary d-grid w-100">Confirm</button>
          </form>
        </div>
      </div>
      <!-- /Forgot Password -->
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-forgot-password-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-forgot-password-mask-light.png" data-app-dark-img="illustrations/auth-basic-forgot-password-mask-dark.png" />
    </div>
  </div>
</div>
@endsection
