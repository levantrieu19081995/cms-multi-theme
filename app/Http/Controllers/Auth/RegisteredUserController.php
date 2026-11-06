<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypesEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Same Blade as the login screen (switches in place); this route only sets the initial mode.
        return view('cmsfrontend::screens.pages.auth.login', ['authMode' => 'register']);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // dd('ád ddawng ky takh');
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            // 'username' => ['required', 'string', 'lowercase', 'username', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'lowercase', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::unique(User::class, 'username'),],

            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'type' => UserTypesEnum::USER->value,
            'status' => UserStatusEnum::INACTIVE->value,
        ]);

        event(new Registered($user));

        // Auth::login($user); Tawts đi để đăng ký nó không tự login vì macjd định đăgn ký là chư kích hoạt

        return redirect(route('dashboard', absolute: false));
    }
}
