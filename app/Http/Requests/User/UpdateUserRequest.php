<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
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
  //   return [
  //     'name' => ['required', 'string', 'max:255'],
  //     'email' => [
  //       'nullable',
  //       'string',
  //       'lowercase',
  //       'email',
  //       'max:255',
  //       Rule::unique('users')->ignore($this->route('user'), 'id')
  //     ],
  //    'username' => [
  //   'required',
  //   'string',
  //   'lowercase',
  //   'max:255',
  //   'regex:/^[a-z0-9._-]+$/', // chỉ cho phép chữ thường, số, ., _, -
  //   Rule::unique('users')->ignore($this->route('user'), 'id'),
  //   ],
  //     'password' => [
  //       'nullable',
  //       'confirmed', 
  //       Rules\Password::defaults(), 
  //     ],
  //   ];
  // }

  // public function attributes(): array
  // {
  //   return [
  //     'name' => 'Họ và tên',
  //     'email' => 'Email',
  //     'username' => 'Tên đăng nhập',
  //     'password' => 'Mật khẩu'
  //   ];
  // }

  // public function messages(): array
  // {
  //   return [
  //     'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
  //     'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
  //   ];
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
        Rule::unique('users', 'email')->ignore($this->route('user')),
      ],
      'username' => [
        'required',
        'string',
        'lowercase',
        'regex:/^[a-z0-9._-]+$/',
        'max:255',
        Rule::unique('users', 'username')->ignore($this->route('user')),
      ],
      'password' => [
        'nullable',
        'confirmed',
        'min:8',
        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
      'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
      'password.regex' =>
      'Mật khẩu phải gồm chữ thường, chữ in hoa, số và ký tự đặc biệt.',
    ];
  }
}
