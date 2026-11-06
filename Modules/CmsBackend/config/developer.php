<?php

/*
|--------------------------------------------------------------------------
| Developer Settings
|--------------------------------------------------------------------------
| One environment variable switches and protects the whole feature:
|
|   DEVELOPER_ACCESS_TOKEN_HASH='$2y$12$...'   bcrypt hash of the Developer password (never the password itself).
|
| Missing or empty: every Developer Settings route answers 404. A value: the feature is on; the user must be signed in, have
| the "Quản trị" role and type the Developer password, which is checked with Hash::check(). The unlock lives in the user's
| session (no separate lifetime). The permission manifest is signed with APP_KEY. Read with config('cmsbackend.developer.*');
| never call env() outside the config files.
*/

return [
    'token_hash' => env('DEVELOPER_ACCESS_TOKEN_HASH'),
    // Internal location of the permission manifest (not an environment setting).
    'manifest_path' => storage_path('app/developer/permission-manifest.json'),
];
