# CRM Data Consistency Testing - Quick Start Guide

**Purpose**: Fast reference for running data consistency tests
**Audience**: Developers, QA Engineers, DevOps
**Related**: ASK-010 Testing Strategy

---

## 🚀 Quick Test Commands

### Run All Tests (Recommended)
```bash
./tests/run-crm-consistency-tests.sh --level=all
```

### Run Specific Test Levels

```bash
# Unit tests only (fast, ~30s)
./tests/run-crm-consistency-tests.sh --level=unit

# Integration tests only (~2min)
./tests/run-crm-consistency-tests.sh --level=integration

# E2E tests only (~3min)
./tests/run-crm-consistency-tests.sh --level=e2e

# Browser tests only (~5min)
./tests/run-crm-consistency-tests.sh --level=browser

# SQL validation only (~1min)
./tests/run-crm-consistency-tests.sh --level=sql

# Performance tests only (~2min)
./tests/run-crm-consistency-tests.sh --level=performance
```

---

## 📋 Test Coverage Matrix

| Test Level | What It Tests | Time | Files Tested |
|------------|---------------|------|--------------|
| **Unit** | Individual service methods | ~30s | AppointmentCreationService, AppointmentModificationService |
| **Integration** | Cross-entity relationships | ~2min | Customer → Call → Appointment → Modification chain |
| **E2E** | Complete user journeys | ~3min | Book → Reschedule → Cancel flows |
| **Browser** | Portal UI verification | ~5min | Customer portal, modification history |
| **SQL** | Database consistency | ~1min | Metadata, relationships, timeline integrity |
| **Performance** | Latency monitoring | ~2min | P95 latency metrics |

---

## 🎯 Critical Test Scenarios

### 1. Metadata Completeness
**What**: Verify appointments have `created_by`, `booking_source`, timestamps
**Why**: Critical for audit trail and compliance
**Test**: Unit + SQL validation

```bash
php artisan test --filter=AppointmentCreationServiceTest::test_creates_appointment_with_complete_metadata
```

### 2. Name Consistency
**What**: Verify customer name matches across Customer → Call → Appointment
**Why**: Prevents "Agent says Max but database shows Hansi" issues
**Test**: Integration + SQL validation

```bash
php artisan test --filter=DataConsistencyIntegrationTest::test_name_consistency_across_all_entities
```

### 3. Reschedule Metadata
**What**: Verify both Appointment AND AppointmentModification have reschedule data
**Why**: Currently reschedule metadata only in AppointmentModification
**Test**: Unit + Integration

```bash
php artisan test --filter=AppointmentModificationServiceTest::test_reschedule_updates_both_appointment_and_creates_modification
```

### 4. Timeline Reconstruction
**What**: Verify complete appointment lifecycle can be reconstructed from modifications
**Why**: Audit compliance, customer support needs
**Test**: Integration + SQL validation

```bash
php artisan test --filter=DataConsistencyIntegrationTest::test_timeline_reconstruction_accuracy
```

### 5. Portal UI Consistency
**What**: Verify portal displays same data as API returns
**Why**: User trust, data accuracy
**Test**: Browser (Puppeteer)

```bash
mocha tests/puppeteer/crm-data-consistency-e2e.cjs --grep "should display portal data matching API response"
```

---

## 🔍 SQL Validation Queries

### Quick Health Check
```sql
-- Run this to get overall data quality metrics
SELECT
    'Total Appointments' AS metric,
    COUNT(*) AS count
FROM appointments
UNION ALL
SELECT
    'Appointments with complete metadata',
    COUNT(*)
FROM appointments
WHERE created_by IS NOT NULL
  AND booking_source IS NOT NULL
UNION ALL
SELECT
    'Name consistency issues',
    COUNT(*)
FROM appointments a
JOIN calls ca ON ca.id = a.call_id
WHERE a.customer_id != ca.customer_id;
```

### Check Specific Appointment
```sql
-- Replace {appointment_id} with actual ID
SELECT
    a.id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    a.created_by,
    a.booking_source,
    a.rescheduled_at,
    a.rescheduled_by,
    a.cancelled_at,
    a.cancelled_by,
    COUNT(am.id) AS modification_count
FROM appointments a
JOIN customers c ON c.id = a.customer_id
LEFT JOIN appointment_modifications am ON am.appointment_id = a.id
WHERE a.id = {appointment_id}
GROUP BY a.id, c.first_name, c.last_name, a.created_by, a.booking_source,
         a.rescheduled_at, a.rescheduled_by, a.cancelled_at, a.cancelled_by;
```

---

## 🐛 Common Issues & Fixes

### Issue: "Appointments missing created_by"

**Symptom**: SQL validation shows appointments with NULL `created_by`

**Fix**:
```sql
-- Update existing appointments with default metadata
UPDATE appointments
SET created_by = 'system_migration',
    booking_source = 'legacy_import'
WHERE created_by IS NULL;
```

**Prevention**: Ensure AppointmentCreationService always sets metadata:
```php
$appointment = Appointment::create([
    'customer_id' => $data['customer_id'],
    'created_by' => $data['created_by'] ?? 'system_default',
    'booking_source' => $data['booking_source'] ?? 'unknown',
    // ... other fields
]);
```

### Issue: "Name inconsistency detected"

**Symptom**: Customer name in database differs from what agent spoke

**Fix**:
```sql
-- Find mismatches
SELECT
    a.id,
    CONCAT(c.first_name, ' ', c.last_name) AS db_name,
    ca.metadata->>'$.spoken_customer_name' AS spoken_name
FROM appointments a
JOIN customers c ON c.id = a.customer_id
JOIN calls ca ON ca.id = a.call_id
WHERE ca.metadata->>'$.spoken_customer_name' IS NOT NULL
  AND ca.metadata->>'$.spoken_customer_name' != CONCAT(c.first_name, ' ', c.last_name);

-- Manual review and correction needed
```

**Prevention**: Validate customer identification before booking:
```php
// In Retell webhook handler
if ($spokenName !== $customer->full_name) {
    Log::warning("Name mismatch", [
        'spoken' => $spokenName,
        'database' => $customer->full_name,
        'customer_id' => $customer->id,
    ]);
    // Prompt agent to verify customer identity
}
```

### Issue: "Reschedule metadata only in AppointmentModification"

**Symptom**: Appointment table doesn't have `rescheduled_at`, `rescheduled_by`

**Fix**: Add columns to appointments table
```bash
php artisan make:migration add_reschedule_metadata_to_appointments_table
```

```php
// Migration
public function up()
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->timestamp('rescheduled_at')->nullable()->after('scheduled_at');
        $table->string('rescheduled_by')->nullable()->after('rescheduled_at');
    });
}
```

**Update service**:
```php
// AppointmentModificationService
public function reschedule($appointment, $newScheduledAt, $modifiedBy, $reason)
{
    $previousScheduledAt = $appointment->scheduled_at;

    // Update appointment
    $appointment->update([
        'scheduled_at' => $newScheduledAt,
        'rescheduled_at' => now(),
        'rescheduled_by' => $modifiedBy,
    ]);

    // Create modification record
    AppointmentModification::create([
        'appointment_id' => $appointment->id,
        'modification_type' => 'reschedule',
        'previous_scheduled_at' => $previousScheduledAt,
        'new_scheduled_at' => $newScheduledAt,
        'modified_by' => $modifiedBy,
        'reason' => $reason,
    ]);
}
```

### Issue: "Timeline discontinuity detected"

**Symptom**: Modification records don't form continuous timeline

**Fix**:
```sql
-- Find discontinuities
WITH timeline AS (
    SELECT
        appointment_id,
        previous_scheduled_at,
        new_scheduled_at,
        created_at,
        LAG(new_scheduled_at) OVER (PARTITION BY appointment_id ORDER BY created_at) AS prev_new_time
    FROM appointment_modifications
    WHERE modification_type = 'reschedule'
)
SELECT * FROM timeline
WHERE prev_new_time IS NOT NULL
  AND prev_new_time != previous_scheduled_at;

-- Manual correction needed for each discontinuity
```

**Prevention**: Validate timeline continuity in service:
```php
public function reschedule($appointment, $newScheduledAt, $modifiedBy, $reason)
{
    $lastModification = AppointmentModification::where('appointment_id', $appointment->id)
        ->where('modification_type', 'reschedule')
        ->latest('created_at')
        ->first();

    if ($lastModification && $lastModification->new_scheduled_at != $appointment->scheduled_at) {
        throw new TimelineDiscontinuityException(
            "Timeline discontinuity: last modification time doesn't match current appointment time"
        );
    }

    // ... proceed with reschedule
}
```

---

## 📊 Test Results Interpretation

### Unit Test Results
```
✅ PASS  Tests\Unit\Services\Appointments\AppointmentCreationServiceTest
  ✅ creates appointment with complete metadata
  ✅ appointment references correct customer name
  ✅ applies default metadata when not provided
  ✅ maintains relationship integrity on creation
  ✅ creates audit trail on appointment creation
```
**Interpretation**: All appointment creation scenarios working correctly

### Integration Test Results
```
✅ PASS  Tests\Feature\CRM\DataConsistencyIntegrationTest
  ✅ complete booking flow maintains data consistency
  ✅ name consistency across all entities
  ✅ reschedule flow preserves complete metadata
  ✅ complete audit trail for complex journey
```
**Interpretation**: Cross-entity relationships and data flow validated

### SQL Validation Results
```sql
Metadata Completeness: 0 issues found
Name Consistency: 0 issues found
Relationship Integrity: 0 orphaned records
Timeline Reconstruction: 0 discontinuities
Audit Trail Completeness: 100% coverage
```
**Interpretation**: Database in consistent state, ready for production

---

## 🔥 Emergency Validation (Production)

### Immediate Health Check
```bash
# 1. Check metadata completeness (should be 0)
mysql -u root -p api_gateway -e "
SELECT COUNT(*) AS missing_metadata_count
FROM appointments
WHERE created_by IS NULL OR booking_source IS NULL;
"

# 2. Check name consistency (should be 0)
mysql -u root -p api_gateway -e "
SELECT COUNT(*) AS name_mismatch_count
FROM appointments a
JOIN calls ca ON ca.id = a.call_id
WHERE a.customer_id != ca.customer_id;
"

# 3. Check orphaned records (should be 0)
mysql -u root -p api_gateway -e "
SELECT COUNT(*) AS orphaned_count
FROM appointment_modifications am
WHERE am.appointment_id NOT IN (SELECT id FROM appointments);
"
```

**Expected Result**: All counts should be 0

### If Issues Found
```bash
# 1. Generate detailed report
mysql -u root -p api_gateway < tests/SQL/data-consistency-validation.sql > /tmp/data-issues-$(date +%Y%m%d_%H%M%S).txt

# 2. Review report
less /tmp/data-issues-*.txt

# 3. If critical issues found, consider rollback
# See DEPLOYMENT_TESTING_CHECKLIST.md for rollback criteria
```

---

## 📞 Support & Escalation

### Test Failures
1. Check test logs in `tests/results/[timestamp]/`
2. Review specific test file for expected behavior
3. Run individual test with verbose output: `php artisan test --filter=[TestName] -v`

### Production Issues
1. Run emergency validation (see above)
2. Generate detailed SQL report
3. Escalate to development lead if issues found

### Questions
- Test Strategy: See `/var/www/api-gateway/tests/DEPLOYMENT_TESTING_CHECKLIST.md`
- SQL Queries: See `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql`
- Browser Tests: See `/var/www/api-gateway/tests/puppeteer/crm-data-consistency-e2e.cjs`

---

## 🎓 Further Reading

- **Test Files Location**: `/var/www/api-gateway/tests/`
- **Unit Tests**: `tests/Unit/Services/Appointments/`
- **Integration Tests**: `tests/Feature/CRM/`
- **E2E Tests**: `tests/Feature/CRM/AppointmentJourneyE2ETest.php`
- **Browser Tests**: `tests/puppeteer/crm-data-consistency-e2e.cjs`
- **SQL Validation**: `tests/SQL/data-consistency-validation.sql`
- **Test Runner**: `tests/run-crm-consistency-tests.sh`
