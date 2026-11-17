<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

/**
 * Ban Đề án v2 (`bandean-v2`). Bản chép độc lập của `ban-de-an` để thiết kế lại: bắt đầu giống hệt, rồi đổi tự do mà không ảnh hưởng theme `ban-de-an` đang chạy.
 * Thứ tự mặc định của trang chủ như Ban Đề án, trừ: banner hero theo thiết kế bannerSBUT (các con số nổi bật nằm ngay trên banner nên cụm
 * `statistics` không còn trong bố cục mặc định; vẫn bật lại được trong Bố cục trang chủ) và bỏ dải lối tắt "01 Đề án - Dự án / 02 Tin tức / 03 Tra cứu" (`proposal-quick-links`). Khai báo độc lập: không phụ thuộc theme nào khác.
 */
final class BandeanV2HomepageLayout extends AbstractThemeLayout
{
    public function themeKey(): string
    {
        return 'bandean-v2';
    }

    public function zones(): array
    {
        return ['hero' => 'homepage.zones.hero', 'main' => 'homepage.zones.main', 'after-main' => 'homepage.zones.after_main'];
    }

    public function blocks(): array
    {
        $body = ['main', 'after-main'];

        return [
            'hero' => self::block('hero', ['hero'], self::standard('university-hero', ['reveal', 'count-up'])),
            'shortcuts' => self::block('shortcuts', $body, self::standard('shortcuts')),
            'introduction' => self::block('section', $body, self::standard('introduction', ['reveal'])),
            'programs' => self::block('section', $body, self::standard('programs', ['reveal'])),
            'statistics' => self::block('statistics', $body, self::standard('statistics', ['count-up'])),
            'research' => self::block('section', $body, self::standard('research', ['reveal'])),
            'news' => self::block('article-list', $body, self::standard('news', ['reveal'])),
            'projects' => self::block('section', $body, self::standard('proposal-projects', ['reveal'])),
            'admissions' => self::block('section', $body, self::standard('admissions', ['reveal'])),
            'audience-links' => self::block('audience-links', $body, self::standard('audience-links', ['stagger'])),
            'media' => self::block('media', $body, self::standard('media', ['reveal'])),
            'documents' => self::block('documents', $body, ['featured' => ['renderer' => 'proposal-documents', 'effects' => ['reveal']]]),
            'partners-support' => self::block('partners-support', $body, self::standard('partners-support', ['reveal'])),
            'quick-links' => self::block('quick-links', $body, self::standard('blocks.quick-links', ['stagger'])),
            'cta' => self::block('cta', $body, self::standard('blocks.cta', ['reveal'])),
            'contact' => self::block('contact', $body, self::standard('blocks.contact', ['reveal'])),
        ];
    }

    public function defaultLayout(): array
    {
        return $this->layoutFrom([
            'hero' => ['hero'],
            'main' => ['shortcuts', 'introduction', 'programs', 'research', 'news', 'projects', 'admissions', 'audience-links', 'media', 'documents', 'partners-support'],
            'after-main' => ['quick-links', 'cta', 'contact'],
        ]);
    }
}
