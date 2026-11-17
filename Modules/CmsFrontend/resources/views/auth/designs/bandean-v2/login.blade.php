@extends('cmsfrontend::auth.shared.auth-shell')
@php extract(\Modules\CmsFrontend\Support\AuthPageContext::make($authMode ?? null, $errors)); @endphp

@section('auth-assets')
    @vite('Modules/CmsFrontend/resources/views/auth/designs/bandean-v2/auth.css')
    {{-- Hình của bộ này nằm trong public/: gọi bằng asset() để đúng khi site chạy dưới một thư mục con (CSS chỉ đọc biến). --}}
    <style>
        .cms-public.cms-login-page {
            --bd2-bg-image: url('{{ asset('assets/img/auth/bandean-v2/background.jpg') }}');
        }
    </style>
@endsection

@section('auth-frame')
    @include('cmsfrontend::auth.shared.register-form')
    @include('cmsfrontend::auth.shared.login-form')

    {{-- Nửa kính mờ: trượt giữa hai cột như ban-de-an, hai minh họa chồng nhau và hiện theo data-auth-mode (CSS). Chữ của hai chế độ: visual-panels. --}}
    <aside class="cms-login-visual">
        <img class="bd2-art bd2-art--login" src="{{ asset('assets/img/auth/bandean-v2/illustration-login.webp') }}" alt="" width="900" height="675" fetchpriority="high">
        @if ($canRegister)
            <img class="bd2-art bd2-art--register" src="{{ asset('assets/img/auth/bandean-v2/illustration-register.webp') }}" alt="" width="900" height="675" loading="lazy">
        @endif
        <div class="cms-login-visual-content">
            @include('cmsfrontend::auth.shared.visual-panels')
        </div>
    </aside>

    @include('cmsfrontend::auth.shared.curtains')
@endsection
