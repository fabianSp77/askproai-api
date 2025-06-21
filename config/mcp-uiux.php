<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UI/UX Best Practices MCP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the UI/UX monitoring and improvement system that tracks
    | Laravel/Filament best practices and suggests improvements.
    |
    */

    'sources' => [
        'laravel' => 'https://laravel.com/docs/master',
        'filament' => 'https://filamentphp.com/docs',
        'tailwind' => 'https://tailwindcss.com/docs',
        'material' => 'https://material.io/design',
        'carbon' => 'https://carbondesignsystem.com',
    ],
    
    'monitoring' => [
        'enabled' => env('UIUX_MONITORING_ENABLED', true),
        'check_interval' => 86400, // Daily
        'performance_threshold' => 3.0, // seconds
        'accessibility_score' => 90, // target score
        'mobile_breakpoint' => 768, // pixels
    ],
    
    'analysis' => [
        'check_accessibility' => true,
        'check_performance' => true,
        'check_responsive' => true,
        'check_consistency' => true,
        'check_best_practices' => true,
    ],
    
    'performance' => [
        'targets' => [
            'page_load' => 2.0, // seconds
            'first_contentful_paint' => 1.0,
            'time_to_interactive' => 3.0,
            'largest_contentful_paint' => 2.5,
        ],
        
        'widget_targets' => [
            'render_time' => 0.5, // seconds
            'update_time' => 0.3,
            'query_count' => 5, // max queries per widget
        ],
    ],
    
    'accessibility' => [
        'wcag_level' => 'AA',
        'color_contrast_ratio' => 4.5,
        'focus_indicator_required' => true,
        'aria_labels_required' => true,
        'keyboard_navigation_required' => true,
    ],
    
    'design_system' => [
        'colors' => [
            'primary' => '#3B82F6',
            'secondary' => '#6B7280',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444',
        ],
        
        'spacing' => [
            'unit' => 4, // pixels
            'scale' => [1, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24],
        ],
        
        'typography' => [
            'font_family' => 'Inter, system-ui, sans-serif',
            'base_size' => 16,
            'scale_ratio' => 1.25,
        ],
    ],
    
    'best_practices' => [
        'laravel' => [
            'use_route_model_binding' => true,
            'use_form_requests' => true,
            'use_resource_controllers' => true,
            'use_view_composers' => true,
            'use_blade_components' => true,
        ],
        
        'filament' => [
            'use_actions' => true,
            'use_relation_managers' => true,
            'use_custom_fields' => true,
            'use_table_filters' => true,
            'use_bulk_actions' => true,
        ],
    ],
    
    'suggestions' => [
        'auto_suggest' => true,
        'min_confidence' => 0.7,
        'max_suggestions_per_component' => 5,
    ],
    
    'cache' => [
        'ttl' => 3600, // 1 hour
        'prefix' => 'uiux',
    ],
    
    'reports' => [
        'generate_weekly' => true,
        'generate_monthly' => true,
        'storage_path' => 'uiux-reports',
        'retention_days' => 90,
    ],
];