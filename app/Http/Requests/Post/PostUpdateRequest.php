<?php

namespace App\Http\Requests\Post;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Banner tắt "hiện chữ": tiêu đề/mô tả được để trống. Bài đăng vẫn cần tiêu đề và slug để lưu và nhận diện trong danh sách,
     * nên khi để trống sẽ điền tên tạm theo giờ tạo (người dùng có thể sửa sau).
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('post_type') !== PostType::BANNER->value || $this->boolean('show_text', true)) {
            return;
        }
        $merge = [];
        if (! filled($this->input('title'))) {
            $merge['title'] = 'Banner '.now()->format('d/m/Y H:i:s');
        }
        if (! filled($this->input('slug'))) {
            $merge['slug'] = 'banner-'.now()->format('YmdHis');
        }
        $this->merge($merge);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $type = $this->input('post_type');

        $rules = null;

        if ($type === PostType::POST->value) {
            $rules = [
                'title' => ['required', 'string', 'max:500'],
                'category_ids' => 'required|array', // Đảm bảo là một mảng
                'excerpt' => ['required', 'max:500'],
                'author_names' => ['required', 'max:500'],
                // Các rules khác cho post
            ];
        } else if ($type === PostType::PAGE->value) {
            $rules = [
                'title' => ['required', 'string', 'max:500'],
                // 'order' => ['min:1', 'integer'],

            ];
        } else if ($type === PostType::IMAGE->value) {
            $rules = [
                'title' => ['required', 'string', 'max:500'],
                'parent_id' => 'nullable|integer',
                'images' => 'required', // Hình ảnh upload
            ];
        } else if ($type === PostType::VIDEO->value) {
            $rules = [
                'title' => 'required|string'
            ];
        } else if ($type === PostType::BANNER->value) {
            $rules = [
                'title' => 'required|string',
                'avatar' => 'required', // Hình ảnh upload
                'description' => ['nullable', 'string', 'max:255'],
                'btn_text' => ['nullable', 'string', 'max:255'],
            ];
        } else if ($type === PostType::EVENT->value) {
            $rules = [
                'title' => ['required', 'string', 'max:500'],
                'excerpt' => ['required', 'max:1000'],
                // Các rules khác cho post
            ];
        } else {
            return [
                'title' => ['required', 'string', 'max:500'],
                'category_ids' => 'required|array', // Đảm bảo là một mảng
                'excerpt' => ['string', 'max:500'],
                // Các rules khác cho post
            ];
        }

        return $rules;
    }
    public function messages()
    {
        $isEvent = $this->input('post_type') === PostType::EVENT->value;

        return [
            'title.required' => __('Tiêu đề không được bỏ trống'),
            'title.max' => __('Tiêu đề không được vượt quá 500 ký tự'),
            'category_ids.required' => __('Chưa chọn chuyên mục'),
            'avatar.required' => __('Chưa chọn hình ảnh'),
            'excerpt.required' => $isEvent ? __('Địa điểm không được để trống') : __('Trích dẫn không được để trống'),
            'author_names.required' => $isEvent ? __('Địa điểm không được để trống') : __('Tác giả không được để trống'),

            // Banner
            'description.max' => __('Mô tả không được vượt quá 255 ký tự'),
            'description.string' => __('Mô tả phải là chuỗi ký tự'),

            'btn_text.max' => __('Nhãn nút không được vượt quá 255 ký tự'),
            'btn_text.string' => __('Nhãn nút phải là chuỗi ký tự'),
        ];
    }
}
