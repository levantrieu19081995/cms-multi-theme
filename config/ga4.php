<?php

return [
    'measurement_id' => env('GA4_ENV') === 'prod'
        ? env('GA4_MEASUREMENT_ID_PROD')
        : env('GA4_MEASUREMENT_ID_DEV'),

    'credentials_path' => env('GA4_ENV') === 'prod'
        ? env('GA4_CREDENTIALS_PATH_PROD')
        : env('GA4_CREDENTIALS_PATH_DEV'),

    'property_id' => env('GA4_ENV') === 'prod'
        ? env('GA4_PROPERTY_ID_PROD')
        : env('GA4_PROPERTY_ID_DEV'),
];