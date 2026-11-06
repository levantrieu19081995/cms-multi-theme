<div class="card">
  <h5 class="card-header">{{ __('Thông tin cá nhân') }}</h5>
  <div class="card-body">
    <small>{{ __("Cập nhật thông tin hồ sơ và địa chỉ email của tài khoản của bạn.") }}</small>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="form-floating form-floating-outline mb-6">
          <input readonly  id="username" name="username" type="text" class="form-control" value="{{ old('username', $user->username) }}" required autofocus autocomplete="username" />
          <label for="name">{{ __('Tài khoản') }}</label>
          <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div class="form-floating form-floating-outline mb-6">
          <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
          <label for="name">{{ __('Tên') }}</label>
          <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
 
        <div class="form-floating form-floating-outline mb-6">
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <label for="email">{{ __('Email') }}</label>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

        </div>
    </form>
  </div>
</div>
