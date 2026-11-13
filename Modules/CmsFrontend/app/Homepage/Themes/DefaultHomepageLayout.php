<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Themes;

/**
 * Khuyến nông (`default`). Thứ tự mặc định:
 * banner, dải thông báo, Tin tức, Thông báo, Văn bản, Lịch làm việc, Truy xuất nguồn gốc, Chuyên mục nổi bật, Sự kiện, Gương tiêu biểu,
 * Dịch vụ, Thư viện ảnh, Video, Đối tác, Hỗ trợ và tư vấn.
 * Mỗi nội dung là một cụm riêng để bật/tắt trong tab Bố cục trang chủ. Các cụm có `split_from` được tách từ cụm ghép cũ
 * (information, schedules-trace, activities, media, partners-support): bố cục đã cài trên server giữ nguyên vị trí và trạng thái của chúng.
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
            'documents' => self::block('documents', $body, self::standard('blocks.documents'), splitFrom: 'information'),
            'schedules-trace' => self::block('schedules-trace', $body, self::standard('schedules-trace')),
            'trace' => self::block('trace', $body, self::standard('blocks.trace'), splitFrom: 'schedules-trace'),
            'activities' => self::block('activities', $body, self::standard('activities')),
            'events' => self::block('events', $body, self::standard('blocks.events'), splitFrom: 'activities'),
            'examples' => self::block('examples', $body, self::standard('blocks.examples'), splitFrom: 'activities'),
            'services' => self::block('services', $body, self::standard('blocks.services'), splitFrom: 'activities'),
            'gallery' => self::block('gallery', $body, self::standard('blocks.gallery'), splitFrom: 'media'),
            'videos' => self::block('videos', $body, self::standard('blocks.videos'), splitFrom: 'media'),
            'partners' => self::block('partners', $body, self::standard('blocks.partners'), splitFrom: 'partners-support'),
            'support' => self::block('support', $body, self::standard('blocks.support'), splitFrom: 'partners-support'),
        ];
    }

    public function defaultLayout(): array
    {
        return $this->layoutFrom([
            'hero' => ['hero'],
            'before-main' => ['notice-ticker'],
            'main' => [
                'news', 'information', 'documents', 'schedules-trace', 'trace', 'activities', 'events', 'examples', 'services',
                'gallery', 'videos', 'partners', 'support',
            ],
        ]);
    }
}
