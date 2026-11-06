@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', __('auth.btn_sign_in'))

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

      <!-- Login -->
      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <x-application-logo />
        <!-- /Logo -->

        <div class="card-body mt-1">
          {{-- <h4 class="mb-1 text-center">{{ __('auth.welcome_to') }} {{config('variables.templateName')}}! 👋</h4> --}}
          <h4 class="mb-1 text-center">Đăng nhập</h4>
          <p class="mb-5">{{ __('auth.please_sign_in') }}</p>

          <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input 
                id="email" type="text" name="login" :value="old('email')" 
                placeholder="{{ __('Tên đăng nhập hoặc Email') }}"
                {{-- required  --}}
                {{-- autofocus  --}}
                autocomplete="username"  
                oninvalid="this.setCustomValidity('Vui lòng nhập tên đăng nhập hoặc email.')"
                oninput="this.setCustomValidity('')"
                />
              <label for="login">{{ __('Tên đăng nhập hoặc Email') }}</label>
              <x-input-error :messages="$errors->get('login')" class="mt-2" />
            </div>
            <div class="mb-5">
              <div class="form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <x-theme::text-input type="password" id="password" class="form-control" 
                    name="password" 
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                    aria-describedby="password"
                    oninvalid="this.setCustomValidity('Vui lòng nhập mật khẩu.')"
                    oninput="this.setCustomValidity('')"
                    />
                    <label for="password">{{ __('auth.password') }}</label>
                  </div>
                  <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                </div>
                <div class="valid-feedback"> Looks good! </div>
                <div class="invalid-feedback"> Please Enter Your DOB </div>
              </div>
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mb-5 d-flex justify-content-between mt-5">
              {{-- <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                <label class="form-check-label" for="remember-me">
                  Remember Me
                </label>
              </div> --}}
              @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="float-end mb-1 mt-2">
                <span>{{ __('auth.forgot_password') }}</span>
              </a>
              @endif
            </div>
            <div class="mb-5">
              <button class="btn btn-primary d-grid w-100" type="submit">{{ __('auth.btn_sign_in') }}</button>
            </div>
          </form>

          @if (Route::has('register'))
          <p class="text-center">
            <span>{{ __('auth.dont_have_an_account') }}</span>
            <a href="{{ route('register') }}">
              <span>{{ __('auth.create_an_account') }}</span>
            </a>
          </p>
          @endif

          {{-- <div class="divider my-5">
            <div class="divider-text">{{ __('auth.or') }}</div>
          </div> --}}

          {{-- <div class="d-flex justify-content-center gap-2"> --}}
            {{-- <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook">
              <i class="tf-icons ri-facebook-fill"></i>
            </a> --}}

            {{-- <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter">
              <i class="tf-icons ri-twitter-fill"></i>
            </a> --}}

            {{-- <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github">
              <i class="tf-icons ri-github-fill"></i>
            </a> --}}

            {{-- <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">
              <i class="tf-icons ri-google-fill"></i>
            </a> --}}
          {{-- </div> --}}
        </div>
      </div>
      <!-- /Login -->
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-login-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-login-mask-light.png" data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
    </div>
  </div>
</div>
@endsection