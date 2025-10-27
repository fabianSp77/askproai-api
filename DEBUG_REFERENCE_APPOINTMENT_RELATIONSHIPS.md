# Debug Reference: Appointment Relationships Issue

## Quick Facts

| Aspect | Details |
|--------|---------|
| **Error** | `Table 'appointment_wishes' doesn't exist` |
| **Location** | `/admin/calls` page (CallResource) |
| **Severity** | Critical (blocks entire page) |
| **Root Cause** | DB backup missing 3 FKs + 1 table |
| **Fix Complexity** | Low (comment-out + try-catch) |
| **Deployment Risk** | LOW |
| **Reversible** | YES |

---

## Error Chain Analysis

### Step 1: Page Load
User navigates to `/admin/calls` → Filament loads CallResource table

### Step 2: Query Building
`CallResource::table()` method calls `modifyQueryUsing()`:
```php
->with('appointmentWishes', fn($q) => ...) // ← FIRST ERROR HERE
->with('appointments', fn($q) => ...)       // ← SECOND ERROR HERE
```

### Step 3: Query Compilation Fails
Eloquent attempts to:
1. Build SQL for appointmentWishes relationship
2. Look up appointment_wishes table schema
3. Generate JOIN query

### Step 4: Database Error
```
SQLSTATE[42S02]: Table or view not found:
1146 Table 'api_gateway.appointment_wishes' doesn't exist
```

### Step 5: Page Crashes
500 error returned to user

---

## Missing Database Elements

### Element 1: appointmentWishes Table
```
Database: api_gateway
Table: appointment_wishes
Status: MISSING
Used By: Call::appointmentWishes() HasMany relationship
Queries: 3 locations in CallResource
```

**Evidence**:
```bash
$ php artisan tinker
>>> DB::table('appointment_wishes')->count();
SQLSTATE[42S02]: Table or view not found
```

### Element 2: appointments.call_id Column
```
Database: api_gateway
Table: appointments
Column: call_id
Status: MISSING
Foreign Key: calls.id
Used By: Call::latestAppointment() HasOne relationship
Queries: Via $record->appointment accessor
```

**Current appointments columns** (missing call_id):
```
id, company_id, branch_id, customer_id, staff_id, service_id,
status, starts_at, ends_at, notes, created_at, updated_at,
deleted_at, is_composite, composite_group_uid, segments,
google_event_id, outlook_event_id, is_recurring, recurring_pattern,
external_calendar_source, external_calendar_id, parent_appointment_id,
is_nested, parent_booking_id, has_nested_slots, phases
```

### Element 3: calls.converted_appointment_id Column
```
Database: api_gateway
Table: calls
Column: converted_appointment_id
Status: MISSING
Foreign Key: appointments.id
Used By: Call::convertedAppointment() BelongsTo relationship
Queries: Fallback in $record->appointment accessor
```

**Current calls columns** (missing converted_appointment_id):
```
id, company_id, branch_id, customer_id, retell_call_id, conversation_id,
from_number, to_number, status, direction, duration_sec, calculated_cost,
started_at, ended_at, metadata, has_appointment, created_at, updated_at, deleted_at
```

---

## Code Issues Identified

### Issue 1: Eager-Loading appointmentWishes (CallResource line 200-203)
```php
->with('appointmentWishes', function ($q) {
    $q->where('status', 'pending')->latest();
})
```

**Problem**: Table doesn't exist
**Solution**: Comment out (table missing from backup)
**Impact**: Cannot show pending appointment wishes

---

### Issue 2: Eager-Loading appointments (CallResource line 204-207)
```php
->with('appointments', function ($q) {
    $q->with('service');
})
```

**Problem**: appointments.call_id FK doesn't exist
**Solution**: Comment out (column missing from backup)
**Impact**: Cannot load appointments for calls

---

### Issue 3: Booking Status Query (CallResource line 234-235)
```php
} elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
    return '⏰ Wunsch';
}
```

**Problem**: Direct call to missing table
**Solution**: Comment out (table missing from backup)
**Impact**: Cannot show '⏰ Wunsch' status (shows '❓ Offen' instead)

---

### Issue 4: Appointment Wishes Lookup (CallResource line 294-298)
```php
$unresolvedWish = $record->appointmentWishes()
    ->where('status', 'pending')
    ->latest()
    ->first();
```

**Problem**: Direct call to missing table
**Solution**: Comment out (table missing from backup)
**Impact**: Cannot show pending wish dates (shows '−' instead)

---

### Issue 5: Appointment Accessor (Call model line 176-206)
```php
public function getAppointmentAttribute(): ?Appointment
{
    if (!$this->relationLoaded('latestAppointment')) {
        $this->load('latestAppointment'); // ← CRASHES: call_id FK missing
    }

    $latest = $this->latestAppointment;

    if ($latest) {
        return $latest;
    }

    if (!$this->relationLoaded('convertedAppointment')) {
        $this->load('convertedAppointment'); // ← CRASHES: converted_appointment_id FK missing
    }

    return $this->convertedAppointment;
}
```

**Problem**: Both relationship loads fail due to missing FKs
**Solution**: Wrap in try-catch blocks
**Impact**: Safely returns null instead of crashing on every $record->appointment access

---

## Relationship Definitions

### Working (Not Fixed)
```php
// ✓ These work fine - tables and FKs exist
$call->customer()       // customers table exists
$call->company()        // companies table exists
$call->branch()         // branches table exists
$call->phoneNumber()    // phone_numbers table exists
```

### Broken (Fixed in This Update)
```php
// ✗ These fail - missing FKs or tables
$call->appointmentWishes()  // ✗ appointment_wishes table missing
$call->appointments()       // ✗ appointments.call_id FK missing
$call->appointment          // ✗ latestAppointment uses call_id FK
```

---

## Fix Implementation

### CallResource.php Changes

**Location**: Line 200-207 (Eager-loading)
```diff
- ->with('appointmentWishes', function ($q) {
-     $q->where('status', 'pending')->latest();
- })
- ->with('appointments', function ($q) {
-     $q->with('service');
- })
+ // ❌ SKIPPED: appointmentWishes (table missing from DB backup)
+ // ->with('appointmentWishes', function ($q) {
+ //     $q->where('status', 'pending')->latest();
+ // })
+ // ❌ SKIPPED: appointments (call_id foreign key missing from appointments table)
+ // ->with('appointments', function ($q) {
+ //     $q->with('service');
+ // })
```

**Location**: Line 234-239 (Booking status check)
```diff
  if ($record->appointment && $record->appointment->starts_at) {
      return '✅ Gebucht';
- } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
-     return '⏰ Wunsch';
  }
+ // ❌ SKIPPED: appointmentWishes check (table missing from DB backup)
+ // } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
+ //     return '⏰ Wunsch';
+ // }
  return '❓ Offen';
```

**Location**: Line 294-311 (Appointment summary)
```diff
- $unresolvedWish = $record->appointmentWishes()
-     ->where('status', 'pending')
-     ->latest()
-     ->first();
-
- if ($unresolvedWish && $unresolvedWish->desired_date) {
-     $wishDate = \Carbon\Carbon::parse($unresolvedWish->desired_date);
-     return new HtmlString(
-         '<span class="text-xs text-orange-600">⏰ ' .
-         $wishDate->locale('de')->isoFormat('ddd DD.MM') .
-         '</span>'
-     );
- }
+ // ❌ SKIPPED: appointmentWishes check (table missing from DB backup)
+ // try {
+ //     $unresolvedWish = $record->appointmentWishes()
+ //         ->where('status', 'pending')
+ //         ->latest()
+ //         ->first();
+ //
+ //     if ($unresolvedWish && $unresolvedWish->desired_date) {
+ //         $wishDate = \Carbon\Carbon::parse($unresolvedWish->desired_date);
+ //         return new HtmlString(
+ //             '<span class="text-xs text-orange-600">⏰ ' .
+ //             $wishDate->locale('de')->isoFormat('ddd DD.MM') .
+ //             '</span>'
+ //         );
+ //     }
+ // } catch (\Exception $e) {
+ //     // silently ignore if table missing
+ // }
```

### Call.php Changes

**Location**: Line 176-206 (Appointment accessor)
```diff
  public function getAppointmentAttribute(): ?Appointment
  {
+     try {
          // Load latest appointment if not already loaded
          if (!$this->relationLoaded('latestAppointment')) {
              $this->load('latestAppointment');
          }

          $latest = $this->latestAppointment;

          if ($latest) {
              return $latest;
          }
+     } catch (\Exception $e) {
+         // Silently handle missing call_id foreign key from DB backup
+         // The call_id column doesn't exist in appointments table from Sept 21 backup
+     }

+     try {
          // Fallback to legacy converted appointment
          if (!$this->relationLoaded('convertedAppointment')) {
              $this->load('convertedAppointment');
          }

          return $this->convertedAppointment;
+     } catch (\Exception $e) {
+         // Silently handle missing converted_appointment_id foreign key from DB backup
+         // The converted_appointment_id column doesn't exist in calls table from Sept 21 backup
+         return null;
+     }
  }
```

---

## Test Results

### Query Execution Test
```
✓ Query executed successfully
✓ Loaded 3 records
✓ All relationships working except missing ones
```

### Sample Output
```
Call ID: 102
  Appointment: null (expected - missing FK)
  Customer: Frau Gesa Großmann B.Eng.
  Company: Demo Company
  Branch: Filiale Charlottenburg
```

### Relationship Status
| Relationship | Status | Evidence |
|--------------|--------|----------|
| customer | ✓ Works | Loads correctly |
| company | ✓ Works | Loads correctly |
| branch | ✓ Works | Loads correctly |
| phoneNumber | ✓ Works | Loads correctly |
| appointmentWishes | ✗ Skipped | Table missing |
| appointments | ✗ Skipped | FK missing |
| appointment | ✓ Safe | Returns null |

---

## Restoration Plan (Future)

When database is fully restored with missing tables/FKs:

### Step 1: Create Missing FK Columns
```sql
ALTER TABLE appointments ADD COLUMN call_id BIGINT UNSIGNED;
ALTER TABLE calls ADD COLUMN converted_appointment_id BIGINT UNSIGNED;
```

### Step 2: Create Missing Table
```sql
CREATE TABLE appointment_wishes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    call_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED,
    desired_date DATETIME,
    status VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (call_id) REFERENCES calls(id)
);
```

### Step 3: Uncomment Code
1. CallResource.php line 200-207: uncomment eager-loading
2. CallResource.php line 234-239: uncomment status check
3. CallResource.php line 295-311: uncomment wish lookup
4. Call.php: Keep try-catch (defensive)

### Step 4: Deploy & Test
```bash
php artisan cache:clear
php artisan config:clear
# Run tests to verify appointments load correctly
```

---

## Debugging Tips

### To Check Current State:
```bash
php artisan tinker
>>> DB::table('appointments')->count();
>>> DB::table('appointment_wishes')->count(); // This will error
>>> Schema::hasColumn('appointments', 'call_id'); // false
>>> Schema::hasColumn('calls', 'converted_appointment_id'); // false
```

### To Test Call Model:
```bash
php artisan tinker
>>> $call = Call::first();
>>> $call->appointment; // Returns null (safe)
>>> $call->customer->name; // Works
```

### To Test Page Load:
```bash
# Navigate to /admin/calls
# Should load without 500 error
# Appointment column will show "−" or empty
```

---

**Document Version**: 1.0
**Last Updated**: 2025-10-27
**Status**: COMPLETE
