<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for the application
    |
    */
    'default' => env('CURRENCY_DEFAULT', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Currencies supported by the exchange rate system
    |
    */
    'supported' => [
        'USD',
        'EUR',
        'GBP',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Exchange Rates
    |--------------------------------------------------------------------------
    |
    | These rates are used when the API is unavailable or no rate is found
    | Review these rates monthly to ensure accuracy
    |
    | Last reviewed: 2025-10-07
    |
    */
    'fallback_rates' => [
        'USD' => [
            'EUR' => env('FALLBACK_USD_EUR_RATE', 0.856),  // Updated 2025-10-07 from ECB
            'GBP' => env('FALLBACK_USD_GBP_RATE', 0.745),
        ],
        'EUR' => [
            'USD' => env('FALLBACK_EUR_USD_RATE', 1.168),
            'GBP' => env('FALLBACK_EUR_GBP_RATE', 0.870),
        ],
        'GBP' => [
            'USD' => env('FALLBACK_GBP_USD_RATE', 1.343),
            'EUR' => env('FALLBACK_GBP_EUR_RATE', 1.150),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for exchange rate API services
    |
    */
    'api' => [
        'ecb' => [
            'enabled' => true,
            'url' => 'https://api.frankfurter.app/latest',
            'timeout' => 10,
        ],
        'fixer' => [
            'enabled' => env('FIXER_API_ENABLED', false),
            'api_key' => env('FIXER_API_KEY'),
            'url' => 'http://data.fixer.io/api/latest',
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache duration for exchange rates (in seconds)
    |
    */
    'cache' => [
        'ttl' => env('EXCHANGE_RATE_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Ranges
    |--------------------------------------------------------------------------
    |
    | Plausible ranges for exchange rates (for anomaly detection)
    |
    */
    'validation' => [
        'min_rate' => 0.01,  // Minimum plausible rate
        'max_rate' => 100.0, // Maximum plausible rate
        'usd_eur' => [
            'min' => 0.70,   // USD → EUR should be between 0.70 and 1.20
            'max' => 1.20,
        ],
    ],
];
