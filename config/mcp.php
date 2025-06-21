<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Configuration
    |--------------------------------------------------------------------------
    |
    | Model Context Protocol settings for the AskProAI platform
    |
    */

    'enabled' => env('MCP_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    */
    'services' => [
        'webhook' => [
            'enabled' => true,
            'timeout' => 30,
            'max_retries' => 3,
            'rate_limit' => [
                'per_minute' => 1000,
                'per_hour' => 50000,
            ],
        ],
        
        'calcom' => [
            'enabled' => true,
            'timeout' => 15,
            'max_retries' => 3,
            'rate_limit' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
            'cache_ttl' => 300, // 5 minutes
        ],
        
        'retell' => [
            'enabled' => true,
            'timeout' => 20,
            'max_retries' => 3,
            'rate_limit' => [
                'per_minute' => 100,
                'per_hour' => 5000,
            ],
            'cache_ttl' => 300,
        ],
        
        'database' => [
            'enabled' => true,
            'timeout' => 10,
            'max_rows' => 1000,
            'read_only' => true,
            'cache_ttl' => 60,
        ],
        
        'queue' => [
            'enabled' => true,
            'timeout' => 5,
            'cache_ttl' => 30,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Orchestrator Configuration
    |--------------------------------------------------------------------------
    */
    'orchestrator' => [
        'health_check_interval' => 60, // seconds
        'metrics_retention' => 86400, // 24 hours
        'tenant_quotas' => [
            'requests_per_minute' => env('MCP_TENANT_RPM', 1000),
            'concurrent_operations' => env('MCP_TENANT_CONCURRENT', 50),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Connection Pool Configuration
    |--------------------------------------------------------------------------
    */
    'connection_pool' => [
        'enabled' => env('DB_POOL_ENABLED', true),
        'min_connections' => env('DB_POOL_MIN', 10),
        'max_connections' => env('DB_POOL_MAX', 200),
        'max_idle_time' => env('DB_POOL_IDLE_TIME', 300), // 5 minutes
        'health_check_interval' => env('DB_POOL_HEALTH_CHECK', 60), // 1 minute
        'acquire_timeout' => env('DB_POOL_TIMEOUT', 5), // 5 seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    */
    'circuit_breakers' => [
        'calcom' => [
            'failure_threshold' => 5,
            'success_threshold' => 2,
            'timeout' => 60,
            'half_open_requests' => 3,
        ],
        'retell' => [
            'failure_threshold' => 5,
            'success_threshold' => 2,
            'timeout' => 60,
            'half_open_requests' => 3,
        ],
        'stripe' => [
            'failure_threshold' => 3,
            'success_threshold' => 2,
            'timeout' => 120,
            'half_open_requests' => 2,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'target_latency_ms' => 100, // Target < 100ms for phone lookups
        'max_latency_ms' => 1000, // Max 1 second
        'concurrent_calls' => env('MCP_CONCURRENT_CALLS', 200),
        'queue_workers' => env('MCP_QUEUE_WORKERS', 50),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'metrics_enabled' => env('MCP_METRICS_ENABLED', true),
        'alert_thresholds' => [
            'error_rate' => 5, // Alert if error rate > 5%
            'latency_p99' => 1000, // Alert if P99 latency > 1s
            'queue_size' => 1000, // Alert if queue > 1000 jobs
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redis Configuration for High Throughput
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'connection_pool_size' => env('REDIS_POOL_SIZE', 50),
        'persistent_connections' => env('REDIS_PERSISTENT', true),
        'read_timeout' => env('REDIS_READ_TIMEOUT', 2),
        'timeout' => env('REDIS_TIMEOUT', 5),
    ],
];