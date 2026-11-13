<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

/**
 * Nhóm dữ liệu nền của trang chủ (HomeController/HomeBlocks) mà từng loại cụm có thể dùng. Trang chủ chỉ truy vấn các nhóm mà một cụm ĐANG BẬT
 * trong bố cục cần; cụm tắt hoặc không có trong bố cục thì không tốn truy vấn. Bảng là cận trên (cụm dùng nhóm nào ở BẤT KỲ theme nào đều ghi vào):
 * thừa một nhóm chỉ tốn truy vấn, thiếu một nhóm làm cụm mất dữ liệu. Loại cụm mới chưa khai báo ở đây thì được nạp ĐỦ mọi nhóm.
 */
final class HomepageDataNeeds
{
    /** Tên nhóm => mô tả dữ liệu nền (khóa biến trang chủ). */
    public const GROUPS = [
        'news' => 'firstPost, posts',
        'events' => 'events',
        'notices' => 'categoryInfo, categoryPosts',
        'documents' => 'homepageDocuments',
        'examples' => 'guongTieuBieu',
        'services' => 'dichvus',
        'links' => 'links',
        'library' => 'libraryImages',
        'videos' => 'videos',
        'schedules' => 'homeSchedules, homeTraceLinks',
        'tabs' => 'homeCategoryTabs',
        'banners' => 'homeBanners',
        'sections' => 'homeSections',
        'statistics' => 'homeStatistics',
    ];

    /** Khóa Definition => các nhóm cụm đó có thể đọc. */
    private const BY_DEFINITION = [
        'hero' => ['banners'],
        'notice-ticker' => ['notices'],
        'article-list' => ['news', 'events', 'notices'],
        'information' => ['notices'],
        'documents' => ['documents'],
        'schedules-trace' => ['schedules'],
        'trace' => ['schedules'],
        'activities' => ['tabs'],
        'events' => ['events'],
        'examples' => ['examples'],
        'services' => ['services'],
        'media' => ['library', 'videos'],
        'gallery' => ['library'],
        'videos' => ['videos'],
        'partners-support' => ['links'],
        'partners' => ['links'],
        'support' => [],
        'statistics' => ['statistics'],
        'section' => ['links', 'sections'],
        'shortcuts' => [],
        'audience-links' => [],
        'quick-links' => [],
        'cta' => [],
        'contact' => [],
    ];

    /**
     * Các nhóm cần nạp cho kế hoạch render. Không có kế hoạch (null) = nạp tất cả.
     *
     * @param  array{zones: array<string, list<array<string, mixed>>>}|null  $plan
     * @return array<string, true> tên nhóm => true
     */
    public static function forPlan(?array $plan): array
    {
        if ($plan === null) {
            return array_fill_keys(array_keys(self::GROUPS), true);
        }
        $needs = [];
        foreach ($plan['zones'] as $blocks) {
            foreach ($blocks as $block) {
                $groups = self::BY_DEFINITION[$block['definition']] ?? array_keys(self::GROUPS);
                foreach ($groups as $group) {
                    $needs[$group] = true;
                }
            }
        }

        return $needs;
    }
}
