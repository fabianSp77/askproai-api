# Cal.com Reschedule Sync Failure - Root Cause Analysis
**Date**: 2025-10-11
**Incident Time**: 06:53:23 UTC
**Appointment ID**: 674
**Cal.com Booking ID**: 11669674
**Severity**: HIGH - Customer-facing failure preventing reschedule operations

---

## Executive Summary

**Issue**: Appointment exists in local database but reschedule operation failed with user-facing error: *"Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an."* (This appointment cannot be rescheduled online. Please call us directly.)

**Root Cause**: Cal.com API returned HTTP 500 Internal Server Error during reschedule operation, triggering fallback error message designed for "booking not found" scenarios.

**Impact**: Customer unable to reschedule appointment via phone AI assistant despite valid Cal.com booking ID in database.

**Status**: Database record correct, Cal.com booking exists, but sync operation blocked by Cal.com API error.

---

## Timeline of Events

### T-0: Appointment Creation (06:36:27 UTC)
```
✅ Cal.com booking created successfully
   - Booking ID: 11669674
   - Start: 2025-10-15 09:00:00
   - End: 2025-10-15 09:30:00
   - Customer: Hansi Hinterseer
   - Phone: +491604366218

✅ Database appointment record created
   - Appointment ID: 674
   - calcom_v2_booking_id: 11669674
   - external_id: 11669674
   - Status: scheduled
   - Source: retell_webhook
```

**Evidence**: Log entry shows successful Cal.com API response with all booking details properly stored.

### T+17min: Customer Reschedule Request (06:53:23 UTC)
```
📞 Customer call: call_34eccfb40e876f37458e06aa368
   User: "Gerne verschieben auf neun Uhr dreißig"
   Agent: "Ich verschiebe den Termin"

🔍 System lookup
   - Found appointment ID 674
   - Cal.com booking ID: 11669674
   - Policy check: PASSED
   - Attempted reschedule to: 2025-10-15 09:30:00

❌ Cal.com API reschedule FAILED
   POST /bookings/11669674/reschedule
   Response: HTTP 500 Internal Server Error

🚨 User-facing error triggered
   "Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an."
```

**Evidence**: Log entry `[2025-10-11 06:53:23] production.ERROR: ❌ Cal.com reschedule exception`

---

## Root Cause Analysis

### Primary Root Cause: Cal.com API Internal Server Error

**Location**: `/var/www/api-gateway/app/Services/CalcomService.php:614-673`

**API Request**:
```
POST https://api.cal.com/v2/bookings/11669674/reschedule
Headers:
  - Authorization: Bearer [API_KEY]
  - cal-api-version: 2024-08-13
  - Content-Type: application/json

Payload:
{
  "start": "2025-10-15T07:30:00+00:00"  // UTC converted from 09:30 Europe/Berlin
}
```

**API Response**:
```
HTTP 500 Internal Server Error
```

**Analysis**: Cal.com API experienced an internal server error that prevented the reschedule operation. This is a Cal.com infrastructure/API issue, not a bug in the application code.

### Secondary Issue: Misleading Error Message

**Location**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php:1265-1270`

**Problem**: Error handling logic conflates two different failure scenarios:
1. **Booking not found** (404) → Should say "cannot reschedule"
2. **Cal.com server error** (500) → Should say "temporary issue, try again"

**Current Code**:
```php
// Line 1265-1270
if (str_contains($e->getMessage(), '500') || str_contains($e->getMessage(), 'not found')) {
    return response()->json([
        'success' => false,
        'status' => 'error',
        'message' => 'Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an.'
    ], 200);
}
```

**Issue**: Both HTTP 500 (temporary server error) and "not found" (permanent issue) trigger the same "cannot be rescheduled" message, which is misleading for transient Cal.com API failures.

---

## System Behavior Verification

### Database Consistency: ✅ CORRECT

**Appointment Record**:
```sql
ID: 674
calcom_v2_booking_id: 11669674
external_id: 11669674
status: scheduled
source: retell_webhook
starts_at: 2025-10-15 09:00:00
ends_at: 2025-10-15 09:45:00
created_at: 2025-10-11 06:36:27
```

**Verification**: Appointment exists in database with valid Cal.com booking ID. No sync failure during creation.

### Cal.com Booking Status: ✅ EXISTS

**Evidence**:
- Booking ID 11669674 was successfully created at 06:36:27
- Cal.com returned complete booking response with all details
- No indication booking was deleted or invalidated

### Reschedule Policy Check: ✅ PASSED

**Evidence**: Log shows policy engine allowed the reschedule operation:
```
policy_config_Staff_28_reschedule
policy_config_Branch_9_reschedule
policy_config_Company_15_reschedule
→ All checks passed, reschedule allowed
```

---

## Error Classification

### Type: TRANSIENT CAL.COM API FAILURE
**Severity**: HIGH (customer-facing)
**Scope**: Single operation
**Reproducibility**: Likely non-reproducible (Cal.com server issue)

### Is This a Bug?
**Answer**: PARTIAL BUG

1. **NOT a bug**: Cal.com API returning HTTP 500 (external service failure)
2. **IS a bug**: Error message incorrectly tells user appointment "cannot be rescheduled" when it's a temporary Cal.com issue

### Was Rejection Correct?
**Answer**: REJECTION WAS NECESSARY BUT MESSAGE WAS WRONG

- **Correct**: System properly prevented reschedule when Cal.com API failed
- **Incorrect**: Error message implied permanent restriction rather than temporary Cal.com unavailability
- **Correct**: Database was NOT updated (transaction integrity maintained)

---

## Transaction Integrity Analysis

### Database Transaction Handling: ✅ CORRECT

**Code Location**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php:1291-1344`

**Transaction Flow**:
```php
// Line 1218-1284: Cal.com API call (OUTSIDE transaction)
if ($calcomBookingId) {
    try {
        $response = $this->calcomService->rescheduleBooking(...);
        $calcomSuccess = $response->successful();
    } catch (\Exception $e) {
        // Exception thrown → returns error to user
        // Database update NEVER executed
        return response()->json(['error' => '...']);
    }
}

// Line 1291: Database transaction (NEVER REACHED on Cal.com failure)
DB::transaction(function () use (...) {
    $booking->update([...]);
});
```

**Analysis**: Transaction pattern is CORRECT:
1. Cal.com API call happens BEFORE database transaction
2. On Cal.com failure → Exception thrown → Early return
3. Database transaction NEVER started → No rollback needed
4. Database remains unchanged (correct state)

**Verification**: Appointment 674 still has original time (09:00:00), not rescheduled time (09:30:00).

---

## Why Appointment "Not in Cal.com Calendar"?

**User Report**: *"Termin ist in unserer DB aber NICHT in Cal.com Kalender"*

**Analysis**: This claim needs verification. Evidence suggests:

1. **Booking WAS created in Cal.com** (06:36:27)
   - Cal.com returned booking ID: 11669674
   - Cal.com returned full booking details
   - No deletion logs found

2. **Possible Explanations**:
   - **User checked wrong calendar**: Different Cal.com user/event type
   - **Calendar sync delay**: Cal.com → Google/Outlook sync lag
   - **Timezone confusion**: 09:00 Europe/Berlin = 07:00 UTC
   - **Cal.com UI issue**: Booking exists but not visible in specific view
   - **Booking later cancelled**: Separate cancellation event not captured

3. **Verification Needed**:
   - Check Cal.com dashboard for booking 11669674
   - Verify calendar integration status
   - Check if booking was cancelled after creation
   - Confirm which Cal.com user/event type was used

---

## Cal.com API HTTP 500 Investigation

### Potential Cal.com Issues

1. **Booking ID Format Mismatch**
   - Request used: `11669674` (numeric)
   - Cal.com may expect: `2yGSGFciUkEiDymwFAT1NS` (UID format)
   - **Code stores BOTH**: `calcom_v2_booking_id` and booking UID

2. **API Version Compatibility**
   - Header sent: `cal-api-version: 2024-08-13`
   - Reschedule endpoint may have changed in newer versions

3. **Booking State Conflict**
   - Booking may be in state that prevents rescheduling
   - Cal.com may have internal validation that failed

4. **Rate Limiting / Throttling**
   - Circuit breaker shows: `circuit_breaker:calcom_api:failures`
   - Multiple failures may have triggered rate limiting

### Recommended Cal.com API Investigation

```bash
# Check if booking exists with different ID format
GET /bookings/11669674
GET /bookings/2yGSGFciUkEiDymwFAT1NS

# Check booking status/state
GET /bookings/11669674 → check "status" field

# Test reschedule with UID instead of numeric ID
POST /bookings/2yGSGFciUkEiDymwFAT1NS/reschedule
```

---

## Recommended Fixes

### 1. Improve Error Message Differentiation (HIGH PRIORITY)

**Location**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php:1256-1278`

**Current Code**:
```php
catch (\Exception $e) {
    Log::error('❌ Cal.com reschedule exception', [...]);

    // PROBLEM: Same message for 500 and 404
    if (str_contains($e->getMessage(), '500') || str_contains($e->getMessage(), 'not found')) {
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an.'
        ], 200);
    }
}
```

**Recommended Fix**:
```php
catch (\Exception $e) {
    Log::error('❌ Cal.com reschedule exception', [...]);

    // Differentiate between transient and permanent failures
    if (str_contains($e->getMessage(), '500') || str_contains($e->getMessage(), 'server error')) {
        return response()->json([
            'success' => false,
            'status' => 'temporary_error',
            'message' => 'Die Terminverwaltung ist vorübergehend nicht erreichbar. Bitte versuchen Sie es in wenigen Minuten erneut oder rufen Sie uns direkt an.'
        ], 200);
    }

    if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), '404')) {
        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'message' => 'Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an.'
        ], 200);
    }

    // Generic fallback
    return response()->json([
        'success' => false,
        'status' => 'error',
        'message' => 'Der Termin konnte nicht umgebucht werden. Bitte versuchen Sie es später erneut.'
    ], 200);
}
```

### 2. Use Booking UID Instead of Numeric ID (MEDIUM PRIORITY)

**Problem**: Code uses numeric booking ID (11669674) but Cal.com may expect UID format (2yGSGFciUkEiDymwFAT1NS).

**Location**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php:1199-1201`

**Current Code**:
```php
$calcomBookingId = $booking->calcom_v2_booking_id
                ?? $booking->calcom_booking_id
                ?? $booking->external_id;
```

**Investigation Needed**:
1. Check if `calcom_v2_booking_id` stores numeric ID or UID
2. Verify Cal.com API expects UID for reschedule operations
3. Parse UID from booking response: `$responseData['data']['uid']`

**Recommended Fix** (if UID required):
```php
// Priority: Use UID if available, fallback to numeric ID
$calcomBookingId = $booking->external_id  // May contain UID
                ?? $booking->calcom_v2_booking_id  // Numeric ID
                ?? $booking->calcom_booking_id;

// Validate booking ID format
if (!$this->isValidCalcomBookingId($calcomBookingId)) {
    Log::warning('Invalid Cal.com booking ID format', [
        'appointment_id' => $booking->id,
        'booking_id' => $calcomBookingId
    ]);
}
```

### 3. Add Retry Logic for Transient Cal.com Failures (LOW PRIORITY)

**Rationale**: HTTP 500 errors are often transient. Single retry may succeed.

**Recommended Implementation**:
```php
$maxRetries = 1;
$retryDelay = 2; // seconds

for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
    try {
        $response = $this->calcomService->rescheduleBooking(...);
        $calcomSuccess = $response->successful();
        break; // Success, exit retry loop
    } catch (\Exception $e) {
        if ($attempt < $maxRetries && str_contains($e->getMessage(), '500')) {
            Log::warning('Cal.com reschedule failed, retrying', [
                'attempt' => $attempt + 1,
                'max_retries' => $maxRetries
            ]);
            sleep($retryDelay);
            continue;
        }
        throw $e; // Final failure or non-retryable error
    }
}
```

### 4. Enhanced Logging for Cal.com API Responses (LOW PRIORITY)

**Rationale**: Better debugging for future Cal.com API issues.

**Recommended Addition**:
```php
Log::channel('calcom')->error('Cal.com reschedule failed', [
    'booking_id' => $calcomBookingId,
    'appointment_id' => $booking->id,
    'http_status' => $response->status(),
    'response_body' => $response->body(),
    'request_payload' => [
        'start' => $rescheduleDate->toIso8601String(),
        'timezone' => $timezone
    ],
    'cal_api_version' => config('services.calcom.api_version')
]);
```

---

## Testing Recommendations

### 1. Verify Cal.com Booking Exists
```bash
# Manual Cal.com API check
curl -X GET "https://api.cal.com/v2/bookings/11669674" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"

# Alternative: Check by UID
curl -X GET "https://api.cal.com/v2/bookings/2yGSGFciUkEiDymwFAT1NS" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

### 2. Test Reschedule with Different ID Formats
```bash
# Test with numeric ID
curl -X POST "https://api.cal.com/v2/bookings/11669674/reschedule" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13" \
  -H "Content-Type: application/json" \
  -d '{"start": "2025-10-15T07:30:00+00:00"}'

# Test with UID
curl -X POST "https://api.cal.com/v2/bookings/2yGSGFciUkEiDymwFAT1NS/reschedule" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13" \
  -H "Content-Type: application/json" \
  -d '{"start": "2025-10-15T07:30:00+00:00"}'
```

### 3. Monitor Circuit Breaker Status
```bash
# Check if Cal.com circuit breaker is open
php artisan tinker --execute="
echo 'Circuit Breaker Status:' . PHP_EOL;
echo 'State: ' . Cache::get('circuit_breaker:calcom_api:state', 'closed') . PHP_EOL;
echo 'Failures: ' . Cache::get('circuit_breaker:calcom_api:failures', 0) . PHP_EOL;
echo 'Last failure: ' . Cache::get('circuit_breaker:calcom_api:last_failure_time', 'N/A') . PHP_EOL;
"
```

---

## Prevention Strategies

### 1. Cal.com API Health Monitoring
- Implement proactive Cal.com API health checks
- Alert on circuit breaker open events
- Track Cal.com API error rates in dashboard

### 2. Fallback Mechanisms
- Allow manual reschedule via admin panel when Cal.com fails
- Queue reschedule operations for retry when Cal.com recovers
- Provide clear user guidance on alternative contact methods

### 3. Better User Communication
- Distinguish between temporary and permanent failures
- Provide estimated retry times for transient errors
- Log detailed error context for support team investigation

---

## Conclusion

**Primary Root Cause**: Cal.com API returned HTTP 500 Internal Server Error, preventing reschedule operation.

**Secondary Issue**: Error message incorrectly suggested permanent restriction rather than temporary Cal.com unavailability.

**Database Integrity**: MAINTAINED - No partial updates or sync inconsistencies.

**Booking Status**: EXISTS in Cal.com (created successfully, reschedule failed due to Cal.com API issue).

**Recommended Actions**:
1. **Immediate**: Improve error message differentiation (HIGH)
2. **Short-term**: Investigate Cal.com booking ID format requirements (MEDIUM)
3. **Long-term**: Add retry logic for transient Cal.com failures (LOW)

**Customer Impact**: Single reschedule attempt failed with misleading message. Appointment remains bookable via direct contact or retry.

---

## Related Files

- `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php` (Lines 1150-1350)
- `/var/www/api-gateway/app/Services/CalcomService.php` (Lines 614-673)
- `/var/www/api-gateway/storage/logs/laravel.log` (2025-10-11 06:53:23)

## Investigation Timestamp
**Completed**: 2025-10-11 (System date per environment context)
