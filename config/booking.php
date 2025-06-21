<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Branch Selection Strategy
    |--------------------------------------------------------------------------
    |
    | This option controls the default strategy used to select branches
    | when processing bookings. Available options:
    | - 'nearest': Select branches based on geographic proximity
    | - 'first_available': Select branch with earliest available slot
    | - 'load_balanced': Distribute bookings evenly across branches
    |
    */
    'default_branch_strategy' => env('BOOKING_BRANCH_STRATEGY', 'nearest'),
    
    /*
    |--------------------------------------------------------------------------
    | Booking Time Constraints
    |--------------------------------------------------------------------------
    |
    | Configure various time-related constraints for bookings
    |
    */
    'time_constraints' => [
        // Minimum advance booking time (in minutes)
        'min_advance_booking' => env('BOOKING_MIN_ADVANCE', 60),
        
        // Maximum advance booking time (in days)
        'max_advance_booking' => env('BOOKING_MAX_ADVANCE', 90),
        
        // Default appointment duration (in minutes)
        'default_duration' => env('BOOKING_DEFAULT_DURATION', 30),
        
        // Buffer time between appointments (in minutes)
        'buffer_time' => env('BOOKING_BUFFER_TIME', 15),
        
        // Time slot interval (in minutes)
        'slot_interval' => env('BOOKING_SLOT_INTERVAL', 15),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Availability Settings
    |--------------------------------------------------------------------------
    |
    | Configure how availability is calculated and cached
    |
    */
    'availability' => [
        // Cache TTL for availability data (in seconds)
        'cache_ttl' => env('BOOKING_AVAILABILITY_CACHE_TTL', 300),
        
        // Number of alternative slots to suggest
        'max_alternatives' => env('BOOKING_MAX_ALTERNATIVES', 5),
        
        // Days to search for alternatives
        'alternative_search_days' => env('BOOKING_ALTERNATIVE_DAYS', 7),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Multi-Location Settings
    |--------------------------------------------------------------------------
    |
    | Configure multi-location booking behavior
    |
    */
    'multi_location' => [
        // Enable cross-branch booking
        'enable_cross_branch' => env('BOOKING_CROSS_BRANCH', true),
        
        // Maximum distance for branch suggestions (in km)
        'max_distance_km' => env('BOOKING_MAX_DISTANCE', 50),
        
        // Consider staff who can work at multiple branches
        'enable_mobile_staff' => env('BOOKING_MOBILE_STAFF', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Customer Preferences
    |--------------------------------------------------------------------------
    |
    | Configure how customer preferences are handled
    |
    */
    'customer_preferences' => [
        // Remember customer's preferred branch
        'remember_branch' => env('BOOKING_REMEMBER_BRANCH', true),
        
        // Remember customer's preferred staff
        'remember_staff' => env('BOOKING_REMEMBER_STAFF', true),
        
        // Weight for preference matching (0-1)
        'preference_weight' => env('BOOKING_PREFERENCE_WEIGHT', 0.7),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure booking notification behavior
    |
    */
    'notifications' => [
        // Send confirmation to customer
        'send_customer_confirmation' => env('BOOKING_NOTIFY_CUSTOMER', true),
        
        // Send notification to staff
        'send_staff_notification' => env('BOOKING_NOTIFY_STAFF', true),
        
        // Send notification to branch manager
        'send_manager_notification' => env('BOOKING_NOTIFY_MANAGER', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    |
    | Configure debug and logging behavior
    |
    */
    'debug' => [
        // Enable detailed booking flow logging
        'enable_detailed_logging' => env('BOOKING_DEBUG', false),
        
        // Log availability calculations
        'log_availability' => env('BOOKING_LOG_AVAILABILITY', false),
        
        // Log branch selection process
        'log_branch_selection' => env('BOOKING_LOG_BRANCHES', false),
    ],
];