# Quick Start: Metadata Testing

## 1-Minute Quick Check ⚡

```bash
# Verify all metadata columns exist
php tests/quick_metadata_check.php
```

**Expected Output**:
```
✓ All metadata columns exist!
```

---

## 5-Minute Full Validation 🧪

```bash
# Run comprehensive validation with detailed output
php tests/manual_metadata_validation.php
```

**Expected Output**:
```
[PASS] Column exists: created_by
[PASS] Column exists: booking_source
...
[PASS] created_by = 'customer'
[PASS] booking_source = 'retell_webhook'
...
✓ ALL TESTS PASSED
```

---

## 10-Minute Complete Test Suite 🚀

```bash
# Run all automated tests
php artisan test tests/Unit/Services/AppointmentMetadataServiceTest.php
php artisan test tests/Feature/CRM/AppointmentMetadataTest.php
```

**Expected Output**:
```
Tests:  19 passed (7 feature + 12 unit)
```

---

## What Gets Tested? ✅

### Booking Metadata
- ✓ created_by (customer/staff/system)
- ✓ booking_source (retell_webhook/crm_admin/portal)
- ✓ booked_by_user_id (null for customers)

### Reschedule Metadata
- ✓ rescheduled_at (timestamp)
- ✓ rescheduled_by (customer/staff)
- ✓ reschedule_source (origin)
- ✓ previous_starts_at (original time)

### Cancellation Metadata
- ✓ cancelled_at (timestamp)
- ✓ cancelled_by (customer/staff)
- ✓ cancellation_source (origin)

---

## Files Created 📁

```
tests/
├── Feature/CRM/AppointmentMetadataTest.php          (7 tests)
├── Unit/Services/AppointmentMetadataServiceTest.php (12 tests)
├── manual_metadata_validation.php                   (interactive)
├── quick_metadata_check.php                         (quick check)
└── METADATA_TEST_SUITE_README.md                    (full docs)
```

---

## Troubleshooting 🔧

**Issue**: Columns don't exist
```bash
# Check which columns are missing
php tests/quick_metadata_check.php
```

**Issue**: Need detailed test output
```bash
# Run manual script for step-by-step validation
php tests/manual_metadata_validation.php
```

**Issue**: Tests fail with migration errors
```bash
# Use manual script instead (doesn't require migrations)
php tests/manual_metadata_validation.php
```

---

## Success Criteria ✅

- ✅ 10/10 metadata columns exist
- ✅ Booking metadata populated
- ✅ Reschedule metadata populated
- ✅ Cancellation metadata populated
- ✅ Complete lifecycle validated
- ✅ All previous metadata preserved

---

**Full Documentation**: `tests/METADATA_TEST_SUITE_README.md`
