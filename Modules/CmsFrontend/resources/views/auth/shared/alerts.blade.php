{{-- Login notices: session status and the error box (filled by the server on a normal post, by auth/login.js after a failed fetch). --}}
@if (session('status'))
    <div class="cms-login-notice is-success" role="status">{{ session('status') }}</div>
@endif

{{-- Always rendered: server errors fill it on a normal post, login.js fills it after a failed fetch. --}}
<div class="cms-login-notice is-error" role="alert" data-login-error @if ($isRegister || ! $errors->any()) hidden @endif>{{ $isRegister ? '' : $loginError }}</div>
