<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Alert Thresholds Configuration
    |--------------------------------------------------------------------------
    |
    | Define thresholds for various metrics that trigger alerts
    |
    */

    'performance' => [
        // Response time thresholds (milliseconds)
        'response_time' => [
            'warning' => env('MONITOR_RESPONSE_TIME_WARNING', 200),
            'critical' => env('MONITOR_RESPONSE_TIME_CRITICAL', 500),
        ],
        
        // Database query thresholds (milliseconds)
        'query_time' => [
            'warning' => env('MONITOR_QUERY_TIME_WARNING', 50),
            'critical' => env('MONITOR_QUERY_TIME_CRITICAL', 100),
        ],
        
        // API call thresholds (milliseconds)
        'api_call_time' => [
            'calcom' => [
                'warning' => env('MONITOR_CALCOM_WARNING', 1000),
                'critical' => env('MONITOR_CALCOM_CRITICAL', 3000),
            ],
            'retell' => [
                'warning' => env('MONITOR_RETELL_WARNING', 500),
                'critical' => env('MONITOR_RETELL_CRITICAL', 2000),
            ],
        ],
    ],
    
    'availability' => [
        // Error rate thresholds (percentage)
        'error_rate' => [
            'warning' => env('MONITOR_ERROR_RATE_WARNING', 0.1),
            'critical' => env('MONITOR_ERROR_RATE_CRITICAL', 1.0),
        ],
        
        // Success rate thresholds (percentage)
        'success_rate' => [
            'warning' => env('MONITOR_SUCCESS_RATE_WARNING', 99.9),
            'critical' => env('MONITOR_SUCCESS_RATE_CRITICAL', 99.0),
        ],
        
        // Circuit breaker thresholds
        'circuit_breaker' => [
            'open_threshold' => env('MONITOR_CIRCUIT_OPEN', 5),
            'half_open_threshold' => env('MONITOR_CIRCUIT_HALF_OPEN', 3),
        ],
    ],
    
    'resources' => [
        // Queue size thresholds
        'queue_size' => [
            'webhooks-critical' => [
                'warning' => env('MONITOR_QUEUE_CRITICAL_WARNING', 50),
                'critical' => env('MONITOR_QUEUE_CRITICAL_CRITICAL', 100),
            ],
            'webhooks-high' => [
                'warning' => env('MONITOR_QUEUE_HIGH_WARNING', 200),
                'critical' => env('MONITOR_QUEUE_HIGH_CRITICAL', 500),
            ],
            'webhooks' => [
                'warning' => env('MONITOR_QUEUE_NORMAL_WARNING', 500),
                'critical' => env('MONITOR_QUEUE_NORMAL_CRITICAL', 1000),
            ],
        ],
        
        // Memory usage thresholds (MB)
        'memory_usage' => [
            'warning' => env('MONITOR_MEMORY_WARNING', 256),
            'critical' => env('MONITOR_MEMORY_CRITICAL', 384),
        ],
        
        // Database connections
        'db_connections' => [
            'warning' => env('MONITOR_DB_CONN_WARNING', 40),
            'critical' => env('MONITOR_DB_CONN_CRITICAL', 45),
        ],
        
        // Redis memory (percentage)
        'redis_memory' => [
            'warning' => env('MONITOR_REDIS_MEM_WARNING', 70),
            'critical' => env('MONITOR_REDIS_MEM_CRITICAL', 85),
        ],
    ],
    
    'business' => [
        // Failed bookings per hour
        'failed_bookings' => [
            'warning' => env('MONITOR_FAILED_BOOKINGS_WARNING', 5),
            'critical' => env('MONITOR_FAILED_BOOKINGS_CRITICAL', 10),
        ],
        
        // Webhook failures per hour
        'webhook_failures' => [
            'warning' => env('MONITOR_WEBHOOK_FAIL_WARNING', 10),
            'critical' => env('MONITOR_WEBHOOK_FAIL_CRITICAL', 25),
        ],
        
        // Duplicate bookings per day
        'duplicate_bookings' => [
            'warning' => env('MONITOR_DUPLICATE_WARNING', 1),
            'critical' => env('MONITOR_DUPLICATE_CRITICAL', 3),
        ],
        
        // No-show rate (percentage)
        'no_show_rate' => [
            'warning' => env('MONITOR_NOSHOW_WARNING', 5),
            'critical' => env('MONITOR_NOSHOW_CRITICAL', 10),
        ],
    ],
    
    'security' => [
        // Failed authentication attempts per hour
        'failed_auth' => [
            'warning' => env('MONITOR_FAILED_AUTH_WARNING', 10),
            'critical' => env('MONITOR_FAILED_AUTH_CRITICAL', 50),
        ],
        
        // Invalid signatures per hour
        'invalid_signatures' => [
            'warning' => env('MONITOR_INVALID_SIG_WARNING', 5),
            'critical' => env('MONITOR_INVALID_SIG_CRITICAL', 20),
        ],
        
        // Rate limit violations per hour
        'rate_limit_violations' => [
            'warning' => env('MONITOR_RATE_LIMIT_WARNING', 50),
            'critical' => env('MONITOR_RATE_LIMIT_CRITICAL', 100),
        ],
    ],
    
    // Alert channels configuration
    'channels' => [
        'email' => [
            'enabled' => env('MONITOR_EMAIL_ENABLED', true),
            'recipients' => env('MONITOR_EMAIL_RECIPIENTS', 'ops@askproai.de'),
        ],
        'slack' => [
            'enabled' => env('MONITOR_SLACK_ENABLED', true),
            'webhook' => env('MONITOR_SLACK_WEBHOOK'),
            'channel' => env('MONITOR_SLACK_CHANNEL', '#alerts'),
        ],
        'sms' => [
            'enabled' => env('MONITOR_SMS_ENABLED', false),
            'numbers' => env('MONITOR_SMS_NUMBERS', ''),
        ],
        'pagerduty' => [
            'enabled' => env('MONITOR_PAGERDUTY_ENABLED', false),
            'integration_key' => env('MONITOR_PAGERDUTY_KEY'),
        ],
    ],
    
    // Alert cooldown periods (minutes)
    'cooldown' => [
        'warning' => env('MONITOR_COOLDOWN_WARNING', 15),
        'critical' => env('MONITOR_COOLDOWN_CRITICAL', 5),
    ],
    
    // Escalation rules
    'escalation' => [
        'enabled' => env('MONITOR_ESCALATION_ENABLED', true),
        'warning_to_critical_minutes' => env('MONITOR_ESCALATION_MINUTES', 30),
        'max_alerts_per_hour' => env('MONITOR_MAX_ALERTS_HOUR', 20),
    ],
];