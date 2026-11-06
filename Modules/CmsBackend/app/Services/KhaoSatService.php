<?php

namespace Modules\CmsBackend\Services;

use Modules\CmsBackend\DataProviders\KhaoSatDataProvider;

class KhaoSatService extends BaseService
{
    public function __construct(KhaoSatDataProvider $dataProvider)
    {
        parent::__construct($dataProvider);
    }

    public function saveSurvey($data)
    {
        $user = $this->dataProvider->createUser($data);
        $this->dataProvider->createKetQua($user->ksu_id, $data['ks_id'], $data['answers']);

        return $user;
    }

    public function getUsersWithFilter($filters)
    {
        return $this->dataProvider->getUsersWithFilter($filters);
    }

    public function getUserSurveyDetail($ksu_id)
    {
        return $this->dataProvider->getUserSurveyDetail($ksu_id);
    }
}
