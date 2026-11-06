<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DichVuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => __('admin.message.vui_long_nhap_tieu_de'),
            'link.required' => __('admin.message.vui_long_nhap_link'),
            'icon.required' => __('admin.message.vui_long_nhap_icon'),
        ];
    }
}