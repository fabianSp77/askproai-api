<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various performance optimization components
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    */
    'cache_warming' => [
        'enabled' => env('PERFORMANCE_CACHE_WARMING', true),
        'ttl' => [
            'phone_mappings' => 3600,      // 1 hour
            'event_types' => 1800,          // 30 minutes
            'company_settings' => 7200,     // 2 hours
            'branch_services' => 3600,      // 1 hour
            'staff_availability' => 900,    // 15 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    */
    'query_optimization' => [
        'enabled' => env('PERFORMANCE_QUERY_OPTIMIZATION', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100), // milliseconds
        'monitor_queries' => env('MONITOR_QUERIES', false),
        'auto_create_indexes' => env('AUTO_CREATE_INDEXES', false),
        'max_monitored_queries' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Pool
    |--------------------------------------------------------------------------
    */
    'connection_pool' => [
        'enabled' => env('PERFORMANCE_CONNECTION_POOL', true),
        'min_connections' => env('DB_POOL_MIN', 5),
        'max_connections' => env('DB_POOL_MAX', 100),
        'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 300), // seconds
        'health_check_interval' => 60, // seconds
        'connection_timeout' => 5, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 100, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Compression
    |--------------------------------------------------------------------------
    */
    'compression' => [
        'enabled' => env('RESPONSE_COMPRESSION', true),
        'min_size' => 1024, // 1KB
        'level' => 6, // 1-9, higher = better compression but slower
        'types' => [
            'text/html',
            'text/css',
            'text/xml',
            'text/plain',
            'application/json',
            'application/javascript',
            'application/xml',
            'application/rss+xml',
            'application/atom+xml',
            'application/font-woff',
            'image/svg+xml',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Caching
    |--------------------------------------------------------------------------
    */
    'asset_caching' => [
        'static_assets_ttl' => 31536000, // 1 year
        'api_cache_ttl' => 60, // 1 minute
        'html_cache_enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Indexes
    |--------------------------------------------------------------------------
    |
    | Critical indexes that should always exist
    */
    'required_indexes' => [
        'phone_numbers' => [
            'idx_phone_branch_lookup' => ['phone_number', 'branch_id', 'is_active'],
        ],
        'branches' => [
            'idx_company_active_branches' => ['company_id', 'is_active', 'id'],
            'idx_calcom_event_lookup' => ['calcom_event_type_id', 'is_active'],
        ],
        'appointments' => [
            'idx_branch_appointments_time' => ['branch_id', 'start_time', 'status'],
            'idx_customer_appointments' => ['customer_id', 'start_time', 'status'],
            'idx_staff_schedule' => ['staff_id', 'start_time', 'end_time'],
        ],
        'calls' => [
            'idx_company_recent_calls' => ['company_id', 'created_at', 'status'],
            'idx_phone_call_history' => ['from_number', 'created_at'],
            'idx_retell_call_status' => ['retell_call_id', 'status'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'track_memory_usage' => env('TRACK_MEMORY_USAGE', true),
        'track_query_count' => env('TRACK_QUERY_COUNT', true),
        'alert_thresholds' => [
            'memory_percent' => 80,
            'query_count' => 100,
            'response_time' => 1000, // milliseconds
            'connection_usage_percent' => 80,
        ],
    ],
];