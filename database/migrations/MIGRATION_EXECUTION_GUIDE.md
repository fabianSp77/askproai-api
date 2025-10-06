# Cal.com V1/V2 Booking ID Migration - Execution Guide

## Overview

This migration fixes the issue where Cal.com V1 booking IDs (numeric) were incorrectly stored in the `calcom_v2_booking_id` column (designed for alphanumeric UIDs).

### Current State Analysis

**Total Appointments**: 128
- **V1 IDs (bigint) in correct column**: 2
- **V2 UIDs (varchar) in correct column**: 3
- **V1 IDs incorrectly in V2 column**: 102 ⚠️ **MAIN ISSUE**
- **Appointments with both V1 and V2 IDs**: 2
- **No Cal.com ID**: 23

### The Problem

Cal.com has two versions:
- **V1**: Uses numeric booking IDs (e.g., `11244795`)
- **V2**: Uses alphanumeric UIDs (e.g., `1DFF95BCdYNCiG33tPL8mK`)

Currently, 102 appointments have V1 numeric IDs stored in the `calcom_v2_booking_id` column, which should only contain V2 UIDs.

## Migration Files

1. **2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php** (Main migration)
2. **2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php** (Cleanup - run after verification)
3. **validation_queries.sql** (Pre/post validation queries)

## Pre-Migration Checklist

### 1. Backup Database
```bash
# Create full database backup
mysqldump -u root -p your_database > backup_before_calcom_migration_$(date +%Y%m%d_%H%M%S).sql

# Or use Laravel backup if configured
php artisan backup:run
```

### 2. Run Pre-Migration Validation
```bash
# Execute validation queries
mysql -u root -p your_database < database/migrations/validation_queries.sql > pre_migration_report.txt

# Review the output to understand current state
cat pre_migration_report.txt
```

Expected pre-migration results:
- `v1_ids_in_v2_column`: 102
- `proper_v2_uids`: 3
- `has_both_ids`: 2

### 3. Review Affected Records
```sql
-- See which appointments will be migrated
SELECT id, calcom_booking_id, calcom_v2_booking_id, source, created_at
FROM appointments
WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
AND deleted_at IS NULL
LIMIT 20;
```

## Migration Execution

### Step 1: Run Main Migration
```bash
# Run the migration
php artisan migrate --path=database/migrations/2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php

# Check migration logs
tail -f storage/logs/laravel.log
```

### Step 2: Immediate Post-Migration Validation

Execute post-migration validation queries:
```bash
# Run queries 6-10 from validation_queries.sql
mysql -u root -p your_database <<'EOF'
-- Should be 0 (all V1 IDs moved out of V2 column)
SELECT COUNT(*) as v1_ids_still_in_v2_column
FROM appointments
WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
AND deleted_at IS NULL;

-- Verify distribution
SELECT
    COUNT(*) as total,
    COUNT(calcom_booking_id) as has_v1_id,
    COUNT(calcom_v2_booking_id) as has_v2_id,
    COUNT(_migration_backup_v2_id) as has_backup
FROM appointments
WHERE deleted_at IS NULL;
EOF
```

Expected results:
- `v1_ids_still_in_v2_column`: **0** ✅
- `has_v1_id`: **104** (2 original + 102 migrated)
- `has_v2_id`: **3** (only proper V2 UIDs remain)
- `has_backup`: **105** (all migrated + unchanged records)

### Step 3: Verify Application Functionality

Test critical Cal.com workflows:
```bash
# Test appointment retrieval
php artisan tinker
>>> $appointment = \App\Models\Appointment::find(512); // V1 ID
>>> $appointment->calcom_booking_id; // Should have numeric ID
>>> $appointment->calcom_v2_booking_id; // Should be NULL

>>> $appointment = \App\Models\Appointment::find(636); // V2 UID
>>> $appointment->calcom_booking_id; // Should have numeric ID
>>> $appointment->calcom_v2_booking_id; // Should have alphanumeric UID
```

## Rollback Procedure

If issues are discovered, rollback immediately:

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# Verify rollback
mysql -u root -p your_database <<'EOF'
SELECT
    id,
    calcom_booking_id,
    calcom_v2_booking_id,
    _migration_backup_v2_id
FROM appointments
WHERE _migration_backup_v2_id IS NOT NULL
LIMIT 10;
EOF
```

Rollback restores:
- Original `calcom_v2_booking_id` values from backup
- Clears `calcom_booking_id` values that were migrated
- Keeps `_migration_backup_v2_id` column for re-running migration

## Monitoring Period (24-48 hours)

### Daily Checks

Monitor for issues:
```bash
# Check logs daily
tail -100 storage/logs/laravel.log | grep -i calcom

# Verify no data anomalies
mysql -u root -p your_database <<'EOF'
SELECT source, COUNT(*) as count
FROM appointments
WHERE (calcom_booking_id IS NOT NULL OR calcom_v2_booking_id IS NOT NULL)
AND deleted_at IS NULL
GROUP BY source;
EOF
```

### Application Monitoring

Watch for:
- Cal.com webhook failures
- Appointment sync errors
- Booking ID lookup failures
- API integration issues

## Cleanup Phase (After 24-48 hours)

Once verified stable, run cleanup migration:

```bash
# Run cleanup to remove backup column
php artisan migrate --path=database/migrations/2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php

# Verify backup column removed
mysql -u root -p your_database -e "DESCRIBE appointments" | grep -i backup
# Should return nothing
```

**WARNING**: After cleanup, rollback is impossible. Ensure everything works before cleanup.

## Risk Assessment

### Low Risk ✅
- Migration uses transactions (automatic rollback on failure)
- Backup column allows safe rollback
- Validation gates prevent partial migrations
- No data deletion (only moves data between columns)

### Potential Issues ⚠️

1. **Application code expects V1 IDs in V2 column**
   - Risk: Medium
   - Mitigation: Search codebase for `calcom_v2_booking_id` usage
   - Action: Update code to check both columns or use proper column

2. **External integrations query V2 column for V1 IDs**
   - Risk: Low
   - Mitigation: Monitor webhooks and API calls
   - Action: Update integration code if needed

3. **Concurrent appointments created during migration**
   - Risk: Low
   - Mitigation: Migration uses WHERE conditions to avoid new records
   - Action: Run during low-traffic period if possible

## Codebase Search Recommendations

Before migration, search for potential issues:

```bash
# Find code referencing calcom_v2_booking_id
grep -r "calcom_v2_booking_id" app/ --include="*.php"

# Find code referencing calcom_booking_id
grep -r "calcom_booking_id" app/ --include="*.php"

# Find Cal.com webhook handlers
grep -r "calcom" app/Http/Controllers/ --include="*.php"
```

Review these files to ensure they handle both V1 and V2 IDs correctly.

## Data Loss Scenarios

**NO DATA LOSS scenarios** (all safe):
- V1 IDs moved from `calcom_v2_booking_id` → `calcom_booking_id`
- Backup column preserves original values
- Transaction rollback on any error
- Validation prevents partial updates

**Theoretical data loss** (prevented by migration logic):
- ❌ Overwriting existing `calcom_booking_id` - **PREVENTED** (checks for NULL)
- ❌ Losing V2 UIDs during migration - **PREVENTED** (only moves numeric IDs)
- ❌ Partial migration on error - **PREVENTED** (transactions)

## Success Criteria

Migration is successful when:
- ✅ Zero V1 numeric IDs in `calcom_v2_booking_id` column
- ✅ All V1 numeric IDs in `calcom_booking_id` column
- ✅ All V2 alphanumeric UIDs remain in `calcom_v2_booking_id` column
- ✅ Backup column exists with original values
- ✅ Application functionality unchanged
- ✅ No errors in logs
- ✅ Cal.com webhooks working
- ✅ Appointment sync operational

## Troubleshooting

### Migration Fails
```bash
# Check logs
tail -100 storage/logs/laravel.log

# Check migration status
php artisan migrate:status | grep calcom

# Review error details
mysql -u root -p your_database -e "SELECT * FROM migrations ORDER BY id DESC LIMIT 5;"
```

### Validation Fails
```bash
# Re-run validation queries
mysql -u root -p your_database < database/migrations/validation_queries.sql

# Check for records that didn't migrate
mysql -u root -p your_database <<'EOF'
SELECT id, calcom_booking_id, calcom_v2_booking_id
FROM appointments
WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
AND deleted_at IS NULL;
EOF
```

### Application Errors After Migration
```bash
# Immediate rollback
php artisan migrate:rollback --step=1

# Investigate root cause
grep -i "calcom_booking_id\|calcom_v2_booking_id" storage/logs/laravel.log

# Review affected code paths
```

## Contact & Escalation

If migration fails or causes issues:
1. **Immediate rollback**: `php artisan migrate:rollback --step=1`
2. **Check logs**: `storage/logs/laravel.log`
3. **Preserve evidence**: Save log outputs and error messages
4. **Escalate**: Notify development team lead
5. **Restore backup**: If critical, restore from pre-migration backup

## Timeline

| Phase | Duration | Action |
|-------|----------|--------|
| Preparation | 1 hour | Backup, validation, code review |
| Execution | 15 min | Run migration, immediate validation |
| Monitoring | 24-48 hrs | Daily checks, error monitoring |
| Cleanup | 5 min | Remove backup column |
| **Total** | **2-3 days** | Full migration lifecycle |

## Final Notes

- **Run during low-traffic period** if possible
- **Backup first** - always have a restore point
- **Validate thoroughly** - use provided SQL queries
- **Monitor closely** - watch logs for 48 hours
- **Don't rush cleanup** - keep backup column until certain
- **Document issues** - record any problems for future reference
