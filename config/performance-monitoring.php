<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the performance monitoring and metrics collection
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),
    
    'metrics_token' => env('MONITORING_METRICS_TOKEN', 'your-secure-metrics-token'),
    
    'prometheus' => [
        'enabled' => env('PROMETHEUS_ENABLED', true),
        'namespace' => env('PROMETHEUS_NAMESPACE', 'askproai'),
    ],
    
    'query_monitoring' => [
        'enabled' => env('QUERY_MONITORING_ENABLED', false),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 50), // milliseconds
        'log_duplicate_queries' => env('LOG_DUPLICATE_QUERIES', true),
    ],
    
    'performance_baseline' => [
        'save_results' => env('PERFORMANCE_BASELINE_SAVE', true),
        'storage_path' => storage_path('performance-baselines'),
    ],
    
    'cache_warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        'schedule' => env('CACHE_WARMING_SCHEDULE', '0 * * * *'), // hourly
    ],
    
    'alerts' => [
        'slow_response_threshold' => env('ALERT_SLOW_RESPONSE', 1000), // milliseconds
        'high_error_rate_threshold' => env('ALERT_ERROR_RATE', 5), // percentage
        'queue_size_threshold' => env('ALERT_QUEUE_SIZE', 1000),
        'failed_jobs_threshold' => env('ALERT_FAILED_JOBS', 100),
    ],
];