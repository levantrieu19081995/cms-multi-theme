<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\Concerns\HasTermTranslationInput;
use Illuminate\Foundation\Http\FormRequest;

class CategoryCreateRequest extends FormRequest
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
        return [
            'name' => 'required|unique:terms,name',
            'parent_id' => 'nullable|exists:term_taxonomy,term_taxonomy_id',
        ] + $this->termTranslationRules();
    }

    public function messages(): array
    {
        return $this->termTranslationMessages();
    }

    public function attributes(): array
    {
        return [
          'name' => 'Tên',
          'parent_id' => 'Chuyên mục cha'
        ];
    }
}
