<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retell AI MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external Retell.ai MCP (Model Context Protocol) server
    | that enables AI-initiated outbound calls.
    |
    */

    // External MCP Server Connection
    'server' => [
        'url' => env('RETELL_MCP_SERVER_URL', 'http://localhost:3001'),
        'token' => env('RETELL_MCP_SERVER_TOKEN'),
        'timeout' => env('RETELL_MCP_TIMEOUT', 30),
        'retry_times' => env('RETELL_MCP_RETRY_TIMES', 3),
        'retry_delay' => env('RETELL_MCP_RETRY_DELAY', 1000), // milliseconds
    ],

    // Feature Toggles
    'features' => [
        'enabled' => env('RETELL_MCP_ENABLED', true),
        'campaigns' => env('RETELL_MCP_CAMPAIGNS_ENABLED', true),
        'voice_testing' => env('RETELL_MCP_VOICE_TESTING_ENABLED', true),
        'webhook_validation' => env('RETELL_MCP_WEBHOOK_VALIDATION', true),
    ],

    // Call Configuration
    'calls' => [
        'default_from_number' => env('RETELL_DEFAULT_FROM_NUMBER'),
        'max_duration' => env('RETELL_MAX_CALL_DURATION', 1800), // 30 minutes
        'recording_enabled' => env('RETELL_RECORDING_ENABLED', true),
        'transcription_enabled' => env('RETELL_TRANSCRIPTION_ENABLED', true),
    ],

    // Campaign Configuration
    'campaigns' => [
        'batch_size' => env('RETELL_CAMPAIGN_BATCH_SIZE', 10),
        'delay_between_calls' => env('RETELL_CAMPAIGN_DELAY', 2000), // milliseconds
        'max_concurrent_calls' => env('RETELL_MAX_CONCURRENT_CALLS', 5),
        'retry_failed_calls' => env('RETELL_RETRY_FAILED_CALLS', true),
        'retry_attempts' => env('RETELL_RETRY_ATTEMPTS', 2),
    ],

    // Batch Processing
    'batch_processing' => [
        'enabled' => env('RETELL_BATCH_PROCESSING_ENABLED', true),
        'chunk_size' => env('RETELL_BATCH_CHUNK_SIZE', 20),
        'delay_between_calls_ms' => env('RETELL_BATCH_CALL_DELAY_MS', 500),
        'max_jobs_per_batch' => env('RETELL_MAX_JOBS_PER_BATCH', 100),
        'queue_name' => env('RETELL_BATCH_QUEUE', 'campaigns'),
        'allow_failures' => env('RETELL_BATCH_ALLOW_FAILURES', true),
        'job_timeout' => env('RETELL_BATCH_JOB_TIMEOUT', 120), // seconds
    ],

    // Rate Limiting
    'rate_limiting' => [
        'global' => [
            'calls_per_minute' => env('RETELL_CALLS_PER_MINUTE', 30),
            'calls_per_hour' => env('RETELL_CALLS_PER_HOUR', 500),
            'calls_per_day' => env('RETELL_CALLS_PER_DAY', 5000),
        ],
        'campaigns' => [
            'calls_per_minute' => env('RETELL_CAMPAIGN_CALLS_PER_MINUTE', 30),
            'calls_per_hour' => env('RETELL_CAMPAIGN_CALLS_PER_HOUR', 300),
            'concurrent_campaigns' => env('RETELL_MAX_CONCURRENT_CAMPAIGNS', 3),
        ],
        'per_company' => [
            'multiplier' => env('RETELL_PER_COMPANY_MULTIPLIER', 0.5),
            'calls_per_minute' => env('RETELL_COMPANY_CALLS_PER_MINUTE', 15),
        ],
    ],

    // Circuit Breaker
    'circuit_breaker' => [
        'failure_threshold' => env('RETELL_CB_FAILURE_THRESHOLD', 5),
        'recovery_time' => env('RETELL_CB_RECOVERY_TIME', 60), // seconds
        'timeout' => env('RETELL_CB_TIMEOUT', 10), // seconds
    ],

    // Monitoring & Logging
    'monitoring' => [
        'log_channel' => env('RETELL_LOG_CHANNEL', 'retell-mcp'),
        'log_level' => env('RETELL_LOG_LEVEL', 'info'),
        'metrics_enabled' => env('RETELL_METRICS_ENABLED', true),
        'webhook_debug' => env('RETELL_WEBHOOK_DEBUG', false),
    ],

    // Security
    'security' => [
        'webhook_secret' => env('RETELL_MCP_WEBHOOK_SECRET'),
        'allowed_ips' => env('RETELL_MCP_ALLOWED_IPS') ? explode(',', env('RETELL_MCP_ALLOWED_IPS')) : [],
        'encrypt_sensitive_data' => env('RETELL_ENCRYPT_SENSITIVE_DATA', true),
    ],

    // Test Mode
    'testing' => [
        'test_mode' => env('RETELL_TEST_MODE', false),
        'test_numbers' => env('RETELL_TEST_NUMBERS') ? explode(',', env('RETELL_TEST_NUMBERS')) : [],
        'mock_calls' => env('RETELL_MOCK_CALLS', false),
    ],

    // Voice Test Scenarios
    'test_scenarios' => [
        'greeting' => [
            'name' => 'Basic Greeting Test',
            'duration' => 30,
            'expected_elements' => ['company_name', 'greeting', 'offer_help'],
        ],
        'appointment_booking' => [
            'name' => 'Appointment Booking Flow',
            'duration' => 180,
            'expected_elements' => ['service_inquiry', 'date_time', 'confirmation'],
        ],
        'objection_handling' => [
            'name' => 'Objection Handling',
            'duration' => 120,
            'expected_elements' => ['objection_response', 'alternative_offer'],
        ],
    ],
];