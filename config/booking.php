<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the intelligent appointment booking system with
    | alternatives and nested bookings for special services.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Alternative Search Configuration
    |--------------------------------------------------------------------------
    */

    'max_alternatives' => env('BOOKING_MAX_ALTERNATIVES', 2),

    'time_window_hours' => env('BOOKING_TIME_WINDOW_HOURS', 2),

    'search_strategies' => [
        'same_day_different_time',
        'next_workday_same_time',
        'next_week_same_day',
        'next_available_workday'
    ],

    'workdays' => [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday'
    ],

    'business_hours' => [
        'start' => env('BOOKING_HOURS_START', '09:00'),
        'end' => env('BOOKING_HOURS_END', '18:00')
    ],

    /*
    |--------------------------------------------------------------------------
    | Nested Booking Configuration (Hairdresser Services)
    |--------------------------------------------------------------------------
    */

    'enable_nested_bookings' => env('BOOKING_ENABLE_NESTED', true),

    'nested_services' => [
        'coloring' => [
            'main_duration' => 120,  // 2 hours total
            'active_work' => 45,     // 45 min application
            'break_start' => 45,     // Break starts after 45 min
            'break_duration' => 45,  // 45 min processing time
            'finish_work' => 30      // 30 min washing/styling
        ],
        'perm' => [
            'main_duration' => 150,  // 2.5 hours total
            'active_work' => 60,
            'break_start' => 60,
            'break_duration' => 60,
            'finish_work' => 30
        ],
        'highlights' => [
            'main_duration' => 180,  // 3 hours total
            'active_work' => 90,
            'break_start' => 90,
            'break_duration' => 60,
            'finish_work' => 30
        ]
    ],

    'break_compatible_services' => [
        'haircut' => 30,         // 30 minutes
        'beard_trim' => 15,      // 15 minutes
        'quick_styling' => 20,   // 20 minutes
        'consultation' => 15,    // 15 minutes
        'wash_and_dry' => 25     // 25 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cal.com Integration
    |--------------------------------------------------------------------------
    */

    'calcom' => [
        'api_key' => env('CAL_COM_API_KEY'),
        'base_url' => env('CAL_COM_BASE_URL', 'https://api.cal.com/v2'),
        'event_type_id' => env('CAL_COM_EVENT_TYPE_ID', 1412903),
        'timezone' => env('CAL_COM_TIMEZONE', 'Europe/Berlin'),
        'default_duration' => 30, // minutes
        'minimum_notice' => 60,   // minutes before appointment
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Matching Configuration
    |--------------------------------------------------------------------------
    |
    | Confidence thresholds for automatic Cal.com host to staff matching.
    | auto_threshold: Minimum confidence required for automatic mapping (0-100)
    | email_confidence: Confidence score for exact email matches
    | name_confidence: Confidence score for name-based matches
    */

    'staff_matching' => [
        'auto_threshold' => env('BOOKING_STAFF_AUTO_THRESHOLD', 75),
        'email_confidence' => env('BOOKING_STAFF_EMAIL_CONFIDENCE', 95),
        'name_confidence' => env('BOOKING_STAFF_NAME_CONFIDENCE', 75),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'send_alternative_notifications' => env('BOOKING_NOTIFY_ALTERNATIVES', true),
        'send_nested_slot_reminders' => env('BOOKING_NOTIFY_NESTED', true),
        'channels' => ['sms', 'email'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    */

    'auto_suggest_alternatives' => env('BOOKING_AUTO_SUGGEST', true),

    'confidence_threshold' => env('BOOKING_CONFIDENCE_MIN', 60),

    'cache_ttl' => 300, // 5 minutes cache for availability checks

    'max_search_days' => 14, // Maximum days to search ahead for alternatives

    'prefer_same_staff' => true, // Try to book with same staff member for alternatives

    'allow_overbooking' => false, // Emergency override for VIP customers
];