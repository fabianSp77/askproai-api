<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cal.com V2 API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Cal.com V2 API migration
    |
    */

    'enabled' => env('CALCOM_V2_ENABLED', true),
    
    'api_version' => '2024-08-13',
    
    'base_url' => env('CALCOM_V2_BASE_URL', 'https://api.cal.com/v2'),
    
    /*
    |--------------------------------------------------------------------------
    | Method-specific Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control which methods use V2 API during migration
    |
    */
    'enabled_methods' => [
        'getEventTypes' => env('CALCOM_V2_GET_EVENT_TYPES', true),
        'checkAvailability' => env('CALCOM_V2_CHECK_AVAILABILITY', true),
        'bookAppointment' => env('CALCOM_V2_BOOK_APPOINTMENT', false),
        'cancelBooking' => env('CALCOM_V2_CANCEL_BOOKING', false),
        'getBooking' => env('CALCOM_V2_GET_BOOKING', true),
        'rescheduleBooking' => env('CALCOM_V2_RESCHEDULE_BOOKING', false),
        'getSchedules' => env('CALCOM_V2_GET_SCHEDULES', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => [
        'request' => env('CALCOM_V2_TIMEOUT', 30),
        'connect' => env('CALCOM_V2_CONNECT_TIMEOUT', 10),
    ],
    
    'retry' => [
        'times' => env('CALCOM_V2_RETRY_TIMES', 3),
        'sleep' => env('CALCOM_V2_RETRY_SLEEP', 1000), // milliseconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => [
        'event_types' => env('CALCOM_V2_CACHE_EVENT_TYPES', 300), // 5 minutes
        'schedules' => env('CALCOM_V2_CACHE_SCHEDULES', 300), // 5 minutes
        'slots' => env('CALCOM_V2_CACHE_SLOTS', 60), // 1 minute
        'bookings' => 0, // Never cache bookings
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('CALCOM_V2_LOGGING_ENABLED', true),
        'channel' => env('CALCOM_V2_LOG_CHANNEL', 'calcom'),
        'log_requests' => env('CALCOM_V2_LOG_REQUESTS', true),
        'log_responses' => env('CALCOM_V2_LOG_RESPONSES', false),
        'mask_sensitive_data' => env('CALCOM_V2_MASK_SENSITIVE', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'enabled' => env('CALCOM_V2_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('CALCOM_V2_CIRCUIT_BREAKER_THRESHOLD', 5),
        'recovery_time' => env('CALCOM_V2_CIRCUIT_BREAKER_RECOVERY', 60), // seconds
        'timeout' => env('CALCOM_V2_CIRCUIT_BREAKER_TIMEOUT', 30), // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => env('CALCOM_V2_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('CALCOM_V2_RATE_LIMIT_MAX', 60),
        'decay_minutes' => env('CALCOM_V2_RATE_LIMIT_DECAY', 1),
    ],
];