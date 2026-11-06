<?php

namespace App\Providers;

use App\Helpers\AlertHelpers;
use Illuminate\Support\ServiceProvider;

class AlertProvider extends ServiceProvider
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
      $this->app->singleton('alert', function () {
        return new AlertHelpers();
      });
    }
}
