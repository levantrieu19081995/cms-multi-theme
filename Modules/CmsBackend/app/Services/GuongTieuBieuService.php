<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\GuongTieuBieuDataProvider;

class GuongTieuBieuService extends BaseService
{
    protected GuongTieuBieuDataProvider $guongTieuBieuDataProvider;

    public function __construct(GuongTieuBieuDataProvider $guongTieuBieuDataProvider)
    {
        parent::__construct($guongTieuBieuDataProvider);
        $this->guongTieuBieuDataProvider = $guongTieuBieuDataProvider;
    }

    public function getShowOnHome()
    {
        return $this->guongTieuBieuDataProvider->getShowOnHome();
    }
}
