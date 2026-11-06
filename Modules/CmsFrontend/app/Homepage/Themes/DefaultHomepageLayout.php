<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

/**
 * Khuyến nông (`default`). Thứ tự mặc định:
 * banner, dải thông báo, Tin tức, Thông tin (Thông báo + Văn bản), Lịch & truy xuất, Hoạt động, Thư viện, Đối tác.
 * Văn bản nằm cạnh Thông báo ngay trong cụm Thông tin của theme này.
 */
final class DefaultHomepageLayout extends AbstractThemeLayout
{
    public function themeKey(): string
    {
        return 'default';
    }

    public function zones(): array
    {
        return ['hero' => 'homepage.zones.hero', 'before-main' => 'homepage.zones.before_main', 'main' => 'homepage.zones.main'];
    }

    public function blocks(): array
    {
        $body = ['before-main', 'main'];

        return [
            'hero' => self::block('hero', ['hero'], self::standard('hero-banner', ['crossfade-slider'])),
            'notice-ticker' => self::block('notice-ticker', $body, self::standard('blocks.notice-ticker')),
            'news' => self::block('article-list', $body, self::standard('news')),
            'information' => self::block('information', $body, self::standard('information')),
            'schedules-trace' => self::block('schedules-trace', $body, self::standard('schedules-trace')),
            'activities' => self::block('activities', $body, self::standard('activities')),
            'media' => self::block('media', $body, self::standard('media')),
            'partners-support' => self::block('partners-support', $body, self::standard('partners-support')),
        ];
    }

    public function defaultLayout(): array
    {
        return $this->layoutFrom([
            'hero' => ['hero'],
            'before-main' => ['notice-ticker'],
            'main' => ['news', 'information', 'schedules-trace', 'activities', 'media', 'partners-support'],
        ]);
    }
}
