<?php

namespace Modules\CmsFrontend\Services;

use Modules\CmsFrontend\DataProviders\BannerDataProvider;

class BannerService
{
    protected BannerDataProvider $bannerDataProvider;

    public function __construct(BannerDataProvider $bannerDataProvider)
    {
        $this->bannerDataProvider = $bannerDataProvider;
    }
    public function getDanhSachBannerWithCache() {
        return $this->bannerDataProvider->getDanhSachBannerWithCache();
    }
}
