# Cal.com V1/V2 Booking ID Migration - Summary

## Executive Summary

**Problem**: 102 out of 128 appointments have Cal.com V1 booking IDs (numeric) incorrectly stored in the `calcom_v2_booking_id` column, which is designed for V2 alphanumeric UIDs.

**Solution**: Automated migration to move V1 IDs from `calcom_v2_booking_id` → `calcom_booking_id` with full rollback capability.

**Risk Level**: Low (transactional, reversible, validated)

**Estimated Duration**: 2-3 days (including monitoring period)

---

## Current Data State

### Breakdown (128 total appointments)
```
┌─────────────────────────────────────────┬───────┐
│ Condition                               │ Count │
├─────────────────────────────────────────┼───────┤
│ V1 IDs in correct column                │     2 │
│ V2 UIDs in correct column               │     3 │
│ V1 IDs in WRONG column (V2)             │   102 │ ← ISSUE
│ Both V1 and V2 IDs present              │     2 │
│ No Cal.com ID                           │    23 │
└─────────────────────────────────────────┴───────┘
```

### Source Distribution
```
cal.com          : 101 appointments (all have Cal.com IDs)
calcom_import    :   2 appointments (all have Cal.com IDs)
retell_transcript:   2 appointments (all have Cal.com IDs)
phone            :   9 appointments (no Cal.com IDs)
test             :   6 appointments (no Cal.com IDs)
other            :   8 appointments (no Cal.com IDs)
```

---

## Migration Strategy

### Phase 1: Preparation
1. **Backup database** - Full mysqldump before migration
2. **Run validation** - Execute pre-migration queries
3. **Code review** - Search for `calcom_v2_booking_id` usage
4. **Create backup column** - `_migration_backup_v2_id` for rollback

### Phase 2: Migration
1. **Backup existing values** - Copy all `calcom_v2_booking_id` to backup column
2. **Move V1 IDs** - Transfer numeric IDs from V2 column → V1 column
3. **Clear V2 column** - Set to NULL for migrated records
4. **Handle conflicts** - Resolve records with both V1 and V2 IDs
5. **Validate** - Ensure zero V1 IDs remain in V2 column

### Phase 3: Monitoring (24-48 hours)
1. **Watch logs** - Monitor for Cal.com errors
2. **Test workflows** - Verify appointment sync and webhooks
3. **Run daily checks** - Execute validation queries

### Phase 4: Cleanup
1. **Final validation** - Confirm migration still stable
2. **Remove backup column** - Drop `_migration_backup_v2_id`
3. **Migration complete** - No rollback possible after this

---

## Files Created

### Migration Files
```
database/migrations/
├── 2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php  (Main migration)
├── 2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php  (Cleanup)
├── validation_queries.sql  (Pre/post validation SQL)
├── MIGRATION_EXECUTION_GUIDE.md  (Detailed execution instructions)
└── MIGRATION_SUMMARY.md  (This file)

scripts/
└── validate_calcom_migration.sh  (Quick validation script)
```

### Quick Start Commands

```bash
# 1. Pre-migration validation
./scripts/validate_calcom_migration.sh pre

# 2. Run migration
php artisan migrate --path=database/migrations/2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php

# 3. Post-migration validation
./scripts/validate_calcom_migration.sh post

# 4. Wait 24-48 hours, then cleanup
php artisan migrate --path=database/migrations/2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php

# 5. Verify cleanup
./scripts/validate_calcom_migration.sh verify
```

---

## Expected Results

### Before Migration
```sql
SELECT * FROM appointments WHERE id = 512;
┌─────┬───────────────────┬──────────────────────┐
│ id  │ calcom_booking_id │ calcom_v2_booking_id │
├─────┼───────────────────┼──────────────────────┤
│ 512 │ NULL              │ 8212577              │ ← V1 ID in wrong column
└─────┴───────────────────┴──────────────────────┘
```

### After Migration
```sql
SELECT * FROM appointments WHERE id = 512;
┌─────┬───────────────────┬──────────────────────┬──────────────────────────┐
│ id  │ calcom_booking_id │ calcom_v2_booking_id │ _migration_backup_v2_id  │
├─────┼───────────────────┼──────────────────────┼──────────────────────────┤
│ 512 │ 8212577           │ NULL                 │ 8212577                  │ ← Fixed
└─────┴───────────────────┴──────────────────────┴──────────────────────────┘
```

### After Cleanup
```sql
SELECT * FROM appointments WHERE id = 512;
┌─────┬───────────────────┬──────────────────────┐
│ id  │ calcom_booking_id │ calcom_v2_booking_id │
├─────┼───────────────────┼──────────────────────┤
│ 512 │ 8212577           │ NULL                 │ ← Clean final state
└─────┴───────────────────┴──────────────────────┘
```

---

## Rollback Procedure

If issues are discovered:

```bash
# Immediate rollback (restores original state)
php artisan migrate:rollback --step=1

# Verify rollback
./scripts/validate_calcom_migration.sh pre
```

**Rollback restores**:
- Original `calcom_v2_booking_id` values from backup
- Clears migrated `calcom_booking_id` values
- Preserves backup column for re-running migration

**Note**: Rollback only possible BEFORE cleanup migration

---

## Risk Assessment

### What Could Go Wrong?

| Risk | Severity | Probability | Mitigation |
|------|----------|-------------|------------|
| Application code expects V1 IDs in V2 column | Medium | Low | Code search, monitoring |
| External integrations fail | Low | Low | Webhook monitoring |
| Concurrent appointments during migration | Low | Very Low | Transaction isolation |
| Data corruption | Critical | Very Low | Transactions + validation |

### Safety Mechanisms

✅ **Transactions** - Automatic rollback on any error
✅ **Backup column** - Original values preserved
✅ **Validation gates** - Migration fails if issues detected
✅ **No data deletion** - Only moves data between columns
✅ **Idempotent** - Safe to re-run if needed
✅ **Logging** - All changes tracked in application logs

---

## Code Search Required

Before migration, search for:

```bash
# Find code referencing V2 booking ID column
grep -r "calcom_v2_booking_id" app/ --include="*.php"

# Find code referencing V1 booking ID column
grep -r "calcom_booking_id" app/ --include="*.php"

# Find Cal.com webhook handlers
grep -r "calcom.*webhook" app/ --include="*.php"

# Find Cal.com API integrations
grep -r "cal\.com" app/ --include="*.php"
```

**Review these files** to ensure they:
- Check both V1 and V2 columns when looking up bookings
- Don't assume V1 IDs are in V2 column
- Handle NULL values appropriately

---

## Success Metrics

Migration is successful when:

- ✅ Zero V1 numeric IDs in `calcom_v2_booking_id` column
- ✅ All 102 V1 IDs moved to `calcom_booking_id` column
- ✅ All 3 V2 UIDs remain in `calcom_v2_booking_id` column
- ✅ Backup column contains original values
- ✅ No application errors in logs
- ✅ Cal.com webhooks functioning
- ✅ Appointment sync operational

---

## Timeline

```
Day 0 (Execution Day)
├── Hour 0-1: Preparation (backup, validation, code review)
├── Hour 1-2: Migration execution
└── Hour 2-3: Immediate post-migration validation

Day 1 (Monitoring)
├── Morning: Daily validation check
├── Afternoon: Application monitoring
└── Evening: Log review

Day 2 (Monitoring)
├── Morning: Daily validation check
├── Afternoon: Final decision on cleanup
└── Evening: Cleanup migration (if stable)

Day 3 (Completion)
└── Final verification
```

**Total Duration**: 2-3 days including monitoring period

---

## Contact & Support

### If Migration Fails

1. **Immediate rollback**: `php artisan migrate:rollback --step=1`
2. **Preserve evidence**: Save logs and error messages
3. **Check validation**: Run `./scripts/validate_calcom_migration.sh post`
4. **Review logs**: `tail -100 storage/logs/laravel.log | grep -i calcom`

### If Application Errors Occur

1. **Check specific error** in logs
2. **Verify data integrity** with validation queries
3. **Search codebase** for problematic references to booking IDs
4. **Consider rollback** if issues are widespread

---

## Next Steps

1. **Read full guide**: `database/migrations/MIGRATION_EXECUTION_GUIDE.md`
2. **Backup database**: Create restore point
3. **Run pre-validation**: `./scripts/validate_calcom_migration.sh pre`
4. **Schedule migration**: Choose low-traffic period
5. **Execute migration**: Follow execution guide
6. **Monitor closely**: Watch logs for 24-48 hours
7. **Run cleanup**: After verification period
8. **Document outcome**: Record any issues for future reference

---

## Questions?

Review these files for detailed information:
- **MIGRATION_EXECUTION_GUIDE.md** - Step-by-step execution instructions
- **validation_queries.sql** - All validation SQL queries
- **validate_calcom_migration.sh** - Automated validation script

Or search the codebase for:
- Migration file: `2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php`
- Cleanup file: `2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php`
