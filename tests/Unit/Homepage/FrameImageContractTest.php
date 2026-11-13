<?php

declare(strict_types=1);

namespace Tests\Unit\Homepage;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ảnh trong các cụm trang chủ Khuyến nông không bị cắt: mọi ảnh của khung đi qua partial frame-image (hiện nguyên + nền mờ),
 * CSS không ép ảnh phủ kín khung. Không cần database.
 */
final class FrameImageContractTest extends TestCase
{
    private const HOME = 'Modules/CmsFrontend/resources/views/screens/default/components/home/';

    private const CSS = 'Modules/CmsFrontend/resources/assets/css/screens/default/home.css';

    private function source(string $relative): string
    {
        return (string) file_get_contents(base_path($relative));
    }

    /** @return array<string, array{string}> */
    public static function clusters(): array
    {
        return [
            'tin tức, thông báo, nổi bật, sự kiện' => ['story-card.blade.php'],
            'banner' => ['hero-banner.blade.php'],
            'video' => ['video-card.blade.php'],
            'gương tiêu biểu' => ['blocks/examples.blade.php'],
            'thư viện ảnh' => ['variants/bao-chi/gallery.blade.php'],
        ];
    }

    #[DataProvider('clusters')]
    public function test_every_photo_of_a_cluster_goes_through_the_frame_partial(string $file): void
    {
        $source = $this->source(self::HOME.$file);

        $this->assertStringContainsString('components.home.frame-image', $source);
        $this->assertSame(0, preg_match('/<img\b/i', $source), 'còn thẻ <img> viết thẳng, ảnh sẽ bị cắt theo CSS chung');
    }

    public function test_the_partial_draws_the_whole_photo_over_a_blurred_copy_of_itself(): void
    {
        $html = Blade::render($this->source(self::HOME.'frame-image.blade.php'), ['src' => '/media/a.jpg', 'width' => 640, 'height' => 360, 'priority' => true]);

        $this->assertSame(2, substr_count($html, 'src="/media/a.jpg"'), 'cùng một địa chỉ: trình duyệt chỉ tải một lần');
        $this->assertMatchesRegularExpression('/<img class="cms-frame-bg"[^>]*aria-hidden="true"[^>]*alt=""|<img class="cms-frame-bg"[^>]*alt=""[^>]*aria-hidden="true"/', $html);
        $this->assertMatchesRegularExpression('/<img class="cms-frame-img"[^>]*fetchpriority="high"[^>]*width="640" height="360"/', $html);
        $this->assertSame(1, substr_count($html, 'fetchpriority="high"'), 'chỉ ảnh thật được ưu tiên');
        $this->assertSame(0, substr_count($html, 'loading="lazy"'), 'ảnh đầu trang: cả ảnh nền cũng tải ngay, không nháy trắng');

        $lazy = Blade::render($this->source(self::HOME.'frame-image.blade.php'), ['src' => '/media/a.jpg']);
        $this->assertSame(2, substr_count($lazy, 'loading="lazy"'));
        $this->assertStringNotContainsString('width=', $lazy);
    }

    public function test_css_shows_the_photo_whole_and_never_forces_it_to_fill_the_frame(): void
    {
        $css = $this->source(self::CSS);

        $this->assertMatchesRegularExpression('/\.cms-frame-img\s*\{[^}]*object-fit:\s*contain/', $css);
        $this->assertMatchesRegularExpression('/\.cms-frame-bg\s*\{[^}]*object-fit:\s*cover[^}]*filter:\s*blur/', $css);
        $this->assertMatchesRegularExpression('/\.cms-frame-img\s*\{[^}]*max-width:\s*100%[^}]*mask-image:[^}]*mask-composite:\s*intersect/', $css, 'viền ảnh mờ dần vào nền mờ');
        $this->assertMatchesRegularExpression('/\.cms-hero-slide\s*>\s*\.cms-frame-img\s*\{[^}]*object-fit:\s*contain/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.cms-frame-img\s*\{[^}]*object-fit:\s*cover/', $css);
    }

    public function test_album_photos_keep_their_own_ratio(): void
    {
        $page = $this->source('Modules/CmsFrontend/resources/views/screens/default/pages/frontend/hinh-anh/show.blade.php');

        $this->assertStringContainsString('--r: {{ $img[\'ratio\'] }}', $page);
        $this->assertMatchesRegularExpression('/\.cms-album-item img\s*\{[^}]*object-fit:\s*contain/', $page);
        $this->assertStringNotContainsString('object-fit: cover', $page);
        $this->assertStringNotContainsString('height: 220px', $page);
    }
}
