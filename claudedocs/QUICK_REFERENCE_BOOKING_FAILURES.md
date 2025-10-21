# Quick Reference: Appointment Booking Failure Patterns

**Last Updated**: 2025-10-21
**Incident**: Call ID `call_fb447d0f0375c52daaa3ffe4c51`

---

## One-Minute Summary

User Hans Schuster tried to book appointments twice, both failed:
- **First attempt (11:00)**: check_availability returned generic error
- **Second attempt (14:00)**: book_appointment returned generic error
- **Root cause**: No explicit service ID passed → system may use different service each time
- **Impact**: User frustrated and hung up

---

## Error Patterns

### Pattern 1: "Fehler beim Prüfen der Verfügbarkeit"
**Translation**: Error checking availability
**Cause**: Cal.com API call failed or service has no integration
**Location**: RetellFunctionCallHandler.php line 459
**Recovery**: Re-check service configuration, verify Cal.com API is responsive

### Pattern 2: "Buchung konnte nicht durchgeführt werden"
**Translation**: Booking could not be completed
**Cause**: Cal.com booking failed (slot unavailable, permission denied, API error)
**Location**: RetellFunctionCallHandler.php line 710
**Recovery**: Verify slot is still available, check Cal.com API, re-attempt

### Pattern 3: "Service nicht verfügbar für diese Filiale"
**Translation**: Service not available for this branch
**Cause**: No service found OR service has no calcom_event_type_id
**Location**: RetellFunctionCallHandler.php lines 255, 589, 753
**Recovery**: Configure service with Cal.com integration

---

## Detection Queries (Copy-Paste Ready)

### Find All Booking Failures Today
```bash
grep "Buchung konnte nicht\|Der Termin konnte nicht" /var/www/api-gateway/storage/logs/laravel.log | wc -l
```

### Find Availability Check Errors
```bash
grep "Fehler beim Prüfen der Verfügbarkeit" /var/www/api-gateway/storage/logs/laravel.log | wc -l
```

### Find Specific Call's Logs
```bash
grep "call_fb447d0f0375c52daaa3ffe4c51" /var/www/api-gateway/storage/logs/laravel.log | head -50
```

### Database Query - Failed Bookings
```sql
SELECT
  c.id,
  c.retell_call_id,
  c.call_status,
  c.appointment_made,
  c.created_at
FROM calls c
WHERE
  c.appointment_made = 0
  AND c.call_successful = 0
  AND DATE(c.created_at) = CURDATE()
ORDER BY c.created_at DESC
LIMIT 20;
```

---

## Regex Patterns

### All Booking-Related Errors
```regex
(Fehler.*Verfügbarkeit|Buchung|Termin konnte nicht|Service.*verfügbar)
```

### Check_Availability Errors Only
```regex
Fehler beim Prüfen der Verfügbarkeit
```

### Book_Appointment Errors Only
```regex
(Der Termin konnte nicht gebucht|Buchung konnte nicht|Fehler bei der Terminbuchung)
```

### Cal.com API Issues
```regex
(Cal\.com|getAvailableSlots|createBooking|calcom_event_type_id)
```

---

## Code Locations

### Where Errors Are Returned

| Error | File | Line | Method |
|-------|------|------|--------|
| Availability error | RetellFunctionCallHandler.php | 459 | checkAvailability() |
| Booking error | RetellFunctionCallHandler.php | 710 | bookAppointment() |
| Service not found | RetellFunctionCallHandler.php | 255, 589, 753 | Multiple |
| Cal.com integration missing | RetellFunctionCallHandler.php | 248, 582 | Multiple |

### Critical Code Sections

**Service Resolution** (THE PROBLEM):
```php
// Line 245 in checkAvailability()
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);

// Line 579 in bookAppointment()
$service = $this->serviceSelector->getDefaultService($companyId, $branchId);
// ↑ NO GUARANTEE same service is returned!
```

**Error Handling** (THE GAP):
```php
// Line 448-460 in checkAvailability()
catch (\Exception $e) {
    // ↓ Generic error, not surfacing root cause
    return $this->responseFormatter->error('Fehler beim Prüfen der Verfügbarkeit');
}

// Line 712-716 in bookAppointment()
catch (\Exception $e) {
    // ↓ Generic error, not surfacing root cause
    return $this->responseFormatter->error('Fehler bei der Terminbuchung');
}
```

---

## Call Timeline (Key Events Only)

```
T+00:00 - Call starts (anonymous caller)
T+10:69 - User requests "morgen elf Uhr" (tomorrow 11:00)
T+12:88 - parse_date() succeeds
T+12:88 - check_availability(11:00) called
T+14:48 - check_availability() RETURNS ERROR
T+29:18 - Agent offers "14 Uhr" (hallucinated - no real availability check!)
T+56:90 - book_appointment(14:00) called
T+58:64 - book_appointment() RETURNS ERROR
T+74:89 - Call ends (user hangs up)

Result: 0 successful bookings, 2 function call failures
```

---

## Impact Analysis

### What Went Wrong
1. User saw vague errors (not knowing WHY availability wasn't found)
2. Agent hallucinated a time without verifying it
3. Booking failed with same vague error
4. User never got appointment despite requesting it

### User Experience
- **Frustration Level**: HIGH (got offered alternative that also failed)
- **Call Duration**: 75 seconds (longer than typical failed call)
- **Recovery**: NONE (user hung up)

### System Impact
- **Data Corruption**: NONE (no incomplete records created)
- **Cal.com State**: Unknown (did booking attempt reach Cal.com?)
- **Logs**: Incomplete (missing root cause of failures)

---

## Immediate Diagnostics

### Is Your System Having This Issue?

**Query 1: Check failure rate**
```sql
SELECT
  COUNT(*) as total_calls,
  SUM(CASE WHEN appointment_made = 0 THEN 1 ELSE 0 END) as failed_bookings,
  ROUND(SUM(CASE WHEN appointment_made = 0 THEN 1 ELSE 0 END) * 100 / COUNT(*), 2) as failure_rate
FROM calls
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

**Query 2: Check for service configuration issues**
```sql
SELECT
  s.id,
  s.name,
  s.company_id,
  s.calcom_event_type_id,
  CASE WHEN s.calcom_event_type_id IS NULL THEN 'MISSING' ELSE 'OK' END as cal_com_status
FROM services s
WHERE s.deleted_at IS NULL
ORDER BY s.company_id, s.name;
```

**Query 3: Check for multiple default services per branch**
```sql
SELECT
  b.id,
  b.name,
  COUNT(s.id) as service_count,
  GROUP_CONCAT(s.name SEPARATOR ', ') as services
FROM branches b
LEFT JOIN services s ON s.branch_id = b.id AND s.deleted_at IS NULL
WHERE b.deleted_at IS NULL
GROUP BY b.id
HAVING service_count > 1
ORDER BY service_count DESC;
```

---

## Fix Checklist

### Short-Term (Today)
- [ ] Identify if this is systemic (run Query 1 above)
- [ ] Check Cal.com API status
- [ ] Verify all services have calcom_event_type_id configured (Query 2)
- [ ] Contact user Hans Schuster with alternative booking method

### Medium-Term (This Week)
- [ ] Pass explicit `service_id` in Retell function calls
- [ ] Add detailed error logging (don't catch generic Exception)
- [ ] Cache service selection across call duration
- [ ] Re-verify availability before booking

### Long-Term (This Month)
- [ ] Implement pre-call service selection validation
- [ ] Add monitoring/alerting for booking failure rates
- [ ] Create automated recovery workflow for failed bookings
- [ ] Add unit tests for service mismatch scenarios

---

## Prevention Checklist for New Code

When implementing appointment booking:

- [ ] **Always use explicit service_id** (don't rely on getDefaultService)
- [ ] **Cache service per call** (store in call context)
- [ ] **Re-check availability before booking** (don't trust prior check)
- [ ] **Surface actual error reasons** (don't return generic messages)
- [ ] **Log full stack traces** (not just error message)
- [ ] **Add fallback user guidance** (when errors occur)
- [ ] **Test Cal.com API failures** (simulate timeout, 500, etc)
- [ ] **Validate service configuration** (before attempting booking)

---

## Related Incidents

This is part of a series of availability/booking issues:

- **2025-10-11**: Cache collision on availability checks
- **2025-10-13**: Retell latency optimization (80% reduction achieved)
- **2025-10-14**: Week picker reactive rendering fixes
- **2025-10-21**: THIS INCIDENT - Service mismatch in booking flow

---

## Further Reading

Full analysis documents:
- `/var/www/api-gateway/claudedocs/INCIDENT_ANALYSIS_CALL_FB447D0F.md` - Complete incident breakdown
- `/var/www/api-gateway/claudedocs/ERROR_DIAGNOSIS_SUMMARY.md` - Technical deep dive
- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` - Source code (lines 200-719)

---

## Contact

**Incident Reporter**: User complaint
**Analysis Date**: 2025-10-21
**Severity**: CRITICAL
**Priority**: HIGH
**Status**: REQUIRES FIX

For questions, refer to the full incident analysis document.
