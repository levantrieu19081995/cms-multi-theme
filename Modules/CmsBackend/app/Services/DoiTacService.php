<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\DoiTacDataProvider;

class DoiTacService extends BaseService
{
    protected DoiTacDataProvider $doiTacDataProvider;

    public function __construct(DoiTacDataProvider $doiTacDataProvider)
    {
        parent::__construct($doiTacDataProvider);
        $this->doiTacDataProvider = $doiTacDataProvider;
    }

    public function getShowOnHome()
    {
        return $this->doiTacDataProvider->getShowOnHome();
    }
}
