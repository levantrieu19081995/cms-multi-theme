<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\HoiDapDataProvider;

class HoiDapService extends BaseService
{
    protected $dataProvider;

    public function __construct(HoiDapDataProvider $dataProvider)
    {
        parent::__construct($dataProvider);
        $this->dataProvider = $dataProvider;
    }

    public function getAll()
    {
        return $this->dataProvider->getAll();
    }

    public function getApproved()
    {
        return $this->dataProvider->getApproved();
    }

    public function store(array $data)
    {
        return $this->dataProvider->store($data);
    }

    public function get($id)
    {
        return $this->dataProvider->findOrFail($id);
    }

    public function answer(int $id, string $answer, int $adminId)
    {
        return $this->dataProvider->answerQuestion($id, $answer, $adminId);
    }

    public function approve(int $id)
    {
        return $this->dataProvider->approveQuestion($id);
    }
}
