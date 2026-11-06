@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Dashboard - Analytics')
@section('vendor-style')
{{-- @vite(Theme::asset(['resources/assets/vendor/?.css'])) --}}
@endsection

@section('page-style')
<!-- Page -->
{{-- @vite(Theme::asset(['resources/assets/vendor/scss/?.css'])) --}}
@endsection

@section('vendor-script')
{{-- @vite(Theme::asset(['resources/assets/vendor/?.js'])) --}}
@endsection

@section('page-script')
{{-- @vite(Theme::asset(['resources/assets/js/?.js'])) --}}
@endsection

@section('content')
<div class="row">
  <div class="col-lg-12 mb-6">
    @include('profile.partials.update-profile-information-form')
  </div>
</div>

<div class="row">
  <div class="col-lg-12 mb-6">
    @include('profile.partials.update-password-form')
  </div>
</div>


<div class="row">
  <div class="col-lg-12 mb-6">
    @include('profile.partials.delete-user-form')
  </div>
</div>
@endsection

