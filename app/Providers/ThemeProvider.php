<?php

namespace App\Providers;

use App\Helpers\ThemeHelpers;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ThemeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
      $this->app->singleton('theme', function () {
          return new ThemeHelpers();
      });

      $themeComponentPath = base_path(app('theme')->getThemePath() . '/components');
      Blade::anonymousComponentPath($themeComponentPath, 'theme');
    }
}
