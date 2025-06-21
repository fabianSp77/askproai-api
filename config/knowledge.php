<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Knowledge Base Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the knowledge base system including paths to watch,
    | indexing settings, and display options.
    |
    */

    // Paths to watch for documentation files
    'watch_paths' => [
        base_path('*.md'),
        base_path('docs'),
        base_path('resources/docs'),
    ],

    // File extensions to index
    'file_extensions' => [
        'md',
        'markdown',
    ],

    // Auto-indexing settings
    'auto_index' => [
        'enabled' => env('KNOWLEDGE_AUTO_INDEX', true),
        'interval' => 60, // seconds
    ],

    // Search settings
    'search' => [
        'min_query_length' => 2,
        'results_per_page' => 20,
        'excerpt_length' => 300,
    ],

    // Cache settings
    'cache' => [
        'ttl' => 300, // seconds (5 minutes)
        'prefix' => 'knowledge_',
    ],

    // UI settings
    'ui' => [
        'show_breadcrumbs' => true,
        'show_toc' => true,
        'show_related' => true,
        'show_feedback' => true,
        'show_reading_time' => true,
        'show_view_count' => true,
    ],

    // Markdown parser settings
    'markdown' => [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 10,
        'extensions' => [
            'table' => true,
            'task_list' => true,
            'github_flavored' => true,
            'attributes' => true,
        ],
    ],

    // Categories that should be created by default
    'default_categories' => [
        [
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'icon' => '🚀',
            'description' => 'Learn the basics and get up and running quickly',
            'order' => 1,
        ],
        [
            'name' => 'User Guide',
            'slug' => 'user-guide',
            'icon' => '📖',
            'description' => 'Comprehensive guides for using the platform',
            'order' => 2,
        ],
        [
            'name' => 'API Reference',
            'slug' => 'api-reference',
            'icon' => '🔌',
            'description' => 'Technical documentation for developers',
            'order' => 3,
        ],
        [
            'name' => 'Troubleshooting',
            'slug' => 'troubleshooting',
            'icon' => '🔧',
            'description' => 'Solutions to common problems',
            'order' => 4,
        ],
    ],
];