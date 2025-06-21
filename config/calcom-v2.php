<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cal.com V2 API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Cal.com V2 API integration. This config file
    | supports environment-specific settings for development, staging, and
    | production environments.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'api_key' => env('CALCOM_V2_API_KEY', env('DEFAULT_CALCOM_API_KEY')),
    'api_url' => env('CALCOM_V2_API_URL', 'https://api.cal.com/v2'),
    
    /*
    |--------------------------------------------------------------------------
    | Organization Settings
    |--------------------------------------------------------------------------
    */
    'organization_id' => env('CALCOM_V2_ORGANIZATION_ID'),
    'team_slug' => env('CALCOM_V2_TEAM_SLUG', env('DEFAULT_CALCOM_TEAM_SLUG')),
    
    /*
    |--------------------------------------------------------------------------
    | Default Event Type
    |--------------------------------------------------------------------------
    */
    'default_event_type_id' => env('CALCOM_V2_DEFAULT_EVENT_TYPE_ID'),
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'secret' => env('CALCOM_V2_WEBHOOK_SECRET', env('CALCOM_WEBHOOK_SECRET')),
        'events' => [
            'BOOKING_CREATED',
            'BOOKING_CONFIRMED', 
            'BOOKING_CANCELLED',
            'BOOKING_RESCHEDULED',
            'BOOKING_REQUESTED',
            'BOOKING_REJECTED',
            'BOOKING_COMPLETED',
            'BOOKING_NO_SHOW',
        ],
        'endpoint' => env('CALCOM_V2_WEBHOOK_ENDPOINT', '/api/webhooks/calcom'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => env('CALCOM_V2_RATE_LIMIT_ENABLED', true),
        'max_requests_per_minute' => env('CALCOM_V2_RATE_LIMIT_MAX', 60),
        'retry_after_seconds' => env('CALCOM_V2_RATE_LIMIT_RETRY', 60),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'enabled' => env('CALCOM_V2_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('CALCOM_V2_CIRCUIT_BREAKER_THRESHOLD', 5),
        'timeout_seconds' => env('CALCOM_V2_CIRCUIT_BREAKER_TIMEOUT', 60),
        'success_threshold' => env('CALCOM_V2_CIRCUIT_BREAKER_SUCCESS', 2),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('CALCOM_V2_CACHE_ENABLED', true),
        'prefix' => env('CALCOM_V2_CACHE_PREFIX', 'calcom_v2'),
        'ttl' => [
            'event_types' => env('CALCOM_V2_CACHE_TTL_EVENT_TYPES', 3600), // 1 hour
            'availability' => env('CALCOM_V2_CACHE_TTL_AVAILABILITY', 300), // 5 minutes
            'bookings' => env('CALCOM_V2_CACHE_TTL_BOOKINGS', 600), // 10 minutes
            'schedules' => env('CALCOM_V2_CACHE_TTL_SCHEDULES', 1800), // 30 minutes
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts' => env('CALCOM_V2_RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('CALCOM_V2_RETRY_INITIAL_DELAY', 100),
        'max_delay_ms' => env('CALCOM_V2_RETRY_MAX_DELAY', 2000),
        'multiplier' => env('CALCOM_V2_RETRY_MULTIPLIER', 2),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration
    |--------------------------------------------------------------------------
    */
    'timeout' => [
        'connect' => env('CALCOM_V2_TIMEOUT_CONNECT', 5),
        'request' => env('CALCOM_V2_TIMEOUT_REQUEST', 30),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('CALCOM_V2_LOGGING_ENABLED', true),
        'channel' => env('CALCOM_V2_LOGGING_CHANNEL', 'calcom'),
        'level' => env('CALCOM_V2_LOGGING_LEVEL', 'info'),
        'log_requests' => env('CALCOM_V2_LOG_REQUESTS', false),
        'log_responses' => env('CALCOM_V2_LOG_RESPONSES', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Settings
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'production' => [
            'verify_ssl' => true,
            'debug' => false,
            'mock_enabled' => false,
        ],
        'staging' => [
            'verify_ssl' => true,
            'debug' => true,
            'mock_enabled' => false,
        ],
        'local' => [
            'verify_ssl' => false,
            'debug' => true,
            'mock_enabled' => env('CALCOM_V2_MOCK_ENABLED', false),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'auto_confirm_bookings' => env('CALCOM_V2_AUTO_CONFIRM', true),
        'send_reminders' => env('CALCOM_V2_SEND_REMINDERS', true),
        'allow_rescheduling' => env('CALCOM_V2_ALLOW_RESCHEDULING', true),
        'allow_cancellation' => env('CALCOM_V2_ALLOW_CANCELLATION', true),
        'sync_attendees' => env('CALCOM_V2_SYNC_ATTENDEES', true),
        'sync_custom_fields' => env('CALCOM_V2_SYNC_CUSTOM_FIELDS', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Booking Defaults
    |--------------------------------------------------------------------------
    */
    'booking_defaults' => [
        'timezone' => env('CALCOM_V2_DEFAULT_TIMEZONE', 'Europe/Berlin'),
        'language' => env('CALCOM_V2_DEFAULT_LANGUAGE', 'de'),
        'buffer_time_minutes' => env('CALCOM_V2_BUFFER_TIME', 0),
        'metadata_prefix' => env('CALCOM_V2_METADATA_PREFIX', 'askproai_'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'enabled' => env('CALCOM_V2_HEALTH_CHECK_ENABLED', true),
        'interval_seconds' => env('CALCOM_V2_HEALTH_CHECK_INTERVAL', 300),
        'timeout_seconds' => env('CALCOM_V2_HEALTH_CHECK_TIMEOUT', 5),
    ],
];