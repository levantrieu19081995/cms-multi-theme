<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\CmsBackend\Services\Developer\HomepageLayoutAdmin;
use Modules\CmsBackend\Support\ModuleUrl;
use Throwable;

/**
 * Tab "Bố cục trang chủ" của Developer Settings: cài bố cục mặc định, xem trước (snapshot), áp dụng và khôi phục.
 * Mọi route nằm sau middleware cms.developer (phiên Developer đã mở khóa); không dùng tài khoản CMS.
 */
final class HomepageLayoutController extends Controller
{
    private const ROUTE = 'cms.dashboard.developer-settings';

    public function __construct(private HomepageLayoutAdmin $admin) {}

    public function install(Request $request, string $theme): RedirectResponse
    {
        try {
            $created = $this->admin->install($theme, $request->ip());
        } catch (Throwable $e) {
            return $this->failed($e, $theme);
        }
        Toastr::success(__('homepage.ui.installed', ['count' => $created]), __('admin.message.thanh_cong'));

        return $this->back($theme);
    }

    public function reset(Request $request, string $theme): RedirectResponse
    {
        try {
            $this->admin->reset($theme, $request->ip());
        } catch (Throwable $e) {
            return $this->failed($e, $theme);
        }
        Toastr::success(__('homepage.ui.reset_done'), __('admin.message.thanh_cong'));

        return $this->back($theme);
    }

    /** Validate toàn bộ cấu hình đang chỉnh rồi tạo snapshot và mở trang Review. Chưa đổi gì ở bố cục Live. */
    public function preview(Request $request, string $theme): RedirectResponse
    {
        try {
            $token = $this->admin->preview($theme, $request->input('layout'), $request->session()->getId());
        } catch (Throwable $e) {
            return $this->failed($e, $theme);
        }

        return redirect(ModuleUrl::route(self::ROUTE.'.homepage.review', ['token' => $token]));
    }

    /** Áp dụng ngay bố cục đang chỉnh (không qua xem trước). Server validate lại toàn bộ như khi áp dụng từ trang xem trước. */
    public function save(Request $request, string $theme): RedirectResponse
    {
        try {
            $rows = $this->admin->applyNow($theme, $request->input('layout'), $request->ip());
        } catch (Throwable $e) {
            return $this->failed($e, $theme);
        }
        Toastr::success(__('homepage.ui.applied_now', ['count' => count($rows)]), __('admin.message.thanh_cong'));

        return $this->back($theme);
    }

    public function review(Request $request, string $token)
    {
        $review = $this->admin->review($token, $request->session()->getId());
        if ($review === null) {
            Toastr::toast(__('homepage.errors.preview_expired'), 'error');

            return redirect(ModuleUrl::route(self::ROUTE, ['tab' => 'layout']));
        }

        return response()->view('cmsbackend::screens.pages.developer-settings.homepage-review', ['review' => $review])
            ->withHeaders($this->privateHeaders());
    }

    /**
     * Trang chủ THẬT của theme (cùng controller, renderer, dữ liệu và asset) với bố cục lấy từ snapshot thay cho Live.
     * Chỉ đọc; không cache công khai, không cho công cụ tìm kiếm lập chỉ mục.
     */
    public function frame(Request $request, string $token): Response
    {
        $review = $this->admin->review($token, $request->session()->getId());
        abort_if($review === null || ! $review['renderable'], 404);
        $request->attributes->set('_homepage_preview', ['token' => $token]);
        $request->attributes->set('_homepage_preview_theme', $review['theme']);
        $view = app(\Modules\CmsFrontend\Http\Controllers\HomeController::class)->index();

        return response($view)->withHeaders($this->privateHeaders());
    }

    public function apply(Request $request, string $token): RedirectResponse
    {
        $review = $this->admin->review($token, $request->session()->getId()); // before apply: the snapshot is consumed by it
        $theme = $review['theme'] ?? null;
        $back = redirect(ModuleUrl::route(self::ROUTE, ['tab' => 'layout'] + ($theme !== null ? ['layout_theme' => $theme] : [])));
        try {
            $rows = $this->admin->apply($token, $request->session()->getId(), $request->ip());
        } catch (Throwable $e) {
            Toastr::toast(__('homepage.errors.'.$this->code($e)), 'error');

            return $back;
        }
        Toastr::success(__('homepage.ui.applied', ['count' => count($rows)]), __('admin.message.thanh_cong'));

        return $back;
    }

    /** @return array<string, string> */
    private function privateHeaders(): array
    {
        return ['X-Robots-Tag' => 'noindex, nofollow', 'Cache-Control' => 'no-store, private'];
    }

    private function back(string $theme): RedirectResponse
    {
        return redirect(ModuleUrl::route(self::ROUTE, ['tab' => 'layout', 'layout_theme' => $theme]));
    }

    private function failed(Throwable $e, string $theme): RedirectResponse
    {
        Toastr::toast(__('homepage.errors.'.$this->code($e)), 'error');

        return $this->back($theme);
    }

    /** Mã lỗi nghiệp vụ -> khóa lang; lỗi không lường trước hiển thị chung và không lộ chi tiết. */
    private function code(Throwable $e): string
    {
        if (! $e instanceof InvalidArgumentException) {
            report($e);

            return 'unexpected';
        }
        $code = explode(':', $e->getMessage())[0];

        return in_array($code, ['preview_invalid', 'preview_expired', 'apply_blocked', 'not_ready', 'theme_unknown', 'unknown_instance'], true) ? $code : 'unexpected';
    }
}
