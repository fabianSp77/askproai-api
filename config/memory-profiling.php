<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Memory Profiling Configuration
    |--------------------------------------------------------------------------
    |
    | Controls for production-safe memory profiling and debugging
    |
    */

    // Enable memory profiling globally
    'enabled' => env('MEMORY_PROFILING_ENABLED', false),

    // Sampling rate (0.01 = 1% of requests)
    'sample_rate' => env('MEMORY_PROFILING_SAMPLE_RATE', 0.01),

    // Memory thresholds in MB
    'thresholds' => [
        'warning' => env('MEMORY_WARNING_THRESHOLD', 1536), // 1.5GB
        'critical' => env('MEMORY_CRITICAL_THRESHOLD', 1792), // 1.75GB
        'dump' => env('MEMORY_DUMP_THRESHOLD', 1900), // 1.9GB
    ],

    // What to profile
    'profile' => [
        'checkpoints' => env('MEMORY_PROFILE_CHECKPOINTS', true),
        'models' => env('MEMORY_PROFILE_MODELS', true),
        'queries' => env('MEMORY_PROFILE_QUERIES', true),
        'global_scopes' => env('MEMORY_PROFILE_SCOPES', true),
        'filament' => env('MEMORY_PROFILE_FILAMENT', true),
        'session' => env('MEMORY_PROFILE_SESSION', true),
    ],

    // Logging configuration
    'logging' => [
        'channel' => env('MEMORY_LOG_CHANNEL', 'stack'),
        'level' => env('MEMORY_LOG_LEVEL', 'warning'),

        // Save detailed dumps to files
        'save_dumps' => env('MEMORY_SAVE_DUMPS', true),
        'dump_path' => storage_path('logs/memory-dumps'),

        // Auto-cleanup old dumps
        'cleanup_days' => env('MEMORY_DUMP_CLEANUP_DAYS', 7),
    ],

    // Safety controls
    'safety' => [
        // Auto-disable profiling if it causes overhead
        'auto_disable_on_overhead' => true,
        'max_overhead_ms' => 50,

        // Circuit breaker: disable after N consecutive failures
        'circuit_breaker_enabled' => true,
        'circuit_breaker_threshold' => 10,

        // Emergency kill switch via cache
        'emergency_disable_cache_key' => 'memory_profiling:emergency_disable',
    ],

    // Advanced options
    'advanced' => [
        // Use tick functions for continuous monitoring (higher overhead)
        'use_tick_monitoring' => env('MEMORY_USE_TICK_MONITORING', false),

        // Track object allocations (requires php-meminfo extension)
        'track_objects' => env('MEMORY_TRACK_OBJECTS', false) && extension_loaded('meminfo'),

        // Generate heap dumps (requires tideways extension)
        'heap_dumps' => env('MEMORY_HEAP_DUMPS', false) && extension_loaded('tideways_xhprof'),
    ],
];
