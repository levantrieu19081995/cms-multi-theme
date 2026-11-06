<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Support;

use Illuminate\Support\ViewErrorBag;
use Modules\CmsBackend\Services\ScreenTheme;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Display data shared by every theme's Login page (views/auth/shared/* and screens/<theme>/pages/auth/login.blade.php).
 * Presentation only: brand text/logo from the active public theme, which pane is shown, the error line. No authentication logic.
 */
final class AuthPageContext
{
    /** @return array<string, mixed> variables the theme page extract()s for the shared partials */
    public static function make(?string $requestedMode, ViewErrorBag $errors): array
    {
        $loginTheme = app(ScreenTheme::class)->key('public');
        $themeConfig = config('cmsbackend.screens.themes.'.$loginTheme, []);
        $brandName = Brand::name() ?: data_get($themeConfig, 'brand.name') ?: __('frontend.brand.name');
        $brandLogo = data_get($themeConfig, 'login.logo', config('cmsfrontend.brand.banner'));

        // Login and register share one page; /register (RegisteredUserController::create) passes $authMode = 'register'.
        $canRegister = \Illuminate\Support\Facades\Route::has('register');
        $authMode = ($canRegister && ($requestedMode ?? (request()->routeIs('register') ? 'register' : 'login')) === 'register') ? 'register' : 'login';

        return [
            'loginTheme' => $loginTheme,
            'brandName' => $brandName,
            // Theme tagline first (login.panel_text, then brand.tagline); the editable site description is the last fallback.
            'brandUnit' => (string) (data_get($themeConfig, 'login.panel_text') ?: data_get($themeConfig, 'brand.tagline') ?: Brand::unit()),
            'brandLogo' => $brandLogo,
            'brandFavicon' => data_get($themeConfig, 'login.favicon', $brandLogo),
            'homeUrl' => ModuleUrl::route('cms.trang-chu'),
            'authTransition' => data_get($themeConfig, 'login.transition'),
            'canRegister' => $canRegister,
            'authMode' => $authMode,
            'isRegister' => $authMode === 'register',
            'loginError' => $errors->first('login') ?: $errors->first('password') ?: $errors->first(),
            'pageTitles' => [
                'login' => __('frontend.login.page_title', ['name' => $brandName]),
                'register' => __('frontend.register.page_title', ['name' => $brandName]),
            ],
        ];
    }
}
