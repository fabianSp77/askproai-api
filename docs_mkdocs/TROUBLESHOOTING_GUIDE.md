# AskProAI Troubleshooting Guide

## Common Issues and Solutions

### 1. Booking Flow Issues

#### Problem: "Time slot no longer available" error
**Symptoms:**
- Customer gets error when trying to book
- Slot was shown as available but booking fails

**Possible Causes:**
1. Race condition - another booking took the slot
2. Stale cache data
3. Cal.com sync delay

**Solutions:**
```sql
-- Check for stuck locks
SELECT * FROM appointment_locks 
WHERE expires_at < NOW() 
AND branch_id = ?;

-- Clean expired locks
DELETE FROM appointment_locks WHERE expires_at < NOW();
```

```bash
# Clear availability cache
php artisan cache:clear --tags=availability

# Force Cal.com sync
php artisan calcom:sync-availability --branch=1
```

#### Problem: Duplicate bookings created
**Symptoms:**
- Same appointment appears multiple times
- Customer receives multiple confirmations

**Possible Causes:**
1. Webhook processed multiple times
2. Frontend retry without idempotency
3. Queue worker issues

**Solutions:**
```bash
# Check webhook deduplication
redis-cli
> KEYS webhook:processing:*
> TTL webhook:processing:xyz

# Check for duplicate webhooks in logs
grep "Webhook already processed" storage/logs/laravel.log | tail -20

# Manually mark webhook as processed
php artisan webhook:mark-processed <idempotency-key>
```

### 2. Cal.com Integration Issues

#### Problem: Cal.com API returns 403 Forbidden
**Symptoms:**
- All Cal.com requests fail
- V1 API calls return 403

**Possible Causes:**
1. Using V1 API with V2-only key
2. API key expired
3. IP not whitelisted

**Solutions:**
```bash
# Test V2 API connection
php artisan tinker
>>> $service = new \App\Services\CalcomV2Service();
>>> $service->testConnection();

# Check API key format
>>> starts_with(config('services.calcom.api_key'), 'cal_live_');

# Force V2 usage
>>> config(['services.calcom.force_v2' => true]);
```

#### Problem: Cal.com sync failing
**Symptoms:**
- Event types not updating
- Availability out of sync
- Bookings not appearing

**Possible Causes:**
1. Circuit breaker open
2. Rate limiting
3. Network issues

**Solutions:**
```bash
# Check circuit breaker status
php artisan circuit-breaker:status calcom

# Reset circuit breaker
php artisan circuit-breaker:reset calcom

# Check rate limit headers
curl -I -H "Authorization: Bearer cal_live_xxx" \
  https://api.cal.com/v2/event-types

# Manual sync with debug
php artisan calcom:sync --debug --verbose
```

### 3. Retell.ai Integration Issues

#### Problem: Calls not importing
**Symptoms:**
- No calls showing in dashboard
- Webhooks not received
- "Anrufe abrufen" button doesn't work

**Possible Causes:**
1. Webhook not registered in Retell
2. Signature verification failing
3. Queue workers not running

**Solutions:**
```bash
# Check if webhooks are being received
tail -f storage/logs/laravel.log | grep -i retell

# Test Retell API connection
php artisan retell:test-connection

# Manual import of calls
php artisan retell:import-calls --days=7

# Check webhook signature
curl -X POST http://localhost:8000/api/retell/webhook \
  -H "x-retell-signature: test" \
  -H "Content-Type: application/json" \
  -d '{"event":"call_ended","call_id":"test"}'
```

#### Problem: Agent provisioning fails
**Symptoms:**
- "Failed to create agent" error
- Agent status stuck in "provisioning"

**Possible Causes:**
1. Branch validation failing
2. Missing required data
3. API quota exceeded

**Solutions:**
```php
// Test provisioning validation
$branch = Branch::find(1);
$validator = new \App\Services\Provisioning\ProvisioningValidator();
$result = $validator->validateBranch($branch);
dd($result->toArray());

// Check for missing data
>>> $branch->services()->count();
>>> $branch->business_hours;
>>> $branch->phone_number;

// Manual agent creation
$provisioner = new \App\Services\Provisioning\RetellAgentProvisioner();
$result = $provisioner->createAgentForBranch($branch);
```

### 4. Performance Issues

#### Problem: Slow dashboard loading
**Symptoms:**
- Dashboard takes > 5 seconds to load
- Timeout errors
- High server load

**Possible Causes:**
1. Missing database indexes
2. N+1 queries
3. No caching

**Solutions:**
```bash
# Check slow queries
php artisan askproai:performance-monitor --slow-queries

# Analyze specific query
EXPLAIN SELECT * FROM appointments WHERE company_id = 1;

# Add missing indexes
php artisan migrate --path=database/migrations/2025_06_17_add_performance_critical_indexes.php

# Enable query log
DB::enableQueryLog();
// ... run slow operation ...
dd(DB::getQueryLog());
```

### 5. Queue/Worker Issues

#### Problem: Jobs not processing
**Symptoms:**
- Webhooks queued but not processed
- Emails not sending
- Background tasks stuck

**Possible Causes:**
1. Horizon not running
2. Redis connection issue
3. Jobs failing silently

**Solutions:**
```bash
# Check Horizon status
php artisan horizon:status

# Start Horizon
php artisan horizon

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Monitor queue sizes
redis-cli
> LLEN queues:webhooks
> LLEN queues:default

# Clear stuck jobs
php artisan queue:clear webhooks
```

### 6. Multi-Tenancy Issues

#### Problem: Data leaking between companies
**Symptoms:**
- Company A sees Company B's data
- Appointments showing for wrong company

**Possible Causes:**
1. Missing company_id scope
2. Global scope disabled
3. Raw queries without tenant filter

**Solutions:**
```php
// Check if model has scope
>>> $model = new \App\Models\Appointment();
>>> $model->getGlobalScopes();

// Test tenant isolation
>>> auth()->user()->company_id = 1;
>>> Appointment::count(); // Should only show company 1
>>> auth()->user()->company_id = 2;
>>> Appointment::count(); // Should only show company 2

// Find queries without scope
grep -r "whereRaw\|DB::raw\|DB::select" app/
```

### 7. Email Issues

#### Problem: Confirmation emails not sending
**Symptoms:**
- No email after booking
- Emails in queue but not sent

**Possible Causes:**
1. SMTP configuration wrong
2. Email queued but worker not running
3. Template rendering error

**Solutions:**
```bash
# Test email configuration
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));

# Check mail queue
php artisan queue:work --queue=emails --tries=1

# Preview email template
>>> $appointment = Appointment::first();
>>> return new \App\Mail\AppointmentConfirmation($appointment);
```

### 8. Security/Authentication Issues

#### Problem: API returns 401 Unauthorized
**Symptoms:**
- Valid token rejected
- Can't access protected routes

**Possible Causes:**
1. Token expired
2. Wrong token type
3. Sanctum not configured

**Solutions:**
```bash
# Check token
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/user

# Generate new token
php artisan tinker
>>> $user = User::first();
>>> $user->createToken('api')->plainTextToken;

# Check Sanctum config
>>> config('sanctum.guard');
>>> config('auth.guards.sanctum');
```

## Monitoring Commands

### Health Checks
```bash
# Overall system health
curl https://api.askproai.de/health

# Cal.com integration health
curl https://api.askproai.de/health/calcom

# Database health
php artisan db:show
```

### Performance Monitoring
```bash
# Real-time performance monitor
php artisan askproai:performance-monitor --live

# Generate performance report
php artisan askproai:performance-monitor --report > performance.json

# Check specific metrics
php artisan askproai:performance-monitor --metric=response_time
```

### Debug Mode
```bash
# Enable debug logging for booking flow
BOOKING_DEBUG=true php artisan serve

# Enable SQL query logging
DB_LOG_QUERIES=true php artisan serve

# Verbose webhook processing
WEBHOOK_DEBUG=true php artisan horizon
```

## Emergency Procedures

### 1. System Overload
```bash
# Disable non-critical features
php artisan down --allow=127.0.0.1

# Scale down workers
php artisan horizon:pause

# Clear caches
php artisan optimize:clear

# Emergency mode
touch storage/framework/emergency
```

### 2. Database Issues
```bash
# Check connections
php artisan db:show

# Kill long-running queries
mysql -e "SHOW PROCESSLIST" | grep -v Sleep
mysql -e "KILL QUERY <process_id>"

# Reset connection pool
php artisan db:reconnect
```

### 3. API Outages
```bash
# Enable fallback mode
php artisan config:set services.calcom.fallback_enabled true

# Use cached data only
php artisan config:set services.calcom.cache_only true

# Disable external APIs temporarily
touch storage/framework/maintenance/calcom
```

## Log Locations

- **Application Logs**: `/storage/logs/laravel.log`
- **Queue Logs**: `/storage/logs/horizon.log`
- **Web Server Logs**: `/var/log/nginx/error.log`
- **PHP Logs**: `/var/log/php-fpm/error.log`
- **Database Logs**: `/var/log/mysql/error.log`

## Getting Help

### Internal Resources
- Slack: #askproai-tech
- Wiki: https://wiki.askproai.internal
- Monitoring: https://monitoring.askproai.de

### External Support
- Cal.com Support: support@cal.com
- Retell.ai Support: support@retellai.com
- Laravel Discord: https://discord.gg/laravel

---
*Last Updated: 2025-06-17*