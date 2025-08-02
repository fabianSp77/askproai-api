<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Content Delivery Network settings here
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CDN Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable CDN for asset delivery
    |
    */
    'enabled' => env('CDN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | CDN URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your CDN
    |
    */
    'url' => env('CDN_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | CDN Providers
    |--------------------------------------------------------------------------
    |
    | Supported CDN providers and their configurations
    |
    */
    'providers' => [
        'cloudflare' => [
            'enabled' => env('CLOUDFLARE_ENABLED', false),
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
            'email' => env('CLOUDFLARE_EMAIL'),
            'features' => [
                'auto_minify' => true,
                'rocket_loader' => true,
                'mirage' => true,
                'polish' => 'lossless',
                'webp' => true,
            ],
        ],
        'aws_cloudfront' => [
            'enabled' => env('CLOUDFRONT_ENABLED', false),
            'distribution_id' => env('CLOUDFRONT_DISTRIBUTION_ID'),
            'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
        'bunnycdn' => [
            'enabled' => env('BUNNYCDN_ENABLED', false),
            'pull_zone' => env('BUNNYCDN_PULL_ZONE'),
            'api_key' => env('BUNNYCDN_API_KEY'),
            'storage_zone' => env('BUNNYCDN_STORAGE_ZONE'),
            'storage_key' => env('BUNNYCDN_STORAGE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Types
    |--------------------------------------------------------------------------
    |
    | Configure which asset types should be served from CDN
    |
    */
    'asset_types' => [
        'css' => true,
        'js' => true,
        'images' => true,
        'fonts' => true,
        'documents' => false,
        'videos' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that should be served from CDN
    |
    */
    'extensions' => [
        'css', 'js', 'map',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'eot',
        'mp4', 'webm', 'ogg',
        'pdf', 'doc', 'docx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Paths that should be served from CDN
    |
    */
    'paths' => [
        'include' => [
            'css/*',
            'js/*',
            'images/*',
            'img/*',
            'fonts/*',
            'build/*',
            'assets/*',
        ],
        'exclude' => [
            'css/admin/*',
            'js/admin/*',
            'private/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Control
    |--------------------------------------------------------------------------
    |
    | Cache control headers for different asset types
    |
    */
    'cache_control' => [
        'default' => 'public, max-age=31536000', // 1 year
        'css' => 'public, max-age=31536000',
        'js' => 'public, max-age=31536000',
        'images' => 'public, max-age=31536000',
        'fonts' => 'public, max-age=31536000, immutable',
        'documents' => 'public, max-age=3600', // 1 hour
        'videos' => 'public, max-age=86400', // 1 day
    ],

    /*
    |--------------------------------------------------------------------------
    | Versioning
    |--------------------------------------------------------------------------
    |
    | Asset versioning strategy
    |
    */
    'versioning' => [
        'enabled' => true,
        'method' => env('CDN_VERSIONING_METHOD', 'hash'), // hash, timestamp, version
        'parameter' => 'v', // Query parameter for version
        'length' => 8, // Hash length
    ],

    /*
    |--------------------------------------------------------------------------
    | Preload
    |--------------------------------------------------------------------------
    |
    | Assets to preload for better performance
    |
    */
    'preload' => [
        'css/app.css' => ['as' => 'style'],
        'js/app.js' => ['as' => 'script'],
        'fonts/inter-latin-400.woff2' => ['as' => 'font', 'crossorigin' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push
    |--------------------------------------------------------------------------
    |
    | HTTP/2 Server Push configuration
    |
    */
    'push' => [
        'enabled' => env('HTTP2_PUSH_ENABLED', true),
        'resources' => [
            'css/app.css',
            'js/app.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimization
    |--------------------------------------------------------------------------
    |
    | CDN optimization settings
    |
    */
    'optimization' => [
        'minify' => [
            'html' => true,
            'css' => true,
            'js' => true,
        ],
        'compress' => [
            'gzip' => true,
            'brotli' => true,
        ],
        'image_optimization' => [
            'enabled' => true,
            'quality' => 85,
            'webp' => true,
            'lazy_loading' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Purge
    |--------------------------------------------------------------------------
    |
    | CDN cache purge configuration
    |
    */
    'purge' => [
        'on_deploy' => env('CDN_PURGE_ON_DEPLOY', true),
        'patterns' => [
            'css/*',
            'js/*',
            'build/manifest.json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    |
    | Fallback configuration when CDN is unavailable
    |
    */
    'fallback' => [
        'enabled' => true,
        'check_interval' => 300, // 5 minutes
        'timeout' => 3, // seconds
        'max_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | CDN security settings
    |
    */
    'security' => [
        'sri' => [ // Subresource Integrity
            'enabled' => true,
            'algorithm' => 'sha384',
        ],
        'cors' => [
            'enabled' => true,
            'origins' => ['*'],
            'methods' => ['GET', 'HEAD'],
            'headers' => ['*'],
            'max_age' => 86400,
        ],
        'hotlink_protection' => [
            'enabled' => false,
            'allowed_domains' => [
                env('APP_URL'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | CDN analytics and monitoring
    |
    */
    'analytics' => [
        'enabled' => env('CDN_ANALYTICS_ENABLED', true),
        'track_bandwidth' => true,
        'track_requests' => true,
        'track_cache_hit_rate' => true,
        'alert_thresholds' => [
            'bandwidth_gb' => 1000, // Alert when monthly bandwidth exceeds 1TB
            'requests_millions' => 10, // Alert when monthly requests exceed 10M
            'cache_hit_rate' => 0.85, // Alert when cache hit rate falls below 85%
        ],
    ],
];