<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Configuration
    |--------------------------------------------------------------------------
    */

    'slot_duration' => env('BOOKING_SLOT_DURATION', 15), // minutes
    'buffer_time' => env('BOOKING_BUFFER_TIME', 5), // minutes between appointments
    'min_advance_booking' => env('BOOKING_MIN_ADVANCE', 120), // minutes
    'max_advance_booking' => env('BOOKING_MAX_ADVANCE', 90), // days
    
    'business_hours' => [
        'start' => env('BUSINESS_HOURS_START', '08:00'),
        'end' => env('BUSINESS_HOURS_END', '20:00'),
    ],
    
    'notification' => [
        'reminders' => [
            '24h' => env('REMINDER_24H_ENABLED', true),
            '2h' => env('REMINDER_2H_ENABLED', true),
            '30m' => env('REMINDER_30M_ENABLED', true),
        ],
        'channels' => [
            'email' => env('NOTIFICATION_EMAIL_ENABLED', true),
            'sms' => env('NOTIFICATION_SMS_ENABLED', true),
            'push' => env('NOTIFICATION_PUSH_ENABLED', true),
            'whatsapp' => env('NOTIFICATION_WHATSAPP_ENABLED', false),
        ],
        'sms_quiet_hours' => [
            'start' => env('SMS_QUIET_START', '21:00'),
            'end' => env('SMS_QUIET_END', '09:00'),
        ],
        'daily_limits' => [
            'sms' => env('DAILY_SMS_LIMIT', 3),
            'email' => env('DAILY_EMAIL_LIMIT', 10),
        ],
    ],
    
    'capacity' => [
        'slots_per_day' => env('CAPACITY_SLOTS_PER_DAY', 8),
        'max_concurrent' => env('CAPACITY_MAX_CONCURRENT', 1),
    ],
    
    'rate_limits' => [
        'event_types' => [
            'attempts' => env('RATE_LIMIT_EVENT_TYPES', 60),
            'decay' => env('RATE_LIMIT_EVENT_TYPES_DECAY', 60), // seconds
        ],
        'availability' => [
            'attempts' => env('RATE_LIMIT_AVAILABILITY', 30),
            'decay' => env('RATE_LIMIT_AVAILABILITY_DECAY', 60),
        ],
        'booking' => [
            'attempts' => env('RATE_LIMIT_BOOKING', 5),
            'decay' => env('RATE_LIMIT_BOOKING_DECAY', 300),
        ],
    ],
    
    'cache' => [
        'availability_ttl' => env('CACHE_AVAILABILITY_TTL', 300), // seconds
        'event_types_ttl' => env('CACHE_EVENT_TYPES_TTL', 3600),
        'working_hours_ttl' => env('CACHE_WORKING_HOURS_TTL', 86400),
    ],
];