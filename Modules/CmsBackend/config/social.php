<?php

/*
|--------------------------------------------------------------------------
| Social channels (Truyền thông đa kênh)
|--------------------------------------------------------------------------
| Read with config('cmsbackend.social.facebook.*'); never call env() outside the config files. The App Secret stays on the
| server: it is never put in a view, a URL, JavaScript, a log line or an error message.
|
| META_REDIRECT_URI must be exactly the callback address registered in the Meta App ("Valid OAuth Redirect URIs"):
|   {APP_URL}/{CMS_ROUTE_PREFIX}/truyen-thong/tai-khoan/facebook/callback
*/

return [
    'facebook' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'graph_version' => env('META_GRAPH_VERSION'),
        'redirect_uri' => env('META_REDIRECT_URI'),

        // Phase 2A asks only for these. read_insights, pages_manage_engagement and pages_manage_metadata belong to later phases.
        'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],

        // Seconds
        'timeout' => 15,
        'connect_timeout' => 5,
        // Life of the one-time OAuth state and of the page list kept between the callback and the confirmation.
        'state_ttl' => 600,
    ],

    // Zalo Official Account (OAuth v4 with PKCE). ZALO_REDIRECT_URI must be exactly the Callback URL registered in the Zalo app:
    //   {APP_URL}/{CMS_ROUTE_PREFIX}/truyen-thong/tai-khoan/zalo/callback
    // Permissions (manage OA information, manage articles) are granted in the Zalo app settings, not through a scope parameter.
    'zalo' => [
        'app_id' => env('ZALO_APP_ID'),
        'app_secret' => env('ZALO_APP_SECRET'),
        'redirect_uri' => env('ZALO_REDIRECT_URI'),

        // Endpoints of the Zalo Platform documentation (docs.zaloplatforms.com). Kept here so a change of address needs no code.
        'authorize_url' => 'https://oauth.zaloapp.com/v4/oa/permission',
        'token_url' => 'https://oauth.zaloapp.com/v4/oa/access_token',
        'api_base' => 'https://openapi.zalo.me/v2.0/',

        // A refresh token lives 3 months; the access token (25 hours) is renewed with it before it expires.
        'refresh_token_months' => 3,
        'access_token_margin' => 300,

        // A new article is processed by Zalo asynchronously: ask for its id a few times right after creating it, then leave it
        // "processing" for the next synchronization.
        'verify_attempts' => 3,
        'verify_delay_ms' => 1000,

        'timeout' => 15,
        'connect_timeout' => 5,
        'state_ttl' => 600,
    ],
];
