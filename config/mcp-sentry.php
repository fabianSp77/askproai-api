<?php

return [
    /**
     * MCP Server Configuration for Sentry Integration
     * This enables Claude Code to directly access Sentry error data
     */
    
    'enabled' => env('MCP_SENTRY_ENABLED', true),
    
    'server' => [
        'name' => 'sentry-mcp-server',
        'version' => '1.0.0',
        'description' => 'MCP server for Sentry error tracking integration',
    ],
    
    'sentry' => [
        'organization' => env('SENTRY_ORGANIZATION', 'askproai'),
        'project' => env('SENTRY_PROJECT', 'api-gateway'),
        'auth_token' => env('SENTRY_AUTH_TOKEN'),
        'api_url' => env('SENTRY_API_URL', 'https://sentry.io/api/0/'),
    ],
    
    'capabilities' => [
        // List recent errors
        'list_issues' => true,
        
        // Get issue details
        'get_issue' => true,
        
        // Get error stack traces
        'get_stacktrace' => true,
        
        // Get error context and breadcrumbs
        'get_context' => true,
        
        // Search issues
        'search_issues' => true,
        
        // Get performance data
        'get_performance' => true,
        
        // Resolve/ignore issues
        'manage_issues' => false, // Set to true if you want Claude to manage issues
    ],
    
    'filters' => [
        // Only show errors from last N days
        'days_back' => env('MCP_SENTRY_DAYS_BACK', 7),
        
        // Minimum error level to show
        'min_level' => env('MCP_SENTRY_MIN_LEVEL', 'warning'),
        
        // Exclude certain error types
        'exclude_types' => [
            'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException',
        ],
    ],
    
    'cache' => [
        // Cache issue list for N seconds
        'ttl' => env('MCP_SENTRY_CACHE_TTL', 300),
        
        // Cache prefix
        'prefix' => 'mcp_sentry',
    ],
];