<?php

namespace App\Providers;

use App\Http\Middleware\CheckPermission;
use App\Observers\RoleObserver;
use App\Services\CustomDownloader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Spatie\MediaLibrary\Downloaders\Downloader;
use Spatie\Permission\Models\Role;

use App\Models\SiteVisitStub;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (env('APP_DEBUG') == true) {
            $this->debugbar();
        }
        $this->app->singleton(Downloader::class, CustomDownloader::class);

        // Same construction as the framework's own 'url' binding (the framework's extend() callbacks still apply), with
        // a generator that maps route names to the current public language (see LocalizedUrlGenerator).
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();
            // Laravel 12 rebinds 'routes' to the URL generator (which resolves 'url' again): only publish the collection when it changed.
            if (! $app->bound('routes') || $app->make('routes') !== $routes) {
                $app->instance('routes', $routes);
            }

            return new \App\Support\LocalizedUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });
        Paginator::useBootstrapFive();
        //Paginator::defaultView('bootstrap-5');

        $this->macros();

        // $this->views();

        $this->observe();
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('check.permission', CheckPermission::class);

        // ✅ Ghi nhận truy cập toàn site nếu là request bình thường
        // Không đếm khi chạy dòng lệnh/test (artisan, PHPUnit): chỉ request web thật, tránh ghi vào bảng visits mỗi lần boot.
        if (
            ! $this->app->runningInConsole() &&
            request()->isMethod('GET') &&
            !request()->ajax() &&
            !str_contains(strtolower(request()->userAgent()), 'bot')
        ) {
            visits(new SiteVisitStub())->increment();
        }
    }

    private function macros()
    {
        // Request macro
        Request::macro('getBase64Param', function ($name) {
            $param = $this->$name;
            if ($param) {
                if (is_array($param)) {
                    $params = [];
                    foreach ($param as $each) {
                        $params[] = base64_decode($each);
                    }
                    return $params;
                } else {
                    return base64_decode($param);
                }
            }
        });
    }

    private function views()
    {
        View::addLocation(base_path() . '/' . config('view.theme_path') . '/' . config('view.theme'));
    }

    private function debugbar()
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);
    }

    private function observe()
    {
        Role::observe(RoleObserver::class);
    }
}
