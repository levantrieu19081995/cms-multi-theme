{{-- Login pane (right column). Same form for every theme; posted with fetch by auth/login.js. --}}
<section class="cms-login-pane cms-login-pane--login" data-auth-pane="login" aria-labelledby="cms-login-title"
         @if ($isRegister) aria-hidden="true" inert @endif>
    <form method="POST" action="{{ route('cms.login.store') }}" class="cms-login-form" data-login-form
          data-label-submitting="{{ __('frontend.login.submitting') }}"
          data-label-error-network="{{ __('frontend.login.error_network') }}"
          data-label-error-generic="{{ __('frontend.login.error_generic') }}">
        @csrf
        <a class="cms-login-brand" href="{{ $homeUrl }}" aria-label="{{ __('frontend.login.back_home') }}">
            <img src="{{ asset($brandLogo) }}" alt="{{ $brandName }}">
            <span>{{ $brandName }}</span>
        </a>
        <p class="cms-login-lead">{{ __('frontend.login.welcome_back') }}</p>
        <h1 class="cms-login-title" id="cms-login-title">{{ __('frontend.login.title') }}</h1>
        <p class="cms-login-description">{{ __('frontend.login.description') }}</p>

        @include('cmsfrontend::auth.shared.alerts')

        <div class="cms-login-field">
            <label for="cms-login">{{ __('frontend.login.username') }}</label>
            <input class="cms-login-input @if (! $isRegister) @error('login') is-error @enderror @endif" id="cms-login" name="login" type="text" value="{{ $isRegister ? '' : old('login') }}" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.login.username') }}" @endif autocomplete="username" required @unless ($isRegister) autofocus @endunless @if (! $isRegister) @error('login') aria-invalid="true" @enderror @endif>
        </div>

        <div class="cms-login-field">
            <label for="cms-password">{{ __('frontend.login.password') }}</label>
            <div class="cms-login-password">
                <input class="cms-login-input @if (! $isRegister) @error('password') is-error @enderror @endif" id="cms-password" name="password" type="password" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.login.password') }}" @endif autocomplete="current-password" required @if (! $isRegister) @error('password') aria-invalid="true" @enderror @endif>
                <button class="cms-password-toggle" type="button" data-toggle-target="cms-password"
                        data-label-show="{{ __('frontend.login.show_password') }}" data-label-hide="{{ __('frontend.login.hide_password') }}"
                        aria-label="{{ __('frontend.login.show_password') }}" aria-pressed="false">
                    @include('cmsfrontend::auth.shared.eye-icon')
                </button>
            </div>
        </div>

        @if (Route::has('password.request'))
            <a class="cms-login-forgot" href="{{ route('password.request') }}">{{ __('frontend.login.forgot_password') }}</a>
        @endif

        <button class="cms-login-submit" type="submit">
            <span>{{ __('frontend.login.submit') }}</span>
        </button>

        <p class="cms-login-switch">
            {{ __('frontend.login.no_account') }}
            @if ($canRegister)
                <button class="cms-login-switch-link" type="button" data-auth-switch="register">{{ __('frontend.login.register') }}</button>
            @else
                <span>{{ __('frontend.login.contact_admin') }}</span>
            @endif
        </p>
    </form>
</section>
