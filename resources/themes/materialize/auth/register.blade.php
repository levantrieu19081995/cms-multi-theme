@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', __('auth.title_register'))

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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const formFields = [
      document.getElementById('name'),
      document.getElementById('username'),
      document.getElementById('email'),
      document.getElementById('password'),
      document.getElementsByName('password_confirmation')[0]
    ];

    const checkboxContainer = document.getElementById('terms-container');
    const checkbox = document.getElementById('terms-conditions');
    const registerBtn = document.getElementById('register-btn');

    // Ẩn nút đăng ký ban đầu
    registerBtn.disabled = true;

    function checkFormFilled() {
      const allFilled = formFields.every(input => input.value.trim() !== '');
      checkboxContainer.style.display = allFilled ? 'block' : 'none';

      // Nếu checkbox chưa được hiển thị => disable luôn nút đăng ký
      if (!allFilled) {
        checkbox.checked = false;
        registerBtn.disabled = true;
      }
    }

    // Gán sự kiện cho từng ô input
    formFields.forEach(input => {
      input.addEventListener('input', checkFormFilled);
    });

    // Khi checkbox thay đổi
    checkbox.addEventListener('change', function () {
      registerBtn.disabled = !this.checked;
    });

    // Kiểm tra trạng thái ban đầu (trường hợp đã nhập trước đó do old())
    checkFormFilled();
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('terms-conditions');
    const registerBtn = document.getElementById('register-btn');

    // Gán trạng thái ban đầu
    registerBtn.disabled = !checkbox.checked;

    // Khi checkbox thay đổi, cập nhật trạng thái nút
    checkbox.addEventListener('change', function () {
      registerBtn.disabled = !this.checked;
    });
  });
</script>
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">

      <!-- Register Card -->
      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <x-application-logo />
        <!-- /Logo -->
        <div class="card-body mt-1">
          <h4 class="mb-1  text-center" >{{ __('auth.title_register') }}</h4>
          {{-- <p class="mb-5">Make your app management easy and fun!</p> --}}

          <form method="POST" class="mb-5" action="{{ route('register') }}">
              @csrf
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input id="name" class="block mt-1 w-full" type="text" id="name" name="name"
                :value="old('name')"
                placeholder="{{ __('auth.name_placeholder') }}"
                required autofocus autocomplete="name" />
              <label for="name">{{ __('auth.name') }}</label>
              <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input id="username" class="block mt-1 w-full" type="text" name="username"
                :value="old('username')"
                placeholder="{{ __('Tên đăng nhập') }}"
                required autocomplete="username" />
              <label for="username">{{ __('Tên đăng nhập') }}</label>
              <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <x-theme::text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')"
                placeholder="{{ __('auth.email') }}"
                required autocomplete="username" />
              <label for="username">{{ __('auth.email') }}</label>
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mb-5 form-password-toggle">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <x-theme::text-input type="password" id="password" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password"

                    required autocomplete="new-password" />
                  <label for="password">{{ __('auth.password') }}</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
              </div>
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mb-5 form-password-toggle">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <x-theme::text-input type="password" id="password" class="form-control" name="password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password"

                    required autocomplete="new-password" />
                  <label for="password">{{ __('auth.password_confirm') }}</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
              </div>
              <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="mb-5"  >
              <div class="form-check mt-2">
                <small>Bạn cần nhập đầy đủ nội dung để thực hiện đăng ký tài khoản</small>
              </div>
            </div>
            <div class="mb-5" id="terms-container" style="display: none;">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms">
                <label class="form-check-label" for="terms-conditions">
                  {{ __('auth.i_agree_to') }}
                  <a  href="javascript:void(0);">{{ __('auth.privacy_policy&terms') }}</a>
                </label>
              </div>
            </div>
            {{-- <button class="btn btn-primary d-grid w-100">
              {{ __('auth.btn_sign_up') }}
            </button> --}}
            <button id="register-btn" class="btn btn-primary d-grid w-100 mt-3" disabled>
              {{ __('auth.btn_sign_up') }}
            </button>

          </form>

          <p class="text-center">
            <span>{{ __('auth.already_have_an_account') }}</span>
            <a href="{{ route('login') }}">
              <span>{{ __('auth.btn_sign_in') }}</span>
            </a>
          </p>

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
            </a>
          </div> --}}
        </div>
      </div>
      <!-- Register Card -->
      {{-- <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-register-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-register-mask-light.png" data-app-dark-img="illustrations/auth-basic-register-mask-dark.png" /> --}}
    </div>
  </div>
</div>
@endsection
