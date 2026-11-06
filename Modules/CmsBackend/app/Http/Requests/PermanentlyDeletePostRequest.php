<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PermanentlyDeletePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Quản trị') ?? false;
    }

    public function rules(): array
    {
        return ['revision' => ['required', 'integer', 'min:0'], 'confirmation' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['revision.required' => __('admin.message.hay_tai_lai_danh_sach_truoc_khi_xoa'), 'revision.integer' => __('admin.message.phien_ban_bai_viet_khong_hop_le'), 'confirmation.required' => __('admin.message.purge_confirmation_required'), 'confirmation.accepted' => __('admin.message.purge_confirmation_required')];
    }
}
