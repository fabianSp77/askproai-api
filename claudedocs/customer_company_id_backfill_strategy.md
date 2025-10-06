# Customer company_id Backfill Strategy

## Executive Summary

**Issue**: 31 of 60 customers have NULL company_id values, bypassing multi-tenant isolation (CompanyScope)
**Risk Level**: 🔴 CRITICAL - Data integrity and tenant isolation violation
**Environment**: PRODUCTION with zero downtime requirement
**Strategy**: Phased relationship-based backfill with comprehensive validation

---

## 1. Strategy Comparison Matrix

### Option A: Relationship-Based Backfill (RECOMMENDED)

**Logic**: Infer company_id from appointments relationship
```sql
UPDATE customers SET company_id = (
    SELECT DISTINCT company_id
    FROM appointments
    WHERE customer_id = customers.id
    LIMIT 1
) WHERE company_id IS NULL
  AND EXISTS (SELECT 1 FROM appointments WHERE customer_id = customers.id)
```

| Aspect | Assessment |
|--------|-----------|
| **Safety** | ✅ High - uses existing relationships |
| **Accuracy** | ✅ 95%+ - appointments are authoritative source |
| **Speed** | ✅ Fast - single query execution |
| **Rollback** | ✅ Full backup table created |
| **Risk** | ⚠️ Multiple company conflict (detectable pre-flight) |
| **Coverage** | ~80-90% (customers with appointments) |

**Validation Checks**:
- ✅ Detect multiple company conflicts BEFORE backfill
- ✅ Verify appointment company_id is NOT NULL
- ✅ Log all changes for audit trail
- ✅ Transaction-safe with rollback capability

**Conflict Detection**:
```sql
-- Find customers with appointments from multiple companies
SELECT customer_id, COUNT(DISTINCT company_id) as company_count
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL)
GROUP BY customer_id
HAVING COUNT(DISTINCT company_id) > 1
```

---

### Option B: Phone Numbers Fallback

**Logic**: For customers without appointments, use phone_numbers relationship
```sql
UPDATE customers c
SET company_id = (
    SELECT DISTINCT company_id
    FROM phone_numbers p
    WHERE p.customer_id = c.id
    LIMIT 1
)
WHERE c.company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = c.id)
```

| Aspect | Assessment |
|--------|-----------|
| **Safety** | ⚠️ Medium - phone_numbers may lack company_id |
| **Accuracy** | 🟡 70-80% - less authoritative than appointments |
| **Speed** | ✅ Fast |
| **Rollback** | ✅ Full backup table |
| **Risk** | ⚠️ phone_numbers.company_id may also be NULL |
| **Coverage** | 10-20% (customers without appointments) |

**Use Case**: Fallback ONLY after Option A, for customers with no appointments

---

### Option C: Manual Review + Assignment

**Process**:
1. Export NULL customers to CSV with relationship data
2. Support team reviews each case manually
3. Assign correct company_id based on business context
4. Import corrected data via migration

| Aspect | Assessment |
|--------|-----------|
| **Safety** | ✅ Highest - human verification |
| **Accuracy** | ✅ 100% - manual verification |
| **Speed** | ❌ Slow - hours to days |
| **Rollback** | ✅ Full backup |
| **Risk** | ✅ Minimal - each case verified |
| **Coverage** | 100% with time investment |

**Use Case**: Final fallback for complex/conflicting cases

---

### Option D: Soft Delete Orphaned Records

**Logic**: Customers with no appointments, no phone records = orphaned data
```sql
UPDATE customers
SET deleted_at = NOW()
WHERE company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = customers.id)
  AND NOT EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = customers.id)
```

| Aspect | Assessment |
|--------|-----------|
| **Safety** | ✅ High - uses soft deletes (reversible) |
| **Accuracy** | ✅ 100% - removes only orphaned data |
| **Speed** | ✅ Fast |
| **Rollback** | ✅ Simple restore (deleted_at = NULL) |
| **Risk** | ✅ Low - soft delete is reversible |
| **Coverage** | 5-10% (truly orphaned records) |

**Use Case**: Clean up data with no business value

---

## 2. RECOMMENDED STRATEGY: Phased Hybrid Approach

### Phase 1: Relationship-Based Backfill (Option A)
- **Target**: Customers with appointments (80-90% coverage)
- **Method**: Infer from appointments.company_id
- **Pre-Flight**: Detect and report multiple company conflicts
- **Action**: Backfill clean cases, flag conflicts for manual review

### Phase 2: Phone Fallback (Option B)
- **Target**: Customers without appointments but with phone records
- **Method**: Infer from phone_numbers.company_id
- **Coverage**: Additional 5-10%

### Phase 3: Manual Review (Option C)
- **Target**: Conflict cases from Phase 1 + remaining NULL
- **Method**: CSV export → manual review → import
- **Coverage**: Remaining 5-10%

### Phase 4: Cleanup (Option D)
- **Target**: True orphans with no relationships
- **Method**: Soft delete
- **Coverage**: Final 1-2%

---

## 3. Data Integrity Analysis

### Current State Assessment
```sql
-- Total NULL company_id count
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Expected: 31

-- Customers with appointments (backfillable via Option A)
SELECT COUNT(DISTINCT customer_id)
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL);

-- Customers with multiple company appointments (CONFLICT)
SELECT customer_id, GROUP_CONCAT(DISTINCT company_id) as companies
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL)
GROUP BY customer_id
HAVING COUNT(DISTINCT company_id) > 1;

-- Customers with phone numbers (fallback Option B)
SELECT COUNT(DISTINCT customer_id)
FROM phone_numbers
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL);

-- True orphans (no relationships)
SELECT COUNT(*)
FROM customers c
WHERE c.company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM calls WHERE customer_id = c.id);
```

### Conflict Resolution Matrix

| Conflict Type | Detection Query | Resolution Strategy |
|---------------|----------------|---------------------|
| Multiple companies via appointments | `HAVING COUNT(DISTINCT company_id) > 1` | Manual review - business determines primary company |
| NULL appointments.company_id | `appointments.company_id IS NULL` | Fix appointments first, then retry backfill |
| Circular NULL (customer→appointment→NULL) | Join validation | Flag for manual review |
| No relationships | All JOINs return empty | Soft delete as orphaned data |

---

## 4. Risk Assessment

### High-Risk Scenarios (MUST DETECT)

#### Risk 1: Data Corruption via Wrong Assignment
**Scenario**: Customer assigned to wrong company
**Impact**: 🔴 CRITICAL - Customer data visible to wrong tenant
**Mitigation**:
- Pre-flight validation: detect multiple company conflicts
- Transaction rollback on any error
- Full backup table for restoration
- Post-validation: verify CompanyScope isolation

#### Risk 2: Data Loss
**Scenario**: Relationship data lost during backfill
**Impact**: 🔴 CRITICAL - Business data permanently lost
**Mitigation**:
- Create backup table BEFORE any modifications
- Transaction-safe operations (ROLLBACK on error)
- Log every single change with before/after values
- Validation query: compare record counts pre/post

#### Risk 3: Production Downtime
**Scenario**: Long-running migration locks tables
**Impact**: 🟡 HIGH - User-facing service disruption
**Mitigation**:
- Small dataset (31 records) = minimal lock time
- Use chunked updates if needed
- Test execution time in staging first
- Optional maintenance mode for safety

#### Risk 4: CompanyScope Bypass Continues
**Scenario**: Backfill incomplete, some NULL values remain
**Impact**: 🟡 MEDIUM - Partial fix, security issue persists
**Mitigation**:
- Post-validation: ensure zero NULL company_id (or documented exceptions)
- Follow-up constraint: add NOT NULL constraint after backfill
- Monitoring: alert on any new NULL insertions

---

## 5. Rollback Strategy

### Automatic Rollback Triggers
- Any UPDATE fails → ROLLBACK transaction
- Record count mismatch → ROLLBACK
- Validation failure → ROLLBACK
- Multiple company conflict detected → ROLLBACK with report

### Manual Rollback Procedure
```sql
-- 1. Restore from backup table
UPDATE customers c
INNER JOIN customers_company_id_backup b ON c.id = b.id
SET c.company_id = b.company_id,
    c.updated_at = b.updated_at;

-- 2. Verify restoration
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Should match pre-migration count (31)

-- 3. Drop backup table
DROP TABLE customers_company_id_backup;
```

### Rollback Decision Criteria
| Criteria | Threshold | Action |
|----------|-----------|--------|
| Production errors increase | >10% baseline | Immediate rollback |
| Data loss detected | ANY | Immediate rollback |
| Validation failures | >5% of records | Rollback + investigate |
| CompanyScope tests fail | ANY | Rollback + fix scope logic |
| Customer complaints | >2 within 1 hour | Rollback + investigate |

---

## 6. Validation Framework

### Pre-Flight Validation (BEFORE Migration)

```sql
-- 1. Count NULL records
SELECT COUNT(*) as null_count FROM customers WHERE company_id IS NULL;

-- 2. Detect multiple company conflicts
SELECT
    c.id,
    c.name,
    c.email,
    GROUP_CONCAT(DISTINCT a.company_id) as appointment_companies,
    COUNT(DISTINCT a.company_id) as company_count
FROM customers c
INNER JOIN appointments a ON a.customer_id = c.id
WHERE c.company_id IS NULL
GROUP BY c.id, c.name, c.email
HAVING COUNT(DISTINCT a.company_id) > 1;

-- 3. Verify appointment data integrity
SELECT COUNT(*)
FROM appointments a
INNER JOIN customers c ON c.id = a.customer_id
WHERE c.company_id IS NULL
  AND a.company_id IS NULL;
-- Expected: 0 (appointments should have company_id)

-- 4. Calculate coverage
SELECT
    'Total NULL' as category,
    COUNT(*) as count
FROM customers WHERE company_id IS NULL
UNION ALL
SELECT
    'Backfillable via appointments',
    COUNT(DISTINCT customer_id)
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL)
UNION ALL
SELECT
    'Backfillable via phone_numbers',
    COUNT(DISTINCT customer_id)
FROM phone_numbers
WHERE customer_id IN (
        SELECT id FROM customers c
        WHERE c.company_id IS NULL
          AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
    );
```

### Post-Migration Validation (AFTER Migration)

```sql
-- 1. Verify all NULL values resolved (or documented)
SELECT COUNT(*) as remaining_null FROM customers WHERE company_id IS NULL;
-- Expected: 0 (or documented exceptions)

-- 2. Verify no data loss
SELECT
    'Pre-migration' as stage,
    COUNT(*) as total_customers
FROM customers_company_id_backup
UNION ALL
SELECT
    'Post-migration',
    COUNT(*)
FROM customers;
-- Counts MUST match exactly

-- 3. Verify relationship integrity
SELECT COUNT(*)
FROM customers c
INNER JOIN appointments a ON a.customer_id = c.id
WHERE c.company_id != a.company_id;
-- Expected: 0 (customer and appointment companies must match)

-- 4. Verify CompanyScope isolation
-- Test: Super admin sees all, regular admin sees only their company
-- (Executed via validation command)

-- 5. Audit log verification
SELECT COUNT(*) FROM customers_backfill_audit_log;
-- Expected: 31 (one log entry per backfilled customer)
```

### CompanyScope Isolation Test

```php
// Test in validation command
public function testCompanyScopeIsolation(): void
{
    // Login as company 1 admin
    Auth::login($company1Admin);

    $company1Customers = Customer::count();

    // Verify no NULL company_id customers returned
    $nullCustomers = Customer::whereNull('company_id')->count();
    $this->assertEquals(0, $nullCustomers);

    // Login as company 2 admin
    Auth::login($company2Admin);

    $company2Customers = Customer::count();

    // Verify isolation (counts should differ)
    $this->assertNotEquals($company1Customers, $company2Customers);
}
```

---

## 7. Success Criteria

### Migration Success
- ✅ Zero NULL company_id values remaining (or documented exceptions)
- ✅ No data loss (record count unchanged)
- ✅ No production errors during migration
- ✅ All relationship integrity maintained
- ✅ CompanyScope isolation tests pass
- ✅ Audit log complete for all changes

### Business Success
- ✅ Multi-tenant isolation fully enforced
- ✅ No customer complaints about data visibility
- ✅ No security incidents from tenant leakage
- ✅ Support team can explain any remaining edge cases

### Technical Success
- ✅ Migration completes in <5 minutes
- ✅ Rollback tested and verified in staging
- ✅ NOT NULL constraint successfully applied
- ✅ Monitoring alerts configured for future NULL insertions

---

## 8. Timeline Estimate

### Phase 1: Analysis (30-60 minutes)
- Run all pre-flight validation queries
- Generate backfill plan report
- Review with team and stakeholder approval

### Phase 2: Staging Test (1-2 hours)
- Copy production data to staging
- Run migration in dry-run mode
- Validate results against success criteria
- Test rollback procedure
- Performance benchmark

### Phase 3: Production Backup (30 minutes)
- Full database backup
- Create customers table snapshot
- Document rollback procedure
- Prepare monitoring dashboards

### Phase 4: Production Execution (30-60 minutes)
- Optional: Enable maintenance mode
- Run Phase 1 migration (appointments backfill)
- Validate results
- Run Phase 2 if needed (phone fallback)
- Final validation
- Disable maintenance mode

### Phase 5: Monitoring (24-48 hours)
- Monitor error rates and logs
- Check for data integrity issues
- Verify CompanyScope working correctly
- Customer feedback monitoring
- Be ready to rollback if needed

### Phase 6: Constraint Application (1 week later)
- After monitoring period clean
- Apply NOT NULL constraint
- Add database trigger for audit trail
- Final validation

**Total Estimated Time**: 4-6 hours of active work + 1 week monitoring

---

## 9. Post-Migration Monitoring

### Key Metrics to Track

| Metric | Baseline | Alert Threshold |
|--------|----------|----------------|
| NULL company_id count | 31 → 0 | ANY increase |
| Customer access errors | Current rate | +10% |
| CompanyScope query performance | Benchmark | +20% |
| Customer support tickets | 7-day average | +15% |
| Failed login attempts | Current rate | +25% |

### Monitoring Queries (Run Every Hour for 48h)

```sql
-- 1. Watch for new NULL insertions
SELECT id, name, email, created_at
FROM customers
WHERE company_id IS NULL
  AND created_at > NOW() - INTERVAL 1 HOUR;

-- 2. Check for relationship integrity breaks
SELECT c.id, c.company_id, a.company_id as appointment_company
FROM customers c
INNER JOIN appointments a ON a.customer_id = c.id
WHERE c.company_id != a.company_id;

-- 3. Monitor error log for tenant isolation issues
SELECT COUNT(*)
FROM error_logs
WHERE message LIKE '%CompanyScope%'
  AND created_at > NOW() - INTERVAL 1 HOUR;
```

### Alerting Rules

```yaml
critical_alerts:
  - condition: "SELECT COUNT(*) FROM customers WHERE company_id IS NULL > 0"
    action: "Page on-call engineer immediately"

  - condition: "Production error rate increase >20%"
    action: "Alert DevOps team, prepare rollback"

  - condition: "Customer support tickets mention 'wrong data' or 'missing customers'"
    action: "Immediate investigation, potential rollback"

warning_alerts:
  - condition: "CompanyScope query time >100ms"
    action: "Performance investigation"

  - condition: "New customer creation fails company_id validation"
    action: "Review application code, fix validation"
```

---

## 10. Future Prevention Strategy

### Application-Level Safeguards

1. **Model Factory Fix**: Ensure customer factories always set company_id
2. **Form Validation**: Require company_id in all customer creation forms
3. **API Validation**: Enforce company_id in API customer creation endpoints
4. **Observer Pattern**: CustomerObserver validates company_id on creating event

### Database-Level Safeguards

1. **NOT NULL Constraint**: Apply after backfill complete (1 week monitoring period)
2. **Database Trigger**: Log any attempt to set company_id to NULL
3. **Foreign Key Constraint**: Ensure company_id references valid companies table

### Monitoring & Alerting

1. **Daily Query**: Check for NULL company_id (should always be 0)
2. **Application Logging**: Log all customer creations with company_id validation
3. **Metrics Dashboard**: Track customer creation success/failure rates

---

## Appendix A: SQL Queries Reference

### Analysis Queries
```sql
-- Total NULL count
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;

-- Backfillable via appointments
SELECT COUNT(DISTINCT customer_id)
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL);

-- Multiple company conflicts
SELECT customer_id, COUNT(DISTINCT company_id) as company_count
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL)
GROUP BY customer_id
HAVING COUNT(DISTINCT company_id) > 1;

-- True orphans
SELECT c.id, c.name, c.email
FROM customers c
WHERE c.company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM calls WHERE customer_id = c.id);
```

### Backfill Queries (DO NOT RUN MANUALLY - use migration)
```sql
-- Phase 1: Appointments-based backfill
UPDATE customers c
SET c.company_id = (
    SELECT DISTINCT company_id
    FROM appointments
    WHERE customer_id = c.id
    LIMIT 1
)
WHERE c.company_id IS NULL
  AND EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND (
    SELECT COUNT(DISTINCT company_id)
    FROM appointments
    WHERE customer_id = c.id
  ) = 1;

-- Phase 2: Phone-based fallback
UPDATE customers c
SET c.company_id = (
    SELECT DISTINCT company_id
    FROM phone_numbers
    WHERE customer_id = c.id
    LIMIT 1
)
WHERE c.company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = c.id AND company_id IS NOT NULL);
```

---

## Appendix B: Team Roles & Responsibilities

| Role | Responsibility | Required Actions |
|------|---------------|------------------|
| **Backend Lead** | Migration execution and rollback | Review code, execute migration, monitor |
| **DevOps Engineer** | Infrastructure and backup | Database backup, monitoring setup |
| **QA Engineer** | Validation and testing | Run validation suite, verify CompanyScope |
| **Product Manager** | Business impact assessment | Approve strategy, customer communication plan |
| **Support Lead** | Customer impact monitoring | Monitor tickets, escalation path |
| **Security Lead** | Tenant isolation verification | Review security implications, approve go-live |

---

## Appendix C: Communication Plan

### Pre-Migration
- **Stakeholders**: Email notification 48 hours before (expected impact, timeline)
- **Engineering Team**: Slack notification with runbook link
- **Support Team**: Briefing on potential customer impact

### During Migration
- **Status Updates**: Every 15 minutes in #engineering channel
- **Incident Channel**: #customer-backfill-migration for real-time coordination

### Post-Migration
- **Success Confirmation**: Email to stakeholders within 1 hour
- **Monitoring Report**: Daily summary for 48 hours
- **Retrospective**: Team review within 1 week

---

**Document Version**: 1.0
**Last Updated**: 2025-10-02
**Status**: READY FOR REVIEW
**Next Step**: Team review → Staging test → Production execution
