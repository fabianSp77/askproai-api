<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Memory Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | These settings help prevent memory exhaustion during application bootstrap
    | by controlling service registration and lazy loading behavior.
    |
    */

    'emergency_mode' => env('EMERGENCY_MODE', false), // Disabled after optimization
    
    'bootstrap' => [
        // Disable memory-intensive operations during bootstrap
        'disable_mcp_warmup' => true,
        'disable_prometheus_init' => true,
        'disable_company_context_bootstrap' => true,
        'lazy_load_services' => true,
    ],
    
    'filament' => [
        // Limit Filament resource discovery
        'disable_auto_discovery' => true,
        'essential_resources_only' => true,
        'max_resources' => 10, // Limit to prevent memory exhaustion
    ],
    
    'services' => [
        // Services to lazy-load instead of registering as singletons
        'lazy_load' => [
            'mcp_servers',
            'prometheus_registry',
            'monitoring_services',
            'external_integrations',
        ],
    ],
    
    'memory_limits' => [
        'admin_panel' => '2048M',
        'api_requests' => '512M', 
        'console_commands' => '1024M',
        'queue_workers' => '256M',
    ],
];