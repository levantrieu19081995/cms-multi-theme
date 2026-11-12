<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CmsBackendServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'CmsBackend';

    protected string $nameLower = 'cmsbackend';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        config(['permission.cache.key' => 'spatie.permission.cache.cms.'.config('database.connections.'.config('database.default').'.database')]);
        config(['filesystems.disks.cms_media' => ['driver' => 'local', 'root' => public_path('media/cms'), 'url' => rtrim(config('app.url'), '/').'/media/cms', 'visibility' => 'public', 'throw' => true]]);
        $this->registerViews();
        // A new post belongs to the current project (side table post_projects; never breaks saving the post).
        \App\Models\Post::created(fn (\App\Models\Post $post) => \Modules\CmsBackend\Support\ProjectPostAssigner::assign($post));
        \Illuminate\Support\Facades\View::share('screenTheme', app(\Modules\CmsBackend\Services\ScreenTheme::class));
        \App\Models\Taxonomy::addGlobalScope('cms-admin-valid-term', function ($builder) {
            if (\Modules\CmsBackend\Support\AdminTheme::request(request())) {
                $builder->whereHas('term');
            }
        });
        // Replace only a selected CMS request's shared layout, without changing core/theme files.
        \Illuminate\Support\Facades\View::creator(['layouts.layoutMaster', 'layouts/layoutMaster'], function ($view) {
            if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.compat.layoutMaster'));
            }
        });
        \Illuminate\Support\Facades\View::creator(['layouts.paginate', 'layouts/paginate'], function ($view) {
            if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.components.shared.layouts.paginate'));
            }
        });
        \Illuminate\Support\Facades\View::creator('_partials.binh-luan.table-binh-luan', function ($view) {
            if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.compat.comment-table'));
            }
        });
        \Illuminate\Support\Facades\View::creator('cmsbackend::screens.components.layouts.post-layout.create_edit_layout', function ($view) {
            if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.components.layouts.post-layout.create_edit_layout'));
            }
        });
        if (config('cmsbackend.register_migrations', false)) {
            $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        }
        \Illuminate\Support\Facades\View::composer('cmsbackend::*', function ($view) {
            $menu = new \Modules\CmsBackend\Support\ProjectMenu($this->app);
            $view->with('cmsMenuData', $menu->forUser(auth()->user()));
        });
        $enabled = array_values(array_map(fn ($module) => $module->getName(), \Nwidart\Modules\Facades\Module::allEnabled()));
        sort($enabled);
        if ($enabled === ['CmsBackend', 'CmsFrontend']) {
            // Core video templates still name the source view namespace. This alias exists only in the CMS-only checkout.
            \Illuminate\Support\Facades\View::addNamespace('cmsbackend', module_path($this->name, 'resources/views'));
            \Illuminate\Support\Facades\View::creator('pages.video.index', function ($view) {
                if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                    $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.compat.video-index'));
                }
            });
            // Keep the shared file manager untouched. CMS receives its own full-size wrapper and
            // an iframe-only skin selected by an explicit query flag from that wrapper.
            \Illuminate\Support\Facades\View::creator('pages.file.index', function ($view) {
                if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                    $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.compat.file-index'));
                }
            });
            \Illuminate\Support\Facades\View::creator('laravel-filemanager::index', function ($view) {
                if (request()->user() && request()->is('laravel-filemanager') && request()->boolean('cms')) {
                    $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.file-manager.index'));
                }
            });
            // "Cài đặt chung" (shared setting.general): CMS view with contact/social/map/homepage/theme fields,
            // saved by the cms.settings.common middleware before the untouched core update.
            \Illuminate\Support\Facades\View::creator('pages.setting.general', function ($view) {
                if (\Modules\CmsBackend\Support\AdminTheme::sharedRequest(request())) {
                    $view->setPath(app(\Modules\CmsBackend\Services\ScreenTheme::class)->pathForView('cmsbackend::screens.pages.compat.setting-general'));
                    $view->with('cmsCommon', \Modules\CmsBackend\Support\CommonSettings::values());
                }
            });
            app('router')->aliasMiddleware('cms.settings.common', \Modules\CmsBackend\Http\Middleware\SaveCommonSettings::class);
            $this->app->booted(function () {
                app('router')->getRoutes()->getByName('setting.general.update')?->middleware('cms.settings.common');
            });
            app('router')->aliasMiddleware('cms.editorial.legacy', \Modules\CmsBackend\Http\Middleware\GuardEditorialLegacyRoutes::class);
            $this->app->booted(function () {
                foreach (app('router')->getRoutes() as $route) {
                    if (str_starts_with($route->getName() ?? '', 'bai-viet.') || in_array($route->uri(), ['api/update-homepage', 'api/update-commentpage', trim((string) config('cmsbackend.route_prefix', 'cms'), '/').'/api/update-homepage', trim((string) config('cmsbackend.route_prefix', 'cms'), '/').'/api/update-commentpage'], true)) {
                        $route->middleware('cms.editorial.legacy');
                    }
                }
            });
            config(['filesystems.disks.cms_files' => ['driver' => 'local', 'root' => storage_path('app/public/cms-userfiles'), 'url' => rtrim(config('app.url'), '/').'/storage/cms-userfiles', 'visibility' => 'public', 'throw' => true], 'lfm.disk' => 'cms_files']);
            $this->app->booted(function () {
                foreach (app('router')->getRoutes() as $route) {
                    $name = $route->getName();
                    if ($name && ! str_starts_with($name, 'cms.')
                        && ! \Diglactic\Breadcrumbs\Breadcrumbs::exists($name)
                        && \Diglactic\Breadcrumbs\Breadcrumbs::exists('cms.'.$name)) {
                        \Diglactic\Breadcrumbs\Breadcrumbs::for($name, function ($trail, ...$parameters) use ($name) {
                            $trail->parent('cms.'.$name, ...$parameters);
                        });
                    }
                }
            });
            // Shared user/role/file screens use the selected project's navigation.
            \Illuminate\Support\Facades\View::composer('*', function ($view) {
                $menu = new \Modules\CmsBackend\Support\ProjectMenu($this->app);
                $view->with('menuData', $menu->forUser(auth()->user()));
            });
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(\Modules\CmsBackend\Services\ScreenTheme::class);
        // Feature Registry: danh sách chức năng (cache) + trạng thái bật/tắt của bản triển khai. Một nguồn duy nhất.
        $this->app->singleton(\Modules\CmsBackend\Features\FeatureRegistry::class);
        // Permission availability per deployment (Developer Settings): one decision point, read once per request.
        $this->app->singleton(\Modules\CmsBackend\Services\Developer\PermissionManifest::class);
        $this->app->singleton(\Modules\CmsBackend\Services\Developer\PermissionAvailability::class);
        $this->app->singleton(\Modules\CmsBackend\Services\Developer\DeveloperAccess::class);
        // Multi-channel publishing: adapters (Facebook, Zalo) are registered here by Phase 2; Phase 1 has none.
        $this->app->singleton(\Modules\CmsBackend\Services\Social\SocialChannelRegistry::class);
        // Adapters are registered only when the settings of their platform are complete (config/social.php).
        $this->app->resolving(\Modules\CmsBackend\Services\Social\SocialChannelRegistry::class, function ($registry, $app) {
            if ($app->make(\Modules\CmsBackend\Services\Social\Facebook\FacebookSettings::class)->configured()) {
                $registry->register(\Modules\CmsBackend\Enums\Social\SocialChannel::FACEBOOK, \Modules\CmsBackend\Services\Social\Facebook\FacebookChannelAdapter::class);
            }
            // The Zalo adapter likewise only when the Zalo App settings (ZALO_*) are complete.
            if ($app->make(\Modules\CmsBackend\Services\Social\Zalo\ZaloSettings::class)->configured()) {
                $registry->register(\Modules\CmsBackend\Enums\Social\SocialChannel::ZALO, \Modules\CmsBackend\Services\Social\Zalo\ZaloChannelAdapter::class);
            }
        });
        $this->app->extend('view.finder', function ($finder, $app) {
            return new \Modules\CmsBackend\Support\ScreenViewFinder($app['files'], $finder, $app->make(\Modules\CmsBackend\Services\ScreenTheme::class));
        });
        $this->app->afterResolving('view', function ($factory, $app): void {
            $factory->setFinder($app['view.finder']);
        });
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\CmsBackend\Console\InstallEditorialWorkflow::class,
            \Modules\CmsBackend\Console\RepairEditorialPermissions::class,
            \Modules\CmsBackend\Console\InstallContractMenu::class,
            \Modules\CmsBackend\Console\InstallHomeBlocks::class,
            \Modules\CmsBackend\Console\InstallLegacyMedia::class,
            \Modules\CmsBackend\Console\AssignPostProject::class,
            \Modules\CmsBackend\Console\InstallCareerStarter::class,
            \Modules\CmsBackend\Console\SyncFeaturePermissions::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
