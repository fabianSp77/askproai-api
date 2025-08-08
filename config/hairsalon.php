<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hair Salon MCP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Hair Salon MCP (Model Context Protocol) integration
    | with Retell.ai for appointment booking and customer service automation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Pricing structure for hair salon MCP services
    |
    */
    'billing' => [
        'cost_per_minute' => env('HAIR_SALON_COST_PER_MINUTE', 0.30),
        'setup_fee' => env('HAIR_SALON_SETUP_FEE', 199.00),
        'monthly_fee' => env('HAIR_SALON_MONTHLY_FEE', 49.00),
        'currency' => env('HAIR_SALON_CURRENCY', 'EUR'),
        
        // Reseller margins
        'reseller' => [
            'call_margin_per_minute' => env('HAIR_SALON_RESELLER_CALL_MARGIN', 0.05),
            'setup_fee_share' => env('HAIR_SALON_RESELLER_SETUP_SHARE', 50.00),
            'monthly_fee_share' => env('HAIR_SALON_RESELLER_MONTHLY_SHARE', 10.00),
        ],
        
        // Billing cycle
        'billing_cycle' => 'monthly', // monthly, weekly, daily
        'payment_terms_days' => 14,
        'late_fee_percentage' => 2.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Calendar Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Calendar API integration
    |
    */
    'google_calendar' => [
        'enabled' => env('HAIR_SALON_GOOGLE_CALENDAR_ENABLED', true),
        'api_key' => env('GOOGLE_CALENDAR_API_KEY'),
        'service_account_token' => env('GOOGLE_SERVICE_ACCOUNT_TOKEN'),
        'cache_minutes' => env('HAIR_SALON_CALENDAR_CACHE_MINUTES', 15),
        
        // Default staff calendars (from requirements)
        'staff_calendars' => [
            'paula' => '8356d9e1f6480e139b45d109b4ccfd9d293bfe3b0a72d6f626dbfd6c03142a6a@group.calendar.google.com',
            'claudia' => 'e8b310b5dbdb5e001f813080a21030d7e16447c155420d21f9bb91340af2724b@group.calendar.google.com',
            'katrin' => '46ff314dc0442572c6167f980f41731efe6e95845ba58866ab37b6e8c132bd30@group.calendar.google.com',
        ],
        
        // Calendar settings
        'timezone' => 'Europe/Berlin',
        'reminder_settings' => [
            'email_24h' => true,
            'popup_30min' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours & Scheduling
    |--------------------------------------------------------------------------
    |
    | Default business hours and scheduling configuration
    |
    */
    'business_hours' => [
        'monday' => ['09:00', '18:00'],
        'tuesday' => ['09:00', '18:00'],
        'wednesday' => ['09:00', '18:00'],
        'thursday' => ['09:00', '20:00'],
        'friday' => ['09:00', '20:00'],
        'saturday' => ['09:00', '16:00'],
        'sunday' => 'closed',
    ],

    'scheduling' => [
        'advance_booking_days' => env('HAIR_SALON_ADVANCE_BOOKING_DAYS', 30),
        'minimum_advance_hours' => env('HAIR_SALON_MIN_ADVANCE_HOURS', 2),
        'buffer_minutes' => env('HAIR_SALON_BUFFER_MINUTES', 15),
        'slot_interval_minutes' => env('HAIR_SALON_SLOT_INTERVAL', 30),
        'max_slots_per_request' => env('HAIR_SALON_MAX_SLOTS', 20),
        
        // Lunch breaks
        'lunch_break' => [
            'enabled' => true,
            'start' => '12:30',
            'end' => '13:30',
        ],
        
        // Special handling
        'consultation_callback_hours' => 2, // Hours to wait before callback
        'cancellation_policy_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Categories & Types
    |--------------------------------------------------------------------------
    |
    | Configuration for different service types and their requirements
    |
    */
    'services' => [
        // Services requiring consultation before booking
        'consultation_required' => [
            'Klassisches Strähnen-Paket',
            'Globale Blondierung',
            'Stähnentechnik Balayage',
            'Faceframe',
        ],
        
        // Services with complex time blocks (work + break periods)
        'multi_block_services' => [
            'Ansatzfärbung + Waschen, schneiden, föhnen' => [
                'total_duration' => 120,
                'blocks' => [
                    [
                        'duration' => 30,
                        'type' => 'work',
                        'description' => 'Färbung auftragen'
                    ],
                    [
                        'duration' => 30,
                        'type' => 'break',
                        'description' => 'Einwirkzeit'
                    ],
                    [
                        'duration' => 60,
                        'type' => 'work',
                        'description' => 'Waschen, schneiden, föhnen'
                    ],
                ],
            ],
        ],
        
        // Default service categories
        'categories' => [
            'herren' => 'Herrenschnitte',
            'damen' => 'Damenschnitte',
            'kinder' => 'Kinderschnitte',
            'faerbung' => 'Färbung & Strähnchen',
            'styling' => 'Styling & Hochsteckfrisuren',
            'pflege' => 'Haarpflege',
            'beratung' => 'Beratung',
            'zusatz' => 'Zusatzleistungen',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retell.ai Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Retell.ai voice AI integration
    |
    */
    'retell' => [
        'enabled' => env('HAIR_SALON_RETELL_ENABLED', true),
        'agent_id' => env('HAIR_SALON_RETELL_AGENT_ID', 'hair_salon_agent'),
        'webhook_secret' => env('HAIR_SALON_RETELL_WEBHOOK_SECRET'),
        
        // Voice settings
        'voice_settings' => [
            'language' => 'de-DE',
            'voice_id' => 'german_female_professional',
            'speed' => 1.0,
            'pitch' => 0.0,
        ],
        
        // Conversation settings
        'conversation' => [
            'max_duration_seconds' => 600, // 10 minutes
            'silence_timeout_seconds' => 30,
            'greeting_message' => 'Hallo! Hier ist Hair & Style Salon. Wie kann ich Ihnen helfen?',
            'goodbye_message' => 'Vielen Dank für Ihren Anruf! Wir freuen uns auf Ihren Besuch.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the MCP server endpoints and behavior
    |
    */
    'mcp' => [
        'enabled' => env('HAIR_SALON_MCP_ENABLED', true),
        'version' => '1.0.0',
        'timeout_seconds' => env('HAIR_SALON_MCP_TIMEOUT', 30),
        
        // Rate limiting
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        
        // Caching
        'caching' => [
            'availability_cache_minutes' => 5,
            'services_cache_minutes' => 30,
            'staff_cache_minutes' => 60,
        ],
        
        // Monitoring
        'monitoring' => [
            'enabled' => true,
            'log_all_requests' => env('HAIR_SALON_MCP_LOG_REQUESTS', false),
            'track_response_times' => true,
            'alert_on_errors' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Management
    |--------------------------------------------------------------------------
    |
    | Configuration for customer data handling and privacy
    |
    */
    'customers' => [
        // Data retention
        'data_retention_months' => 24,
        'inactive_customer_months' => 12,
        
        // Privacy settings
        'require_consent' => true,
        'anonymize_after_months' => 36,
        
        // Communication preferences
        'default_communication' => 'phone',
        'sms_notifications' => env('HAIR_SALON_SMS_ENABLED', false),
        'email_notifications' => env('HAIR_SALON_EMAIL_ENABLED', true),
        
        // Customer sources
        'sources' => [
            'phone' => 'Telefon',
            'walk_in' => 'Laufkundschaft',
            'referral' => 'Empfehlung',
            'online' => 'Online-Buchung',
            'social_media' => 'Social Media',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications & Alerts
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications and alerts
    |
    */
    'notifications' => [
        'appointment_confirmations' => true,
        'appointment_reminders' => true,
        'cancellation_notifications' => true,
        'callback_requests' => true,
        
        // Reminder timing
        'reminder_hours_before' => 24,
        'followup_hours_after' => 2,
        
        // Staff notifications
        'staff_new_appointment' => true,
        'staff_cancellation' => true,
        'staff_callback_request' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting & Analytics
    |--------------------------------------------------------------------------
    |
    | Configuration for reporting and analytics
    |
    */
    'analytics' => [
        'enabled' => true,
        'track_call_outcomes' => true,
        'track_booking_conversion' => true,
        'track_service_popularity' => true,
        'track_staff_utilization' => true,
        
        // Reporting intervals
        'daily_reports' => true,
        'weekly_reports' => true,
        'monthly_reports' => true,
        
        // KPIs to track
        'kpis' => [
            'call_to_booking_conversion',
            'average_booking_value',
            'customer_satisfaction',
            'staff_efficiency',
            'revenue_per_call',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling & Fallbacks
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and fallback behavior
    |
    */
    'error_handling' => [
        'max_retries' => 3,
        'retry_delay_seconds' => 2,
        'fallback_to_manual_booking' => true,
        'emergency_contact' => env('HAIR_SALON_EMERGENCY_CONTACT'),
        
        // Fallback business hours (when calendar unavailable)
        'fallback_business_hours' => [
            'monday' => '09:00-18:00',
            'tuesday' => '09:00-18:00',
            'wednesday' => '09:00-18:00',
            'thursday' => '09:00-20:00',
            'friday' => '09:00-20:00',
            'saturday' => '09:00-16:00',
            'sunday' => 'closed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for development and testing environments
    |
    */
    'development' => [
        'demo_mode' => env('HAIR_SALON_DEMO_MODE', false),
        'fake_calendar_responses' => env('HAIR_SALON_FAKE_CALENDAR', false),
        'mock_payment_processing' => env('HAIR_SALON_MOCK_PAYMENTS', false),
        'test_customer_phone' => '+49 40 99999999',
        
        // Seed data
        'create_demo_data' => env('HAIR_SALON_CREATE_DEMO_DATA', true),
        'demo_appointments_count' => 20,
        'demo_customers_count' => 10,
    ],

];