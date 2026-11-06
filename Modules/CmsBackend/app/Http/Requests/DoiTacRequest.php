<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoiTacRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => __('admin.message.vui_long_nhap_tieu_de'),
            'slug.required' => __('admin.message.vui_long_nhap_duong_dan'),
            'excerpt.required' => __('admin.message.vui_long_nhap_phan_gioi_thieu_ngan'),
            'content.required' => __('admin.message.vui_long_nhap_thong_tin_doi_tac'),
        ];
    }
}
