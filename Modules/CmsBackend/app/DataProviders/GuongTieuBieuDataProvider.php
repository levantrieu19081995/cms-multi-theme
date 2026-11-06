<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\GuongTieuBieu;

class GuongTieuBieuDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new GuongTieuBieu());
    }
    public function getShowOnHome()
    {
        return $this->model->where('show_home_page', 1)
            ->orderBy('id', 'desc')
            ->get();
    }
}
