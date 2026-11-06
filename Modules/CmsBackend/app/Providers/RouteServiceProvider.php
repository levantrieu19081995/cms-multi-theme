<?php

namespace Modules\CmsBackend\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Modules\CmsBackend\Http\Middleware\CDNCheckPermission;
use Modules\CmsBackend\Http\Middleware\Authenticate;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'CmsBackend';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
        require base_path('Modules/CmsBackend/routes/breadcrumbs.php');
        app('router')->aliasMiddleware('cms.check.permission', CDNCheckPermission::class);
        app('router')->aliasMiddleware('cms.auth', Authenticate::class);
        app('router')->aliasMiddleware('cms.feature', \Modules\CmsBackend\Http\Middleware\RequireFeature::class);
        app('router')->aliasMiddleware('cms.developer', \Modules\CmsBackend\Http\Middleware\DeveloperGate::class);
        app('router')->aliasMiddleware('cms.developer.enabled', \Modules\CmsBackend\Http\Middleware\DeveloperEnabled::class);
        // 404 for a disabled feature must come before the sign-in redirect.
        app(\Illuminate\Contracts\Http\Kernel::class)->prependToMiddlewarePriority(\Modules\CmsBackend\Http\Middleware\DeveloperEnabled::class);
        // Developer password attempts: 5 a minute per address, counted whether right or wrong.
        \Illuminate\Support\Facades\RateLimiter::for('developer-unlock', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('developer|'.$request->ip()));
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function loadRoutes(): void
    {
        // The application routes are already registered by the main provider.
        $this->map();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->prefix(config('cmsbackend.route_prefix', 'cms'))->name('cms.')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix(trim(config('cmsbackend.route_prefix', 'cms') . '/api', '/'))->name('cms.api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
