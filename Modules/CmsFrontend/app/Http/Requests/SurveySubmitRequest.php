<?php

namespace Modules\CmsFrontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CmsFrontend\Services\SurveyPublicService;

/**
 * Câu trả lời gửi lên. Quy tắc dựng từ cấu trúc khảo sát (mã UUID), nên đáp án lạ hoặc thiếu câu bắt buộc bị từ chối ở đây
 * trước khi tới Service (Service kiểm tra lần hai).
 */
class SurveySubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(SurveyPublicService $service): array
    {
        $survey = $service->find((string) $this->route('slug'));

        return $survey === null ? [] : $service->answerRules($survey)['rules'];
    }

    public function attributes(): array
    {
        $survey = app(SurveyPublicService::class)->find((string) $this->route('slug'));

        return $survey === null ? [] : app(SurveyPublicService::class)->answerRules($survey)['attributes'];
    }

    public function messages(): array
    {
        return [
            '*.required' => __('survey.error.answer_required'),
            '*.in' => __('survey.error.invalid_answer'),
            '*.between' => __('survey.error.invalid_answer'),
            '*.integer' => __('survey.error.invalid_answer'),
            '*.max' => __('survey.error.answer_too_long'),
            '*.min' => __('survey.error.answer_required'),
        ];
    }

    /** @return array<string, mixed> */
    public function answers(): array
    {
        return (array) ($this->validated()['answers'] ?? []);
    }
}
