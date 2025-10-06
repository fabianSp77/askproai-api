# Cal.com Booking ID Migration - Quick Reference Card

## One-Line Summary
**Move 102 V1 numeric booking IDs from `calcom_v2_booking_id` (varchar) → `calcom_booking_id` (bigint)**

---

## Commands (Copy & Paste)

### 1. Pre-Migration Validation
```bash
./scripts/validate_calcom_migration.sh pre
```

### 2. Database Backup
```bash
mysqldump -u root -p$(grep DB_PASSWORD .env | cut -d '=' -f2) $(grep DB_DATABASE .env | cut -d '=' -f2) > backup_calcom_migration_$(date +%Y%m%d_%H%M%S).sql
```

### 3. Run Migration
```bash
php artisan migrate --path=database/migrations/2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php
```

### 4. Post-Migration Validation
```bash
./scripts/validate_calcom_migration.sh post
```

### 5. Rollback (if needed)
```bash
php artisan migrate:rollback --step=1
```

### 6. Cleanup (after 24-48 hours)
```bash
php artisan migrate --path=database/migrations/2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php
```

### 7. Final Verification
```bash
./scripts/validate_calcom_migration.sh verify
```

---

## Expected Results

### Pre-Migration
- **V1 IDs in V2 column**: 102
- **Proper V2 UIDs**: 3
- **V1 IDs in V1 column**: 2

### Post-Migration
- **V1 IDs in V2 column**: 0 ✅
- **V1 IDs in V1 column**: 104
- **Proper V2 UIDs**: 3
- **Backup records**: 105

### After Cleanup
- **Backup column**: Removed ✅
- **V1 IDs in V2 column**: 0 ✅

---

## Quick Validation SQL

### Check if migration needed
```sql
SELECT COUNT(*) as needs_migration
FROM appointments
WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
AND deleted_at IS NULL;
-- Should be 102 before, 0 after
```

### Check migration success
```sql
SELECT
    COUNT(*) as total,
    COUNT(calcom_booking_id) as v1_ids,
    COUNT(calcom_v2_booking_id) as v2_ids,
    SUM(CASE WHEN calcom_v2_booking_id REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as problem_records
FROM appointments
WHERE deleted_at IS NULL;
-- problem_records should be 0 after migration
```

### Sample migrated record
```sql
SELECT
    id,
    calcom_booking_id,
    calcom_v2_booking_id,
    _migration_backup_v2_id,
    source
FROM appointments
WHERE _migration_backup_v2_id IS NOT NULL
LIMIT 5;
```

---

## Files

| File | Purpose |
|------|---------|
| `MIGRATION_SUMMARY.md` | Executive overview |
| `MIGRATION_EXECUTION_GUIDE.md` | Detailed instructions |
| `validation_queries.sql` | All validation SQL |
| `validate_calcom_migration.sh` | Automated validation |
| `QUICK_REFERENCE.md` | This file |
| `2025_10_05_102511_fix_calcom_v1_v2_booking_id_separation.php` | Main migration |
| `2025_10_05_102629_cleanup_calcom_booking_id_migration_backup.php` | Cleanup migration |

---

## Rollback Safety

✅ **Full rollback possible** until cleanup migration
✅ **Backup column** preserves original values
✅ **Transactions** auto-rollback on errors
✅ **Validation** prevents partial migrations
❌ **No rollback** after cleanup migration

---

## Timeline

```
┌─────────────┬──────────────────────────────┐
│ Phase       │ Duration                     │
├─────────────┼──────────────────────────────┤
│ Prepare     │ 1 hour                       │
│ Execute     │ 15 minutes                   │
│ Monitor     │ 24-48 hours                  │
│ Cleanup     │ 5 minutes                    │
├─────────────┼──────────────────────────────┤
│ TOTAL       │ 2-3 days                     │
└─────────────┴──────────────────────────────┘
```

---

## Risk Level: LOW ✅

- Transactional (auto-rollback on failure)
- Reversible (rollback restores original state)
- Validated (checks prevent partial updates)
- Non-destructive (no data deletion)
- Monitored (logs all changes)

---

## Troubleshooting

### Migration fails
```bash
# Check logs
tail -100 storage/logs/laravel.log | grep -i calcom

# Review migration status
php artisan migrate:status | grep calcom

# Rollback and investigate
php artisan migrate:rollback --step=1
```

### Application errors
```bash
# Find code using booking IDs
grep -r "calcom_v2_booking_id\|calcom_booking_id" app/ --include="*.php"

# Check recent errors
tail -200 storage/logs/laravel.log
```

### Validation fails
```bash
# Re-run validation
./scripts/validate_calcom_migration.sh post

# Check problem records
mysql -u root -p -e "
    SELECT * FROM appointments
    WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
    AND deleted_at IS NULL;
" your_database
```

---

## Success Checklist

- [ ] Pre-validation run and reviewed
- [ ] Database backup created
- [ ] Migration executed successfully
- [ ] Post-validation shows 0 V1 IDs in V2 column
- [ ] Application logs show no errors
- [ ] Cal.com webhooks working
- [ ] 24-48 hour monitoring period complete
- [ ] Cleanup migration executed
- [ ] Final verification passed
- [ ] Migration documented

---

## Emergency Contact

**Immediate rollback**: `php artisan migrate:rollback --step=1`

**Restore backup**:
```bash
mysql -u root -p your_database < backup_calcom_migration_YYYYMMDD_HHMMSS.sql
```

---

## Notes

- Run during **low-traffic period** if possible
- **Monitor logs** closely for 24-48 hours
- **Don't rush cleanup** - keep backup column until certain
- **Document issues** for future reference
