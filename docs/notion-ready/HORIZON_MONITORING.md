# Horizon Monitoring

## Dashboard Access

Access Horizon dashboard at: `/horizon`

## Metrics Available

1. **Job Metrics**
   - Jobs per minute
   - Job runtime
   - Failed jobs

2. **Queue Metrics**
   - Queue length
   - Wait time
   - Throughput

3. **Worker Metrics**
   - Active workers
   - Memory usage
   - CPU usage

## Monitoring Commands

```bash
# Check status
php artisan horizon:status

# List failed jobs
php artisan queue:failed

# Monitor in real-time
php artisan horizon:snapshot
```

## Alerts Configuration

```php
// config/horizon.php
'waits' => [
    'redis:default' => 60,  // Alert if job waits > 60 seconds
],

'trim' => [
    'recent' => 60,
    'pending' => 60,
    'completed' => 60,
    'recent_failed' => 10080,
    'failed' => 10080,
    'monitored' => 10080,
],
```

## Supervisor Configuration

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/horizon.log
stopwaitsecs=3600
```