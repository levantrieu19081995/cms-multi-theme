<?php

namespace Modules\CmsFrontend\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'CmsFrontend';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
        require base_path('Modules/CmsFrontend/routes/breadcrumbs.php');
        // Khảo sát công khai: đọc 60 lượt/phút/IP; bắt đầu + gửi 10 lượt/phút cho mỗi IP và khảo sát, và 60 lượt/giờ/IP.
        \Illuminate\Support\Facades\RateLimiter::for('survey-read', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('survey-read|'.$request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('survey-write', fn (\Illuminate\Http\Request $request) => [
            \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('survey-write|'.$request->ip().'|'.$request->route('slug')),
            \Illuminate\Cache\RateLimiting\Limit::perHour(60)->by('survey-write-hour|'.$request->ip()),
        ]);
        // Biểu mẫu liên hệ: 5 lần gửi / 10 phút / IP, kể cả bản ngôn ngữ (/en/lien-he). Quá giới hạn: quay lại form với thông báo gọn.
        \Illuminate\Support\Facades\RateLimiter::for('contact-write', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, 5)
            ->by('contact-write|'.$request->ip())
            ->response(fn (\Illuminate\Http\Request $request, array $headers) => $request->expectsJson()
                ? response()->json(['message' => __('messages.contact_throttled')], 429, $headers)
                : redirect()->back()->withInput($request->except('website'))->with('error', __('messages.contact_throttled'))->withHeaders($headers)));
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
        // Avoid re-registering shared routes over the existing project routes.
        $this->map();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        $admin = trim((string) config('cmsbackend.route_prefix', 'cms'), '/');
        $public = trim((string) config('cmsbackend.public_route_prefix', ''), '/');

        // Administration side of the module (/cms): sign-in, overview, settings, plus the old-address redirects.
        Route::middleware('web')->prefix($admin)->name('cms.')->group(module_path($this->name, '/routes/web.php'));
        Route::middleware('web')->prefix($admin)->group(module_path($this->name, '/routes/compat.php'));

        // Public website: the root of the domain, with the same route names (cms.*) every theme already calls.
        Route::middleware('web')->prefix($public)->name('cms.')->group(module_path($this->name, '/routes/public.php'));

        // The shared "/dashboard" address of the framework leads to the administration overview.
        $enabled = array_values(array_map(fn ($module) => $module->getName(), \Nwidart\Modules\Facades\Module::allEnabled()));
        sort($enabled);
        if ($enabled === ['CmsBackend', 'CmsFrontend'] && $admin !== '') {
            Route::middleware('web')->group(function () use ($admin) {
                Route::redirect('/dashboard', '/'.$admin)->middleware('auth')->name('dashboard');
            });
        }
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
