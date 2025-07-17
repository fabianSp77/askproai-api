# Cal.com Troubleshooting Guide

## Overview

This guide provides solutions to common Cal.com integration issues in AskProAI, including debugging steps, error resolutions, and recovery procedures.

## Quick Diagnostics

### Health Check Command

```bash
# Run comprehensive Cal.com health check
php artisan calcom:health-check

# Output includes:
# - API connectivity status
# - Authentication verification  
# - Event type sync status
# - Recent webhook activity
# - Circuit breaker state
# - Error statistics
```

### Common Issues Dashboard

Access the Cal.com diagnostics dashboard:
```
https://api.askproai.de/admin/calcom-diagnostics
```

## Authentication Issues

### Problem: "Invalid API Key" (401 Error)

**Symptoms:**
- All Cal.com API calls fail with 401
- "Unauthorized" errors in logs
- Circuit breaker opens frequently

**Diagnosis:**
```bash
# Check API key configuration
php artisan tinker
>>> $company = Company::find(1);
>>> $company->calcom_api_key; // Should not be null
>>> decrypt($company->calcom_api_key); // Check actual value

# Test API key directly
php artisan calcom:test-auth --company=1
```

**Solutions:**

1. **Verify API Key Format:**
   ```php
   // Correct format
   cal_live_xxxxxxxxxxxxxxxxxxxxxx
   
   // Incorrect formats
   Bearer cal_live_xxxxx  // Don't include "Bearer"
   cal_test_xxxxx         // Test keys don't work in production
   ```

2. **Update API Key:**
   ```php
   $company = Company::find(1);
   $company->update([
       'calcom_api_key' => 'cal_live_new_key_here'
   ]);
   
   // Clear cache
   Cache::forget("calcom:auth:{$company->id}");
   ```

3. **Check Environment:**
   ```bash
   # Ensure using production API
   grep CALCOM .env
   # Should show: CALCOM_V2_API_URL=https://api.cal.com/v2
   # Not: https://api.cal.com/staging/v2
   ```

### Problem: "Organization Not Found" (403 Error)

**Symptoms:**
- Authentication succeeds but requests fail
- "Forbidden" errors for team operations
- Event types don't sync

**Solutions:**

1. **Verify Organization/Team Settings:**
   ```php
   $company->update([
       'calcom_team_id' => 12345,        // Numeric team ID
       'calcom_team_slug' => 'team-slug', // URL slug
       'calcom_organization_id' => 67890  // If using orgs
   ]);
   ```

2. **Check API Key Permissions:**
   - Log into Cal.com
   - Go to Settings → API Keys
   - Ensure key has "Team" scope enabled

## Booking Failures

### Problem: "No Available Slots"

**Symptoms:**
- Availability check returns empty
- Bookings fail with "slot not available"
- Customers report no appointments available

**Diagnosis:**
```bash
# Debug availability for specific event type
php artisan calcom:debug-availability 2026361 --date=2025-01-20

# Check raw API response
php artisan calcom:raw-api GET /slots/available \
  --params='{"eventTypeId":2026361,"startTime":"2025-01-20T00:00:00Z","endTime":"2025-01-20T23:59:59Z"}'
```

**Common Causes & Solutions:**

1. **Timezone Mismatch:**
   ```php
   // Always specify timezone
   $slots = $calcomService->getAvailableSlots(
       eventTypeId: $eventTypeId,
       startDate: $startDate,
       endDate: $endDate,
       timeZone: 'Europe/Berlin' // Critical!
   );
   ```

2. **Schedule Not Configured:**
   - Check in Cal.com: Event Type → Availability
   - Ensure working hours are set
   - Verify no date overrides blocking availability

3. **Buffer Times Too Large:**
   ```php
   // Check event type settings
   $eventType = CalcomEventType::find($id);
   echo "Buffer before: {$eventType->before_event_buffer} min\n";
   echo "Buffer after: {$eventType->after_event_buffer} min\n";
   echo "Min notice: {$eventType->minimum_booking_notice} min\n";
   ```

4. **All Slots Booked:**
   ```sql
   -- Check booking density
   SELECT 
       DATE(start_time) as date,
       COUNT(*) as bookings
   FROM appointments
   WHERE calcom_event_type_id = 2026361
       AND start_time >= NOW()
       AND status = 'confirmed'
   GROUP BY DATE(start_time)
   ORDER BY date;
   ```

### Problem: "Booking Creation Failed"

**Symptoms:**
- Booking POST requests fail
- Various validation errors
- Appointments stuck in "pending" status

**Common Error Messages & Solutions:**

1. **"Invalid time slot"**
   ```php
   // Ensure slot is still available
   $isAvailable = $calcomService->verifySlotAvailable(
       $eventTypeId,
       $requestedTime
   );
   
   if (!$isAvailable) {
       // Slot taken between check and booking
       // Retry with different slot
   }
   ```

2. **"Missing required fields"**
   ```php
   // Check required custom fields
   $eventType = $calcomService->getEventType($eventTypeId);
   $requiredFields = $eventType->customInputs->where('required', true);
   
   // Ensure all required fields in responses
   $responses = [
       'name' => $customer->full_name,
       'email' => $customer->email,
       'phone' => $customer->phone_number,
       // Add all required custom fields
       ...$this->mapCustomFields($requiredFields, $formData)
   ];
   ```

3. **"Email validation failed"**
   ```php
   // Validate email format before sending
   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       Log::error('Invalid email format', ['email' => $email]);
       // Use placeholder email: noreply@askproai.de
   }
   ```

## Event Type Sync Issues

### Problem: "Event Types Not Syncing"

**Symptoms:**
- New event types don't appear
- Sync command shows 0 synced
- Old event types remain after deletion

**Diagnosis:**
```bash
# Manual sync with debug output
php artisan calcom:sync-event-types --company=1 --debug

# Check sync logs
tail -f storage/logs/calcom-sync.log

# Verify API response
php artisan calcom:raw-api GET /event-types
```

**Solutions:**

1. **Clear Sync Cache:**
   ```php
   Cache::tags(['calcom', 'event_types'])->flush();
   
   // Force sync
   php artisan calcom:sync-event-types --force
   ```

2. **Check Team Assignment:**
   ```php
   // Event types might be team-specific
   $response = $calcomService->getEventTypes([
       'teamId' => $company->calcom_team_id
   ]);
   ```

3. **Handle Deleted Event Types:**
   ```php
   // Soft delete missing event types
   $calcomIds = collect($syncedEventTypes)->pluck('id');
   
   CalcomEventType::where('company_id', $company->id)
       ->whereNotIn('calcom_event_type_id', $calcomIds)
       ->update(['is_active' => false]);
   ```

## Webhook Issues

### Problem: "Webhooks Not Received"

**Symptoms:**
- Bookings create but no webhooks
- Appointment status not updating
- No entries in webhook logs

**Diagnosis:**
```bash
# Check webhook configuration
curl https://api.askproai.de/api/webhooks/calcom \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"test": true}'

# Monitor webhook endpoint
tail -f storage/logs/webhooks.log | grep calcom
```

**Solutions:**

1. **Verify Webhook URL in Cal.com:**
   ```
   1. Log into Cal.com
   2. Settings → Webhooks
   3. Ensure URL is: https://api.askproai.de/api/webhooks/calcom
   4. Test webhook from Cal.com dashboard
   ```

2. **Check SSL Certificate:**
   ```bash
   # Cal.com requires valid SSL
   curl -I https://api.askproai.de
   # Should show valid certificate
   ```

3. **Verify Webhook Secret:**
   ```php
   // In .env
   CALCOM_WEBHOOK_SECRET=matching_secret_from_calcom
   
   // Test signature
   php artisan calcom:test-webhook-signature
   ```

### Problem: "Webhook Signature Verification Failed"

**Symptoms:**
- 401 responses to webhooks
- "Invalid signature" in logs
- Webhooks work in test but not production

**Solutions:**

1. **Check Multiple Headers:**
   ```php
   // Cal.com may send signature in different headers
   $signature = $request->header('X-Cal-Signature-256')
       ?? $request->header('Cal-Signature-256')
       ?? $request->header('X-Cal-Signature')
       ?? $request->header('Cal-Signature');
   ```

2. **Handle Payload Variations:**
   ```php
   // Try with and without trailing newline
   $payload = $request->getContent();
   $signatures = [
       hash_hmac('sha256', $payload, $secret),
       hash_hmac('sha256', rtrim($payload, "\r\n"), $secret),
   ];
   ```

3. **Debug Signature Mismatch:**
   ```php
   Log::debug('Webhook signature debug', [
       'provided' => $signature,
       'expected' => hash_hmac('sha256', $payload, $secret),
       'payload_length' => strlen($payload),
       'payload_hash' => md5($payload),
   ]);
   ```

## Performance Issues

### Problem: "Slow API Responses"

**Symptoms:**
- Availability checks timeout
- Booking creation takes >5 seconds
- Circuit breaker trips frequently

**Diagnosis:**
```bash
# Check response times
php artisan calcom:performance-test

# Monitor circuit breaker
php artisan calcom:circuit-status --watch
```

**Solutions:**

1. **Enable Caching:**
   ```php
   // config/calcom-v2.php
   'cache' => [
       'enabled' => true,
       'event_types_ttl' => 300,  // 5 minutes
       'slots_ttl' => 60,         // 1 minute
   ]
   ```

2. **Optimize Queries:**
   ```php
   // Batch availability checks
   $dateRange = CarbonPeriod::create($start, $end);
   $allSlots = [];
   
   foreach ($dateRange as $date) {
       $key = "calcom:slots:{$eventTypeId}:{$date->format('Y-m-d')}";
       $allSlots[$date->format('Y-m-d')] = Cache::remember($key, 60, function() use ($date) {
           return $this->fetchSlotsForDate($date);
       });
   }
   ```

3. **Implement Request Pooling:**
   ```php
   // Concurrent requests for multiple event types
   $responses = Http::pool(fn($pool) => 
       collect($eventTypeIds)->map(fn($id) => 
           $pool->withToken($this->apiKey)
               ->get("/event-types/{$id}")
       )
   );
   ```

## Circuit Breaker Issues

### Problem: "Circuit Breaker Open"

**Symptoms:**
- All requests fail immediately
- "Circuit breaker is open" errors
- No actual API calls being made

**Diagnosis:**
```bash
# Check circuit state
php artisan calcom:circuit-status

# View circuit history
redis-cli
> HGETALL calcom:circuit:state
> HGET calcom:circuit:failures
```

**Solutions:**

1. **Manual Reset (Emergency):**
   ```bash
   php artisan calcom:circuit-reset
   
   # Or via code
   $breaker = app(CircuitBreaker::class);
   $breaker->reset('calcom');
   ```

2. **Adjust Thresholds:**
   ```php
   // config/calcom-v2.php
   'circuit_breaker' => [
       'failure_threshold' => 10,  // Increase from 5
       'timeout' => 30,           // Decrease from 60
   ]
   ```

3. **Implement Fallback:**
   ```php
   try {
       $result = $calcomService->createBooking($data);
   } catch (CircuitBreakerOpenException $e) {
       // Fallback to manual booking
       $appointment->update([
           'requires_manual_booking' => true,
           'manual_booking_reason' => 'Cal.com unavailable'
       ]);
       
       // Notify staff
       dispatch(new NotifyManualBookingRequired($appointment));
   }
   ```

## Data Integrity Issues

### Problem: "Appointment Status Mismatch"

**Symptoms:**
- AskProAI shows confirmed, Cal.com shows cancelled
- Duplicate bookings
- Missing Cal.com booking IDs

**Solutions:**

1. **Sync Appointment Status:**
   ```bash
   # Reconcile specific appointment
   php artisan calcom:sync-appointment 12345
   
   # Bulk reconciliation
   php artisan calcom:reconcile-bookings --from=2025-01-01
   ```

2. **Implement Status Mapping:**
   ```php
   class CalcomStatusMapper
   {
       const STATUS_MAP = [
           'ACCEPTED' => 'confirmed',
           'PENDING' => 'pending',
           'CANCELLED' => 'cancelled',
           'REJECTED' => 'rejected',
       ];
       
       public static function mapStatus(string $calcomStatus): string
       {
           return self::STATUS_MAP[$calcomStatus] ?? 'unknown';
       }
   }
   ```

### Problem: "Duplicate Bookings"

**Symptoms:**
- Same time slot booked twice
- Customer receives multiple confirmations
- Cal.com shows conflicts

**Solutions:**

1. **Implement Booking Lock:**
   ```php
   // Use Redis lock during booking
   $lock = Cache::lock("booking:{$eventTypeId}:{$slot}", 30);
   
   if ($lock->get()) {
       try {
           $booking = $calcomService->createBooking($data);
       } finally {
           $lock->release();
       }
   } else {
       throw new SlotUnavailableException();
   }
   ```

2. **Add Idempotency Key:**
   ```php
   $booking = $calcomService->createBooking([
       'metadata' => [
           'idempotency_key' => $appointment->uuid,
           'askproai_id' => $appointment->id,
       ],
       // ... other data
   ]);
   ```

## Recovery Procedures

### Emergency Fallback Mode

```php
// Enable fallback mode
Cache::put('calcom:fallback_mode', true, now()->addHours(2));

// In booking service
if (Cache::get('calcom:fallback_mode')) {
    return $this->handleManualBooking($appointment);
}
```

### Bulk Recovery Script

```bash
# Recover failed bookings from last 24 hours
php artisan calcom:recover-failed-bookings --hours=24

# Specific date range
php artisan calcom:recover-failed-bookings \
  --from="2025-01-15 00:00:00" \
  --to="2025-01-15 23:59:59"
```

### Data Verification

```sql
-- Find appointments without Cal.com booking
SELECT * FROM appointments 
WHERE status = 'confirmed' 
  AND calcom_booking_id IS NULL 
  AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Check for orphaned bookings
SELECT c.* FROM calcom_bookings c
LEFT JOIN appointments a ON c.id = a.calcom_booking_id
WHERE a.id IS NULL;
```

## Monitoring & Alerts

### Key Metrics to Monitor

```php
// Add to monitoring dashboard
$metrics = [
    'api_success_rate' => $this->calculateSuccessRate(),
    'avg_response_time' => $this->getAverageResponseTime(),
    'circuit_breaker_trips' => $this->getCircuitBreakerTrips(),
    'webhook_failures' => $this->getWebhookFailures(),
    'sync_failures' => $this->getSyncFailures(),
];

// Alert thresholds
$alerts = [
    'api_success_rate' => ['<', 95],  // Alert if below 95%
    'avg_response_time' => ['>', 1000], // Alert if above 1s
    'circuit_breaker_trips' => ['>', 5], // Alert if >5 per hour
];
```

### Debug Mode

```bash
# Enable debug mode
CALCOM_DEBUG=true
CALCOM_LOG_REQUESTS=true
CALCOM_LOG_RESPONSES=true

# Watch debug logs
tail -f storage/logs/calcom-debug.log
```

## Common Error Codes

| Error Code | Meaning | Solution |
|------------|---------|----------|
| `CAL-001` | Invalid API key | Verify API key format and permissions |
| `CAL-002` | Event type not found | Re-sync event types |
| `CAL-003` | Slot unavailable | Check availability before booking |
| `CAL-004` | Invalid date format | Use ISO 8601 format |
| `CAL-005` | Rate limit exceeded | Implement backoff strategy |
| `CAL-006` | Team not found | Verify team ID/slug |
| `CAL-007` | User not found | Check staff Cal.com mapping |
| `CAL-008` | Invalid timezone | Use valid IANA timezone |
| `CAL-009` | Booking conflict | Implement retry logic |
| `CAL-010` | Webhook signature invalid | Verify webhook secret |

## Support Escalation

### Level 1: Self-Service
1. Run health check command
2. Check this troubleshooting guide
3. Review error logs

### Level 2: System Admin
1. Access diagnostics dashboard
2. Check circuit breaker status
3. Review integration logs
4. Test with Cal.com API directly

### Level 3: Cal.com Support
1. Gather debug information
2. Create support ticket with Cal.com
3. Include API request/response logs
4. Reference account details

## Useful Commands Summary

```bash
# Diagnostics
php artisan calcom:health-check
php artisan calcom:test-connection
php artisan calcom:circuit-status

# Debugging
php artisan calcom:debug-availability <event-type-id>
php artisan calcom:raw-api <method> <endpoint>
php artisan calcom:test-webhook-signature

# Recovery
php artisan calcom:circuit-reset
php artisan calcom:sync-event-types --force
php artisan calcom:recover-failed-bookings

# Monitoring
php artisan calcom:performance-test
php artisan calcom:webhook-status
php artisan calcom:sync-status
```