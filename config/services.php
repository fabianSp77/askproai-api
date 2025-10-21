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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'calcom' => [
        'api_key' => env('CALCOM_API_KEY'),
        'base_url' => env('CALCOM_BASE_URL', 'https://api.cal.com/v2'),
        'api_version' => env('CALCOM_API_VERSION', '2024-08-13'),
        'event_type_id' => env('CALCOM_EVENT_TYPE_ID'),
        'team_slug' => env('CALCOM_TEAM_SLUG'),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
        'client_id' => env('CALCOM_CLIENT_ID'),
        'client_secret' => env('CALCOM_CLIENT_SECRET'),
        'redirect_uri' => env('CALCOM_REDIRECT_URI'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'samedi' => [
        'base_url' => env('SAMEDI_BASE_URL', 'https://api.samedi.de/api/v1'),
        'client_id' => env('SAMEDI_CLIENT_ID', ''),
        'client_secret' => env('SAMEDI_CLIENT_SECRET', ''),
    ],

    'retellai' => [
        'api_key' => env('RETELLAI_API_KEY', env('RETELL_TOKEN')),
        'base_url' => rtrim(env('RETELLAI_BASE_URL', env('RETELL_BASE_URL', env('RETELL_BASE', 'https://api.retell.ai'))), '/'),
        'agent_id' => env('RETELL_AGENT_ID', 'agent_9a8202a740cd3120d96fcfda1e'),
        'webhook_secret' => env('RETELLAI_WEBHOOK_SECRET', env('RETELL_WEBHOOK_SECRET')),
        'log_webhooks' => env('RETELLAI_LOG_WEBHOOKS', true),
        'function_secret' => env('RETELLAI_FUNCTION_SECRET'),
        'allow_unsigned_webhooks' => env('RETELLAI_ALLOW_UNSIGNED_WEBHOOKS', false),
    ],

    'firebase' => [
        'credentials_path' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
