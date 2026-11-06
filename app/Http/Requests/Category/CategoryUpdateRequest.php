<?php

namespace App\Http\Requests\Category;

use App\Models\Taxonomy;
use App\Http\Requests\Concerns\HasTermTranslationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
{
    use HasTermTranslationInput;

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


        // Nếu đây là yêu cầu cập nhật, chúng ta sẽ kiểm tra trùng lặp tên và slug, ngoại trừ ID hiện tại

        $categoryId = $this->route('chuyen_muc'); // Lấy ID của chuyên mục đang được chỉnh sửa
        $termId = $this->input('term_id'); // Lấy ID của term đang cập nhật
        $cate = Taxonomy::with('term', 'parent')->where('taxonomy', 'category')->findOrFail($categoryId);
        $termId =  $cate->term->term_id;
        //Lấy id te
        return [
            'name' => [
                'required',
                'string',
                'max:500',
                // Kiểm tra unique trên cột `name` trong bảng `terms`, nhưng bỏ qua term hiện tại
                'unique:terms,name,' . $termId . ',term_id',
            ],
            'parent_id' => 'nullable|exists:term_taxonomy,term_taxonomy_id',

            //  thêm max:255
            'description' => ['nullable', 'string', 'max:255'],
        ] + $this->termTranslationRules();
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên',
            'parent_id' => 'Chuyên mục cha',
            'description' => 'Mô tả',
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',
        ] + $this->termTranslationMessages();
    }
}
