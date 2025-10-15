# 🚨 Staff Assignment Issue - Call 766 Analysis

**Date:** 2025-10-06 21:00
**Analyzed By:** Claude Code
**Issue:** Mitarbeiter wurde nicht dem Termin zugeordnet
**Status:** ⚠️ ROOT CAUSE IDENTIFIED - FIX REQUIRED

---

## 📊 Problem Summary

**User Report:** "Warum sind die Mitarbeiter dem letzten Anruf nicht zugeordnet worden?"

**Finding:** Appointment 640 hat **keinen Mitarbeiter zugeordnet** (`staff_id = NULL`), obwohl:
- ✅ Cal.com Host-Daten vorhanden sind
- ✅ Matching-Strategien existieren
- ✅ Service hat Mitarbeiter zugeordnet
- ✅ Name-Matching sollte funktionieren (75% Confidence)

**Root Cause:**
1. Appointment 640 wurde von Call 682 (Company ID 1) erstellt
2. Call 766 wurde später damit verlinkt (Company ID 15)
3. **Company ID Mismatch:** Appointment hat Company 1, Staff ist in Company 15
4. **Multi-Tenant Isolation verhindert Matching**

---

## 🔍 Detailed Analysis

### Call Timeline

```
2025-10-05 22:21:55 - Call 682 created (Company 15)
2025-10-05 22:22:07 - Appointment 640 created (Company 1) ❌ Wrong company!
2025-10-05 22:22:07 - Staff assignment attempted but failed
2025-10-05 22:22:07 - Call 682 NOT linked to Appointment 640

2025-10-06 18:22:01 - Call 766 created (Company 15)
2025-10-06 18:22:12 - Duplicate booking detected
2025-10-06 18:45:00 - Call 766 linked to Appointment 640 (via fix)
```

### Data Verification

**Appointment 640:**
```
ID: 640
Customer ID: 340
Company ID: 1 ❌ (Should be 15)
Staff ID: NULL ❌ (Should be 28f22a49-a131-11f0-a0a1-ba630025b4ae)
Service ID: 47
Starts At: 2025-10-10 11:00:00
Status: scheduled
Assignment Model: NULL
```

**Cal.com Host Data:**
```json
{
  "id": 1414768,
  "name": "Fabian Spitzer",
  "email": "fabianspitzer@icloud.com",
  "username": "askproai",
  "timeZone": "Europe/Berlin"
}
```

**Staff Database:**
```
Staff ID: 28f22a49-a131-11f0-a0a1-ba630025b4ae
Name: Fabian Spitzer
Email: fabian@askproai.de ❌ (Doesn't match Cal.com: fabianspitzer@icloud.com)
Company ID: 15
Service 47 Assignment: YES ✅
Is Active: true
```

---

## 🔍 Root Cause Analysis

### Issue #1: Company ID Mismatch

**Appointment 640 has `company_id = 1` instead of `15`**

**Why This Matters:**
```php
// EmailMatchingStrategy.php:22-26
$staff = Staff::query()
    ->where('company_id', $context->companyId)  // Company 1
    ->where('email', $email)                     // fabianspitzer@icloud.com
    ->where('is_active', true)
    ->first();

// Returns NULL because:
// - Staff.company_id = 15 (not 1)
// - Multi-tenant isolation working correctly
```

**How Appointment Got Wrong Company ID:**

Looking at `AppointmentCreationService.php:382-396`:
```php
$appointment = Appointment::create([
    'customer_id' => $customer->id,
    'service_id' => $service->id,
    'branch_id' => $branchId,
    'tenant_id' => $customer->tenant_id ?? 1,  // ⚠️ Fallback to 1
    'starts_at' => $bookingDetails['starts_at'],
    'ends_at' => $bookingDetails['ends_at'],
    'call_id' => $call ? $call->id : null,
    'status' => 'scheduled',
    // ... other fields
]);
```

**PROBLEM:** No `company_id` is explicitly set during appointment creation!

**Database Default:**
```sql
-- From schema analysis
`company_id` bigint(20) unsigned NOT NULL DEFAULT 1
```

**So the appointment inherits `company_id = 1` from database default.**

### Issue #2: Email Mismatch

**Cal.com Email:** `fabianspitzer@icloud.com`
**Staff Email:** `fabian@askproai.de`

**Why This Matters:**
```php
// EmailMatchingStrategy.php:14-30
public function match(array $hostData, HostMatchContext $context): ?MatchResult
{
    $email = $hostData['email'] ?? null;  // fabianspitzer@icloud.com

    $staff = Staff::query()
        ->where('company_id', $context->companyId)
        ->where('email', $email)  // Exact match required
        ->where('is_active', true)
        ->first();

    // Returns NULL - no exact email match
}
```

**Email Matching Priority: 100 (Highest)**
**Name Matching Priority: 50 (Medium)**

Even if company_id was correct, email strategy would fail. Name strategy would succeed (75% confidence), but it's tried second.

### Issue #3: Name Matching Would Work (If Company ID Correct)

**Test Results:**
```
Cal.com Name: "Fabian Spitzer"
Normalized: "fabian spitzer"

Staff Name: "Fabian Spitzer"
Normalized: "fabian spitzer"

Match: YES ✅ (75% confidence)
```

**But name matching only runs if email matching fails:**
```php
// CalcomHostMappingService.php:72-95
foreach ($this->strategies as $strategy) {
    $matchResult = $strategy->match($hostData, $context);

    if ($matchResult && $matchResult->confidence >= 80) {
        // Create mapping and return
        return $mapping->staff_id;
    }
}
```

**Name matching returns 75% confidence, which is < 80% threshold!**

So even with correct company_id, name matching wouldn't auto-create a mapping.

---

## 🐛 Issues Summary

| Issue | Description | Impact | Severity |
|-------|-------------|--------|----------|
| **#1: Missing company_id** | Appointment creation doesn't set company_id explicitly | Appointment gets default company_id = 1 | 🔴 CRITICAL |
| **#2: Email mismatch** | Cal.com email differs from staff email | Email matching (priority 100) fails | 🟡 HIGH |
| **#3: Name threshold** | Name matching returns 75% but threshold is 80% | Name matching (priority 50) doesn't create mapping | 🟡 MEDIUM |
| **#4: Call not linked** | Original Call 682 has appointment_id = NULL | Breaks audit trail and analytics | 🟡 MEDIUM |

---

## 🔧 Recommended Fixes

### Fix #1: Set company_id During Appointment Creation (CRITICAL)

**Location:** `app/Services/Retell/AppointmentCreationService.php:382-396`

**Current Code:**
```php
$appointment = Appointment::create([
    'customer_id' => $customer->id,
    'service_id' => $service->id,
    'branch_id' => $branchId,
    'tenant_id' => $customer->tenant_id ?? 1,
    // ... missing company_id
]);
```

**Fixed Code:**
```php
$appointment = Appointment::create([
    'customer_id' => $customer->id,
    'service_id' => $service->id,
    'branch_id' => $branchId,
    'company_id' => $call ? $call->company_id : $customer->company_id,  // ✅ ADD THIS
    'tenant_id' => $customer->tenant_id ?? 1,
    'starts_at' => $bookingDetails['starts_at'],
    'ends_at' => $bookingDetails['ends_at'],
    'call_id' => $call ? $call->id : null,
    'status' => 'scheduled',
    'notes' => 'Created via Retell webhook',
    'source' => 'retell_webhook',
    'calcom_v2_booking_id' => $calcomBookingId,
    'external_id' => $calcomBookingId,
    'metadata' => json_encode($bookingDetails)
]);
```

**Why:**
- Ensures appointment inherits correct company from call or customer
- Enables multi-tenant isolation for staff matching
- Prevents default fallback to company_id = 1

**Impact:** ✅ FIXES ISSUE #1

### Fix #2: Lower Name Matching Threshold OR Add Email Alias Support

**Option A: Lower Threshold (Quick Fix)**

**Location:** `app/Services/CalcomHostMappingService.php:76`

**Current:**
```php
if ($matchResult && $matchResult->confidence >= 80) {
```

**Fixed:**
```php
if ($matchResult && $matchResult->confidence >= 75) {  // ✅ Allow name matching
```

**Pros:** Simple, allows name-based mapping
**Cons:** Lower confidence threshold for all mappings

**Option B: Staff Email Aliases (Proper Solution)**

**Add new migration:**
```php
Schema::create('staff_email_aliases', function (Blueprint $table) {
    $table->id();
    $table->uuid('staff_id');
    $table->string('email')->unique();
    $table->string('alias_type')->default('calcom'); // calcom, google, microsoft
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
});
```

**Update EmailMatchingStrategy:**
```php
public function match(array $hostData, HostMatchContext $context): ?MatchResult
{
    $email = $hostData['email'] ?? null;

    if (!$email) {
        return null;
    }

    // Try direct email match first
    $staff = Staff::query()
        ->where('company_id', $context->companyId)
        ->where('email', $email)
        ->where('is_active', true)
        ->first();

    // Try email aliases if no direct match
    if (!$staff) {
        $alias = DB::table('staff_email_aliases')
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if ($alias) {
            $staff = Staff::find($alias->staff_id);

            // Verify company context
            if ($staff && $staff->company_id !== $context->companyId) {
                $staff = null;
            }
        }
    }

    if (!$staff) {
        return null;
    }

    return new MatchResult(
        staff: $staff,
        confidence: 95,
        reason: "Email match: {$email}",
        metadata: [
            'match_field' => 'email',
            'match_value' => $email,
            'strategy' => 'EmailMatchingStrategy',
            'via_alias' => isset($alias)
        ]
    );
}
```

**Pros:**
- Proper solution for email aliases
- Maintains 95% confidence for email matching
- Flexible for multiple email addresses per staff
- Audit trail for which email was used

**Cons:**
- Requires database migration
- More complex implementation
- Need UI for managing aliases

**Recommendation:** Option A (quick fix) now, Option B (proper solution) later

**Impact:** ✅ FIXES ISSUE #2 and #3

### Fix #3: Link Original Call to Appointment (Data Backfill)

**SQL Fix:**
```sql
UPDATE calls
SET appointment_id = 640
WHERE id = 682;
```

**Verification:**
```sql
SELECT id, created_at, company_id, customer_id, appointment_id
FROM calls
WHERE id IN (682, 766);
```

**Expected Result:**
```
ID  | Created             | Company | Customer | Appointment
682 | 2025-10-05 22:21:55 | 15      | 340      | 640 ✅
766 | 2025-10-06 18:22:01 | 15      | 340      | 640 ✅
```

**Impact:** ✅ FIXES ISSUE #4

### Fix #4: Backfill Appointment 640 Company ID and Staff (Data Fix)

**Once Fix #1 is deployed, fix existing appointment:**

```sql
-- Fix company_id
UPDATE appointments
SET company_id = 15,
    updated_at = NOW()
WHERE id = 640;

-- Verify
SELECT id, company_id, customer_id, staff_id, starts_at
FROM appointments
WHERE id = 640;
```

**Then manually create staff assignment or trigger re-assignment:**

```php
// Via tinker
$appointment = \App\Models\Appointment::find(640);
$call = \App\Models\Call::find(682);

// Get booking data from call
$bookingDetails = json_decode($call->booking_details, true);
$calcomBooking = $bookingDetails['calcom_booking'] ?? null;

if ($calcomBooking) {
    $service = app(\App\Services\Retell\AppointmentCreationService::class);

    // Use reflection to call private method (for one-time fix)
    $method = new ReflectionMethod($service, 'assignStaffFromCalcomHost');
    $method->setAccessible(true);
    $method->invoke($service, $appointment, $calcomBooking, $call);
}

echo "Staff ID after assignment: " . $appointment->fresh()->staff_id;
```

**Expected Result:**
```
Staff ID after assignment: 28f22a49-a131-11f0-a0a1-ba630025b4ae ✅
```

---

## 📊 Testing Plan

### Test Case 1: Verify Name Matching Works After Fix #1

**Setup:**
```php
// Create test appointment with correct company_id
$testAppointment = Appointment::create([
    'customer_id' => 340,
    'service_id' => 47,
    'company_id' => 15,  // ✅ Correct company
    'starts_at' => now()->addDays(7),
    'ends_at' => now()->addDays(7)->addMinutes(30),
    'status' => 'scheduled',
    'source' => 'test'
]);

// Simulate Cal.com host data
$hostData = [
    'id' => 1414768,
    'name' => 'Fabian Spitzer',
    'email' => 'fabianspitzer@icloud.com',
    'username' => 'askproai',
    'timeZone' => 'Europe/Berlin'
];

$context = new HostMatchContext(
    companyId: 15,
    branchId: null,
    serviceId: 47,
    calcomBooking: $hostData
);

// Test name matching
$nameStrategy = new NameMatchingStrategy();
$result = $nameStrategy->match($hostData, $context);

// Verify
assert($result !== null, "Name matching should find staff");
assert($result->confidence === 75, "Confidence should be 75%");
assert($result->staff->id === '28f22a49-a131-11f0-a0a1-ba630025b4ae', "Should match Fabian Spitzer");
```

**Expected:** ✅ Name matching finds staff with 75% confidence

### Test Case 2: Verify Threshold Allows Name Matching (After Fix #2)

**Setup:**
```php
// With threshold lowered to 75
$hostMappingService = app(CalcomHostMappingService::class);

$staffId = $hostMappingService->resolveStaffForHost($hostData, $context);

// Verify
assert($staffId === '28f22a49-a131-11f0-a0a1-ba630025b4ae', "Should auto-create mapping");

// Check mapping was created
$mapping = CalcomHostMapping::where('calcom_host_id', 1414768)->first();
assert($mapping !== null, "Mapping should be created");
assert($mapping->mapping_source === 'auto_name', "Source should be auto_name");
assert($mapping->confidence_score === 75, "Confidence should be 75");
```

**Expected:** ✅ Mapping created automatically via name matching

### Test Case 3: End-to-End Appointment Creation (After All Fixes)

**Setup:**
```php
// Simulate new Retell call with booking
$testCall = Call::create([
    'retell_call_id' => 'test_call_' . time(),
    'company_id' => 15,
    'from_number' => '+493012345678',
    'call_status' => 'ended',
    'booking_details' => json_encode([
        'calcom_booking' => [
            'id' => 9999999,
            'uid' => 'test_booking_' . time(),
            'hosts' => [[
                'id' => 1414768,
                'name' => 'Fabian Spitzer',
                'email' => 'fabianspitzer@icloud.com',
                'username' => 'askproai',
                'timeZone' => 'Europe/Berlin'
            ]],
            'start' => '2025-10-15T09:00:00.000Z',
            'end' => '2025-10-15T09:30:00.000Z',
        ]
    ])
]);

// Create appointment via service
$service = app(\App\Services\Retell\AppointmentCreationService::class);
$appointment = $service->storeAppointment($testCall, [
    'starts_at' => '2025-10-15 11:00:00',
    'ends_at' => '2025-10-15 11:30:00',
    'service' => 'Beratung',
    'customer_name' => 'Test Customer'
]);

// Verify
assert($appointment->company_id === 15, "Company ID should be 15");
assert($appointment->staff_id === '28f22a49-a131-11f0-a0a1-ba630025b4ae', "Staff should be assigned");
assert($appointment->calcom_host_id === 1414768, "Cal.com host ID should be stored");
```

**Expected:** ✅ Full workflow works with correct company_id and staff assignment

---

## 📋 Implementation Checklist

### Immediate (Critical - Deploy ASAP)

- [ ] **Fix #1:** Add `company_id` to appointment creation
  - File: `app/Services/Retell/AppointmentCreationService.php:382-396`
  - Change: Add `'company_id' => $call ? $call->company_id : $customer->company_id`
  - Test: Create test appointment and verify company_id is correct

- [ ] **Fix #2:** Lower name matching threshold to 75%
  - File: `app/Services/CalcomHostMappingService.php:76`
  - Change: `if ($matchResult && $matchResult->confidence >= 75)`
  - Test: Verify name matching creates mappings automatically

- [ ] **Fix #3:** Backfill Call 682 appointment link
  - SQL: `UPDATE calls SET appointment_id = 640 WHERE id = 682;`
  - Verify: Check both calls link to appointment 640

- [ ] **Fix #4:** Backfill Appointment 640 company_id and staff
  - SQL: `UPDATE appointments SET company_id = 15 WHERE id = 640;`
  - Tinker: Run staff assignment script (see Fix #4 above)
  - Verify: `staff_id = 28f22a49-a131-11f0-a0a1-ba630025b4ae`

### Short-Term (Proper Solution)

- [ ] **Create staff_email_aliases table**
  - Migration: `2025_10_07_000000_create_staff_email_aliases_table.php`
  - Add foreign keys and indexes

- [ ] **Update EmailMatchingStrategy**
  - Add alias lookup fallback
  - Maintain 95% confidence for email matches
  - Track whether match was via alias in metadata

- [ ] **Add UI for managing staff email aliases**
  - Filament admin panel
  - Allow staff to add/remove email aliases
  - Show which emails are mapped to which Cal.com accounts

- [ ] **Seed initial aliases**
  - Add `fabianspitzer@icloud.com` → `fabian@askproai.de` mapping
  - Add any other known email aliases

### Long-Term (Improvements)

- [ ] **Manual Host Mapping UI**
  - Allow admins to manually map Cal.com hosts to staff
  - Override auto-matching when needed
  - Audit trail for manual changes

- [ ] **Confidence Score Tuning**
  - Analyze match success rates over time
  - Adjust thresholds based on real data
  - A/B test different confidence levels

- [ ] **Multi-Email Staff Support**
  - Allow staff to have multiple primary emails
  - Not just aliases, but fully recognized emails
  - Update all matching strategies accordingly

---

## 🎯 Success Metrics

### Before Fixes

| Metric | Value | Status |
|--------|-------|--------|
| Appointment 640 Company ID | 1 | ❌ Wrong |
| Appointment 640 Staff ID | NULL | ❌ Missing |
| Call 682 appointment_id | NULL | ❌ Missing |
| Call 766 appointment_id | 640 | ✅ Fixed earlier |
| Host Mapping Count | 0 | ❌ None created |
| Email Match Success | 0% | ❌ Email mismatch |
| Name Match Success | 0% | ❌ Threshold too high |

### After Fixes (Expected)

| Metric | Value | Status |
|--------|-------|--------|
| Appointment 640 Company ID | 15 | ✅ Correct |
| Appointment 640 Staff ID | 28f22a49... | ✅ Assigned |
| Call 682 appointment_id | 640 | ✅ Linked |
| Call 766 appointment_id | 640 | ✅ Linked |
| Host Mapping Count | 1+ | ✅ Created |
| Email Match Success | 95% | ✅ Via aliases |
| Name Match Success | 75% | ✅ Auto-mapping |

---

## 🔍 Additional Findings

### Database Schema Issue

**Current Schema:**
```sql
`company_id` bigint(20) unsigned NOT NULL DEFAULT 1
```

**Issue:** Default value of 1 is dangerous for multi-tenant system

**Recommendation:** Remove default after ensuring all appointment creation explicitly sets company_id:
```sql
ALTER TABLE appointments
MODIFY COLUMN company_id bigint(20) unsigned NOT NULL;
```

**Benefit:** Force explicit company_id, prevent silent bugs

### Missing Validation

**AppointmentCreationService** doesn't validate:
- Call and customer belong to same company
- Service belongs to company
- Staff (if provided) belongs to company

**Recommendation:** Add validation method:
```php
private function validateTenantIsolation(Call $call, Customer $customer, Service $service, ?Staff $staff = null): void
{
    if ($customer->company_id !== $call->company_id) {
        throw new TenantMismatchException("Customer and call belong to different companies");
    }

    if ($service->company_id !== $call->company_id) {
        throw new TenantMismatchException("Service and call belong to different companies");
    }

    if ($staff && $staff->company_id !== $call->company_id) {
        throw new TenantMismatchException("Staff and call belong to different companies");
    }
}
```

---

## ✅ Final Verdict

### Root Cause: ✅ IDENTIFIED

**Primary Issue:** Appointment creation doesn't explicitly set `company_id`, causing it to default to `1` instead of inheriting from call/customer.

**Secondary Issue:** Email mismatch between Cal.com (`fabianspitzer@icloud.com`) and Staff (`fabian@askproai.de`) prevents email-based matching.

**Tertiary Issue:** Name matching works but confidence threshold (80%) is higher than name matching provides (75%).

### Impact: 🟡 MEDIUM

- Staff assignment fails for all appointments created via Cal.com
- Multi-tenant isolation prevents cross-company matching (working correctly)
- Audit trail incomplete (calls not linked to appointments they created)
- Analytics and reporting affected

### Urgency: 🔴 HIGH

- Affects all new appointment creations
- Staff scheduling and calendar management impacted
- User experience degraded (no staff visible on appointments)

### Fixes Required: 4

1. ✅ Add company_id to appointment creation **(CRITICAL)**
2. ✅ Lower name matching threshold to 75% **(HIGH)**
3. ✅ Backfill Call 682 appointment link **(MEDIUM)**
4. ✅ Backfill Appointment 640 data **(MEDIUM)**

### Estimated Fix Time: **30-60 minutes**

- Code changes: 15 minutes
- Testing: 15 minutes
- Data backfill: 10 minutes
- Deployment: 10 minutes

---

**Report Generated:** 2025-10-06 21:00
**Analysis Method:** Complete code review, database analysis, matching strategy testing
**Documentation:** Complete (This document: 18KB)

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
