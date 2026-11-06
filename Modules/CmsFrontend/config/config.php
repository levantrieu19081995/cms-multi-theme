<?php

return [
    'name' => 'CmsFrontend',
    'brand' => [
        'name' => env('CMS_UNIT_NAME', 'Nam Bình Dương'),
        'unit' => env('CMS_UNIT_NAME', 'Nam Bình Dương'),
        'parent_unit' => env('CMS_PARENT_UNIT', ''),
        'banner' => 'assets/img/khuyen-nong-can-tho/brand-banner.png',
        'facebook' => env('CMS_FACEBOOK_URL'),
        'youtube' => env('CMS_YOUTUBE_URL'),
        'zalo' => env('CMS_ZALO_URL'),
        'messenger' => env('CMS_MESSENGER_URL'),
    ],
    // Main menu icons: keyword in the slugged item title => Remix Icon class (first match wins).
    'menu_icons' => [
        'trang-chu' => 'ri-home-5-fill',
        'gioi-thieu' => 'ri-information-line',
        'tin-tuc' => 'ri-newspaper-line',
        'dich-vu' => 'ri-service-line',
        'su-kien' => 'ri-calendar-event-line',
        'van-ban' => 'ri-file-text-line',
        'tai-lieu' => 'ri-book-open-line',
        'thu-vien' => 'ri-gallery-line',
        'video' => 'ri-video-line',
        'hoi-dap' => 'ri-question-line',
        'khao-sat' => 'ri-survey-line',
        'lien-he' => 'ri-customer-service-2-line',
        'default' => 'ri-arrow-right-s-line',
    ],
    // Footer icon links: first URL path segment => Remix Icon class.
    'footer_quick_icons' => [
        'dich-vu' => 'ri-service-line',
        'su-kien' => 'ri-calendar-event-line',
        'hoi-dap' => 'ri-question-answer-line',
        'lien-he' => 'ri-phone-line',
        'van-ban' => 'ri-file-text-line',
        'thu-vien-hinh-anh' => 'ri-image-line',
        'thu-vien-video' => 'ri-video-line',
        'default' => 'ri-links-line',
    ],
    'home' => [
        'technical_category_slug' => env('CMS_HOME_TECHNICAL_CATEGORY_SLUG', 'ky-thuat-san-xuat'),
    ],
];
