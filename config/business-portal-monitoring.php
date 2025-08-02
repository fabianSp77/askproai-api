<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Business Portal Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for Business Portal-specific performance monitoring,
    | SLA targets, alert thresholds, and dashboard settings.
    |
    */

    'enabled' => env('BUSINESS_PORTAL_MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | SLA Targets (Response Time in Milliseconds)
    |--------------------------------------------------------------------------
    |
    | Define response time SLA targets for each Business Portal endpoint.
    | These targets are used for compliance monitoring and alerting.
    |
    */
    'sla_targets' => [
        // Critical API Endpoints (must be fastest)
        '/business/api/calls' => env('SLA_API_CALLS', 200),
        '/business/api/dashboard' => env('SLA_API_DASHBOARD', 200),
        '/business/api/appointments' => env('SLA_API_APPOINTMENTS', 150),
        
        // High Priority Endpoints
        '/business/dashboard' => env('SLA_DASHBOARD', 300),
        '/business/calls' => env('SLA_CALLS_LIST', 400),
        '/business/calls/*' => env('SLA_CALL_DETAIL', 200),
        
        // Medium Priority Endpoints
        '/business/settings' => env('SLA_SETTINGS', 250),
        '/business/team' => env('SLA_TEAM', 300),
        '/business/api/customers' => env('SLA_API_CUSTOMERS', 180),
        '/business/customers' => env('SLA_CUSTOMERS', 350),
        
        // Low Priority Endpoints
        '/business/feedback' => env('SLA_FEEDBACK', 500),
        '/business/help' => env('SLA_HELP', 500),
        '/business/reports' => env('SLA_REPORTS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure when to trigger alerts based on SLA compliance.
    | Values are multipliers of the SLA target.
    |
    */
    'alert_thresholds' => [
        'warning' => env('ALERT_WARNING_MULTIPLIER', 1.2),    // 20% above SLA
        'critical' => env('ALERT_CRITICAL_MULTIPLIER', 1.5),  // 50% above SLA
        'emergency' => env('ALERT_EMERGENCY_MULTIPLIER', 2.0), // 100% above SLA
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Categories
    |--------------------------------------------------------------------------
    |
    | Categorize endpoints by business importance for prioritized monitoring
    | and alerting. Higher priority categories get faster alert notifications.
    |
    */
    'endpoint_categories' => [
        'critical' => [
            '/business/api/calls',
            '/business/api/dashboard', 
            '/business/api/appointments'
        ],
        'high' => [
            '/business/calls',
            '/business/calls/*',
            '/business/dashboard'
        ],
        'medium' => [
            '/business/settings',
            '/business/team',
            '/business/api/customers',
            '/business/customers'
        ],
        'low' => [
            '/business/feedback',
            '/business/help',
            '/business/reports'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Targets
    |--------------------------------------------------------------------------
    |
    | Overall performance targets for health scoring and compliance.
    |
    */
    'performance_targets' => [
        'overall_sla_compliance' => env('TARGET_SLA_COMPLIANCE', 99.0), // 99% SLA compliance
        'max_error_rate' => env('TARGET_MAX_ERROR_RATE', 1.0),          // < 1% error rate
        'uptime_target' => env('TARGET_UPTIME', 99.9),                  // 99.9% uptime
        'avg_response_time' => env('TARGET_AVG_RESPONSE_TIME', 200),    // < 200ms average
        'p95_response_time' => env('TARGET_P95_RESPONSE_TIME', 400),    // < 400ms P95
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the performance monitoring dashboard.
    |
    */
    'dashboard' => [
        'refresh_interval' => env('DASHBOARD_REFRESH_INTERVAL', 30),     // seconds
        'default_timeframe' => env('DASHBOARD_DEFAULT_TIMEFRAME', 'last_hour'),
        'max_endpoints_display' => env('DASHBOARD_MAX_ENDPOINTS', 10),
        'max_alerts_display' => env('DASHBOARD_MAX_ALERTS', 5),
        'auto_refresh' => env('DASHBOARD_AUTO_REFRESH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep performance data for different types of metrics.
    |
    */
    'retention' => [
        'realtime_metrics' => env('RETENTION_REALTIME_HOURS', 24),      // 24 hours
        'hourly_aggregates' => env('RETENTION_HOURLY_DAYS', 30),        // 30 days
        'daily_aggregates' => env('RETENTION_DAILY_DAYS', 90),          // 90 days
        'alert_logs' => env('RETENTION_ALERTS_DAYS', 30),               // 30 days
        'performance_reports' => env('RETENTION_REPORTS_DAYS', 365),    // 1 year
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Notifications
    |--------------------------------------------------------------------------
    |
    | Configure how and when to send performance alerts.
    |
    */
    'alerts' => [
        'enabled' => env('PERFORMANCE_ALERTS_ENABLED', true),
        'channels' => [
            'log' => env('ALERT_LOG_ENABLED', true),
            'email' => env('ALERT_EMAIL_ENABLED', false),
            'slack' => env('ALERT_SLACK_ENABLED', false),
            'webhook' => env('ALERT_WEBHOOK_ENABLED', false),
        ],
        
        // Email settings
        'email' => [
            'to' => env('PERFORMANCE_ALERT_EMAIL', 'admin@askproai.de'),
            'from' => env('PERFORMANCE_ALERT_FROM', 'noreply@askproai.de'),
            'subject_prefix' => env('ALERT_EMAIL_PREFIX', '[Performance Alert]'),
        ],
        
        // Slack settings
        'slack' => [
            'webhook_url' => env('SLACK_PERFORMANCE_WEBHOOK'),
            'channel' => env('SLACK_PERFORMANCE_CHANNEL', '#alerts'),
            'username' => env('SLACK_PERFORMANCE_USERNAME', 'Performance Monitor'),
        ],
        
        // Custom webhook settings
        'webhook' => [
            'url' => env('PERFORMANCE_ALERT_WEBHOOK'),
            'method' => env('PERFORMANCE_ALERT_WEBHOOK_METHOD', 'POST'),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Alert-Source' => 'AskProAI-Performance-Monitor',
            ],
        ],
        
        // Alert throttling (prevent spam)
        'throttling' => [
            'same_alert_minutes' => env('ALERT_THROTTLE_SAME_MINUTES', 15),  // Don't repeat same alert for 15 min
            'max_alerts_per_hour' => env('ALERT_THROTTLE_MAX_PER_HOUR', 10), // Max 10 alerts per hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Scoring Weights
    |--------------------------------------------------------------------------
    |
    | How much each factor contributes to the overall health score.
    | All weights should add up to 100.
    |
    */
    'health_score_weights' => [
        'sla_compliance' => env('HEALTH_WEIGHT_SLA', 40),          // 40%
        'error_rate' => env('HEALTH_WEIGHT_ERRORS', 25),          // 25%
        'resource_utilization' => env('HEALTH_WEIGHT_RESOURCES', 20), // 20%
        'alert_status' => env('HEALTH_WEIGHT_ALERTS', 15),        // 15%
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Monitoring Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for system resource monitoring and alerts.
    |
    */
    'resources' => [
        'memory' => [
            'warning_percent' => env('RESOURCE_MEMORY_WARNING', 75),
            'critical_percent' => env('RESOURCE_MEMORY_CRITICAL', 85),
            'emergency_percent' => env('RESOURCE_MEMORY_EMERGENCY', 95),
        ],
        'cpu' => [
            'warning_percent' => env('RESOURCE_CPU_WARNING', 70),
            'critical_percent' => env('RESOURCE_CPU_CRITICAL', 85),
            'emergency_percent' => env('RESOURCE_CPU_EMERGENCY', 95),
        ],
        'database' => [
            'max_connections' => env('RESOURCE_DB_MAX_CONNECTIONS', 100),
            'warning_percent' => env('RESOURCE_DB_WARNING', 75),
            'critical_percent' => env('RESOURCE_DB_CRITICAL', 90),
        ],
        'redis' => [
            'max_memory_mb' => env('RESOURCE_REDIS_MAX_MB', 2048),
            'warning_percent' => env('RESOURCE_REDIS_WARNING', 80),
            'critical_percent' => env('RESOURCE_REDIS_CRITICAL', 90),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Monitoring Features
    |--------------------------------------------------------------------------
    |
    | Enable/disable advanced monitoring features.
    |
    */
    'features' => [
        'query_analysis' => env('MONITORING_QUERY_ANALYSIS', true),
        'n_plus_one_detection' => env('MONITORING_N_PLUS_ONE', true),
        'memory_leak_detection' => env('MONITORING_MEMORY_LEAKS', true),
        'user_experience_tracking' => env('MONITORING_USER_EXPERIENCE', true),
        'performance_regression_detection' => env('MONITORING_REGRESSION', true),
        'predictive_alerts' => env('MONITORING_PREDICTIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export & Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for exporting metrics to external monitoring systems.
    |
    */
    'export' => [
        'prometheus' => [
            'enabled' => env('EXPORT_PROMETHEUS_ENABLED', false),
            'endpoint' => env('EXPORT_PROMETHEUS_ENDPOINT', '/metrics'),
            'labels' => [
                'service' => 'askproai-business-portal',
                'environment' => env('APP_ENV', 'production'),
            ],
        ],
        'statsd' => [
            'enabled' => env('EXPORT_STATSD_ENABLED', false),
            'host' => env('STATSD_HOST', 'localhost'),
            'port' => env('STATSD_PORT', 8125),
            'prefix' => env('STATSD_PREFIX', 'askproai.portal'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        'mock_data' => env('MONITORING_MOCK_DATA', false),
        'debug_headers' => env('MONITORING_DEBUG_HEADERS', true),
        'verbose_logging' => env('MONITORING_VERBOSE_LOGS', false),
        'simulate_alerts' => env('MONITORING_SIMULATE_ALERTS', false),
    ],
];