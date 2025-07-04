<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'calcom' => [
        'api_key' => env('CALCOM_API_KEY', env('DEFAULT_CALCOM_API_KEY')),
        'team_slug' => env('CALCOM_TEAM_SLUG', env('DEFAULT_CALCOM_TEAM_SLUG', 'askproai')),
        'base_url' => env('CALCOM_BASE_URL', 'https://api.cal.com'),
        'user_id' => env('CALCOM_USER_ID'),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
        
        // V2 API Configuration
        'api_version' => env('CALCOM_API_VERSION', 'v2'),
        'v2_base_url' => env('CALCOM_V2_BASE_URL', 'https://api.cal.com/v2'),
        'use_v2_api' => env('CALCOM_USE_V2_API', false),
        
        // Migration settings
        'v2_enabled_methods' => explode(',', env('CALCOM_V2_ENABLED_METHODS', '')),
        'v2_mandatory_methods' => explode(',', env('CALCOM_V2_MANDATORY_METHODS', '')),
        
        // Performance settings
        'cache_ttl' => env('CALCOM_CACHE_TTL', 300), // 5 minutes
        'circuit_breaker_enabled' => env('CALCOM_CIRCUIT_BREAKER_ENABLED', true),
        'circuit_breaker_threshold' => env('CALCOM_CIRCUIT_BREAKER_THRESHOLD', 5),
        'circuit_breaker_timeout' => env('CALCOM_CIRCUIT_BREAKER_TIMEOUT', 60),
        
        // Default event type for testing
        'default_event_type_id' => env('CALCOM_DEFAULT_EVENT_TYPE_ID'),
    ],

    'retell' => [
        'api_key' => env('DEFAULT_RETELL_API_KEY', env('RETELL_TOKEN')),
        'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
        'secret' => env('RETELL_WEBHOOK_SECRET', env('DEFAULT_RETELL_API_KEY', env('RETELL_TOKEN'))), // Deprecated, use webhook_secret
        'token' => env('RETELL_TOKEN'),
        'agent_id' => env('DEFAULT_RETELL_AGENT_ID'),
        'base_url' => env('RETELL_BASE_URL', env('RETELL_BASE', 'https://api.retellai.com')),
        'base' => env('RETELL_BASE', 'https://api.retellai.com'),
        'verify_ip' => env('RETELL_VERIFY_IP', false),
        'default_company_id' => env('RETELL_DEFAULT_COMPANY_ID', 1),
        'default_branch_id' => env('RETELL_DEFAULT_BRANCH_ID', 1),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'publishable' => env('STRIPE_PUBLISHABLE_KEY', env('STRIPE_KEY')),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'webhook' => [
        'async' => [
            'retell' => env('WEBHOOK_ASYNC_RETELL', true),
            'calcom' => env('WEBHOOK_ASYNC_CALCOM', true),
            'stripe' => env('WEBHOOK_ASYNC_STRIPE', true),
        ],
        'retry' => [
            'max_attempts' => env('WEBHOOK_RETRY_MAX_ATTEMPTS', 3),
            'backoff' => env('WEBHOOK_RETRY_BACKOFF', '10,30,90'),
        ],
        'deduplication' => [
            'ttl' => env('WEBHOOK_DEDUPLICATION_TTL', 300), // 5 minutes
            'processing_ttl' => env('WEBHOOK_PROCESSING_TTL', 60), // 1 minute
        ],
        'monitoring' => [
            'alert_threshold' => env('WEBHOOK_ALERT_THRESHOLD', 100), // ms
            'log_slow_webhooks' => env('WEBHOOK_LOG_SLOW', true),
        ],
    ],

    'whatsapp' => [
        'business_id' => env('WHATSAPP_BUSINESS_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
        'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
        
        // Templates
        'templates' => [
            'appointment_reminder_24h' => env('WHATSAPP_TEMPLATE_REMINDER_24H', 'appointment_reminder_24h'),
            'appointment_reminder_2h' => env('WHATSAPP_TEMPLATE_REMINDER_2H', 'appointment_reminder_2h'),
            'appointment_reminder_30min' => env('WHATSAPP_TEMPLATE_REMINDER_30MIN', 'appointment_reminder_30min'),
            'appointment_confirmation' => env('WHATSAPP_TEMPLATE_CONFIRMATION', 'appointment_confirmation'),
            'appointment_cancellation' => env('WHATSAPP_TEMPLATE_CANCELLATION', 'appointment_cancellation'),
        ],
        
        // Twilio alternative configuration (if using Twilio for WhatsApp)
        'provider' => env('WHATSAPP_PROVIDER', 'meta'), // meta or twilio
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        'sandbox_mode' => env('TWILIO_SANDBOX_MODE', false),
        
        // Webhook configuration
        'webhook_url' => env('TWILIO_WEBHOOK_URL', env('APP_URL') . '/api/mcp/twilio/status-callback'),
        
        // Feature flags
        'sms_enabled' => env('TWILIO_SMS_ENABLED', true),
        'whatsapp_enabled' => env('TWILIO_WHATSAPP_ENABLED', true),
        'voice_enabled' => env('TWILIO_VOICE_ENABLED', false),
        
        // Rate limiting
        'rate_limit' => env('TWILIO_RATE_LIMIT', 1), // messages per second
        
        // Logging
        'log_messages' => env('TWILIO_LOG_MESSAGES', true),
    ],

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
        'pro' => env('DEEPL_PRO', false), // true for Pro API, false for Free API
    ],

];