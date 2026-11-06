<?php

namespace Modules\CmsFrontend\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use App\Models\Post;

class VideoDataProvider
{
    public function getDanhSachVideoTrangChu()
    {
        $ngayHienTai = now();
        $posts = Post::with(['media'])
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::VIDEO->value)
            ->where('show_home_page', 1) //có hiển thị trang chủ   
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->where('publication_date_expires', '=', null)
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            })
            ->orderBy('published_at', 'desc')
            ->get();
        // ->take(3)->get();
        return $posts;
    }
    /** Query chung cho video đã publish và còn hiệu lực */
    protected function baseQuery()
    {
        $now = now();

        return Post::with('media')
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::VIDEO->value)
            ->where('published_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('publication_date_expires')
                    ->orWhere('publication_date_expires', '>=', $now);
            });
    }

    /**
     * LẤY TOÀN BỘ VIDEO (không lọc show_home_page)
     * - Có thể truyền $perPage để phân trang, mặc định lấy hết
     */
    public function getTatCaVideo(?int $perPage = null)
    {
        $q = $this->baseQuery()->orderByDesc('published_at');

        return $perPage ? $q->paginate($perPage) : $q->get();
    }


    public function getVideoBySlug($slug)
    {
        return $this->baseQuery()
            ->where('slug', $slug)
            ->first();
    }
}
