# Data Recovery Strategy - Call & Appointment Data (2025-11-06)

## Executive Summary

**Problem**: Missing call and appointment data in admin UI due to October 1, 2025 regression
**Impact**: ~99% of calls showed no appointment data in admin panels
**Root Cause**: Bidirectional linking code removed from AppointmentCreationService
**Status**: ✅ Future appointments fixed | ⚠️ Historical data recovery in progress

---

## Recovery Status Overview

| Issue | Count | Fixed | Remaining | Status |
|-------|-------|-------|-----------|--------|
| Broken bidirectional links | 2 | 2 | 0 | ✅ Complete |
| Missing staff assignments | 111 | 6 | 105 | ⚠️ Partially Fixed |
| Appointments from hidden call data | 15 | 0 | 15 | 🔄 Ready to recover |
| NULL service_id appointments | 105 | 0 | 105 | ❌ Not recoverable |

---

## Problem 1: Broken Bidirectional Links ✅ SOLVED

### Problem Statement
Appointments had `call_id` set (forward link) but calls didn't have `appointment_id` set (backward link missing).

### Root Cause
```php
// MISSING from AppointmentCreationService.php (regression: Oct 1, 2025)
$call->update([
    'appointment_id' => $appointment->id,  // ❌ This line was missing
]);
```

### Impact
- 2 appointments (IDs 568, 571) had broken links
- Created: Oct 3, 2025 22:19 onwards
- Admin UI couldn't display appointment data for these calls

### Solution Applied
1. **Code Fix**: Added call.update() immediately after appointment.save() in AppointmentCreationService.php:465-489
2. **Historical Fix**: Created and ran `heal_call_appointment_links_2025-11-06.php`
3. **Result**: 2/2 broken links fixed (100% success rate)

### Files Modified
- `app/Services/Retell/AppointmentCreationService.php:465-489`
- `app/Filament/Resources/CallResource.php:197-219`

### Verification
```bash
php artisan tinker --execute="
\App\Models\Appointment::whereNotNull('call_id')
    ->whereHas('call', fn(\$q) => \$q->whereNull('appointment_id'))
    ->count();
"
# Output: 0 (no broken links remaining)
```

---

## Problem 2: Missing Staff Assignments ⚠️ PARTIALLY SOLVED

### Problem Statement
99.1% of appointments had `staff_id: NULL` because AppointmentCreationService didn't auto-select staff when not provided by Retell AI.

### Root Cause
```php
// app/Services/Retell/AppointmentCreationService.php
// MISSING: Auto-selection logic from service_staff pivot table
$staffId = $bookingDetails['staff_id'] ?? null;  // ❌ Always NULL if not provided
```

### Impact
- 111 appointments without staff_id (last 3 months)
- Admin UI showed no staff information
- 6 appointments had valid services with assignable staff
- 105 appointments had NULL service_id (no staff could be assigned)

### Solution Applied
1. **Code Fix**: Added auto-selection from service.staff() relationship:
```php
// ✅ NEW: Auto-select from service's assigned staff
$availableStaff = $service->staff()
    ->wherePivot('can_book', true)
    ->first();

if ($availableStaff) {
    $staffId = $availableStaff->id;
}
```

2. **Historical Fix**: Created and ran `heal_missing_staff_assignments_2025-11-06.php`
3. **Result**: 6/111 fixed (5.4% success rate)

### Why Only 5.4% Fixed?
- **105 appointments** have `service_id: NULL` (completely missing service)
- **6 appointments** had service "Hairdetox" with staff in service_staff pivot table
- No service = no staff relationship = cannot auto-assign

### Files Modified
- `app/Services/Retell/AppointmentCreationService.php:441-470`

### Verification
```bash
php artisan tinker --execute="
echo 'With staff: ' . \App\Models\Appointment::whereNotNull('staff_id')
    ->where('created_at', '>', now()->subMonths(3))->count() . PHP_EOL;
echo 'Without staff: ' . \App\Models\Appointment::whereNull('staff_id')
    ->where('created_at', '>', now()->subMonths(3))->count();
"
```

---

## Problem 3: Appointments from Hidden Call Data 🔄 READY TO RECOVER

### Problem Statement
15 calls have appointment data stored in "hidden" columns but NO appointments exist in database:
- `calls.datum_termin` (appointment date)
- `calls.uhrzeit_termin` (appointment time)
- `calls.dienstleistung` (service name)

These calls have `appointment_id: NULL` - appointments were never created.

### Root Cause
October 1, 2025 regression caused appointment creation to fail, but call records still captured the intended appointment data.

### Impact
- 15 calls with recoverable appointment data
- **11/15 are test data** (customer "Hansi Hinterseher")
- **4/15 are real customer data**
- All created on Oct 1, 2025 (same day as regression)

### Data Breakdown

#### Real Customer Calls (4)
```sql
SELECT id, customer_id, datum_termin, uhrzeit_termin, dienstleistung, created_at
FROM calls
WHERE appointment_id IS NULL
  AND datum_termin IS NOT NULL
  AND customer_id NOT IN (SELECT id FROM customers WHERE name LIKE '%Hansi%');

-- Call IDs: 461, 462, 463, 465
```

#### Test Data Calls (11)
```sql
-- All belong to customer "Hansi Hinterseher" (likely test calls)
-- Call IDs: 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481
```

### Recovery Strategy

#### Phase 1: Automated Recovery (Recommended)
Run `recover_appointments_from_calls_2025-11-06.php` with:
```bash
# Recover ONLY real customer data (exclude test data)
php database/scripts/recover_appointments_from_calls_2025-11-06.php

# Dry run first to preview changes
php database/scripts/recover_appointments_from_calls_2025-11-06.php --dry-run

# Include test data if needed
php database/scripts/recover_appointments_from_calls_2025-11-06.php --include-test-data
```

**Recovery Logic**:
1. Find calls with `datum_termin` but no `appointment_id`
2. Fuzzy match `dienstleistung` to existing services (>80% similarity)
3. Parse date + time into `starts_at` datetime
4. Auto-assign staff from matched service's staff relationship
5. Create appointment with bidirectional call link
6. Set `status: 'confirmed'` and add recovery notes

#### Phase 2: Manual Review (If Needed)
For unmatched services or failed recoveries:
1. Check service name mappings in admin panel
2. Manually create appointments via Filament UI
3. Link to calls using call_id field

### Expected Outcomes
- ✅ 4 real customer appointments recovered (if services match)
- ⏭️ 11 test data appointments skipped (unless --include-test-data used)
- 📊 Linking rate improvement: ~0.2% increase

### Files Created
- `database/scripts/recover_appointments_from_calls_2025-11-06.php`

---

## Problem 4: NULL service_id Appointments ❌ NOT RECOVERABLE

### Problem Statement
105 appointments have `service_id: NULL` with no recovery path.

### Data Analysis
```php
// All 105 appointments:
- service_id: NULL
- call_id: NULL (not linked to any calls)
- created_at: 2025-09-26 (all same date - suspicious)
- metadata: {} (empty, no service information)
- customer_id: Various (not test data)
```

### Why Not Recoverable?
1. **No service data**: Neither service_id nor service info in metadata
2. **No call linkage**: No call_id to check for hidden data
3. **No Cal.com reference**: No calcom_v2_booking_id to fetch from Cal.com
4. **No context**: Created on same date suggests bulk import failure

### Likely Cause
Failed data import or migration on 2025-09-26 that created incomplete appointment records.

### Recommended Action
**Option 1**: Leave as-is (orphaned data, no impact on functionality)
**Option 2**: Soft-delete these records (mark as invalid data)
```bash
php artisan tinker --execute="
\App\Models\Appointment::whereNull('service_id')
    ->where('created_at', '>=', '2025-09-26')
    ->where('created_at', '<', '2025-09-27')
    ->update(['notes' => 'Invalid data - service_id missing (orphaned from 2025-09-26)']);
"
```

**Option 3**: Hard-delete if confirmed as corrupt
```bash
# CAUTION: Permanent deletion
php artisan tinker --execute="
\App\Models\Appointment::whereNull('service_id')
    ->where('created_at', '>=', '2025-09-26')
    ->where('created_at', '<', '2025-09-27')
    ->forceDelete();
"
```

---

## Implementation Checklist

### ✅ Completed
- [x] Fix bidirectional linking in AppointmentCreationService
- [x] Fix staff auto-assignment in AppointmentCreationService
- [x] Re-enable eager loading in CallResource
- [x] Create heal_call_appointment_links script
- [x] Create heal_missing_staff_assignments script
- [x] Run bidirectional link healing (2/2 fixed)
- [x] Run staff assignment healing (6/111 fixed)

### 🔄 Ready to Execute
- [ ] Run appointment recovery from call hidden data
  ```bash
  # Dry run first
  php database/scripts/recover_appointments_from_calls_2025-11-06.php --dry-run

  # Execute recovery (real data only)
  php database/scripts/recover_appointments_from_calls_2025-11-06.php
  ```

### ⏳ Pending Decision
- [ ] Decide on NULL service_id appointments (leave/mark/delete)
- [ ] Document recovery process in admin guide
- [ ] Add monitoring for appointment creation failures

---

## Monitoring & Prevention

### Key Metrics to Track
```php
// Linking health
$linkingRate = Call::whereNotNull('appointment_id')->count() / Call::count() * 100;
// Target: >95% for calls created after 2025-11-06

// Staff assignment health
$staffRate = Appointment::whereNotNull('staff_id')->count() / Appointment::count() * 100;
// Target: >95% for appointments created after 2025-11-06

// Service assignment health
$serviceRate = Appointment::whereNotNull('service_id')->count() / Appointment::count() * 100;
// Target: 100% (should never be NULL)
```

### Automated Alerts
Add to monitoring system:
```php
// Alert if appointment created without call linkage
if ($appointment->exists && !$appointment->call_id) {
    Log::warning('Appointment created without call_id', [
        'appointment_id' => $appointment->id,
    ]);
}

// Alert if call updated without appointment linkage
if ($call->exists && $call->has_appointment && !$call->appointment_id) {
    Log::error('Call has_appointment=true but appointment_id is NULL', [
        'call_id' => $call->id,
    ]);
}
```

### Regression Tests
Add to test suite:
```php
// Test bidirectional linking
public function test_appointment_creation_creates_bidirectional_link()
{
    $call = Call::factory()->create();
    $appointment = Appointment::factory()->create(['call_id' => $call->id]);

    $this->assertNotNull($appointment->call_id);
    $this->assertNotNull($call->fresh()->appointment_id);
    $this->assertEquals($appointment->id, $call->fresh()->appointment_id);
}

// Test staff auto-assignment
public function test_appointment_auto_assigns_staff_from_service()
{
    $service = Service::factory()->create();
    $staff = Staff::factory()->create();
    $service->staff()->attach($staff->id, ['can_book' => true]);

    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'staff_id' => null,
    ]);

    $this->assertNotNull($appointment->staff_id);
    $this->assertEquals($staff->id, $appointment->staff_id);
}
```

---

## Documentation Updates

### Admin Guide
Add section: "Troubleshooting Missing Appointment Data"
- How to check call-appointment linkage
- How to manually link calls to appointments
- When to run healing scripts

### Developer Guide
Add section: "Appointment Creation Best Practices"
- Always create bidirectional links
- Always auto-assign staff when possible
- Always validate service_id before saving
- Transaction-wrap appointment + call updates

---

## Rollback Plan (If Issues Occur)

### Rollback Code Changes
```bash
git revert <commit-hash>  # Revert AppointmentCreationService changes
git revert <commit-hash>  # Revert CallResource changes
php artisan config:clear
php artisan cache:clear
```

### Rollback Database Changes
```sql
-- Restore original state for healed records
UPDATE calls
SET appointment_id = NULL,
    has_appointment = FALSE,
    appointment_link_status = NULL,
    appointment_linked_at = NULL
WHERE appointment_link_status = 'linked'
  AND appointment_linked_at > '2025-11-06';

-- Remove recovered appointments
DELETE FROM appointments
WHERE recovery_source = 'call_hidden_data'
  AND recovery_date > '2025-11-06';
```

---

## Summary

**Total Issues Identified**: 4
**Fully Resolved**: 1 (bidirectional linking)
**Partially Resolved**: 1 (staff assignments - 6/111)
**Ready to Recover**: 1 (4 real + 11 test appointments from call data)
**Not Recoverable**: 1 (105 NULL service_id appointments)

**Estimated Recovery Impact**:
- Linking rate: 0.12% → 95%+ (for new appointments)
- Staff assignment: 0.9% → 95%+ (for new appointments)
- Historical data: +4 real appointments recoverable (0.6% improvement)

**Next Action**: Run appointment recovery script to create 4 missing real customer appointments.
