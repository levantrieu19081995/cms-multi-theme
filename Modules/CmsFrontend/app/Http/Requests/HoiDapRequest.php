<?php

namespace Modules\CmsFrontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoiDapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'    => 'required|string|max:500',
            'email'   => 'required|email|max:255',
            'phone'   => 'required|regex:/^[0-9\-\+\s\(\)]+$/|min:8|max:15',
            'title'   => 'required|string|max:255',          // ✅ tăng lên 255
            'content' => 'required|string|min:10|max:10000', // ✅ giới hạn rõ ràng hơn
        ];
    }

    public function messages()
    {
        return [
            'name.required'    => __('messages.name_required'),
            'name.max'         => __('messages.name_max_500'),
            'email.email'      => __('messages.email_invalid'),
            'phone.regex'      => __('messages.phone_invalid_digits'),
            'phone.min'        => __('messages.phone_short'),
            'phone.max'        => __('messages.phone_long'),
            'title.required'   => __('messages.title_required'),
            'title.max'        => __('messages.title_max_255'),
            'content.required' => __('messages.content_required'),
            'content.min'      => __('messages.content_min_10'),
            'content.max'      => __('messages.content_max_10000'),
        ];
    }
}
