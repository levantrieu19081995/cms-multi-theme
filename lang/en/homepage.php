<?php

// Homepage Layout Manager (Developer Settings + Homepage settings).
return [
    'blocks' => [
        'hero' => ['name' => 'Top banner', 'description' => 'The top block (banner/hero). Content is managed in the Banner screen.'],
        'notice-ticker' => ['name' => 'Latest notice ticker', 'description' => 'The newest notice below the banner, taken from the homepage notice category.'],
        'article-list' => ['name' => 'Featured news', 'description' => 'Posts switched on as "Show on homepage" in the news list.'],
        'information' => ['name' => 'Notices', 'description' => 'Latest posts of the notice category.'],
        'documents' => ['name' => 'Documents', 'description' => 'Documents as a large block of their own, filtered by document type or group.'],
        'schedules-trace' => ['name' => 'Work schedules', 'description' => 'Two work-schedule categories (department leaders, board of directors).'],
        'trace' => ['name' => 'Traceability', 'description' => 'Two traceability portals: title, caption, link and image.'],
        'activities' => ['name' => 'Featured categories', 'description' => 'Each category chosen in Homepage settings is one row of posts.'],
        'events' => ['name' => 'Featured events', 'description' => 'Events switched on for the homepage.'],
        'examples' => ['name' => 'Role models', 'description' => 'Published role models (the Khuyến nông theme titles them "Digital extension").'],
        'services' => ['name' => 'Services', 'description' => 'Active services.'],
        'media' => ['name' => 'Photo library and videos', 'description' => 'Albums and videos switched on for the homepage.'],
        'gallery' => ['name' => 'Photo library', 'description' => 'Albums switched on for the homepage.'],
        'videos' => ['name' => 'Videos', 'description' => 'Videos switched on for the homepage.'],
        'partners-support' => ['name' => 'Partners and links', 'description' => 'Partners/links shown on the homepage.'],
        'partners' => ['name' => 'Partner units', 'description' => 'Partners/links shown on the homepage.'],
        'support' => ['name' => 'Support and advice', 'description' => 'Ask a question (Q&A) and contact for advice (when the Contact feature is on).'],
        'statistics' => ['name' => 'Key figures', 'description' => 'Four key figures entered in Homepage settings.'],
        'shortcuts' => ['name' => 'Top shortcuts', 'description' => 'Shortcuts taken from the main menu of the website.'],
        'section' => ['name' => 'Category block', 'description' => 'Latest posts of one category (introduction, programmes, research, admissions, projects...).'],
        'audience-links' => ['name' => 'Audience links', 'description' => 'Links for each user group, declared in the theme configuration.'],
        'quick-links' => ['name' => 'Feature shortcuts', 'description' => 'Strip of shortcuts to Documents, Photo library, Services and Q&A.'],
        'cta' => ['name' => 'Contact call to action', 'description' => 'Call to action linking to the Contact page (only when the Contact feature is on).'],
        'contact' => ['name' => 'Contact block', 'description' => 'Contact block at the bottom of the page (only when the Contact feature is on).'],
    ],

    'zones' => ['hero' => 'Top of page', 'before_main' => 'Before main content', 'main' => 'Main content', 'after_main' => 'After main content'],

    'fields' => [
        'notice_ticker_help' => 'The notice category is chosen in the "Notices" block', 'schedule_department_title' => 'Department leaders schedule title', 'schedule_board_title' => 'Board of Directors schedule title', 'trace_market_title' => 'Traceability marketplace title', 'trace_market_caption' => 'Traceability marketplace short text', 'trace_portal_title' => 'Traceability portal title', 'trace_portal_caption' => 'Traceability portal short text', 'banner_link' => 'Manage banners', 'notice_category' => 'Notice category', 'news_link' => 'Manage news (switch on "Show on homepage" in the list)',
        'document_filter' => 'Documents shown', 'documents_link' => 'Manage documents', 'schedule_department' => 'Department leaders schedule category',
        'schedule_board' => 'Board of Directors schedule category', 'trace_market_url' => 'Traceability marketplace link', 'trace_market_image' => 'Traceability marketplace image',
        'trace_portal_url' => 'Traceability portal link', 'trace_portal_image' => 'Traceability portal image', 'featured_categories' => 'Featured categories (tab order)',
        'events_link' => 'Manage events', 'examples_link' => 'Manage role models', 'gallery_link' => 'Manage photo library', 'video_link' => 'Manage videos',
        'partners_link' => 'Manage partners', 'services_link' => 'Manage services', 'support_link' => 'Manage Q&A', 'statistics_link' => 'Enter key figures', 'menu_link' => 'Manage menus',
        'section_category' => 'Category', 'section_category_help' => 'Leave empty to use the theme default category.',
    ],

    'settings' => [
        'title' => 'Homepage block content', 'intro' => 'Only the blocks switched on for the current theme are shown. Switching blocks on/off and ordering them is done by the Developer.',
        'none' => 'No block is switched on.', 'manage' => 'Go to the management screen', 'managed_by' => 'Content is managed by its own feature.',
        'missing' => 'The category was deleted (ID :ids). Please choose again.', 'choose' => '-- Choose --', 'no_settings' => 'This block has no content settings.',
        'document_latest' => 'Latest (all types)', 'document_type' => 'Type', 'document_group' => 'Group', 'saved' => 'Homepage block settings saved.',
        'ordered_hint' => 'The order you choose is the display order.',
    ],

    'variants' => ['standard' => 'Standard', 'featured' => 'Featured'],

    'effects' => ['reveal' => 'Text revealed by line', 'stagger' => 'Rise one by one', 'count-up' => 'Count up', 'crossfade-slider' => 'Crossfade slider', 'gallery-orbit' => '3D photo ring'],

    'ui' => [
        'apply_now' => 'Apply now', 'apply_now_confirm' => 'Save and apply this layout to the public homepage right away, without a preview?', 'applied_now' => 'Layout applied to the homepage (:count blocks).',
        'effect' => 'Effect', 'effect_default' => 'Theme default', 'effect_none' => 'No effect', 'effect_reveal' => 'Text revealed by line', 'effect_stagger' => 'Rise one by one', 'effect_not_ready' => 'The effect column does not exist yet (migration add_effect_key_to_homepage_theme_blocks not run): blocks use the theme default effect and cannot be changed.', 'tab' => 'Homepage layout', 'theme' => 'Theme', 'layout_state' => 'Layout', 'state_live' => 'Installed in the database', 'state_default' => 'Not installed (showing the default layout)',
        'note' => 'Working layout = the form below (unsaved). Preview creates a server-side temporary copy and does not change the homepage. Only Apply on the preview page changes the Live layout. Legacy mode keeps today\'s homepage.',
        'not_ready' => 'The feature tables do not exist yet (migration not run). Every theme stays in Legacy mode.',
        'registry_errors' => 'Declarations in code are inconsistent:', 'no_theme' => 'No theme supports layouts in this build.',
        'install' => 'Install default layout', 'installed' => ':count blocks installed.', 'reset' => 'Restore default', 'confirm_reset' => 'Restore this theme\'s default layout? Position, variant and on/off are reset; content settings are kept.',
        'reset_done' => 'Default layout restored.', 'drag' => 'Drag to reorder', 'enabled' => 'On',
        'variant' => 'Variant', 'zone' => 'Zone', 'move_up' => 'Up', 'move_down' => 'Down', 'missing_definition' => 'Missing Definition', 'missing_renderer' => 'Missing renderer',
        'preview' => 'Preview', 'preview_hint' => 'Check with the real renderer, data and effects of the live page before applying.',
        'review_title' => 'Homepage layout preview', 'preview_mode' => 'PREVIEW MODE', 'snapshot_at' => 'Snapshot at', 'not_applied' => 'Not applied', 'same_as_live' => 'Same as the layout in use',
        'device' => 'Screen size', 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile', 'replay' => 'Replay effects', 'back_to_config' => 'Back to configuration',
        'apply' => 'Apply layout', 'confirm_apply' => 'Apply this layout as the theme\'s Live layout?', 'cannot_apply' => 'Cannot apply:', 'applied' => 'Layout applied (:count blocks).',
    ],

    'errors' => [
        'preview_invalid' => 'Invalid layout (zone, variant or block not supported by the theme).', 'preview_expired' => 'The preview expired or does not belong to this session. Create it again.',
        'apply_blocked' => 'Some blocks lack a renderer/asset or have invalid settings, so the layout cannot be applied.', 'not_ready' => 'The migration of this feature has not been run.',
        'theme_unknown' => 'Unsupported theme.', 'unknown_instance' => 'Unknown block.', 'unexpected' => 'Unexpected error, see the log.',
        'theme_not_in_build' => 'This theme has no assets in the current build, so it cannot be previewed.',
        'required' => 'Required.', 'category_invalid' => 'The category does not exist.', 'number_invalid' => 'Invalid number or out of range.',
        'too_long' => 'Text is too long.', 'option_invalid' => 'Invalid choice.', 'url_invalid' => 'Invalid link (http/https or starting with /).',
    ],

    'issues' => [
        'missing_definition' => 'Missing Definition', 'invalid_zone' => 'Invalid zone', 'invalid_effect' => 'Invalid effect', 'missing_effect' => 'Missing effect/asset', 'invalid_variant' => 'Invalid variant', 'missing_renderer' => 'Missing renderer',
        'unknown_instance' => 'Block not declared by the theme', 'duplicate' => 'Duplicate block', 'theme_unknown' => 'Invalid theme', 'payload' => 'Invalid payload',
    ],

    'render' => [
        'block_error' => 'This block could not be displayed.', 'missing_settings' => 'This block has not chosen its required content source yet:',
    ],
];
