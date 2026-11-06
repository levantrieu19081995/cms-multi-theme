{{-- Texts of the image panel; the theme page wraps them in its own <aside class="cms-login-visual"> (image, overlay, decoration). --}}
<div class="cms-login-panel @unless ($isRegister) is-visible @endunless" data-auth-panel="login" @if ($isRegister) aria-hidden="true" @endif>
    <p class="cms-login-kicker">{{ $brandName }}</p>
    <h2>{{ __('frontend.login.panel_heading') }}</h2>
    <p>{{ $brandUnit !== '' ? $brandUnit : __('frontend.login.panel_text') }}</p>
    @if ($canRegister)
        <div class="cms-login-panel-switch">
            <span>{{ __('frontend.login.no_account') }}</span>
            <button type="button" data-auth-switch="register" @if ($isRegister) tabindex="-1" @endif>{{ __('frontend.login.register') }}</button>
        </div>
    @endif
</div>
@if ($canRegister)
    <div class="cms-login-panel @if ($isRegister) is-visible @endif" data-auth-panel="register" @unless ($isRegister) aria-hidden="true" @endunless>
        <p class="cms-login-kicker">{{ $brandName }}</p>
        <h2>{{ __('frontend.register.panel_heading') }}</h2>
        <p>{{ __('frontend.register.panel_text') }}</p>
        <div class="cms-login-panel-switch">
            <span>{{ __('frontend.register.have_account') }}</span>
            <button type="button" data-auth-switch="login" @unless ($isRegister) tabindex="-1" @endunless>{{ __('frontend.register.login') }}</button>
        </div>
    </div>
@endif
