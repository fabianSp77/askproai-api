<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for webhook processing including
    | IP whitelisting, signature verification, and processing options.
    |
    */

    // IP Whitelist for webhook endpoints
    'ip_whitelist' => array_filter(array_map('trim', explode(',', env('WEBHOOK_IP_WHITELIST', '')))),
    
    // Retell.ai specific configuration
    'retell' => [
        // Known Retell.ai IP addresses
        'known_ips' => [
            // US West (Oregon) - us-west-2
            '35.160.120.126',
            '44.233.151.27', 
            '34.211.200.85',
            // Add more as documented by Retell
        ],
        
        // Signature verification
        'verify_signature' => env('RETELL_VERIFY_SIGNATURE', true),
        'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
        
        // Debug mode - bypasses signature verification
        'debug_mode' => env('RETELL_DEBUG_MODE', false),
        
        // Processing options
        'async_processing' => env('RETELL_ASYNC_PROCESSING', true),
        'queue_name' => env('RETELL_QUEUE_NAME', 'webhooks'),
    ],
    
    // Cal.com specific configuration
    'calcom' => [
        'known_ips' => [
            // Add Cal.com IPs when available
        ],
        'verify_signature' => env('CALCOM_VERIFY_SIGNATURE', true),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
    ],
    
    // Stripe specific configuration
    'stripe' => [
        'known_ips' => [
            // Stripe doesn't use fixed IPs, relies on signature verification
        ],
        'verify_signature' => env('STRIPE_VERIFY_SIGNATURE', true),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300), // 5 minutes
    ],
    
    // General webhook processing settings
    'processing' => [
        // Enable idempotency checking
        'idempotency_enabled' => env('WEBHOOK_IDEMPOTENCY_ENABLED', true),
        'idempotency_ttl' => env('WEBHOOK_IDEMPOTENCY_TTL', 86400), // 24 hours
        
        // Retry configuration
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60), // seconds
        
        // Logging
        'log_all_webhooks' => env('WEBHOOK_LOG_ALL', true),
        'log_retention_days' => env('WEBHOOK_LOG_RETENTION_DAYS', 30),
    ],
    
    // Emergency bypass - only for debugging
    'bypass_all_security' => env('WEBHOOK_BYPASS_SECURITY', false),
];