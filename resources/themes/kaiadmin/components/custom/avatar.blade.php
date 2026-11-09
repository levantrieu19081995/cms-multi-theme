@props(['user' => Auth::user()])

<img src="{{ $user && isset($user->profile_photo_url) && $user->profile_photo_url ? $user->profile_photo_url : Avatar::create((string) ($user->name ?: ($user->username ?? '') ?: ($user->email ?? '') ?: '?'))->toBase64() }}" {!! $attributes !!}>
