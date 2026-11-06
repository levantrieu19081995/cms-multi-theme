<?php

namespace App\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Terms;
use App\Enums\Taxonomy as EnumsTaxonomy;

abstract class AbstractPostDataProvider
{
    /**
     * Undocumented function
     *
     * @param array UserTypesEnum $userType or []
     * @param array UserStatusEnum $userStatus or []
     * @param array Role $role or []
     * @param string $filter
     * @param string $sortBy
     * @param string $order
     * @param integer $pageSize
     * @return void
     */
    public function paginate(
        string $postTypes,
        $postStatuses,
        $authorId,
        $categoryIds,
        $tagIds,
        string $filter,
        string $sortBy,
        // string $order,// <- đổi từ $order thành $direction vì laravel heieur lầm order là sắp xêp chwuas không phải cột dữ liệu
        string $direction,
        int $pageSize = 20,
        $page = 1,
        ?int $show_home_page = null,
        $categoryPermission = null,

    ): LengthAwarePaginator {
        $posts = Post::with(['author', 'publisher', 'taxonomies'])
            ->where(function ($builder) use ($authorId, $postTypes, $postStatuses, $categoryIds, $tagIds, $categoryPermission) {
                //Điều kiện cho tác giả
                if ($authorId) {
                    $builder->whereHas('author', function ($builder) use ($authorId) {
                        if ($authorId) {
                            $builder->where('author_id', $authorId);
                        }
                    });
                }

                //Điều kiện cho chuyên mục
                if (PostType::POST->value == $postTypes) {
                    $cateArray = array_map('strval', $categoryPermission->pluck('term_taxonomy_id')->toArray());
                    if (!empty($cateArray)) {
                        $builder->whereHas('taxonomies', function ($builder) use ($categoryIds, $cateArray) {
                            $builder->whereIn('term_taxonomy.term_taxonomy_id', $cateArray);
                        });
                    }
                }
                if ($categoryIds) {
                    $builder->whereHas('taxonomies', function ($builder) use ($categoryIds) {
                        if ($categoryIds) {
                            $builder->whereIn('term_taxonomy.term_taxonomy_id', $categoryIds);
                        }
                    });
                }
                //
                //Điều kiện cho thẻ
                if ($tagIds) {
                    $builder->whereHas('taxonomies', function ($builder) use ($tagIds) {
                        if ($tagIds) {
                            $builder->whereIn('term_taxonomy.term_taxonomy_id', $tagIds);
                        }
                    }, '=', count($tagIds));
                }

                // $builder ->with(['taxonomy']);
                if ($postTypes) {
                    $builder->where('post_type', $postTypes);
                }
                if ($postStatuses) {
                    $builder->whereIn('status', $postStatuses);
                }
            })
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('title', 'like', "%$filter%");
                }
            })->when($show_home_page, function ($query) use ($show_home_page) {
                return $query->where('show_home_page', $show_home_page);
            })
            ->whereNull('deleted_at')
            // ->orderBy($sortBy, $order) // chổ này gây hiểu lầm order là sắp xép chưa khong phải là cột order
            ->orderBy($sortBy, $direction)
            ->paginate($pageSize, ['*'], 'page', $page);
        return $posts;
    }


    public function findByIdSimple(int $id, $postTypes = null)
    {
        $post = Post::with(['author', 'taxonomies'])
            ->where(function ($builder) use ($postTypes) {
                if ($postTypes) {
                    $builder->where('post_type', $postTypes);
                }
                // else{
                //     $builder->where('post_type', PostType::POST->value);
                // }
            })
            ->findOrFail($id);
        return $post;
    }

    public function findById(int $id, $postTypes = null)
    {
        $post = Post::with(['author', 'taxonomies', 'approvedComments' => function ($query) {
            $query->with('replies'); // Lấy bình luận trả lời cho mỗi bình luận gốc
        }])->where(function ($builder) use ($postTypes) {
            if ($postTypes) {
                $builder->where('post_type', $postTypes);
            } else {
                $builder->where('post_type', PostType::POST->value);
            }
        })
            ->findOrFail($id);
        return $post;
    }
    //image

    public function getPageHierarchy($post_id, $postType)
    {
        // Get all descendant IDs of the current page
        $excludedIds = $this->getAllChildrenIds(Post::find($post_id));
        $excludedIds[] = $post_id; // Exclude the current page itself

        // Fetch top-level pages (post_parent = NULL) and build the hierarchy
        $pages = Post::where('post_type', $postType)
            ->whereNull('post_parent') // Only top-level pages
            ->whereNotIn('id', $excludedIds) // Exclude current page and descendants
            ->get();

        // Build the hierarchy recursively, excluding descendants
        return $this->buildHierarchy($pages, $excludedIds);
    }
    public function buildHierarchy($pages, $excludedIds)
    {
        $hierarchy = [];

        foreach ($pages as $page) {
            // Only add children that are not excluded
            $children = Post::where('post_parent', $page->id)
                ->whereNotIn('id', $excludedIds)
                ->get();

            // Recursively build the hierarchy for children
            $page->children = $this->buildHierarchy($children, $excludedIds);

            // Add the page to the hierarchy array
            $hierarchy[] = $page;
        }

        return $hierarchy;
    }
    public function getAllChildrenIds($post)
    {
        if (!$post) {
            return [];
        }

        // Get the IDs of the immediate children
        $ids = $post->children()->pluck('id')->toArray();

        // Recursively get the IDs of all descendants (children, grandchildren, etc.)
        foreach ($post->children as $child) {
            $ids = array_merge($ids, $this->getAllChildrenIds($child)); // Recursion to get all descendant IDs
        }

        return $ids;
    }
}
