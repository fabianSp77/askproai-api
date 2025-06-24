<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Screenshot Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the automated screenshot service used by Claude
    | and other automation tools.
    |
    */

    'api_token' => env('SCREENSHOT_API_TOKEN'),
    
    'service_email' => env('SCREENSHOT_SERVICE_EMAIL', 'screenshot@askproai.de'),
    
    'chromium_path' => env('CHROMIUM_PATH', '/usr/bin/chromium'),
    
    'default_options' => [
        'width' => 1920,
        'height' => 1080,
        'fullPage' => true,
        'waitUntilNetworkIdle' => true,
        'deviceScaleFactor' => 2,
    ],
    
    'storage_path' => storage_path('app/screenshots'),
    
    'cleanup_days' => 7,
];