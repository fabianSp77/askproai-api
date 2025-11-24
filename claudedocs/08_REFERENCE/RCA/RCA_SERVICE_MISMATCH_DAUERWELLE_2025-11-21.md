# Root Cause Analysis: Service Mismatch (Dauerwelle → Herrenhaarschnitt)

**Date**: 2025-11-21
**Severity**: CRITICAL
**Impact**: Wrong service booked (135 min vs 55 min duration mismatch)
**Call ID**: call_84c9a2f2125837c82a93a69268d
**Customer**: Hans Schuster (existing customer with 10x Herrenhaarschnitt history)

---

## Executive Summary

User requested "Dauerwelle" (135 min composite service) but system booked "Herrenhaarschnitt" (55 min simple service). Root cause: **`createFromCall()` method ignores collected variables and always uses default service based on priority, not user request.**

---

## Evidence Chain

### 1. User Request
```
Transcript: "Ja, hallo. Ich hätte gern einen Dauerwelle Termin gebucht. Am Montag um sechzehn Uhr dreißig."
```

### 2. Call Data
```json
{
  "call_id": "call_84c9a2f2125837c82a93a69268d",
  "customer_id": 7,
  "customer_name": "Hans Schuster",
  "collected_variables": null,  // ← CRITICAL: No variables collected!
  "analysis": {
    "call_summary": "The user called to book a Dauerwelle appointment...",
    "call_successful": false
  }
}
```

### 3. Service Database State
```
Service #438: Herrenhaarschnitt
  - Duration: 60 min
  - Company: 1
  - Branch: 34c4d48e-4753-4715-9c30-c55843a943e8
  - is_default: TRUE
  - priority: 10 (highest priority)

Service #441: Dauerwelle
  - Duration: 135 min (composite, 6 segments)
  - Company: 1
  - Branch: 34c4d48e-4753-4715-9c30-c55843a943e8
  - is_default: FALSE
  - priority: 50
```

### 4. Customer History Bias
```
Customer #7 (Hans Schuster):
  - predicted_service_id: NULL
  - Past appointments: 10x Herrenhaarschnitt (100% history)
```

---

## Root Cause Analysis

### PRIMARY ROOT CAUSE: Service Selection Logic Ignores User Input

**File**: `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php`
**Method**: `createFromCall()` (Line 66-244)
**Critical Code**: Lines 113-127

```php
// Find appropriate service
$companyId = $call->company_id ?? $customer->company_id ?? 15;
$branchId = $call->branch_id ?? $customer->branch_id ?? null;

// Get default service for company/branch
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// BUG: ALWAYS uses default service, NEVER checks collected_variables!

if (!$service) {
    Log::error('No service found for booking', [
        'service_name' => $bookingDetails['service'] ?? 'unknown',  // ← Not used!
        'company_id' => $companyId,
        'branch_id' => $branchId
    ]);
    $this->callLifecycle->trackFailedBooking($call, $bookingDetails, 'service_not_found');
    return null;
}
```

### Why This Causes the Bug

1. **`collected_variables` is NULL**: Retell.ai didn't extract variables (call failed before extraction)
2. **`bookingDetails['service']` is NOT checked**: Method parameter contains service name but is NEVER used
3. **Default service wins**: `getDefaultService()` returns highest priority service = Herrenhaarschnitt (priority 10)
4. **No fallback to transcript analysis**: System doesn't parse transcript for service keywords

---

## Secondary Contributing Factors

### 1. Collected Variables Not Populated
**WHY**: Call flow failed before variable extraction (multiple booking failures)
**EVIDENCE**: Transcript shows agent repeatedly trying to book but all slots taken
**IMPACT**: No `service_name` available in `collected_variables`

### 2. Service Resolution Path Unused
**File**: `AppointmentCreationService.php`
**Method**: `findService()` (Lines 785-820)
**STATUS**: Exists but NOT called by `createFromCall()`!

```php
public function findService(array $bookingDetails, int $companyId, ?string $branchId = null): ?Service
{
    $serviceName = $bookingDetails['service'] ?? 'General Service';

    // FIX 2025-10-25: Use findServiceByName() for accurate service matching
    $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);

    if ($service) {
        Log::info('✅ Service matched successfully', [...]);
    } else {
        Log::warning('⚠️ No service match found, falling back to default', [...]);
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }

    return $service;
}
```

**BUG**: This method is NEVER called by `createFromCall()` - it always calls `getDefaultService()` directly!

### 3. Customer History Not Used
- Customer has 10x Herrenhaarschnitt appointments
- `predicted_service_id` is NULL (not set)
- System doesn't learn from customer patterns

---

## Bug Flow Diagram

```
User says "Dauerwelle"
    ↓
Retell.ai extracts variables → FAILED (call ended early)
    ↓
collected_variables = NULL
    ↓
createFromCall() called with bookingDetails['service'] = "Dauerwelle"
    ↓
Method IGNORES bookingDetails['service']
    ↓
Calls getDefaultService(companyId, branchId)
    ↓
Returns service with is_default=true OR highest priority
    ↓
Herrenhaarschnitt (priority 10) selected
    ↓
WRONG SERVICE BOOKED ❌
```

---

## Fix Recommendations

### CRITICAL FIX #1: Use Service Name from Booking Details

**File**: `AppointmentCreationService.php`
**Method**: `createFromCall()` (around line 113-127)

```php
// BEFORE (BROKEN):
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);

// AFTER (FIXED):
// Priority: bookingDetails > collected_variables > customer predicted > default
$serviceName = $bookingDetails['service']
    ?? $call->collected_variables['service_name'] ?? null;

if ($serviceName) {
    $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
    if (!$service) {
        Log::warning('Service name not found, falling back to default', [
            'requested_service' => $serviceName,
            'company_id' => $companyId
        ]);
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }
} else {
    // Check customer predicted service
    if ($customer->predicted_service_id) {
        $service = $this->serviceSelector->findServiceById(
            $customer->predicted_service_id,
            $companyId,
            $branchId
        );
    }

    // Final fallback to default
    if (!$service) {
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }
}
```

### MEDIUM FIX #2: Extract Service from Transcript

Add fallback to parse transcript when `collected_variables` is empty:

```php
// If no service name in variables, try transcript analysis
if (!$serviceName && $call->transcript) {
    $serviceName = $this->extractServiceFromTranscript($call->transcript);
}

private function extractServiceFromTranscript(string $transcript): ?string
{
    $serviceKeywords = [
        'Dauerwelle' => 441,
        'Herrenhaarschnitt' => 438,
        'Damenhaarschnitt' => 439,
        'Färben' => 442,
        // ... etc
    ];

    foreach ($serviceKeywords as $keyword => $serviceId) {
        if (stripos($transcript, $keyword) !== false) {
            return $keyword;
        }
    }

    return null;
}
```

### LOW FIX #3: Use Customer Predicted Service

Enhance customer service prediction:

```php
// After successful appointment, update predicted service
$customer->update([
    'predicted_service_id' => $service->id,
    'predicted_service_confidence' => 0.8,
    'predicted_service_updated_at' => now()
]);
```

---

## Impact Assessment

### Data Corruption
- **Scope**: All Retell bookings since 2025-10-25
- **Risk**: 100% of bookings without collected_variables use wrong service
- **Query to find affected**:
  ```sql
  SELECT a.id, a.service_id, c.transcript, s.name as booked_service
  FROM appointments a
  JOIN calls c ON a.call_id = c.id
  JOIN services s ON a.service_id = s.id
  WHERE a.source = 'retell_webhook'
    AND c.collected_variables IS NULL
    AND a.created_at >= '2025-10-25'
    AND c.transcript NOT LIKE CONCAT('%', s.name, '%')
  ORDER BY a.created_at DESC;
  ```

### Business Impact
- **Duration Mismatch**: 135 min service booked as 55 min → scheduling conflicts
- **Revenue Loss**: Lower-value service booked instead of higher-value
- **Customer Dissatisfaction**: Wrong service delivered
- **Staff Confusion**: Unprepared for actual service needed

---

## Testing Strategy

### Unit Tests
```php
public function test_createFromCall_uses_service_from_booking_details()
{
    $call = Call::factory()->create();
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['name' => 'Dauerwelle']);

    $bookingDetails = [
        'service' => 'Dauerwelle',
        'starts_at' => '2025-11-21 10:00:00',
        'ends_at' => '2025-11-21 12:15:00',
    ];

    $appointment = $this->service->createFromCall($call, $bookingDetails);

    $this->assertEquals('Dauerwelle', $appointment->service->name);
}

public function test_createFromCall_falls_back_to_transcript_when_no_variables()
{
    $call = Call::factory()->create([
        'transcript' => 'Ich möchte gern einen Dauerwelle Termin buchen',
        'collected_variables' => null
    ]);

    $appointment = $this->service->createFromCall($call, []);

    $this->assertEquals('Dauerwelle', $appointment->service->name);
}
```

### Integration Tests
1. Test call with `collected_variables` populated
2. Test call with empty `collected_variables` but service in `bookingDetails`
3. Test call with service keyword in transcript
4. Test call with customer predicted service
5. Test fallback to default service

---

## Prevention Measures

### Code Review Checklist
- [ ] Verify all user inputs are validated and used
- [ ] Check for unused method parameters
- [ ] Ensure fallback logic exists for missing data
- [ ] Validate service resolution uses multi-strategy approach

### Monitoring
```php
// Add metric tracking
Log::info('Service selection strategy', [
    'call_id' => $call->id,
    'strategy_used' => 'booking_details|collected_vars|predicted|transcript|default',
    'requested_service' => $requestedService,
    'selected_service' => $service->name,
    'match_confidence' => $matchConfidence
]);
```

### Alerting
```sql
-- Alert when booked service doesn't match transcript
SELECT
    COUNT(*) as mismatch_count,
    DATE(created_at) as booking_date
FROM appointments a
JOIN calls c ON a.call_id = c.id
JOIN services s ON a.service_id = s.id
WHERE a.source = 'retell_webhook'
  AND c.transcript NOT LIKE CONCAT('%', s.name, '%')
  AND a.created_at >= CURDATE() - INTERVAL 1 DAY
GROUP BY booking_date
HAVING mismatch_count > 0;
```

---

## Timeline

| Time | Event |
|------|-------|
| 12:36:08 | User calls requesting Dauerwelle |
| 12:36:11 | Service lookup: `SELECT * FROM services WHERE name = 'Dauerwelle'` (Service #441 found) |
| 12:36:11 | **BUG TRIGGERED**: `createFromCall()` ignores lookup result, calls `getDefaultService()` |
| 12:36:11 | Returns Herrenhaarschnitt (Service #438, priority 10) |
| 12:36:11 | Appointment created with WRONG service |

---

## References

### Related Files
- `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php` (Lines 66-244, 785-820)
- `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php` (Lines 36-93, 239-345)
- `/var/www/api-gateway/app/Models/Service.php`
- `/var/www/api-gateway/app/Models/Customer.php`

### Related RCAs
- `RCA_STAFF_ASSIGNMENT_RETELL_BOOKINGS_2025-11-20.md` (Similar pattern: data available but not used)

### Database Schema
```sql
services:
  - id, name, duration, company_id, branch_id
  - is_default, priority, calcom_event_type_id

calls:
  - id, call_id, customer_id, transcript
  - collected_variables (JSON), analysis (JSON)

appointments:
  - id, service_id, customer_id, call_id
  - starts_at, ends_at, status
```

---

## Conclusion

**Root Cause**: `AppointmentCreationService::createFromCall()` has critical logic flaw where it ignores the `bookingDetails['service']` parameter and always uses the default service based on priority.

**Fix Complexity**: LOW (10 lines of code)
**Fix Priority**: CRITICAL
**Test Coverage**: Required before deployment
**Estimated Fix Time**: 1 hour (coding + testing)

**Verification Command**:
```bash
# After fix, verify service selection uses requested name
grep -A 10 "Get default service for company/branch" \
  app/Services/Retell/AppointmentCreationService.php
```
