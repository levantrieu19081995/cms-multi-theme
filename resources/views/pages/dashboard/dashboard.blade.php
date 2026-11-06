@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Dashboard - Analytics')
@section('vendor-style')
{{-- @vite(['resources/assets/vendor/?.css']) --}}
@endsection

@section('page-style')
<!-- Page -->
{{-- @vite(['resources/assets/vendor/scss/?.css']) --}}
@endsection

@section('vendor-script')
{{-- @vite(['resources/assets/vendor/?.js']) --}}
@endsection

@section('page-script')
{{-- @vite(['resources/assets/js/?.js']) --}}
@endsection

@section('content')
<h1>Dashboard</h1>
@endsection
