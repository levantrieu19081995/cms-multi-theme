<?php

return [
    'name' => 'CmsBackend',
    'unit_key' => env('CMS_UNIT_KEY', 'nam-binh-duong'),
    'unit_name' => env('CMS_UNIT_NAME', 'Nam Bình Dương'),
    'unit_type' => env('CMS_UNIT_TYPE', 'education'),
    // Administration prefix (/cms). The public website is served from the root of the domain: public_route_prefix is empty.
    'route_prefix' => trim(env('CMS_ROUTE_PREFIX', 'cms'), '/'),
    'public_route_prefix' => '',
    'share_source_media' => env('CMS_SHARE_SOURCE_MEDIA', true),
    'register_migrations' => env('CMS_REGISTER_MIGRATIONS', false),
    'editorial_database' => env('CMS_DB_DATABASE', 'cms_workspace'),
];
