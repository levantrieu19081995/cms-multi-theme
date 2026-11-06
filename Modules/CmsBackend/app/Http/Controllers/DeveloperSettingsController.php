<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CmsBackend\Features\FeatureRegistry;
use Modules\CmsBackend\Services\Developer\DeveloperAccess;
use Modules\CmsBackend\Services\Developer\DeveloperAudit;
use Modules\CmsBackend\Services\Developer\PermissionAvailability;
use Modules\CmsBackend\Services\Developer\PermissionManifest;
use Modules\CmsBackend\Services\DeveloperSettings;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Developer Settings. Access is decided by the cms.developer middleware (a hash in .env and the Developer unlock in the session; the
 * CMS sign-in is not involved); this controller never trusts the browser: the permission list posted by the form is validated again in
 * PermissionAvailability::save(). The theme tab stays read only (Phase 3).
 */
final class DeveloperSettingsController extends Controller
{
    private const ROUTE = 'cms.dashboard.developer-settings';

    public function __construct(
        private DeveloperSettings $settings,
        private PermissionAvailability $availability,
        private PermissionManifest $manifest,
        private DeveloperAccess $access,
        private FeatureRegistry $features,
    ) {}

    public function index(Request $request)
    {
        $state = $this->availability->manifest();

        return view('cmsbackend::screens.pages.developer-settings.index', [
            'permissions' => $this->availability->overview(),
            'theme' => $this->settings->themes(),
            'features' => $this->features->report(),
            'featureErrors' => $this->features->errors(),
            'homepageLayout' => app(\Modules\CmsBackend\Services\Developer\HomepageLayoutAdmin::class)->panel($request->query('layout_theme') ? (string) $request->query('layout_theme') : null),
            'accounts' => app(\Modules\CmsBackend\Services\Developer\AccountAdmin::class)->panel(),
            'manifest' => [
                'state' => $state['state'],
                'reason' => $state['reason'],
                'updated_at' => $state['updated_at'],
                'project' => $this->manifest->projectKey(),
                'has_key' => $this->manifest->hasKey(),
                'has_backup' => $this->manifest->hasBackup(),
            ],
        ]);
    }

    public function unlockForm(Request $request)
    {
        if ($this->access->unlocked($request->session())) {
            return redirect(ModuleUrl::route(self::ROUTE));
        }

        return view('cmsbackend::screens.pages.developer-settings.unlock', ['configured' => $this->access->hasToken()]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token', '');
        if (strlen($token) > 512 || ! $this->access->verifyToken($token)) {
            // Who and from where only; the submitted value is never logged.
            Log::warning('Developer Settings unlock refused (ip '.$request->ip().').');

            return back()->withErrors(['token' => __('admin.developer.unlock_failed')]);
        }
        $this->access->unlock($request->session());
        Log::info('Developer Settings unlocked (ip '.$request->ip().').');

        return redirect(ModuleUrl::route(self::ROUTE));
    }

    public function lock(Request $request): RedirectResponse
    {
        $this->access->lock($request->session());

        return redirect(ModuleUrl::route('cms.dashboard.developer-settings.unlock'));
    }

    public function saveAvailability(Request $request): RedirectResponse
    {
        $allowed = $request->input('allowed', []);
        if (! is_array($allowed)) {
            return $this->failed('error_unknown_permission');
        }

        try {
            $result = $this->availability->save(array_filter($allowed, 'is_string'), $request->ip());
        } catch (InvalidArgumentException $e) {
            return $this->failed($e->getMessage());
        }

        Toastr::success(__('admin.developer.saved', ['on' => count($result['enabled']), 'off' => count($result['disabled'])]), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE));
    }

    public function restoreAvailability(Request $request): RedirectResponse
    {
        try {
            $this->availability->restore($request->ip());
        } catch (InvalidArgumentException $e) {
            return $this->failed($e->getMessage());
        }

        Toastr::success(__('admin.developer.restored'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE));
    }

    public function saveTheme(Request $request): RedirectResponse
    {
        $theme = $request->input('theme');
        try {
            $result = $this->settings->switchTheme(is_string($theme) ? $theme : '', $request->ip());
        } catch (InvalidArgumentException $e) {
            return $this->failed($e->getMessage(), 'theme');
        }

        Toastr::success(__('admin.developer.theme_saved', ['theme' => $result['new']]), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE, ['tab' => 'theme']));
    }

    /** Bật/tắt một chức năng. Chỉ phiên Developer đã mở khóa (middleware cms.developer); dữ liệu của chức năng không bị đụng tới. */
    public function saveFeature(Request $request, string $feature): RedirectResponse
    {
        $definition = $this->features->find($feature);
        $enabled = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($definition === null || $enabled === null) {
            return $this->failed('feature_invalid', 'features');
        }
        $before = $this->features->isEnabled($feature);
        if ($before !== $enabled) {
            $this->features->setEnabled($feature, $enabled);
            if ($feature === 'multilingual') {
                // Nội dung cache theo ngôn ngữ (trang chủ, menu, văn bản tùy chọn) phải dựng lại theo danh sách ngôn ngữ mới.
                app(\App\Services\LocaleManager::class)->bumpCacheVersion();
            }
            DeveloperAudit::record(['feature' => $feature, 'before' => $before, 'after' => $enabled, 'ip' => $request->ip()], $enabled ? 'Developer Settings: bật chức năng '.$feature : 'Developer Settings: tắt chức năng '.$feature);
            Log::info('Developer Settings: feature '.$feature.' '.($enabled ? 'enabled' : 'disabled').' (ip '.$request->ip().').');
        }
        Toastr::success(__($enabled ? 'admin.developer.feature_on' : 'admin.developer.feature_off', ['name' => $definition->name()]), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE, ['tab' => 'features']));
    }

    private function failed(string $key, ?string $tab = null): RedirectResponse
    {
        \App\Facades\Toastr::toast(__('admin.developer.'.$key), 'error');

        return redirect(ModuleUrl::route(self::ROUTE, $tab !== null ? ['tab' => $tab] : []));
    }
}
