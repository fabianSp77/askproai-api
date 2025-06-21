<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safe Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the safe deployment system that ensures zero-downtime
    | deployments with automatic rollback capabilities.
    |
    */

    'checks' => [
        'database' => env('DEPLOYMENT_CHECK_DATABASE', true),
        'migrations' => env('DEPLOYMENT_CHECK_MIGRATIONS', true),
        'tests' => env('DEPLOYMENT_CHECK_TESTS', true),
        'dependencies' => env('DEPLOYMENT_CHECK_DEPENDENCIES', true),
        'disk_space' => env('DEPLOYMENT_CHECK_DISK_SPACE', true),
        'services' => env('DEPLOYMENT_CHECK_SERVICES', true),
    ],
    
    'rollback' => [
        'auto_rollback' => env('DEPLOYMENT_AUTO_ROLLBACK', true),
        'max_downtime' => env('DEPLOYMENT_MAX_DOWNTIME', 300), // 5 minutes
        'backup_retention' => env('DEPLOYMENT_BACKUP_RETENTION', 7), // days
        'snapshot_before_deploy' => true,
    ],
    
    'notifications' => [
        'enabled' => env('DEPLOYMENT_NOTIFICATIONS_ENABLED', true),
        'channels' => ['log', 'slack', 'email'],
        'slack' => env('DEPLOYMENT_SLACK_WEBHOOK'),
        'email' => env('DEPLOYMENT_EMAIL'),
    ],
    
    'zero_downtime' => [
        'enabled' => env('DEPLOYMENT_ZERO_DOWNTIME', true),
        'strategy' => env('DEPLOYMENT_STRATEGY', 'blue_green'), // 'blue_green' or 'rolling'
        'health_check_url' => '/health',
        'warm_up_time' => 30, // seconds
        'parallel_instances' => 2,
    ],
    
    'testing' => [
        'run_unit_tests' => true,
        'run_integration_tests' => true,
        'run_e2e_tests' => false, // Can be slow
        'fail_on_test_failure' => true,
        'parallel_testing' => true,
    ],
    
    'pre_deployment' => [
        'backup_database' => true,
        'backup_code' => true,
        'backup_config' => true,
        'backup_storage' => false, // Can be large
        'verify_disk_space' => true,
        'min_free_space_gb' => 2,
    ],
    
    'post_deployment' => [
        'clear_caches' => true,
        'restart_queues' => true,
        'warm_up_cache' => true,
        'run_health_checks' => true,
        'monitor_duration' => 60, // seconds
    ],
    
    'services_check' => [
        'mysql' => [
            'enabled' => true,
            'timeout' => 5,
        ],
        'redis' => [
            'enabled' => true,
            'timeout' => 3,
        ],
        'calcom' => [
            'enabled' => true,
            'url' => env('CALCOM_BASE_URL') . '/api/health',
            'timeout' => 10,
        ],
        'retell' => [
            'enabled' => true,
            'url' => 'https://api.retellai.com/health',
            'timeout' => 10,
        ],
    ],
    
    'critical_paths' => [
        // Paths that must work after deployment
        '/admin/login',
        '/admin',
        '/api/appointments',
        '/api/retell/webhook',
        '/api/calcom/webhook',
        '/health',
    ],
    
    'performance_monitoring' => [
        'enabled' => true,
        'response_time_threshold' => 3000, // ms
        'error_rate_threshold' => 0.05, // 5%
        'check_interval' => 5, // seconds
    ],
    
    'git' => [
        'auto_pull' => true,
        'branch' => env('DEPLOYMENT_GIT_BRANCH', 'main'),
        'remote' => env('DEPLOYMENT_GIT_REMOTE', 'origin'),
    ],
    
    'composer' => [
        'install' => true,
        'optimize' => true,
        'no_dev' => env('APP_ENV') === 'production',
    ],
    
    'npm' => [
        'install' => true,
        'build' => true,
        'production' => env('APP_ENV') === 'production',
    ],
    
    'logging' => [
        'channel' => 'deployment',
        'level' => 'info',
        'separate_file' => true,
    ],
];