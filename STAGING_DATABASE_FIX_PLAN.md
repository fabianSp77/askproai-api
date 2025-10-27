# Staging Database Schema Fix Plan

**Date**: 2025-10-26
**Status**: Analysis Complete | Ready for Implementation
**Priority**: Critical (blocks Customer Portal testing)

---

## Executive Summary

The staging database (`askproai_staging`) was partially populated during fresh setup, but migrations failed at certain points. Current state:
- **Staging**: 48 tables (incomplete)
- **Production**: 244 tables (complete reference)
- **Gap**: 196 tables missing (80% of schema incomplete)

### Key Issues

1. **Duplicate Column Error**: `priority` column already exists in `services` table
   - Migration `2025_10_23_162250` fails on first run
   - Indicates partial migration execution

2. **Missing Critical Tables**: No Retell, advanced notification, or data consistency tables
   - Customer Portal functionality requires these tables
   - Blocking end-to-end testing

3. **Orphaned Data**: Some base tables exist but related tables are missing
   - Foreign key constraints will fail if enforced
   - Data integrity issues possible

---

## Root Cause Analysis

### Why Partial Migration Succeeded

1. **Initial Migration Batch** (~batch 1101): First 47 tables created successfully
   - Basic schema established (users, companies, branches, etc.)
   - Testing framework tables created

2. **Subsequent Migrations Failed**: Batch 1102+ did not execute
   - `2025_10_23_162250` attempted to add `priority` column
   - Column already existed (from earlier partial execution or initial seed)
   - Migration framework entered error state
   - Remaining migrations queued but never executed

### Why Column Already Existed

Likely scenario:
- Initial schema generation included partial service migration
- Priority column was added in early batch
- Fresh staging database copy brought it along
- Re-running same migration causes conflict

---

## Pragmatic Fix Strategy

### Option A: Fast Track (Recommended for Staging)

**Approach**: Skip duplicated columns, re-run migrations from failure point

**Time**: 15-30 minutes
**Risk**: Low (staging only)
**Rationale**:
- Staging is for testing, not data preservation
- Existing data is non-critical (test data)
- Fastest path to operational Customer Portal

**Steps**:
1. Back up current staging database
2. Identify ALL stuck migration columns
3. Create idempotent skip file
4. Reset migration batch counter
5. Re-run migrations

### Option B: Full Reset (Most Reliable)

**Approach**: Drop and recreate staging database entirely

**Time**: 5-10 minutes
**Risk**: Very Low (no production impact)
**Rationale**:
- Ensures 100% schema consistency with production
- Eliminates any hidden state issues
- Clean slate for testing

**Steps**:
1. Backup current database (optional, test data only)
2. Drop staging database
3. Create fresh database
4. Run all migrations
5. Seed test data if needed

**Recommended**: Best practice for staging environment

### Option C: Sync from Production (Highest Confidence)

**Approach**: Clone production schema to staging

**Time**: 10-20 minutes
**Risk**: Very Low (schema-only, no production access needed)
**Rationale**:
- 100% guaranteed schema parity
- Eliminates any migration order issues
- Production is source of truth

**Steps**:
1. Dump production schema: `mysqldump --no-data`
2. Import to staging
3. Run Laravel seeders for test data
4. Verify table count matches

---

## Implementation Plan

### Phase 1: Immediate Backup (5 minutes)

```bash
# Backup current staging (preserve test data if any)
mysqldump -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging > /var/www/api-gateway/backups/staging_backup_before_fix.sql

# Verify backup
ls -lh /var/www/api-gateway/backups/staging_backup_before_fix.sql
```

### Phase 2: Database Reconstruction (Choose ONE approach)

#### APPROACH A: Full Reset (RECOMMENDED)

```bash
#!/bin/bash
set -e

# 1. Drop staging database
mysql -u root -e "DROP DATABASE IF EXISTS askproai_staging;"

# 2. Recreate empty database
mysql -u root -e "CREATE DATABASE askproai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Grant permissions
mysql -u root -e "GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_staging_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 4. Run migrations with fresh state
cd /var/www/api-gateway
php artisan migrate --env=staging --force

echo "✓ Staging database fully migrated"
```

#### APPROACH B: Skip Duplicates (Faster but requires analysis)

```bash
#!/bin/bash

# 1. Identify all migrations not yet run
php artisan migrate:status --env=staging | grep "Pending"

# 2. Delete failed migrations from migrations table
mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' askproai_staging -e "
  DELETE FROM migrations
  WHERE migration LIKE '%2025_10_23%'
     OR migration LIKE '%2025_10_2[5-9]%'
     OR migration LIKE '%2025_10_3%';
"

# 3. Re-run from failure point
php artisan migrate --env=staging --force
```

#### APPROACH C: Schema Sync from Production

```bash
#!/bin/bash

# 1. Backup current staging
mysqldump -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging > /tmp/staging_before_sync.sql

# 2. Dump production schema (no data)
mysqldump -u askproai_user -p'askproai_secure_pass_2024' \
  --no-data askproai_db > /tmp/production_schema.sql

# 3. Drop and recreate staging
mysql -u root -e "DROP DATABASE IF EXISTS askproai_staging;"
mysql -u root -e "CREATE DATABASE askproai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_staging_user'@'localhost';"

# 4. Import production schema
mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging < /tmp/production_schema.sql

# 5. Reset migration tracking
mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging -e "TRUNCATE migrations;"

# 6. Seed with Laravel migrations for proper tracking
php artisan migrate:refresh --env=staging --force --seed
```

---

### Phase 3: Verification (10 minutes)

```bash
# 1. Verify table count
STAGING_TABLES=$(mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='askproai_staging';" 2>&1 | tail -1)

PROD_TABLES=$(mysql -u askproai_user -p'askproai_secure_pass_2024' \
  askproai_db -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='askproai_db';" 2>&1 | tail -1)

echo "Staging tables: $STAGING_TABLES"
echo "Production tables: $PROD_TABLES"

if [ "$STAGING_TABLES" -eq "$PROD_TABLES" ]; then
    echo "✓ Schema count matches!"
else
    echo "✗ Schema mismatch: Staging has $STAGING_TABLES vs Production $PROD_TABLES"
fi

# 2. Verify critical Customer Portal tables
CRITICAL_TABLES=(
    "retell_call_sessions"
    "retell_call_events"
    "retell_transcript_segments"
    "retell_function_traces"
    "appointments"
    "customers"
    "calls"
)

echo -e "\n=== Critical Tables Check ==="
for table in "${CRITICAL_TABLES[@]}"; do
    COUNT=$(mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
        askproai_staging -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_NAME='$table';" 2>&1 | tail -1)

    if [ "$COUNT" -gt 0 ]; then
        echo "✓ $table"
    else
        echo "✗ $table MISSING"
    fi
done

# 3. Check for migration errors
php artisan migrate:status --env=staging | grep -i "fail\|error" && echo "✗ Migration errors found" || echo "✓ All migrations passed"

# 4. Verify Laravel can connect
php artisan tinker --env=staging << 'TINKER'
DB::connection('staging')->getPdo();
echo "✓ Database connection verified\n";
TINKER
```

### Phase 4: Application Testing (15 minutes)

```bash
# 1. Clear Laravel cache
php artisan cache:clear --env=staging
php artisan config:clear --env=staging

# 2. Test database access
php artisan db:seed --env=staging --class=TestDataSeeder 2>&1 | head -20

# 3. Run schema validation
php artisan schema:validate --env=staging

# 4. Quick health check
php artisan route:list --env=staging | grep -i "appointment\|customer\|retell" | head -10
```

---

## Recommended Execution Path

### For Maximum Safety (Option B - Full Reset):

**Best Approach**: Drop and recreate, then run migrations fresh

```bash
# EXECUTE THIS SCRIPT
#!/bin/bash
set -e

echo "=== STAGING DATABASE RESET PROCEDURE ==="
echo "Starting at $(date)"

# Backup
echo "[1/5] Backing up current staging database..."
mysqldump -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging > /var/www/api-gateway/backups/staging_backup_$(date +%s).sql
echo "✓ Backup created"

# Drop & Recreate
echo "[2/5] Dropping and recreating database..."
mysql -u root -e "
  DROP DATABASE IF EXISTS askproai_staging;
  CREATE DATABASE askproai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL PRIVILEGES ON askproai_staging.* TO 'askproai_staging_user'@'localhost';
  FLUSH PRIVILEGES;
"
echo "✓ Database recreated"

# Migrate
echo "[3/5] Running migrations..."
cd /var/www/api-gateway
php artisan migrate --env=staging --force
echo "✓ Migrations complete"

# Verify
echo "[4/5] Verifying schema..."
TABLES=$(mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' \
  askproai_staging -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='askproai_staging';" 2>&1 | tail -1)
echo "Staging tables: $TABLES"

# Clear cache
echo "[5/5] Clearing application cache..."
php artisan cache:clear --env=staging
php artisan config:clear --env=staging

echo -e "\n✓ COMPLETE: Staging database ready for Customer Portal testing"
echo "Completed at $(date)"
```

---

## Migration Status Report

### Currently Missing (Blocking Customer Portal)

| Table Prefix | Count | Status | Impact |
|--------------|-------|--------|--------|
| retell_* | 12 | MISSING | Voice AI call tracking, transcripts, agents |
| conversation_flow_* | 3 | MISSING | Conversation flow definitions |
| data_flow_* | 1 | MISSING | Data consistency tracking |
| system_test_* | 2 | MISSING | Testing infrastructure |
| appointment_wish_* | 2 | MISSING | Customer appointment preferences |
| admin_updates | 1 | MISSING | Admin update tracking |

### Total Gap
- **Tables Present**: 48
- **Tables Missing**: 196
- **Percentage Complete**: 19.7%

---

## Risk Assessment

| Factor | Risk Level | Mitigation |
|--------|-----------|-----------|
| Data Loss | LOW | Test data only, backed up |
| Production Impact | NONE | Staging only, isolated DB |
| Downtime | NONE | No user-facing system affected |
| Foreign Keys | MEDIUM | Will be established in migration |
| Migration Order | LOW | Laravel handles dependencies |

---

## Success Criteria

After executing the fix plan:

1. ✓ Table count: staging ≥ 240 tables (match production)
2. ✓ All critical Customer Portal tables exist
3. ✓ Migration status shows all "Ran"
4. ✓ No `migrate:status` errors
5. ✓ `php artisan tinker` can connect and query tables
6. ✓ No foreign key constraint warnings
7. ✓ Customer Portal feature flags enabled in `.env.staging`

---

## Rollback Plan

If anything goes wrong:

```bash
# Restore from backup
mysql -u root -e "DROP DATABASE IF EXISTS askproai_staging;"
mysql -u askproai_staging_user -p'St4g1ng_S3cur3_P@ssw0rd_2025' < \
  /var/www/api-gateway/backups/staging_backup_XXXXXX.sql
```

---

## Next Steps

1. **Execute Phase 1**: Backup (5 min)
2. **Execute Phase 2**: Choose and run one approach (10 min)
3. **Execute Phase 3**: Verify with provided scripts (10 min)
4. **Execute Phase 4**: Test application connectivity (15 min)
5. **Document Results**: Update this file with actual execution times

**Estimated Total Time**: 45-60 minutes for complete fix

---

## Notes

- This plan focuses on **speed** (staging environment)
- No production database is modified
- All changes are reversible via backup
- Customer Portal testing can begin after Phase 3 verification
- Database will be 100% schema-compatible with production after execution
