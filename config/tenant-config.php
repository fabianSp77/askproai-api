<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-tenant functionality including isolation,
    | security, performance, and auditing settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Tenant Scoping
    |--------------------------------------------------------------------------
    |
    | Control how tenant scoping is applied throughout the application.
    |
    */
    'scoping' => [
        'enabled' => env('TENANT_SCOPING_ENABLED', true),
        'strict_mode' => env('TENANT_STRICT_MODE', true),
        'company_column' => 'company_id',
        'default_isolation' => 'strict', // strict, lenient, disabled
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Job Classes
    |--------------------------------------------------------------------------
    |
    | Job classes that are allowed to have tenant context in background
    | processing. Only these classes can receive trusted tenant context.
    |
    */
    'trusted_job_classes' => [
        'App\Jobs\ProcessRetellCallJob',
        'App\Jobs\ProcessRetellWebhookJob',
        'App\Jobs\ProcessRetellCallEndedJob',
        'App\Jobs\RefreshCallDataJob',
        'App\Jobs\SyncCalcomEventTypesJob',
        'App\Jobs\ProcessAppointmentBookingJob',
        'App\Jobs\SendAppointmentReminderJob',
        'App\Jobs\SendCallSummaryEmailJob',
        'App\Jobs\ProcessCalcomWebhookJob',
        'App\Jobs\ProcessStripeWebhookJob',
        'App\Jobs\SendAppointmentEmailJob',
        'App\Jobs\ProcessWebhookJob',
        'App\Jobs\FetchRetellCallsJob',
        'App\Jobs\SyncCalcomBookingsJob',
        'App\Jobs\ProcessGdprExportJob',
        
        // Console commands
        'App\Console\Commands\MonitorRetellIntegration',
        'App\Console\Commands\TestRetellIntegration',
        'App\Console\Commands\SyncEventTypes',
        'App\Console\Commands\CleanupStaleData',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Tenant Operations
    |--------------------------------------------------------------------------
    |
    | Configuration for legitimate cross-tenant operations.
    |
    */
    'cross_tenant' => [
        'enabled' => env('TENANT_CROSS_TENANT_ENABLED', true),
        'allowed_reasons' => [
            'system_maintenance',
            'data_migration',
            'super_admin_operation',
            'webhook_processing',
            'cross_company_integration',
            'billing_aggregation',
            'platform_analytics',
            'security_audit',
        ],
        'require_super_admin' => env('TENANT_CROSS_TENANT_REQUIRE_SUPER_ADMIN', true),
        'audit_all' => env('TENANT_CROSS_TENANT_AUDIT_ALL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for tenant isolation and monitoring.
    |
    */
    'security' => [
        'block_untrusted_sources' => env('TENANT_BLOCK_UNTRUSTED_SOURCES', true),
        'log_security_events' => env('TENANT_LOG_SECURITY_EVENTS', true),
        'alert_on_violations' => env('TENANT_ALERT_ON_VIOLATIONS', true),
        'max_violations_per_hour' => env('TENANT_MAX_VIOLATIONS_PER_HOUR', 10),
        
        'monitored_headers' => [
            'X-Company-Id',
            'X-Tenant-Id',
            'X-Organization-Id',
        ],
        
        'monitored_parameters' => [
            'company_id',
            'tenant_id',
            'organization_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings to optimize tenant-related operations.
    |
    */
    'performance' => [
        'cache_tenant_context' => env('TENANT_CACHE_CONTEXT', true),
        'cache_duration' => env('TENANT_CACHE_DURATION', 300), // 5 minutes
        'lazy_load_relationships' => env('TENANT_LAZY_LOAD_RELATIONSHIPS', true),
        'query_optimization' => env('TENANT_QUERY_OPTIMIZATION', true),
        'batch_operations' => env('TENANT_BATCH_OPERATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for tenant operation auditing and logging.
    |
    */
    'audit' => [
        'enabled' => env('TENANT_AUDIT_ENABLED', true),
        'log_channel' => env('TENANT_AUDIT_LOG_CHANNEL', 'security'),
        'detailed_traces' => env('TENANT_AUDIT_DETAILED_TRACES', false),
        'retention_days' => env('TENANT_AUDIT_RETENTION_DAYS', 90),
        
        'events' => [
            'context_established' => true,
            'context_switched' => true,
            'cross_tenant_access' => true,
            'security_violations' => true,
            'job_context_propagation' => true,
            'repository_operations' => false, // Can be noisy
            'helper_operations' => false,     // Can be noisy
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for models and tenant scoping behavior.
    |
    */
    'models' => [
        'auto_scope' => env('TENANT_AUTO_SCOPE_MODELS', true),
        'prevent_cross_tenant_relations' => env('TENANT_PREVENT_CROSS_RELATIONS', true),
        'validate_on_save' => env('TENANT_VALIDATE_ON_SAVE', true),
        
        // Models that should never have tenant scoping
        'global_models' => [
            'App\Models\User', // Users can belong to multiple companies
            'App\Models\Company',
            'App\Models\Role',
            'App\Models\Permission',
            'App\Models\SystemLog',
            'App\Models\AuditLog',
        ],
        
        // Models that require strict tenant isolation
        'strict_models' => [
            'App\Models\Customer',
            'App\Models\Appointment',
            'App\Models\Call',
            'App\Models\Invoice',
            'App\Models\Integration',
            'App\Models\Branch',
            'App\Models\Staff',
            'App\Models\Service',
        ],
    ],

];