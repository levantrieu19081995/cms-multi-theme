<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

/**
 * Nam Bình Dương (`nam-binh-duong`, SBUT). Thứ tự mặc định của trang chủ. Theme này không có cụm Thông tin,
 * Hoạt động hay Lịch & truy xuất; chúng chỉ xuất hiện khi theme có renderer riêng.
 */
final class NamBinhDuongHomepageLayout extends AbstractThemeLayout
{
    public function themeKey(): string
    {
        return 'nam-binh-duong';
    }

    public function zones(): array
    {
        return ['hero' => 'homepage.zones.hero', 'main' => 'homepage.zones.main', 'after-main' => 'homepage.zones.after_main'];
    }

    public function blocks(): array
    {
        $body = ['main', 'after-main'];

        return [
            'hero' => self::block('hero', ['hero'], self::standard('university-hero', ['reveal'])),
            'shortcuts' => self::block('shortcuts', $body, self::standard('shortcuts')),
            'introduction' => self::block('section', $body, self::standard('introduction', ['reveal'])),
            'programs' => self::block('section', $body, self::standard('programs', ['reveal'])),
            'statistics' => self::block('statistics', $body, self::standard('statistics', ['count-up'])),
            'research' => self::block('section', $body, self::standard('research', ['reveal'])),
            'news' => self::block('article-list', $body, self::standard('news', ['reveal'])),
            'admissions' => self::block('section', $body, self::standard('admissions', ['reveal'])),
            'audience-links' => self::block('audience-links', $body, self::standard('audience-links', ['stagger'])),
            'media' => self::block('media', $body, self::standard('media', ['reveal'])),
            'partners-support' => self::block('partners-support', $body, self::standard('partners-support', ['reveal'])),
            'quick-links' => self::block('quick-links', $body, self::standard('blocks.quick-links', ['stagger'])),
            'cta' => self::block('cta', $body, self::standard('blocks.cta', ['reveal'])),
        ];
    }

    public function defaultLayout(): array
    {
        return $this->layoutFrom([
            'hero' => ['hero'],
            'main' => ['shortcuts', 'introduction', 'programs', 'statistics', 'research', 'news', 'admissions', 'audience-links', 'media', 'partners-support'],
            'after-main' => ['quick-links', 'cta'],
        ]);
    }
}
