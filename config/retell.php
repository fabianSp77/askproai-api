<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retell AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Retell AI appointment booking and extraction services
    |
    */

    'min_confidence' => env('RETELL_MIN_CONFIDENCE', 60),
    'default_duration' => env('RETELL_DEFAULT_DURATION', 45),
    'timezone' => env('RETELL_TIMEZONE', 'Europe/Berlin'),
    'language' => env('RETELL_LANGUAGE', 'de'),
    'fallback_phone' => env('RETELL_FALLBACK_PHONE', '+49000000000'),
    'fallback_email' => env('RETELL_FALLBACK_EMAIL', 'noreply@placeholder.local'),
    'default_company_id' => env('RETELL_DEFAULT_COMPANY_ID', null), // Should be set per environment

    /*
    |--------------------------------------------------------------------------
    | Business Hours Configuration
    |--------------------------------------------------------------------------
    */

    'business_hours' => [
        'start' => env('RETELL_BUSINESS_HOUR_START', 8),
        'end' => env('RETELL_BUSINESS_HOUR_END', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction Configuration
    |--------------------------------------------------------------------------
    */

    'extraction' => [
        'max_transcript_length' => env('RETELL_MAX_TRANSCRIPT_LENGTH', 50000),
        'base_confidence' => env('RETELL_BASE_CONFIDENCE', 50),
        'retell_confidence' => env('RETELL_RETELL_CONFIDENCE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'branch_ttl' => env('RETELL_CACHE_BRANCH_TTL', 3600), // 1 hour
        'service_ttl' => env('RETELL_CACHE_SERVICE_TTL', 3600), // 1 hour
    ],

];