@extends('cmsfrontend::auth.shared.auth-shell')
@php
    extract(\Modules\CmsFrontend\Support\AuthPageContext::make($authMode ?? null, $errors));
    $authPlaceholders = true; // inputs show their label as placeholder (labels stay for screen readers)
@endphp

@section('auth-assets')
    @vite('Modules/CmsFrontend/resources/assets/css/screens/default/auth.css')
@endsection

{{-- One centred card over a full-screen photo: no image panel. Page transition = push-horizontal (screens.themes.default.login.transition, effects/auth). --}}
@section('auth-frame-attrs')
data-auth-layout="card" style="--auth-background: url('{{ asset('assets/img/khuyen-nong-can-tho/admin/packground.png') }}')"
@endsection

@section('auth-frame')
    @include('cmsfrontend::auth.shared.register-form')
    @include('cmsfrontend::auth.shared.login-form')
@endsection
