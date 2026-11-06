<?php

namespace App\DataProviders;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VideoDataProvider
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
        $postStatuses,
        $authorId,
        string $filter,
        string $sortBy,
        string $order,
        int $pageSize = 20
    ): LengthAwarePaginator {
        $posts = Post::with(['author', 'publisher'])
            ->where(function ($builder) use ($authorId, $postStatuses) {
                //Điều kiện cho tác giả
                if ($authorId) {
                    $builder->whereHas('author', function ($builder) use ($authorId) {
                        if ($authorId) {
                            $builder->where('author_id', $authorId);
                        }
                    });
                }
                $builder->where('post_type', PostType::VIDEO->value);

                if ($postStatuses) {
                    $builder->whereIn('status', $postStatuses);
                }
            })
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('title', 'like', "%$filter%");
                }
            })
            ->whereNull('deleted_at')
            ->orderBy($sortBy, $order)
            ->paginate($pageSize);


        // $posts = Posts::join('users as author', 'author.id', '=', 'posts.author_id')
        // ->join('users as publisher', 'publisher.id', '=', 'posts.publisher_id')
        // ->select('title','content','slug','author.name','publisher.name','status','comment_status','post_type','excerpt','created_at','published_at')
        // ->get();

        return $posts;
    }

    public function findById(int $id)
    {
        $post = Post::with(['parent'])
            ->where('post_type', PostType::VIDEO->value)
            ->whereNull('deleted_at')
            ->findOrFail($id);
        return $post;
    }

    public function getPageHierarchy($post_id)
    {
        // Get all descendant IDs of the current page
        $excludedIds = $this->getAllChildrenIds(Post::find($post_id));
        $excludedIds[] = $post_id; // Exclude the current page itself

        // Fetch top-level pages (post_parent = NULL) and build the hierarchy
        $pages = Post::where('post_type', PostType::VIDEO->value)
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
