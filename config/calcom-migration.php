<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cal.com API Migration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for migrating from Cal.com V1 to V2 API
    |
    */

    // Enable logging of migration status
    'log_status' => env('CALCOM_MIGRATION_LOG_STATUS', true),
    
    // Log all V1 API usage
    'log_v1_usage' => env('CALCOM_LOG_V1_USAGE', true),
    
    // Force V2 API usage (bypass gradual rollout)
    'force_v2' => env('CALCOM_FORCE_V2', false),
    
    // Percentage of requests to route to V2 (0-100)
    'v2_rollout_percentage' => env('CALCOM_V2_ROLLOUT_PERCENTAGE', 100),
    
    // Feature flags for specific endpoints
    'endpoints' => [
        'availability' => [
            'use_v2' => env('CALCOM_V2_AVAILABILITY', true),
        ],
        'bookings' => [
            'use_v2' => env('CALCOM_V2_BOOKINGS', true),
        ],
        'event_types' => [
            'use_v2' => env('CALCOM_V2_EVENT_TYPES', true),
        ],
    ],
    
    // Monitoring and alerting
    'monitoring' => [
        // Alert if V1 usage exceeds this threshold
        'v1_usage_alert_threshold' => env('CALCOM_V1_USAGE_ALERT_THRESHOLD', 100),
        
        // Alert email
        'alert_email' => env('CALCOM_MIGRATION_ALERT_EMAIL', 'tech@askproai.de'),
    ],
    
    // Backwards compatibility options
    'compatibility' => [
        // Convert V2 responses to V1 format
        'convert_responses' => env('CALCOM_CONVERT_RESPONSES', true),
        
        // Add deprecation headers to responses
        'add_deprecation_headers' => env('CALCOM_ADD_DEPRECATION_HEADERS', true),
    ],
];