@extends('cmsfrontend::auth.shared.auth-shell')
@php extract(\Modules\CmsFrontend\Support\AuthPageContext::make($authMode ?? null, $errors)); @endphp

@section('auth-assets')
    @vite('Modules/CmsFrontend/resources/assets/css/screens/nam-binh-duong/auth.css')
@endsection

@section('auth-frame')
    @include('cmsfrontend::auth.shared.register-form')
    @include('cmsfrontend::auth.shared.login-form')

    {{-- Image panel: slides between the two columns (position and motion come from login-base.css / effects/auth). --}}
    <aside class="cms-login-visual">
        <img class="cms-login-visual-image" src="{{ asset('assets/img/auth/meeting-login-background.jpg') }}" alt="" fetchpriority="high">
        <div class="cms-login-visual-overlay"></div>
        <div class="cms-login-visual-content">
            @include('cmsfrontend::auth.shared.visual-panels')
        </div>
    </aside>

    @include('cmsfrontend::auth.shared.curtains')
@endsection
