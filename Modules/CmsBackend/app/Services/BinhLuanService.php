<?php

namespace Modules\CmsBackend\Services;

use App\DataProviders\CommentDataProvider;

// CHÚ Ý: use model đúng namespace

class BinhLuanService extends BaseService
{
    protected $dataProvider;

    public function __construct(CommentDataProvider $dataProvider)
    {
        parent::__construct($dataProvider);
        $this->dataProvider = $dataProvider;
    }

    public function thongKeTheoThang(?int $year = null): array
    {
        return $this->dataProvider->thongKeTheoThang($year);
    }
}
