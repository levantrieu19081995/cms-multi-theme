<?php

namespace Modules\CmsFrontend\Services;

use Modules\CmsFrontend\Services\BaseService;
use Modules\CmsFrontend\DataProviders\HoiDapDataProvider;

class HoiDapService extends BaseService
{
    protected $dataProvider;

    public function __construct(HoiDapDataProvider $dataProvider)
    {
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

    public function delete($id)
    {
        return $this->dataProvider->delete($id);
    }
}
