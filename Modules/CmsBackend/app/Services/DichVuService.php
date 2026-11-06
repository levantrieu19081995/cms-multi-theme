<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\DichVuDataProvider;

class DichVuService extends BaseService
{
    protected DichVuDataProvider $dichVuDataProvider;

    public function __construct(DichVuDataProvider $dichVuDataProvider)
    {
        parent::__construct($dichVuDataProvider);
        $this->dichVuDataProvider = $dichVuDataProvider;
    }

    public function getAllActive()
    {
        return $this->dichVuDataProvider->getAll([
            'status' => 1,
            ['deleted_at', '=', null], // Hợp lệ rồi
        ]);
    }
}
