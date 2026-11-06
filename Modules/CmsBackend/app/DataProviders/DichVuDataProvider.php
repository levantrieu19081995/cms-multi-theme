<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\DichVu;

class DichVuDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new DichVu());
    }
    public function savePostBanner($dvId, $title, $link, $status)
    {
        return $this->model->updateOrCreate(
            ['id' => $dvId],
            [
                'title' => $title,
                'link' => $link,
                'status' => $status,
            ]
        );
    }
    /**
     * Phân trang dịch vụ theo filter (title, link) và status.
     */
    public function getAll(array $where = [], array $with = [])
    {
        $query = $this->model;

        // Eager load quan hệ nếu có
        if (!empty($with)) {
            $query = $query->with($with);
        }

        // Hỗ trợ điều kiện mảng [field, operator, value]
        foreach ($where as $key => $condition) {
            if (is_array($condition)) {
                $query = $query->where(...$condition); // dùng spread
            } else {
                $query = $query->where($key, $condition);
            }
        }

        return $query->get();
    }
}
