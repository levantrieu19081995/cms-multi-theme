<?php

namespace Modules\CmsBackend\Services;

use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\DataProviders\MarqueeDataProvider;
use Modules\CmsBackend\Models\Marquee;

class MarqueeService extends BaseService
{
    protected MarqueeDataProvider $marqueeDataProvider;

    public function __construct(MarqueeDataProvider $marqueeDataProvider)
    {
        parent::__construct($marqueeDataProvider);
        $this->marqueeDataProvider = $marqueeDataProvider;
    }

    public function getDsMarquee(string $filter, ?int $pageSize = 10)
    {
        return $this->marqueeDataProvider->getDsMarquee($filter, $pageSize);
    }

    public function create(array $data)
    {

        $data['user_id'] = Auth::user()->id;

        return $this->marqueeDataProvider->create($data);
    }

    public function updateMarquee(array $data, Marquee $marquee)
    {
        // Cập nhật slug mới nếu tên thay đổ
        return $this->marqueeDataProvider->updateMarquee($data, $marquee);
    }

    public function approveMarquee(int $id)
    {
        return $this->marqueeDataProvider->approveMarquee($id);
    }
}
