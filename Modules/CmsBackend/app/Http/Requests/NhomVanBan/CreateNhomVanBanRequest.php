<?php

namespace Modules\CmsBackend\Http\Requests\NhomVanBan;

use Illuminate\Foundation\Http\FormRequest;

class CreateNhomVanBanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nvb_name' => [
                'required',
                'max:50',
                'unique:qlvb_nhom_van_ban,nvb_name,0,nvb_id'
            ],
            'nvb_description' => [
                'required',
                'max:255',
            ]
        ];
    }

    public function attributes(): array
    {
        return [
            'nvb_name' => __('admin.ui.ten_nhom_van_ban'),
            'nvb_description' => __('admin.ui.mo_ta_nhom_van_ban')
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}