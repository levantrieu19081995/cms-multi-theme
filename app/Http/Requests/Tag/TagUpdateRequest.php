<?php

namespace App\Http\Requests\Tag;

use App\Models\Taxonomy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Taxonomy as EnumsTaxonomy;

class TagUpdateRequest extends FormRequest
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


        // Nếu đây là yêu cầu cập nhật, chúng ta sẽ kiểm tra trùng lặp tên và slug, ngoại trừ ID hiện tại

            $categoryId = $this->route('the'); // Lấy ID của chuyên mục đang được chỉnh sửa
            $termId = $this->input('term_id'); // Lấy ID của term đang cập nhật
            $cate = Taxonomy::with('term','parent')->where('taxonomy', EnumsTaxonomy::POSTTAG->value)->findOrFail($categoryId);
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
            ];
    }

}
