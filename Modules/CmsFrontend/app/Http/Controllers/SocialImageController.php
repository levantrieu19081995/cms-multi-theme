<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Modules\CmsBackend\Models\GuongTieuBieu;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SocialImageController extends Controller
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;
    private const JPEG_QUALITY = 82;

    public function show(Post $post): BinaryFileResponse
    {
        abort_unless(
            $post->status === PostStatus::PUBLISHED->value
                && in_array($post->post_type, ['post', 'event', 'page'], true),
            404
        );

        return $this->respondWithAvatar('post|' . $post->getKey(), $post->getFirstMedia('avatars'));
    }

    /**
     * Share image of a "gương tiêu biểu": its own avatar, the brand banner only when there is none.
     */
    public function showExample(int $id): BinaryFileResponse
    {
        $example = GuongTieuBieu::query()->findOrFail($id);

        return $this->respondWithAvatar('guong|' . $example->getKey(), $example->getFirstMedia('avatars'));
    }

    private function respondWithAvatar(string $cacheOwner, ?Media $media): BinaryFileResponse
    {
        if (!$media) {
            return $this->brandFallback();
        }

        $sourcePath = $media->getPath();

        if (!is_file($sourcePath) || !function_exists('imagecreatefromstring')) {
            return $this->brandFallback();
        }

        $version = $media->updated_at?->getTimestamp() ?: filemtime($sourcePath);
        $cacheDirectory = storage_path('app/cms-social-images');
        $cachePath = $cacheDirectory . DIRECTORY_SEPARATOR
            . sha1($cacheOwner . '|' . $media->getKey() . '|' . $version) . '.jpg';

        if (!is_file($cachePath)) {
            $this->makePreview($sourcePath, $cacheDirectory, $cachePath);
        }

        return response()->file($cachePath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }

    private function makePreview(string $sourcePath, string $cacheDirectory, string $cachePath): void
    {
        $source = @imagecreatefromstring((string) file_get_contents($sourcePath));

        abort_unless($source !== false, 404);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = self::WIDTH / self::HEIGHT;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $sourceY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $sourceX = 0;
            $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $preview = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $background = imagecolorallocate($preview, 255, 255, 255);
        imagefill($preview, 0, 0, $background);
        imagecopyresampled(
            $preview,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            self::WIDTH,
            self::HEIGHT,
            $cropWidth,
            $cropHeight
        );

        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0775, true);
        }

        imagejpeg($preview, $cachePath, self::JPEG_QUALITY);
        imagedestroy($preview);
        imagedestroy($source);
    }

    private function brandFallback(): BinaryFileResponse
    {
        $path = public_path(config('cmsfrontend.brand.banner'));

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => mime_content_type($path) ?: 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
