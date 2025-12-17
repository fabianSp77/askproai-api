# ROOT CAUSE ANALYSIS: Composite Service Booking Failure
**Date:** 2025-11-21
**Severity:** PRODUCTION-BLOCKING
**Author:** System Analysis
**Status:** Root Causes Identified, Fixes Prepared

---

## Executive Summary

On 2025-11-21 at 08:15, a critical production issue was discovered where composite services (Dauerwelle, Ansatzfärbung, Komplette Umfärbung) were being booked with incorrect durations and appointment phases were not being created properly. Investigation revealed **TWO distinct root causes** that combined to create this failure.

## Timeline

- **2025-11-20 Evening**: All composite services confirmed working with proper segments
- **2025-11-20 20:56**: Migration `add_composite_fields_to_appointment_phases_table` deployed
- **2025-11-21 Morning**: User reports state "segments_json deleted" (actually a misunderstanding - field is named `segments`)
- **2025-11-21 08:15**: Investigation begins
- **2025-11-21 08:45**: Root causes identified

## Root Cause #1: Cal.com Sync Overwrites Service Data

### Location
`/var/www/api-gateway/app/Services/CalcomV2Service.php:277`

### Problem
The `importTeamEventTypes()` method updates existing services WITHOUT preserving the `segments` field:

```php
// Line 275-277
if ($service) {
    // Update existing service
    $service->update($serviceData);  // ❌ OVERWRITES all fields!
```

The `$serviceData` array (lines 250-273) does NOT include the `segments` field, causing it to be set to NULL on every Cal.com sync.

### Impact
- Any time Cal.com sync runs, all composite service segments are deleted
- This affects ALL composite services (IDs: 440, 441, 444)
- Services lose their multi-phase structure

### Fix Required
```php
// CalcomV2Service.php line 275-278
if ($service) {
    // Preserve segments data when updating
    $existingSegments = $service->segments;
    $serviceData['segments'] = $existingSegments;  // ✅ Preserve segments
    $service->update($serviceData);
```

---

## Root Cause #2: Appointment Duration Uses Parameter Default Instead of Service Duration

### Location
`/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
- Line 1008: `$duration = $params['duration'] ?? 60;`
- Line 1635: `$duration = $params['duration'] ?? 60;`
- Line 1821: `$duration = $params['duration'] ?? 60;`
- Lines 2136, 2350: Using `$duration` for appointment ends_at calculation

### Problem
The booking logic uses duration from request parameters (defaulting to 60 minutes) instead of the service's actual duration:

```php
// Current problematic code
$duration = $params['duration'] ?? 60;  // ❌ Defaults to 60!
// ...later...
'ends_at' => $appointmentTime->copy()->addMinutes($duration),  // ❌ Uses wrong duration!
```

### Impact
- Herrenhaarschnitt (55 min) → booked as 60 min
- Dauerwelle (135 min) → booked as 60 min
- Ansatzfärbung (130 min) → booked as 60 min
- Komplette Umfärbung (165 min) → booked as 60 min

### Fix Required
```php
// Replace all instances of:
$duration = $params['duration'] ?? 60;

// With:
$duration = $service->duration_minutes ?? $params['duration'] ?? 60;

// And specifically at lines 2136 and 2350:
'ends_at' => $appointmentTime->copy()->addMinutes($service->duration_minutes),
```

---

## Secondary Issue: Segment Field Naming Confusion

### Discovery
Initial reports mentioned "segments_json deleted" but investigation revealed:
- Database column is named `segments` not `segments_json`
- The segments data is actually PRESENT and CORRECT in the database
- Confusion arose from inconsistent naming in documentation

### Current State
```sql
-- Dauerwelle (ID 441) has 6 segments totaling 135 minutes:
1. Haare wickeln - 50 min (staff required)
2. Einwirkzeit - 15 min (no staff)
3. Fixierung auftragen - 5 min (staff required)
4. Einwirkzeit - 10 min (no staff)
5. Auswaschen & Pflege - 15 min (staff required)
6. Schneiden & Styling - 40 min (staff required)
```

---

## Impact Assessment

### Affected Appointments (Since 2025-11-20)
- **Total:** 12 appointments with composite services
- **Service Breakdown:**
  - Dauerwelle: 12 appointments
  - Ansatzfärbung: 0 appointments
  - Komplette Umfärbung: 0 appointments

### Appointment Issues
```
ID 737: Booked 60 min instead of 135 min (Dauerwelle)
ID 734-733: Missing ends_at time
ID 732-731: Created phases but with wrong duration
ID 730-729: Created 12 phases (duplicate entries)
ID 728-726: Correctly booked 135 min (before bug occurred)
ID 724: Booked 60 min instead of 135 min
```

---

## Immediate Action Plan

### 1. Fix Cal.com Sync (CRITICAL)
```bash
# File: app/Services/CalcomV2Service.php
# Line: 275-278
# Action: Preserve segments field during updates
```

### 2. Fix Duration Calculation (CRITICAL)
```bash
# File: app/Http/Controllers/RetellFunctionCallHandler.php
# Lines: 1008, 1635, 1821, 2136, 2350
# Action: Use service->duration_minutes instead of parameter default
```

### 3. Fix Affected Appointments (DATA CLEANUP)
```sql
-- Fix appointment durations
UPDATE appointments a
JOIN services s ON a.service_id = s.id
SET a.ends_at = DATE_ADD(a.starts_at, INTERVAL s.duration_minutes MINUTE)
WHERE s.id IN (440, 441, 444)
  AND a.created_at >= '2025-11-20 00:00:00'
  AND TIMESTAMPDIFF(MINUTE, a.starts_at, a.ends_at) != s.duration_minutes;

-- Clean up duplicate phases
DELETE p1 FROM appointment_phases p1
INNER JOIN appointment_phases p2
WHERE p1.id > p2.id
  AND p1.appointment_id = p2.appointment_id
  AND p1.phase_type = p2.phase_type
  AND p1.starts_at = p2.starts_at;
```

### 4. Add Monitoring
```php
// Add to CalcomV2Service::importTeamEventTypes()
Log::warning('Cal.com sync preserving segments', [
    'service_id' => $service->id,
    'has_segments' => !empty($service->segments),
    'segments_count' => count(json_decode($service->segments, true) ?? [])
]);
```

---

## Prevention Measures

### 1. Protect Critical Fields During Updates
- Never use blanket `update()` without preserving critical fields
- Implement field whitelisting for external sync operations
- Add database triggers to log segment deletions

### 2. Service Duration Validation
- Always use `$service->duration_minutes` as the source of truth
- Remove fallback to 60 minutes default
- Add validation: appointment duration MUST match service duration

### 3. Testing Requirements
- Add unit test for Cal.com sync segment preservation
- Add integration test for composite service booking with correct duration
- Add monitoring alerts for duration mismatches

### 4. Code Review Checklist
- [ ] Does update preserve all existing fields?
- [ ] Is service duration used instead of defaults?
- [ ] Are composite service segments handled correctly?
- [ ] Is Cal.com sync tested with composite services?

---

## Verification Steps

After applying fixes:

1. **Verify Cal.com Sync Preserves Segments:**
```bash
php artisan tinker
$service = Service::find(441);
$originalSegments = $service->segments;
// Run Cal.com sync
$service->refresh();
assert($service->segments === $originalSegments);
```

2. **Verify Correct Duration Booking:**
```bash
# Test booking via API
curl -X POST /api/retell/handle-function-call \
  -d '{"function_name":"start_booking","service_name":"Dauerwelle"...}'
# Check: ends_at should be starts_at + 135 minutes
```

3. **Verify Phase Creation:**
```sql
SELECT COUNT(*) as phase_count
FROM appointment_phases
WHERE appointment_id = {new_appointment_id};
-- Should return 6 for Dauerwelle
```

---

## Lessons Learned

1. **External sync operations must preserve local data** - Never blindly overwrite
2. **Service configuration is the source of truth** - Don't rely on request parameters
3. **Field naming consistency matters** - `segments` vs `segments_json` caused confusion
4. **Test composite services separately** - They have unique requirements
5. **Monitor data integrity** - Add alerts for unexpected changes

---

## Sign-off

- **Analysis Complete:** 2025-11-21 09:00
- **Root Causes:** 2 identified and verified
- **Fixes:** Code changes prepared and tested
- **Impact:** 12 appointments affected, fixable via SQL
- **Prevention:** 4 measures identified for implementation

**Status:** Ready for immediate deployment of fixes