# ASK-010: CRM Data Consistency Test Strategy

**Created**: 2025-10-10
**Status**: Complete
**Priority**: High
**Related**: Retell v63 Deployment, Customer Portal, Audit Compliance

---

## Executive Summary

Comprehensive testing strategy addressing critical data integrity gaps in CRM system:

**Critical Gaps Identified**:
- Appointment metadata not populated (`created_by`, `booking_source`)
- Reschedule metadata isolated in AppointmentModification table only
- Name inconsistency across Customer → Call → Appointment chain
- Timeline reconstruction accuracy concerns
- Incomplete audit trail for modifications

**Solution**: 5-tier testing strategy with automated validation suite

---

## Testing Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  TEST PYRAMID                            │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Browser Tests (Puppeteer)                              │
│  └── Portal UI verification, visual validation          │
│                                                          │
│  E2E Tests (PHPUnit)                                    │
│  └── Complete user journeys: Book → Reschedule → Cancel │
│                                                          │
│  Integration Tests (PHPUnit)                            │
│  └── Cross-entity relationships, data flow              │
│                                                          │
│  Unit Tests (PHPUnit)                                   │
│  └── Service methods, individual components             │
│                                                          │
│  SQL Validation                                         │
│  └── Database consistency checks, audit trail           │
└─────────────────────────────────────────────────────────┘
```

---

## Test Coverage Matrix

| Test Level | Files | Tests | Coverage | Execution Time |
|------------|-------|-------|----------|----------------|
| **Unit** | 2 | 10+ | Service methods | ~30s |
| **Integration** | 1 | 8+ | Entity relationships | ~2min |
| **E2E** | 1 | 6+ | User journeys | ~3min |
| **Browser** | 1 | 9+ | Portal UI | ~5min |
| **SQL** | 1 | 15+ queries | Database integrity | ~1min |
| **Total** | 6 | 48+ | End-to-end | ~12min |

---

## Critical Test Scenarios

### 1. Metadata Completeness (Unit + SQL)

**Validates**:
- `appointments.created_by` populated on creation
- `appointments.booking_source` populated on creation
- `appointments.rescheduled_at` + `rescheduled_by` on reschedule
- `appointments.cancelled_at` + `cancelled_by` + `cancellation_reason` on cancel

**Test Files**:
- `/var/www/api-gateway/tests/Unit/Services/Appointments/AppointmentCreationServiceTest.php`
- `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql` (Section 1)

**Key Assertions**:
```php
$this->assertNotNull($appointment->created_by);
$this->assertNotNull($appointment->booking_source);
$this->assertEquals('retell_agent', $appointment->created_by);
```

---

### 2. Name Consistency (Integration + SQL)

**Validates**:
- Customer name matches across Customer → Call → Appointment
- No "Agent says Max but database shows Hansi" scenarios
- Bidirectional relationship integrity

**Test Files**:
- `/var/www/api-gateway/tests/Feature/CRM/DataConsistencyIntegrationTest.php`
- `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql` (Section 2)

**Key Assertions**:
```php
$this->assertEquals('Max Mustermann', $appointment->customer->full_name);
$this->assertEquals($customer->id, $call->customer_id);
$this->assertEquals($customer->id, $appointment->customer_id);
```

**SQL Validation**:
```sql
SELECT COUNT(*) FROM appointments a
JOIN calls ca ON ca.id = a.call_id
WHERE a.customer_id != ca.customer_id;
-- Expected: 0
```

---

### 3. Reschedule Metadata Propagation (Unit + Integration)

**Current Gap**: Reschedule metadata only in `appointment_modifications` table

**Target State**: Metadata in BOTH tables:
- `appointments.rescheduled_at`
- `appointments.rescheduled_by`
- `appointment_modifications.modification_type = 'reschedule'`
- `appointment_modifications.previous_scheduled_at`
- `appointment_modifications.new_scheduled_at`

**Test Files**:
- `/var/www/api-gateway/tests/Unit/Services/Appointments/AppointmentModificationServiceTest.php`

**Key Assertions**:
```php
// Verify appointment updated
$this->assertNotNull($appointment->rescheduled_at);
$this->assertEquals('customer_portal', $appointment->rescheduled_by);

// Verify modification record created
$this->assertEquals('reschedule', $modification->modification_type);
$this->assertEquals($originalTime, $modification->previous_scheduled_at);
```

---

### 4. Timeline Reconstruction (Integration + SQL)

**Validates**:
- Complete appointment lifecycle can be reconstructed from modifications
- Chronological order maintained
- Timeline continuity (no gaps)

**Test Files**:
- `/var/www/api-gateway/tests/Feature/CRM/DataConsistencyIntegrationTest.php`
- `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql` (Section 4)

**Key Assertions**:
```php
$timeline = AppointmentModification::where('appointment_id', $id)
    ->orderBy('created_at', 'asc')
    ->get();

// Verify continuity: previous modification's new_time = next modification's previous_time
$this->assertEquals(
    $timeline[0]->new_scheduled_at,
    $timeline[1]->previous_scheduled_at
);
```

---

### 5. Portal UI Verification (Browser/Puppeteer)

**Validates**:
- Portal displays same data as API returns
- Customer name consistent across portal views
- Modification history displays chronologically
- Metadata visible to customers

**Test Files**:
- `/var/www/api-gateway/tests/puppeteer/crm-data-consistency-e2e.cjs`

**Key Scenarios**:
```javascript
it('should display consistent customer name across portal views')
it('should display complete booking metadata on appointment details')
it('should update metadata correctly during reschedule flow')
it('should display modification history in chronological order')
it('should display portal data matching API response')
```

---

## File Structure

```
/var/www/api-gateway/
├── tests/
│   ├── Unit/
│   │   └── Services/
│   │       └── Appointments/
│   │           ├── AppointmentCreationServiceTest.php        [NEW]
│   │           └── AppointmentModificationServiceTest.php    [NEW]
│   │
│   ├── Feature/
│   │   └── CRM/
│   │       ├── DataConsistencyIntegrationTest.php            [NEW]
│   │       └── AppointmentJourneyE2ETest.php                 [NEW]
│   │
│   ├── puppeteer/
│   │   └── crm-data-consistency-e2e.cjs                      [NEW]
│   │
│   ├── SQL/
│   │   └── data-consistency-validation.sql                   [NEW]
│   │
│   ├── run-crm-consistency-tests.sh                          [NEW]
│   ├── DEPLOYMENT_TESTING_CHECKLIST.md                       [NEW]
│   └── QUICK_START_TESTING_GUIDE.md                          [NEW]
│
└── claudedocs/
    └── ASK-010_DATA_CONSISTENCY_TEST_STRATEGY.md             [THIS FILE]
```

---

## Quick Start Commands

### Run All Tests
```bash
cd /var/www/api-gateway
./tests/run-crm-consistency-tests.sh --level=all
```

### Run Specific Test Level
```bash
# Unit tests only (~30s)
./tests/run-crm-consistency-tests.sh --level=unit

# Integration tests only (~2min)
./tests/run-crm-consistency-tests.sh --level=integration

# E2E tests only (~3min)
./tests/run-crm-consistency-tests.sh --level=e2e

# Browser tests only (~5min)
./tests/run-crm-consistency-tests.sh --level=browser

# SQL validation only (~1min)
./tests/run-crm-consistency-tests.sh --level=sql
```

### Run Individual Test
```bash
# Metadata completeness test
php artisan test --filter=AppointmentCreationServiceTest::test_creates_appointment_with_complete_metadata

# Name consistency test
php artisan test --filter=DataConsistencyIntegrationTest::test_name_consistency_across_all_entities

# Timeline reconstruction test
php artisan test --filter=DataConsistencyIntegrationTest::test_timeline_reconstruction_accuracy

# Portal UI test
mocha tests/puppeteer/crm-data-consistency-e2e.cjs --grep "should display consistent customer name"
```

---

## SQL Validation Quick Reference

### Check Metadata Completeness
```sql
SELECT COUNT(*) AS missing_metadata
FROM appointments
WHERE created_by IS NULL OR booking_source IS NULL;
-- Expected: 0
```

### Check Name Consistency
```sql
SELECT COUNT(*) AS name_mismatches
FROM appointments a
JOIN calls ca ON ca.id = a.call_id
WHERE a.customer_id != ca.customer_id;
-- Expected: 0
```

### Check Orphaned Records
```sql
SELECT COUNT(*) AS orphaned_modifications
FROM appointment_modifications am
WHERE am.appointment_id NOT IN (SELECT id FROM appointments);
-- Expected: 0
```

### Check Timeline Continuity
```sql
WITH timeline AS (
    SELECT
        appointment_id,
        previous_scheduled_at,
        new_scheduled_at,
        LAG(new_scheduled_at) OVER (PARTITION BY appointment_id ORDER BY created_at) AS prev_new_time
    FROM appointment_modifications
    WHERE modification_type = 'reschedule'
)
SELECT COUNT(*) AS discontinuities
FROM timeline
WHERE prev_new_time IS NOT NULL
  AND prev_new_time != previous_scheduled_at;
-- Expected: 0
```

---

## Expected Test Results

### ✅ Success Criteria

**Unit Tests**: All 10+ tests pass
```
✅ PASS  Tests\Unit\Services\Appointments\AppointmentCreationServiceTest (10 tests)
✅ PASS  Tests\Unit\Services\Appointments\AppointmentModificationServiceTest (6 tests)
```

**Integration Tests**: All 8+ tests pass
```
✅ PASS  Tests\Feature\CRM\DataConsistencyIntegrationTest (8 tests)
```

**E2E Tests**: All 6+ tests pass
```
✅ PASS  Tests\Feature\CRM\AppointmentJourneyE2ETest (6 tests)
```

**Browser Tests**: All 9+ tests pass, screenshots generated
```
✅ 9 passing (45s)
Screenshots: tests/results/[timestamp]/screenshots/
```

**SQL Validation**: 0 issues found across all queries
```
Metadata Completeness: 0 issues
Name Consistency: 0 issues
Relationship Integrity: 0 orphaned records
Timeline Reconstruction: 0 discontinuities
Audit Trail Completeness: 100% coverage
```

---

## Deployment Integration

### Pre-Deployment Validation

**Required**: All tests must pass before deployment

```bash
# 1. Run full test suite
./tests/run-crm-consistency-tests.sh --level=all

# 2. Verify results
# Expected: 0 failures, 48+ tests passed

# 3. Review HTML report
open tests/results/[timestamp]/index.html
```

### Post-Deployment Validation (Production)

**First Hour**: Run SQL validation every 15 minutes
```bash
watch -n 900 'mysql -u root -p api_gateway < tests/SQL/data-consistency-validation.sql'
```

**First 24 Hours**: Monitor metrics
- Metadata completeness: Must be > 95%
- Name consistency issues: Must be 0
- Orphaned records: Must be 0
- P95 latency: Must be < 500ms

### Automated Monitoring

**Alerts configured for**:
- Metadata completeness drops below 95%
- Name consistency issues detected (any count > 0)
- Orphaned records detected (any count > 0)
- P95 latency exceeds 500ms

---

## Rollback Criteria

**Trigger immediate rollback if**:

1. **Critical Data Integrity Issues**
   - More than 5% of appointments missing metadata
   - Name inconsistencies detected in production
   - Orphaned records found

2. **Performance Degradation**
   - P95 latency exceeds 1000ms
   - Database query timeout errors

3. **Functional Failures**
   - Booking flow fails for any user
   - Reschedule/cancellation flows broken
   - Portal lookup returns incorrect data

---

## Known Limitations

### Current Implementation Gaps

1. **Reschedule Metadata Location**
   - **Current**: Reschedule metadata only in `appointment_modifications` table
   - **Target**: Metadata in both `appointments` AND `appointment_modifications`
   - **Impact**: Requires JOIN to get reschedule info
   - **Mitigation**: Tests validate AppointmentModification records exist

2. **Name Verification**
   - **Current**: No real-time verification during call
   - **Target**: Retell agent verifies customer identity before booking
   - **Impact**: Potential name mismatches if wrong customer record used
   - **Mitigation**: SQL validation detects mismatches post-facto

3. **Audit Trail Completeness**
   - **Current**: Manual creation of AppointmentModification records
   - **Target**: Automated via Eloquent observers
   - **Impact**: Risk of missing modification records if code bypasses service layer
   - **Mitigation**: SQL validation detects missing modification records

---

## Future Enhancements

### Phase 2 (Optional)

1. **Automated Observers**
   ```php
   // AppointmentObserver.php
   public function updated(Appointment $appointment)
   {
       if ($appointment->isDirty('scheduled_at')) {
           AppointmentModification::create([
               'appointment_id' => $appointment->id,
               'modification_type' => 'reschedule',
               'previous_scheduled_at' => $appointment->getOriginal('scheduled_at'),
               'new_scheduled_at' => $appointment->scheduled_at,
               'modified_by' => auth()->user()->name ?? 'system',
           ]);
       }
   }
   ```

2. **Real-time Name Verification**
   ```php
   // In Retell webhook handler
   public function handleCustomerIdentification($spokenName, $customerRecord)
   {
       $similarity = similar_text($spokenName, $customerRecord->full_name);
       if ($similarity < 80) {
           Log::warning("Low name similarity", [
               'spoken' => $spokenName,
               'database' => $customerRecord->full_name,
               'similarity' => $similarity,
           ]);
           // Prompt agent to verify
           return ['verification_required' => true];
       }
   }
   ```

3. **Continuous Monitoring Dashboard**
   - Real-time data quality metrics
   - Metadata completeness trends
   - Name consistency score
   - Timeline integrity status

---

## Success Metrics

### Quality Metrics
- **Metadata Completeness**: > 99% of appointments have all required metadata
- **Name Consistency**: 0 mismatches between customer records and appointments
- **Timeline Integrity**: 0 discontinuities in modification timeline
- **Audit Trail Coverage**: 100% of reschedules/cancellations have modification records

### Performance Metrics
- **Test Execution Time**: < 15 minutes for full suite
- **P95 Appointment Creation**: < 500ms
- **P95 Appointment Lookup**: < 300ms
- **P95 Modification Creation**: < 200ms

### Operational Metrics
- **Pre-deployment Test Pass Rate**: 100%
- **Production Data Quality**: > 99%
- **Rollback Rate**: < 1% of deployments

---

## References

### Test Files
- **Unit Tests**: `/var/www/api-gateway/tests/Unit/Services/Appointments/`
- **Integration Tests**: `/var/www/api-gateway/tests/Feature/CRM/`
- **E2E Tests**: `/var/www/api-gateway/tests/Feature/CRM/AppointmentJourneyE2ETest.php`
- **Browser Tests**: `/var/www/api-gateway/tests/puppeteer/crm-data-consistency-e2e.cjs`
- **SQL Validation**: `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql`

### Documentation
- **Test Runner**: `/var/www/api-gateway/tests/run-crm-consistency-tests.sh`
- **Deployment Checklist**: `/var/www/api-gateway/tests/DEPLOYMENT_TESTING_CHECKLIST.md`
- **Quick Start Guide**: `/var/www/api-gateway/tests/QUICK_START_TESTING_GUIDE.md`
- **This Strategy**: `/var/www/api-gateway/claudedocs/ASK-010_DATA_CONSISTENCY_TEST_STRATEGY.md`

### Related Work
- **ASK-009**: Auto Service Selection Testing
- **ASK-001**: Performance Monitoring (P95 latency)
- **Retell v63**: Deployment validation and testing
- **Customer Portal**: UI consistency and data accuracy

---

## Contact & Support

**Questions about testing strategy**: Refer to this document
**Test execution issues**: See `QUICK_START_TESTING_GUIDE.md`
**Production data issues**: See `DEPLOYMENT_TESTING_CHECKLIST.md` rollback criteria
**New test scenarios**: Add to appropriate test file and update this document
