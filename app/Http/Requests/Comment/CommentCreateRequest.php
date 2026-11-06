<?php

namespace App\Http\Requests\Comment;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class CommentCreateRequest extends FormRequest
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
            'content' => 'required|min:5',
            'parent_id' => 'nullable|exists:comments,id', // Kiểm tra nếu là bình luận trả lời
           
        ];
    }
}
