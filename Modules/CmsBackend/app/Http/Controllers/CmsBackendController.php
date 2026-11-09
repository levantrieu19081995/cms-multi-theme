<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use Modules\CmsBackend\Services\LienHeService;
use Modules\CmsBackend\Services\KhaoSatService;
use App\Models\Comment;
use App\Enums\PostStatus;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\CmsBackend\Services\BinhLuanService;
use Modules\CmsBackend\Services\PostService;

class CmsBackendController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $lienHeService;
    protected $khaoSatService;
    protected $postService;
    protected $binhLuanService;
    public function __construct(LienHeService $lienHeService, KhaoSatService $khaoSatService, PostService $postService, BinhLuanService $binhLuanService)
    {
        $this->lienHeService = $lienHeService;
        $this->khaoSatService = $khaoSatService;
        $this->postService = $postService;
        $this->binhLuanService = $binhLuanService;
    }

   public function index(Request $request)
    {
        $year = is_numeric($request->year) ? (int) $request->year : null;

        [$postMonths, $postCategories, $postDataByCategory, $totalPosts] = $this->getPostStats($year);
        [$postStatusByCategory, $postStatusByMonth] = $this->getPostStatusStats($year);
        [$timelineData, $soLienHe] = $this->getLienHeStats($year);
        $timelineBinhLuan = $this->getBinhLuanStats($year);
        [$careerCategories, $careerSeries, $totalKhaoSat] = $this->getKhaoSatStats($year);

        $careerTitle = __('admin.ui.thong_ke_khao_sat_dinh_huong_nghe_nghiep');
        $updatedAt = now();

        return view('cmsbackend::screens.pages.index', compact(
            'careerCategories',
            'careerSeries',
            'totalKhaoSat',
            'careerTitle',
            'postMonths',
            'postCategories',
            'postDataByCategory',
            'postStatusByCategory',
            'postStatusByMonth',
            'timelineData',
            'soLienHe',
            'timelineBinhLuan',
            'updatedAt',
            'totalPosts',
        ));
    }
    private function getPostStats($year): array
    {
        $postCategoryMonthlyStats = $this->postService->getPostCountByCategoryMonthly($year);
        $postCategories = [];
        $postMonths = [];
        $postDataByCategory = [];

        foreach ($postCategoryMonthlyStats as $month => $categories) {
            // $postDataByCategory['Tổng số bài viết'][$month] = $this->postService->totalPostByMonth($month) ?? null;
            if (!in_array($month, $postMonths)) {
                $postMonths[] = $month;
            }
            foreach ($categories as $category => $count) {
                if (!in_array($category, $postCategories)) {
                    $postCategories[] = $category;
                }
                if (!isset($postDataByCategory[$category])) {
                    $postDataByCategory[$category] = [];
                }
                $postDataByCategory[$category][$month] = $count; // Chuyển đổi 0 thành null để tránh hiển thị cột với giá trị 0
            }
        }
        $allMonths = [];
        foreach ($postDataByCategory as $categoryData) {
            foreach ($categoryData as $month => $value) {
                $allMonths[$month] = true;
            }
        }
        $allMonths = array_keys($allMonths);
        usort($allMonths, static function (string $left, string $right): int {
            return Carbon::createFromFormat('m-Y', $left)->timestamp <=> Carbon::createFromFormat('m-Y', $right)->timestamp;
        });
        $postMonths = $allMonths;

        // 2. Chuyển đổi sang định dạng ApexCharts
        $seriesData = [];

        foreach ($postDataByCategory as $category => $monthData) {
            $data = [];
            foreach ($allMonths as $month) {
                $data[] = $monthData[$month] ?? null;
            }

            $seriesData[] = [
                'name' => $category,
                'data' => $data
            ];
        }
        $postDataByCategory = $seriesData;

        $totalPosts = $this->postService->totalPostsByYear($year);

        return [$postMonths, $postCategories, $postDataByCategory, $totalPosts];
    }

    private function getPostStatusStats(?int $year): array
    {
        $categoryRows = DB::table('posts')
            ->leftJoin('term_relationships', function ($join) {
                $join->on('posts.id', '=', 'term_relationships.object_id')
                    ->where('term_relationships.object_type', '=', \App\Models\Post::class);
            })
            ->leftJoin('term_taxonomy', function ($join) {
                $join->on('term_relationships.term_taxonomy_id', '=', 'term_taxonomy.term_taxonomy_id')
                    ->where('term_taxonomy.taxonomy', '=', 'category');
            })
            ->leftJoin('terms', 'term_taxonomy.term_id', '=', 'terms.term_id')
            ->selectRaw("COALESCE(terms.name, ?) AS category, COALESCE(posts.status, ?) AS status, COUNT(DISTINCT posts.id) AS post_count", [__('admin.ui.no_category'), PostStatus::DRAFT->value])
            ->whereNull('posts.deleted_at')
            ->where('posts.post_type', 'post')
            ->when($year, fn ($query) => $query->whereYear('posts.created_at', $year))
            ->groupBy('category', 'status')
            ->get();

        $monthRows = DB::table('posts')
            ->selectRaw("DATE_FORMAT(posts.created_at, '%m-%Y') AS month, COALESCE(posts.status, ?) AS status, COUNT(*) AS post_count", [PostStatus::DRAFT->value])
            ->whereNull('posts.deleted_at')
            ->where('posts.post_type', 'post')
            ->when($year, fn ($query) => $query->whereYear('posts.created_at', $year))
            ->groupBy('month', 'status')
            ->orderByRaw("STR_TO_DATE(month, '%m-%Y')")
            ->get();

        $byCategory = [];
        foreach ($categoryRows as $row) {
            $byCategory[$row->category] ??= ['total' => 0];
            $byCategory[$row->category][$row->status] = (int) $row->post_count;
            $byCategory[$row->category]['total'] += (int) $row->post_count;
        }

        $byMonth = [];
        foreach ($monthRows as $row) {
            $byMonth[$row->month] ??= ['total' => 0];
            $byMonth[$row->month][$row->status] = (int) $row->post_count;
            $byMonth[$row->month]['total'] += (int) $row->post_count;
        }

        return [$byCategory, $byMonth];
    }

    private function getLienHeStats($year): array
    {
        // Feature Registry đang tắt Liên hệ: không đọc dữ liệu liên hệ cho bảng điều khiển.
        if (! app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact')) {
            return [[], 0];
        }
        $timelineData = $this->lienHeService->thongKeTheoThang($year);
        $soLienHe = array_sum(array_column($timelineData, 'y'));
        return [$timelineData, $soLienHe];
    }

    private function getBinhLuanStats($year): array
    {
        return $this->binhLuanService->thongKeTheoThang($year);
    }

    private function getKhaoSatStats($year): array
    {
        // Feature Registry đang tắt Khảo sát: không đọc dữ liệu khảo sát cho bảng điều khiển.
        if (! app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('survey')) {
            return [[], [], 0];
        }
        $records = $this->khaoSatService->getUsersWithFilter(['year' => $year, 'has_result' => true]);
        $groupedBySchool = $records->groupBy('school_key');

        $schoolNames = $groupedBySchool->map(function ($items) {
            // Lượt không khai báo trường có school_name = null: biểu đồ cần một nhãn chữ, không được là null.
            return $items->first()->school_name ?: __('career.stats.no_school');
        })->values()->all();

        $allResults = $records->groupBy('ksu_ket_qua');
        $series = [];

        foreach ($allResults as $resultName => $items) {
            $countsPerSchool = [];
            foreach ($groupedBySchool as $schoolKey => $schoolItems) {
                $count = $schoolItems->where('ksu_ket_qua', $resultName)->count();
                $countsPerSchool[] = $count;
            }

            $series[] = [
                'name' => $resultName,
                'data' => $countsPerSchool
            ];
        }

        return [$schoolNames, $series, $records->total()];
    }
}
