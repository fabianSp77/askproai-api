<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Channels Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which channels should be used for sending alerts.
    |
    */
    'channels' => [
        'email' => [
            'enabled' => env('ALERTS_EMAIL_ENABLED', true),
            'recipients' => env('ALERTS_EMAIL_RECIPIENTS') 
                ? explode(',', env('ALERTS_EMAIL_RECIPIENTS'))
                : [],
            'from_address' => env('ALERTS_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('ALERTS_EMAIL_FROM_NAME', 'AskProAI Alerts'),
        ],
        
        'database' => [
            'enabled' => true, // Always enabled for tracking
        ],
        
        'slack' => [
            'enabled' => env('ALERTS_SLACK_ENABLED', false),
            'webhook_url' => env('ALERTS_SLACK_WEBHOOK'),
            'channel' => env('ALERTS_SLACK_CHANNEL', '#alerts'),
            'username' => env('ALERTS_SLACK_USERNAME', 'AskProAI Bot'),
        ],
        
        'webhook' => [
            'enabled' => env('ALERTS_WEBHOOK_ENABLED', false),
            'url' => env('ALERTS_WEBHOOK_URL'),
            'secret' => env('ALERTS_WEBHOOK_SECRET'),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Alert Rules and Thresholds
    |--------------------------------------------------------------------------
    |
    | Define thresholds and rules for generating alerts.
    |
    */
    'rules' => [
        // API health thresholds
        'api_success_rate_threshold' => env('ALERTS_API_SUCCESS_THRESHOLD', 90), // percentage
        'api_response_time_threshold' => env('ALERTS_API_RESPONSE_THRESHOLD', 2000), // milliseconds
        
        // Error rate thresholds
        'error_rate_threshold' => env('ALERTS_ERROR_THRESHOLD', 10), // errors per 15 minutes
        'critical_error_threshold' => env('ALERTS_CRITICAL_ERROR_THRESHOLD', 5), // critical errors per hour
        
        // System resource thresholds
        'disk_space_warning_threshold' => env('ALERTS_DISK_WARNING', 80), // percentage
        'disk_space_critical_threshold' => env('ALERTS_DISK_CRITICAL', 90), // percentage
        'database_size_threshold' => env('ALERTS_DB_SIZE_THRESHOLD', 5000), // MB
        
        // Circuit breaker thresholds
        'circuit_breaker_failure_threshold' => env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),
        'circuit_breaker_timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60), // seconds
        
        // Alert throttling
        'throttle_minutes' => env('ALERTS_THROTTLE_MINUTES', 15), // prevent spam
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Alert Severity Levels
    |--------------------------------------------------------------------------
    |
    | Define which error types map to which severity levels.
    |
    */
    'severity_mapping' => [
        'critical' => [
            'circuit_breaker_open',
            'api_down',
            'database_connection_failed',
            'disk_space_critical',
            'health_check_failed',
        ],
        
        'high' => [
            'api_degraded',
            'high_error_rate',
            'slow_response',
            'disk_space_low',
            'database_size_large',
        ],
        
        'medium' => [
            'webhook_failed',
            'cache_miss_high',
            'queue_backed_up',
        ],
        
        'low' => [
            'test_alert',
            'maintenance_reminder',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Alert Escalation Rules
    |--------------------------------------------------------------------------
    |
    | Define escalation rules for unacknowledged alerts.
    |
    */
    'escalation' => [
        'enabled' => env('ALERTS_ESCALATION_ENABLED', false),
        'intervals' => [
            30,  // First escalation after 30 minutes
            60,  // Second escalation after 1 hour
            120, // Third escalation after 2 hours
        ],
        'max_escalations' => 3,
    ],
];