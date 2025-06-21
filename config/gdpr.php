<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GDPR Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all GDPR-related configuration for the application,
    | including cookie categories, retention periods, and compliance settings.
    |
    */

    'enabled' => env('GDPR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cookie Settings
    |--------------------------------------------------------------------------
    */
    
    'cookie_policy_version' => '1.0',
    'cookie_consent_duration_days' => 365,
    
    'cookie_categories' => [
        'functional' => [
            'askproai_session',
            'XSRF-TOKEN',
            'locale',
            'timezone',
        ],
        'analytics' => [
            '_ga',
            '_gid',
            '_gat',
            '_gat_gtag_*',
            'gtag_*',
        ],
        'marketing' => [
            '_fbp',
            'fr',
            'tr',
            '_gcl_*',
            'ide',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Periods
    |--------------------------------------------------------------------------
    */
    
    'retention_periods' => [
        'appointments' => 730, // 2 years
        'calls' => 365,       // 1 year
        'invoices' => 3650,   // 10 years (legal requirement in Germany)
        'consents' => 1095,   // 3 years
        'logs' => 90,         // 90 days
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR Request Settings
    |--------------------------------------------------------------------------
    */
    
    'request_response_days' => 30, // Maximum days to respond to GDPR requests
    'export_format' => 'json',     // json or csv
    'export_include_files' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Anonymization Settings
    |--------------------------------------------------------------------------
    */
    
    'anonymization' => [
        'enabled' => true,
        'placeholder_email' => 'deleted.user.{id}@anonymized.local',
        'placeholder_name' => 'DELETED USER',
        'keep_statistical_data' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party Services
    |--------------------------------------------------------------------------
    */
    
    'third_party_services' => [
        'google_analytics' => [
            'enabled' => env('GOOGLE_ANALYTICS_ENABLED', false),
            'tracking_id' => env('GOOGLE_ANALYTICS_ID'),
            'anonymize_ip' => true,
            'requires_consent' => 'analytics',
        ],
        'facebook_pixel' => [
            'enabled' => env('FACEBOOK_PIXEL_ENABLED', false),
            'pixel_id' => env('FACEBOOK_PIXEL_ID'),
            'requires_consent' => 'marketing',
        ],
        'retell_ai' => [
            'data_processing_agreement' => true,
            'data_location' => 'EU',
            'encryption' => 'AES-256',
        ],
        'cal_com' => [
            'data_processing_agreement' => true,
            'data_location' => 'EU',
            'gdpr_compliant' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal Pages
    |--------------------------------------------------------------------------
    */
    
    'legal_pages' => [
        'privacy_policy_url' => '/portal/privacy-policy',
        'cookie_policy_url' => '/portal/cookie-policy',
        'terms_of_service_url' => '/portal/terms',
        'imprint_url' => '/portal/imprint',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    */
    
    'data_protection_officer' => [
        'name' => env('DPO_NAME', 'Data Protection Officer'),
        'email' => env('DPO_EMAIL', 'dpo@askproai.com'),
        'phone' => env('DPO_PHONE', '+49 30 12345678'),
        'address' => env('DPO_ADDRESS', 'Beispielstraße 1, 10115 Berlin, Germany'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Features
    |--------------------------------------------------------------------------
    */
    
    'features' => [
        'cookie_consent_banner' => true,
        'privacy_center' => true,
        'data_export' => true,
        'data_deletion' => true,
        'consent_logging' => true,
        'automated_deletion' => false, // Requires manual review
        'breach_notification' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | German-specific Settings (BDSG)
    |--------------------------------------------------------------------------
    */
    
    'bdsg' => [
        'employee_data_protection' => true,
        'works_council_agreement' => false,
        'telecom_secrecy' => true,
    ],

];