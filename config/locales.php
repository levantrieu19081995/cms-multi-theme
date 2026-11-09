<?php

/*
| Ngôn ngữ nội dung công khai (xem kế hoạch đa ngôn ngữ).
| - Tiếng Việt giữ nguyên URL hiện tại; các ngôn ngữ còn lại có tiền tố /{ngôn ngữ} đặt sau tiền tố CMS_ROUTE_PREFIX.
| - Danh sách này không thay thế config('app.available_locales') của giao diện quản trị (Phase 9).
*/
return [
    'default' => env('CMS_DEFAULT_LOCALE', 'vi'),

    'supported' => [
        'vi' => 'Tiếng Việt',
        'en' => 'English',
    ],

    /**
     * Transition to the translation tables as the official source (plan: Phase 15).
     * false (default): public pages in the default language read the original columns (posts.title/slug/excerpt/content,
     *                  terms.name/slug, menu_items.title/url), which are still written together with the Vietnamese translation.
     * true:            they read the Vietnamese translation instead, falling back to the column only for a record that has no
     *                  Vietnamese translation row. Switch on once every record has its `vi` row (see the backfill migration).
     * The original columns are neither dropped nor stopped being written by this setting.
     */
    'read_default_from_translations' => (bool) env('CMS_READ_DEFAULT_FROM_TRANSLATIONS', false),

    /**
     * Public routes of content that lives in tables of its own and has no translation tables yet (documents, Q&A, services,
     * partners, role models, surveys). They exist for the default language only: on a page in another language a link to them goes to
     * the "content is being updated" notice instead of opening the Vietnamese page.
     */
    'default_only_routes' => [
        'cms.frontend.vanban.',
        'cms.frontend.hoidap.',
        'cms.frontend.khaosat.',   // Khảo sát (gồm Định hướng nghề nghiệp): câu hỏi/ngành do quản trị nhập, chưa có bảng bản dịch
        'cms.frontend.dichvu.',
        'cms.doi-tac.',
        'cms.guong-tieu-bieu.',
    ],

    /** Open Graph locale per language. */
    'og_locale' => [
        'vi' => 'vi_VN',
        'en' => 'en_US',
    ],

    /** Mã dùng cho thuộc tính lang/hreflang. */
    'html_lang' => [
        'vi' => 'vi',
        'en' => 'en',
    ],

    /** Dublin Core language code (ISO 639-2). */
    'dc_language' => [
        'vi' => 'vie',
        'en' => 'eng',
    ],
];
