<?php

namespace Modules\CmsFrontend\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CmsBackend\Services\HoiDapService;
use Modules\CmsBackend\DataProviders\HoiDapDataProvider;
use Modules\CmsFrontend\Models\Question;

class HoiDapServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(HoiDapService::class, function ($app) {
            return new HoiDapService(new HoiDapDataProvider());
        });
    }
}
