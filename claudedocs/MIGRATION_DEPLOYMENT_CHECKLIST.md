# Migration Deployment Checklist - Quick Reference

**Phase 2: Multi-Tenant Database Schema Deployment**
**Date**: 2025-10-02

---

## Pre-Deployment Checklist

### Prerequisites (Complete Before Testing)

- [ ] **Review full deployment plan**
  - Location: `/var/www/api-gateway/claudedocs/MIGRATION_TESTING_DEPLOYMENT_PLAN.md`
  - Estimated reading time: 15 minutes

- [ ] **Verify test environment ready**
  - MySQL test database can be created
  - Sufficient disk space for backup (~500MB minimum)
  - Test database name: `askproai_test`

- [ ] **Confirm team availability**
  - Database administrator available for 2-3 hours
  - Rollback window scheduled (recommended: low-traffic period)
  - Emergency contacts documented

- [ ] **Backup storage verified**
  - Directory exists: `/var/backups/mysql/`
  - Sufficient space: >500MB free
  - Permissions correct: root access

---

## Testing Phase (Estimated Time: 45 minutes)

### Step 1: Run Automated Test Suite

```bash
# Execute comprehensive test script
sudo /var/www/api-gateway/scripts/test_migrations.sh
```

**Expected Outcome**: All tests pass with green checkmarks

**What Gets Tested**:
- ✓ Test database creation
- ✓ Schema cloning from production
- ✓ Migration execution (6 tables)
- ✓ Foreign key constraints (CASCADE delete)
- ✓ Index validation
- ✓ Cascade delete behavior
- ✓ Rollback and re-migration
- ✓ Data integrity (orphaned records check)

**If Tests Fail**:
- [ ] Review log file: `/var/log/migration_test_YYYYMMDD_HHMMSS.log`
- [ ] Identify failure point from colored output
- [ ] Consult troubleshooting section in deployment plan
- [ ] **DO NOT PROCEED TO PRODUCTION** until all tests pass

---

### Step 2: Manual Verification (Optional but Recommended)

```bash
# Connect to test database
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test

# Verify tables created
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE '%policy%';
SHOW TABLES LIKE '%callback%';
SHOW TABLES LIKE '%appointment_mod%';

# Check foreign keys
SELECT
  TABLE_NAME,
  CONSTRAINT_NAME,
  REFERENCED_TABLE_NAME,
  DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'askproai_test'
  AND DELETE_RULE = 'CASCADE';
```

**Expected**: All 6 tables exist, foreign keys use CASCADE

---

## Production Deployment (Estimated Time: 20 minutes)

### Pre-Deployment Checks

- [ ] **All tests passed** (from Testing Phase)
- [ ] **Backup directory ready**: `/var/backups/mysql/`
- [ ] **Maintenance window scheduled** (if using downtime)
- [ ] **Team notified** of deployment window
- [ ] **Rollback plan reviewed** (Section 6 of deployment plan)

---

### Option A: Full Safety Deployment (Recommended)

**Includes**: Backup + Maintenance Mode + Verification

```bash
# Execute with full safety features
sudo /var/www/api-gateway/scripts/deploy_migrations.sh
```

**Prompts You'll See**:
1. "Continue? (yes/no):" → Type **yes**
2. "Ready to deploy to PRODUCTION?" → Type **yes**

**Timeline**:
- Backup creation: ~2 minutes
- Maintenance mode: <5 seconds
- Migration execution: <1 second
- Verification: ~10 seconds
- Cache clearing: ~5 seconds
- Total: ~3 minutes

---

### Option B: Zero-Downtime Deployment

**Includes**: Backup + No Maintenance Mode

```bash
# Execute without maintenance mode (zero downtime)
sudo /var/www/api-gateway/scripts/deploy_migrations.sh --no-maintenance
```

**Use When**: Application must remain available during deployment

**Risk**: Users might experience errors if they interact with new tables during migration (unlikely, tables are new)

---

### Option C: Fast Deployment (NOT RECOMMENDED)

**Includes**: No Backup + No Maintenance Mode

```bash
# DANGER: Skip backup (NOT RECOMMENDED)
sudo /var/www/api-gateway/scripts/deploy_migrations.sh --skip-backup --no-maintenance
```

**ONLY USE IF**: You have external backups or this is a development environment

**Risk**: Cannot rollback without manual database restore

---

## Post-Deployment Verification (Estimated Time: 30 minutes)

### Immediate Checks (First 5 minutes)

- [ ] **Verify deployment success message**
  - Look for green "✓ Deployment Successful!" message
  - Note migration execution time (<1 second expected)

- [ ] **Check application responds**
  ```bash
  curl -I https://api.askproai.de
  # Expected: HTTP/2 200 OK
  ```

- [ ] **Verify tables exist in production**
  ```bash
  mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
  SELECT TABLE_NAME, TABLE_ROWS
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = 'askproai_db'
    AND TABLE_NAME IN (
      'notification_configurations',
      'policy_configurations',
      'callback_requests',
      'appointment_modifications',
      'callback_escalations',
      'appointment_modification_stats'
    );
  "
  ```
  **Expected**: 6 tables listed, TABLE_ROWS = 0

---

### Monitoring Period (Next 25 minutes)

- [ ] **Monitor application logs**
  ```bash
  tail -f /var/www/api-gateway/storage/logs/laravel.log
  ```
  **Watch For**: Foreign key constraint errors, database errors

- [ ] **Monitor database error log**
  ```bash
  tail -f /var/log/mysql/error.log
  ```
  **Watch For**: Constraint violations, deadlocks

- [ ] **Check system resources**
  ```bash
  htop
  ```
  **Watch For**: Memory spikes, CPU issues

- [ ] **Test application functionality**
  - Try creating a test booking (if applicable)
  - Verify existing features still work
  - Check API endpoints respond normally

---

### 30-Minute Checkpoint

- [ ] **No errors in application logs** (critical errors only)
- [ ] **No database constraint violations**
- [ ] **Application performance normal**
- [ ] **No user-reported issues**

**If ALL checks pass**: Deployment successful, continue normal operations

**If ANY checks fail**: Proceed to Rollback section

---

## Rollback Procedures (If Issues Detected)

### Severity Assessment

**Minor Issues** (Continue monitoring):
- Non-critical warnings in logs
- Slight performance degradation
- Isolated errors not affecting users

**Action**: Continue monitoring, document issues, fix in next deployment

---

**Major Issues** (Rollback migrations):
- Repeated foreign key constraint errors
- Application features breaking
- Database performance degradation

**Action**: Execute migration rollback

```bash
cd /var/www/api-gateway

# Enable maintenance mode
php artisan down

# Rollback migrations
php artisan migrate:rollback --step=6 --force

# Verify tables dropped
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
SHOW TABLES LIKE 'notification_configurations';
"
# Expected: Empty result

# Clear caches
php artisan config:clear
php artisan cache:clear

# Disable maintenance mode
php artisan up
```

**Time**: 2-5 minutes

---

**Critical Issues** (Full database restore):
- Data corruption detected
- Cascade delete triggered unexpectedly
- Orphaned records appearing
- Migration rollback failed

**Action**: Restore from backup

```bash
# Enable maintenance mode
cd /var/www/api-gateway
php artisan down

# Stop application services
systemctl stop php8.3-fpm
systemctl stop nginx

# Restore database (adjust timestamp)
BACKUP_FILE="/var/backups/mysql/askproai_db_pre_migration_20251002_HHMMSS.sql.gz"
gunzip < "${BACKUP_FILE}" | mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db

# Restart services
systemctl start php8.3-fpm
systemctl start nginx

# Disable maintenance mode
cd /var/www/api-gateway
php artisan up

# Verify application health
curl -I https://api.askproai.de
```

**Time**: 10-20 minutes (depends on backup size)

---

## Success Criteria

### Deployment considered successful when:

- ✓ All 6 tables created with correct structure
- ✓ Foreign keys validated (CASCADE delete on company_id)
- ✓ Indexes created on all tables
- ✓ No orphaned records detected
- ✓ Application logs clean (no critical errors)
- ✓ Database logs clean (no constraint violations)
- ✓ 30-minute monitoring period passed without issues
- ✓ Application performance unchanged
- ✓ Backup created and stored securely

---

## Communication Template

### Team Notification - Deployment Start

```
Subject: [DEPLOYMENT] Phase 2 Multi-Tenant Schema Migration - STARTING

Team,

Starting production deployment of Phase 2 multi-tenant database migrations.

Timeline:
- Start: [TIME]
- Expected completion: [TIME + 20 minutes]
- Maintenance window: [IF APPLICABLE]

Affected systems:
- Database: askproai_db (6 new tables)
- Application: Zero impact expected

Monitoring: Active for 30 minutes post-deployment

Contact: [YOUR NAME/NUMBER] for issues
```

---

### Team Notification - Deployment Success

```
Subject: [SUCCESS] Phase 2 Multi-Tenant Schema Migration - COMPLETED

Team,

✓ Production deployment completed successfully.

Summary:
- 6 tables created: notification_configurations, policy_configurations, callback_requests, appointment_modifications, callback_escalations, appointment_modification_stats
- Migration time: <1 second
- Foreign keys validated: 6/6 CASCADE
- Data integrity: Verified (0 orphaned records)
- Backup: /var/backups/mysql/askproai_db_pre_migration_[TIMESTAMP].sql.gz

Status: Monitoring active for next 30 minutes

Next steps:
- Continue Phase 3 (Observers - already complete)
- Begin Phase 4 (Policy Service Layer)
```

---

### Team Notification - Deployment Rollback

```
Subject: [ROLLBACK] Phase 2 Multi-Tenant Schema Migration - ROLLED BACK

Team,

Production deployment rolled back due to [REASON].

Actions taken:
- [Migrations rolled back / Database restored from backup]
- Application status: [Online/Maintenance mode]
- Data integrity: [Verified/Under investigation]

Current status: Application running on pre-migration schema

Next steps:
- Root cause analysis: [TIME]
- Fix scheduled for: [DATE/TIME]
- Re-deployment planned: [DATE/TIME]

Contact: [YOUR NAME/NUMBER] for questions
```

---

## Files Reference

### Documentation
- **Full Deployment Plan**: `/var/www/api-gateway/claudedocs/MIGRATION_TESTING_DEPLOYMENT_PLAN.md`
- **This Checklist**: `/var/www/api-gateway/claudedocs/MIGRATION_DEPLOYMENT_CHECKLIST.md`

### Scripts
- **Test Script**: `/var/www/api-gateway/scripts/test_migrations.sh`
- **Deploy Script**: `/var/www/api-gateway/scripts/deploy_migrations.sh`

### Migrations
- `/var/www/api-gateway/database/migrations/2025_10_01_060100_create_notification_configurations_table.php`
- `/var/www/api-gateway/database/migrations/2025_10_01_060201_create_policy_configurations_table.php`
- `/var/www/api-gateway/database/migrations/2025_10_01_060203_create_callback_requests_table.php`
- `/var/www/api-gateway/database/migrations/2025_10_01_060304_create_appointment_modifications_table.php`
- `/var/www/api-gateway/database/migrations/2025_10_01_060305_create_callback_escalations_table.php`
- `/var/www/api-gateway/database/migrations/2025_10_01_060400_create_appointment_modification_stats_table.php`

### Logs
- **Migration Test Log**: `/var/log/migration_test_YYYYMMDD_HHMMSS.log`
- **Production Deploy Log**: `/var/log/migration_production_YYYYMMDD_HHMMSS.log`
- **Application Log**: `/var/www/api-gateway/storage/logs/laravel.log`
- **Database Log**: `/var/log/mysql/error.log`

### Backups
- **Backup Directory**: `/var/backups/mysql/`
- **Backup Pattern**: `askproai_db_pre_migration_YYYYMMDD_HHMMSS.sql.gz`

---

## Quick Command Reference

```bash
# Test migrations
sudo /var/www/api-gateway/scripts/test_migrations.sh

# Deploy to production (full safety)
sudo /var/www/api-gateway/scripts/deploy_migrations.sh

# Deploy without downtime
sudo /var/www/api-gateway/scripts/deploy_migrations.sh --no-maintenance

# Manual rollback
php artisan migrate:rollback --step=6 --force

# Check migration status
php artisan migrate:status

# Monitor logs
tail -f /var/www/api-gateway/storage/logs/laravel.log
tail -f /var/log/mysql/error.log

# Check database tables
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SHOW TABLES LIKE '%notification%';"

# Verify foreign keys
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
SELECT TABLE_NAME, CONSTRAINT_NAME, DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'askproai_db'
  AND TABLE_NAME IN ('notification_configurations', 'policy_configurations', 'callback_requests', 'appointment_modifications', 'callback_escalations', 'appointment_modification_stats');
"
```

---

## Support Contacts

**Database Issues**: Check MySQL error log first
**Application Issues**: Check Laravel log first
**Emergency Rollback**: Use rollback procedures above

**Escalation Path**:
1. Check logs for specific error messages
2. Consult troubleshooting section in full deployment plan
3. If data corruption suspected → Immediate rollback
4. If uncertain → Contact senior database administrator

---

**Document Version**: 1.0
**Last Updated**: 2025-10-02
**Prepared By**: Backend Architect
