<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuongTieuBieuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $avatarRule = $this->isMethod('post') ? 'required' : 'nullable';

        return [
            'title'          => ['required', 'string', 'max:255'],
            // BẮT BUỘC & trong khoảng 0..5, cho phép số lẻ
            'rating'         => ['required', 'numeric', 'between:0,5'],
            'role'           => ['required', 'string', 'max:255'],
            'excerpt'        => ['nullable', 'string', 'max:1000'],
            'content'        => [
                'required',
                function ($attr, $value, $fail) {
                    if (!trim(strip_tags($value ?? ''))) $fail(__('admin.message.noi_dung_khong_duoc_bo_trong'));
                }
            ],
            'avatar'         => [$avatarRule, 'string'],
            'show_home_page' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => __('admin.message.vui_long_nhap_ten_tam_guong_tieu_bieu'),
            'title.max'        => __('admin.message.ten_khong_vuot_qua_max_ky_tu'),
            'rating.required'  => __('admin.message.vui_long_nhap_muc_danh_gia'),
            'rating.numeric'   => __('admin.message.muc_danh_gia_phai_la_so'),
            'rating.between'   => __('admin.message.muc_danh_gia_phai_tu_0_den_5'),
            'role.required'    => __('admin.message.vui_long_nhap_vai_tro'),
            'role.max'         => __('admin.message.vai_tro_toi_da_max_ky_tu'),
            'excerpt.max'      => __('admin.message.gioi_thieu_toi_da_max_ky_tu'),
            'avatar.required'  => __('admin.message.vui_long_chon_anh_dai_dien'),
            'content.required' => __('admin.message.noi_dung_khong_duoc_bo_trong'),
        ];
    }





    public function attributes(): array
    {
        return [
            'title'   => __('admin.message.ten_tam_guong'),
            'rating'  => __('admin.message.muc_danh_gia'),
            'role'    => __('admin.message.vai_tro'),
            'excerpt' => __('admin.message.gioi_thieu_ngan'),
            'content' => __('admin.message.noi_dung'),
            'avatar'  => __('admin.message.anh_dai_dien'),
        ];
    }
}
