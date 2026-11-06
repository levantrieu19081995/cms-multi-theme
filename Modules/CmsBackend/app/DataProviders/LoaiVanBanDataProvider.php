<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\LoaiVanBan;

class LoaiVanBanDataProvider extends BaseDataProvider
{
    public function __construct(LoaiVanBan $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }
    public function getDsLoaiVanBan($filter, $pageSize = 10)
    {
        return LoaiVanBan::with(['user'])
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('lvb_ma',  'like', "%$filter%")
                        ->orWhere('lvb_ten', 'like', value: "%$filter%");
                }
            })->orderBy('created_at', 'desc')
            ->paginate($pageSize);
    }
}
