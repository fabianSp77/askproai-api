<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Secure Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the secure unified authentication system
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    */
    'audit_scope_bypasses' => env('AUDIT_SCOPE_BYPASSES', false),
    'audit_retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'login_attempts' => env('LOGIN_RATE_LIMIT', 5),
        'login_window' => env('LOGIN_RATE_WINDOW', 15), // minutes
        '2fa_attempts' => env('2FA_RATE_LIMIT', 5),
        '2fa_window' => env('2FA_RATE_WINDOW', 5), // minutes
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        'max_attempts' => env('LOCKOUT_MAX_ATTEMPTS', 5),
        'duration' => env('LOCKOUT_DURATION', 30), // minutes
        'ip_block_threshold' => env('IP_BLOCK_THRESHOLD', 20), // failed attempts per hour
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        'enforce_for_admins' => env('2FA_ENFORCE_ADMINS', true),
        'window' => env('2FA_WINDOW', 1), // TOTP window in 30-second periods
        'recovery_codes' => env('2FA_RECOVERY_CODES', 8),
        'qr_code_size' => env('2FA_QR_SIZE', 400),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'regenerate_on_login' => env('SESSION_REGENERATE', true),
        'single_device_login' => env('SINGLE_DEVICE_LOGIN', false),
        'timeout_minutes' => env('SESSION_TIMEOUT', 120),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'strict_transport_security' => env('HSTS_ENABLED', true),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'prevent_reuse' => env('PASSWORD_PREVENT_REUSE', 5),
    ],
];