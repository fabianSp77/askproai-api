<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gateway Mode Feature Flag
    |--------------------------------------------------------------------------
    |
    | Enable/disable the Service Gateway routing feature.
    | When enabled, calls can be routed to: appointment, service_desk, or hybrid
    |
    */
    'mode_enabled' => env('GATEWAY_MODE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default Gateway Mode
    |--------------------------------------------------------------------------
    |
    | The default mode when no company-specific configuration exists.
    | Options: 'appointment', 'service_desk', 'hybrid'
    |
    */
    'default_mode' => env('GATEWAY_DEFAULT_MODE', 'appointment'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Gateway Modes
    |--------------------------------------------------------------------------
    |
    | List of valid gateway modes for validation.
    |
    */
    'modes' => ['appointment', 'service_desk', 'hybrid'],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for hybrid mode intent detection.
    |
    */
    'hybrid' => [
        'fallback_mode' => env('GATEWAY_HYBRID_FALLBACK', 'appointment'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags (2-Phase Delivery-Gate Pattern)
    |--------------------------------------------------------------------------
    |
    | Control flags for progressive rollout of enrichment features.
    |
    */
    'features' => [
        // Enable 2-phase delivery flow (wait for enrichment)
        'enrichment_enabled' => env('GATEWAY_ENRICHMENT_ENABLED', false),

        // Include presigned audio URL in webhook payloads
        'audio_in_webhook' => env('GATEWAY_AUDIO_IN_WEBHOOK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for case output delivery and enrichment.
    |
    */
    'delivery' => [
        // Initial delay before first delivery attempt (when wait_for_enrichment=true)
        'initial_delay_seconds' => env('GATEWAY_DELIVERY_INITIAL_DELAY', 90),

        // Maximum time to wait for enrichment before delivering with partial data
        'enrichment_timeout_seconds' => env('GATEWAY_ENRICHMENT_TIMEOUT', 180),

        // TTL for presigned audio URLs in webhook payload (minutes)
        'audio_url_ttl_minutes' => env('GATEWAY_AUDIO_URL_TTL', 60),

        // Queue name for delivery jobs
        'queue' => env('GATEWAY_DELIVERY_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for case output handlers.
    |
    */
    'output' => [
        'queue' => env('GATEWAY_OUTPUT_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Alerts Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for administrative notifications on critical failures.
    | When delivery permanently fails, admins are notified via these channels.
    |
    */
    'alerts' => [
        // Email address(es) for delivery failure notifications
        // Can be comma-separated for multiple recipients
        'admin_email' => env('GATEWAY_ADMIN_EMAIL'),

        // Enable/disable admin alerts
        'enabled' => env('GATEWAY_ALERTS_ENABLED', true),

        // Optional Slack webhook for critical alerts
        'slack_webhook' => env('GATEWAY_SLACK_WEBHOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Configuration
    |--------------------------------------------------------------------------
    |
    | Cache time-to-live settings for dashboard widgets.
    | Centralizes all widget cache durations for easy tuning.
    |
    */
    'cache' => [
        // Widget statistics (real-time feel)
        'widget_stats_seconds' => env('GATEWAY_CACHE_WIDGET_STATS', 55),

        // Widget trend data (longer retention)
        'widget_trends_seconds' => env('GATEWAY_CACHE_WIDGET_TRENDS', 300),

        // Recent activity (very fresh)
        'recent_activity_seconds' => env('GATEWAY_CACHE_RECENT_ACTIVITY', 30),
    ],
];
