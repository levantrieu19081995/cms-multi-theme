<?php

namespace App\DataProviders;

use App\Models\Posts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Taxonomy;
use App\Models\Terms;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Models\Comment;

class CommentDataProvider
{

    public function paginate(string $filter, int $pageSize = 20): LengthAwarePaginator
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $comments = Comment::with('post', 'user')
            ->where('content', 'like', "%$filter%")
            ->whereHas('post')
            ->orderByDesc('created_at')
            ->paginate($pageSize);

        return $comments;
    }
    public function thongKeTheoThang(?int $year = null): array
    {
        $query = Comment::selectRaw('DATE_FORMAT(created_at, "%m-%Y") as month, COUNT(*) as count');

        // Nếu có lọc theo năm
        if ($year) {
            $query->whereYear('created_at', $year);
        }

        return $query
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'x' => $item->month,
                    'y' => $item->count
                ];
            })
            ->toArray();
    }
}
