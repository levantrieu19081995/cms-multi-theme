<?php

namespace Modules\CmsBackend\Services;

use App\DataProviders\PostDataProvider;

class PostService extends BaseService
{
    protected $dataProvider;

    public function __construct(PostDataProvider $dataProvider)
    {
        parent::__construct($dataProvider);
        $this->dataProvider = $dataProvider;
    }

    public function getPostCountByCategoryMonthly($year = null)
    {
        return $this->dataProvider->getPostCountByCategoryMonthly($year);
    }

    public function totalPostsByYear($year = null)
    {
        return $this->dataProvider->totalPostByYear($year);
    }

    public function totalPostByMonth($monthYear = null)
    {
        return $this->dataProvider->totalPostByMonth($monthYear);
    }
}
