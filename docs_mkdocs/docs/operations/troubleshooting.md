# Troubleshooting Guide

## Overview

This guide provides solutions to common issues encountered with AskProAI. It's organized by symptoms and includes step-by-step debugging procedures.

## Common Issues

### Application Won't Start

#### Symptoms
- 500 Internal Server Error
- White screen
- "Whoops, something went wrong" message

#### Diagnosis Steps

1. **Check error logs**:
   ```bash
   tail -f /var/www/api-gateway/storage/logs/laravel.log
   tail -f /var/log/nginx/error.log
   tail -f /var/log/php8.2-fpm.log
   ```

2. **Verify file permissions**:
   ```bash
   # Check ownership
   ls -la /var/www/api-gateway
   
   # Fix permissions
   sudo chown -R askproai:www-data /var/www/api-gateway
   sudo chmod -R 755 /var/www/api-gateway
   sudo chmod -R 775 /var/www/api-gateway/storage
   sudo chmod -R 775 /var/www/api-gateway/bootstrap/cache
   ```

3. **Check environment file**:
   ```bash
   # Verify .env exists
   ls -la /var/www/api-gateway/.env
   
   # Check critical variables
   grep -E "APP_KEY|DB_" /var/www/api-gateway/.env
   ```

4. **Clear caches**:
   ```bash
   php artisan optimize:clear
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

#### Common Solutions

- **Missing APP_KEY**: Run `php artisan key:generate`
- **Database connection error**: Verify database credentials and server is running
- **Permission denied**: Fix file permissions as shown above
- **Class not found**: Run `composer dump-autoload`

### Database Connection Issues

#### Symptoms
- "SQLSTATE[HY000] [2002] Connection refused"
- "Access denied for user"
- "Unknown database"

#### Diagnosis Steps

1. **Test database connection**:
   ```bash
   # Test MySQL connection
   mysql -h 127.0.0.1 -u askproai_user -p
   
   # From application
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

2. **Check MySQL service**:
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   
   # Check if listening
   sudo netstat -tlnp | grep 3306
   ```

3. **Verify credentials**:
   ```bash
   # In .env file
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=askproai_db
   DB_USERNAME=askproai_user
   DB_PASSWORD=your_password
   ```

#### Common Solutions

```sql
-- Create database and user
CREATE DATABASE IF NOT EXISTS askproai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'askproai_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON askproai_db.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;

-- Check user permissions
SHOW GRANTS FOR 'askproai_user'@'localhost';
```

### Queue Processing Issues

#### Symptoms
- Jobs not being processed
- Emails not sending
- Webhooks not being handled
- High job failure rate

#### Diagnosis Steps

1. **Check queue workers**:
   ```bash
   # Check Horizon status
   php artisan horizon:status
   
   # Check supervisor
   sudo supervisorctl status
   
   # View Horizon dashboard
   http://your-domain/horizon
   ```

2. **Monitor queue sizes**:
   ```bash
   # Check Redis queues
   redis-cli
   > LLEN queues:default
   > LLEN queues:high
   > LLEN queues:failed
   ```

3. **Check failed jobs**:
   ```bash
   php artisan queue:failed
   
   # Inspect specific failed job
   php artisan queue:failed:show {id}
   ```

#### Common Solutions

1. **Restart queue workers**:
   ```bash
   sudo supervisorctl restart horizon
   # or
   php artisan horizon:terminate
   php artisan horizon
   ```

2. **Process failed jobs**:
   ```bash
   # Retry all failed jobs
   php artisan queue:retry all
   
   # Retry specific job
   php artisan queue:retry {id}
   
   # Clear failed jobs
   php artisan queue:flush
   ```

3. **Debug specific job**:
   ```php
   // Add to job class
   public function failed(Throwable $exception)
   {
       Log::error('Job failed', [
           'job' => static::class,
           'data' => $this->data,
           'exception' => $exception->getMessage(),
           'trace' => $exception->getTraceAsString(),
       ]);
   }
   ```

### Webhook Issues

#### Symptoms
- Webhooks not being received
- "Invalid signature" errors
- Webhook processing failures

#### Diagnosis Steps

1. **Check webhook logs**:
   ```bash
   # Application logs
   grep -i webhook /var/www/api-gateway/storage/logs/laravel.log
   
   # Nginx access logs
   grep webhook /var/log/nginx/access.log
   ```

2. **Test webhook endpoint**:
   ```bash
   # Test Retell webhook
   curl -X POST https://your-domain/api/retell/webhook \
     -H "Content-Type: application/json" \
     -H "x-retell-signature: test" \
     -d '{"event_type":"test"}'
   ```

3. **Verify webhook configuration**:
   ```php
   php artisan tinker
   >>> config('services.retell.webhook_secret')
   >>> config('services.calcom.webhook_secret')
   ```

#### Common Solutions

1. **Fix signature verification**:
   ```php
   // app/Http/Middleware/VerifyRetellSignature.php
   public function handle($request, Closure $next)
   {
       Log::debug('Webhook received', [
           'headers' => $request->headers->all(),
           'body' => $request->getContent(),
       ]);
       
       // Temporarily bypass for testing (remove in production!)
       if (app()->environment('local')) {
           return $next($request);
       }
       
       // Normal verification
       $signature = $request->header('x-retell-signature');
       // ... rest of verification
   }
   ```

2. **Process webhook manually**:
   ```php
   php artisan tinker
   >>> $webhook = \App\Models\WebhookEvent::find(123);
   >>> app(\App\Services\Webhooks\RetellWebhookHandler::class)->handle($webhook->payload);
   ```

### Performance Issues

#### Symptoms
- Slow page loads
- High response times
- Database queries timing out
- High memory usage

#### Diagnosis Steps

1. **Enable query logging**:
   ```php
   // Add to AppServiceProvider
   if (config('app.debug')) {
       DB::listen(function ($query) {
           if ($query->time > 100) {
               Log::warning('Slow query detected', [
                   'sql' => $query->sql,
                   'time' => $query->time,
                   'bindings' => $query->bindings,
               ]);
           }
       });
   }
   ```

2. **Profile with Laravel Debugbar**:
   ```bash
   composer require barryvdh/laravel-debugbar --dev
   ```

3. **Check resource usage**:
   ```bash
   # CPU and memory
   top -p $(pgrep -d',' php)
   
   # MySQL processes
   mysql -e "SHOW PROCESSLIST"
   
   # Redis memory
   redis-cli INFO memory
   ```

#### Common Solutions

1. **Optimize database queries**:
   ```php
   // Bad - N+1 problem
   $appointments = Appointment::all();
   foreach ($appointments as $appointment) {
       echo $appointment->customer->name;
   }
   
   // Good - Eager loading
   $appointments = Appointment::with('customer')->get();
   ```

2. **Add database indexes**:
   ```sql
   -- Check missing indexes
   SELECT 
       t.TABLE_NAME,
       t.COLUMN_NAME,
       t.DATA_TYPE
   FROM INFORMATION_SCHEMA.COLUMNS t
   WHERE t.TABLE_SCHEMA = 'askproai_db'
       AND t.COLUMN_NAME LIKE '%_id'
       AND NOT EXISTS (
           SELECT 1 
           FROM INFORMATION_SCHEMA.STATISTICS s 
           WHERE s.TABLE_SCHEMA = t.TABLE_SCHEMA 
               AND s.TABLE_NAME = t.TABLE_NAME 
               AND s.COLUMN_NAME = t.COLUMN_NAME
       );
   ```

3. **Enable caching**:
   ```php
   // Cache expensive queries
   $eventTypes = Cache::remember('event-types', 3600, function () {
       return EventType::with('services')->get();
   });
   ```

### External Service Integration Issues

#### Cal.com Issues

**Symptoms**:
- "Event type not found"
- "No available slots"
- Booking failures

**Solutions**:
```bash
# Test Cal.com connection
php artisan debug:calcom test

# Sync event types
php artisan calcom:sync-event-types --force

# Debug specific booking
php artisan tinker
>>> $service = app(\App\Services\CalcomV2Service::class);
>>> $service->getAvailability(2026361, '2025-07-01');
```

#### Retell.ai Issues

**Symptoms**:
- Calls not being logged
- Agent not responding
- Webhook not received

**Solutions**:
```bash
# Test Retell connection
php artisan debug:retell test

# Check agent configuration
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.retellai.com/v1/agents

# Manually import calls
php artisan retell:import-calls --days=7
```

### Email Delivery Issues

#### Symptoms
- Emails not being sent
- Emails going to spam
- SMTP connection errors

#### Diagnosis Steps

1. **Test email configuration**:
   ```php
   php artisan tinker
   >>> Mail::raw('Test email', function($message) {
   ...     $message->to('test@example.com')->subject('Test');
   ... });
   ```

2. **Check mail logs**:
   ```bash
   # If using log driver
   tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i mail
   
   # Check mail queue
   sudo mailq
   ```

3. **Verify SMTP settings**:
   ```bash
   # Test SMTP connection
   telnet smtp.server.com 587
   ```

#### Common Solutions

1. **Use different mail driver for testing**:
   ```env
   # In .env
   MAIL_MAILER=log  # Logs emails instead of sending
   # or
   MAIL_MAILER=smtp
   MAIL_HOST=localhost
   MAIL_PORT=1025  # For Mailhog
   ```

2. **Fix authentication**:
   ```env
   MAIL_USERNAME=your-username
   MAIL_PASSWORD="your-password"  # Quote if special characters
   MAIL_ENCRYPTION=tls
   ```

### Caching Issues

#### Symptoms
- Stale data being displayed
- Changes not reflecting
- "Cache store not found" errors

#### Diagnosis Steps

1. **Check Redis connection**:
   ```bash
   redis-cli ping
   redis-cli INFO
   ```

2. **Verify cache configuration**:
   ```php
   php artisan tinker
   >>> Cache::put('test', 'value', 60);
   >>> Cache::get('test');
   ```

#### Common Solutions

1. **Clear all caches**:
   ```bash
   php artisan cache:clear
   redis-cli FLUSHDB
   ```

2. **Fix Redis connection**:
   ```env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   CACHE_DRIVER=redis
   ```

## Debugging Tools

### Artisan Commands

```bash
# System information
php artisan about

# List all routes
php artisan route:list

# Test database connection
php artisan db:show

# View configuration
php artisan config:show

# Queue status
php artisan queue:monitor

# Clear everything
php artisan optimize:clear
```

### Tinker Debugging

```php
// Test database
DB::connection()->getPdo();
DB::select('SELECT 1');

// Test Redis
Redis::ping();
Redis::set('test', 'value');
Redis::get('test');

// Test services
app(RetellService::class)->testConnection();
app(CalcomService::class)->getEventTypes();

// Debug specific models
$appointment = Appointment::with(['customer', 'service', 'branch'])->find(1);
dd($appointment->toArray());

// Check configuration
config('services.retell');
env('DEFAULT_RETELL_API_KEY');
```

### Log Analysis

```bash
# Find errors in logs
grep -i error /var/www/api-gateway/storage/logs/*.log

# Find specific user activity
grep "user_id:123" /var/www/api-gateway/storage/logs/*.log

# Monitor logs in real-time
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -v INFO

# Count errors by type
grep ERROR /var/www/api-gateway/storage/logs/*.log | awk '{print $4}' | sort | uniq -c
```

## Emergency Procedures

### Application Down

1. **Immediate Actions**:
   ```bash
   # Restart services
   sudo systemctl restart nginx
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart mysql
   sudo systemctl restart redis
   
   # Check disk space
   df -h
   
   # Check memory
   free -m
   ```

2. **Enable maintenance mode**:
   ```bash
   php artisan down --message="Maintenance in progress" --retry=60
   ```

3. **Quick diagnostics**:
   ```bash
   # Check last 100 errors
   tail -n 1000 /var/www/api-gateway/storage/logs/laravel.log | grep ERROR
   
   # Check system load
   uptime
   top
   ```

### Database Locked

```sql
-- Show running processes
SHOW PROCESSLIST;

-- Kill long-running query
KILL QUERY process_id;

-- Show locks
SELECT * FROM INFORMATION_SCHEMA.INNODB_LOCKS;
SELECT * FROM INFORMATION_SCHEMA.INNODB_LOCK_WAITS;

-- Force unlock tables
UNLOCK TABLES;
```

### High Load Emergency

```bash
#!/bin/bash
# Emergency load reduction

# 1. Enable maintenance mode
php artisan down

# 2. Stop non-critical services
sudo supervisorctl stop horizon

# 3. Increase PHP-FPM limits temporarily
sudo sed -i 's/pm.max_children = .*/pm.max_children = 100/' /etc/php/8.2/fpm/pool.d/www.conf
sudo systemctl reload php8.2-fpm

# 4. Clear caches
redis-cli FLUSHDB

# 5. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# 6. Gradually bring back online
php artisan up
sudo supervisorctl start horizon
```

## Monitoring Commands

### Health Check Script

```bash
#!/bin/bash
# health-check.sh

echo "=== AskProAI Health Check ==="

# Check services
services=("nginx" "php8.2-fpm" "mysql" "redis" "supervisor")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        echo "✓ $service is running"
    else
        echo "✗ $service is down"
    fi
done

# Check disk space
echo -e "\n=== Disk Usage ==="
df -h | grep -E "^/dev|Filesystem"

# Check memory
echo -e "\n=== Memory Usage ==="
free -h

# Check database
echo -e "\n=== Database Status ==="
mysql -e "SELECT COUNT(*) as connections FROM information_schema.processlist;" 2>/dev/null || echo "✗ Cannot connect to MySQL"

# Check Redis
echo -e "\n=== Redis Status ==="
redis-cli ping 2>/dev/null || echo "✗ Cannot connect to Redis"

# Check application
echo -e "\n=== Application Status ==="
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost/health || echo "✗ Application not responding"
```

## Best Practices

1. **Always backup before changes**
2. **Test in staging first**
3. **Monitor logs during changes**
4. **Document issues and solutions**
5. **Keep debug mode off in production**
6. **Use version control for configurations**
7. **Maintain runbooks for common issues**

## Related Documentation

- [Debugging Guide](../development/debugging.md)
- [Monitoring Guide](monitoring.md)
- [Performance Optimization](performance.md)
- [Deployment Guide](../deployment/production.md)