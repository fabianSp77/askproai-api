<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integration Services Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures various third-party integration services
    | used throughout the application.
    |
    */

    'github_notion' => [
        'enabled' => env('GITHUB_NOTION_SYNC_ENABLED', true),
        'auto_sync' => env('GITHUB_NOTION_AUTO_SYNC', false),
        
        // Default sync intervals (in minutes)
        'sync_intervals' => [
            'issues' => 15,
            'pull_requests' => 30,
            'releases' => 1440, // 24 hours
        ],
        
        // Batch processing
        'batch_size' => env('GITHUB_NOTION_BATCH_SIZE', 50),
        
        // Cache TTL for sync data (in seconds)
        'cache_ttl' => 86400, // 24 hours
        
        // Default field mappings
        'field_mappings' => [
            'issue_status' => [
                'open' => 'To Do',
                'closed' => 'Done',
            ],
            'pr_status' => [
                'open' => 'In Review',
                'draft' => 'Draft',
                'merged' => 'Merged',
                'closed' => 'Closed',
            ],
            'priority_labels' => [
                'critical' => 'High',
                'urgent' => 'High',
                'bug' => 'Medium',
                'important' => 'Medium',
                'enhancement' => 'Low',
                'documentation' => 'Low',
            ],
        ],
    ],
    
    'notion' => [
        'api_key' => env('NOTION_API_KEY'),
        'version' => '2022-06-28',
        'base_url' => 'https://api.notion.com/v1',
    ],
    
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
        'api_version' => '2022-11-28',
        'base_url' => 'https://api.github.com',
    ],
];