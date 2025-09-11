<?php

return [
    /**
     * Flowbite Pro Configuration
     */
    
    // Enable Flowbite Pro components
    'enabled' => env('FLOWBITE_ENABLED', true),
    
    // Component paths
    'paths' => [
        'components' => resource_path('flowbite-pro'),
        'views' => resource_path('views/flowbite'),
        'assets' => public_path('flowbite-pro'),
    ],
    
    // Theme configuration
    'theme' => [
        'primary' => 'blue',
        'dark_mode' => true,
        'rtl' => false,
    ],
    
    // Component defaults
    'defaults' => [
        'size' => 'md',
        'variant' => 'default',
        'rounded' => 'lg',
    ],
    
    // Filament integration
    'filament' => [
        'enabled' => true,
        'widgets' => true,
        'forms' => true,
        'tables' => true,
    ],
    
    // Livewire integration
    'livewire' => [
        'enabled' => true,
        'prefix' => 'flowbite',
    ],
];