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
        'api_key' => env('DEFAULT_CALCOM_API_KEY'),
        'team_slug' => env('DEFAULT_CALCOM_TEAM_SLUG', 'askproai'),
        'base_url' => env('CALCOM_BASE_URL', 'https://api.cal.com'),
    ],

    'retell' => [
        'api_key' => env('DEFAULT_RETELL_API_KEY', env('RETELL_TOKEN')),
        'secret' => env('RETELL_WEBHOOK_SECRET', env('DEFAULT_RETELL_API_KEY', env('RETELL_TOKEN'))),
        'token' => env('RETELL_TOKEN'),
        'agent_id' => env('DEFAULT_RETELL_AGENT_ID'),
        'base_url' => env('RETELL_BASE_URL', env('RETELL_BASE', 'https://api.retellai.com')),
        'base' => env('RETELL_BASE', 'https://api.retellai.com'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'publishable' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];