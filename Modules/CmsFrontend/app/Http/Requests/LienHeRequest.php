<?php

namespace Modules\CmsFrontend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LienHeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => ['required', 'string', 'regex:/^[0-9\-\+\s\(\)\.]+$/', 'min:8', 'max:30'],
            'address' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'name.required'    => __('messages.name_required'),
            'name.max'         => __('messages.name_max_255'),
            'email.required'   => __('messages.email_required'),
            'email.email'      => __('messages.email_invalid'),
            'phone.required'   => __('messages.phone_required'),
            'phone.regex'      => __('messages.phone_invalid'),
            'phone.min'        => __('messages.phone_short'),
            'phone.max'        => __('messages.phone_long'),
            'address.required' => __('messages.address_required'),
            'subject.required' => __('messages.title_required'),
            'message.required' => __('messages.content_required'),
            'message.min'      => __('messages.content_min_10'),
            'message.max'      => __('messages.content_max_5000'),
        ];
    }
}
