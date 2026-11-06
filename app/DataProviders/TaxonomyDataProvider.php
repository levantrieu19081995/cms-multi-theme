<?php

namespace App\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PublishType;
use App\Models\Posts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Taxonomy;
use App\Models\Terms;
use App\Enums\Taxonomy as EnumsTaxonomy;

class TaxonomyDataProvider
{
    /**
     * Undocumented function
     * @param integer $parentId
     * @return void
     */
    public function getCategoryHierarchy($parentId = 0, $type)
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', $type)
            ->where('parent', $parentId)
            ->get();

        // Lặp qua từng category và tìm con của nó
        foreach ($categories as $category) {
            $category->children = $this->getCategoryHierarchy($category->term_taxonomy_id, $type);
        }

        return $categories;
    }

    public function getCategoryHierarchyBySlug($slug, $type)
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        // $categories = Taxonomy::with(['term']);
        $categories = Taxonomy::whereHas('term', function ($builder) use ($slug) {
            $builder->where('terms.slug', $slug);
        })->first();
        return $this->getCategoryHierarchy($categories->term_taxonomy_id, $type);
    }

    public function findBySlug($slug, $type)
    {

        $category = Taxonomy::with('term')->whereHas('term', function ($builder) use ($slug) {
            $builder->where('slug', $slug);
        })->where('taxonomy', $type)->first();
        return $category;
    }
    public function findByIdIn($ids)
    {
        $category = Taxonomy::whereIn('term_taxonomy_id', $ids)
            ->get();
        return $category;
    }

    function getPostsGroupedByTerm($termTaxonomyIds)
    {
        $ngayHienTai = now();
        // Lấy danh sách các chuyên mục (term) theo term_taxonomy_id
        $categories = Taxonomy::with(['posts' => function ($query) use ($ngayHienTai) {
            $query->where('status', PostStatus::PUBLISHED->value)
                ->where('published_at', '<=', $ngayHienTai)
                ->where(function ($query) use ($ngayHienTai) {
                    $query->where('publication_date_expires', '=', null)
                        ->orWhere('publication_date_expires', '>=', $ngayHienTai)
                        ->orWhere('publish_type', '=', PublishType::PUBLISH_NOW->value);
                })->whereNull('deleted_at')
                ->orderBy('published_at', 'desc')->take(5);
        }])
            ->whereIn('term_taxonomy_id', $termTaxonomyIds)
            ->get();
        // Khởi tạo mảng kết quả
        $groupedPosts = [];

        foreach ($categories as $category) {
            // Lọc các bài viết trùng lặp nếu có, lấy tối đa 10 bài cho mỗi chuyên mục
            $uniquePosts = $category->posts;

            // Lưu thông tin chuyên mục và bài viết
            $groupedPosts[] = [
                'term_name' => $category->term->name,
                'term_slug' => $category->term->slug,
                'posts' => $uniquePosts
            ];
        }
        return $groupedPosts;
    }



    public function getCategoryAll()
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', EnumsTaxonomy::CATEGORY->value)
            ->get();


        return $categories;
    }
    public function paginate(string $filter, int $pageSize = 20, $type): LengthAwarePaginator
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', $type)
            ->whereHas('term', function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('terms.name', 'like', "%$filter%");
                }
            })
            ->paginate($pageSize);

        // Lặp qua từng category và tìm con của nó
        foreach ($categories as $category) {
            $category->parent = Taxonomy::with('term')
                ->where('taxonomy',  $type)
                ->where('term_taxonomy_id',  $category->parent)
                ->first();
        }

        return $categories;
    }
    public function getCategoryWithParent($type)
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', $type)
            ->get();

        // Lặp qua từng category và tìm con của nó
        foreach ($categories as $category) {
            $category->parent = Taxonomy::with('term')
                ->where('taxonomy',  $type)
                ->where('term_taxonomy_id',  $category->parent)
                ->first();
        }

        return $categories;
    }
    public function getCategoryExclusions($term_id, $allChildrenIds, $type)
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', $type)
            ->whereNotIn('term_id', $allChildrenIds)
            ->where('term_id', '!=', $term_id)
            ->get();

        // // Lặp qua từng category và tìm con của nó
        // foreach ($categories as $category) {
        //     $category->parent = Taxonomy::with('term')
        //     ->where('taxonomy',  EnumsTaxonomy::CATEGORY->value)
        //     ->where('term_taxonomy_id',  $category->parent)
        //     ->first();
        // }

        return $categories;
    }
    // Hàm đệ quy để lấy tất cả id của các chuyên mục con
    public function getAllChildrenIds(Taxonomy $category)
    {
        $childrenIds = $category->children()->pluck('term_id')->toArray();
        foreach ($category->children as $child) {
            $childrenIds = array_merge($childrenIds, $this->getAllChildrenIds($child));
        }
        return $childrenIds;
    }


    public function getCategoryTopUsed()
    {
        $topCategories = Taxonomy::with('term') // Giả sử bạn có mối quan hệ với bảng terms
            ->where('taxonomy',  EnumsTaxonomy::CATEGORY->value)
            ->orderBy('count', 'desc') // Sắp xếp theo số lượng sử dụng
            //->take(10) // Lấy 10 chuyên mục được sử dụng nhiều nhất
            ->get();

        return $topCategories;
    }
}
