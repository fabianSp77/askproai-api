<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file handles all monitoring, alerting, and logging
    | for the AskProAI platform in production.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Error Tracking (Sentry)
    |--------------------------------------------------------------------------
    */
    'sentry' => [
        'enabled' => env('SENTRY_ENABLED', true),
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
        'release' => env('SENTRY_RELEASE', null),
        
        // Performance monitoring
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
        
        // PII and sensitive data
        'send_default_pii' => false,
        'before_send' => 'App\Services\Monitoring\SentryBeforeSend@handle',
        
        // Custom contexts
        'contexts' => [
            'stripe' => true,
            'customer_portal' => true,
            'tenant' => true,
        ],
        
        // Alert thresholds
        'alert_thresholds' => [
            'error_rate' => 0.05, // 5% error rate
            'transaction_duration' => 3000, // 3 seconds
            'crash_free_rate' => 0.99, // 99% crash-free sessions
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Performance Monitoring (APM)
    |--------------------------------------------------------------------------
    */
    'apm' => [
        'enabled' => env('APM_ENABLED', true),
        
        // Transaction monitoring
        'transactions' => [
            'stripe_webhook' => [
                'threshold' => 1000, // 1 second
                'sample_rate' => 1.0, // Monitor all Stripe webhooks
            ],
            'customer_portal' => [
                'threshold' => 500, // 500ms
                'sample_rate' => 0.5,
            ],
            'api_endpoints' => [
                'threshold' => 300, // 300ms
                'sample_rate' => 0.3,
            ],
        ],
        
        // Database query monitoring
        'database' => [
            'slow_query_threshold' => 100, // 100ms
            'log_queries' => env('LOG_QUERIES', false),
            'explain_queries' => env('EXPLAIN_QUERIES', false),
        ],
        
        // External service monitoring
        'external_services' => [
            'stripe' => [
                'timeout_threshold' => 5000, // 5 seconds
                'error_rate_threshold' => 0.01, // 1% error rate
            ],
            'calcom' => [
                'timeout_threshold' => 3000, // 3 seconds
                'error_rate_threshold' => 0.05, // 5% error rate
            ],
            'retell' => [
                'timeout_threshold' => 3000, // 3 seconds
                'error_rate_threshold' => 0.05, // 5% error rate
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    */
    'health_checks' => [
        'enabled' => env('HEALTH_CHECKS_ENABLED', true),
        'endpoint' => '/health',
        'secret' => env('HEALTH_CHECK_SECRET'),
        
        'checks' => [
            // Critical checks (system will be marked as down if these fail)
            'critical' => [
                'database' => [
                    'enabled' => true,
                    'timeout' => 5,
                ],
                'redis' => [
                    'enabled' => true,
                    'timeout' => 3,
                ],
                'stripe_api' => [
                    'enabled' => true,
                    'timeout' => 10,
                ],
            ],
            
            // Warning checks (system operational but degraded)
            'warning' => [
                'queue_size' => [
                    'enabled' => true,
                    'threshold' => 1000,
                ],
                'disk_space' => [
                    'enabled' => true,
                    'threshold' => 90, // 90% full
                ],
                'memory_usage' => [
                    'enabled' => true,
                    'threshold' => 85, // 85% used
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Rules
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'channels' => [
            'email' => [
                'enabled' => env('ALERT_EMAIL_ENABLED', true),
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
            'slack' => [
                'enabled' => env('ALERT_SLACK_ENABLED', false),
                'webhook_url' => env('ALERT_SLACK_WEBHOOK'),
                'channel' => env('ALERT_SLACK_CHANNEL', '#alerts'),
            ],
            'sms' => [
                'enabled' => env('ALERT_SMS_ENABLED', false),
                'recipients' => explode(',', env('ALERT_SMS_RECIPIENTS', '')),
            ],
        ],
        
        'rules' => [
            // Payment failures
            'payment_failure' => [
                'enabled' => true,
                'threshold' => 3, // Alert after 3 failures in 5 minutes
                'window' => 300, // 5 minutes
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'sms'],
            ],
            
            // Security issues
            'security_breach_attempt' => [
                'enabled' => true,
                'threshold' => 5, // 5 attempts
                'window' => 60, // 1 minute
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'sms'],
            ],
            
            // Stripe webhook failures
            'stripe_webhook_failure' => [
                'enabled' => true,
                'threshold' => 5,
                'window' => 300,
                'severity' => 'high',
                'channels' => ['email', 'slack'],
            ],
            
            // High error rate
            'high_error_rate' => [
                'enabled' => true,
                'threshold' => 0.05, // 5% error rate
                'window' => 300,
                'severity' => 'high',
                'channels' => ['email', 'slack'],
            ],
            
            // Database connection issues
            'database_connection_failure' => [
                'enabled' => true,
                'threshold' => 3,
                'window' => 60,
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'sms'],
            ],
            
            // Queue backlog
            'queue_backlog' => [
                'enabled' => true,
                'threshold' => 1000, // 1000 jobs
                'severity' => 'medium',
                'channels' => ['email'],
            ],
            
            // Customer portal downtime
            'portal_downtime' => [
                'enabled' => true,
                'threshold' => 1, // Any downtime
                'severity' => 'critical',
                'channels' => ['email', 'slack', 'sms'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        // Structured logging
        'structured' => env('STRUCTURED_LOGGING', true),
        
        // Log levels for different components
        'levels' => [
            'stripe' => env('LOG_LEVEL_STRIPE', 'info'),
            'customer_portal' => env('LOG_LEVEL_PORTAL', 'info'),
            'webhooks' => env('LOG_LEVEL_WEBHOOKS', 'debug'),
            'api' => env('LOG_LEVEL_API', 'info'),
            'security' => env('LOG_LEVEL_SECURITY', 'warning'),
        ],
        
        // Sensitive data masking
        'masking' => [
            'enabled' => true,
            'fields' => [
                'password',
                'stripe_secret',
                'api_key',
                'token',
                'secret',
                'card_number',
                'cvv',
                'ssn',
                'tax_id',
            ],
        ],
        
        // Log retention
        'retention' => [
            'days' => env('LOG_RETENTION_DAYS', 30),
            'archive' => env('LOG_ARCHIVE_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'endpoint' => '/metrics',
        'secret' => env('METRICS_SECRET'),
        
        // Business metrics
        'business' => [
            'subscriptions_created' => true,
            'subscriptions_cancelled' => true,
            'revenue_processed' => true,
            'portal_registrations' => true,
            'portal_logins' => true,
            'support_tickets' => true,
        ],
        
        // Technical metrics
        'technical' => [
            'request_duration' => true,
            'database_queries' => true,
            'cache_hits' => true,
            'queue_jobs' => true,
            'external_api_calls' => true,
            'error_rates' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        
        // Track security events
        'events' => [
            'failed_logins' => true,
            'password_resets' => true,
            'privilege_escalations' => true,
            'data_exports' => true,
            'api_key_usage' => true,
            'suspicious_activity' => true,
        ],
        
        // Rate limiting monitoring
        'rate_limiting' => [
            'track_violations' => true,
            'alert_threshold' => 100, // Alert after 100 violations
        ],
        
        // IP monitoring
        'ip_monitoring' => [
            'enabled' => true,
            'track_suspicious_ips' => true,
            'geo_blocking_alerts' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'enabled' => env('MONITORING_DASHBOARD_ENABLED', true),
        'route' => '/admin/monitoring',
        'middleware' => ['auth', 'can:view-monitoring'],
        
        'widgets' => [
            'system_health' => true,
            'error_rates' => true,
            'performance_metrics' => true,
            'business_metrics' => true,
            'security_overview' => true,
            'alert_history' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Specific Monitoring
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'stripe' => [
            'monitor_webhooks' => true,
            'monitor_api_calls' => true,
            'track_payment_failures' => true,
            'track_dispute_rates' => true,
            'track_subscription_churn' => true,
        ],
        
        'customer_portal' => [
            'track_page_views' => true,
            'track_user_actions' => true,
            'monitor_load_times' => true,
            'track_errors' => true,
            'session_analytics' => true,
        ],
    ],
];