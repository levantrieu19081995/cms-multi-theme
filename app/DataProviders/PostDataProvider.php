<?php

namespace App\DataProviders;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Terms;
use App\Enums\Taxonomy as EnumsTaxonomy;


use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PostDataProvider extends AbstractPostDataProvider
{
    // public function __construct()
    // {
    //     parent::__construct();
    // }
    public function getPostCountByCategoryMonthly(?int $year = null): array
    {
        // Truy vấn chính (không cần thay đổi nhiều)
        $rawResults = DB::table('posts')
            ->leftJoin('term_relationships', function ($join) {
                $join->on('posts.id', '=', 'term_relationships.object_id')
                    ->where('term_relationships.object_type', '=', \App\Models\Post::class);
            })
            ->leftJoin('term_taxonomy', function ($join) {
                $join->on('term_relationships.term_taxonomy_id', '=', 'term_taxonomy.term_taxonomy_id')
                    ->where('term_taxonomy.taxonomy', '=', 'category');
            })
            ->leftJoin('terms', 'term_taxonomy.term_id', '=', 'terms.term_id')
            ->selectRaw("
        DATE_FORMAT(posts.created_at, '%m-%Y') AS month,
        COALESCE(terms.name, 'Không có chuyên mục') AS category,
        COUNT(DISTINCT posts.id) AS post_count
    ")
            ->whereNull('posts.deleted_at')
            ->where('post_type', 'post')
            ->where(function ($builder) use ($year) {
                //Điều kiện cho tác giả
                if ($year) {
                    $builder->whereYear('posts.created_at', $year);
                }})
            ->groupBy('month', 'category')
            ->orderBy(DB::raw("STR_TO_DATE(month, '%m-%Y')"))
            ->get();


        // Build lại kết quả
        $results = [];
        foreach ($rawResults as $row) {
            $category = $row->category;
            $month = $row->month;
            $count = $row->post_count;

            // Gộp dữ liệu
            $results[$month][$category] = $count;
        }

        return $results;
    }

    public function totalPostByYear(?int $year = null): int{
        return Post::whereNull('deleted_at')
            ->where('post_type', 'post')
            ->where(function ($builder) use ($year) {
                //Điều kiện cho tác giả
                if ($year) {
                    $builder->whereYear('created_at', $year);
                }
            })->count();
    }

    public function totalPostByMonth(string $monthYear): int
    {
        // Tách chuỗi "07-2025" thành mảng [07, 2025]
        [$month, $year] = explode('-', $monthYear);

        return Post::whereNull('deleted_at')
            ->where('post_type', 'post')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
    }

}
