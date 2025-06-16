<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures the caching strategy for the AskProAI platform.
    | It defines TTL values, cache tags, and warming strategies for different
    | types of data to optimize performance while maintaining data freshness.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configuration (in seconds)
    |--------------------------------------------------------------------------
    */
    'ttl' => [
        // Cal.com event types - refreshed every 5 minutes
        'event_types' => env('CACHE_TTL_EVENT_TYPES', 300),
        
        // Customer lookups - cached for 10 minutes
        'customer_lookup' => env('CACHE_TTL_CUSTOMER_LOOKUP', 600),
        
        // Appointment availability - very short cache due to real-time nature
        'availability' => env('CACHE_TTL_AVAILABILITY', 120),
        
        // Company settings - longer cache as they change infrequently
        'company_settings' => env('CACHE_TTL_COMPANY_SETTINGS', 1800),
        
        // Staff schedules - moderate cache time
        'staff_schedules' => env('CACHE_TTL_STAFF_SCHEDULES', 300),
        
        // Service lists - can be cached longer
        'service_lists' => env('CACHE_TTL_SERVICE_LISTS', 3600),
        
        // API responses - default TTL for middleware
        'api_response_default' => env('CACHE_TTL_API_RESPONSE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags Configuration
    |--------------------------------------------------------------------------
    */
    'tags' => [
        'company_prefix' => 'company',
        'staff_prefix' => 'staff',
        'customer_prefix' => 'customer',
        'appointments_prefix' => 'appointments',
        'services_prefix' => 'services',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Configuration
    |--------------------------------------------------------------------------
    */
    'warming' => [
        // Enable/disable cache warming
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        
        // Run cache warming for these types on schedule
        'scheduled_types' => [
            'event_types',
            'company_settings',
            'staff_schedules',
            'services',
        ],
        
        // Cache warming schedule (in minutes)
        'schedules' => [
            'all' => 30,           // Warm all caches every 30 minutes
            'event_types' => 15,   // Warm event types every 15 minutes
            'critical' => 5,       // Critical data warming interval
        ],
        
        // Maximum companies to warm in a single batch
        'batch_size' => env('CACHE_WARMING_BATCH_SIZE', 50),
        
        // Queue to use for cache warming jobs
        'queue' => env('CACHE_WARMING_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Response Cache Configuration
    |--------------------------------------------------------------------------
    */
    'api_response' => [
        // Enable/disable API response caching
        'enabled' => env('API_RESPONSE_CACHE_ENABLED', true),
        
        // Routes to cache with specific TTLs (in seconds)
        'routes' => [
            'api/services' => 3600,
            'api/services/*' => 3600,
            'api/event-types' => 300,
            'api/event-types/*' => 300,
            'api/availability' => 120,
            'api/availability/*' => 120,
            'api/staff/*/schedule' => 300,
            'api/staff/*/availability' => 120,
            'api/company/settings' => 1800,
            'api/company/*/settings' => 1800,
            'api/branches' => 900,
            'api/branches/*' => 900,
        ],
        
        // Routes to never cache
        'exclude' => [
            'api/auth/*',
            'api/webhooks/*',
            'api/admin/*',
            'api/billing/*',
            'api/user/profile',
        ],
        
        // Cache based on these headers
        'vary_by_headers' => [
            'X-Company-ID',
            'X-Tenant-ID',
            'Accept-Language',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Configuration
    |--------------------------------------------------------------------------
    */
    'invalidation' => [
        // Automatically invalidate related caches
        'cascade' => [
            // When company cache is cleared, also clear these
            'company' => [
                'services',
                'staff_schedules',
                'event_types',
            ],
            
            // When staff cache is cleared, also clear these
            'staff' => [
                'availability',
                'staff_schedules',
            ],
            
            // When appointment cache is cleared, also clear these
            'appointments' => [
                'availability',
            ],
        ],
        
        // Events that trigger cache invalidation
        'events' => [
            'App\Events\CompanyUpdated' => ['company'],
            'App\Events\StaffUpdated' => ['staff'],
            'App\Events\ServiceUpdated' => ['services'],
            'App\Events\AppointmentCreated' => ['availability', 'appointments'],
            'App\Events\AppointmentUpdated' => ['availability', 'appointments'],
            'App\Events\AppointmentCancelled' => ['availability', 'appointments'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Driver Configuration
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        // Preferred cache driver for different types
        'default' => env('CACHE_DRIVER', 'redis'),
        
        // Use specific drivers for different cache types
        'by_type' => [
            'api_response' => env('API_CACHE_DRIVER', 'redis'),
            'data' => env('DATA_CACHE_DRIVER', 'redis'),
            'warming' => env('WARMING_CACHE_DRIVER', 'redis'),
        ],
        
        // Redis configuration for caching
        'redis' => [
            'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('CACHE_REDIS_LOCK_CONNECTION', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring & Alerts
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        // Enable cache hit/miss logging
        'log_stats' => env('CACHE_LOG_STATS', false),
        
        // Alert thresholds
        'alerts' => [
            // Alert if cache hit rate drops below this percentage
            'min_hit_rate' => env('CACHE_MIN_HIT_RATE', 70),
            
            // Alert if cache size exceeds this (in MB)
            'max_size_mb' => env('CACHE_MAX_SIZE_MB', 1024),
        ],
        
        // Metrics collection
        'metrics' => [
            'enabled' => env('CACHE_METRICS_ENABLED', true),
            'sample_rate' => env('CACHE_METRICS_SAMPLE_RATE', 0.1), // 10% sampling
        ],
    ],
];