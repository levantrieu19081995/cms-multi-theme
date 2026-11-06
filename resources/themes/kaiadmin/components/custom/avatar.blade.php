@props(['user' => Auth::user()])

<img src="{{ $user && isset($user->profile_photo_url) && $user->profile_photo_url ? $user->profile_photo_url : Avatar::create($user->name)->toBase64() }}" {!! $attributes !!}>
