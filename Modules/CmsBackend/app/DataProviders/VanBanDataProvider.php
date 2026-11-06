<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\VanBan;

class VanBanDataProvider extends BaseDataProvider
{
    public function __construct(VanBan $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }
    public function paginateVB($vbBanHanhTuNgay, $vbBanHanhDenNgay, $lvbId,$nvbId,  $filter, $pageSize = 10, $sortBy = 'vb_id',
         $order = 'desc',)
    {
        return VanBan::with(['user', 'loaiVanBan', 'nhomVanBan'])
            ->where(function ($builder) use ($vbBanHanhTuNgay, $vbBanHanhDenNgay) {
                if ($vbBanHanhTuNgay && $vbBanHanhDenNgay) {
                    $builder->where('vb_ngay_ban_hanh', '>=', $vbBanHanhTuNgay)
                        ->where('vb_ngay_ban_hanh', '<=', $vbBanHanhDenNgay);
                } else if ($vbBanHanhTuNgay) {
                    $builder->where('vb_ngay_ban_hanh', operator: '>=', value: $vbBanHanhTuNgay);
                } else if ($vbBanHanhDenNgay) {
                    $builder->where('vb_ngay_ban_hanh', '<=', value: $vbBanHanhDenNgay);
                }
            })
            ->where(function ($builder) use ($lvbId) {
                if ($lvbId > 0) {
                    $builder->whereLvbId($lvbId);
                }
            })
            ->where(function ($builder) use ($nvbId) {
                if ($nvbId > 0) {
                    $builder->whereNvbId($nvbId);
                }
            })
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('vb_so_hieu', 'like', "%$filter%")
                        ->orWhere('vb_trich_yeu', 'like', "%$filter%");
                }
            })-> orderBy($sortBy, $order)->paginate($pageSize);

    }

    public function create(array $data): VanBan
    {
        $vanBan = new VanBan;
        $vanBan->fill($data);
        $vanBan->save();
        return $vanBan;
    }
    public function handleFiles(VanBan $vanBan, $filesInput)
    {
        if ($filesInput) {
            $files = json_decode($filesInput);
            foreach ($files as $file) {
                $vanBan->addMediaFromUrl($file)->toMediaCollection('fileDinhKem');
            }
        }
    }

    public function updateVB(VanBan $vanBan, array $data): VanBan
    {
        $vanBan->fill($data);
        $vanBan->save();
        return $vanBan;
    }
     public function syncFiles(VanBan $vanBan, $filesInput)
    {
        $files = json_decode($filesInput ?? '[]');
        $allMedia = $vanBan->getMedia('fileDinhKem');
        $mediaToKeep = $allMedia->filter(fn($media) => in_array($media->getUrl(), $files));
        $vanBan->clearMediaCollectionExcept('fileDinhKem', $mediaToKeep->toArray());

        foreach ($files as $file) {
            $existingMedia = $mediaToKeep->first(fn($media) => $media->getUrl() === $file);
            if (!$existingMedia) {
                $vanBan->addMediaFromUrl($file)->toMediaCollection('fileDinhKem');
            }
        }
    }

    public function getFileUrls(VanBan $vanBan)
    {
        return $vanBan->getMedia('fileDinhKem')->map(fn($media) => $media->getUrl())->values();
    }
}
