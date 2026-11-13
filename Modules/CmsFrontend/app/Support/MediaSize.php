<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Support;

use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Kích thước thật của ảnh đã tải lên (có tính chiều xoay EXIF của ảnh chụp điện thoại), để dựng khung hiện nguyên ảnh mà không cắt.
 * Chỉ đọc phần đầu tệp, nhớ lại theo id và lần sửa của ảnh nên mỗi ảnh chỉ đọc một lần. Ảnh không đọc được (tệp mất, ảnh ở nơi lưu từ xa) trả về null.
 */
final class MediaSize
{
    public const FALLBACK_RATIO = 1.5;

    private const MIN_RATIO = 0.2;

    private const MAX_RATIO = 6.0;

    /** @return array{width: int, height: int}|null */
    public static function of(?Media $media): ?array
    {
        if ($media === null) {
            return null;
        }
        $key = 'cms.media-size.'.$media->getKey().'.'.($media->updated_at?->getTimestamp() ?? 0);
        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached === [] ? null : $cached;
            }
            $size = self::read($media->getPath());
            if ($size === null) {
                Cache::put($key, [], now()->addMinutes(10));
            } else {
                Cache::forever($key, $size);
            }

            return $size;
        } catch (Throwable) {
            return null;
        }
    }

    /** Tỉ lệ rộng / cao, giới hạn trong khoảng hợp lý để một ảnh quá dẹt hoặc quá dài không phá bố cục. */
    public static function ratio(?Media $media, float $fallback = self::FALLBACK_RATIO): float
    {
        $size = self::of($media);
        if ($size === null) {
            return $fallback;
        }

        return round(max(self::MIN_RATIO, min(self::MAX_RATIO, $size['width'] / $size['height'])), 4);
    }

    /** @return array{width: int, height: int}|null */
    public static function read(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if ($info === false || ($info[0] ?? 0) <= 0 || ($info[1] ?? 0) <= 0) {
            return null;
        }
        [$width, $height] = [(int) $info[0], (int) $info[1]];
        if (($info[2] ?? 0) === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path, 'IFD0');
            if (is_array($exif) && in_array((int) ($exif['Orientation'] ?? 1), [5, 6, 7, 8], true)) {
                [$width, $height] = [$height, $width];
            }
        }

        return ['width' => $width, 'height' => $height];
    }
}
