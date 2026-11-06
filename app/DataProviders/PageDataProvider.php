<?php

namespace App\DataProviders;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PageDataProvider extends AbstractPostDataProvider
{
    // public function __construct()
    // {
    //     parent::__construct();
    // }
  
    // public function getPageHierarchy($post_id)
    // {
    //     // Get all descendant IDs of the current page
    //     $excludedIds = $this->getAllChildrenIds(Post::find($post_id));
    //     $excludedIds[] = $post_id; // Exclude the current page itself

    //     // Fetch top-level pages (post_parent = NULL) and build the hierarchy
    //     $pages = Post::where('post_type', 'page')
    //         ->whereNull('post_parent') // Only top-level pages
    //         ->whereNotIn('id', $excludedIds) // Exclude current page and descendants
    //         ->get();

    //     // Build the hierarchy recursively, excluding descendants
    //     return $this->buildHierarchy($pages, $excludedIds);
    // }
    // public function buildHierarchy($pages, $excludedIds)
    // {
    //     $hierarchy = [];

    //     foreach ($pages as $page) {
    //         // Only add children that are not excluded
    //         $children = Post::where('post_parent', $page->id)
    //             ->whereNotIn('id', $excludedIds)
    //             ->get();

    //         // Recursively build the hierarchy for children
    //         $page->children = $this->buildHierarchy($children, $excludedIds);

    //         // Add the page to the hierarchy array
    //         $hierarchy[] = $page;
    //     }

    //     return $hierarchy;
    // }
    // public function getAllChildrenIds($post)
    // {
    //     if (!$post) {
    //         return [];
    //     }

    //     // Get the IDs of the immediate children
    //     $ids = $post->children()->pluck('id')->toArray();

    //     // Recursively get the IDs of all descendants (children, grandchildren, etc.)
    //     foreach ($post->children as $child) {
    //         $ids = array_merge($ids, $this->getAllChildrenIds($child)); // Recursion to get all descendant IDs
    //     }

    //     return $ids;
    // }
}
