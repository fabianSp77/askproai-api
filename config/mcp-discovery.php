<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the MCP Discovery & Evolution System that automatically
    | discovers new MCPs from various sources and evaluates their relevance
    | for the AskProAI platform.
    |
    */

    'sources' => [
        'anthropic' => [
            'enabled' => true,
            'url' => env('MCP_ANTHROPIC_REGISTRY_URL', 'https://api.anthropic.com/mcp/registry'),
            'check_interval' => 3600, // 1 hour
            'api_key' => env('ANTHROPIC_API_KEY'),
        ],
        
        'github' => [
            'enabled' => true,
            'org' => 'anthropics',
            'topic' => 'mcp',
            'check_interval' => 7200, // 2 hours
            'token' => env('GITHUB_TOKEN'),
        ],
        
        'npm' => [
            'enabled' => true,
            'scope' => '@anthropic',
            'keyword' => 'mcp',
            'check_interval' => 7200, // 2 hours
        ],
        
        'community' => [
            'enabled' => true,
            'sources' => [
                'https://awesome-mcp.com/api/list',
                'https://mcp-directory.dev/api/packages',
            ],
            'check_interval' => 14400, // 4 hours
        ],
    ],
    
    'evaluation' => [
        'auto_install' => env('MCP_AUTO_INSTALL', false),
        'test_environment' => env('MCP_TEST_ENVIRONMENT', 'staging'),
        'approval_required' => env('MCP_APPROVAL_REQUIRED', true),
        'relevance_threshold' => 0.3,
    ],
    
    'categories' => [
        // Categories relevant to AskProAI
        'priority' => [
            'calendar',
            'scheduling',
            'appointment',
            'booking',
            'telephony',
            'voice',
            'ai',
            'conversation',
        ],
        
        'secondary' => [
            'crm',
            'customer',
            'business',
            'automation',
            'monitoring',
            'analytics',
            'performance',
        ],
        
        'technical' => [
            'database',
            'api',
            'integration',
            'laravel',
            'php',
        ],
    ],
    
    'keywords' => [
        // Keywords to match in MCP descriptions
        'high_priority' => [
            'laravel',
            'filament',
            'calcom',
            'retell',
            'appointment',
            'booking',
            'german',
            'gdpr',
        ],
        
        'medium_priority' => [
            'php',
            'mysql',
            'webhook',
            'multi-tenant',
            'saas',
            'deployment',
            'monitoring',
        ],
    ],
    
    'storage' => [
        'catalog_path' => 'mcp/catalog.json',
        'cache_ttl' => 86400, // 24 hours
        'backup_enabled' => true,
    ],
    
    'notifications' => [
        'channels' => ['log', 'database'],
        'min_relevance_for_notification' => 0.7,
        'slack_webhook' => env('MCP_SLACK_WEBHOOK'),
        'email_recipients' => env('MCP_EMAIL_RECIPIENTS'),
    ],
];