<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures all internal MCP servers available in the system.
    | Each server provides specific capabilities and tools.
    |
    */

    'servers' => [
        'calcom' => [
            'enabled' => true,
            'class' => \App\Services\MCP\CalcomMCPServer::class,
            'description' => 'Calendar and appointment management via Cal.com',
        ],
        
        'retell' => [
            'enabled' => true,
            'class' => \App\Services\MCP\RetellMCPServer::class,
            'description' => 'AI phone call management and transcription',
        ],
        
        'database' => [
            'enabled' => true,
            'class' => \App\Services\MCP\DatabaseMCPServer::class,
            'description' => 'Direct database operations with safety checks',
        ],
        
        'queue' => [
            'enabled' => true,
            'class' => \App\Services\MCP\QueueMCPServer::class,
            'description' => 'Background job and queue management',
        ],
        
        'webhook' => [
            'enabled' => true,
            'class' => \App\Services\MCP\WebhookMCPServer::class,
            'description' => 'Webhook processing and event handling',
        ],
        
        'stripe' => [
            'enabled' => true,
            'class' => \App\Services\MCP\StripeMCPServer::class,
            'description' => 'Payment processing and billing management',
        ],
        
        'knowledge' => [
            'enabled' => true,
            'class' => \App\Services\MCP\KnowledgeMCPServer::class,
            'description' => 'Knowledge base and documentation management',
        ],
        
        'appointment' => [
            'enabled' => true,
            'class' => \App\Services\MCP\AppointmentMCPServer::class,
            'description' => 'Advanced appointment scheduling and management',
        ],
        
        'customer' => [
            'enabled' => true,
            'class' => \App\Services\MCP\CustomerMCPServer::class,
            'description' => 'Customer relationship management',
        ],
        
        'company' => [
            'enabled' => true,
            'class' => \App\Services\MCP\CompanyMCPServer::class,
            'description' => 'Multi-tenant company management',
        ],
        
        'branch' => [
            'enabled' => true,
            'class' => \App\Services\MCP\BranchMCPServer::class,
            'description' => 'Branch and location management',
        ],
        
        'github' => [
            'enabled' => true,
            'class' => \App\Services\MCP\GitHubMCPServer::class,
            'description' => 'GitHub repository and issue management',
        ],
        
        'notion' => [
            'enabled' => true,
            'class' => \App\Services\MCP\NotionMCPServer::class,
            'description' => 'Notion workspace and database integration',
        ],
        
        'memory_bank' => [
            'enabled' => true,
            'class' => \App\Services\MCP\MemoryBankMCPServer::class,
            'description' => 'Persistent memory and context storage',
        ],
        
        'figma' => [
            'enabled' => true,
            'class' => \App\Services\MCP\FigmaMCPServer::class,
            'description' => 'Design-to-code conversion from Figma',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    
    'cache_ttl' => 300, // 5 minutes
    'metrics_enabled' => true,
    'health_check_interval' => 60, // seconds
];