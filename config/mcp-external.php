<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external Model Context Protocol servers that enhance
    | Claude's capabilities with specialized tools and integrations.
    |
    */

    'external_servers' => [
        'sequential_thinking' => [
            'enabled' => env('MCP_SEQUENTIAL_THINKING_ENABLED', true),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-sequential-thinking'],
            'timeout' => 30,
            'description' => 'Step-by-step reasoning and problem-solving capabilities',
        ],
        
        'postgres' => [
            'enabled' => env('MCP_POSTGRES_ENABLED', true),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-postgres'],
            'env' => [
                // Note: AskProAI uses MySQL/MariaDB, not PostgreSQL
                // These settings map to MySQL for compatibility
                'POSTGRES_HOST' => env('DB_HOST', '127.0.0.1'),
                'POSTGRES_PORT' => env('DB_PORT', '3306'),
                'POSTGRES_USER' => env('DB_USERNAME'),
                'POSTGRES_PASSWORD' => env('DB_PASSWORD'),
                'POSTGRES_DATABASE' => env('DB_DATABASE'),
            ],
            'timeout' => 15,
            'description' => 'Direct database access and management (MySQL/MariaDB)',
        ],
        
        'effect_docs' => [
            'enabled' => env('MCP_EFFECT_DOCS_ENABLED', false),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-effect-docs'],
            'timeout' => 20,
            'description' => 'Documentation generation and effect tracking',
        ],
        
        'taskmaster_ai' => [
            'enabled' => env('MCP_TASKMASTER_ENABLED', false),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-taskmaster-ai'],
            'timeout' => 30,
            'description' => 'Advanced task management and automation',
        ],
        
        'github' => [
            'enabled' => env('MCP_GITHUB_ENABLED', true),
            'command' => 'node',
            'args' => ['/var/www/api-gateway/mcp-external/github-mcp/index.js'],
            'env' => [
                'GITHUB_TOKEN' => env('GITHUB_TOKEN'),
            ],
            'timeout' => 30,
            'description' => 'GitHub API integration for repository management, issues, PRs, and code access',
        ],
        
        'apidog' => [
            'enabled' => env('MCP_APIDOG_ENABLED', true),
            'command' => 'node',
            'args' => ['/var/www/api-gateway/mcp-external/apidog-mcp/index.js'],
            'env' => [
                'APIDOG_API_KEY' => env('APIDOG_API_KEY'),
                'APIDOG_PROJECT_ID' => env('APIDOG_PROJECT_ID'),
            ],
            'timeout' => 30,
            'description' => 'API specification management, code generation, and documentation from Apidog',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Management Settings
    |--------------------------------------------------------------------------
    */
    'management' => [
        'auto_start' => env('MCP_EXTERNAL_AUTO_START', false),
        'health_check_interval' => 60, // seconds
        'restart_on_failure' => true,
        'max_restart_attempts' => 3,
        'log_channel' => 'mcp-external',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'max_concurrent_requests' => 10,
        'request_timeout' => 30, // seconds
        'memory_limit' => '256M',
    ],
];