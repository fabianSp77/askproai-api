<?php

return [
    /**
     * Livewire Performance Configuration
     * 
     * These settings help prevent browser overload in the admin panel
     */
    
    // Minimum polling intervals (in seconds)
    'polling' => [
        'min_interval' => 10,  // Minimum 10 seconds between polls
        'default_interval' => 30,  // Default 30 seconds if not specified
        'max_concurrent_requests' => 3,  // Max concurrent Livewire requests
    ],
    
    // Component-specific overrides
    'component_settings' => [
        // Disable polling for these components entirely
        'disable_polling' => [
            'mcp-control-center',
            'retell-ultimate-dashboard-large',
            'ultimate-system-cockpit-v2',
            'ultimate-system-cockpit-v3',
            'ultimate-system-cockpit-v4',
        ],
        
        // Force longer intervals for these components
        'force_intervals' => [
            'live-appointment-board' => 60,  // 1 minute
            'realtime-metrics' => 30,  // 30 seconds
            'ml-training-dashboard' => 60,  // 1 minute
        ],
    ],
    
    // DOM optimization
    'dom' => [
        'max_nodes_warning' => 5000,  // Warn if DOM exceeds this
        'max_nodes_error' => 10000,   // Error if DOM exceeds this
        'lazy_load_threshold' => 50,  // Items before lazy loading kicks in
    ],
    
    // Memory management
    'memory' => [
        'warning_threshold_mb' => 256,  // Warn at 256MB
        'error_threshold_mb' => 512,    // Error at 512MB
        'gc_interval_seconds' => 60,    // Garbage collection interval
    ],
    
    // Network optimization
    'network' => [
        'debounce_ms' => 500,  // Debounce user input
        'batch_requests' => true,  // Batch multiple updates
        'compress_responses' => true,  // Gzip responses
    ],
];