<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\UserTermPermissionDataProvider;

class UserTermPermissionService extends BaseService
{
    protected $dataProvider;

    public function __construct(UserTermPermissionDataProvider $dataProvider)
    {
        parent::__construct($dataProvider);
        $this->dataProvider = $dataProvider;
    }
}
