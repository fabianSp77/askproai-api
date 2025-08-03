## Debugging & Troubleshooting

### Booking Flow Debugging

#### 1. Enable Debug Logging
```bash
# Set in .env
LOG_CHANNEL=stack
LOG_LEVEL=debug
BOOKING_DEBUG=true
```

#### 2. Check Correlation IDs
```sql
-- Find all logs for a failed booking
SELECT * FROM api_call_logs 
WHERE correlation_id = 'YOUR-CORRELATION-ID'
ORDER BY created_at;

-- Check webhook processing
SELECT * FROM webhook_events 
WHERE payload->>'$.call_id' = 'YOUR-CALL-ID';
```

#### 3. Common Issues & Solutions

**Issue: "Time slot no longer available"**
```sql
-- Check for stuck locks
SELECT * FROM appointment_locks 
WHERE expires_at < NOW() 
AND branch_id = 'YOUR-BRANCH-ID';

-- Clean expired locks
DELETE FROM appointment_locks WHERE expires_at < NOW();
```

**Issue: "Cal.com sync failed"**
```bash
# Check circuit breaker status
php artisan circuit-breaker:status

# Reset circuit breaker
php artisan circuit-breaker:reset calcom

# Manually retry sync
php artisan appointments:sync-failed
```

**Issue: "Webhook not processing"**
```php
// Check webhook signature
curl -X POST https://api.askproai.de/api/webhook \
  -H "x-retell-signature: YOUR_SIGNATURE" \
  -H "Content-Type: application/json" \
  -d '{"event_type":"call_ended","call_id":"test"}'
```

**Issue: "Database Access Denied" after deployment**
This error typically occurs when Laravel's cached configuration contains incorrect database credentials.

**Symptoms:**
- Error: `Access denied for user 'askproai_user'@'localhost' (using password: YES)`
- Occurs after deployment or environment changes
- Application was working before, suddenly stops

**Root Cause:**
Laravel's config cache (`bootstrap/cache/config.php`) may contain incorrect values from:
1. `.env.production` template files with placeholder values
2. Old cached values from previous deployments
3. Environment file precedence issues

**Solution:**
```bash
# 1. Delete the cached config file
rm -f bootstrap/cache/config.php

# 2. Check for .env.production files that might override .env
ls -la .env*

# 3. Rename any .env.production files to prevent loading
mv .env.production .env.production.template

# 4. Recreate config cache with correct values
php artisan config:cache

# 5. Restart PHP-FPM to ensure changes take effect
sudo systemctl restart php8.3-fpm
```

**Prevention:**
- Never commit `.env.production` files with actual credentials
- Use `.env.production.template` for template files
- Always clear config cache after deployment: `php artisan optimize:clear`
- Verify database credentials in `.env` before caching config

### Performance Monitoring

```sql
-- Slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 10;

-- API performance
SELECT 
    service,
    AVG(duration_ms) as avg_ms,
    MAX(duration_ms) as max_ms,
    COUNT(*) as total_calls
FROM api_call_logs
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY service;
```

## Common Development Tasks
