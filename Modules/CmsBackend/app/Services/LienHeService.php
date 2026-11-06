<?php

namespace Modules\CmsBackend\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\CmsBackend\DataProviders\LienHeDataProvider;
use Modules\CmsFrontend\Models\LienHe;

class LienHeService
{
    public function __construct(protected LienHeDataProvider $dataProvider) {}

    public function paginate(string $filter = '', bool $trashed = false): LengthAwarePaginator
    {
        return $this->dataProvider->paginate($filter, $trashed);
    }

    public function find(int $id): LienHe
    {
        return $this->dataProvider->find($id);
    }

    public function destroy(int $id): void
    {
        $this->dataProvider->find($id)->delete();
    }

    public function restore(int $id): void
    {
        $this->dataProvider->find($id)->restore();
    }

    /** Chỉ xóa vĩnh viễn bản ghi đã nằm trong thùng rác. */
    public function purge(int $id): bool
    {
        $record = $this->dataProvider->find($id);
        if (! $record->trashed()) {
            return false;
        }
        $record->forceDelete();

        return true;
    }

    public function thongKeTheoThang(?int $year = null): array
    {
        return $this->dataProvider->thongKeTheoThang($year);
    }
}
