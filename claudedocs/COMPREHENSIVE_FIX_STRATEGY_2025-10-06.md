# Comprehensive Fix Strategy: Duplicate Booking Prevention
**Date**: 2025-10-06
**Issue**: Cal.com idempotency returns existing bookings, causing duplicate appointments in database

## 🔍 Analysis Summary

### Root Cause
Cal.com's idempotency logic returns existing bookings when same parameters are sent:
- Same email (`termin@askproai.de` - fallback email)
- Same time slot (2025-10-10 08:00)
- Same event type (2563193)
- Within ~35 minute window

**Evidence**: Call 688 received booking from Call 687 created 35 minutes earlier
- Booking ID: `8Fxv4pCqnb1Jva1w9wn5wX`
- Created timestamp: `2025-10-06T09:05:21.002Z` (Call 687's time)
- Metadata call_id: `call_927bf219b2cc20cd24dc97c9f0b` (Call 687's ID)
- Customer name: "Hansi Sputer" (Call 687's customer, NOT "Hans Schuster")

### Current Vulnerabilities

#### 1. **Code Level** (`AppointmentCreationService.php`)
**Location**: Lines 574-602 (`bookInCalcom` method)
```php
if ($response->successful()) {
    $appointmentData = $response->json();
    $bookingId = $appointmentData['data']['id'] ?? $appointmentData['id'] ?? null;

    // ❌ NO VALIDATION:
    // - Is this a NEW booking? (createdAt check)
    // - Does metadata match current request? (call_id check)
    // - Already exists in database? (duplicate check)

    return ['booking_id' => $bookingId, 'booking_data' => $appointmentData];
}
```

**Location**: Lines 320-373 (`createLocalRecord` method)
```php
$appointment = Appointment::create([
    // ... fields ...
    'calcom_v2_booking_id' => $calcomBookingId,  // ❌ NO DUPLICATE CHECK
]);
```

#### 2. **Database Level**
```sql
-- Current Schema:
Field: calcom_v2_booking_id
Type: varchar(255)
Null: YES
Key: MUL  -- Non-unique index only!
```

**Problem**: Database allows duplicate `calcom_v2_booking_id` values

## 🛡️ Multi-Layer Defense Strategy

### Layer 1: Booking Freshness Validation (Primary Defense)
**File**: `app/Services/Retell/AppointmentCreationService.php`
**Method**: `bookInCalcom()` - Line 574
**Priority**: 🔴 **CRITICAL**

**Logic**:
```php
if ($response->successful()) {
    $appointmentData = $response->json();
    $bookingData = $appointmentData['data'] ?? $appointmentData;
    $bookingId = $bookingData['id'] ?? $appointmentData['id'] ?? null;

    // FIX 1: Validate booking freshness
    $createdAt = isset($bookingData['createdAt'])
        ? Carbon::parse($bookingData['createdAt'])
        : null;

    if ($createdAt && $createdAt->lt(now()->subSeconds(30))) {
        Log::error('🚨 STALE BOOKING DETECTED', [
            'booking_id' => $bookingId,
            'created_at' => $createdAt->toIso8601String(),
            'age_seconds' => now()->diffInSeconds($createdAt),
            'threshold' => 30,
            'call_id' => $call?->retell_call_id,
            'metadata_call_id' => $bookingData['metadata']['call_id'] ?? null
        ]);
        return null; // Reject stale booking
    }

    // ... rest of method
}
```

**Threshold**: 30 seconds (configurable)
- Fresh bookings created within last 30 seconds → ACCEPT
- Older bookings → REJECT as potential duplicates from idempotency

### Layer 2: Metadata Call ID Validation (Secondary Defense)
**File**: Same location
**Priority**: 🔴 **CRITICAL**

**Logic**:
```php
// FIX 2: Validate metadata call_id matches current request
$bookingCallId = $bookingData['metadata']['call_id'] ?? null;
if ($bookingCallId && $call && $bookingCallId !== $call->retell_call_id) {
    Log::error('🚨 CALL ID MISMATCH', [
        'expected_call_id' => $call->retell_call_id,
        'received_call_id' => $bookingCallId,
        'booking_id' => $bookingId,
        'created_at' => $createdAt?->toIso8601String()
    ]);
    return null; // Reject booking from different call
}
```

**Rationale**: Cal.com stores `call_id` in booking metadata. If returned booking has different `call_id`, it belongs to a different call.

### Layer 3: Database Duplicate Check (Tertiary Defense)
**File**: `app/Services/Retell/AppointmentCreationService.php`
**Method**: `createLocalRecord()` - Line 320
**Priority**: 🟡 **IMPORTANT**

**Logic**:
```php
public function createLocalRecord(
    Customer $customer,
    Service $service,
    array $bookingDetails,
    ?string $calcomBookingId = null,
    ?Call $call = null,
    ?array $calcomBookingData = null
): Appointment {
    // FIX 3: Check for existing appointment with same Cal.com booking ID
    if ($calcomBookingId) {
        $existingAppointment = Appointment::where('calcom_v2_booking_id', $calcomBookingId)
            ->first();

        if ($existingAppointment) {
            Log::error('🚨 DUPLICATE BOOKING ID IN DATABASE', [
                'existing_appointment_id' => $existingAppointment->id,
                'existing_call_id' => $existingAppointment->call_id,
                'existing_customer_id' => $existingAppointment->customer_id,
                'new_call_id' => $call?->id,
                'new_customer_id' => $customer->id,
                'calcom_booking_id' => $calcomBookingId
            ]);

            // DECISION: Return existing appointment instead of creating duplicate
            return $existingAppointment;
        }
    }

    // Proceed with creation if no duplicate found
    $appointment = Appointment::create([/* ... */]);
    // ...
}
```

**Rationale**: Last line of defense before database insertion. Prevents duplicates even if API validation fails.

### Layer 4: Database Unique Constraint (Safety Net)
**File**: New migration
**Priority**: 🟡 **IMPORTANT**

**Migration**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // IMPORTANT: Clean up existing duplicates BEFORE adding constraint

        // 1. Find duplicate calcom_v2_booking_ids
        $duplicates = DB::table('appointments')
            ->select('calcom_v2_booking_id')
            ->whereNotNull('calcom_v2_booking_id')
            ->groupBy('calcom_v2_booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('calcom_v2_booking_id');

        // 2. For each duplicate, keep oldest appointment, delete newer ones
        foreach ($duplicates as $bookingId) {
            $appointments = DB::table('appointments')
                ->where('calcom_v2_booking_id', $bookingId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Keep first (oldest), delete rest
            $toDelete = $appointments->slice(1)->pluck('id');

            if ($toDelete->isNotEmpty()) {
                DB::table('appointments')
                    ->whereIn('id', $toDelete)
                    ->delete();

                Log::warning('Migration: Deleted duplicate appointments', [
                    'calcom_booking_id' => $bookingId,
                    'kept_appointment_id' => $appointments->first()->id,
                    'deleted_appointment_ids' => $toDelete->toArray()
                ]);
            }
        }

        // 3. Add unique constraint
        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('calcom_v2_booking_id', 'unique_calcom_v2_booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('unique_calcom_v2_booking_id');
        });
    }
};
```

**Note**: Migration will fail if duplicates exist (MySQL constraint violation). Cleanup logic removes duplicates first.

## 📋 Implementation Order

### Phase 1: Code Fixes (Immediate - No Breaking Changes)
1. ✅ **Fix 1**: Booking freshness validation in `bookInCalcom()`
2. ✅ **Fix 2**: Metadata call_id validation in `bookInCalcom()`
3. ✅ **Fix 3**: Duplicate check in `createLocalRecord()`

**Deployment**: Safe to deploy immediately - adds validation, doesn't break existing functionality

### Phase 2: Database Constraint (After Code Fixes)
4. ✅ **Fix 4**: Add unique constraint migration
5. ✅ Clean up existing duplicate (Appointment ID 643)

**Deployment**: Run after code fixes are deployed and verified

### Phase 3: Testing & Validation
6. ✅ Unit tests for all three validation layers
7. ✅ Integration test: Attempt duplicate booking
8. ✅ End-to-end test: Real booking scenario

## 🧪 Testing Strategy

### Unit Tests
**File**: `tests/Unit/Services/Retell/AppointmentCreationServiceTest.php`

```php
public function test_rejects_stale_booking_from_idempotency()
{
    $oldTimestamp = now()->subMinutes(5)->toIso8601String();

    $calcomResponse = [
        'data' => [
            'id' => 12345,
            'createdAt' => $oldTimestamp,
            'metadata' => ['call_id' => 'old_call_id']
        ]
    ];

    // Mock Cal.com returning old booking
    Http::fake(['*' => Http::response($calcomResponse, 200)]);

    $result = $this->appointmentService->bookInCalcom(/* ... */);

    $this->assertNull($result); // Should reject stale booking
}

public function test_rejects_booking_with_mismatched_call_id()
{
    $calcomResponse = [
        'data' => [
            'id' => 12345,
            'createdAt' => now()->toIso8601String(),
            'metadata' => ['call_id' => 'different_call_id']
        ]
    ];

    Http::fake(['*' => Http::response($calcomResponse, 200)]);

    $call = Call::factory()->create(['retell_call_id' => 'correct_call_id']);
    $result = $this->appointmentService->bookInCalcom(/* ... */, $call);

    $this->assertNull($result); // Should reject mismatched call_id
}

public function test_prevents_duplicate_calcom_booking_id_in_database()
{
    $bookingId = 'duplicate_booking_id';

    // Create first appointment
    $appointment1 = Appointment::factory()->create([
        'calcom_v2_booking_id' => $bookingId
    ]);

    // Attempt to create second appointment with same booking ID
    $appointment2 = $this->appointmentService->createLocalRecord(
        /* ... */,
        $bookingId
    );

    // Should return existing appointment, not create new one
    $this->assertEquals($appointment1->id, $appointment2->id);
    $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', $bookingId)->count());
}
```

### Integration Test
Simulate actual duplicate booking scenario:
1. Call 1 books time slot → Success
2. Call 2 books SAME time slot 30 seconds later → Should be rejected

### Manual Verification
```bash
# 1. Deploy fixes to staging
# 2. Make test call booking time slot A
# 3. Immediately make second test call booking same time slot A
# 4. Verify: Only ONE appointment created in database
# 5. Verify: Second call logs show rejection with reason
```

## 🚨 Rollback Plan

### If Issues Arise After Deployment

**Code Fixes (Phase 1)**:
```bash
git revert <commit-hash>
php artisan config:clear
php artisan route:clear
```

**Database Migration (Phase 2)**:
```bash
php artisan migrate:rollback --step=1
```

**Emergency Disable**:
Add feature flag in `.env`:
```
CALCOM_DUPLICATE_PREVENTION_ENABLED=false
```

Wrap validation code:
```php
if (config('app.calcom_duplicate_prevention_enabled', true)) {
    // Validation logic
}
```

## 📊 Monitoring & Alerts

### Metrics to Track
1. **Rejection Rate**: % of bookings rejected due to staleness/mismatch
2. **Duplicate Attempts**: Count of duplicate booking_id detections
3. **Idempotency Hits**: How often Cal.com returns existing bookings

### Log Queries
```bash
# Find stale booking rejections
grep "STALE BOOKING DETECTED" storage/logs/laravel.log

# Find call ID mismatches
grep "CALL ID MISMATCH" storage/logs/laravel.log

# Find duplicate booking ID attempts
grep "DUPLICATE BOOKING ID IN DATABASE" storage/logs/laravel.log
```

### Alert Conditions
- **High rejection rate** (>10%): May indicate issue with timestamp handling
- **Frequent duplicates**: Investigate Cal.com idempotency behavior changes
- **Database constraint violations**: Code validation failed - investigate immediately

## 🎯 Success Criteria

### Immediate Goals
- ✅ Zero duplicate `calcom_v2_booking_id` values in database
- ✅ All stale bookings rejected with clear log messages
- ✅ All call_id mismatches detected and rejected

### Long-term Goals
- ✅ 100% booking data integrity
- ✅ Full audit trail of duplicate attempts
- ✅ Automated alerting for anomalies

## 📝 Documentation Updates

### Update Files
1. `README.md`: Add section on duplicate prevention
2. `docs/API_INTEGRATION.md`: Document Cal.com idempotency behavior
3. `docs/TROUBLESHOOTING.md`: Add duplicate booking section

### Team Communication
- Notify backend team of changes
- Update Retell webhook documentation
- Add monitoring dashboard for booking metrics

---

**Next Steps**: Begin Phase 1 implementation immediately
