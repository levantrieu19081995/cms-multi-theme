{{-- Register pane (left column). Needs the variables from Modules\CmsFrontend\Support\AuthPageContext (extracted by the theme page). Same form for every theme. --}}
@if ($canRegister)
    <section class="cms-login-pane cms-login-pane--register" data-auth-pane="register" aria-labelledby="cms-register-title"
             @unless ($isRegister) aria-hidden="true" inert @endunless>
        <form method="POST" action="{{ route('register') }}" class="cms-login-form cms-register-form"
              data-label-submitting="{{ __('frontend.register.submitting') }}">
            @csrf
            <a class="cms-login-brand" href="{{ $homeUrl }}" aria-label="{{ __('frontend.login.back_home') }}">
                <img src="{{ asset($brandLogo) }}" alt="{{ $brandName }}">
                <span>{{ $brandName }}</span>
            </a>
            <p class="cms-login-lead">{{ __('frontend.register.lead') }}</p>
            <h1 class="cms-login-title" id="cms-register-title">{{ __('frontend.register.title') }}</h1>
            <p class="cms-login-description">{{ __('frontend.register.description') }}</p>

            <div class="cms-login-field">
                <label for="cms-register-name">{{ __('frontend.register.name') }}</label>
                <input class="cms-login-input @error('name') is-error @enderror" id="cms-register-name" name="name" type="text" value="{{ old('name') }}" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.register.name') }}" @endif autocomplete="name" required @if ($isRegister) autofocus @endif @error('name') aria-invalid="true" aria-describedby="cms-register-name-error" @enderror>
                @error('name') <p class="cms-login-field-error" id="cms-register-name-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="cms-login-field">
                <label for="cms-register-username">{{ __('frontend.register.username') }}</label>
                <input class="cms-login-input @error('username') is-error @enderror" id="cms-register-username" name="username" type="text" value="{{ old('username') }}" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.register.username') }}" @endif autocomplete="username" required @error('username') aria-invalid="true" aria-describedby="cms-register-username-error" @enderror>
                @error('username') <p class="cms-login-field-error" id="cms-register-username-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="cms-login-field">
                <label for="cms-register-email">{{ __('frontend.register.email') }}</label>
                <input class="cms-login-input @error('email') is-error @enderror" id="cms-register-email" name="email" type="email" value="{{ old('email') }}" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.register.email') }}" @endif autocomplete="email" @error('email') aria-invalid="true" aria-describedby="cms-register-email-error" @enderror>
                @error('email') <p class="cms-login-field-error" id="cms-register-email-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="cms-login-field">
                <label for="cms-register-password">{{ __('frontend.register.password') }}</label>
                <div class="cms-login-password">
                    <input class="cms-login-input @error('password') is-error @enderror" id="cms-register-password" name="password" type="password" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.register.password') }}" @endif autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="cms-register-password-error" @enderror>
                    <button class="cms-password-toggle" type="button" data-toggle-target="cms-register-password"
                            data-label-show="{{ __('frontend.login.show_password') }}" data-label-hide="{{ __('frontend.login.hide_password') }}"
                            aria-label="{{ __('frontend.login.show_password') }}" aria-pressed="false">
                        @include('cmsfrontend::auth.shared.eye-icon')
                    </button>
                </div>
                @error('password') <p class="cms-login-field-error" id="cms-register-password-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="cms-login-field">
                <label for="cms-register-password-confirmation">{{ __('frontend.register.password_confirmation') }}</label>
                <div class="cms-login-password">
                    <input class="cms-login-input" id="cms-register-password-confirmation" name="password_confirmation" type="password" @if (! empty($authPlaceholders)) placeholder="{{ __('frontend.register.password_confirmation') }}" @endif autocomplete="new-password" required>
                    <button class="cms-password-toggle" type="button" data-toggle-target="cms-register-password-confirmation"
                            data-label-show="{{ __('frontend.register.show_confirm_password') }}" data-label-hide="{{ __('frontend.register.hide_confirm_password') }}"
                            aria-label="{{ __('frontend.register.show_confirm_password') }}" aria-pressed="false">
                        @include('cmsfrontend::auth.shared.eye-icon')
                    </button>
                </div>
            </div>

            <label class="cms-login-terms">
                <input type="checkbox" name="terms" value="1" required>
                <span>{{ __('frontend.register.terms') }}</span>
            </label>

            <button class="cms-login-submit" type="submit">
                <span>{{ __('frontend.register.submit') }}</span>
            </button>

            <p class="cms-login-switch">
                {{ __('frontend.register.have_account') }}
                <button class="cms-login-switch-link" type="button" data-auth-switch="login">{{ __('frontend.register.login') }}</button>
            </p>
        </form>
    </section>
@endif
