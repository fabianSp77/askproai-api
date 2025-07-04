<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook processing, security, and monitoring
    |
    */

    'replay_window' => env('WEBHOOK_REPLAY_WINDOW', 300), // 5 minutes
    
    'rate_limits' => [
        'standard' => '60,1', // 60 requests per minute
        'health_check' => '10,1', // 10 requests per minute
    ],
    
    'providers' => [
        'retell' => [
            'enabled' => true,
            'verify_signature' => true,
            'verify_ip' => env('RETELL_VERIFY_IP', false),
            'allowed_ips' => [
                // Retell.ai webhook IPs (if known)
            ],
        ],
        'calcom' => [
            'enabled' => true,
            'verify_signature' => true,
            'verify_ip' => false,
        ],
        'stripe' => [
            'enabled' => true,
            'verify_signature' => true,
            'verify_ip' => false,
        ],
    ],
    
    'logging' => [
        'enabled' => true,
        'channel' => 'webhook_security',
        'log_payloads' => env('WEBHOOK_LOG_PAYLOADS', true),
        'mask_sensitive_data' => true,
        'retention_days' => 30,
    ],
    
    'monitoring' => [
        'track_metrics' => true,
        'alert_on_failures' => true,
        'failure_threshold' => 5, // Alert after 5 consecutive failures
    ],
    
    'queues' => [
        'high_priority' => 'webhooks-high-priority',
        'standard' => 'webhooks',
        'retry' => 'webhooks-retry',
    ],
];