<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Performance Monitoring (APM)
    |--------------------------------------------------------------------------
    |
    | Configuration for application performance monitoring, including
    | query tracking, API response times, and resource usage.
    |
    */
    'apm' => [
        'enabled' => env('APM_ENABLED', true),
        'trace_sql' => env('APM_TRACE_SQL', true),
        'trace_api' => env('APM_TRACE_API', true),
        'slow_threshold_ms' => env('APM_SLOW_THRESHOLD_MS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for security monitoring, including threat detection,
    | suspicious activity tracking, and automated alerts.
    |
    */
    'security' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'track_failed_logins' => env('SECURITY_TRACK_FAILED_LOGINS', true),
        'track_suspicious_requests' => env('SECURITY_TRACK_SUSPICIOUS_REQUESTS', true),
        'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 5),
        'block_suspicious_ips' => env('SECURITY_BLOCK_SUSPICIOUS_IPS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable or disable query performance monitoring. When enabled, the system
    | will track all database queries and provide performance statistics.
    |
    */
    'query_performance' => env('MONITORING_QUERY_PERFORMANCE', false),

    /*
    |--------------------------------------------------------------------------
    | Slow Query Threshold
    |--------------------------------------------------------------------------
    |
    | Queries that take longer than this value (in milliseconds) will be
    | logged as slow queries and shown in the performance dashboard.
    |
    */
    'slow_query_threshold' => env('MONITORING_SLOW_QUERY_MS', 100),

    /*
    |--------------------------------------------------------------------------
    | Enable Performance Widget
    |--------------------------------------------------------------------------
    |
    | Show the performance widget in the bottom right corner of the page
    | when in debug mode. This shows query count, time, and warnings.
    |
    */
    'show_performance_widget' => env('MONITORING_SHOW_WIDGET', true),

    /*
    |--------------------------------------------------------------------------
    | N+1 Detection Threshold
    |--------------------------------------------------------------------------
    |
    | Similar queries executed more than this number of times will be
    | flagged as potential N+1 query problems.
    |
    */
    'n_plus_one_threshold' => env('MONITORING_N_PLUS_ONE_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Query Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep slow query logs in the cache (in hours).
    |
    */
    'query_log_retention_hours' => env('MONITORING_QUERY_LOG_RETENTION', 24),

    /*
    |--------------------------------------------------------------------------
    | Real-time Metrics
    |--------------------------------------------------------------------------
    |
    | Enable real-time metrics collection for dashboards and monitoring.
    |
    */
    'realtime_metrics' => [
        'enabled' => env('MONITORING_REALTIME_ENABLED', false),
        'interval_seconds' => env('MONITORING_REALTIME_INTERVAL', 60),
        'retention_days' => env('MONITORING_REALTIME_RETENTION', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure API rate limiting per user and per IP address.
    |
    */
    'rate_limiting' => [
        'enabled' => env('MONITORING_RATE_LIMIT_ENABLED', true),
        'per_minute' => env('MONITORING_RATE_LIMIT_PER_MINUTE', 60),
        'per_hour' => env('MONITORING_RATE_LIMIT_PER_HOUR', 1000),
        'burst' => env('MONITORING_RATE_LIMIT_BURST', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Endpoints
    |--------------------------------------------------------------------------
    |
    | Configure health check endpoints for monitoring.
    |
    */
    'health_checks' => [
        'database' => true,
        'redis' => true,
        'horizon' => true,
        'disk_space' => true,
        'memory' => true,
        'services' => [
            'retell' => env('MONITORING_CHECK_RETELL', true),
            'calcom' => env('MONITORING_CHECK_CALCOM', true),
            'stripe' => env('MONITORING_CHECK_STRIPE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure when to trigger alerts for various metrics.
    |
    */
    'alerts' => [
        'memory_percent' => env('MONITORING_ALERT_MEMORY', 80),
        'disk_percent' => env('MONITORING_ALERT_DISK', 90),
        'error_rate_percent' => env('MONITORING_ALERT_ERROR_RATE', 5),
        'response_time_ms' => env('MONITORING_ALERT_RESPONSE_TIME', 1000),
    ],
];
