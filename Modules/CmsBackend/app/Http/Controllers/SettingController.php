<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;
use Modules\CmsFrontend\Homepage\HomepageSettingsService;

/**
 * Cài đặt trang chủ: form sinh động từ `settingsSchema()` của các cụm ĐANG BẬT của giao diện hiện tại (Homepage Layout Manager).
 * Admin chỉ sửa nguồn nội dung; bật/tắt, zone, variant và thứ tự là việc của Developer.
 */
class SettingController extends Controller
{
    public function __construct()
    {
        // bắt buộc đăng nhập (nếu bạn đang dùng auth)
        $this->middleware('auth');

        // Ai có "Quản lý vai trò" là vào được tất cả
        $this->middleware('cms.check.permission:'.PermissionsEnum::HOMEPAGE_MANAGER->label())
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $categories = Taxonomy::where('taxonomy', EnumsTaxonomy::CATEGORY->value)->with('term')->get();
        $dynamicForms = $this->forms();

        return view('cmsbackend::screens.pages.setting.general', compact('categories', 'dynamicForms'));
    }

    /**
     * Lưu cấu hình của mọi cụm đang bật. Validate hết theo schema của từng Definition trước; một field sai thì không lưu gì. Cụm không
     * đang bật bị bỏ qua dù trình duyệt có gửi lên.
     */
    public function update(Request $request)
    {
        $theme = app(ScreenTheme::class)->key('public');
        $settings = app(HomepageSettingsService::class);
        $forms = $this->forms();
        $errors = [];
        foreach ($forms as $form) {
            $input = (array) $request->input('homepage_blocks.'.$form['instance'], []);
            foreach ($settings->validate($form['definition'], $input)['errors'] as $field => $code) {
                $errors['homepage_blocks.'.$form['instance'].'.'.$field] = __('homepage.errors.'.$code);
            }
        }
        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }
        foreach ($forms as $form) {
            $settings->save($theme, $form['instance'], (array) $request->input('homepage_blocks.'.$form['instance'], []));
        }
        Toastr::success(__('homepage.settings.saved'));

        return redirect()->route('cms.setting.trang-chu');
    }

    /**
     * Field của các cụm đang bật của giao diện hiện tại.
     *
     * @return list<array<string, mixed>>
     */
    private function forms(): array
    {
        $theme = app(ScreenTheme::class)->key('public');
        $plan = app(HomepageLayoutService::class)->live($theme);

        return $plan === null ? [] : app(HomepageSettingsService::class)->forms($theme, $plan);
    }
}
