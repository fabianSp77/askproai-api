# CRM Data Consistency - Deployment Testing Checklist

**Purpose**: Comprehensive validation checklist before deployment
**Related**: ASK-010 Data Consistency Testing Strategy
**Last Updated**: 2025-10-10

---

## Pre-Deployment Testing Checklist

### 1. Unit Tests ✅

- [ ] **AppointmentCreationService Tests**
  - [ ] Creates appointments with complete metadata
  - [ ] Applies default metadata when not provided
  - [ ] Maintains relationship integrity on creation
  - [ ] Creates audit trail on appointment creation
  - [ ] References correct customer name

- [ ] **AppointmentModificationService Tests**
  - [ ] Reschedule updates both appointment and creates modification
  - [ ] Cancellation creates complete audit trail
  - [ ] Modification history maintains chronological order
  - [ ] Timeline reconstruction accuracy verified
  - [ ] Metadata JSON structure complete

**Run Command**:
```bash
php artisan test --filter=AppointmentCreationServiceTest
php artisan test --filter=AppointmentModificationServiceTest
```

**Expected Result**: All tests pass (0 failures)

---

### 2. Integration Tests ✅

- [ ] **Complete Booking Flow**
  - [ ] Customer → Call → Appointment relationship chain intact
  - [ ] Metadata completeness verified at each step
  - [ ] Name consistency across all entities

- [ ] **Cross-Entity Relationships**
  - [ ] Bidirectional relationships verified
  - [ ] Foreign key constraints enforced
  - [ ] Cascade behavior validated

- [ ] **Reschedule Flow**
  - [ ] Appointment metadata updated correctly
  - [ ] AppointmentModification record created
  - [ ] Original booking metadata preserved
  - [ ] Timeline continuity maintained

- [ ] **Audit Trail Completeness**
  - [ ] Multi-step journey tracked completely
  - [ ] Chronological order maintained
  - [ ] All modifications reference correct appointment

**Run Command**:
```bash
php artisan test --filter=DataConsistencyIntegrationTest
```

**Expected Result**: All tests pass (0 failures)

---

### 3. End-to-End Tests ✅

- [ ] **Complete User Journeys**
  - [ ] Book appointment via phone (Retell)
  - [ ] Book → Reschedule → Cancel lifecycle
  - [ ] Multiple reschedules with name consistency
  - [ ] Portal lookup shows consistent data
  - [ ] Retell agent workflow captures metadata

**Run Command**:
```bash
php artisan test --filter=AppointmentJourneyE2ETest
```

**Expected Result**: All tests pass (0 failures)

---

### 4. Browser Tests (Puppeteer) ✅

- [ ] **Portal UI Verification**
  - [ ] Customer name consistent across portal views
  - [ ] Complete booking metadata displayed
  - [ ] Reschedule flow updates metadata correctly
  - [ ] Modification history displays chronologically
  - [ ] Cancellation preserves audit trail
  - [ ] Name consistency after multiple modifications
  - [ ] Portal data matches API response
  - [ ] Missing metadata handled gracefully

**Run Command**:
```bash
mocha tests/puppeteer/crm-data-consistency-e2e.cjs --timeout 60000
```

**Expected Result**: All tests pass, screenshots generated

---

### 5. SQL Validation Queries ✅

- [ ] **Metadata Completeness**
  - [ ] No appointments missing `created_by`
  - [ ] No appointments missing `booking_source`
  - [ ] Reschedule metadata complete (`rescheduled_at`, `rescheduled_by`)
  - [ ] Cancellation metadata complete (`cancelled_at`, `cancelled_by`, `cancellation_reason`)

- [ ] **Name Consistency**
  - [ ] Customer name matches across all entities
  - [ ] No call-appointment customer mismatches
  - [ ] No agent-spoken name vs database name mismatches

- [ ] **Relationship Integrity**
  - [ ] No orphaned appointments (missing customer)
  - [ ] No orphaned appointments (missing call)
  - [ ] No orphaned modifications (missing appointment)

- [ ] **Timeline Reconstruction**
  - [ ] Modification timeline continuity verified
  - [ ] No timeline discontinuities detected

- [ ] **Audit Trail Completeness**
  - [ ] All reschedules have modification records
  - [ ] All cancellations have modification records
  - [ ] All modification records have complete metadata

**Run Command**:
```bash
mysql -u root -p api_gateway < tests/SQL/data-consistency-validation.sql
```

**Expected Result**: 0 issues found in all queries

---

### 6. Performance Validation ✅

- [ ] **P95 Latency Monitoring**
  - [ ] Appointment creation P95 < 500ms
  - [ ] Appointment lookup P95 < 300ms
  - [ ] Modification creation P95 < 200ms

- [ ] **Database Performance**
  - [ ] Query performance indexes in place
  - [ ] No N+1 query issues detected
  - [ ] Relationship eager loading optimized

**Run Command**:
```bash
php artisan test --filter=PerformanceMonitoringP95Test
```

**Expected Result**: All latency targets met

---

### 7. Security Validation ✅

- [ ] **Data Access Control**
  - [ ] Tenant middleware enforces customer isolation
  - [ ] Retell webhook signature verification active
  - [ ] Filament admin access restricted

- [ ] **Sensitive Data Protection**
  - [ ] Customer PII properly masked in logs
  - [ ] Audit trail access controlled
  - [ ] Portal access requires authentication

**Run Command**:
```bash
php artisan test --filter=TenantMiddlewareSecurityTest
php artisan test --filter=RetellWebhookSecurityTest
```

**Expected Result**: All security tests pass

---

## Production Validation (Post-Deployment)

### 8. Smoke Tests 🔥

- [ ] **API Endpoints**
  ```bash
  # Appointment creation
  curl -X POST https://api.example.com/api/appointments \
    -H "Authorization: Bearer TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "customer_id": 1,
      "call_id": 1,
      "service_id": 1,
      "scheduled_at": "2025-10-15 14:00:00",
      "duration_minutes": 60
    }'

  # Expected: 201 Created with complete metadata
  ```

- [ ] **Portal Lookup**
  ```bash
  curl https://portal.example.com/api/appointments/lookup?email=test@example.com

  # Expected: 200 OK with consistent customer name
  ```

- [ ] **Monitoring Endpoints**
  ```bash
  curl https://api.example.com/api/monitoring/p95

  # Expected: 200 OK with P95 metrics
  ```

### 9. Live Data Validation 🔍

- [ ] **Sample Production Data Check**
  ```sql
  -- Check first 10 recent appointments for metadata completeness
  SELECT
      id,
      customer_id,
      created_by,
      booking_source,
      rescheduled_at,
      rescheduled_by
  FROM appointments
  ORDER BY created_at DESC
  LIMIT 10;
  ```

- [ ] **Modification Records Check**
  ```sql
  -- Verify recent modifications have complete metadata
  SELECT
      id,
      appointment_id,
      modification_type,
      modified_by,
      metadata
  FROM appointment_modifications
  ORDER BY created_at DESC
  LIMIT 10;
  ```

### 10. User Acceptance Testing 👥

- [ ] **Customer Portal Testing**
  - [ ] Customer can lookup appointment by email
  - [ ] Customer name displays correctly
  - [ ] Booking details show complete metadata
  - [ ] Reschedule flow works end-to-end
  - [ ] Cancellation flow works end-to-end
  - [ ] Modification history displays correctly

- [ ] **Admin Panel Testing**
  - [ ] Admin can view appointment details
  - [ ] Metadata fields populated correctly
  - [ ] Audit trail visible and complete
  - [ ] Filters and search work correctly

---

## Rollback Criteria ⚠️

**Trigger rollback if**:

1. **Critical Data Integrity Issues**
   - More than 5% of appointments missing metadata
   - Name inconsistencies detected in production data
   - Orphaned records found in production

2. **Performance Degradation**
   - P95 latency exceeds 1000ms for any endpoint
   - Database query performance issues detected
   - User-facing timeout errors

3. **Functional Failures**
   - Booking flow fails for any user
   - Reschedule/cancellation flows broken
   - Portal lookup returns incorrect data

4. **Security Issues**
   - Tenant isolation breach detected
   - Unauthorized data access logged
   - Authentication/authorization bypass discovered

---

## Post-Deployment Monitoring

### Continuous Validation (First 24 Hours)

- [ ] **Hour 1**: Run full SQL validation suite every 15 minutes
- [ ] **Hour 2-4**: Run SQL validation every 30 minutes
- [ ] **Hour 4-24**: Run SQL validation every 2 hours

**Monitoring Dashboard**: `/api/monitoring/p95`

### Automated Alerts

- [ ] Configure alert for metadata completeness < 95%
- [ ] Configure alert for name consistency issues detected
- [ ] Configure alert for P95 latency > 500ms
- [ ] Configure alert for orphaned records detected

---

## Test Results Documentation

### Test Execution Record

| Test Suite | Status | Executed By | Timestamp | Notes |
|------------|--------|-------------|-----------|-------|
| Unit Tests | ⏳ Pending | | | |
| Integration Tests | ⏳ Pending | | | |
| E2E Tests | ⏳ Pending | | | |
| Browser Tests | ⏳ Pending | | | |
| SQL Validation | ⏳ Pending | | | |
| Performance Tests | ⏳ Pending | | | |
| Security Tests | ⏳ Pending | | | |
| Smoke Tests | ⏳ Pending | | | |

### Sign-Off

- [ ] **Development Lead**: _____________________ Date: _____
- [ ] **QA Lead**: _____________________ Date: _____
- [ ] **Product Owner**: _____________________ Date: _____

---

## Quick Command Reference

```bash
# Run all tests
./tests/run-crm-consistency-tests.sh --level=all

# Run specific test level
./tests/run-crm-consistency-tests.sh --level=unit
./tests/run-crm-consistency-tests.sh --level=integration
./tests/run-crm-consistency-tests.sh --level=e2e
./tests/run-crm-consistency-tests.sh --level=browser
./tests/run-crm-consistency-tests.sh --level=sql

# Run SQL validation manually
mysql -u root -p api_gateway < tests/SQL/data-consistency-validation.sql

# Run browser tests manually
mocha tests/puppeteer/crm-data-consistency-e2e.cjs --timeout 60000

# Check test coverage
php artisan test --coverage

# Generate HTML report
./tests/run-crm-consistency-tests.sh --level=all
# See: tests/results/[timestamp]/index.html
```

---

## Troubleshooting

### Common Issues

**Issue**: Unit tests fail with "Class not found"
**Solution**: Run `composer dump-autoload`

**Issue**: Browser tests fail with "Connection refused"
**Solution**: Ensure Laravel dev server running on port 8000

**Issue**: SQL validation shows metadata issues
**Solution**: Run migration to add missing columns:
```bash
php artisan migrate --path=database/migrations/[migration_file].php
```

**Issue**: Performance tests fail P95 targets
**Solution**: Check database indexes:
```sql
SHOW INDEX FROM appointments;
SHOW INDEX FROM appointment_modifications;
```

---

## Related Documentation

- `/var/www/api-gateway/tests/Unit/Services/Appointments/AppointmentCreationServiceTest.php`
- `/var/www/api-gateway/tests/Feature/CRM/DataConsistencyIntegrationTest.php`
- `/var/www/api-gateway/tests/Feature/CRM/AppointmentJourneyE2ETest.php`
- `/var/www/api-gateway/tests/puppeteer/crm-data-consistency-e2e.cjs`
- `/var/www/api-gateway/tests/SQL/data-consistency-validation.sql`
- `/var/www/api-gateway/tests/run-crm-consistency-tests.sh`
