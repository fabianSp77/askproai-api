<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Security Configuration for AskProAI
    |--------------------------------------------------------------------------
    |
    | This file contains all security-related configuration options for the
    | AskProAI application. These settings help protect against common
    | security vulnerabilities and enforce best practices.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Security Settings
    |--------------------------------------------------------------------------
    */
    
    'api' => [
        // Rate limiting per API key (requests per hour)
        'rate_limit' => env('API_RATE_LIMIT', 100),
        
        // Rate limiting for authentication attempts (per IP)
        'auth_rate_limit' => env('API_AUTH_RATE_LIMIT', 10),
        
        // API key requirements
        'api_key_length' => env('API_KEY_LENGTH', 36),
        'api_key_prefix' => env('API_KEY_PREFIX', 'ask_'),
        
        // API key rotation settings
        'key_rotation_days' => env('API_KEY_ROTATION_DAYS', 90),
        'key_rotation_warning_days' => env('API_KEY_ROTATION_WARNING_DAYS', 7),
        
        // Request size limits
        'max_request_size' => env('MAX_REQUEST_SIZE', '10MB'),
        'max_file_upload_size' => env('MAX_FILE_UPLOAD_SIZE', '5MB'),
        
        // Request timeout settings
        'request_timeout' => env('API_REQUEST_TIMEOUT', 30),
        'webhook_timeout' => env('WEBHOOK_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security Policies
    |--------------------------------------------------------------------------
    */
    
    'passwords' => [
        // Minimum password requirements
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        
        // Password history and rotation
        'prevent_reuse' => env('PASSWORD_PREVENT_REUSE', 5),
        'force_change_days' => env('PASSWORD_FORCE_CHANGE_DAYS', 90),
        'lockout_attempts' => env('PASSWORD_LOCKOUT_ATTEMPTS', 5),
        'lockout_duration' => env('PASSWORD_LOCKOUT_DURATION', 900), // 15 minutes
        
        // Password strength validation regex
        'strength_regex' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security Settings
    |--------------------------------------------------------------------------
    */
    
    'sessions' => [
        // Session timeout settings
        'lifetime_minutes' => env('SESSION_LIFETIME', 120),
        'idle_timeout_minutes' => env('SESSION_IDLE_TIMEOUT', 30),
        
        // Session security flags
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'strict'),
        
        // Session invalidation triggers
        'invalidate_on_ip_change' => env('SESSION_INVALIDATE_IP_CHANGE', true),
        'invalidate_on_user_agent_change' => env('SESSION_INVALIDATE_UA_CHANGE', true),
        
        // Concurrent session limits
        'max_concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    */
    
    'headers' => [
        // Content Security Policy
        'csp_enabled' => env('CSP_ENABLED', true),
        'csp_directives' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            'font-src' => "'self' https://fonts.gstatic.com",
            'img-src' => "'self' data: https:",
            'connect-src' => "'self' https://api.cal.com https://api.retellai.com",
            'frame-src' => "'none'",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
        ],
        
        // HTTP Strict Transport Security
        'hsts_enabled' => env('HSTS_ENABLED', true),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
        'hsts_include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        
        // Other security headers
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation & Sanitization
    |--------------------------------------------------------------------------
    */
    
    'validation' => [
        // Global input size limits
        'max_input_vars' => env('MAX_INPUT_VARS', 1000),
        'max_input_nesting_level' => env('MAX_INPUT_NESTING_LEVEL', 5),
        
        // File upload validation
        'allowed_file_types' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'txt'],
            'audio' => ['mp3', 'wav', 'ogg'],
        ],
        
        'forbidden_file_types' => [
            'php', 'js', 'html', 'exe', 'sh', 'bat', 'cmd'
        ],
        
        // URL validation patterns
        'allowed_protocols' => ['http', 'https'],
        'blocked_domains' => [],
        
        // XSS prevention
        'auto_escape_output' => env('AUTO_ESCAPE_OUTPUT', true),
        'strip_dangerous_tags' => env('STRIP_DANGEROUS_TAGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Monitoring Security
    |--------------------------------------------------------------------------
    */
    
    'logging' => [
        // Security event logging
        'log_failed_logins' => env('LOG_FAILED_LOGINS', true),
        'log_api_key_usage' => env('LOG_API_KEY_USAGE', true),
        'log_suspicious_requests' => env('LOG_SUSPICIOUS_REQUESTS', true),
        
        // Log data retention
        'security_log_retention_days' => env('SECURITY_LOG_RETENTION_DAYS', 90),
        'audit_log_retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        
        // Sensitive data filtering
        'filter_sensitive_data' => env('FILTER_SENSITIVE_DATA', true),
        'sensitive_fields' => [
            'password', 'token', 'api_key', 'secret', 'credential',
            'ssn', 'credit_card', 'bank_account'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security Settings
    |--------------------------------------------------------------------------
    */
    
    'database' => [
        // Query monitoring
        'log_slow_queries' => env('DB_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        
        // Connection security
        'use_ssl' => env('DB_USE_SSL', false),
        'verify_ssl_cert' => env('DB_VERIFY_SSL_CERT', true),
        
        // Query limits
        'max_query_time' => env('DB_MAX_QUERY_TIME', 30), // seconds
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Security Settings
    |--------------------------------------------------------------------------
    */
    
    'network' => [
        // IP allowlisting/blocklisting
        'trusted_proxies' => env('TRUSTED_PROXIES', []),
        'blocked_ips' => env('BLOCKED_IPS', []),
        'admin_ip_whitelist' => env('ADMIN_IP_WHITELIST', []),
        
        // Geographic restrictions
        'blocked_countries' => env('BLOCKED_COUNTRIES', []),
        'allowed_countries' => env('ALLOWED_COUNTRIES', []),
        
        // DDoS protection
        'enable_ddos_protection' => env('ENABLE_DDOS_PROTECTION', true),
        'ddos_threshold' => env('DDOS_THRESHOLD', 100), // requests per minute
        'ddos_ban_duration' => env('DDOS_BAN_DURATION', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption & Cryptography
    |--------------------------------------------------------------------------
    */
    
    'encryption' => [
        // Algorithm preferences
        'preferred_cipher' => env('ENCRYPTION_CIPHER', 'AES-256-GCM'),
        'hash_algorithm' => env('HASH_ALGORITHM', 'sha256'),
        
        // Key management
        'rotate_app_key_days' => env('ROTATE_APP_KEY_DAYS', 365),
        'key_derivation_iterations' => env('KEY_DERIVATION_ITERATIONS', 10000),
        
        // Data encryption requirements
        'encrypt_sensitive_db_fields' => env('ENCRYPT_SENSITIVE_DB_FIELDS', true),
        'encrypt_log_files' => env('ENCRYPT_LOG_FILES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring & Alerting
    |--------------------------------------------------------------------------
    */
    
    'monitoring' => [
        // Alert thresholds
        'failed_login_threshold' => env('FAILED_LOGIN_ALERT_THRESHOLD', 10),
        'suspicious_request_threshold' => env('SUSPICIOUS_REQUEST_ALERT_THRESHOLD', 50),
        'api_abuse_threshold' => env('API_ABUSE_ALERT_THRESHOLD', 500),
        
        // Alert channels
        'security_alert_email' => env('SECURITY_ALERT_EMAIL', 'security@your-domain.com'),
        'security_alert_slack' => env('SECURITY_ALERT_SLACK_WEBHOOK'),
        
        // Automated responses
        'auto_ban_suspicious_ips' => env('AUTO_BAN_SUSPICIOUS_IPS', true),
        'auto_revoke_compromised_keys' => env('AUTO_REVOKE_COMPROMISED_KEYS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance & Audit Settings
    |--------------------------------------------------------------------------
    */
    
    'compliance' => [
        // GDPR compliance
        'gdpr_enabled' => env('GDPR_ENABLED', true),
        'data_retention_days' => env('DATA_RETENTION_DAYS', 2555), // 7 years
        'anonymize_old_data' => env('ANONYMIZE_OLD_DATA', true),
        
        // Audit trail requirements
        'audit_all_admin_actions' => env('AUDIT_ALL_ADMIN_ACTIONS', true),
        'audit_data_access' => env('AUDIT_DATA_ACCESS', true),
        'audit_data_changes' => env('AUDIT_DATA_CHANGES', true),
        
        // Backup security
        'encrypt_backups' => env('ENCRYPT_BACKUPS', true),
        'backup_retention_days' => env('BACKUP_RETENTION_DAYS', 90),
    ],

];