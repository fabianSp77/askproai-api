# Queue & Horizon Complete Guide

## Overview

Laravel Horizon provides a dashboard and code-driven configuration for Redis queues.

## Installation & Setup

```bash
# Install Horizon
composer require laravel/horizon
php artisan horizon:install

# Publish assets
php artisan horizon:publish
```

## Configuration

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'tries' => 3,
            'nice' => 0,
        ],
    ],
],
```

## Queue Priorities

1. **High Priority**: Webhooks, API callbacks
2. **Default**: Email, notifications
3. **Low Priority**: Reports, maintenance

## Running Horizon

```bash
# Development
php artisan horizon

# Production (via Supervisor)
supervisorctl start horizon
```