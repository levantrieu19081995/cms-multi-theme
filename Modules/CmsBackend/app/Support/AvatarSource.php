<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

/**
 * Gắn ảnh đại diện (URL chọn từ trình quản lý tệp) vào một model có media.
 *
 * Ảnh nằm trên chính website này thì chép thẳng từ đĩa, KHÔNG gọi HTTP về chính mình: gọi lại chính mình làm treo `php artisan serve`
 * (một luồng, tự chờ nhau) và có thể hỏng sau proxy/HTTPS hoặc khi tên tệp có dấu cách/chữ có dấu. Chỉ ảnh ở máy chủ khác mới tải qua URL.
 */
final class AvatarSource
{
    /** @return 'local'|'remote'|'failed' */
    public static function attach(HasMedia $model, string $url, string $collection = 'avatars'): string
    {
        $url = trim($url);
        if ($url === '') {
            return 'failed';
        }
        try {
            $path = self::localPath($url);
            if ($path !== null) {
                $model->clearMediaCollection($collection);
                $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);

                return 'local';
            }
            $model->clearMediaCollection($collection);
            $model->addMediaFromUrl($url)->toMediaCollection($collection);

            return 'remote';
        } catch (Throwable $e) {
            Log::error('AvatarSource: không gắn được ảnh đại diện', ['url' => $url, 'error' => $e->getMessage()]);

            return 'failed';
        }
    }

    /** Đường dẫn tệp trên đĩa nếu URL trỏ vào chính website này (và tệp nằm trong thư mục public/storage cho phép); ngược lại null. */
    private static function localPath(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host !== '' && ! in_array($host, self::ownHosts(), true)) {
            return null;
        }
        $relative = ltrim(rawurldecode((string) $parts['path']), '/');
        if ($relative === '' || str_contains($relative, "\0") || str_contains($relative, '..')) {
            return null;
        }
        $roots = array_filter([realpath(public_path()), realpath(storage_path('app/public'))]);
        $file = realpath(public_path($relative));
        if ($file === false || ! is_file($file)) {
            return null;
        }
        foreach ($roots as $root) {
            if (str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
                return $file;
            }
        }

        return null;
    }

    /** @return list<string> tên máy (không cổng) được coi là chính website này */
    private static function ownHosts(): array
    {
        $hosts = [];
        foreach ([(string) config('app.url'), (string) config('filesystems.disks.public.url'), (string) config('filesystems.disks.media.url')] as $configured) {
            $host = parse_url($configured, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $hosts[] = strtolower($host);
            }
        }
        if (app()->bound('request')) {
            $hosts[] = strtolower(request()->getHost());
        }

        return array_values(array_unique($hosts));
    }
}
