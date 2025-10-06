# Customer company_id Backfill - Execution Runbook

**Purpose**: Step-by-step execution guide for production backfill migration
**Owner**: Backend Lead + DevOps Engineer
**Estimated Duration**: 4-6 hours active work + 1 week monitoring
**Risk Level**: 🔴 CRITICAL - Multi-tenant data integrity

---

## Pre-Execution Checklist

### Prerequisites (Complete Before Starting)

- [ ] **Team Availability**
  - [ ] Backend Lead available for entire execution window
  - [ ] DevOps Engineer on standby for rollback
  - [ ] QA Engineer ready for validation testing
  - [ ] Support Lead monitoring customer tickets

- [ ] **Documentation Review**
  - [ ] All team members have read strategy document
  - [ ] Rollback procedure understood by all
  - [ ] Communication plan approved
  - [ ] Stakeholder notification sent (48 hours prior)

- [ ] **Environment Preparation**
  - [ ] Staging environment mirrors production data
  - [ ] All migrations tested in staging successfully
  - [ ] Monitoring dashboards configured
  - [ ] Incident channel (#customer-backfill-migration) created

- [ ] **Code Deployment**
  - [ ] Migration file deployed: `2025_10_02_164329_backfill_customer_company_id.php`
  - [ ] Validation command deployed: `ValidateCustomerCompanyId.php`
  - [ ] Constraint migration available (for Phase 6): `2025_10_09_000000_add_company_id_constraint_to_customers.php`

- [ ] **Backup Verification**
  - [ ] Full database backup taken
  - [ ] Backup restoration tested
  - [ ] Backup size verified (sufficient storage)
  - [ ] Backup accessible to DevOps team

---

## Phase 1: Analysis & Pre-Flight Validation (30-60 minutes)

### Objective
Understand current state and validate backfill plan before execution

### Steps

#### 1.1 Run Pre-Migration Validation

```bash
# SSH into production server
ssh production-server

# Navigate to application directory
cd /var/www/api-gateway

# Run comprehensive pre-migration validation
php artisan customer:validate-company-id --pre-migration --comprehensive

# Expected output:
# - NULL company_id count: 31
# - Backfill coverage: 80-90%
# - Conflicts detected: 0-5
# - Orphaned records: 1-3
```

**Success Criteria:**
- ✅ Command completes without errors
- ✅ NULL count matches expected (31)
- ✅ Coverage > 75%
- ✅ Conflicts < 10

**If Failures Occur:**
- ⚠️ Coverage < 75%: Review manual review process for remaining records
- ⚠️ Conflicts > 10: Export conflict list, schedule team review, PAUSE migration
- 🔴 Command errors: Investigate, fix issues, restart Phase 1

#### 1.2 Generate Analysis Report

```bash
# Run analysis queries manually for documentation
mysql -u root -p api_gateway << 'EOF'
-- Total NULL count
SELECT COUNT(*) as null_count FROM customers WHERE company_id IS NULL;

-- Backfillable via appointments
SELECT COUNT(DISTINCT customer_id) as backfillable_appointments
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL);

-- Multiple company conflicts
SELECT
    customer_id,
    GROUP_CONCAT(DISTINCT company_id) as company_ids,
    COUNT(DISTINCT company_id) as company_count
FROM appointments
WHERE customer_id IN (SELECT id FROM customers WHERE company_id IS NULL)
GROUP BY customer_id
HAVING COUNT(DISTINCT company_id) > 1;

-- True orphans
SELECT COUNT(*) as orphan_count
FROM customers c
WHERE c.company_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM phone_numbers WHERE customer_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM calls WHERE customer_id = c.id);
EOF
```

**Action:** Save output to `/var/www/api-gateway/storage/logs/backfill_pre_analysis_$(date +%Y%m%d_%H%M%S).log`

#### 1.3 Team Review & Go/No-Go Decision

**Review Meeting (15 minutes):**
- Backend Lead presents analysis results
- Discuss any conflicts or unexpected findings
- Confirm coverage is acceptable
- Review rollback procedure one more time

**Go/No-Go Decision:**
- ✅ GO: Proceed to Phase 2 (Staging Test)
- 🛑 NO-GO: Document reasons, schedule follow-up, STOP execution

---

## Phase 2: Staging Environment Test (1-2 hours)

### Objective
Validate migration in staging with production-like data

### Steps

#### 2.1 Prepare Staging Environment

```bash
# SSH into staging server
ssh staging-server
cd /var/www/api-gateway

# Copy production database to staging (if not already synced)
# Note: Use anonymized data if required by policy
mysqldump -u root -p api_gateway > /tmp/production_backup_$(date +%Y%m%d).sql

# Restore to staging
mysql -u staging_user -p api_gateway_staging < /tmp/production_backup_$(date +%Y%m%d).sql
```

#### 2.2 Run Migration in Staging (DRY RUN)

```bash
# Set DRY_RUN mode in migration file (already set to true by default)
# Edit: database/migrations/2025_10_02_164329_backfill_customer_company_id.php
# Ensure: private const DRY_RUN = false; // Change to true for dry run

# Run migration in DRY_RUN mode
php artisan migrate --path=database/migrations/2025_10_02_164329_backfill_customer_company_id.php

# Expected output:
# - "DRY_RUN mode - rolling back for testing"
# - Statistics logged with no actual changes
```

**Review Logs:**
```bash
tail -100 storage/logs/laravel.log | grep -A 50 "Customer company_id Backfill"
```

**Verify:**
- ✅ DRY_RUN logs show expected backfill counts
- ✅ No actual database changes (NULL count unchanged)
- ✅ Backup table created and dropped
- ✅ Audit log created and dropped

#### 2.3 Run Migration in Staging (ACTUAL)

```bash
# Set DRY_RUN = false for actual execution
# Edit migration file: private const DRY_RUN = false;

# Run migration for real
php artisan migrate --path=database/migrations/2025_10_02_164329_backfill_customer_company_id.php

# Monitor output and logs
tail -f storage/logs/laravel.log
```

**Expected Duration:** 30-60 seconds (small dataset)

**Success Criteria:**
- ✅ Migration completes successfully
- ✅ Logs show backfill statistics
- ✅ No errors or exceptions

#### 2.4 Validate Staging Results

```bash
# Run post-migration validation
php artisan customer:validate-company-id --post-migration --fail-on-issues

# Expected output:
# - Remaining NULL: 0 (or documented conflicts only)
# - All relationship integrity checks PASS
# - CompanyScope isolation tests PASS
```

**Manual Verification:**
```bash
mysql -u staging_user -p api_gateway_staging << 'EOF'
-- Verify NULL elimination
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Expected: 0 or very low (documented conflicts)

-- Verify backup table exists
SHOW TABLES LIKE 'customers_company_id_backup';

-- Verify audit log
SELECT COUNT(*), backfill_source FROM customers_backfill_audit_log GROUP BY backfill_source;
EOF
```

#### 2.5 Test Rollback in Staging

```bash
# Test rollback procedure
php artisan migrate:rollback --step=1

# Verify restoration
mysql -u staging_user -p api_gateway_staging << 'EOF'
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Expected: 31 (back to original state)
EOF

# Verify backup tables dropped
mysql -u staging_user -p api_gateway_staging << 'EOF'
SHOW TABLES LIKE 'customers_company_id_backup';
SHOW TABLES LIKE 'customers_backfill_audit_log';
-- Expected: Empty (tables dropped)
EOF
```

**Success Criteria:**
- ✅ Rollback restores original NULL count
- ✅ Backup and audit tables cleaned up
- ✅ No data loss or corruption

#### 2.6 Performance Benchmark

```bash
# Time migration execution
time php artisan migrate --path=database/migrations/2025_10_02_164329_backfill_customer_company_id.php

# Expected: < 5 minutes for 31 records
# Note: Production may be slower due to load, but should still be < 10 minutes
```

#### 2.7 Staging Sign-Off

**Review Checklist:**
- [ ] Migration completed successfully
- [ ] Validation command passes all checks
- [ ] Rollback tested and verified
- [ ] Performance within acceptable limits
- [ ] Team approval to proceed to production

---

## Phase 3: Production Backup (30 minutes)

### Objective
Create comprehensive backups before any production modifications

### Steps

#### 3.1 Full Database Backup

```bash
# SSH into production database server
ssh production-db-server

# Create full database backup
mysqldump -u root -p \
  --single-transaction \
  --quick \
  --lock-tables=false \
  api_gateway > /backup/api_gateway_full_$(date +%Y%m%d_%H%M%S).sql

# Verify backup size and integrity
ls -lh /backup/api_gateway_full_*.sql
mysql -u root -p < /backup/api_gateway_full_$(date +%Y%m%d_%H%M%S).sql --dry-run

# Compress backup
gzip /backup/api_gateway_full_$(date +%Y%m%d_%H%M%S).sql
```

**Expected Size:** 500MB - 2GB (adjust based on your database size)

**Backup Retention:**
- Keep for 30 days minimum
- Store in secure off-site location
- Verify backup is restorable

#### 3.2 Customers Table Snapshot

```bash
# Create specific customers table backup
mysqldump -u root -p api_gateway customers > /backup/customers_table_$(date +%Y%m%d_%H%M%S).sql

# Verify
wc -l /backup/customers_table_*.sql
# Expected: 60+ lines (header + 60 customers)
```

#### 3.3 Document Backup Locations

```bash
# Create backup manifest
cat > /backup/backup_manifest_$(date +%Y%m%d_%H%M%S).txt << EOF
=== Customer company_id Backfill Migration Backup ===
Date: $(date)
Database: api_gateway
Migration: 2025_10_02_164329_backfill_customer_company_id

Backups Created:
- Full Database: /backup/api_gateway_full_$(date +%Y%m%d_%H%M%S).sql.gz
- Customers Table: /backup/customers_table_$(date +%Y%m%d_%H%M%S).sql

Current State:
- Total Customers: $(mysql -u root -p -e "SELECT COUNT(*) FROM api_gateway.customers" -sN)
- NULL company_id: $(mysql -u root -p -e "SELECT COUNT(*) FROM api_gateway.customers WHERE company_id IS NULL" -sN)

Restoration Command:
gunzip -c /backup/api_gateway_full_$(date +%Y%m%d_%H%M%S).sql.gz | mysql -u root -p api_gateway

Rollback Procedure:
php artisan migrate:rollback --step=1
EOF
```

#### 3.4 Backup Verification

```bash
# Test backup restoration in isolated environment
# DO NOT run on production

# Verify backup files exist and are readable
md5sum /backup/api_gateway_full_*.sql.gz
md5sum /backup/customers_table_*.sql
```

**Sign-Off:**
- [ ] Full database backup created and verified
- [ ] Customers table snapshot created
- [ ] Backup manifest documented
- [ ] Backup restoration tested (in isolated env)
- [ ] DevOps team has backup access

---

## Phase 4: Production Execution (30-60 minutes)

### Objective
Execute migration in production with minimal risk

### Steps

#### 4.1 Enable Monitoring

```bash
# Start monitoring dashboard
# Open monitoring tools (Grafana, New Relic, etc.)

# Watch key metrics:
# - Database connection pool usage
# - Error rate
# - API response times
# - Customer-facing request success rate

# Tail application logs in real-time
ssh production-server
cd /var/www/api-gateway
tail -f storage/logs/laravel.log | grep -i "customer\|company\|backfill"
```

#### 4.2 Communication Check-In

```bash
# Post in #customer-backfill-migration Slack channel
```
**Message:**
```
🚨 STARTING PRODUCTION MIGRATION 🚨
Migration: Customer company_id Backfill
Start Time: [current timestamp]
Expected Duration: 30-60 minutes
Impact: None expected (read-only analysis, then quick update)
Team: @backend-lead @devops @qa @support

Status updates every 15 minutes.
STOP command: "ABORT MIGRATION" in this channel
```

#### 4.3 Optional: Enable Maintenance Mode

**Decision Point:** Do you need maintenance mode?
- ✅ YES: If zero risk tolerance, enable maintenance mode
- 🟡 NO: Migration is fast (<5 min), minimal disruption expected

```bash
# If enabling maintenance mode:
php artisan down --message="Scheduled maintenance - back in 15 minutes" --retry=60

# Verify maintenance mode active
curl -I https://your-production-url.com
# Expected: 503 Service Unavailable
```

**Note:** Only enable if absolutely necessary. Migration is designed for zero downtime.

#### 4.4 Execute Migration

```bash
# SSH into production server
ssh production-server
cd /var/www/api-gateway

# Ensure DRY_RUN = false in migration file
grep "DRY_RUN" database/migrations/2025_10_02_164329_backfill_customer_company_id.php
# Expected output: private const DRY_RUN = false;

# Run migration
php artisan migrate --path=database/migrations/2025_10_02_164329_backfill_customer_company_id.php

# MONITOR OUTPUT CLOSELY
# Watch for:
# - "Pre-flight validation passed"
# - "Backup table created"
# - "Phase 1 completed: X backfilled"
# - "Phase 2 completed: Y backfilled"
# - "Post-migration validation passed"
# - "Migration COMPLETED"
```

**Expected Timeline:**
- 00:00 - 00:30: Pre-flight validation
- 00:30 - 00:45: Backup table creation
- 00:45 - 02:00: Phase 1 (appointments backfill)
- 02:00 - 02:30: Phase 2 (phone fallback)
- 02:30 - 03:00: Phase 3 (orphan cleanup)
- 03:00 - 04:00: Post-migration validation
- 04:00 - 05:00: Report generation

**Total Time:** 3-5 minutes

#### 4.5 Monitor Logs in Real-Time

```bash
# In separate terminal, watch logs
tail -f storage/logs/laravel.log | grep "Customer company_id Backfill"

# Watch for:
# ✅ "Pre-flight validation passed"
# ✅ "Backup table created: X records"
# ✅ "Phase 1 completed: Y backfilled"
# ✅ "Migration COMPLETED"

# Watch for errors:
# ❌ "ABORTED"
# ❌ "FAILED"
# ❌ "Exception"
```

#### 4.6 Status Update (15 minutes in)

```bash
# Post in Slack channel
```
**Message:**
```
✅ Migration Progress Update
Status: Phase 1 Complete
Records Backfilled: [X from logs]
Current Phase: Phase 2 (Phone Fallback)
Issues: None
Next Update: 15 minutes
```

#### 4.7 Migration Completion

```bash
# Verify migration completed successfully
php artisan migrate:status | grep backfill_customer_company_id
# Expected: "Ran" status with green checkmark
```

**Success Indicators:**
- ✅ "Migration COMPLETED" in logs
- ✅ No errors or exceptions
- ✅ Backup table created
- ✅ Audit log populated

**If Errors Occur:**
- 🔴 Transaction automatically rolled back
- 🔴 Review error logs
- 🔴 Notify team in Slack
- 🔴 Proceed to Phase 7 (Rollback)

#### 4.8 Disable Maintenance Mode

```bash
# If maintenance mode was enabled
php artisan up

# Verify site is accessible
curl -I https://your-production-url.com
# Expected: 200 OK
```

---

## Phase 5: Post-Migration Validation (30 minutes)

### Objective
Verify migration success and data integrity

### Steps

#### 5.1 Run Comprehensive Validation

```bash
php artisan customer:validate-company-id --post-migration --comprehensive --fail-on-issues

# Monitor output:
# - Remaining NULL count: 0 (or documented)
# - Relationship integrity: PASS
# - CompanyScope isolation: PASS
# - Audit log completeness: PASS
```

**Expected Results:**
- ✅ ALL VALIDATIONS PASSED
- ✅ Zero critical failures
- ⚠️ Warnings acceptable if documented

**If Failures:**
- Review failure details
- Determine if rollback needed
- Escalate to team lead

#### 5.2 Manual Database Verification

```bash
mysql -u root -p api_gateway << 'EOF'
-- 1. Verify NULL elimination
SELECT COUNT(*) as remaining_null
FROM customers
WHERE company_id IS NULL
  AND deleted_at IS NULL;
-- Expected: 0

-- 2. Verify backup table exists (for rollback capability)
SELECT COUNT(*) as backup_records
FROM customers_company_id_backup;
-- Expected: 31

-- 3. Verify audit log
SELECT
    backfill_source,
    COUNT(*) as count
FROM customers_backfill_audit_log
GROUP BY backfill_source;
-- Expected:
-- appointments | ~25-28
-- phone_numbers | ~2-3
-- soft_delete | ~1-2

-- 4. Verify relationship integrity
SELECT COUNT(*) as mismatches
FROM customers c
INNER JOIN appointments a ON c.id = a.customer_id
WHERE c.company_id != a.company_id;
-- Expected: 0

-- 5. Verify no data loss
SELECT COUNT(*) as current_count FROM customers;
SELECT COUNT(*) as backup_count FROM customers_company_id_backup;
-- Current should be >= backup (backup is subset)
EOF
```

#### 5.3 CompanyScope Functional Testing

**Manual Test Procedure:**

1. **Super Admin Test:**
```bash
# Login as super_admin user in browser
# Navigate to: /customers
# Expected: See ALL customers from ALL companies
```

2. **Regular Admin Test:**
```bash
# Login as regular admin (Company 1) in browser
# Navigate to: /customers
# Expected: See ONLY Company 1 customers

# Login as regular admin (Company 2) in browser
# Navigate to: /customers
# Expected: See ONLY Company 2 customers (different list)
```

3. **NULL Visibility Test:**
```bash
# Login as any regular admin
# Search for customers with NULL company_id
# Expected: Zero results (NULL customers should be invisible)
```

**Test Results:**
- [ ] Super admin sees all customers ✅
- [ ] Company 1 admin sees only Company 1 data ✅
- [ ] Company 2 admin sees only Company 2 data ✅
- [ ] NULL customers invisible to regular admins ✅

#### 5.4 Review Manual Review CSV

```bash
# Check if manual review CSV was generated
ls -lh storage/app/customers_manual_review_*.csv

# If file exists, review contents
cat storage/app/customers_manual_review_*.csv

# Expected: 0-5 customers requiring manual review
# Action: Export CSV, send to support team for assignment
```

#### 5.5 Post-Migration Report

```bash
# Generate comprehensive report
cat > storage/logs/backfill_post_migration_report_$(date +%Y%m%d_%H%M%S).md << 'EOF'
# Customer company_id Backfill - Post-Migration Report

## Migration Summary
- **Start Time:** [from logs]
- **End Time:** [from logs]
- **Duration:** [calculated]
- **Status:** SUCCESS / PARTIAL / FAILED

## Statistics
- **Total NULL Before:** 31
- **Backfilled via Appointments:** [X]
- **Backfilled via Phone Numbers:** [Y]
- **Soft Deleted (Orphans):** [Z]
- **Remaining NULL After:** [N]
- **Success Rate:** [(X+Y)/31 * 100]%

## Validation Results
- NULL Elimination: PASS / FAIL
- Relationship Integrity: PASS / FAIL
- CompanyScope Isolation: PASS / FAIL
- Audit Log Completeness: PASS / FAIL
- Backup Table Verified: YES / NO

## Manual Review Required
- Customers with Conflicts: [N]
- CSV Export Location: storage/app/customers_manual_review_*.csv

## Rollback Capability
- Backup Table: customers_company_id_backup (31 records)
- Rollback Command: php artisan migrate:rollback --step=1
- Rollback Tested: YES (in staging)

## Next Steps
1. Monitor production for 24-48 hours
2. Review manual review CSV with support team
3. Schedule Phase 6 (Constraint Migration) for [date +1 week]

## Sign-Off
- Backend Lead: ________________
- DevOps Engineer: ________________
- QA Engineer: ________________
EOF

# Review and save report
cat storage/logs/backfill_post_migration_report_*.md
```

---

## Phase 6: Monitoring Period (24-48 hours)

### Objective
Monitor production stability and catch any issues early

### Steps

#### 6.1 Continuous Monitoring

**Hour 1-2 (Critical Period):**
```bash
# Watch error logs continuously
tail -f storage/logs/laravel.log | grep -i "error\|exception\|failed"

# Monitor database queries
# Watch for slow queries or deadlocks related to customers table

# Monitor customer support tickets
# Check for reports of missing data or wrong company visibility
```

**Hour 3-24:**
```bash
# Check error rate every hour
php artisan tinker
>>> \DB::table('error_logs')->where('created_at', '>', now()->subHours(1))->count()

# Check for new NULL insertions
mysql -u root -p api_gateway << 'EOF'
SELECT id, name, email, created_at
FROM customers
WHERE company_id IS NULL
  AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
EOF
```

**Day 2-7:**
```bash
# Daily verification query
mysql -u root -p api_gateway << 'EOF'
SELECT
    DATE(created_at) as date,
    COUNT(*) as null_count
FROM customers
WHERE company_id IS NULL
  AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
-- Expected: Zero new NULL records
EOF
```

#### 6.2 Monitoring Alerts

**Critical Alerts (Immediate Action):**
- 🚨 New NULL company_id detected → Investigate source
- 🚨 Customer reports wrong data visibility → Potential isolation break
- 🚨 Error rate increase >20% → Review logs, prepare rollback
- 🚨 CompanyScope query failures → Immediate investigation

**Warning Alerts (Review Within 1 Hour):**
- ⚠️ Slow customer queries (>500ms) → Performance degradation
- ⚠️ Customer support tickets increase >15% → User impact
- ⚠️ Database connection pool exhaustion → Resource issue

#### 6.3 Daily Status Reports

**Daily Report Template:**
```markdown
## Customer company_id Backfill - Day [N] Monitoring Report

**Date:** [YYYY-MM-DD]
**Status:** GREEN / YELLOW / RED

### Metrics
- NULL company_id Count: [N] (Expected: 0)
- New NULL Insertions (24h): [N] (Expected: 0)
- Error Rate Change: [+/-X%] (Baseline: [Y%])
- Customer Support Tickets: [N] (Baseline: [Y])
- CompanyScope Query Performance: [Xms avg] (Baseline: [Yms])

### Issues Detected
- [Issue 1 description] - Status: Open/Resolved
- [Issue 2 description] - Status: Open/Resolved

### Actions Taken
- [Action 1]
- [Action 2]

### Next Steps
- Continue monitoring for [X] more days
- [Any specific actions needed]

**Sign-Off:** [Name] - [Date]
```

#### 6.4 Week 1 Summary

**After 7 Days of Clean Monitoring:**
- [ ] Zero new NULL company_id insertions
- [ ] No customer complaints about data visibility
- [ ] No production errors related to migration
- [ ] CompanyScope performance within baseline
- [ ] Support ticket volume normal

**Decision:** PROCEED to Phase 7 (Constraint Application)

---

## Phase 7: Apply NOT NULL Constraint (Week 2)

### Objective
Permanently prevent future NULL company_id values

### Prerequisites
- [ ] Phase 5 (Migration) completed successfully
- [ ] 7 days of monitoring with zero issues
- [ ] All manual review cases resolved
- [ ] Team approval to proceed

### Steps

#### 7.1 Final Pre-Constraint Validation

```bash
# Verify absolutely zero NULL values
php artisan customer:validate-company-id --post-migration --fail-on-issues

# Manual verification
mysql -u root -p api_gateway << 'EOF'
SELECT COUNT(*) FROM customers WHERE company_id IS NULL AND deleted_at IS NULL;
-- MUST be 0, otherwise ABORT constraint migration
EOF
```

**ABORT Criteria:**
- ❌ ANY NULL values found → Resolve before proceeding
- ❌ Validation failures → Investigate and fix

#### 7.2 Execute Constraint Migration

```bash
# Run constraint migration
php artisan migrate --path=database/migrations/2025_10_09_000000_add_company_id_constraint_to_customers.php

# Monitor output
# Expected:
# - "Pre-flight validation passed: No NULL company_id values found"
# - "Foreign key constraint added successfully"
# - "NOT NULL constraint added successfully"
# - "Constraint enforcement verified"
```

**Success Indicators:**
- ✅ Migration completes without errors
- ✅ Test NULL insertion correctly rejected
- ✅ Foreign key constraint applied

#### 7.3 Verify Constraint Application

```bash
# Verify column is NOT NULL
mysql -u root -p api_gateway << 'EOF'
DESCRIBE customers;
-- Look for company_id column: Null = "NO"

-- Verify foreign key exists
SELECT
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'api_gateway'
  AND TABLE_NAME = 'customers'
  AND COLUMN_NAME = 'company_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
-- Expected: Foreign key to companies table
EOF
```

#### 7.4 Test Constraint Enforcement

```bash
# Test NULL insertion attempt (should fail)
mysql -u root -p api_gateway << 'EOF'
INSERT INTO customers (name, email, company_id, created_at, updated_at)
VALUES ('TEST CONSTRAINT', 'test@example.com', NULL, NOW(), NOW());
-- Expected ERROR: Column 'company_id' cannot be null
EOF

# Verify error message confirms constraint
# Expected: "Column 'company_id' cannot be null"
```

#### 7.5 Monitor for Application Impact

**24-Hour Watch Period:**
```bash
# Monitor customer creation errors
tail -f storage/logs/laravel.log | grep -i "company_id\|cannot be null"

# Check for failed customer creations
mysql -u root -p api_gateway << 'EOF'
SELECT COUNT(*) FROM customers WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
-- Compare to baseline: should be similar rate
EOF
```

**If Issues Detected:**
- Investigate customer creation code paths
- Ensure all forms/APIs set company_id
- Fix application code, not database constraint

#### 7.6 Cleanup (After 30 Days)

**After 30 Days of Stable Operation:**
```bash
# Optional: Drop backup tables (keep database backup)
mysql -u root -p api_gateway << 'EOF'
DROP TABLE IF EXISTS customers_company_id_backup;
DROP TABLE IF EXISTS customers_backfill_audit_log;
EOF

# Archive manual review CSV
mv storage/app/customers_manual_review_*.csv storage/app/archive/
```

**Retention:**
- Keep database backups for 90 days
- Keep audit logs for 1 year
- Keep execution reports indefinitely

---

## Rollback Procedures

### When to Rollback

**Immediate Rollback Triggers:**
- 🔴 Data loss detected (customer records missing)
- 🔴 Relationship integrity broken (customer.company_id != appointment.company_id)
- 🔴 Production errors increase >20%
- 🔴 CompanyScope tests fail
- 🔴 Customer data visible to wrong tenants
- 🔴 Any data corruption detected

**Evaluation Required:**
- ⚠️ Remaining NULL > expected (review conflicts)
- ⚠️ Performance degradation (may be unrelated)
- ⚠️ Customer complaints (investigate first)

### Rollback Procedure

#### Option A: Automatic Rollback (Preferred)

```bash
# SSH into production server
ssh production-server
cd /var/www/api-gateway

# Rollback migration
php artisan migrate:rollback --step=1

# Verify rollback
mysql -u root -p api_gateway << 'EOF'
-- Verify NULL count restored
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Expected: 31 (original count)

-- Verify backup tables dropped
SHOW TABLES LIKE 'customers_company_id_backup';
SHOW TABLES LIKE 'customers_backfill_audit_log';
-- Expected: Empty (tables dropped)
EOF
```

**Verification:**
- [ ] NULL count restored to 31 ✅
- [ ] Backup tables cleaned up ✅
- [ ] No data loss ✅
- [ ] Application functioning normally ✅

#### Option B: Manual Rollback (If Automatic Fails)

```bash
# Step 1: Restore from backup table manually
mysql -u root -p api_gateway << 'EOF'
-- Verify backup table still exists
SELECT COUNT(*) FROM customers_company_id_backup;

-- Restore company_id to NULL for backed up customers
UPDATE customers c
INNER JOIN customers_company_id_backup b ON c.id = b.id
SET c.company_id = NULL,
    c.updated_at = NOW();

-- Restore soft deleted orphans
UPDATE customers c
INNER JOIN customers_backfill_audit_log a ON c.id = a.customer_id
SET c.deleted_at = NULL,
    c.updated_at = NOW()
WHERE a.backfill_source = 'soft_delete';

-- Verify restoration
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Expected: 31
EOF

# Step 2: Clean up manually
mysql -u root -p api_gateway << 'EOF'
DROP TABLE IF EXISTS customers_company_id_backup;
DROP TABLE IF EXISTS customers_backfill_audit_log;
EOF
```

#### Option C: Full Database Restore (Last Resort)

```bash
# Only use if manual rollback fails or data corruption detected

# Step 1: Put site in maintenance mode
php artisan down --message="Emergency maintenance - restoring backup"

# Step 2: Restore from full database backup
gunzip -c /backup/api_gateway_full_[timestamp].sql.gz | mysql -u root -p api_gateway

# Step 3: Verify restoration
mysql -u root -p api_gateway << 'EOF'
SELECT COUNT(*) FROM customers;
SELECT COUNT(*) FROM customers WHERE company_id IS NULL;
-- Verify counts match pre-migration state
EOF

# Step 4: Bring site back online
php artisan up
```

**Communication:**
```bash
# Post in #customer-backfill-migration Slack channel
```
**Message:**
```
🚨 ROLLBACK EXECUTED 🚨
Rollback Method: [Automatic/Manual/Full Restore]
Reason: [Specific reason]
Status: [Success/Failed]
Data Integrity: [Verified/Under Investigation]
Next Steps: [Root cause analysis, plan revision]

Post-Mortem Meeting: [Scheduled time]
```

---

## Post-Execution Tasks

### Immediate (Within 24 Hours)

- [ ] Complete post-migration validation report
- [ ] Send success notification to stakeholders
- [ ] Archive all logs and reports
- [ ] Update documentation with actual results
- [ ] Send thank you to team members

### Week 1

- [ ] Daily monitoring reports
- [ ] Review manual review CSV with support team
- [ ] Resolve any remaining edge cases
- [ ] Monitor for application issues

### Week 2

- [ ] Schedule constraint migration (Phase 7)
- [ ] Final validation before constraint
- [ ] Apply NOT NULL constraint
- [ ] Monitor constraint enforcement

### Month 1

- [ ] Review 30-day metrics
- [ ] Cleanup backup tables (if stable)
- [ ] Conduct post-mortem meeting
- [ ] Document lessons learned
- [ ] Update runbook with improvements

---

## Emergency Contacts

**Primary Team:**
- Backend Lead: [Name] - [Phone] - [Slack: @handle]
- DevOps Engineer: [Name] - [Phone] - [Slack: @handle]
- QA Engineer: [Name] - [Phone] - [Slack: @handle]

**Escalation:**
- Engineering Manager: [Name] - [Phone]
- CTO: [Name] - [Phone]

**Communication Channels:**
- Primary: #customer-backfill-migration (Slack)
- Emergency: [Phone numbers / PagerDuty]

---

## Success Criteria Summary

**Migration Successful If:**
- ✅ Zero data loss
- ✅ NULL company_id eliminated (or documented exceptions < 5)
- ✅ Relationship integrity maintained (100%)
- ✅ CompanyScope isolation verified
- ✅ No production errors related to migration
- ✅ Rollback capability verified
- ✅ Audit trail complete

**Project Successful If:**
- ✅ All above + 7 days stable monitoring
- ✅ NOT NULL constraint applied successfully
- ✅ Zero customer complaints
- ✅ Team confident in multi-tenant isolation
- ✅ Documentation complete for future reference

---

**Document Version:** 1.0
**Last Updated:** 2025-10-02
**Status:** READY FOR EXECUTION
**Approval Required From:** Backend Lead, DevOps Engineer, Product Manager
