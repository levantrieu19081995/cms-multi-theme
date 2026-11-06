<?php

namespace Modules\CmsFrontend\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use App\Models\Post;

class ImageLibraryDataProvider
{
    public function getThuVienHinhAnhTrangChu()
    {
        $ngayHienTai = now();
        $posts = Post::with(['media'])
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::IMAGE->value)
            ->where('show_home_page', 1) //có hiển thị trang chủ   
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->where('publication_date_expires', '=', null)
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            })
            ->orderBy('published_at', 'desc')
            ->first();
        return $posts;
    }

    public function getThuVienHinhAnhTrangChuList()
    {
        $ngayHienTai = now();

        // TODO: đổi KEY này thành key option thật bạn đang lưu "1,2"
        $opt = app(\App\DataProviders\OptionDataProvider::class)
            ->getOptionByKey('WEB_IMAGE_LIBRARY_HOME'); // <--- sửa key tại đây

        $ids = collect(explode(',', (string) ($opt?->value ?? '')))
            ->map(fn($x) => (int) trim($x))
            ->filter(fn($x) => $x > 0)
            ->values()
            ->all();

        return Post::with(['media'])
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::IMAGE->value)
            ->where('show_home_page', 1)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->whereNull('publication_date_expires')
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai);
            })
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->orderBy('published_at', 'desc')
            ->get();
    }




    public function getDanhSachThuVienHinhAnh($id = 0)
    {
        $ngayHienTai = now();
        $posts = Post::with(['media', 'children', 'parent'])
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::IMAGE->value)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->where('publication_date_expires', '=', null)
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai)
                    ->orWhere('publish_type', '=', PublishType::PUBLISH_NOW->value);
            })
            ->where(function ($builder) use ($id) {
                if ($id != 0 && $id != null) {
                    $builder->where('id', '=', $id);
                } else {
                    $builder->where('post_parent', '=', null);
                }
            })
            ->orderBy('published_at', 'desc')
            ->get();
        return $posts;
    }

    public function getThuVienHinhAnhBySlug($slug)
    {
        $ngayHienTai = now();
        return Post::with(['media', 'children', 'parent'])
            ->whereNull('deleted_at')
            ->where('status', PostStatus::PUBLISHED->value)
            ->where('post_type', PostType::IMAGE->value)
            ->where('slug', $slug)
            ->where('published_at', '<=', $ngayHienTai)
            ->where(function ($builder) use ($ngayHienTai) {
                $builder->whereNull('publication_date_expires')
                    ->orWhere('publication_date_expires', '>=', $ngayHienTai)
                    ->orWhere('publish_type', '=', PublishType::PUBLISH_NOW->value);
            })
            ->first();
    }
}
