<?php

namespace App\Http\Requests\ImageLibrary;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class ImageLibraryUpdateRequest extends FormRequest
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
            'title' => 'required|string',
            'parent_id' => 'nullable|integer',
            'images' => 'required', // Hình ảnh upload
            ];
    }
    public function messages()
    {
        return [
            'title.required' => __('Tiêu đề không được bỏ trống'),
            'images.required' => __('Chưa có hình ảnh được chọn')
            // Customize other messages as needed
        ];
    }
}
