<?php

namespace Modules\CmsBackend\Http\Requests\LoaiVanBan;

use Illuminate\Foundation\Http\FormRequest;

class CreateLoaiVanBanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lvb_ten' => [
                'required',
                'max:255',
                'unique:qlvb_loai_van_ban,lvb_ten,0,lvb_id'
            ],
            'lvb_ma' => [
                'required',
                'max:10',
                'unique:qlvb_loai_van_ban,lvb_ma,0,lvb_id'
            ]
        ];
    }

    public function attributes(): array
    {
        return [
            'lvb_ten' => __('admin.ui.ten_loai_van_ban'),
            'lvb_ma' => __('admin.ui.ma_loai_van_ban')
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
