<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use Illuminate\Support\Facades\Http;

/**
 * Downloads the picture of a publication so it can be uploaded to Facebook as a file. Facebook is never asked to fetch the picture
 * from our site (that call fails with "invalid image file" whenever Facebook's own fetcher cannot reach the site), so posting
 * does not depend on the route between Meta and our server.
 *
 * The address comes from an administrator, so it is checked before any request: https only, no credentials in it, and the host
 * must be the site of the article itself or resolve only to public addresses (nothing inside our network). Redirects are not
 * followed, the size is limited and the bytes must really be a JPEG, PNG, GIF or BMP picture.
 */
final class FacebookImageFetcher
{
    /** Facebook accepts photos up to this size through the API. */
    private const MAX_BYTES = 8 * 1024 * 1024;

    /** The library holds videos up to 50 MB (config/lfm.php). */
    private const MAX_VIDEO_BYTES = 52 * 1024 * 1024;

    private const VIDEO_EXTENSIONS = ['mp4', 'mpeg', 'mpg', 'mov', 'webm'];

    private const TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_BMP => 'bmp',
    ];

    /**
     * @param  string  ...$trustedHosts  hosts of the site itself (the article address, the application): always allowed
     * @return array{bytes: string, filename: string}
     *
     * @throws FacebookApiException kind IMAGE
     */
    public function fetch(string $url, string ...$trustedHosts): array
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass']) || ! $this->hostAllowed($host, $trustedHosts)) {
            throw new FacebookApiException(FacebookApiException::IMAGE);
        }

        try {
            $response = Http::timeout(15)->connectTimeout(5)->withOptions(['allow_redirects' => false])
                ->withHeaders(['Accept' => 'image/*'])->get($url);
        } catch (\Throwable) {
            throw new FacebookApiException(FacebookApiException::IMAGE);
        }

        $bytes = $response->successful() ? (string) $response->body() : '';
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw new FacebookApiException(FacebookApiException::IMAGE);
        }

        $info = @getimagesizefromstring($bytes);
        $extension = is_array($info) ? (self::TYPES[$info[2]] ?? null) : null;
        if ($extension === null) {
            throw new FacebookApiException(FacebookApiException::IMAGE);
        }

        return ['bytes' => $bytes, 'filename' => 'image.'.$extension];
    }

    /**
     * Download the video of a publication to a temporary file (never into memory), under the same address rules as a picture.
     * The caller deletes the file.
     *
     * @return array{path: string, filename: string}
     *
     * @throws FacebookApiException kind VIDEO
     */
    public function fetchVideo(string $url, string ...$trustedHosts): array
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $extension = strtolower(pathinfo((string) ($parts['path'] ?? ''), PATHINFO_EXTENSION));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])
            || ! in_array($extension, self::VIDEO_EXTENSIONS, true) || ! $this->hostAllowed($host, $trustedHosts)) {
            throw new FacebookApiException(FacebookApiException::VIDEO);
        }

        $path = tempnam(sys_get_temp_dir(), 'social-video-');
        if ($path === false) {
            throw new FacebookApiException(FacebookApiException::VIDEO);
        }

        try {
            $response = Http::timeout(180)->connectTimeout(5)->withOptions(['allow_redirects' => false, 'sink' => $path])
                ->withHeaders(['Accept' => 'video/*'])->get($url);
            $size = is_file($path) ? (int) filesize($path) : 0;
            if (! $response->successful() || $size <= 0 || $size > self::MAX_VIDEO_BYTES || ! $this->looksLikeVideo($path)) {
                throw new FacebookApiException(FacebookApiException::VIDEO);
            }
        } catch (\Throwable $e) {
            @unlink($path);

            throw $e instanceof FacebookApiException ? $e : new FacebookApiException(FacebookApiException::VIDEO);
        }

        return ['path' => $path, 'filename' => 'video.'.$extension];
    }

    /** MP4 / MOV carry "ftyp" in their first bytes, MPEG a start code, WebM / Matroska the EBML header. */
    private function looksLikeVideo(string $path): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 16);

        return substr($head, 4, 4) === 'ftyp' || str_starts_with($head, "\x1A\x45\xDF\xA3") || str_starts_with($head, "\x00\x00\x01");
    }

    /** @param string[] $trustedHosts */
    private function hostAllowed(string $host, array $trustedHosts): bool
    {
        foreach ($trustedHosts as $trusted) {
            if ($trusted !== '' && $host === strtolower($trusted)) {
                return true;
            }
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : (gethostbynamel($host) ?: []);
        if ($addresses === []) {
            return false;
        }
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
