<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\NhomVanBan;
use Illuminate\Support\Str;

class NhomVanBanDataProvider extends BaseDataProvider
{
    public function __construct(NhomVanBan $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }
    public function getDsNhomVanBan($filter, $pageSize = 10)
    {
        return NhomVanBan::with(['user'])
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    // $builder->where('lvb_name',  'like', value: "%$filter%")
                    $builder->where('nvb_name',  'like', value: "%$filter%")
                        ->orWhere('nvb_description', 'like', value: "%$filter%");
                }
            })->orderBy('created_at', 'desc')
            ->paginate($pageSize);
    }
    public function create(array $data): NhomVanBan
    {
        return $this->model->create($data);
    }
    public function updateNVB(array $data, NhomVanBan $nhomVanBan): NhomVanBan
    {
        $nhomVanBan->fill($data);
        $nhomVanBan->save();

        return $nhomVanBan;
    }

    public function generateUniqueSlug(string $name, $ignoreId = null): string
    {
        // Bước 1: tạo slug cơ bản
        $slug = Str::slug($name);
        $originalSlug = $slug;

        // Bước 2: kiểm tra trùng, nếu có thì tăng dần hậu tố
        $i = 1;
        while (NhomVanBan::where('nvb_slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('nvb_id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
    public function getNVBBySlug(string $slug)
    {
        return $this->model->where('nvb_slug', $slug)->firstOrFail();
    }
}
