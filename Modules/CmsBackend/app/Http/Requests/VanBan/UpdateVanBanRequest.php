<?php

namespace Modules\CmsBackend\Http\Requests\VanBan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVanBanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vbId = $this->route('van_ban')->vb_id;
        return [
            'vb_file_url' => ['nullable', 'string'], // quyền validate cái đường dẫn và tập tin
            'fileDinhKem' => ['nullable', 'string'], // quyền validate cái đường dẫn và tập tin
            'vb_so_hieu' => [
                'required',
                'max:50',
                "unique:qlvb_van_ban,vb_so_hieu,$vbId,vb_id"
            ],
            'vb_trich_yeu' => [
                'required',
                'max:1000',
            ],
            'vb_ngay_ban_hanh' => [
                'required'
            ],
            'vb_ngay_hieu_luc' => [
                'required'
            ],
            'vb_ngay_cong_bo' => [
                'required'
            ],
            'lvb_id' => [
                'required',
                'numeric',
                'min:0',
                'not_in:0'
            ],
            'nvb_id' => [
                'required',
                'numeric',
                'min:0',
                'not_in:0'
            ],
            'vb_file_url' => [
                // 'required',
                'max:255'
                // 'url'
            ],
            'vb_thoi_gian_luu_tru' => [
                'required',
                'numeric',
            ],
            'vb_lan_ban_hanh' => [
                'required',
                'numeric',
                'min:1',
            ],
            'vb_co_quan_ban_hanh' => [
                'required',
                'max:50',
            ],
        ];
    }

    public function attributes()
    {
        return [
            'vb_so_hieu' => __('admin.message.so_hieu_van_ban'),
            'vb_trich_yeu' => __('admin.ui.trich_yeu'),
            'vb_ngay_ban_hanh' => __('admin.ui.ngay_ban_hanh'),
            'vb_ngay_hieu_luc' => __('admin.ui.ngay_hieu_luc'),
            'lvb_id' => __('admin.ui.loai_van_ban'),
            'nvb_id' => __('admin.ui.nhom_van_ban'),
            'vb_file_url' => __('admin.message.duong_dan_tap_tin'),
            'vb_thoi_gian_luu_tru' => __('admin.message.thoi_gian_luu_tru'),
            'vb_lan_ban_hanh' => __('admin.ui.lan_ban_hanh'),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $vb_file_url = trim((string) $this->input('vb_file_url', ''));
            $fileDinhKem = $this->input('fileDinhKem');
            $hasFileInput = false;

            if (is_string($fileDinhKem)) {
                $fileDinhKem = trim($fileDinhKem);
                $hasFileInput = $fileDinhKem !== ''
                    && $fileDinhKem !== '[]'
                    && $fileDinhKem !== '{}'
                    && $fileDinhKem !== 'null'
                    && $fileDinhKem !== 'undefined';
            } elseif (is_array($fileDinhKem)) {
                $hasFileInput = count(array_filter($fileDinhKem, fn($file) => trim((string) $file) !== '')) > 0;
            }

            $vanBan = $this->route('van_ban');
            $hasExistingFile = !$this->has('fileDinhKem')
                && $vanBan
                && $vanBan->getMedia('fileDinhKem')->isNotEmpty();

            if ($vb_file_url === '' && !$hasFileInput && !$hasExistingFile) {
                $validator->errors()->add('vb_file_url', __('admin.message.ban_phai_nhap_duong_dan_tap_tin_hoac_dinh_kem_fi'));
                $validator->errors()->add('fileDinhKem', __('admin.message.ban_phai_nhap_duong_dan_tap_tin_hoac_dinh_kem_fi'));
            }
        });
    }
}
