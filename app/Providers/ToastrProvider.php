<?php

namespace App\Providers;

use App\Helpers\ToastrHelpers;
use Illuminate\Support\ServiceProvider;

class ToastrProvider extends ServiceProvider
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
      $this->app->singleton('toastr', function () {
        return new ToastrHelpers();
      });
    }
}
