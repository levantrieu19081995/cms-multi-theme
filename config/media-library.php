<?php
return [
    // production
    // 'disk_name' => 'minio',

    // development
    'disk_name' => 'media',

    // ...
    'media_downloader' => App\Services\CustomDownloader::class,
];
