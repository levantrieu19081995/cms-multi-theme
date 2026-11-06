<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cấu trúc câu hỏi gửi từ trình dựng dưới dạng JSON trong trường `structure`. Kiểm tra sâu (kiểu, lựa chọn, thang điểm) do
 * SurveyService::normalizeStructure đảm nhiệm để một nơi duy nhất quyết định quy tắc.
 */
class SurveyStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'structure' => ['present', 'array', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('structure');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->merge(['structure' => is_array($decoded) ? $decoded : null]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function groups(): array
    {
        return array_values((array) $this->validated()['structure']);
    }

    public function messages(): array
    {
        return ['structure.present' => __('admin.survey.error.structure_invalid'), 'structure.array' => __('admin.survey.error.structure_invalid')];
    }
}
