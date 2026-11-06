<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\LoaiVanBanDataProvider;

class LoaiVanBanService extends BaseService
{
    protected LoaiVanBanDataProvider $loaiVanBanDataProvider;

    public function __construct(LoaiVanBanDataProvider $loaiVanBanDataProvider)
    {
        parent::__construct($loaiVanBanDataProvider);
        $this->loaiVanBanDataProvider = $loaiVanBanDataProvider;
    }

    public function getDsLoaiVanBan(string $filter, ?int $pageSize = 10)
    {
        return $this->loaiVanBanDataProvider->getDsLoaiVanBan($filter, $pageSize);
    }
}
