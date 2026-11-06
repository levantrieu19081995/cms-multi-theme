@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('admin.menu.quan_ly_tap_tin'))

@section('page-style')
    <style>{!! \File::get(module_path('CmsBackend', 'resources/assets/css/file-manager.css')) !!}</style>
@endsection

@section('content')
    <div class="cms-file-manager-page">
        <iframe
            class="cms-file-manager-frame"
            src="{{ url('/laravel-filemanager?mode=include&type=image&cms=1') }}"
            title="{{ __('admin.ui.thu_vien_tap_tin_khuyen_nong_can_tho') }}"
            frameborder="0"
        ></iframe>
    </div>
@endsection
