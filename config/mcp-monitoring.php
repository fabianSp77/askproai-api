<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring MCP services including alerts, thresholds,
    | and notification settings.
    |
    */

    'enabled' => env('MCP_MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Alert Rules
    |--------------------------------------------------------------------------
    |
    | Define alert rules that trigger notifications when certain conditions
    | are met. Each rule can specify service, condition, threshold, and
    | notification settings.
    |
    */
    'alerts' => [
        [
            'name' => 'high_error_rate',
            'service' => null, // null means all services
            'condition' => [
                'type' => 'error_rate',
                'threshold' => 10, // percentage
                'window' => 300, // seconds (5 minutes)
            ],
            'severity' => 'critical',
            'message' => 'High error rate detected',
            'cooldown' => 600, // seconds before re-alerting
            'notify' => ['email', 'slack'],
        ],
        [
            'name' => 'slow_response',
            'service' => null,
            'condition' => [
                'type' => 'response_time',
                'threshold' => 2000, // milliseconds
            ],
            'severity' => 'warning',
            'message' => 'Slow response time detected',
            'cooldown' => 300,
            'notify' => ['slack'],
        ],
        [
            'name' => 'consecutive_errors',
            'service' => null,
            'condition' => [
                'type' => 'consecutive_errors',
                'count' => 5,
            ],
            'severity' => 'critical',
            'message' => 'Multiple consecutive errors detected',
            'cooldown' => 600,
            'notify' => ['email', 'slack'],
        ],
        [
            'name' => 'service_down',
            'service' => 'database',
            'condition' => [
                'type' => 'service_down',
                'minutes' => 2,
            ],
            'severity' => 'critical',
            'message' => 'Database service appears to be down',
            'cooldown' => 300,
            'notify' => ['email', 'slack', 'sms'],
        ],
        [
            'name' => 'cache_miss_rate',
            'service' => 'cache',
            'condition' => [
                'type' => 'cache_miss_rate',
                'threshold' => 50, // percentage
                'window' => 600, // 10 minutes
            ],
            'severity' => 'warning',
            'message' => 'High cache miss rate detected',
            'cooldown' => 900,
            'notify' => ['slack'],
        ],
        [
            'name' => 'queue_backlog',
            'service' => 'queue',
            'condition' => [
                'type' => 'queue_size',
                'threshold' => 1000,
            ],
            'severity' => 'warning',
            'message' => 'Large queue backlog detected',
            'cooldown' => 600,
            'notify' => ['slack'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Define performance thresholds for different services. These are used
    | to determine service health status.
    |
    */
    'thresholds' => [
        'response_time' => [
            'excellent' => 100, // ms
            'good' => 500,
            'acceptable' => 1000,
            'poor' => 2000,
        ],
        'error_rate' => [
            'excellent' => 0.1, // percentage
            'good' => 1,
            'acceptable' => 5,
            'poor' => 10,
        ],
        'uptime' => [
            'excellent' => 99.9, // percentage
            'good' => 99,
            'acceptable' => 95,
            'poor' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metric Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long metrics are retained in the database.
    |
    */
    'retention' => [
        'detailed' => 7, // days to keep detailed metrics
        'aggregated' => 30, // days to keep aggregated metrics
        'alerts' => 90, // days to keep alert history
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure notification channels for alerts.
    |
    */
    'notifications' => [
        'email' => [
            'enabled' => env('MCP_NOTIFY_EMAIL', true),
            'to' => env('MCP_NOTIFY_EMAIL_TO', 'admin@example.com'),
            'from' => env('MCP_NOTIFY_EMAIL_FROM', 'monitoring@example.com'),
        ],
        'slack' => [
            'enabled' => env('MCP_NOTIFY_SLACK', false),
            'webhook' => env('MCP_NOTIFY_SLACK_WEBHOOK'),
            'channel' => env('MCP_NOTIFY_SLACK_CHANNEL', '#alerts'),
        ],
        'sms' => [
            'enabled' => env('MCP_NOTIFY_SMS', false),
            'provider' => env('MCP_NOTIFY_SMS_PROVIDER', 'twilio'),
            'to' => env('MCP_NOTIFY_SMS_TO'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Settings
    |--------------------------------------------------------------------------
    |
    | Configure circuit breaker behavior for services.
    |
    */
    'circuit_breaker' => [
        'failure_threshold' => 5, // failures before opening
        'success_threshold' => 2, // successes before closing
        'timeout' => 60, // seconds in open state
        'half_open_max_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the monitoring dashboard.
    |
    */
    'dashboard' => [
        'refresh_interval' => 10, // seconds
        'default_time_range' => '1h',
        'max_chart_points' => 100,
        'enable_auto_refresh' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prometheus Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure Prometheus metric export.
    |
    */
    'prometheus' => [
        'enabled' => env('MCP_PROMETHEUS_ENABLED', true),
        'namespace' => 'mcp',
        'labels' => [
            'app' => env('APP_NAME', 'askproai'),
            'env' => env('APP_ENV', 'production'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configure monitoring settings for specific services.
    |
    */
    'services' => [
        'database' => [
            'health_check_query' => 'SELECT 1',
            'slow_query_threshold' => 1000, // ms
            'connection_pool_monitor' => true,
        ],
        'cache' => [
            'monitor_hit_rate' => true,
            'monitor_memory_usage' => true,
            'low_hit_rate_threshold' => 70, // percentage
        ],
        'queue' => [
            'monitor_job_processing' => true,
            'monitor_failed_jobs' => true,
            'max_queue_size' => 10000,
        ],
        'sentry' => [
            'monitor_error_rate' => true,
            'monitor_quota_usage' => true,
        ],
        'ui_ux' => [
            'monitor_page_load' => true,
            'monitor_js_errors' => true,
            'slow_page_threshold' => 3000, // ms
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode for verbose logging.
    |
    */
    'debug' => env('MCP_MONITORING_DEBUG', false),
];