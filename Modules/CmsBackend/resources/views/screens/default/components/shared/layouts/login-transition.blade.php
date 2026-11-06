{{-- Dashboard side of the "login doors open" transition and the logout → login transition. All logic and styles live in
     CmsFrontend effects/auth (dashboard-arrival.css / .js, logout-login-transition.js); this file only declares data and loads them.
     The two scripts are plain blocking scripts on purpose: pagereveal fires once, before the first render, so a deferred module would be too late. --}}
@vite('Modules/CmsFrontend/resources/assets/effects/auth/dashboard-arrival.css')
@vite('Modules/CmsFrontend/resources/assets/effects/auth/push-horizontal.css')
<script>window.cmsAuthDebug = @json((bool) config('app.debug'));</script>
<script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/auth/push-horizontal-transition.js') }}"></script>
<script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/auth/dashboard-arrival.js') }}"></script>
<script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/auth/logout-login-transition.js') }}"></script>
