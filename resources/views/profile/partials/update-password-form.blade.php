<div class="card">
  <h5 class="card-header">{{ __('Mật khẩu') }}</h5>
  <div class="card-body">
    <small>{{ __('Đảm bảo tài khoản của bạn đang sử dụng mật khẩu dài, ngẫu nhiên để giữ an toàn.') }}</small>
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div class="form-floating form-floating-outline mb-6">
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
            <label for="update_password_current_password">{{ __('Mật khẩu hiện tại') }}</label>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="form-floating form-floating-outline mb-6">
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
            <label for="update_password_password">{{ __('Mật khẩu mới') }}</label>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="form-floating form-floating-outline mb-6">
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            <label for="update_password_password_confirmation">{{ __('Nhập lại mật khẩu mới') }}</label>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            {{-- @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Cập nhật mật khẩu thành công !') }}</p>
            @endif --}}
        </div>
    </form>
  </div>
</div>
