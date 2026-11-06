<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use App\Models\User;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Http\RedirectResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    //     if (Auth::attempt([
    //         $loginType => $request->login,
    //         'password' => $request->password,
    //     ], $request->boolean('remember'))) {
    //         $request->authenticate();
    //         $request->session()->regenerate();

    //         return redirect()->intended('/');
    //     }

    //     return back()->withErrors([
    //         'login' => 'Thông tin đăng nhập không chính xác.',
    //     ]);
    // }

    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    //     // Lấy user từ DB trước khi attempt để kiểm tra status
    //     $user = \App\Models\User::where($loginType, $request->login)->first();

    //     // Nếu không tồn tại user hoặc password sai → trả về lỗi đăng nhập
    //     if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
    //         return back()->withErrors([
    //             'login' => 'Thông tin đăng nhập không chính xác.',
    //         ]);
    //     }

    //     // Nếu user có status = 0 → không cho đăng nhập
    //     if ($user->status == 0) {
    //         return back()->withErrors([
    //             'login' => 'Tài khoản của bạn đang bị khóa hoặc chưa được kích hoạt.',
    //         ]);
    //     }

    //     // Đăng nhập thành công
    //     Auth::login($user, $request->boolean('remember'));
    //     $request->session()->regenerate();

    //     return redirect()->intended('/');
    // }


    public function store(LoginRequest $request): RedirectResponse
    {
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        /** @var User|null $user */
        $user = User::where($loginType, $request->login)->first();

        // 1️⃣ Không tồn tại user
        if (!$user) {
            return back()->withErrors([
                'login' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        // 2️⃣ Nếu đang bị khóa tạm
        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $seconds = now()->diffInSeconds($user->locked_until);
            $minutes = intdiv($seconds, 60);
            $remainSeconds = $seconds % 60;

            $message = 'Tài khoản bị tạm khóa. Vui lòng thử lại sau ';
            if ($minutes > 0) {
                $message .= "{$minutes} phút {$remainSeconds} giây.";
            } else {
                $message .= "{$remainSeconds} giây.";
            }

            return back()->withErrors([
                'login' => $message,
            ]);
        }

        //  Sai mật khẩu
        if (!Hash::check($request->password, $user->password)) {
            $user->failed_login_attempts++;

            // Nếu đủ 5 lần → khóa 2 phút
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = now()->addMinutes(5);
                $user->failed_login_attempts = 0; // reset để lần sau đếm lại
            }

            $user->save();

            return back()->withErrors([
                'login' => 'Thông tin đăng nhập không chính xác.',
            ]);
        }

        //  Đúng mật khẩu → đăng nhập bình thường
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }



    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
