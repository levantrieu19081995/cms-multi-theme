<?php

namespace App\Http\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\OptionEnum;

class OptionUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected array $dynamicRules = [];

    public function prepareForValidation()
    {
        $controller = $this->route()?->getController();

        // Nếu controller có method getDynamicRules thì lấy ra và set
        if (method_exists($controller, 'getDynamicRules')) {
            $this->setDynamicRules($controller->getDynamicRules());
        }
    }


    public function setDynamicRules(array $rules): void
    {
        $this->dynamicRules = $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allRules = [
            OptionEnum::WEB_TITLE->value        => 'required|max:255',
            OptionEnum::WEB_DESCRIPTION->value  => 'required|max:500',
        ];

        // Lấy danh sách field đang sử dụng từ controller (nếu có)
        $controller = $this->route()?->getController();
        $optionsLoad = method_exists($controller, 'getOptionsLoad')
            ? $controller->getOptionsLoad()
            : [];

        // Gộp dynamicRules, ưu tiên dynamicRules nếu trùng key
        $mergedRules = array_merge($allRules, $this->dynamicRules);

        $rules = [];

        // Chỉ lấy các rule có key nằm trong optionsLoad
        foreach ($optionsLoad as $key) {
            if (isset($mergedRules[$key])) {
                $rules[$key] = $mergedRules[$key];
            }
        }

        return $rules;
    }
}
