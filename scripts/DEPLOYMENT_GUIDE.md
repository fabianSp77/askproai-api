# Production Deployment Guide

Complete guide for deploying database migrations to production with automated validation, monitoring, and rollback capabilities.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Scripts Reference](#scripts-reference)
3. [Quick Start](#quick-start)
4. [Detailed Usage](#detailed-usage)
5. [Expected Outputs](#expected-outputs)
6. [Troubleshooting](#troubleshooting)
7. [Recovery Procedures](#recovery-procedures)

---

## Overview

This deployment automation suite provides:

- **Pre-deployment validation** - Verify environment before changes
- **Migration validation** - Test each migration independently
- **Smoke testing** - Validate functionality post-deployment
- **Continuous monitoring** - 3-hour observation window
- **Emergency rollback** - Automated recovery procedures
- **Orchestration** - Complete deployment workflow automation

### Architecture

```
deploy-production.sh (ORCHESTRATOR)
├── deploy-pre-check.sh        → Pre-deployment validation
├── [Database Backup]           → Safety checkpoint
├── [Enable Maintenance Mode]   → Prevent user disruption
├── php artisan migrate         → Execute migrations
├── validate-migration.sh       → Verify each table
├── smoke-test.sh              → Functional validation
├── [Disable Maintenance Mode]  → Site goes live
└── monitor-deployment.sh       → Post-deployment observation
    └── emergency-rollback.sh   → Auto-rollback on failure
```

---

## Scripts Reference

### 1. deploy-pre-check.sh

**Purpose**: Comprehensive environment validation before deployment

**Location**: `/var/www/api-gateway/scripts/deploy-pre-check.sh`

**Checks Performed**:
- Database connectivity
- MySQL version compatibility (≥8.0)
- Disk space availability (≥10GB)
- Required parent tables exist
- No table name conflicts
- Backup directory writable
- PHP extensions loaded
- Artisan command access
- Migration files exist
- Database locks detection
- Redis connection

**Usage**:
```bash
./deploy-pre-check.sh
```

**Exit Codes**:
- `0` - All checks passed
- `1` - Critical checks failed

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/pre-check-*.log`

---

### 2. validate-migration.sh

**Purpose**: Validate individual migration execution and table structure

**Location**: `/var/www/api-gateway/scripts/validate-migration.sh`

**Validates**:
- Table exists
- All expected columns present
- All indexes created
- Foreign key constraints
- Soft delete support
- Basic INSERT operations
- Foreign key enforcement

**Usage**:
```bash
./validate-migration.sh <table_name>

# Examples:
./validate-migration.sh policy_configurations
./validate-migration.sh callback_requests
```

**Exit Codes**:
- `0` - Validation passed
- `1` - Validation failed

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/validate-<table>-*.log`

---

### 3. smoke-test.sh

**Purpose**: Quick functional validation after deployment

**Location**: `/var/www/api-gateway/scripts/smoke-test.sh`

**Tests Performed**:
- PolicyConfiguration CRUD operations
- CallbackRequest CRUD operations
- Status transitions
- Foreign key integrity
- Cache operations
- Index performance
- Effective policy config query

**Usage**:
```bash
./smoke-test.sh
```

**Exit Codes**:
- `0` - GREEN (all tests passed)
- `1` - YELLOW (operational with warnings)
- `2` - RED (critical failures)

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/smoke-test-*.log`

---

### 4. monitor-deployment.sh

**Purpose**: Continuous monitoring during 3-hour post-deployment window

**Location**: `/var/www/api-gateway/scripts/monitor-deployment.sh`

**Monitors**:
- Error log analysis
- Slow query detection
- Redis memory usage
- Database connections
- Queue workers
- Disk space
- Migration tables
- Application health
- Migration integrity

**Usage**:
```bash
# Default: 3 hours (180 minutes)
./monitor-deployment.sh

# Custom duration: 1 hour
./monitor-deployment.sh 60

# Stop monitoring: Ctrl+C
```

**Alert Thresholds**:
- Errors: >5 per minute
- Slow queries: >10 per minute
- Redis memory: >512MB
- Disk usage: >90% critical, >80% warning

**Exit Codes**:
- `0` - Monitoring complete, no critical issues
- `1` - Critical errors detected

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/monitor-*.log`

---

### 5. emergency-rollback.sh

**Purpose**: Automated rollback for failed deployments

**Location**: `/var/www/api-gateway/scripts/emergency-rollback.sh`

**Rollback Steps**:
1. Enable maintenance mode
2. Create emergency pre-rollback backup
3. Rollback migrations (--step=7)
4. Restore database from backup
5. Clear all caches
6. Verify rollback success
7. Disable maintenance mode
8. Run post-rollback health checks

**Usage**:
```bash
# Interactive mode (requires confirmation)
./emergency-rollback.sh

# Automatic mode (no confirmation)
./emergency-rollback.sh --auto

# Use specific backup file
./emergency-rollback.sh --backup-file=/path/to/backup.sql.gz
```

**Exit Codes**:
- `0` - Rollback successful
- `1` - Rollback failed (manual intervention required)

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/rollback-*.log`

---

### 6. deploy-production.sh (ORCHESTRATOR)

**Purpose**: Complete deployment workflow automation

**Location**: `/var/www/api-gateway/scripts/deploy-production.sh`

**Deployment Flow**:
1. Pre-deployment validation
2. Database backup
3. Enable maintenance mode
4. Run migrations (one by one)
5. Validate each migration table
6. Clear application caches
7. Run smoke tests
8. Disable maintenance mode
9. Start post-deployment monitoring

**Usage**:
```bash
# Full deployment with all safety checks
./deploy-production.sh

# Skip backup creation (not recommended)
./deploy-production.sh --skip-backup

# Skip post-deployment monitoring
./deploy-production.sh --skip-monitoring

# Both options
./deploy-production.sh --skip-backup --skip-monitoring
```

**Exit Codes**:
- `0` - Deployment successful
- `1` - Pre-check failed (no changes made)
- `2` - Deployment failed (rollback executed)
- `3` - Error during deployment (rollback triggered)

**Output Location**: `/var/www/api-gateway/storage/logs/deployment/deploy-*.log`

---

## Quick Start

### Option 1: Full Automated Deployment (RECOMMENDED)

```bash
cd /var/www/api-gateway/scripts

# Run complete deployment with all safety checks
./deploy-production.sh
```

This will:
- Validate environment
- Create backup
- Deploy migrations
- Validate changes
- Run smoke tests
- Start monitoring

**Duration**: ~5-10 minutes + 3 hours monitoring

---

### Option 2: Step-by-Step Manual Deployment

```bash
cd /var/www/api-gateway/scripts

# Step 1: Pre-check
./deploy-pre-check.sh
# ✅ All checks passed? Continue. ❌ Failed? Fix issues first.

# Step 2: Manual backup (if not using orchestrator)
mysqldump -u root askproai_db > /var/www/api-gateway/storage/backups/manual-backup-$(date +%Y%m%d-%H%M%S).sql

# Step 3: Enable maintenance mode
cd /var/www/api-gateway
php artisan down --retry=60

# Step 4: Run migrations
php artisan migrate --force --step=7

# Step 5: Validate migrations
cd /var/www/api-gateway/scripts
./validate-migration.sh policy_configurations
./validate-migration.sh callback_requests

# Step 6: Run smoke tests
./smoke-test.sh
# 🟢 GREEN? Continue. 🔴 RED? Rollback immediately.

# Step 7: Disable maintenance mode
cd /var/www/api-gateway
php artisan up

# Step 8: Start monitoring
cd /var/www/api-gateway/scripts
./monitor-deployment.sh 180
```

---

## Detailed Usage

### Pre-Deployment Checklist

Before running deployment:

```bash
# 1. Check current environment
cd /var/www/api-gateway
php artisan --version
php -v
mysql --version

# 2. Verify database access
php artisan db:show

# 3. Check disk space
df -h /var/www/api-gateway

# 4. Review pending migrations
php artisan migrate:status

# 5. Check for running processes
ps aux | grep "queue:work"
ps aux | grep "artisan"

# 6. Verify backup directory
ls -lh /var/www/api-gateway/storage/backups/
```

### During Deployment

Monitor deployment progress:

```bash
# Follow deployment log
tail -f /var/www/api-gateway/storage/logs/deployment/deploy-*.log

# Check Laravel errors
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Monitor database connections
watch -n 5 'mysql -u root -e "SHOW PROCESSLIST;"'
```

### Post-Deployment

After successful deployment:

```bash
# 1. Monitor error logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i error

# 2. Check migration tables
mysql -u root askproai_db -e "SELECT COUNT(*) FROM policy_configurations;"
mysql -u root askproai_db -e "SELECT COUNT(*) FROM callback_requests;"

# 3. Test API endpoints
curl -X GET https://your-domain.com/api/health

# 4. Monitor performance
cd /var/www/api-gateway/scripts
./monitor-deployment.sh 180
```

---

## Expected Outputs

### ✅ Successful Pre-Check

```
==================================
Pre-Deployment Check - 2025-10-02 14:30:00
==================================
ℹ️  Starting pre-deployment validation...

ℹ️  Checking database connectivity...
✅ Database connection successful
ℹ️  Checking MySQL version...
✅ MySQL version: 8.0.35
✅ Version meets minimum requirement (8.0)
ℹ️  Checking disk space...
ℹ️  Available disk space: 45GB
✅ Sufficient disk space available (45GB >= 10GB)
ℹ️  Checking required parent tables exist...
✅ Table exists: companies
✅ Table exists: branches
✅ Table exists: services
✅ Table exists: staff
✅ Table exists: appointments
✅ Table exists: customers
ℹ️  Checking for table name conflicts...
✅ No conflict for table: policy_configurations
✅ No conflict for table: callback_requests

==================================
Pre-Deployment Check Summary
==================================
✅ Checks passed: 15
⚠️  Warnings: 0
❌ Checks failed: 0
==================================
✅ All critical checks passed - safe to deploy
```

### ✅ Successful Migration Validation

```
===================================
Migration Validation: policy_configurations
===================================
ℹ️  Checking if table 'policy_configurations' exists...
✅ Table 'policy_configurations' exists
ℹ️  Checking columns for table 'policy_configurations'...
✅ Column exists: id
✅ Column exists: company_id
✅ Column exists: branch_id
✅ Column exists: config_type
✅ Column exists: callback_url
✅ All expected columns present (20 columns)
ℹ️  Checking indexes for table 'policy_configurations'...
✅ Index exists: policy_configurations_company_id_foreign
✅ Index exists: policy_configurations_config_type_index
✅ All expected indexes present (9 indexes)
ℹ️  Checking foreign keys for table 'policy_configurations'...
✅ Foreign keys created: 4 constraints
ℹ️  Testing basic INSERT operation...
✅ INSERT test passed (transaction rolled back)
ℹ️  Testing foreign key constraints...
✅ Foreign key constraints working correctly

===================================
Validation Summary: policy_configurations
===================================
✅ Checks passed: 7
❌ Checks failed: 0
===================================
✅ Migration validation PASSED
```

### 🟢 Successful Smoke Test (GREEN)

```
ℹ️  Starting smoke tests...

ℹ️  Testing PolicyConfiguration CREATE...
✅ PolicyConfiguration CREATE test passed
ℹ️  Testing PolicyConfiguration UPDATE...
✅ PolicyConfiguration UPDATE test passed
ℹ️  Testing PolicyConfiguration SOFT DELETE...
✅ PolicyConfiguration SOFT DELETE test passed
ℹ️  Testing CallbackRequest CREATE...
✅ CallbackRequest CREATE test passed
ℹ️  Testing CallbackRequest STATUS transitions...
✅ CallbackRequest STATUS transitions test passed
ℹ️  Testing FOREIGN KEY integrity enforcement...
✅ Foreign key integrity test passed
ℹ️  Testing getEffectivePolicyConfig() query simulation...
✅ Effective policy config query test passed
ℹ️  Testing CACHE operations...
✅ Cache operations test passed
ℹ️  Testing INDEX performance...
✅ Index performance test passed

==================================
🟢 GREEN - All systems operational
==================================
✅ Tests passed: 9
⚠️  Warnings: 0
❌ Tests failed: 0
==================================
```

### ✅ Successful Deployment Summary

```
╔══════════════════════════════════════════════════════╗
║           DEPLOYMENT SUMMARY                         ║
╚══════════════════════════════════════════════════════╝

Final State: DEPLOYMENT_COMPLETE
Timestamp: Wed Oct  2 14:45:23 UTC 2025
Backup: /var/www/api-gateway/storage/backups/pre-deploy-20251002-144500.sql.gz
Log File: /var/www/api-gateway/storage/logs/deployment/deploy-20251002-144500.log

✅ DEPLOYMENT SUCCESSFUL

Next Steps:
  1. Monitor application for 3 hours
  2. Watch monitoring logs in: /var/www/api-gateway/storage/logs/deployment/
  3. Check error logs: tail -f /var/www/api-gateway/storage/logs/laravel.log
  4. Keep backup for 7 days: /var/www/api-gateway/storage/backups/pre-deploy-20251002-144500.sql.gz
```

### ❌ Failed Deployment with Rollback

```
╔══════════════════════════════════════════════════════╗
║           DEPLOYMENT SUMMARY                         ║
╚══════════════════════════════════════════════════════╝

Final State: ROLLED_BACK
Timestamp: Wed Oct  2 14:50:15 UTC 2025
Backup: /var/www/api-gateway/storage/backups/pre-deploy-20251002-144500.sql.gz
Log File: /var/www/api-gateway/storage/logs/deployment/deploy-20251002-144500.log

❌ DEPLOYMENT FAILED - ROLLED BACK

Urgent Actions:
  1. Review deployment log: /var/www/api-gateway/storage/logs/deployment/deploy-20251002-144500.log
  2. Review rollback log: /var/www/api-gateway/storage/logs/deployment/rollback-20251002-145000.log
  3. Verify site functionality
  4. Investigate root cause before retry
```

---

## Troubleshooting

### Pre-Check Failures

#### Issue: Database connection failed

```bash
# Check database credentials
cat /var/www/api-gateway/.env | grep DB_

# Test connection manually
mysql -u root askproai_db -e "SELECT 1;"

# Check MySQL service
systemctl status mysql
```

#### Issue: Insufficient disk space

```bash
# Check disk usage
df -h /var/www/api-gateway

# Clean old backups
find /var/www/api-gateway/storage/backups/ -name "*.sql.gz" -mtime +7 -delete

# Clear logs
find /var/www/api-gateway/storage/logs/ -name "*.log" -mtime +30 -delete
```

#### Issue: Required table missing

```bash
# Check which tables exist
mysql -u root askproai_db -e "SHOW TABLES;"

# Run previous migrations
cd /var/www/api-gateway
php artisan migrate:status
php artisan migrate --force
```

### Migration Failures

#### Issue: Foreign key constraint failed

```bash
# Check parent table data
mysql -u root askproai_db -e "SELECT COUNT(*) FROM companies;"
mysql -u root askproai_db -e "SELECT COUNT(*) FROM customers;"

# Check for orphaned records
mysql -u root askproai_db -e "
SELECT * FROM callback_requests
WHERE customer_id NOT IN (SELECT id FROM customers)
LIMIT 10;
"
```

#### Issue: Table already exists

```bash
# Check if table exists from previous attempt
mysql -u root askproai_db -e "SHOW TABLES LIKE 'policy_configurations';"

# Drop if it's from failed deployment
mysql -u root askproai_db -e "DROP TABLE IF EXISTS policy_configurations;"

# Re-run migration
cd /var/www/api-gateway
php artisan migrate --force
```

### Smoke Test Failures

#### Issue: Tests timeout

```bash
# Check database load
mysql -u root -e "SHOW PROCESSLIST;"

# Check for locks
mysql -u root -e "SHOW OPEN TABLES WHERE In_use > 0;"

# Kill slow queries
mysql -u root -e "KILL <process_id>;"
```

#### Issue: Cache operations fail

```bash
# Check Redis connection
redis-cli ping

# Restart Redis
systemctl restart redis

# Clear Redis manually
redis-cli FLUSHALL
```

### Monitoring Alerts

#### Issue: High error rate

```bash
# View recent errors
tail -n 100 /var/www/api-gateway/storage/logs/laravel.log | grep ERROR

# Check specific error patterns
grep "SQLSTATE" /var/www/api-gateway/storage/logs/laravel.log | tail -20

# Stop monitoring and investigate
# Press Ctrl+C to stop monitor-deployment.sh
```

#### Issue: Critical alerts threshold exceeded

```bash
# If >5 critical alerts, consider rollback
cd /var/www/api-gateway/scripts
./emergency-rollback.sh

# Investigate root cause
tail -n 500 /var/www/api-gateway/storage/logs/laravel.log
```

---

## Recovery Procedures

### Full Rollback

If deployment fails and automatic rollback doesn't work:

```bash
cd /var/www/api-gateway/scripts

# 1. Enable maintenance mode
cd /var/www/api-gateway
php artisan down

# 2. Find latest backup
ls -lht /var/www/api-gateway/storage/backups/ | head -5

# 3. Restore database
gunzip < /var/www/api-gateway/storage/backups/pre-deploy-YYYYMMDD-HHMMSS.sql.gz | mysql -u root askproai_db

# 4. Rollback migrations
php artisan migrate:rollback --step=7

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL

# 6. Disable maintenance mode
php artisan up

# 7. Verify functionality
cd /var/www/api-gateway/scripts
./smoke-test.sh
```

### Partial Rollback (Single Migration)

If only one migration needs rollback:

```bash
cd /var/www/api-gateway

# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Verify table removed
mysql -u root askproai_db -e "SHOW TABLES LIKE 'policy_configurations';"
```

### Manual Table Cleanup

If migrations leave orphaned tables:

```bash
# Drop tables manually
mysql -u root askproai_db -e "
DROP TABLE IF EXISTS callback_requests;
DROP TABLE IF EXISTS policy_configurations;
"

# Verify cleanup
mysql -u root askproai_db -e "SHOW TABLES;"

# Update migrations table
mysql -u root askproai_db -e "
DELETE FROM migrations
WHERE migration LIKE '%create_policy_configurations_table%'
   OR migration LIKE '%create_callback_requests_table%';
"
```

### Site Recovery

If site is down after deployment:

```bash
# 1. Check maintenance mode
ls -la /var/www/api-gateway/storage/framework/down

# 2. Disable if exists
cd /var/www/api-gateway
php artisan up

# 3. Check web server
systemctl status nginx
systemctl restart nginx

# 4. Check PHP-FPM
systemctl status php8.3-fpm
systemctl restart php8.3-fpm

# 5. Check application
curl -I http://localhost

# 6. Check errors
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

---

## Best Practices

### Before Deployment

1. **Test in staging** - Always test migrations in staging environment first
2. **Review migration code** - Double-check migration files for correctness
3. **Backup verification** - Ensure backup directory has sufficient space
4. **Low-traffic window** - Schedule deployments during low-traffic periods
5. **Team notification** - Notify team before deployment

### During Deployment

1. **Monitor actively** - Watch logs during deployment process
2. **Quick rollback** - Be ready to rollback if issues arise
3. **Communication** - Keep stakeholders informed of progress
4. **Document issues** - Log any unexpected behavior

### After Deployment

1. **Full monitoring** - Complete 3-hour monitoring window
2. **Error tracking** - Monitor error logs for new patterns
3. **Performance check** - Verify response times remain acceptable
4. **Data validation** - Spot-check migrated data integrity
5. **Backup retention** - Keep deployment backup for 7+ days

---

## Maintenance

### Log Cleanup

```bash
# Clean old deployment logs (30+ days)
find /var/www/api-gateway/storage/logs/deployment/ -name "*.log" -mtime +30 -delete

# Clean old backups (7+ days)
find /var/www/api-gateway/storage/backups/ -name "*.sql.gz" -mtime +7 -delete
```

### Script Updates

After modifying scripts:

```bash
# Make executable
chmod +x /var/www/api-gateway/scripts/*.sh

# Test individually
./deploy-pre-check.sh
./validate-migration.sh policy_configurations
./smoke-test.sh

# Version control
cd /var/www/api-gateway
git add scripts/
git commit -m "Update deployment scripts"
```

---

## Support

### Log Locations

- **Deployment logs**: `/var/www/api-gateway/storage/logs/deployment/`
- **Application logs**: `/var/www/api-gateway/storage/logs/laravel.log`
- **MySQL slow query**: `/var/log/mysql/slow-query.log`
- **Nginx errors**: `/var/log/nginx/error.log`

### Contact

For deployment issues:

1. Review logs first
2. Check troubleshooting section
3. Contact DevOps team
4. Escalate if data integrity at risk

---

**Last Updated**: 2025-10-02
**Version**: 1.0.0
**Maintained by**: DevOps Team
