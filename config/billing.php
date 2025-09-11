<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Abrechnungssystem Konfiguration
    |--------------------------------------------------------------------------
    |
    | Hauptkonfiguration für das mehrstufige Abrechnungssystem
    | Platform → Reseller → Endkunden
    |
    */

    'enabled' => env('BILLING_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Stripe Konfiguration
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        'api_version' => '2023-10-16',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preismodelle (in Cents)
    |--------------------------------------------------------------------------
    */
    'pricing' => [
        // Platform-Grundpreise (was Platform von Resellern erhält)
        'platform' => [
            'call_minutes' => env('PLATFORM_PRICE_CALL_MINUTES', 30), // 0.30€/Minute
            'api_call' => env('PLATFORM_PRICE_API_CALL', 10),        // 0.10€/Anruf
            'appointment' => env('PLATFORM_PRICE_APPOINTMENT', 100),  // 1.00€/Termin
            'sms' => env('PLATFORM_PRICE_SMS', 5),                   // 0.05€/SMS
        ],
        
        // Standard-Endkundenpreise (was Kunden zahlen)
        'customer' => [
            'call_minutes' => env('CUSTOMER_PRICE_CALL_MINUTES', 40), // 0.40€/Minute
            'api_call' => env('CUSTOMER_PRICE_API_CALL', 15),        // 0.15€/Anruf
            'appointment' => env('CUSTOMER_PRICE_APPOINTMENT', 150),  // 1.50€/Termin
            'sms' => env('CUSTOMER_PRICE_SMS', 8),                   // 0.08€/SMS
        ],
        
        // Mindestvergütungen
        'minimums' => [
            'call_duration_seconds' => 60,  // Mindestabrechnung 1 Minute
            'balance_warning' => 1000,       // Warnung bei < 10€
            'balance_critical' => 500,       // Kritisch bei < 5€
            'auto_topup_trigger' => 1000,    // Auto-Aufladung bei < 10€
            'auto_topup_amount' => 5000,     // Auto-Aufladung 50€
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reseller Konfiguration
    |--------------------------------------------------------------------------
    */
    'reseller' => [
        'enabled' => env('RESELLER_SYSTEM_ENABLED', true),
        'default_commission_rate' => env('DEFAULT_COMMISSION_RATE', 25.0), // 25% Standard-Provision
        'minimum_balance' => env('RESELLER_MIN_BALANCE', 10000),          // Min. 100€ Guthaben
        'payout_threshold' => env('RESELLER_PAYOUT_THRESHOLD', 5000),     // Auszahlung ab 50€
        'payout_frequency' => env('RESELLER_PAYOUT_FREQUENCY', 'monthly'), // monthly, weekly, daily
        'allow_custom_pricing' => env('RESELLER_CUSTOM_PRICING', true),   // Eigene Preise erlauben
        'max_markup_percentage' => env('RESELLER_MAX_MARKUP', 100),       // Max. 100% Aufschlag
    ],

    /*
    |--------------------------------------------------------------------------
    | Auflade-Beträge (in Cents)
    |--------------------------------------------------------------------------
    */
    'topup_amounts' => [
        1000,   // 10€
        2000,   // 20€
        2500,   // 25€ (Standard)
        5000,   // 50€
        7500,   // 75€
        10000,  // 100€
        15000,  // 150€
        25000,  // 250€
        50000,  // 500€ (Enterprise)
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Währung und Formatierung
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'code' => 'EUR',
        'symbol' => '€',
        'decimal_separator' => ',',
        'thousands_separator' => '.',
        'precision' => 2,
        'position' => 'after', // Symbol-Position: after = "10,00€", before = "€10,00"
    ],

    /*
    |--------------------------------------------------------------------------
    | Benachrichtigungen
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'low_balance' => [
            'enabled' => env('NOTIFY_LOW_BALANCE', true),
            'email' => env('NOTIFY_LOW_BALANCE_EMAIL', true),
            'sms' => env('NOTIFY_LOW_BALANCE_SMS', false),
            'webhook' => env('NOTIFY_LOW_BALANCE_WEBHOOK', true),
        ],
        'payment_success' => [
            'enabled' => env('NOTIFY_PAYMENT_SUCCESS', true),
            'email' => env('NOTIFY_PAYMENT_EMAIL', true),
        ],
        'usage_alerts' => [
            'high_usage_threshold' => env('HIGH_USAGE_THRESHOLD', 10000), // 100€/Tag
            'unusual_activity' => env('UNUSUAL_ACTIVITY_DETECTION', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring und Health Checks
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'health_check_interval' => env('BILLING_HEALTH_CHECK_INTERVAL', 300), // 5 Minuten
        'transaction_check_limit' => env('TRANSACTION_CHECK_LIMIT', 100),
        'balance_sync_check' => env('BALANCE_SYNC_CHECK', true),
        'webhook_failure_threshold' => env('WEBHOOK_FAILURE_THRESHOLD', 3),
        'alert_channels' => ['mail', 'slack', 'database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sicherheit
    |--------------------------------------------------------------------------
    */
    'security' => [
        'require_2fa_for_topup' => env('REQUIRE_2FA_TOPUP', false),
        'max_daily_topup' => env('MAX_DAILY_TOPUP', 100000), // 1000€/Tag
        'suspicious_activity_lock' => env('SUSPICIOUS_LOCK', true),
        'api_rate_limit' => env('BILLING_API_RATE_LIMIT', 60), // 60 Anfragen/Minute
        'webhook_ip_whitelist' => env('WEBHOOK_IP_WHITELIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup und Recovery
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('BILLING_BACKUP_ENABLED', true),
        'frequency' => env('BILLING_BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('BILLING_BACKUP_RETENTION', 30),
        'encrypt_backups' => env('BILLING_BACKUP_ENCRYPT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'auto_topup' => env('FEATURE_AUTO_TOPUP', false),
        'volume_discounts' => env('FEATURE_VOLUME_DISCOUNTS', false),
        'subscription_plans' => env('FEATURE_SUBSCRIPTIONS', false),
        'postpaid_billing' => env('FEATURE_POSTPAID', false),
        'invoice_generation' => env('FEATURE_INVOICES', true),
        'multi_currency' => env('FEATURE_MULTI_CURRENCY', false),
        'white_label' => env('FEATURE_WHITE_LABEL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Entwicklungsumgebung
    |--------------------------------------------------------------------------
    */
    'development' => [
        'test_mode' => env('BILLING_TEST_MODE', false),
        'bypass_payment' => env('BILLING_BYPASS_PAYMENT', false),
        'log_all_transactions' => env('BILLING_LOG_ALL', true),
        'simulate_webhook_failures' => env('SIMULATE_WEBHOOK_FAILURES', false),
    ],
];
