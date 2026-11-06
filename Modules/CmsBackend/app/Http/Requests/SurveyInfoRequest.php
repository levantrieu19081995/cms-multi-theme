<?php

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Thông tin chung của khảo sát. Quyền được kiểm tra ở route (cms.check.permission), không ở đây. */
class SurveyInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ks_ten' => ['required', 'string', 'max:500'],
            'ks_ma' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-_ ]+$/'],
            'ks_mo_ta_ngan' => ['nullable', 'string', 'max:500'],
            'ks_gioi_thieu' => ['nullable', 'string', 'max:100000'],
            'ks_loai' => ['nullable', 'string', 'in:general,satisfaction,feedback,research'],
            'ks_bat_dau' => ['nullable', 'date'],
            'ks_ket_thuc' => ['nullable', 'date', 'after:ks_bat_dau'],
            'ks_cho_phep_an_danh' => ['sometimes', 'boolean'],
            'ks_yeu_cau_thong_tin' => ['sometimes', 'boolean'],
            'ks_so_lan_gui' => ['nullable', 'integer', 'min:0', 'max:100'],
            'ks_noi_dung_hoan_thanh' => ['nullable', 'string', 'max:5000'],
            'ks_ghichu' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ks_cho_phep_an_danh' => $this->boolean('ks_cho_phep_an_danh'),
            'ks_yeu_cau_thong_tin' => $this->boolean('ks_yeu_cau_thong_tin'),
            'ks_so_lan_gui' => $this->input('ks_so_lan_gui', 1) === '' ? 1 : $this->input('ks_so_lan_gui', 1),
        ]);
    }

    public function attributes(): array
    {
        return [
            'ks_ten' => __('admin.survey.field.name'),
            'ks_ma' => __('admin.survey.field.slug'),
            'ks_bat_dau' => __('admin.survey.field.start'),
            'ks_ket_thuc' => __('admin.survey.field.end'),
            'ks_so_lan_gui' => __('admin.survey.field.submit_limit'),
        ];
    }
}
