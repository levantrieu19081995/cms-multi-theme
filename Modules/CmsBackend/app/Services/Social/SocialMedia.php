<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

/**
 * The pictures and the video of a publication. They are kept as plain addresses in order; the kind of each is read from the file
 * extension of its address. A post carries either up to MAX_IMAGES pictures or one video, never both (Facebook cannot mix them).
 */
final class SocialMedia
{
    public const IMAGE = 'image';

    public const VIDEO = 'video';

    public const MAX_IMAGES = 10;

    /** Extensions the library accepts for pictures and videos (config/lfm.php: image and video folders). */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

    private const VIDEO_EXTENSIONS = ['mp4', 'mpeg', 'mpg', 'mov', 'webm'];

    /** 'image', 'video', or null for an address that is neither. */
    public static function kind(string $url): ?string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match (true) {
            in_array($extension, self::IMAGE_EXTENSIONS, true) => self::IMAGE,
            in_array($extension, self::VIDEO_EXTENSIONS, true) => self::VIDEO,
            default => null,
        };
    }

    /**
     * Clean list typed or picked by an administrator: trimmed, relative addresses made absolute, duplicates and addresses of
     * another kind dropped, order kept.
     *
     * @return list<array{type: string, url: string}>
     */
    public static function normalize(mixed $urls): array
    {
        $items = [];
        $seen = [];
        foreach ((array) $urls as $url) {
            $url = is_string($url) ? trim($url) : '';
            if ($url === '') {
                continue;
            }
            if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                $url = url($url);
            }
            $kind = preg_match('~^https?://[^\s]+$~i', $url) === 1 ? self::kind($url) : null;
            if ($kind === null || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $items[] = ['type' => $kind, 'url' => $url];
        }

        return $items;
    }

    /**
     * @param  list<array{type: string, url: string}>  $items
     * @return string|null the key of the error message ('admin.social.<key>') when the list breaks the rules, else null
     */
    public static function violation(array $items): ?string
    {
        $videos = count(array_filter($items, fn (array $item) => $item['type'] === self::VIDEO));
        $images = count($items) - $videos;

        return match (true) {
            $videos > 1 => 'error_media_one_video',
            $videos === 1 && $images > 0 => 'error_media_mixed',
            $images > self::MAX_IMAGES => 'error_media_too_many',
            default => null,
        };
    }

    /**
     * @param  list<array{type: string, url: string}>  $items
     * @return list<string> addresses of one kind
     */
    public static function urls(array $items, string $type): array
    {
        return array_values(array_map(fn (array $item) => $item['url'], array_filter($items, fn (array $item) => $item['type'] === $type)));
    }
}
