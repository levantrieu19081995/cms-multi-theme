<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarqueeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('admin.message.vui_long_nhap_ten_marquee'),
        ];
    }
}