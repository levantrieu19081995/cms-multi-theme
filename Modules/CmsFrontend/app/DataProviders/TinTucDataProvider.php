<?php

namespace Modules\CmsFrontend\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PublishType;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\Comment;

class TinTucDataProvider
{
    public function getQueryList(
        string $postTypes,
        ?string $categorySlug = null,
        ?string $filter = null,
        $except_id = null,
        array $orderby = ['published_at:desc'],
        // ?int $show_home_page = 1,
        ?int $show_home_page = null,
    ) {
        $ngayHienTai = now();
        $posts = Post::with(['author', 'taxonomies', 'media'])
            ->where(function ($builder) use ($postTypes, $categorySlug, $filter) {
                // Searching all posts must not drop uncategorised ones; category pages still require the category.
                if ($postTypes == PostType::POST->value && ($categorySlug !== null || blank($filter))) {
                    $builder->whereHas('taxonomies', function ($builder) use ($categorySlug) {
                        $builder->whereHas('term', function ($builder) use ($categorySlug) {
                            if ($categorySlug !== null) {
                                $builder->where('slug', '=', $categorySlug);
                            }
                        })->where('taxonomy', EnumsTaxonomy::CATEGORY->value);
                    });
                }
                if ($postTypes) {
                    $builder->where('post_type', $postTypes);
                }
            })

            ->where(function ($builder) use ($filter, $except_id, $show_home_page) {
                if ($filter) {
                    $like = '%' . addcslashes($filter, '\\%_') . '%';
                    $locale = app(\App\Services\LocaleManager::class)->publicLocale();
                    $builder->where(function ($search) use ($like, $locale) {
                        if ($locale !== null) {
                            // Another language searches the texts of that language only (post_translations), never the Vietnamese columns.
                            $search->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where(fn ($text) => $text
                                ->where('title', 'like', $like)
                                ->orWhere('excerpt', 'like', $like)
                                ->orWhere('content', 'like', $like)
                                ->orWhere('seo_title', 'like', $like)
                                ->orWhere('seo_description', 'like', $like)));
                        } else {
                            $search->where('title', 'like', $like)
                                ->orWhere('excerpt', 'like', $like)
                                ->orWhere('content', 'like', $like);
                        }
                        // Author names are people's names, shared by every language.
                        $search->orWhere('author_names', 'like', $like)
                            ->orWhereHas('author', fn ($author) => $author->where('name', 'like', $like));
                    });
                }
                if ($except_id) {
                    $builder->whereNotIn('id', $except_id);
                }
                if ($show_home_page !== null) {
                    $builder->where('show_home_page', $show_home_page);
                }
            })
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->whereNull('publication_date_expires')
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            });

        foreach ($orderby as $order) {
            [$field, $direction] = explode(':', $order);
            $posts->orderBy($field, $direction);
        }

        return $posts;
    }

    public function getPostBySlug(string $slug)
    {
        $ngayHienTai = now();

        return Post::with(['author', 'taxonomies', 'media'])
            ->where('slug', '=', $slug)
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->whereNull('publication_date_expires')
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            })
            ->first();
    }

    public function getCommentsByPost(string $post_id)
    {
        return Comment::with(['user', 'replies'])
            ->where('post_id', '=', $post_id)
            ->where('status', '=', \App\Enums\CommentStatus::APPROVED->value)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTinTucPublic(?int $numItems, ?int $show_home_page = 1)
    {
        return Post::with('author', 'media')
            ->where('show_home_page', $show_home_page)
            ->where('post_type', PostType::POST->value)
            ->where('show_home_page', 1)
            ->where('status', PostStatus::PUBLISHED->value)
            ->orderBy('published_at', 'desc')
            ->limit($numItems)
            ->whereNull('deleted_at')
            ->get();
    }

    public function getTinTucByCategoryId(int $categoryId, int $limit = 10)
    {
        return Post::with('author')
            ->where('post_type', PostType::POST->value)
            ->where('status', PostStatus::PUBLISHED->value)
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
