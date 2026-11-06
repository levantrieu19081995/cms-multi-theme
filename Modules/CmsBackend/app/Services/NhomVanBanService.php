<?php

namespace Modules\CmsBackend\Services;

use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\DataProviders\NhomVanBanDataProvider;
use Modules\CmsBackend\Models\NhomVanBan;

class NhomVanBanService extends BaseService
{
    protected NhomVanBanDataProvider $nhomVanBanDataProvider;

    public function __construct(NhomVanBanDataProvider $nhomVanBanDataProvider)
    {
        parent::__construct($nhomVanBanDataProvider);
        $this->nhomVanBanDataProvider = $nhomVanBanDataProvider;
    }

    public function getDsNhomVanBan(string $filter, ?int $pageSize = 10)
    {
        return $this->nhomVanBanDataProvider->getDsNhomVanBan($filter, $pageSize);
    }

    public function create(array $data)
    {
        $data['user_id'] = Auth::user()->id;
        $data['nvb_slug'] = $this->nhomVanBanDataProvider->generateUniqueSlug($data['nvb_name']);

        return $this->nhomVanBanDataProvider->create($data);
    }

    public function updateNVB(array $data, NhomVanBan $nhomVanBan)
    {
        // Cập nhật slug mới nếu tên thay đổi
        if (isset($data['nvb_name'])) {
            $data['nvb_slug'] = $this->nhomVanBanDataProvider->generateUniqueSlug($data['nvb_name'], $nhomVanBan->id);
        }

        return $this->nhomVanBanDataProvider->updateNVB($data, $nhomVanBan);
    }

    public function getNVBBySlug(string $slug)
    {
        return $this->nhomVanBanDataProvider->getNVBBySlug($slug);
    }
}
