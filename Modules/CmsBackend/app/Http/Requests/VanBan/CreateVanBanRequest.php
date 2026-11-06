<?php

namespace Modules\CmsBackend\Http\Requests\VanBan;

use Illuminate\Foundation\Http\FormRequest;

class CreateVanBanRequest extends FormRequest
{
    /**
     * Nếu không upload file thật, ép field về null để rule 'file' không chạy sai.
     */
    protected function prepareForValidation()
    {
        // if (!$this->hasFile('fileDinhKem')) {
        //     $this->merge(['fileDinhKem' => null]);
        // }
    }

    public function rules(): array
    {
        return [
            // --- YÊU CẦU ÍT NHẤT 1 TRONG 2 ---
            'vb_file_url' => [
                'nullable',
                'string',
                'max:255',
                'url',                          // URL hợp lệ
                'regex:/^https?:\/\/.+/i',      // chỉ http/https
                'required_without:fileDinhKem', // nếu KHÔNG có file thì URL bắt buộc
            ],
            'fileDinhKem' => [
                'nullable',
                'string',                         // chỉ kiểm tra khi thật sự có file (nhờ prepareForValidation)
                'required_without:vb_file_url', // nếu KHÔNG có URL thì file bắt buộc
                // thêm 'mimes:pdf,doc,docx','max:5120' nếu muốn
            ],

            // --- CÁC FIELD KHÁC ---
            'vb_so_hieu' => ['required', 'max:50', 'unique:qlvb_van_ban,vb_so_hieu,0,vb_id'],
            'vb_trich_yeu' => ['required', 'max:1000'],
            'vb_ngay_ban_hanh' => ['required'],
            'vb_ngay_hieu_luc' => ['required'],
            'vb_ngay_cong_bo' => ['required'],
            'lvb_id' => ['required', 'numeric', 'min:0', 'not_in:0'],
            'nvb_id' => ['required', 'numeric', 'min:0', 'not_in:0'],
            'vb_thoi_gian_luu_tru' => ['required', 'numeric'],
            // 'vb_lan_ban_hanh' => ['required', 'numeric', 'min:1'],
            'vb_co_quan_ban_hanh' => ['required', 'max:50'],
            'vb_lan_ban_hanh'     => ['required', 'integer', 'min:1'],
            'vb_thoi_gian_luu_tru' => ['required', 'integer', 'min:-1'],
        ];
    }

    public function attributes()
    {
        return [
            'vb_so_hieu' => __('admin.message.so_hieu_van_ban'),
            'vb_trich_yeu' => __('admin.ui.trich_yeu'),
            'vb_ngay_ban_hanh' => __('admin.ui.ngay_ban_hanh'),
            'vb_ngay_hieu_luc' => __('admin.ui.ngay_hieu_luc'),
            'vb_ngay_cong_bo' => __('admin.ui.ngay_cong_bo'),
            'lvb_id' => __('admin.ui.loai_van_ban'),
            'nvb_id' => __('admin.ui.nhom_van_ban'),
            'vb_file_url' => __('admin.message.url_tap_tin'),
            'fileDinhKem' => __('admin.message.file_dinh_kem'),
            'vb_thoi_gian_luu_tru' => __('admin.message.thoi_gian_luu_tru'),
            'vb_lan_ban_hanh' => __('admin.ui.lan_ban_hanh'),
            'vb_co_quan_ban_hanh' => __('admin.ui.co_quan_ban_hanh'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
