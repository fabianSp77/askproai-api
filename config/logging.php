<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'sentry'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'permission' => 0664,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 7,
            'permission' => 0664,
        ],

        'mcp-external' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mcp-external.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'permission' => 0664,
        ],

        'mail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail.log'),
            'level' => 'error',
            'days' => 7,
            'permission' => 0664,
        ],

        'calcom' => [
            'driver' => 'daily',
            'path' => storage_path('logs/calcom.log'),
            'level' => 'error',
            'days' => 7,
            'permission' => 0664,
        ],

        'retell' => [
            'driver' => 'daily',
            'path' => storage_path('logs/retell_webhook.log'),
            'level' => 'error',
            'days' => 7,
            'permission' => 0664,
        ],

        'frontend' => [
            'driver' => 'daily',
            'path' => storage_path('logs/frontend-errors.log'),
            'level' => 'error',
            'days' => 14,
            'permission' => 0664,
        ],

        'webhooks' => [
            'driver' => 'daily',
            'path' => storage_path('logs/webhooks.log'),
            'level' => 'info',
            'days' => 30,
            'permission' => 0664,
        ],

        'critical' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical.log'),
            'level' => 'error',
            'days' => 90,
            'permission' => 0664,
        ],

        'slow_queries' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slow_queries.log'),
            'level' => 'warning',
            'days' => 30,
            'permission' => 0664,
        ],

        'booking_flow' => [
            'driver' => 'daily',
            'path' => storage_path('logs/booking_flow.log'),
            'level' => 'info',
            'days' => 30,
            'permission' => 0664,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api_calls.log'),
            'level' => 'info',
            'days' => 14,
            'permission' => 0664,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // Sentry integration
        'sentry' => [
            'driver' => 'sentry',
            'level' => env('LOG_LEVEL', 'error'),
            'bubble' => true,
        ],

        // Production monitoring channels
        'monitoring' => [
            'driver' => 'daily',
            'path' => storage_path('logs/monitoring.log'),
            'level' => env('LOG_LEVEL_MONITORING', 'debug'),
            'days' => 14,
            'permission' => 0664,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => env('LOG_LEVEL_SECURITY', 'warning'),
            'days' => 30,
            'permission' => 0664,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => env('LOG_LEVEL_PERFORMANCE', 'info'),
            'days' => 7,
            'permission' => 0664,
        ],

        'stripe' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stripe.log'),
            'level' => env('LOG_LEVEL_STRIPE', 'info'),
            'days' => 30,
            'permission' => 0664,
        ],

        'portal' => [
            'driver' => 'daily',
            'path' => storage_path('logs/portal.log'),
            'level' => env('LOG_LEVEL_PORTAL', 'info'),
            'days' => 14,
            'permission' => 0664,
        ],
    ],

];
