@php
    $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Quản lý tập tin')
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
    {{ Breadcrumbs::render('file.index', '') }}
    <iframe src="/laravel-filemanager?mode=include&type=image" frameborder="0"
        style="width: 100%; height: 100%; min-height: 500px"></iframe>
@endsection
