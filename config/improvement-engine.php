<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Continuous Improvement Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the continuous improvement system that monitors
    | performance, identifies bottlenecks, and suggests optimizations.
    |
    */

    'monitoring' => [
        'enabled' => env('IMPROVEMENT_ENGINE_ENABLED', true),
        'interval' => env('IMPROVEMENT_MONITORING_INTERVAL', 300), // 5 minutes
        'retention' => env('IMPROVEMENT_DATA_RETENTION', 30), // days
        
        'thresholds' => [
            'response_time' => 1000, // ms
            'error_rate' => 0.01, // 1%
            'cpu_usage' => 80, // %
            'memory_usage' => 85, // %
            'disk_usage' => 90, // %
            'queue_size' => 1000,
            'database_connections' => 80, // % of max
        ],
    ],
    
    'analysis' => [
        'enabled' => true,
        'schedule' => 'hourly', // 'hourly', 'daily', 'weekly'
        'lookback_period' => 7, // days
        'confidence_threshold' => 0.95,
        'minimum_data_points' => 100,
        
        'modules' => [
            'performance' => true,
            'bottlenecks' => true,
            'patterns' => true,
            'predictions' => true,
            'optimizations' => true,
        ],
    ],
    
    'optimization' => [
        'auto_apply' => env('IMPROVEMENT_AUTO_OPTIMIZE', false),
        'test_environment' => env('IMPROVEMENT_TEST_ENV', 'staging'),
        'approval_required' => env('IMPROVEMENT_APPROVAL_REQUIRED', true),
        'rollback_enabled' => true,
        
        'types' => [
            'query_optimization' => true,
            'cache_optimization' => true,
            'index_optimization' => true,
            'configuration_tuning' => true,
            'code_optimization' => false, // Requires manual review
        ],
    ],
    
    'metrics' => [
        'collect' => [
            'performance' => true,
            'resources' => true,
            'business' => true,
            'errors' => true,
            'user_experience' => true,
        ],
        
        'sources' => [
            'application' => true,
            'database' => true,
            'cache' => true,
            'queue' => true,
            'external_apis' => true,
        ],
    ],
    
    'bottleneck_detection' => [
        'database' => [
            'slow_query_threshold' => 1000, // ms
            'lock_wait_threshold' => 50, // ms
            'connection_usage_threshold' => 0.8, // 80%
        ],
        
        'api' => [
            'response_time_threshold' => 2000, // ms
            'timeout_threshold' => 30000, // ms
            'error_rate_threshold' => 0.05, // 5%
        ],
        
        'queue' => [
            'processing_time_threshold' => 5000, // ms
            'backlog_threshold' => 1000, // jobs
            'failure_rate_threshold' => 0.1, // 10%
        ],
    ],
    
    'pattern_detection' => [
        'temporal' => [
            'peak_hours' => true,
            'day_patterns' => true,
            'seasonal_trends' => true,
        ],
        
        'behavioral' => [
            'user_patterns' => true,
            'error_patterns' => true,
            'performance_patterns' => true,
        ],
    ],
    
    'predictions' => [
        'enabled' => true,
        'algorithms' => [
            'linear_regression' => true,
            'time_series' => true,
            'anomaly_detection' => true,
        ],
        
        'predict' => [
            'resource_exhaustion' => true,
            'performance_degradation' => true,
            'scaling_needs' => true,
            'failure_risks' => true,
        ],
    ],
    
    'alerts' => [
        'enabled' => true,
        'channels' => ['log', 'database', 'slack'],
        
        'levels' => [
            'info' => ['log'],
            'warning' => ['log', 'database'],
            'critical' => ['log', 'database', 'slack'],
        ],
        
        'throttle' => [
            'enabled' => true,
            'max_alerts_per_hour' => 10,
        ],
    ],
    
    'recommendations' => [
        'generate' => true,
        'priority_weights' => [
            'performance_impact' => 0.4,
            'implementation_effort' => 0.3,
            'risk_level' => 0.2,
            'cost_benefit' => 0.1,
        ],
        
        'categories' => [
            'performance' => true,
            'scalability' => true,
            'reliability' => true,
            'security' => true,
            'cost_optimization' => true,
        ],
    ],
    
    'benchmarks' => [
        'response_times' => [
            'excellent' => 100, // ms
            'good' => 500,
            'acceptable' => 1000,
            'poor' => 3000,
        ],
        
        'availability' => [
            'target' => 99.9, // %
            'minimum' => 99.0,
        ],
        
        'error_rates' => [
            'excellent' => 0.001, // 0.1%
            'good' => 0.01, // 1%
            'acceptable' => 0.05, // 5%
        ],
    ],
    
    'storage' => [
        'path' => 'improvement-engine',
        'compression' => true,
        'encryption' => false,
    ],
    
    'reporting' => [
        'daily_summary' => true,
        'weekly_report' => true,
        'monthly_analysis' => true,
        'export_formats' => ['json', 'pdf', 'csv'],
    ],
];