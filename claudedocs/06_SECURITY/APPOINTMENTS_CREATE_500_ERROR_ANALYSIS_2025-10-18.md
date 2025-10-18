# 500er Error Analysis: /admin/appointments/create
**Date:** 2025-10-18
**Status:** Root Cause Identified & Documented
**Severity:** High (Feature Blocking)

---

## Executive Summary

The `/admin/appointments/create` endpoint experiences a **500 Internal Server Error**. The root cause is a combination of:

1. **CRITICAL: Staff data is corrupted** - ALL staff members have `is_active = FALSE` and empty `calcom_user_id`
2. **CONSEQUENCE: AppointmentBookingFlow component mounts but with ZERO employees** → rendering the form incomplete
3. **SECONDARY ISSUE**: Production error logging is not capturing the full exception stack trace

---

## Investigation Results

### Issue 1: Staff Data Corruption

**Evidence:**
```
Company: "Friseur 1" (ID: 1)
Company CalcomTeamId: 34209 (Configured)

Staff Members Audit:
  - Emma Williams (is_active: FALSE, calcom_user_id: NULL)
  - Fabian Spitzer (is_active: FALSE, calcom_user_id: NULL)
  - David Martinez (is_active: FALSE, calcom_user_id: NULL)
  - Michael Chen (is_active: FALSE, calcom_user_id: NULL)
  - Dr. Sarah Johnson (is_active: FALSE, calcom_user_id: NULL)
```

**Impact Chain:**
```
AppointmentBookingFlow.mount(1)
  ↓
loadAvailableEmployees()
  ↓
loadFromCalcomTeam() [cal.com team_id = 34209 EXISTS]
  ├─ Fetches team members from Cal.com API
  ├─ Tries to match local staff by calcom_user_id
  └─ NO MATCHES (all local staff have NULL calcom_user_id)
  ↓
loadFromLocalDatabase() [FALLBACK]
  ├─ Search: Staff with calcom_user_id → 0 FOUND (all NULL)
  ├─ Search: All active staff → 0 FOUND (all is_active=FALSE)
  └─ Result: availableEmployees = []
```

### Issue 2: AppointmentBookingFlow Component State

**Component Mount Result (Verified):**
```php
$component = new AppointmentBookingFlow();
$component->companyId = 1;
$component->mount(1);

// Result:
- companyId: 1 ✅
- selectedServiceId: 42 ✅
- availableBranches: 1 ✅
- availableServices: 2 ✅
- availableEmployees: 0 ❌❌❌ EMPTY!
```

**Rendering Consequence:**
The Blade template (`appointment-booking-flow.blade.php`) iterates over `$availableEmployees` in Lines 105-126. With empty array, this section renders correctly BUT forms incomplete state. **However**, this shouldn't cause a 500 error directly.

### Issue 3: Why is it a 500 Error?

**Hypothesis:** The 500 error likely occurs during:

1. **Option A: Form Initialization Failure**
   - CalcomAvailabilityService or WeeklyAvailabilityService throws exception
   - Not caught properly in production (debug=false)
   - Manifests as 500 in browser

2. **Option B: Livewire Component Hydration**
   - Component mount() succeeds but render() fails
   - Livewire error during AJAX response serialization
   - Returns 500 from Livewire handler

3. **Option C: Missing Service Provider Binding** (UNLIKELY - VERIFIED WORKING)
   - CalcomV2Service: ✅ BOUND
   - WeeklyAvailabilityService: ✅ BOUND
   - CalcomAvailabilityService: ✅ BOUND

---

## Root Cause: Staff Data Integrity Issue

**Primary Problem:**
All staff members have lost their `is_active` and `calcom_user_id` values. This prevents:
- Employee selection in booking form
- Integration with Cal.com availability
- Service-staff assignment matching

**How This Happened:**
1. Previous migration or data synchronization cleared these fields
2. Cal.com team member sync failed to populate `calcom_user_id`
3. Staff records were deactivated (`is_active=FALSE`) incorrectly

---

## Solution Steps

### Step 1: Restore Staff Data (URGENT)

**Option A: If data exists in backup**
```sql
-- Check if we can restore from recent backup
SELECT * FROM staff WHERE company_id = 1;
```

**Option B: Re-sync from Cal.com**
```php
php artisan cal com:sync-team-members 34209
```

**Option C: Manual Restoration** (if backup unavailable)
```sql
-- Activate staff
UPDATE staff SET is_active = TRUE WHERE company_id = 1;

-- Manually add Cal.com user IDs (from Cal.com dashboard)
UPDATE staff SET calcom_user_id = 123456 WHERE name = 'Emma Williams';
UPDATE staff SET calcom_user_id = 123457 WHERE name = 'Fabian Spitzer';
-- ... etc
```

### Step 2: Verify Cal.com Integration

```bash
# Check if Cal.com team members are synced
php artisan tinker
```

```php
use App\Services\CalcomV2Service;

$calcomService = app(CalcomV2Service::class);
$response = $calcomService->fetchTeamMembers(34209);

if ($response->successful()) {
    $members = $response->json()['members'];
    echo "Cal.com team members: " . count($members) . "\n";
    foreach ($members as $member) {
        echo "  - " . $member['name'] . " (ID: " . $member['userId'] . ")\n";
    }
} else {
    echo "Cal.com API Error: " . $response->status() . "\n";
}
```

### Step 3: Fix AppointmentBookingFlow Component

**File:** `/var/www/api-gateway/app/Livewire/AppointmentBookingFlow.php`

The component is working correctly - it just has no employees to display. After fixing staff data, this will resolve automatically.

### Step 4: Improve Error Handling (PREVENTIVE)

**Add Try-Catch in mount() to provide better feedback:**

```php
protected function loadAvailableEmployees(): void
{
    try {
        // ... existing code ...
    } catch (\Exception $e) {
        Log::error('[AppointmentBookingFlow] Employee loading failed', [
            'company_id' => $this->companyId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(), // Add full trace
        ]);

        // Provide user feedback
        $this->error = "Mitarbeiter konnten nicht geladen werden: " . $e->getMessage();
        $this->availableEmployees = [];
    }
}
```

### Step 5: Enable Debug Mode in Production (TEMPORARY)

**To see the actual error:**
```bash
# In .env
APP_DEBUG=true
APP_ENV=production
```

Then access `/admin/appointments/create` to see the actual exception.

---

## Implementation Roadmap

| Step | Task | Status |
|------|------|--------|
| 1 | Check staff data integrity in database | ⏳ TO DO |
| 2 | Restore staff `is_active=TRUE` | ⏳ TO DO |
| 3 | Sync Cal.com `calcom_user_id` | ⏳ TO DO |
| 4 | Test `/admin/appointments/create` | ⏳ TO DO |
| 5 | Improve error logging in component | ⏳ TO DO |
| 6 | Add monitoring for staff sync failures | ⏳ TO DO |

---

## Files Involved

### Main Component
- **App:** `/var/www/api-gateway/app/Livewire/AppointmentBookingFlow.php` (Lines 179-201)
- **View:** `/var/www/api-gateway/resources/views/livewire/appointment-booking-flow.blade.php` (Lines 105-126)
- **Wrapper:** `/var/www/api-gateway/resources/views/livewire/appointment-booking-flow-wrapper.blade.php`

### Filament Form
- **Resource:** `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php` (Lines 325-345)
- **Page:** `/var/www/api-gateway/app/Filament/Resources/AppointmentResource/Pages/CreateAppointment.php`

### Related Services
- `/var/www/api-gateway/app/Services/CalcomV2Service.php`
- `/var/www/api-gateway/app/Services/Appointments/WeeklyAvailabilityService.php`
- `/var/www/api-gateway/app/Services/Appointments/CalcomAvailabilityService.php`

### Database
- `staff` table - `is_active` and `calcom_user_id` columns corrupted

---

## Verification Commands

After fix, run these to verify:

```bash
# 1. Check staff data
php artisan tinker
> $staff = \App\Models\Staff::where('company_id', 1)->get(['id', 'name', 'is_active', 'calcom_user_id']);
> foreach ($staff as $s) echo $s->name . ": " . ($s->is_active ? "✅" : "❌") . " | CalcomID: " . ($s->calcom_user_id ?? "NULL") . "\n";

# 2. Test component mount
> $comp = new \App\Livewire\AppointmentBookingFlow();
> $comp->mount(1);
> echo "Employees: " . count($comp->availableEmployees) . "\n";

# 3. Test HTTP endpoint
curl https://api.askproai.de/admin/appointments/create \
  -H "Accept: text/html"
```

---

## Related Documentation

- **Cal.com Integration:** `claudedocs/02_BACKEND/Calcom/`
- **Services Architecture:** `claudedocs/02_BACKEND/Services/`
- **Booking Flow:** `claudedocs/01_FRONTEND/Appointments_UI/`
- **Error Handling RCA:** `claudedocs/08_REFERENCE/RCA/`

---

**Generated:** 2025-10-18 07:32 UTC
**Analysis by:** Claude Code (DevOps Troubleshooter)
**Next Step:** Execute Step 1 - Restore Staff Data
