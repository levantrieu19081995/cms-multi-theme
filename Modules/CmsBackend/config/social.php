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
];
