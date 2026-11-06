<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rules;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    // public function rules(): array
    // {
    //     return [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    //           'username' => ['required', 'string', 'lowercase','regex:/^[a-z0-9._-]+$/', 'max:255', 'unique:'.User::class],
    //         'password' => ['required', 'confirmed', Rules\Password::defaults()],
    //     ];
    // }

    // public function attributes(): array
    // {
    //     return [
    //       'name' => 'Họ và tên',
    //        'username' => 'Tên đăng nhập',
    //       'email' => 'Email',
    //       'password' => 'Mật khẩu'
    //     ];
    // }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                // 'unique:users,email',
            ],
            'username' => [
                'required',
                'string',
                'lowercase',
                'regex:/^[a-z0-9._-]+$/',
                'max:255',
                // 'unique:users,username',
            ],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                // 👇 CHỐT 4 ĐIỀU KIỆN
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.regex' =>
            'Mật khẩu phải gồm chữ thường, chữ in hoa, số và ký tự đặc biệt.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ và tên',
            'username' => 'Tên đăng nhập',
            'email' => 'Email',
            'password' => 'Mật khẩu',
        ];
    }
}
