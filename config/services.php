<?php

return [

    /* ----------------------------------------------------------
     |  Externe Services
     * --------------------------------------------------------- */

    'calcom' => [
        'api_key'        => env('CALCOM_API_KEY'),
        'base_url'       => env('CALCOM_BASE_URL', 'https://api.cal.com/v2'), // V2 ONLY - V1 deprecated end 2025
        'v2_base_url'    => env('CALCOM_V2_BASE_URL', 'https://api.cal.com/v2'), // V2 for all operations
        'team_slug'      => env('CALCOM_TEAM_SLUG'),
        'team_id'        => env('CALCOM_TEAM_ID'),
        'event_type_id'  => env('CALCOM_EVENT_TYPE_ID'),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
        'max_retries'    => env('CALCOM_MAX_RETRIES', 3),
        'timeout'        => env('CALCOM_TIMEOUT', 30),
        'timezone'       => env('CALCOM_TIMEZONE', 'Europe/Berlin'),
        'language'       => env('CALCOM_LANGUAGE', 'de'),
        'user_id'        => env('CALCOM_USER_ID'),
        'organization_id' => env('CALCOM_ORGANIZATION_ID', 77594),
        'username'       => env('CALCOM_USERNAME', 'askproai'),
        'v2_api_version' => env('CALCOM_V2_API_VERSION', '2024-08-13'),
        'hybrid_mode'    => env('CALCOM_HYBRID_MODE', false), // Disable hybrid mode - V2 only
        'use_v2_only'    => env('CALCOM_USE_V2_ONLY', true), // Force V2 usage
        'rate_limit_delay' => env('CALCOM_RATE_LIMIT_DELAY', 200), // ms between requests
    ],

    'retell' => [
        'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    ],

    'webhooks' => [
        'enabled' => env('WEBHOOKS_ENABLED', false),
        'secret' => env('WEBHOOK_SECRET', 'your-webhook-secret-key'),
        'urls' => [
            'transaction' => [
                'created' => explode(',', env('WEBHOOK_TRANSACTION_CREATED', '')),
                'updated' => explode(',', env('WEBHOOK_TRANSACTION_UPDATED', '')),
                'deleted' => explode(',', env('WEBHOOK_TRANSACTION_DELETED', '')),
            ],
        ],
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_times' => env('WEBHOOK_RETRY_TIMES', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60),
    ],

    // ... (weitere Service-Configs wie stripe, mailgun, etc.)

];
