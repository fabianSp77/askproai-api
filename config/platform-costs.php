<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Cost Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for tracking external platform costs
    | including Retell.ai, Twilio, Cal.com, and other services.
    |
    */

    'retell' => [
        'enabled' => true,
        'pricing' => [
            // Retell.ai pricing per minute in USD
            'per_minute_usd' => env('RETELL_COST_PER_MINUTE_USD', 0.07),
            // Additional costs for AI models, webhooks, etc.
            'ai_surcharge_usd' => env('RETELL_AI_SURCHARGE_USD', 0.0),
        ],
    ],

    'twilio' => [
        'enabled' => true,
        'pricing' => [
            // Twilio pricing varies by country, these are defaults
            'inbound_per_minute_usd' => env('TWILIO_INBOUND_COST_USD', 0.0085),
            'outbound_per_minute_usd' => env('TWILIO_OUTBOUND_COST_USD', 0.013),
            // Phone number monthly cost
            'phone_number_monthly_usd' => env('TWILIO_PHONE_NUMBER_COST_USD', 1.0),
        ],
        'estimation' => [
            // Enable/disable estimation when webhook doesn't provide actual Twilio cost
            // IMPORTANT: Retell's combined_cost does NOT include Twilio telephony costs
            'enabled' => env('TWILIO_ESTIMATION_ENABLED', true),
            // Minimum call duration to estimate costs (avoid 0-duration calls)
            'min_duration_sec' => env('TWILIO_MIN_DURATION_SEC', 1),
        ],
    ],

    'calcom' => [
        'enabled' => true,
        'pricing' => [
            // Cal.com pricing per user per month in USD
            'per_user_per_month_usd' => env('CALCOM_USER_COST_USD', 15),
            // Team/Organization tier pricing if different
            'team_per_user_per_month_usd' => env('CALCOM_TEAM_USER_COST_USD', 15),
        ],
    ],

    'openai' => [
        'enabled' => false, // Enable if tracking OpenAI costs separately
        'pricing' => [
            // GPT-4 pricing per 1K tokens
            'gpt4_input_per_1k' => env('OPENAI_GPT4_INPUT_COST', 0.03),
            'gpt4_output_per_1k' => env('OPENAI_GPT4_OUTPUT_COST', 0.06),
            // GPT-3.5 pricing per 1K tokens
            'gpt35_input_per_1k' => env('OPENAI_GPT35_INPUT_COST', 0.0005),
            'gpt35_output_per_1k' => env('OPENAI_GPT35_OUTPUT_COST', 0.0015),
        ],
    ],

    'exchange_rates' => [
        // Default exchange rates (fallback if API is unavailable)
        'defaults' => [
            'USD_TO_EUR' => env('DEFAULT_USD_TO_EUR_RATE', 0.92),
            'GBP_TO_EUR' => env('DEFAULT_GBP_TO_EUR_RATE', 1.16),
        ],
        // External API configurations
        'ecb' => [
            'enabled' => env('ECB_RATES_ENABLED', true),
            'url' => 'https://api.frankfurter.app/latest',
        ],
        'fixer' => [
            'enabled' => env('FIXER_RATES_ENABLED', false),
            'api_key' => env('FIXER_API_KEY'),
            'url' => 'http://data.fixer.io/api/latest',
        ],
        // How often to update rates (in hours)
        'update_frequency' => env('EXCHANGE_RATE_UPDATE_HOURS', 24),
    ],

    'reporting' => [
        // Automatically generate monthly reports
        'auto_generate_monthly' => env('AUTO_GENERATE_MONTHLY_REPORTS', true),
        // Day of month to generate reports (1-28)
        'generation_day' => env('REPORT_GENERATION_DAY', 1),
        // Automatically finalize reports after X days
        'auto_finalize_after_days' => env('AUTO_FINALIZE_REPORTS_DAYS', 7),
    ],

    'alerts' => [
        'enabled' => env('COST_ALERTS_ENABLED', false),
        'thresholds' => [
            // Alert if daily costs exceed this amount in EUR
            'daily_cost_eur' => env('DAILY_COST_ALERT_EUR', 100),
            // Alert if monthly costs exceed this amount in EUR
            'monthly_cost_eur' => env('MONTHLY_COST_ALERT_EUR', 2000),
            // Alert if profit margin drops below this percentage
            'min_profit_margin' => env('MIN_PROFIT_MARGIN_ALERT', 20),
        ],
        'recipients' => env('COST_ALERT_RECIPIENTS', 'admin@example.com'),
    ],
];