<?php

declare(strict_types=1);

namespace Tests\Unit\Homepage;

use Illuminate\Support\Facades\Cache;
use Modules\CmsFrontend\Support\MediaSize;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Đo ảnh để dựng khung không cắt: kích thước thật, chiều xoay EXIF, giới hạn tỉ lệ, ảnh không đọc được, nhớ kết quả. Không cần database.
 */
final class MediaSizeTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function image(int $width, int $height, string $type = 'png', ?int $orientation = null): string
    {
        $canvas = imagecreatetruecolor($width, $height);
        $path = tempnam(sys_get_temp_dir(), 'ms').'.'.$type;
        $type === 'jpg' ? imagejpeg($canvas, $path) : imagepng($canvas, $path);
        if ($orientation !== null) {
            $bytes = (string) file_get_contents($path);
            $tiff = "II*\x00\x08\x00\x00\x00\x01\x00\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientation)."\x00\x00\x00\x00\x00\x00";
            $segment = "Exif\x00\x00".$tiff;
            file_put_contents($path, substr($bytes, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($bytes, 2));
        }

        return $this->files[] = $path;
    }

    private function media(int $id, string $path): Media
    {
        $media = new class extends Media
        {
            public string $file = '';

            public function getPath(string $conversionName = ''): string
            {
                return $this->file;
            }
        };
        $media->file = $path;
        $media->setAttribute('id', $id);

        return $media;
    }

    public function test_it_reads_the_real_size_of_an_image(): void
    {
        $this->assertSame(['width' => 400, 'height' => 300], MediaSize::read($this->image(400, 300)));
    }

    public function test_a_phone_photo_stored_rotated_is_measured_as_it_is_displayed(): void
    {
        $this->assertSame(['width' => 300, 'height' => 400], MediaSize::read($this->image(400, 300, 'jpg', 6)));
        $this->assertSame(['width' => 400, 'height' => 300], MediaSize::read($this->image(400, 300, 'jpg', 1)));
        $this->assertSame(['width' => 400, 'height' => 300], MediaSize::read($this->image(400, 300, 'jpg')));
    }

    public function test_missing_or_unreadable_files_have_no_size(): void
    {
        $this->assertNull(MediaSize::read(sys_get_temp_dir().'/does-not-exist-'.uniqid().'.png'));
        $text = tempnam(sys_get_temp_dir(), 'ms');
        $this->files[] = $text;
        file_put_contents($text, 'not an image');
        $this->assertNull(MediaSize::read($text));
        $this->assertNull(MediaSize::of(null));
        $this->assertNull(MediaSize::of($this->media(9, $text)));
    }

    public function test_ratio_is_width_over_height_and_falls_back_when_unknown(): void
    {
        $this->assertSame(1.3333, MediaSize::ratio($this->media(1, $this->image(400, 300))));
        $this->assertSame(0.75, MediaSize::ratio($this->media(2, $this->image(300, 400))));
        $this->assertSame(MediaSize::FALLBACK_RATIO, MediaSize::ratio(null));
        $this->assertSame(2.0, MediaSize::ratio(null, 2.0));
    }

    public function test_extreme_shapes_are_limited_so_one_image_cannot_break_a_row(): void
    {
        $this->assertSame(6.0, MediaSize::ratio($this->media(3, $this->image(1000, 20))));
        $this->assertSame(0.2, MediaSize::ratio($this->media(4, $this->image(20, 1000))));
    }

    public function test_the_size_is_read_once_per_image_version(): void
    {
        $path = $this->image(400, 300);
        $media = $this->media(5, $path);
        $this->assertSame(['width' => 400, 'height' => 300], MediaSize::of($media));

        unlink($path);
        $this->assertSame(['width' => 400, 'height' => 300], MediaSize::of($media), 'lần sau lấy từ bộ nhớ, không đọc lại tệp');

        $this->assertNull(MediaSize::of($this->media(6, $path)), 'ảnh khác (id khác) thì đọc riêng và thấy tệp đã mất');
    }
}
