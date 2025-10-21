# Quick Reference: Data Consistency Prevention Architecture

**Version**: 1.0
**Date**: 2025-10-20

---

## File Locations

### Service Classes
```
app/Services/Validation/PostBookingValidationService.php
app/Services/Monitoring/DataConsistencyMonitor.php
app/Services/Resilience/AppointmentBookingCircuitBreaker.php
```

### Database Migrations
```
database/migrations/2025_10_20_000001_create_data_consistency_tables.php
database/migrations/2025_10_20_000002_create_data_consistency_triggers.php
```

### Documentation
```
claudedocs/07_ARCHITECTURE/APPOINTMENT_DATA_CONSISTENCY_PREVENTION_ARCHITECTURE_2025_10_20.md
```

---

## Quick Integration Guide

### 1. Add Post-Booking Validation

**In AppointmentCreationService::createLocalRecord()**

```php
use App\Services\Validation\PostBookingValidationService;

public function createLocalRecord(...): Appointment
{
    // ... existing appointment creation ...

    $appointment->save();

    // ADD THIS: Post-validation
    $validation = app(PostBookingValidationService::class)
        ->validateAppointmentCreation($call, $appointment->id, $calcomBookingId);

    if (!$validation->success) {
        app(PostBookingValidationService::class)->rollbackOnFailure($call, $validation->reason);
        throw new \Exception("Appointment validation failed: {$validation->reason}");
    }

    return $appointment;
}
```

### 2. Add Circuit Breaker Protection

**In AppointmentCreationService::createFromCall()**

```php
use App\Services\Resilience\AppointmentBookingCircuitBreaker;
use App\Services\Resilience\CircuitOpenException;

public function createFromCall(Call $call, array $bookingDetails): ?Appointment
{
    $service = $this->findService($bookingDetails, $call->company_id);
    $circuitKey = "appointment_booking:service:{$service->id}";

    try {
        return app(AppointmentBookingCircuitBreaker::class)->executeWithCircuitBreaker(
            $circuitKey,
            fn() => $this->performBooking($call, $bookingDetails, $service)
        );
    } catch (CircuitOpenException $e) {
        Log::warning('Circuit breaker OPEN', ['service_id' => $service->id]);
        $this->queueForManualReview($call, $bookingDetails);
        return null;
    }
}
```

### 3. Run Scheduled Monitoring

**In app/Console/Kernel.php**

```php
protected function schedule(Schedule $schedule): void
{
    // Run inconsistency detection every 15 minutes
    $schedule->call(function () {
        $summary = app(\App\Services\Monitoring\DataConsistencyMonitor::class)
            ->detectInconsistencies();

        Log::info('Scheduled consistency check', $summary);
    })->everyFifteenMinutes();

    // Generate daily report
    $schedule->call(function () {
        $report = app(\App\Services\Monitoring\DataConsistencyMonitor::class)
            ->generateDailyReport();

        // Send to monitoring system or email
        Log::info('Daily consistency report', $report);
    })->dailyAt('08:00');
}
```

---

## Database Schema

### Tables Created

**circuit_breaker_states**
- Stores circuit breaker state (closed/open/half_open)
- Tracks failure/success counts
- Records timestamps for state transitions

**data_consistency_alerts**
- Logs detected inconsistencies
- Tracks auto-correction status
- Enables reporting and analysis

**manual_review_queue**
- Queues failed bookings for manual review
- Priority-based assignment
- Resolution tracking

### Triggers Created

1. **set_default_call_direction**: Auto-set direction='inbound' if NULL
2. **sync_customer_link_status**: Update link status when customer_id changes
3. **validate_session_outcome_consistency**: Auto-correct session_outcome vs appointment_made mismatches
4. **sync_appointment_link_status**: Update call flags when appointment is created/deleted

---

## Usage Examples

### Check Single Call for Inconsistencies

```php
use App\Services\Monitoring\DataConsistencyMonitor;

$monitor = app(DataConsistencyMonitor::class);
$inconsistencies = $monitor->checkCall($call);

if (!empty($inconsistencies)) {
    Log::warning('Inconsistencies found', ['call_id' => $call->id, 'issues' => $inconsistencies]);
}
```

### Manually Trigger Validation

```php
use App\Services\Validation\PostBookingValidationService;

$validator = app(PostBookingValidationService::class);
$result = $validator->validateAppointmentCreation($call, $appointmentId, $calcomBookingId);

if (!$result->success) {
    // Handle failure
    $validator->rollbackOnFailure($call, $result->reason);
}
```

### Check Circuit Breaker Status

```php
use App\Services\Resilience\AppointmentBookingCircuitBreaker;

$circuitBreaker = app(AppointmentBookingCircuitBreaker::class);
$stats = $circuitBreaker->getStatistics('appointment_booking:service:123');

/*
Returns:
[
    'circuit_key' => 'appointment_booking:service:123',
    'state' => 'closed',
    'failure_count' => 0,
    'success_count' => 0,
    'last_failure_at' => null,
    'opened_at' => null,
    'closed_at' => null
]
*/
```

### Retry Operation with Backoff

```php
use App\Services\Validation\PostBookingValidationService;

$validator = app(PostBookingValidationService::class);

$result = $validator->retryWithBackoff(
    fn() => $this->createAppointment(...),
    maxAttempts: 3
);
```

---

## Deployment Steps

### 1. Run Migrations

```bash
php artisan migrate
```

This will create:
- `circuit_breaker_states` table
- `data_consistency_alerts` table
- `manual_review_queue` table
- Database triggers for auto-correction

### 2. Verify Triggers

```sql
-- Check triggers exist
SELECT trigger_name, event_manipulation, event_object_table
FROM information_schema.triggers
WHERE trigger_schema = 'public'
  AND event_object_table IN ('calls', 'appointments');
```

Expected output:
```
before_insert_call_set_direction          | INSERT | calls
before_update_call_sync_customer_link     | UPDATE | calls
before_insert_or_update_call_validate_outcome | INSERT, UPDATE | calls
after_appointment_change_sync_call        | INSERT, DELETE | appointments
```

### 3. Register Service Providers

**In app/Providers/AppServiceProvider.php**

```php
public function register(): void
{
    $this->app->singleton(PostBookingValidationService::class);
    $this->app->singleton(DataConsistencyMonitor::class);
    $this->app->singleton(AppointmentBookingCircuitBreaker::class);
}
```

### 4. Setup Scheduled Tasks

Add to `app/Console/Kernel.php` (see section above)

### 5. Configure Logging Channel

**In config/logging.php**

```php
'channels' => [
    // ... existing channels ...

    'consistency' => [
        'driver' => 'single',
        'path' => storage_path('logs/data-consistency.log'),
        'level' => 'debug',
    ],
],
```

---

## Monitoring Queries

### Check Current Data Consistency

```sql
-- Session outcome mismatches
SELECT COUNT(*) as mismatches
FROM calls
WHERE session_outcome = 'appointment_booked'
  AND appointment_made = false
  AND created_at >= NOW() - INTERVAL '24 hours';

-- Missing appointments
SELECT COUNT(*) as missing
FROM calls c
LEFT JOIN appointments a ON a.call_id = c.id
WHERE c.appointment_made = true
  AND a.id IS NULL
  AND c.created_at >= NOW() - INTERVAL '24 hours';

-- Circuit breaker status
SELECT circuit_key, state, failure_count, last_failure_at
FROM circuit_breaker_states
WHERE state != 'closed';
```

### View Recent Alerts

```sql
SELECT
    alert_type,
    severity,
    COUNT(*) as count,
    SUM(CASE WHEN auto_corrected THEN 1 ELSE 0 END) as auto_corrected_count
FROM data_consistency_alerts
WHERE detected_at >= NOW() - INTERVAL '24 hours'
GROUP BY alert_type, severity
ORDER BY count DESC;
```

### Manual Review Queue

```sql
SELECT
    mrq.id,
    mrq.call_id,
    mrq.reason,
    mrq.priority,
    mrq.status,
    c.retell_call_id,
    c.from_number,
    mrq.created_at
FROM manual_review_queue mrq
JOIN calls c ON c.id = mrq.call_id
WHERE mrq.status = 'pending'
ORDER BY mrq.priority ASC, mrq.created_at ASC
LIMIT 10;
```

---

## Troubleshooting

### Circuit Breaker Stuck Open

```php
// Manually close circuit
use App\Services\Resilience\AppointmentBookingCircuitBreaker;

$circuitBreaker = app(AppointmentBookingCircuitBreaker::class);
// Note: No public method to force close - this is by design
// Let it auto-recover via HALF_OPEN state

// Check status
$stats = $circuitBreaker->getStatistics('appointment_booking:service:123');
```

### Too Many Alerts

```php
// Adjust throttle time in DataConsistencyMonitor.php
private const ALERT_THROTTLE_MINUTES = 15; // Increase from 5 to 15
```

### Validation Taking Too Long

```php
// Increase timeout in PostBookingValidationService.php
private const MAX_APPOINTMENT_AGE_SECONDS = 600; // Increase from 300 to 600
```

### Trigger Not Firing

```sql
-- Check trigger exists
SELECT * FROM information_schema.triggers
WHERE trigger_name = 'before_insert_call_set_direction';

-- Test manually
INSERT INTO calls (retell_call_id, status, from_number, to_number, company_id)
VALUES ('test-123', 'ongoing', '+49123456789', '+49987654321', 1);

-- Check direction was set
SELECT direction FROM calls WHERE retell_call_id = 'test-123';
-- Should return 'inbound'
```

---

## Performance Impact

### Expected Overhead

- **Post-Validation**: +50-100ms per appointment creation
- **Circuit Breaker Check**: +1-5ms per booking attempt
- **Database Triggers**: <1ms per INSERT/UPDATE
- **Monitoring Detection**: Runs async, no request impact

### Optimization Tips

1. **Redis for Circuit Breaker**: Already implemented, fast state checks
2. **Async Monitoring**: Run consistency checks after response sent
3. **Alert Throttling**: Prevents log flooding (5-minute default)
4. **Index Coverage**: All queries use proper indexes

---

## Success Metrics

### Target KPIs
```
Consistency Rate:     ≥ 99.5%
Validation Latency:   ≤ 100ms (p95)
Circuit Recovery:     ≤ 60 seconds
Alert Response:       ≤ 5 minutes
Auto-Correction:      ≥ 90%
False Positives:      ≤ 1%
```

### How to Measure

```sql
-- Daily consistency rate
SELECT
    (COUNT(*) FILTER (WHERE
        (session_outcome = 'appointment_booked' AND appointment_made = true)
        OR (session_outcome != 'appointment_booked' AND appointment_made = false)
    )::float / COUNT(*)) * 100 as consistency_rate_pct
FROM calls
WHERE created_at >= NOW() - INTERVAL '1 day'
  AND session_outcome IS NOT NULL;
```

---

## Support & Maintenance

### Log Locations
```
storage/logs/laravel.log                # General logs
storage/logs/data-consistency.log       # Consistency-specific logs
```

### Key Log Patterns
```bash
# Find validation failures
grep "Post-booking validation failed" storage/logs/laravel.log

# Find circuit breaker opens
grep "Circuit breaker OPENING" storage/logs/laravel.log

# Find auto-corrections
grep "Auto-corrected" storage/logs/data-consistency.log
```

### Contact
For questions or issues, refer to the full architecture document:
`claudedocs/07_ARCHITECTURE/APPOINTMENT_DATA_CONSISTENCY_PREVENTION_ARCHITECTURE_2025_10_20.md`
