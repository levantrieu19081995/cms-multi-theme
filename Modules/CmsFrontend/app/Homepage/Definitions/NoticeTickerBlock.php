<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

/** Dải thông báo mới chạy dưới banner (cùng chuyên mục thông báo với cụm Thông báo và văn bản). */
final class NoticeTickerBlock extends AbstractBlockDefinition
{
    protected string $key = 'notice-ticker';

    protected string $icon = 'ri-notification-3-line';

    protected ?string $route = 'cms.dashboard.cdn.bai-viet.index';


    public function __construct()
    {
        $this->schema = ['news_link' => self::link('cms.dashboard.cdn.bai-viet.index', 'homepage.fields.notice_ticker_help')];
    }

    /** Dải thông báo dùng chuyên mục thông báo của cụm `information` CÙNG THEME (nơi duy nhất chọn chuyên mục này). */
    public function resolve(array $settings, array $data): array
    {
        $theme = (string) ($data['_theme'] ?? '');
        $category = (int) (app(\Modules\CmsFrontend\Homepage\HomepageSettingsService::class)->read($theme, 'information')['notice_category'] ?? 0);
        if ($theme === '' || $category <= 0) {
            return [];
        }
        $notices = app(\Modules\CmsFrontend\Services\HomeBlocks::class)->noticePosts($category);

        return ['categoryInfo' => $notices['info'], 'categoryPosts' => $notices['posts']];
    }
}
