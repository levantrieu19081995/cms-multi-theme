<?php

namespace Modules\CmsBackend\DataProviders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Modules\CmsFrontend\Models\LienHe;

class LienHeDataProvider
{
    /** Danh sách đang hoạt động hoặc thùng rác; tìm theo tiêu đề, họ tên, email, điện thoại. */
    public function paginate(string $filter = '', bool $trashed = false, int $perPage = 10): LengthAwarePaginator
    {
        $query = $trashed ? LienHe::onlyTrashed()->orderByDesc('deleted_at') : LienHe::query();
        if ($filter !== '') {
            $like = '%'.addcslashes($filter, '\%_').'%';
            $query->where(fn ($q) => $q->where('subject', 'like', $like)->orWhere('name', 'like', $like)
                ->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function find(int $id): LienHe
    {
        return LienHe::withTrashed()->findOrFail($id);
    }

    /** Số liên hệ theo tháng, đúng thứ tự thời gian (không gồm bản ghi trong thùng rác). x = "mm-YYYY". */
    public function thongKeTheoThang(?int $year = null): array
    {
        $months = LienHe::query()->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->pluck('created_at')
            ->filter()
            ->countBy(fn ($createdAt) => Carbon::parse($createdAt)->format('Y-m'))
            ->sortKeys();

        return $months->map(fn (int $count, string $ym) => ['x' => substr($ym, 5, 2).'-'.substr($ym, 0, 4), 'y' => $count])->values()->all();
    }
}
