# PHASE B Migration Testing - Quick Reference

**⚡ Fast execution guide for migration testing**

---

## Quick Start (5 commands, 30 minutes total)

```bash
# 1. Navigate to project directory
cd /var/www/api-gateway

# 2. Run enhanced migration test suite (10 min)
./scripts/test_migrations_enhanced.sh

# 3. Run SQL verification (3 min)
mysql -u askproai_user -p askproai_test < scripts/verify_migrations.sql

# 4. Run performance benchmarks (5 min)
mysql -u askproai_user -p askproai_test < scripts/performance_benchmarks.sql

# 5. Run rollback safety tests (12 min)
./scripts/test_rollback_safety.sh
```

**Success**: All scripts exit with code 0, see ✓ green checkmarks

---

## What Gets Tested

### ✓ test_migrations_enhanced.sh
- Creates test database
- Runs all 6 migrations
- Validates foreign keys (CASCADE DELETE)
- Checks indexes (29 total)
- Tests unique constraints (3 tables)
- Verifies NOT NULL on company_id
- Seeds test data
- Tests cascade delete behavior
- Benchmarks query performance (<100ms)
- Rolls back and re-migrates
- Verifies data integrity (zero orphans)

### ✓ verify_migrations.sql
- Table existence (6 tables)
- Foreign key constraints
- Index coverage and cardinality
- Unique constraints
- Column constraints (NOT NULL, ENUM)
- Polymorphic relationships
- JSON columns
- Soft delete support
- Data integrity (orphan detection)

### ✓ performance_benchmarks.sql
- 17 benchmark queries
- Company-scoped lookups (<50ms)
- Polymorphic lookups (<75ms)
- 30-day rolling window (<75ms)
- Materialized stats O(1) (<10ms)
- Cross-table JOINs (<150ms)
- Index cardinality analysis
- Table statistics
- Query execution plans

### ✓ test_rollback_safety.sh
- Full rollback (6 migrations)
- Partial rollback (step-by-step)
- FK cleanup verification
- Re-migration testing
- Cascade delete after rollback
- Unique constraints after rollback
- Data insertion validation
- Migration idempotency
- Partial + selective re-migration

---

## Expected Results

### Enhanced Migration Test
```
✓ Test database created
✓ 6 migrations executed
✓ 6 foreign keys validated
✓ Indexes validated on all 6 tables
✓ Unique constraints validated
✓ NOT NULL validated
✓ Test data seeded
✓ Cascade delete verified (before: X, after: 0)
✓ Performance benchmarks completed
✓ Rollback and re-migration tested
✓ Data integrity verified (orphaned records: 0)
```

### Rollback Safety Test
```
✓ Full forward migration tested
✓ Step-by-step rollback verified
✓ Foreign key cleanup validated
✓ Re-migration after rollback successful
✓ Cascade delete functional after rollback
✓ Migration idempotency verified
✓ Partial rollback and selective re-migration tested
```

---

## Performance Targets

| Query Type | Target | Critical |
|------------|--------|----------|
| Company-scoped | < 50ms | ✓ |
| Polymorphic lookup | < 75ms | ✓ |
| Callback queue | < 50ms | ✓ SLA |
| 30-day window | < 75ms | ✓ Quota |
| Stats O(1) | < 10ms | ✓ Critical |
| Cross-table JOIN | < 150ms | - |

---

## Troubleshooting

### Test fails with "Required table not found"
**Fix**: Base tables missing (companies, customers, branches)
```bash
# Check base tables exist
mysql -u askproai_user -p askproai_db -e "SHOW TABLES LIKE 'companies';"
```

### Performance benchmark exceeds 100ms
**Fix**: Indexes not created or need analysis
```bash
# Check indexes exist
mysql -u askproai_user -p askproai_test -e "
SHOW INDEX FROM notification_configurations;"

# Analyze table statistics
mysql -u askproai_user -p askproai_test -e "
ANALYZE TABLE notification_configurations;"
```

### Rollback fails with FK error
**Fix**: Manual FK cleanup may be needed
```bash
# Find dependent FKs
mysql -u askproai_user -p askproai_test -e "
SELECT * FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'notification_configurations';"
```

### Cascade delete leaves orphaned records
**Fix**: Check CASCADE DELETE rule on FKs
```bash
# Verify CASCADE rules
mysql -u askproai_user -p askproai_test -e "
SELECT TABLE_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'askproai_test'
  AND DELETE_RULE = 'CASCADE';"
```

---

## Production Deployment

### Pre-Deployment
```bash
# 1. Backup production database
mysqldump -u askproai_user -p askproai_db > /backup/askproai_db_$(date +%Y%m%d_%H%M%S).sql

# 2. Verify backup
mysql -u askproai_user -p -e "SELECT COUNT(*) FROM askproai_db.companies;"
```

### Deployment
```bash
# 3. Run migrations
cd /var/www/api-gateway
php artisan migrate --force

# 4. Quick verification
mysql -u askproai_user -p askproai_db -e "
SELECT COUNT(*) AS migration_tables FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_db'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );"
# Expected: 6
```

### Emergency Rollback
```bash
# 5. Rollback if needed
php artisan migrate:rollback --step=6 --force

# 6. Verify rollback
mysql -u askproai_user -p askproai_db -e "
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_db'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );"
# Expected: 0
```

---

## Log Files

```bash
# Migration test logs
tail -f /var/log/migration_test_enhanced_*.log

# Performance logs
tail -f /var/log/migration_perf_*.log

# Rollback test logs
tail -f /var/log/rollback_test_*.log

# Check for errors
grep -i "error\|fail" /var/log/migration_test_enhanced_*.log
```

---

## Success Checklist

- [ ] test_migrations_enhanced.sh exits with code 0
- [ ] All 6 tables created
- [ ] 6 company_id foreign keys validated
- [ ] 29 indexes created
- [ ] 3 unique constraints enforced
- [ ] Zero orphaned records after cascade delete
- [ ] All performance queries < 100ms
- [ ] Stats queries < 10ms (O(1))
- [ ] test_rollback_safety.sh exits with code 0
- [ ] Full rollback successful
- [ ] Re-migration successful
- [ ] Migration idempotency verified

---

## Need Help?

**Full Documentation**: `/var/www/api-gateway/claudedocs/PHASE_B_MIGRATION_TESTING_GUIDE.md`

**Test Scripts**:
- `/var/www/api-gateway/scripts/test_migrations_enhanced.sh`
- `/var/www/api-gateway/scripts/verify_migrations.sql`
- `/var/www/api-gateway/scripts/performance_benchmarks.sql`
- `/var/www/api-gateway/scripts/test_rollback_safety.sh`
- `/var/www/api-gateway/scripts/seed_test_data.sh`

**Migration Files**: `/var/www/api-gateway/database/migrations/2025_10_01_*`

---

**Last Updated**: 2025-10-02
**Status**: Ready for Execution
