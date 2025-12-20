<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gateway Mode Feature Flag
    |--------------------------------------------------------------------------
    |
    | Enable/disable the Service Gateway routing feature.
    | When enabled, calls can be routed to: appointment, service_desk, or hybrid
    |
    */
    'mode_enabled' => env('GATEWAY_MODE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default Gateway Mode
    |--------------------------------------------------------------------------
    |
    | The default mode when no company-specific configuration exists.
    | Options: 'appointment', 'service_desk', 'hybrid'
    |
    */
    'default_mode' => env('GATEWAY_DEFAULT_MODE', 'appointment'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Gateway Modes
    |--------------------------------------------------------------------------
    |
    | List of valid gateway modes for validation.
    |
    */
    'modes' => ['appointment', 'service_desk', 'hybrid'],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for hybrid mode intent detection.
    |
    */
    'hybrid' => [
        'fallback_mode' => env('GATEWAY_HYBRID_FALLBACK', 'appointment'),
    ],
];
