<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CmsBackend\Services\ScreenTheme;

final class UpdateScreenThemesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::ABOUT_MANAGER->label()) ?? false;
    }

    public function rules(): array
    {
        $screens = app(ScreenTheme::class);

        return [
            'cms_admin_theme' => ['prohibited'],
            'cms_public_theme' => ['required', 'string', Rule::in(array_keys($screens->choices('public')))],
        ];
    }

    public function messages(): array
    {
        return [
            'cms_admin_theme.prohibited' => __('admin.message.giao_dien_quan_tri_duoc_co_dinh_chi_co_the_chon_'),
            'cms_public_theme.required' => __('admin.message.hay_chon_giao_dien_trang_cong_khai'),
            'cms_public_theme.in' => __('admin.message.giao_dien_trang_cong_khai_chua_duoc_dang_ky'),
        ];
    }
}
