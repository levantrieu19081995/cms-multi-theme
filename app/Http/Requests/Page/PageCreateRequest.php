<?php

namespace App\Http\Requests\Page;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class PageCreateRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'order' => ['min:1', 'integer'],
            //'slug' => ['required', 'string', 'lowercase', 'max:500', 'unique:'.Post::class],
           
        ];
    }
    public function messages()
    {
        return [
            'title.required' => __('Tiêu đề không được bỏ trống'),
            'title.max' => __('Tiêu đề không được vượt quá 500 ký tự'),
            // Customize other messages as needed
        ];
    }
}
