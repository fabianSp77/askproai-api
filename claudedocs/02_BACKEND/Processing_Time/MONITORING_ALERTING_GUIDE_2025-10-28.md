# Processing Time - Monitoring & Alerting Guide

**Date**: 2025-10-28
**Status**: ✅ **PRODUCTION READY**
**Version**: 1.0

---

## 📋 Overview

Comprehensive monitoring and alerting strategy for Processing Time / Split Appointments feature. Covers metrics, log patterns, health checks, and incident response.

---

## 🎯 Key Metrics to Monitor

### 1. Phase Creation Success Rate

**What**: Percentage of appointments with processing time that successfully create phases
**Target**: >99%
**Alert Threshold**: <95%
**Critical**: <90%

**Query** (Laravel Tinker):
```php
$total = App\Models\Appointment::whereHas('service', function($q) {
    $q->where('has_processing_time', true);
})->whereDate('created_at', today())->count();

$withPhases = App\Models\Appointment::whereHas('service', function($q) {
    $q->where('has_processing_time', true);
})->whereHas('phases')->whereDate('created_at', today())->count();

$successRate = ($withPhases / $total) * 100;
echo "Phase Creation Success Rate: {$successRate}%\n";
```

**SQL Query**:
```sql
SELECT
    COUNT(*) as total_processing_time_appointments,
    SUM(CASE WHEN has_phases THEN 1 ELSE 0 END) as with_phases,
    ROUND(SUM(CASE WHEN has_phases THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate_percent
FROM (
    SELECT
        a.id,
        EXISTS(SELECT 1 FROM appointment_phases WHERE appointment_id = a.id) as has_phases
    FROM appointments a
    INNER JOIN services s ON a.service_id = s.id
    WHERE s.has_processing_time = 1
        AND DATE(a.created_at) = CURDATE()
) subquery;
```

---

### 2. Average Phase Creation Time

**What**: Time taken to create phases for appointments
**Target**: <50ms
**Alert Threshold**: >200ms
**Critical**: >500ms

**Log Pattern**:
```bash
# Extract phase creation times from logs
grep "AppointmentPhaseObserver: Phases created" storage/logs/laravel.log | \
    grep -oP '(?<=duration:)\d+' | \
    awk '{sum+=$1; n++} END {print "Average: " sum/n " ms"}'
```

**Implementation** (Add to Observer):
```php
// In AppointmentPhaseObserver::created()
$startTime = microtime(true);
$phases = $this->phaseService->createPhasesForAppointment($appointment);
$duration = (microtime(true) - $startTime) * 1000; // Convert to ms

Log::info('AppointmentPhaseObserver: Phases created', [
    'appointment_id' => $appointment->id,
    'phases_count' => count($phases),
    'duration_ms' => round($duration, 2),
]);
```

---

### 3. Cache Hit Rate (Availability)

**What**: Percentage of availability requests served from cache vs. Cal.com API
**Target**: >80%
**Alert Threshold**: <60%
**Critical**: <40%

**Log Pattern**:
```bash
# Count cache hits vs misses
echo "Cache Hits:"
grep "week_availability.*:pt_" storage/logs/laravel.log | grep "from cache" | wc -l

echo "Cache Misses (Cal.com API calls):"
grep "week_availability.*:pt_" storage/logs/laravel.log | grep "Cal.com API response" | wc -l
```

**Redis Query**:
```bash
redis-cli --scan --pattern "week_availability:*:pt_*" | wc -l
```

---

### 4. Cal.com Sync Success Rate

**What**: Percentage of Processing Time appointments successfully synced to Cal.com
**Target**: >99%
**Alert Threshold**: <95%
**Critical**: <90%

**Log Pattern**:
```bash
# Sync successes
grep "SyncAppointmentToCalcomJob.*Processing Time" storage/logs/laravel.log | \
    grep -c "success"

# Sync failures
grep "SyncAppointmentToCalcomJob.*Processing Time" storage/logs/laravel.log | \
    grep -c "failed\|error"
```

**Database Query**:
```sql
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN sync_status = 'synced' THEN 1 ELSE 0 END) as synced,
    SUM(CASE WHEN sync_status = 'error' THEN 1 ELSE 0 END) as errors,
    ROUND(SUM(CASE WHEN sync_status = 'synced' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
FROM appointments a
INNER JOIN services s ON a.service_id = s.id
WHERE s.has_processing_time = 1
    AND DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY);
```

---

### 5. Phase Distribution (Quality Check)

**What**: Distribution of phase types created
**Expected**: ~33% initial, ~33% processing, ~33% final (varies by service config)
**Alert**: Significant deviation (>20% from expected)

**Query**:
```php
$distribution = App\Models\AppointmentPhase::selectRaw('
    phase_type,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointment_phases), 2) as percentage
')->whereDate('created_at', today())->groupBy('phase_type')->get();
```

---

### 6. Overlapping Bookings (Feature Validation)

**What**: Number of appointments successfully booked during processing phases
**Target**: >0 (proves feature works)
**Quality**: Increasing trend

**Query**:
```sql
-- Find appointments booked during processing phases
SELECT
    DATE(a2.starts_at) as booking_date,
    COUNT(*) as overlapping_appointments,
    GROUP_CONCAT(DISTINCT s.name) as services_used
FROM appointments a1
INNER JOIN appointment_phases ph ON a1.id = ph.appointment_id
INNER JOIN appointments a2 ON a1.staff_id = a2.staff_id
    AND a2.starts_at >= ph.start_time
    AND a2.starts_at < ph.end_time
    AND a2.id != a1.id
INNER JOIN services s ON a2.service_id = s.id
WHERE ph.staff_required = 0  -- Processing phase (staff available)
    AND DATE(a1.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(a2.starts_at)
ORDER BY booking_date DESC;
```

---

## 📊 Monitoring Dashboards

### 1. Real-Time Health Check

**Script**: `scripts/monitoring/processing-time-health-check.sh`

```bash
#!/bin/bash
# Processing Time Feature Health Check
# Run: ./scripts/monitoring/processing-time-health-check.sh

echo "=========================================="
echo "Processing Time Feature - Health Check"
echo "=========================================="
echo ""

# Check feature flag status
echo "1. Feature Flag Status:"
php artisan tinker --execute="
    echo 'Master Toggle: ' . (config('features.processing_time_enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
    echo 'Service Whitelist: ' . (empty(config('features.processing_time_service_whitelist')) ? 'EMPTY (all allowed)' : count(config('features.processing_time_service_whitelist')) . ' services') . PHP_EOL;
    echo 'Company Whitelist: ' . (empty(config('features.processing_time_company_whitelist')) ? 'EMPTY (all allowed)' : implode(',', config('features.processing_time_company_whitelist'))) . PHP_EOL;
    echo 'Auto Phases: ' . (config('features.processing_time_auto_create_phases') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
"
echo ""

# Check today's metrics
echo "2. Today's Metrics:"
php artisan tinker --execute="
    \$total = App\\Models\\Appointment::whereHas('service', function(\$q) {
        \$q->where('has_processing_time', true);
    })->whereDate('created_at', today())->count();

    \$withPhases = App\\Models\\Appointment::whereHas('service', function(\$q) {
        \$q->where('has_processing_time', true);
    })->whereHas('phases')->whereDate('created_at', today())->count();

    echo 'Total Processing Time Appointments: ' . \$total . PHP_EOL;
    echo 'With Phases Created: ' . \$withPhases . PHP_EOL;
    if (\$total > 0) {
        echo 'Success Rate: ' . round((\$withPhases / \$total) * 100, 2) . '%' . PHP_EOL;
    }
"
echo ""

# Check recent errors
echo "3. Recent Errors (Last 24h):"
grep -c "AppointmentPhaseObserver: Failed" storage/logs/laravel.log 2>/dev/null || echo "0"
echo ""

# Check cache status
echo "4. Cache Status:"
redis-cli --scan --pattern "week_availability:*:pt_*" | wc -l | xargs echo "Cached availability entries: "
echo ""

echo "=========================================="
echo "Health Check Complete"
echo "=========================================="
```

---

### 2. Weekly Performance Report

**Script**: `scripts/monitoring/processing-time-weekly-report.sh`

```bash
#!/bin/bash
# Processing Time Feature - Weekly Performance Report
# Run: ./scripts/monitoring/processing-time-weekly-report.sh

echo "=========================================="
echo "Processing Time - Weekly Report"
echo "Date: $(date '+%Y-%m-%d')"
echo "=========================================="
echo ""

echo "Phase Creation Performance:"
php artisan tinker --execute="
    \$startDate = now()->subDays(7);
    \$total = App\\Models\\Appointment::whereHas('service', function(\$q) {
        \$q->where('has_processing_time', true);
    })->where('created_at', '>=', \$startDate)->count();

    \$withPhases = App\\Models\\Appointment::whereHas('service', function(\$q) {
        \$q->where('has_processing_time', true);
    })->whereHas('phases')->where('created_at', '>=', \$startDate)->count();

    echo 'Total Processing Time Appointments: ' . \$total . PHP_EOL;
    echo 'With Phases: ' . \$withPhases . PHP_EOL;
    if (\$total > 0) {
        echo 'Success Rate: ' . round((\$withPhases / \$total) * 100, 2) . '%' . PHP_EOL;
    }

    echo PHP_EOL . 'Phase Distribution:' . PHP_EOL;
    \$distribution = App\\Models\\AppointmentPhase::selectRaw('
        phase_type,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointment_phases WHERE created_at >= ?), 2) as percentage
    ', [\$startDate])
    ->where('created_at', '>=', \$startDate)
    ->groupBy('phase_type')
    ->get();

    foreach (\$distribution as \$phase) {
        echo '  ' . ucfirst(\$phase->phase_type) . ': ' . \$phase->count . ' (' . \$phase->percentage . '%)' . PHP_EOL;
    }
"
echo ""

echo "Services Using Processing Time:"
php artisan tinker --execute="
    \$services = App\\Models\\Service::where('has_processing_time', true)->get(['id', 'name', 'company_id']);
    echo 'Total Services: ' . \$services->count() . PHP_EOL;
    foreach (\$services as \$service) {
        \$appointmentCount = \$service->appointments()->where('created_at', '>=', now()->subDays(7))->count();
        echo '  ' . \$service->name . ' (Company ' . \$service->company_id . '): ' . \$appointmentCount . ' appointments' . PHP_EOL;
    }
"
echo ""

echo "=========================================="
```

---

## 🚨 Alert Configuration

### Log-Based Alerts

**Tool**: Any log aggregation service (Papertrail, Loggly, CloudWatch, etc.)

**Alert Rules**:

1. **Phase Creation Failures**
   - Pattern: `AppointmentPhaseObserver: Failed`
   - Threshold: >5 occurrences in 1 hour
   - Severity: HIGH
   - Action: Investigate immediately

2. **Observer Disabled**
   - Pattern: `processing_time_auto_create_phases.*false`
   - Threshold: 1 occurrence
   - Severity: INFO
   - Action: Verify intentional (testing mode)

3. **Cache Miss Spike**
   - Pattern: `Cal.com API response received` with `:pt_` in cache_key
   - Threshold: >100 in 5 minutes
   - Severity: MEDIUM
   - Action: Check cache service health

4. **Cal.com Sync Failures**
   - Pattern: `SyncAppointmentToCalcomJob.*failed|error` with processing time context
   - Threshold: >10 occurrences in 1 hour
   - Severity: HIGH
   - Action: Check Cal.com API status

---

### Database-Based Alerts

**Tool**: Scheduled Laravel command + monitoring service

**Command**: `app/Console/Commands/MonitorProcessingTimeHealth.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorProcessingTimeHealth extends Command
{
    protected $signature = 'monitor:processing-time-health';
    protected $description = 'Monitor Processing Time feature health metrics';

    public function handle(): int
    {
        // Calculate success rate
        $total = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })->whereDate('created_at', today())->count();

        $withPhases = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })->whereHas('phases')->whereDate('created_at', today())->count();

        $successRate = $total > 0 ? ($withPhases / $total) * 100 : 100;

        // Alert if below threshold
        if ($successRate < 95 && $total > 10) {
            Log::warning('Processing Time: Low phase creation success rate', [
                'success_rate' => $successRate,
                'total_appointments' => $total,
                'with_phases' => $withPhases,
                'alert_level' => 'HIGH',
            ]);

            $this->error("ALERT: Phase creation success rate is {$successRate}% (threshold: 95%)");
            return 1;
        }

        // Check for orphaned appointments (processing time service but no phases)
        $orphaned = Appointment::whereHas('service', function($q) {
            $q->where('has_processing_time', true);
        })
        ->whereDoesntHave('phases')
        ->whereDate('created_at', today())
        ->get();

        if ($orphaned->count() > 0) {
            Log::info('Processing Time: Found orphaned appointments without phases', [
                'count' => $orphaned->count(),
                'appointment_ids' => $orphaned->pluck('id')->toArray(),
            ]);
        }

        $this->info("Processing Time Health Check: OK");
        $this->info("Success Rate: {$successRate}% ({$withPhases}/{$total})");

        return 0;
    }
}
```

**Cron Schedule** (in `app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule): void
{
    // Run every hour during business hours
    $schedule->command('monitor:processing-time-health')
        ->hourly()
        ->between('8:00', '20:00')
        ->timezone('Europe/Berlin');
}
```

---

## 🔍 Diagnostic Commands

### 1. Check Service Configuration

```bash
# List all services with processing time
php artisan tinker
>>> App\Models\Service::where('has_processing_time', true)->get(['id', 'name', 'initial_duration', 'processing_duration', 'final_duration'])->toArray();
```

### 2. Check Recent Phase Creation

```bash
# Last 10 appointments with phases
php artisan tinker
>>> App\Models\Appointment::whereHas('phases')->latest()->take(10)->with('phases')->get()->map(function($a) {
    return [
        'id' => $a->id,
        'service' => $a->service->name,
        'phases' => $a->phases->count(),
        'created' => $a->created_at->diffForHumans(),
    ];
});
```

### 3. Cache Inspection

```bash
# View all Processing Time cache keys
redis-cli --scan --pattern "week_availability:*:pt_*"

# Check specific cache key
redis-cli GET "week_availability:1:service-uuid:2025-10-28:pt_1"

# Clear all Processing Time caches
redis-cli --scan --pattern "week_availability:*:pt_*" | xargs redis-cli DEL
```

### 4. Validate Phase Integrity

```bash
# Check for appointments with phases where service no longer has processing time
php artisan tinker
>>> App\Models\Appointment::whereHas('phases')->whereHas('service', function($q) {
    $q->where('has_processing_time', false);
})->count();
```

---

## 📈 Performance Benchmarks

### Baseline Metrics (Expected)

| Metric | Value | Notes |
|--------|-------|-------|
| Phase creation time | 20-50ms | 3 DB inserts + validation |
| Cache hit rate | 80-90% | With 60s TTL |
| Cal.com sync time | 200-500ms | Network dependent |
| Observer overhead | <10ms | Event hook processing |
| Database query time | <5ms | With proper indexes |

### Load Testing

**Scenario 1: Normal Load**
- 100 appointments/hour with processing time
- Expected: 0 failures, <50ms avg phase creation

**Scenario 2: Peak Load**
- 500 appointments/hour with processing time
- Expected: <1% failures, <100ms avg phase creation

**Scenario 3: Cache Cold Start**
- All availability caches cleared
- Expected: Temporary spike in Cal.com API calls, auto-recovery within 5 minutes

---

## 🛠️ Troubleshooting Runbook

### Issue: Phases Not Created

**Symptoms**:
- Appointments exist without phases
- Success rate <95%

**Diagnosis**:
```bash
# 1. Check feature flags
php artisan tinker
>>> config('features.processing_time_auto_create_phases');

# 2. Check service configuration
>>> $service = App\Models\Service::find('uuid');
>>> $service->hasProcessingTime();

# 3. Check recent errors
tail -f storage/logs/laravel.log | grep "AppointmentPhase"

# 4. Check observer is registered
>>> app(Illuminate\Contracts\Events\Dispatcher::class)->hasListeners('eloquent.created: App\Models\Appointment');
```

**Resolution**:
1. Enable auto-create flag if disabled
2. Verify service whitelist/company whitelist
3. Check service phase duration configuration
4. Manually create phases: `php artisan tinker >>> app(App\Services\AppointmentPhaseCreationService::class)->createPhasesForAppointment($appointment)`

---

### Issue: High Cache Miss Rate

**Symptoms**:
- Cache hit rate <60%
- Increased Cal.com API calls
- Slower availability lookups

**Diagnosis**:
```bash
# 1. Check Redis connectivity
redis-cli PING

# 2. Check cache TTL
redis-cli TTL "week_availability:1:uuid:2025-10-28:pt_1"

# 3. Check cache key pattern
redis-cli --scan --pattern "week_availability:*:pt_*" | head -10
```

**Resolution**:
1. Restart Redis if connectivity issues
2. Check Laravel cache configuration (`config/cache.php`)
3. Verify cache driver in `.env` (should be `redis`)
4. Clear and rebuild cache: `php artisan cache:clear`

---

### Issue: Cal.com Sync Failures

**Symptoms**:
- Sync success rate <95%
- `sync_status = 'error'` in appointments table

**Diagnosis**:
```bash
# 1. Check Cal.com API health
curl -X GET "https://api.cal.com/v2/health" -H "Authorization: Bearer YOUR_TOKEN"

# 2. Check recent sync errors
tail -f storage/logs/laravel.log | grep "SyncAppointmentToCalcom.*error"

# 3. Check appointments with sync errors
php artisan tinker
>>> App\Models\Appointment::whereHas('service', function($q) {
    $q->where('has_processing_time', true);
})->where('sync_status', 'error')->latest()->take(5)->get(['id', 'sync_error'])->toArray();
```

**Resolution**:
1. Check Cal.com API status
2. Verify API credentials in `.env`
3. Retry failed syncs: `php artisan queue:retry all`
4. Check rate limits: Cal.com may throttle requests

---

## 📞 Escalation Path

### Level 1: Automated Alerts
- Monitoring service detects anomaly
- Logs alert with context
- Attempts auto-recovery if configured

### Level 2: Developer On-Call
- Review logs and metrics
- Run diagnostic commands
- Implement temporary fixes (feature flag toggle, cache clear, etc.)

### Level 3: Feature Toggle
- Disable feature temporarily if critical
- Investigate root cause
- Implement permanent fix
- Re-enable with monitoring

### Level 4: Rollback
- If feature causes system-wide issues
- Disable all Processing Time appointments
- Migrate existing appointments to regular format
- Plan controlled re-launch

---

## ✅ Monitoring Checklist

### Daily (Automated)
- [ ] Phase creation success rate >99%
- [ ] No critical errors in logs
- [ ] Cache hit rate >80%
- [ ] Cal.com sync success rate >99%

### Weekly (Manual Review)
- [ ] Review weekly performance report
- [ ] Check for orphaned appointments
- [ ] Validate phase distribution
- [ ] Review overlapping bookings (feature validation)
- [ ] Check service adoption rate

### Monthly (Strategic Review)
- [ ] Analyze performance trends
- [ ] Review customer feedback
- [ ] Plan optimizations
- [ ] Update documentation
- [ ] Review rollout progress

---

**Last Updated**: 2025-10-28
**Next Review**: 2025-11-28
**Monitoring Status**: ✅ Production Ready
