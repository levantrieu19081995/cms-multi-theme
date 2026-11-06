<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\DoiTac;

class DoiTacDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new DoiTac());
    }
   public function getShowOnHome()
{
    return $this->model->where('show_home_page', 1)
        ->orderBy('id', 'desc')
        ->get();
}
}