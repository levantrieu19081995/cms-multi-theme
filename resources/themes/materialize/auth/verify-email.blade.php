@php
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', __('auth.verify_your_email'))

@section('page-style')
<!-- Page -->
@vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">

      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <x-application-logo />
        <!-- /Logo -->

        <!-- Verify Email -->
        <div class="card-body mt-1">
          <h4 class="mb-1">{{ __('auth.verify_your_email') }} ✉️</h4>
          <p class="text-start mb-0">
            {{ __('auth.verify_your_email_notify') }}
          </p>
          <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div>
              <button type="submit" class="btn btn-primary w-100 my-5" href="{{url('/')}}">
                {{ __('auth.btn_resend_verification_email') }}
              </button>
            </div>
          </form>
          @if (session('status') == 'verification-link-sent')
              <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                  {{ __('auth.verify_your_email_notify_2') }}
              </div>
          @endif
        </div>
      </div>
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-login-mask-'.$configData['style'].'.png') }}" class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-login-mask-light.png" data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
      <!-- /Verify Email -->
    </div>
  </div>
</div>
@endsection
