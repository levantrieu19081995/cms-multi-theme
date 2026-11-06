<?php

namespace Modules\CmsFrontend\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PublishType;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class BannerDataProvider
{
  
public function getDanhSachBannerWithCache()
{
    return Post::with(['author', 'publisher', 'taxonomies'])
        ->where(function ($builder) {
            $builder->where('post_type', PostType::BANNER->value);
            $builder->where('status', PostStatus::PUBLISHED->value);
        })
        ->where('show_home_page', 1)
        ->whereNull('deleted_at')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($banner) {

            // Lấy URL ảnh gốc từ media library
            $original = $banner->getFirstMediaPath('avatars');
            // Nếu ảnh rỗng → dùng ảnh placeholder
            if (empty($original)) {
                $banner->webp = asset('images/banner-placeholder.webp');
                return $banner;
            }

            // Convert + resize + cache webp
            $banner->webp = $this->getCachedBannerWebp($original);

            return $banner;
        });
}
 private function getCachedBannerWebp(?string $sourcePath, int $maxWidth = 1920)
{
    try {
        // 1) Không có path → trả placeholder
        if (empty($sourcePath) || !file_exists($sourcePath)) {
            Log::warning('getCachedBannerWebp: Source file missing: ' . ($sourcePath ?? 'NULL'));
            return asset('images/banner-placeholder.webp');
        }

        // 2) Hash theo NỘI DUNG FILE (md5_file) → đúng nhất
        $hash = md5_file($sourcePath);

        // 3) Tên file cache
        $cacheRelative = "cache/banner/{$hash}.webp";
        $disk = Storage::disk('public');

        // 4) Nếu đã có cache → trả URL
        if ($disk->exists($cacheRelative)) {
            return $disk->url($cacheRelative);
        }

        // 5) Tạo hình ảnh từ file gốc
        $img = Image::make($sourcePath);

        // 6) Resize tối đa theo width
        if ($img->width() > $maxWidth) {
            $img->resize($maxWidth, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            });
        }

        // 7) Convert sang WebP
        $img->encode('webp', 80);

        // 8) Lưu cache
        $disk->put($cacheRelative, (string) $img);

        // 9) Trả URL WebP
        return $disk->url($cacheRelative);

    } catch (\Throwable $e) {
        Log::error('getCachedBannerWebp ERROR: ' . $e->getMessage(), [
            'path' => $sourcePath,
            'trace' => $e->getTraceAsString()
        ]);
        return asset('images/banner-placeholder.webp');
    }
}

}
