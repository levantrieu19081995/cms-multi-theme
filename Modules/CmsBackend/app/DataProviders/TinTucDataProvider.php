<?php

namespace Modules\CmsBackend\DataProviders;

use App\Enums\PostStatus;
use App\Models\Post;


class TinTucDataProvider extends BaseDataProvider
{
    public function __construct(Post $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }

    // Các phương thức xử lý dữ liệu khác...
    public function getChiTietTinTucBySlug(string $slug)
    {
        $ngayHienTai = now();
        $posts = Post::with(['author', 'taxonomies', 'media'])
            ->where(function ($builder) use ($slug) {
                if ($slug) {
                    $builder->where('slug', '=', $slug);
                }
            })
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->where('publication_date_expires', '=', null)
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            })
            ->first();

        return $posts;
    }
}
