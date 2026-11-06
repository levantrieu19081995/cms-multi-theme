<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use App\Models\Option;
use Modules\CmsBackend\Services\ScreenTheme;

/**
 * Homepage options owned by Khuyến nông (PLHĐ blocks), stored in the CMS database `options` table
 * under a `cms_` prefix so they never collide with the shared WEB_* keys.
 */
final class HomeSettings
{
    public const SCHEDULE_DEPARTMENT = 'cms_home_category_lich_so';

    public const SCHEDULE_BOARD = 'cms_home_category_lich_bgd';

    public const TRACE_MARKET_URL = 'cms_trace_market_url';

    public const TRACE_MARKET_IMAGE = 'cms_trace_market_image';

    public const TRACE_PORTAL_URL = 'cms_trace_portal_url';

    public const TRACE_PORTAL_IMAGE = 'cms_trace_portal_image';

    /** Menu id (Menu screen) for the footer icon links; managed by cms:home-install, not the settings form. */
    public const FOOTER_MENU = 'cms_footer_menu';

    /** JSON array of term_taxonomy ids, in tab order, for the homepage "Chuyên mục nổi bật" block. */
    public const FEATURED_CATEGORIES = 'cms_home_featured_categories';

    /** PLHĐ order: Tin ngành, Tin địa bàn, Các tỉnh thành, Mô hình, KHKT mới, Chính sách, SP dịch vụ, Giá nông sản, OCOP, Văn nghệ. */
    public const FEATURED_DEFAULT = [36, 38, 37, 39, 40, 41, 42, 43, 44, 45];

    /** Option key holding the tab order of one theme: "cms_home_featured_categories.<theme>". */
    public static function featuredKey(?string $theme = null): string
    {
        return self::FEATURED_CATEGORIES.'.'.($theme ?? app(ScreenTheme::class)->key('public'));
    }

    /**
     * Tab order of the theme (default: the active public theme) as an array of term_taxonomy ids.
     * A theme that was never saved keeps using the earlier shared list, then the built-in default.
     *
     * @return int[]
     */
    public static function featuredCategories(?string $theme = null): array
    {
        $value = Option::where('key', self::featuredKey($theme))->value('value')
            ?? Option::where('key', self::FEATURED_CATEGORIES)->value('value');
        $ids = $value === null ? self::FEATURED_DEFAULT : (json_decode((string) $value, true) ?: []);

        return array_values(array_unique(array_map('intval', array_filter((array) $ids, 'is_numeric'))));
    }

    /** JSON list of {label, value} for the homepage "Các con số nổi bật" block (public themes nam-binh-duong / ban-de-an). */
    public const STATISTICS = 'cms_home_statistics';

    public const STATISTICS_MAX = 4;

    /** option_translations key of the label of statistic slot N ("cms_home_statistics.label.N"). */
    public static function statisticLabelKey(int $slot): string
    {
        return self::STATISTICS.'.label.'.$slot;
    }

    /**
     * Rows of the "Các con số nổi bật" block. A row keeps the slot (position on the admin form) it was saved in, so its
     * labels in other languages stay attached to it even when another slot is emptied.
     *
     * Default language: Vietnamese label (stored, or the theme config until an admin saves). Another language: the label is
     * the one entered in option_translations for that slot (plan: shared value, per-language label); a slot without a
     * label in that language is not shown, and nothing is borrowed from Vietnamese.
     *
     * @param  array<int, array{label: string, value: ?string}>  $defaults  theme config shown until an admin saves the block
     * @return array<int, array{slot: int, label: string, value: string}>
     */
    public static function statistics(array $defaults = [], ?string $locale = null): array
    {
        $locales = app(\App\Services\LocaleManager::class);
        $locale ??= $locales->current();
        $stored = json_decode((string) Option::where('key', self::STATISTICS)->value('value'), true);
        $rows = is_array($stored)
            ? self::cleanStatistics($stored)
            : self::cleanStatistics(array_map(fn ($row) => ['label' => (string) $row['label'], 'value' => (string) ($row['value'] ?? '')], array_values($defaults)));
        if ($locales->isDefault($locale)) {
            return $rows;
        }

        $texts = app(\App\Services\OptionTranslations::class);
        $result = [];
        foreach ($rows as $row) {
            // Defaults shown before the first save have no stored English label: use the fixed text of the language file.
            $label = $texts->get(self::statisticLabelKey($row['slot']), $locale)
                ?? (is_array($stored) ? null : (\Illuminate\Support\Facades\Lang::has('frontend.statistics_default.'.$row['slot'], $locale) ? __('frontend.statistics_default.'.$row['slot'], [], $locale) : null));
            if ($label !== null && trim($label) !== '') {
                $result[] = ['slot' => $row['slot'], 'label' => trim($label), 'value' => $row['value']];
            }
        }

        return $result;
    }

    /** @param array<int|string, mixed> $rows each row: ['label' => ..., 'value' => ...] keyed by form slot; rows without a Vietnamese label are dropped */
    public static function saveStatistics(array $rows): void
    {
        Option::updateOrCreate(['key' => self::STATISTICS], ['value' => json_encode(self::cleanStatistics($rows), JSON_UNESCAPED_UNICODE), 'autoload' => 1]);
    }

    /**
     * Languages other than the default: label per slot into option_translations (blank = no label in that language). A
     * slot whose Vietnamese label was cleared loses its other-language labels as well.
     *
     * @param  array<int|string, mixed>  $rows  the posted rows, keyed by slot, each possibly holding label_{locale}
     */
    public static function saveStatisticLabels(array $rows): void
    {
        $locales = app(\App\Services\LocaleManager::class);
        $service = app(\App\Services\TranslationService::class);
        for ($slot = 0; $slot < self::STATISTICS_MAX; $slot++) {
            $row = (array) ($rows[$slot] ?? []);
            $active = trim((string) ($row['label'] ?? '')) !== '';
            $values = [];
            foreach ($locales->alternates() as $locale) {
                $values[$locale] = $active ? (string) ($row['label_'.$locale] ?? '') : '';
            }
            $service->saveOptionTranslations(self::statisticLabelKey($slot), $values);
        }
    }

    /** @return array<int, array{slot: int, label: string, value: string}> */
    private static function cleanStatistics(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row, $index) => [
                'slot' => (int) ($row['slot'] ?? $index),
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn ($row) => $row['label'] !== '' && $row['slot'] >= 0 && $row['slot'] < self::STATISTICS_MAX)
            ->unique('slot')
            ->take(self::STATISTICS_MAX)->values()->all();
    }

    /** Category for the homepage "Thông tin – Thông báo" block (shared key name, CMS database). */
    public const CATEGORY_HOME = 'web_category_home';

    /** Bộ lọc cụm "Văn bản, tài liệu" ở trang chủ: `lvb:<id>` (loại văn bản) hoặc `nvb:<id>` (nhóm văn bản); trống = mới nhất. */
    public const DOCUMENT_FILTER = 'cms_home_document_filter';

    public const KEYS = [
        self::SCHEDULE_DEPARTMENT, self::SCHEDULE_BOARD,
        self::TRACE_MARKET_URL, self::TRACE_MARKET_IMAGE, self::TRACE_PORTAL_URL, self::TRACE_PORTAL_IMAGE,
        self::CATEGORY_HOME, self::DOCUMENT_FILTER,
    ];

    /** @return array<string, string> key => value ('' when not set) */
    public static function all(): array
    {
        $stored = Option::whereIn('key', self::KEYS)->pluck('value', 'key');

        return collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => (string) ($stored[$key] ?? '')])->all();
    }

    /** @return array{column: string, id: int}|null cột của bảng văn bản cần lọc và giá trị; null khi lấy văn bản mới nhất */
    public static function documentFilter(): ?array
    {
        $value = (string) Option::where('key', self::DOCUMENT_FILTER)->value('value');
        if (preg_match('/^(lvb|nvb):(\d+)$/D', trim($value), $match) !== 1) {
            return null;
        }

        return ['column' => $match[1].'_id', 'id' => (int) $match[2]];
    }

}
