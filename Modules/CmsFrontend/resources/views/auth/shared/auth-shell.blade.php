{{-- Common Login/Register document. Theme pages (screens/<theme>/pages/auth/login.blade.php) @extend this and provide:
       auth-assets  the theme's @vite auth.css (colour/font/logo tokens: --auth-*)
       auth-frame-attrs  optional extra attributes for the frame (e.g. data-auth-layout, data-auth-doors, an inline --auth-* style)
       auth-frame   the layout inside .cms-login-frame (panes, image panel, curtains), built from the auth.shared partials
     Variables come from Modules\CmsFrontend\Support\AuthPageContext (extract()ed by the theme page). --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitles[$authMode] }}</title>
    <meta name="description" content="{{ $isRegister ? __('frontend.register.meta_description', ['name' => $brandName]) : __('frontend.login.meta_description', ['name' => $brandName]) }}">
    <link rel="icon" href="{{ \Modules\CmsBackend\Support\SiteLogo::urlOr($brandFavicon) }}">
    @vite('Modules/CmsFrontend/resources/assets/auth/login-base.css')
    @yield('auth-assets')
    @vite('Modules/CmsFrontend/resources/assets/effects/auth/style.css')
    @vite('Modules/CmsFrontend/resources/assets/effects/auth/push-horizontal.css')
    {{-- Logout arrival (doors close over the page just left): effects/auth/login-arrival.js. A plain blocking script on purpose: pagereveal fires once, before the first render. --}}
    <script>window.cmsAuthDebug = @json((bool) config('app.debug'));</script>
    <script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/auth/push-horizontal-transition.js') }}"></script>
    <script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/auth/login-arrival.js') }}"></script>
</head>
<body class="cms-public cms-login-page" data-auth-theme="{{ $loginTheme }}">
    <div class="cms-login-page-wrap">
        <main class="cms-login-frame" data-auth-mode="{{ $authMode }}" @if (config('app.debug')) data-debug @endif
              data-login-url="{{ route('cms.login') }}"
              @if (! empty($authTransition)) data-auth-transition="{{ $authTransition }}" @endif
              @if ($canRegister) data-register-url="{{ route('register') }}" @endif
              data-title-login="{{ $pageTitles['login'] }}"
              data-title-register="{{ $pageTitles['register'] }}"
              @yield('auth-frame-attrs')>
            @yield('auth-frame')
        </main>
    </div>
    @vite('Modules/CmsFrontend/resources/assets/auth/login.js')
    @include('cmsbackend::screens.components.developer-shortcut', ['selector' => 'a.cms-login-brand'])
</body>
</html>
