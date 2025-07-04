<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different endpoints and user types.
    | The adaptive rate limiter will adjust limits based on usage patterns.
    |
    */

    'enabled' => env('RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Limits
    |--------------------------------------------------------------------------
    |
    | Default rate limits applied when no specific rule matches
    |
    */
    'default' => [
        'requests' => env('RATE_LIMIT_DEFAULT_REQUESTS', 60),
        'minutes' => env('RATE_LIMIT_DEFAULT_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Specific Limits
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for specific endpoints or patterns
    | Patterns support wildcards (*) for flexible matching
    |
    */
    'endpoints' => [
        // Webhook endpoints - higher limits for system integrations
        'api/retell/webhook' => ['requests' => 100, 'minutes' => 1],
        'api/calcom/webhook' => ['requests' => 100, 'minutes' => 1],
        'api/stripe/webhook' => ['requests' => 100, 'minutes' => 1],
        'api/webhook' => ['requests' => 100, 'minutes' => 1],
        
        // API endpoints - standard limits
        'api/appointments' => ['requests' => 60, 'minutes' => 1],
        'api/customers' => ['requests' => 60, 'minutes' => 1],
        'api/calls' => ['requests' => 60, 'minutes' => 1],
        'api/branches' => ['requests' => 60, 'minutes' => 1],
        'api/services' => ['requests' => 60, 'minutes' => 1],
        
        // MCP endpoints - moderate limits
        'api/mcp/*' => ['requests' => 100, 'minutes' => 1],
        
        // Retell custom functions - lower limits to prevent abuse
        'api/retell/collect-appointment' => ['requests' => 30, 'minutes' => 1],
        'api/retell/identify-customer' => ['requests' => 30, 'minutes' => 1],
        'api/retell/transfer-*' => ['requests' => 20, 'minutes' => 1],
        
        // Admin panel - higher limits for internal use
        'admin/*' => ['requests' => 300, 'minutes' => 1],
        
        // Default for any unmatched API endpoint
        'api/*' => ['requests' => 60, 'minutes' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Type Multipliers
    |--------------------------------------------------------------------------
    |
    | Adjust rate limits based on user type or subscription level
    |
    */
    'user_multipliers' => [
        'guest' => 1.0,
        'basic' => 1.5,
        'pro' => 2.0,
        'enterprise' => 5.0,
        'admin' => 10.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Limiting
    |--------------------------------------------------------------------------
    |
    | Enable adaptive rate limiting that adjusts based on usage patterns
    |
    */
    'adaptive' => [
        'enabled' => env('ADAPTIVE_RATE_LIMITING', true),
        'decrease_after_violations' => 3,  // Decrease limit after N violations
        'increase_after_period' => 24,     // Increase limit after N hours of good behavior
        'min_multiplier' => 0.5,           // Minimum limit multiplier
        'max_multiplier' => 2.0,           // Maximum limit multiplier
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    |
    | Configure which rate limit headers to include in responses
    |
    */
    'headers' => [
        'include_limit' => true,        // X-RateLimit-Limit
        'include_remaining' => true,    // X-RateLimit-Remaining
        'include_retry_after' => true,  // Retry-After (on 429 responses)
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Configure storage backend for rate limit counters
    |
    */
    'storage' => [
        'driver' => env('RATE_LIMIT_STORAGE', 'cache'), // cache, redis, database
        'prefix' => 'rate_limit:',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure rate limit monitoring and alerting
    |
    */
    'monitoring' => [
        'log_violations' => env('LOG_RATE_LIMIT_VIOLATIONS', true),
        'alert_threshold' => 100, // Alert after N violations per hour
        'metrics_enabled' => env('RATE_LIMIT_METRICS', true),
    ],
];