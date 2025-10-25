<?php

return [
    /* Basis-URL der REST-API  */
    'base_url'  => env('CALCOM_BASE', 'https://api.cal.com'),

    /* Dein Secret-Key  */
    'api_key'   => env('CALCOM_API_KEY'),

    /* EINE der beiden Varianten setzen                           *
     * Mit deinem Test klappt → teamUsername (= Workspace-Slug)   */
    'team_slug' => env('CALCOM_TEAM_SLUG'),   // z. B. askproai
    'user_slug' => env('CALCOM_USER_SLUG'),   // leer lassen

    /*
    |--------------------------------------------------------------------------
    | Minimum Booking Notice (Bug #11 Fix - 2025-10-25)
    |--------------------------------------------------------------------------
    |
    | Minimum number of minutes in advance that bookings must be made.
    | This prevents users from booking appointments too close to the start time.
    |
    | Cal.com enforces this at the API level, but we validate upfront to provide
    | better error messages with alternative times.
    |
    | Default: 15 minutes
    | Override per service: services.minimum_booking_notice
    | Override per branch: branch_service.branch_policies.booking_notice_minutes
    |
    */
    'minimum_booking_notice_minutes' => env('CALCOM_MIN_BOOKING_NOTICE', 15),
];
