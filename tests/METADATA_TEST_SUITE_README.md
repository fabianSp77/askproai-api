# Appointment Metadata Test Suite

**ASK-010: Data Consistency Testing Strategy**

## Overview

This comprehensive test suite validates that ALL appointment metadata fields are correctly populated throughout the complete appointment lifecycle: **Booking → Rescheduling → Cancellation**.

## Critical Metadata Fields Tested

### Booking Metadata
- `created_by`: Who created the appointment (customer/staff/system)
- `booking_source`: Where the booking originated (retell_webhook/crm_admin/customer_portal)
- `booked_by_user_id`: Internal user ID if staff-booked (null for customer bookings)

### Reschedule Metadata
- `rescheduled_at`: Timestamp of reschedule
- `rescheduled_by`: Who rescheduled (customer/staff)
- `reschedule_source`: Where reschedule originated (retell_api/customer_portal/crm_admin)
- `previous_starts_at`: Original appointment time before reschedule

### Cancellation Metadata
- `cancelled_at`: Timestamp of cancellation
- `cancelled_by`: Who cancelled (customer/staff)
- `cancellation_source`: Where cancellation originated (retell_api/customer_portal/crm_admin)

## Test Files

### 1. Feature Test: `tests/Feature/CRM/AppointmentMetadataTest.php`

**Purpose**: End-to-end validation of metadata through complete appointment lifecycle

**Test Scenarios**:
1. ✓ Booking metadata populated via AppointmentCreationService
2. ✓ Reschedule metadata populated correctly
3. ✓ Cancellation metadata populated correctly
4. ✓ Complete lifecycle (Book → Reschedule → Cancel) maintains all metadata
5. ✓ Staff-booked appointments have correct metadata
6. ✓ Multiple reschedules preserve metadata chain
7. ✓ All metadata fields are accessible

**Run Command**:
```bash
php artisan test tests/Feature/CRM/AppointmentMetadataTest.php
```

**Example Test**:
```php
public function test_booking_metadata_populated_correctly_via_appointment_creation_service()
{
    $appointment = $this->appointmentService->createLocalRecord(
        $this->customer,
        $this->service,
        $bookingDetails,
        'calcom_' . uniqid(),
        $this->call
    );

    // Assert booking metadata
    $this->assertEquals('customer', $appointment->created_by);
    $this->assertEquals('retell_webhook', $appointment->booking_source);
    $this->assertNull($appointment->booked_by_user_id);
}
```

### 2. Unit Test: `tests/Unit/Services/AppointmentMetadataServiceTest.php`

**Purpose**: Unit-level validation of metadata handling logic

**Test Coverage**:
- ✓ Default metadata application
- ✓ Explicit metadata preservation
- ✓ Reschedule metadata updates
- ✓ Cancellation metadata updates
- ✓ Field type validation
- ✓ Metadata preservation through status changes
- ✓ Multiple metadata sources handling
- ✓ Null value handling
- ✓ Timestamp precision validation
- ✓ Metadata immutability verification
- ✓ Query filtering by metadata

**Run Command**:
```bash
php artisan test tests/Unit/Services/AppointmentMetadataServiceTest.php
```

**Example Test**:
```php
public function test_reschedule_metadata_updates_correctly()
{
    $appointment->update([
        'starts_at' => $newTime,
        'rescheduled_at' => now(),
        'rescheduled_by' => 'customer',
        'reschedule_source' => 'customer_portal',
        'previous_starts_at' => $originalTime,
    ]);

    $this->assertEquals('customer', $appointment->rescheduled_by);
    $this->assertEquals('customer_portal', $appointment->reschedule_source);
    // Original booking metadata preserved
    $this->assertEquals('customer', $appointment->created_by);
}
```

### 3. Manual Validation Script: `tests/manual_metadata_validation.php`

**Purpose**: Interactive validation with detailed output for debugging

**Features**:
- Database schema validation
- Test data creation with metadata
- Complete lifecycle testing
- Color-coded PASS/FAIL output
- Automatic rollback (no database changes)

**Run Command**:
```bash
php tests/manual_metadata_validation.php
```

**Sample Output**:
```
======================================================================
  STEP 1: Database Schema Validation
======================================================================

[PASS] Column exists: created_by
[PASS] Column exists: booking_source
[PASS] Column exists: booked_by_user_id
...

======================================================================
  STEP 3: Test Booking Metadata
======================================================================

[PASS] Appointment created: ID 12345
[PASS] created_by = 'customer'
[PASS] booking_source = 'retell_webhook'
[PASS] booked_by_user_id = null (customer booking)
```

### 4. Quick Column Check: `tests/quick_metadata_check.php`

**Purpose**: Fast validation that all metadata columns exist in database

**Run Command**:
```bash
php tests/quick_metadata_check.php
```

**Sample Output**:
```
=== APPOINTMENT METADATA FIELD CHECK ===

Booking Fields:
  ✓ created_by
  ✓ booking_source
  ✓ booked_by_user_id

Reschedule Fields:
  ✓ rescheduled_at
  ✓ rescheduled_by
  ✓ reschedule_source
  ✓ previous_starts_at

Cancellation Fields:
  ✓ cancelled_at
  ✓ cancelled_by
  ✓ cancellation_source

Summary:
  Total: 10
  Passed: 10
  Failed: 0

✓ All metadata columns exist!
```

## Quick Start Testing Guide

### 1. Verify Database Schema
```bash
php tests/quick_metadata_check.php
```
**Expected**: All 10 metadata columns should exist

### 2. Run Manual Validation
```bash
php tests/manual_metadata_validation.php
```
**Expected**: All tests should pass with green [PASS] indicators

### 3. Run Unit Tests
```bash
php artisan test tests/Unit/Services/AppointmentMetadataServiceTest.php
```
**Expected**: All 12 unit tests should pass

### 4. Run Feature Tests
```bash
php artisan test tests/Feature/CRM/AppointmentMetadataTest.php
```
**Expected**: All 7 feature tests should pass

### 5. Run Complete Test Suite
```bash
php artisan test tests/Feature/CRM/AppointmentMetadataTest.php tests/Unit/Services/AppointmentMetadataServiceTest.php
```
**Expected**: 19 total tests passing

## Test Scenarios Explained

### Scenario 1: Customer Books Appointment via Retell Webhook

```php
// AppointmentCreationService creates appointment
$appointment = $appointmentService->createLocalRecord(...);

// Expected metadata:
created_by: 'customer'
booking_source: 'retell_webhook'
booked_by_user_id: null  // No staff involved
```

### Scenario 2: Customer Reschedules via Portal

```php
// Customer reschedules through portal
$appointment->update([
    'rescheduled_at' => now(),
    'rescheduled_by' => 'customer',
    'reschedule_source' => 'customer_portal',
    'previous_starts_at' => $originalTime,
]);

// Booking metadata preserved:
created_by: 'customer'  // Still original
booking_source: 'retell_webhook'  // Still original

// Reschedule metadata added:
rescheduled_at: timestamp
rescheduled_by: 'customer'
reschedule_source: 'customer_portal'
previous_starts_at: original time
```

### Scenario 3: Customer Cancels via Retell API

```php
// Customer cancels through Retell
$appointment->update([
    'status' => 'cancelled',
    'cancelled_at' => now(),
    'cancelled_by' => 'customer',
    'cancellation_source' => 'retell_api',
]);

// All previous metadata preserved:
created_by: 'customer'
booking_source: 'retell_webhook'
rescheduled_at: timestamp
rescheduled_by: 'customer'

// Cancellation metadata added:
cancelled_at: timestamp
cancelled_by: 'customer'
cancellation_source: 'retell_api'
```

## Metadata Values Reference

### `created_by` / `rescheduled_by` / `cancelled_by` Values:
- `customer`: Customer-initiated action
- `staff`: Staff member action
- `system`: Automated system action

### `booking_source` / `reschedule_source` / `cancellation_source` Values:
- `retell_webhook`: Retell AI phone booking
- `retell_api`: Retell API endpoint
- `customer_portal`: Customer self-service portal
- `crm_admin`: CRM admin panel
- `calcom_webhook`: Cal.com webhook
- `api`: Generic API endpoint

## Database Schema

All metadata columns exist in the `appointments` table:

```sql
CREATE TABLE appointments (
    -- ... other fields ...

    -- Booking metadata
    created_by VARCHAR(255) NULL,
    booking_source VARCHAR(255) NULL,
    booked_by_user_id BIGINT UNSIGNED NULL,

    -- Reschedule metadata
    rescheduled_at TIMESTAMP NULL,
    rescheduled_by VARCHAR(255) NULL,
    reschedule_source VARCHAR(255) NULL,
    previous_starts_at TIMESTAMP NULL,

    -- Cancellation metadata
    cancelled_at TIMESTAMP NULL,
    cancelled_by VARCHAR(255) NULL,
    cancellation_source VARCHAR(255) NULL,

    -- ... other fields ...
);
```

## Troubleshooting

### Issue: Tests fail with migration errors

**Solution**: The Feature tests require full database setup. Use the manual validation script instead:
```bash
php tests/manual_metadata_validation.php
```

### Issue: "Column doesn't exist" errors

**Solution**: Run the quick check to verify schema:
```bash
php tests/quick_metadata_check.php
```

If columns are missing, check for pending migrations in `database/migrations/`

### Issue: Metadata not being populated

**Check**:
1. Verify `AppointmentCreationService` is setting metadata (lines 406-408)
2. Verify controllers are passing metadata on updates
3. Check the manual validation script output for specific failures

## Integration Points

### AppointmentCreationService Integration

The `AppointmentCreationService::createLocalRecord()` method populates booking metadata:

```php
// File: app/Services/Retell/AppointmentCreationService.php
// Lines 406-408

$appointment->forceFill([
    // ... other fields ...
    'created_by' => 'customer',
    'booking_source' => 'retell_webhook',
    'booked_by_user_id' => null  // Customer bookings have no user
]);
```

### Controller Integration

Controllers should populate reschedule/cancellation metadata:

```php
// Reschedule endpoint
$appointment->update([
    'starts_at' => $newTime,
    'rescheduled_at' => now(),
    'rescheduled_by' => 'customer',
    'reschedule_source' => 'retell_api',
    'previous_starts_at' => $appointment->starts_at,
]);

// Cancellation endpoint
$appointment->update([
    'status' => 'cancelled',
    'cancelled_at' => now(),
    'cancelled_by' => 'customer',
    'cancellation_source' => 'retell_api',
]);
```

## Test Maintenance

### Adding New Metadata Fields

1. Add column to database migration
2. Add to `tests/quick_metadata_check.php` column list
3. Add test case to `AppointmentMetadataTest.php`
4. Add unit test to `AppointmentMetadataServiceTest.php`
5. Update manual validation script with new field checks

### Updating Test Data

All tests use factories where possible. Update factories in:
- `database/factories/AppointmentFactory.php`
- `database/factories/CustomerFactory.php`
- `database/factories/ServiceFactory.php`

## Success Criteria

✅ **All metadata columns exist in database**
- Run: `php tests/quick_metadata_check.php`
- Expected: 10/10 columns exist

✅ **Booking metadata populated correctly**
- created_by = 'customer' for customer bookings
- booking_source = 'retell_webhook' for phone bookings
- booked_by_user_id = null for customer bookings

✅ **Reschedule metadata populated correctly**
- rescheduled_at = timestamp when rescheduled
- rescheduled_by = actor who rescheduled
- reschedule_source = source of reschedule
- previous_starts_at = original time before reschedule

✅ **Cancellation metadata populated correctly**
- cancelled_at = timestamp when cancelled
- cancelled_by = actor who cancelled
- cancellation_source = source of cancellation

✅ **Metadata preserved through lifecycle**
- Booking metadata never changes
- Reschedule metadata preserved after cancellation
- All fields queryable and filterable

## Summary

This test suite provides **comprehensive coverage** of appointment metadata validation through:

1. **19 automated tests** (7 feature + 12 unit)
2. **2 manual scripts** for interactive validation
3. **Complete lifecycle testing** (Book → Reschedule → Cancel)
4. **Clear pass/fail output** for debugging
5. **No database pollution** (transactions rolled back)

All tests are **ready to run** and validate that the metadata tracking system works correctly for data consistency and audit trail requirements.
