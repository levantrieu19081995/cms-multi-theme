<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

/**
 * Ban Đề án (`ban-de-an`). Thứ tự mặc định của trang chủ. Văn bản là một cụm lớn riêng (variant `featured`).
 * Khai báo độc lập: theme này không phụ thuộc khai báo của Nam Bình Dương dù một số partial có cùng tên.
 */
final class BanDeAnHomepageLayout extends AbstractThemeLayout
{
    public function themeKey(): string
    {
        return 'ban-de-an';
    }

    public function zones(): array
    {
        return ['hero' => 'homepage.zones.hero', 'main' => 'homepage.zones.main', 'after-main' => 'homepage.zones.after_main'];
    }

    public function blocks(): array
    {
        $body = ['main', 'after-main'];

        return [
            'hero' => self::block('hero', ['hero'], self::standard('proposal-hero', ['reveal'])),
            'shortcuts' => self::block('shortcuts', $body, self::standard('shortcuts')),
            'proposal-quick-links' => self::block('quick-links', $body, self::standard('proposal-quick-links', ['stagger'])),
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
            'main' => ['shortcuts', 'proposal-quick-links', 'introduction', 'programs', 'statistics', 'research', 'news', 'projects', 'admissions', 'audience-links', 'media', 'documents', 'partners-support'],
            'after-main' => ['quick-links', 'cta', 'contact'],
        ]);
    }
}
