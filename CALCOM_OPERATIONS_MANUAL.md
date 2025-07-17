# Cal.com Operations Manual

## Overview

This manual provides standard operating procedures for managing the Cal.com integration in AskProAI, including daily tasks, monitoring procedures, performance optimization, and maintenance routines.

## Daily Operations

### Morning Checklist (Start of Business Day)

```bash
# 1. Check system health
php artisan calcom:health-check

# 2. Verify overnight sync status
php artisan calcom:sync-status --since=yesterday

# 3. Check for failed webhooks
php artisan queue:failed | grep ProcessCalcomWebhook

# 4. Review error logs from last 24h
tail -n 1000 storage/logs/calcom.log | grep ERROR

# 5. Verify circuit breaker state
php artisan calcom:circuit-status
```

### Automated Daily Tasks

These tasks run automatically via cron:

```cron
# Sync event types (daily at 3 AM)
0 3 * * * cd /var/www/api-gateway && php artisan calcom:sync-event-types --all

# Clean up old webhook logs (daily at 4 AM)
0 4 * * * cd /var/www/api-gateway && php artisan calcom:cleanup-logs --days=30

# Generate daily report (daily at 6 AM)
0 6 * * * cd /var/www/api-gateway && php artisan calcom:daily-report

# Cache warmup (every 6 hours)
0 */6 * * * cd /var/www/api-gateway && php artisan calcom:cache-warmup
```

## Monitoring Procedures

### Real-time Monitoring Dashboard

Access the Cal.com monitoring dashboard:
```
https://api.askproai.de/admin/calcom-monitoring
```

Key metrics displayed:
- API response times (last 24h)
- Success/failure rates
- Active bookings count
- Webhook processing queue
- Circuit breaker status

### Manual Monitoring Commands

```bash
# Live API performance monitoring
watch -n 5 'php artisan calcom:metrics --live'

# Webhook queue monitoring
php artisan horizon:monitor

# Database query monitoring
mysql -u askproai_user -p askproai_db -e "
SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN calcom_booking_id IS NULL THEN 1 ELSE 0 END) as missing_bookings,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_bookings
FROM appointments 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
"
```

### Alert Configuration

Configure alerts in `.env`:

```bash
# Alert thresholds
CALCOM_ALERT_API_FAILURE_RATE=5     # Alert if >5% failures
CALCOM_ALERT_RESPONSE_TIME=2000     # Alert if >2 seconds
CALCOM_ALERT_CIRCUIT_BREAKER=true   # Alert on circuit breaker trips
CALCOM_ALERT_WEBHOOK_QUEUE=100      # Alert if >100 pending webhooks
```

## Performance Optimization

### Cache Management

#### Cache Warmup Strategy

```php
// app/Console/Commands/CalcomCacheWarmup.php
class CalcomCacheWarmup extends Command
{
    public function handle()
    {
        $companies = Company::where('is_active', true)->get();
        
        foreach ($companies as $company) {
            // Warm event types cache
            $this->warmEventTypesCache($company);
            
            // Warm availability cache for next 7 days
            $this->warmAvailabilityCache($company);
        }
    }
    
    private function warmEventTypesCache(Company $company)
    {
        $service = new CalcomV2Service($company);
        $eventTypes = $service->getEventTypes(); // This caches automatically
        
        $this->info("Warmed {$eventTypes->count()} event types for {$company->name}");
    }
    
    private function warmAvailabilityCache(Company $company)
    {
        $eventTypes = $company->calcomEventTypes()->active()->get();
        $dates = CarbonPeriod::create(now(), now()->addDays(7));
        
        foreach ($eventTypes as $eventType) {
            foreach ($dates as $date) {
                Cache::remember(
                    "calcom:slots:{$eventType->calcom_event_type_id}:{$date->format('Y-m-d')}",
                    300,
                    fn() => $this->fetchSlots($eventType, $date)
                );
            }
        }
    }
}
```

#### Cache Optimization Settings

```php
// config/calcom-v2.php
'cache' => [
    'enabled' => env('CALCOM_V2_CACHE_ENABLED', true),
    'driver' => env('CALCOM_CACHE_DRIVER', 'redis'),
    'prefix' => 'calcom',
    'ttl' => [
        'event_types' => 300,    // 5 minutes
        'schedules' => 600,      // 10 minutes
        'user_info' => 1800,     // 30 minutes
        'slots' => 60,           // 1 minute (critical for accuracy)
    ],
    'tags' => ['calcom', 'api'],
];
```

### Database Optimization

#### Indexes for Performance

```sql
-- Ensure these indexes exist
CREATE INDEX idx_appointments_calcom_booking ON appointments(calcom_booking_id);
CREATE INDEX idx_appointments_status_date ON appointments(status, start_time);
CREATE INDEX idx_calcom_event_types_active ON calcom_event_types(company_id, is_active);
CREATE INDEX idx_webhook_events_created ON webhook_events(created_at, event_type);
```

#### Query Optimization

```php
// Use eager loading
$appointments = Appointment::with([
    'calcomEventType',
    'customer',
    'branch.company'
])->whereDate('start_time', today())->get();

// Use chunking for large datasets
CalcomEventType::where('is_active', true)
    ->chunk(100, function ($eventTypes) {
        foreach ($eventTypes as $eventType) {
            $this->processEventType($eventType);
        }
    });
```

### API Request Optimization

#### Request Batching

```php
// Batch multiple availability checks
public function batchCheckAvailability(array $requests)
{
    $responses = Http::pool(fn($pool) => 
        collect($requests)->map(fn($req) => 
            $pool->withToken($this->apiKey)
                ->timeout(10)
                ->get('/slots/available', $req['params'])
        )
    );
    
    return collect($responses)->map(fn($response, $index) => [
        'request' => $requests[$index],
        'available' => $response->successful() ? $response->json()['slots'] : []
    ]);
}
```

#### Rate Limiting Management

```php
// Implement adaptive rate limiting
class AdaptiveRateLimiter
{
    private $window = 60; // 1 minute window
    private $maxRequests = 100;
    
    public function attempt(callable $callback)
    {
        $key = "calcom:rate_limit:" . now()->format('Y-m-d-H-i');
        $attempts = Redis::incr($key);
        
        if ($attempts == 1) {
            Redis::expire($key, $this->window);
        }
        
        if ($attempts > $this->maxRequests) {
            $waitTime = $this->window - (time() % $this->window);
            sleep($waitTime);
            return $this->attempt($callback); // Retry
        }
        
        return $callback();
    }
}
```

## Maintenance Procedures

### Weekly Maintenance Tasks

```bash
#!/bin/bash
# weekly-calcom-maintenance.sh

echo "Starting Cal.com weekly maintenance..."

# 1. Clean up old logs
find /var/www/api-gateway/storage/logs -name "calcom*.log" -mtime +7 -delete

# 2. Optimize database tables
mysql -u root -p askproai_db -e "OPTIMIZE TABLE appointments, calcom_event_types, webhook_events;"

# 3. Clear orphaned cache entries
php artisan cache:prune-stale-tags

# 4. Reconcile booking statuses
php artisan calcom:reconcile-bookings --week

# 5. Generate weekly report
php artisan calcom:weekly-report --email=admin@askproai.de

echo "Weekly maintenance completed."
```

### Monthly Maintenance Tasks

```bash
#!/bin/bash
# monthly-calcom-maintenance.sh

echo "Starting Cal.com monthly maintenance..."

# 1. Full event type re-sync
php artisan calcom:sync-event-types --all --force

# 2. Archive old webhook logs
php artisan calcom:archive-webhooks --months=3

# 3. Clean up failed jobs older than 30 days
php artisan queue:prune-failed --hours=720

# 4. Analyze and report on API usage
php artisan calcom:usage-report --month

# 5. Review and rotate API keys if needed
php artisan calcom:key-rotation-check

echo "Monthly maintenance completed."
```

### Database Maintenance

```sql
-- Monthly cleanup queries
-- Remove orphaned webhook events
DELETE we FROM webhook_events we
LEFT JOIN appointments a ON we.reference_id = a.id
WHERE we.created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
  AND a.id IS NULL;

-- Archive old appointments
INSERT INTO appointments_archive
SELECT * FROM appointments 
WHERE start_time < DATE_SUB(NOW(), INTERVAL 1 YEAR)
  AND status IN ('completed', 'cancelled');

-- Clean up duplicate event types
DELETE e1 FROM calcom_event_types e1
INNER JOIN calcom_event_types e2
WHERE e1.id > e2.id
  AND e1.calcom_event_type_id = e2.calcom_event_type_id
  AND e1.company_id = e2.company_id;
```

## Backup Procedures

### Daily Backup Configuration

```bash
# /etc/cron.d/calcom-backup
# Daily Cal.com data backup at 2 AM
0 2 * * * root /usr/local/bin/backup-calcom-data.sh
```

### Backup Script

```bash
#!/bin/bash
# backup-calcom-data.sh

BACKUP_DIR="/var/backups/calcom"
DATE=$(date +%Y%m%d)
DB_NAME="askproai_db"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup Cal.com related tables
mysqldump -u root -p$DB_PASSWORD $DB_NAME \
  appointments \
  calcom_event_types \
  calcom_bookings \
  webhook_events \
  > $BACKUP_DIR/calcom_backup_$DATE.sql

# Backup configuration files
tar -czf $BACKUP_DIR/calcom_config_$DATE.tar.gz \
  /var/www/api-gateway/config/calcom*.php \
  /var/www/api-gateway/.env

# Keep only last 30 days of backups
find $BACKUP_DIR -name "calcom_*" -mtime +30 -delete

# Upload to S3 (optional)
aws s3 cp $BACKUP_DIR/calcom_backup_$DATE.sql s3://askproai-backups/calcom/
```

### Restore Procedures

```bash
# Restore from backup
mysql -u root -p askproai_db < /var/backups/calcom/calcom_backup_20250115.sql

# Verify restoration
php artisan calcom:verify-data --date=2025-01-15
```

## Security Operations

### API Key Rotation

```bash
# Check API key age
php artisan calcom:key-age-report

# Rotate keys older than 90 days
php artisan calcom:rotate-keys --age=90 --notify

# Update keys in vault
php artisan calcom:export-keys --encrypt
```

### Security Audit Commands

```bash
# Weekly security audit
php artisan calcom:security-audit

# Check for exposed sensitive data
grep -r "cal_live" /var/www/api-gateway/storage/logs/

# Verify webhook signatures
php artisan calcom:verify-webhook-signatures --last=100
```

## Disaster Recovery

### Service Degradation Procedures

```php
// Enable degraded mode
Cache::put('calcom:degraded_mode', true, now()->addHours(4));

// In services
if (Cache::get('calcom:degraded_mode')) {
    // Use cached data only
    // Queue bookings for later processing
    // Show warning to users
}
```

### Emergency Contacts

```yaml
Cal.com Support:
  Email: enterprise-support@cal.com
  Priority: Use account #ASK-PRO-AI-001
  
Internal Escalation:
  Level 1: System Admin (admin@askproai.de)
  Level 2: Technical Lead (+49 30 12345678)
  Level 3: CTO (emergency@askproai.de)
```

## Reporting

### Daily Reports

Generated automatically and sent to configured recipients:

```php
// Daily report includes:
- Total bookings created
- Failed booking attempts
- API performance metrics
- Webhook processing stats
- Error summary
- Circuit breaker events
```

### Weekly Analytics

```sql
-- Weekly booking analytics
SELECT 
    DATE(created_at) as booking_date,
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_processing_time
FROM appointments
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY booking_date DESC;
```

### Monthly Executive Summary

```bash
# Generate executive summary
php artisan calcom:executive-report --month=2025-01 --format=pdf

# Contents:
# - Total bookings and revenue
# - System availability percentage  
# - Top performing event types
# - Integration health metrics
# - Recommendations for optimization
```

## Troubleshooting Escalation

### Level 1: Automated Recovery
- Circuit breaker resets
- Cache clearing
- Webhook replay
- Automatic retries

### Level 2: Manual Intervention
- Force sync event types
- Manual booking creation
- Database reconciliation
- API key rotation

### Level 3: Engineering Support
- Code patches
- Infrastructure scaling
- Cal.com API issues
- Data corruption recovery

## Performance Benchmarks

### Target Metrics
- API Response Time: < 500ms (p95)
- Booking Success Rate: > 99%
- Webhook Processing: < 5s
- Daily Availability: > 99.9%
- Cache Hit Rate: > 80%

### Monitoring SLAs
- Health checks: Every 1 minute
- Performance metrics: Every 5 minutes
- Full sync: Daily
- Backup verification: Weekly

## Quick Reference Card

```bash
# Emergency Commands
php artisan calcom:emergency-stop      # Stop all Cal.com operations
php artisan calcom:emergency-start     # Resume operations
php artisan calcom:fallback-mode       # Enable manual booking mode

# Diagnostic Commands  
php artisan calcom:diagnose            # Run full diagnostics
php artisan calcom:test-all            # Test all integrations
php artisan calcom:status              # Quick status check

# Recovery Commands
php artisan calcom:recover             # Automatic recovery
php artisan calcom:rebuild-cache       # Rebuild all caches
php artisan calcom:reset-circuit       # Reset circuit breaker
```

## Documentation Updates

This operations manual should be reviewed and updated:
- Weekly: Command outputs and scripts
- Monthly: Procedures and checklists
- Quarterly: Full manual review
- Annually: Complete rewrite/restructure