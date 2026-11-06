<?php

namespace Modules\CmsBackend\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Http\Middleware\CheckPermission;
use Closure;
use Illuminate\Http\Request;
use Modules\CmsBackend\Support\AdminTheme;
use Modules\CmsBackend\Support\CommonSettings;

/**
 * Attached to the shared route setting.general.update (CMS-only checkout): saves the Khuyến nông fields shown
 * on "Cài đặt chung" (contact, social links, map), then lets the core controller save its own.
 * Route middleware runs before the controller's own permission middleware, so the same "Cài đặt chung"
 * permission is checked here first; without it nothing is saved and the core controller refuses as usual.
 */
class SaveCommonSettings
{
    public function handle(Request $request, Closure $next)
    {
        if (AdminTheme::sharedRequest($request) && $this->allowed($request)) {
            CommonSettings::save($request);
        }
        return $next($request);
    }

    private function allowed(Request $request): bool
    {
        $probe = app(CheckPermission::class)->handle($request, fn () => response('', 204), PermissionsEnum::ABOUT_MANAGER->label());
        return $probe->getStatusCode() === 204;
    }
}
