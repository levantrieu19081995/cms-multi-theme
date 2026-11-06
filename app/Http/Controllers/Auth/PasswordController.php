<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Facades\Toastr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    // public function update(Request $request): RedirectResponse
    // {
    //     dd("cap nhat mat khau owr thong tin nguwoif dung");
    //     $validated = $request->validateWithBag('updatePassword', [
    //         'current_password' => ['required', 'current_password'],
    //         'password' => ['required', Password::defaults(), 'confirmed'],
    //     ]);

    //     $request->user()->update([
    //         'password' => Hash::make($validated['password']),
    //     ]);

    //     //  Thêm thông báo
    //     Toastr::success('Cập nhật hồ sơ thành công');
    //     return back()->with('status', 'password-updated');
    // }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag(
            'updatePassword',
            [
                'current_password' => ['required', 'current_password'],
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols(),
                ],
            ],
            [
                'password.required' => 'Vui lòng nhập mật khẩu mới.',
                'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
                'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            ]
        );



        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user = $request->user();
        //  Xóa toàn bộ session của user
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        Auth::logout();

        Toastr::success('Cập nhật mật khẩu thành công');

        return back()->with('status', 'password-updated');
    }
}
