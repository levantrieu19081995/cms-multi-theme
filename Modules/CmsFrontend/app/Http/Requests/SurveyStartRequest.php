<?php

namespace Modules\CmsFrontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CmsFrontend\Services\SurveyPublicService;

/**
 * Bước "bắt đầu": đồng ý cung cấp dữ liệu và thông tin cá nhân (chỉ khi khảo sát yêu cầu). Quy tắc suy ra từ cấu hình khảo sát.
 */
class SurveyStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(SurveyPublicService $service): array
    {
        $survey = $service->find((string) $this->route('slug'));
        // Khảo sát định hướng nghề nghiệp có thể kèm trường người làm đang học (danh sách trong tùy chọn school_list; khóa lạ bị bỏ qua).
        $school = $survey?->ks_loai === \Modules\CmsBackend\Services\SurveyService::CAREER ? ['truong' => ['nullable', 'string', 'max:100']] : [];
        if ($survey === null || ! $survey->ks_yeu_cau_thong_tin) {
            return $school;
        }
        $anonymous = $survey->ks_cho_phep_an_danh && $this->boolean('an_danh');
        if ($anonymous) {
            return ['an_danh' => ['boolean']] + $school;
        }

        return $school + [
            'an_danh' => ['sometimes', 'boolean'],
            'dong_y' => ['accepted'],
            'ho_ten' => ['required', 'string', 'max:255'],
            'so_dien_thoai' => ['nullable', 'required_without:email', 'regex:/^[0-9\-\+\s\(\)]+$/', 'min:8', 'max:20'],
            'email' => ['nullable', 'required_without:so_dien_thoai', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'dong_y.accepted' => __('survey.error.consent_required'),
            'so_dien_thoai.required_without' => __('survey.error.contact_required'),
            'email.required_without' => __('survey.error.contact_required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'ho_ten' => __('survey.field.full_name'),
            'so_dien_thoai' => __('survey.field.phone'),
            'email' => __('survey.field.email'),
        ];
    }
}
