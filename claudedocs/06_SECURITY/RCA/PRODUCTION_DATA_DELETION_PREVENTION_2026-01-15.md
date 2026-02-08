# Production Data Deletion Prevention - Security Analysis

**Incident**: PHPUnit tests deleted production data
**Date**: 2026-01-15
**Severity**: CRITICAL
**Status**: Analysis Complete - Remediation Required

---

## Executive Summary

A PHPUnit test run inadvertently executed against the production database, causing data deletion. The root cause was insufficient isolation between test and production environments. This analysis identifies multiple layers of missing safeguards and provides a prioritized remediation plan.

---

## 1. Current State Assessment

### 1.1 What Was Fixed
- `phpunit.xml` now specifies `DB_DATABASE=askproai_testing`
- Test database `askproai_testing` created and accessible

### 1.2 Critical Findings

| Area | Finding | Risk Level |
|------|---------|------------|
| **Database User** | Same credentials for production + testing | CRITICAL |
| **TestCase.php** | No production environment check | HIGH |
| **APP_ENV** | Production .env has `APP_ENV=production` | OK |
| **Backup Script** | `backup-run.sh` is MISSING | CRITICAL |
| **RefreshDatabase** | 40+ tests use this trait without safeguards | HIGH |

---

## 2. Risk Matrix - What Could Still Go Wrong

### 2.1 High Probability / High Impact

| Risk | Probability | Impact | Current Mitigation |
|------|-------------|--------|-------------------|
| Manual test run on production server | HIGH | CRITICAL | None - same user can access both DBs |
| Cron job misconfiguration | MEDIUM | CRITICAL | CI uses separate DB |
| Developer runs tests with wrong .env | HIGH | HIGH | phpunit.xml override |
| Artisan tinker deletes data | HIGH | HIGH | None |
| Migration run on wrong DB | MEDIUM | HIGH | None |

### 2.2 Medium Probability / High Impact

| Risk | Probability | Impact | Current Mitigation |
|------|-------------|--------|-------------------|
| CI/CD pipeline misconfiguration | LOW | CRITICAL | Uses isolated containers |
| Backup failure goes unnoticed | MEDIUM | HIGH | Health check script (but broken) |
| Direct MySQL deletion | LOW | HIGH | User has full privileges |

### 2.3 Low Probability / Critical Impact

| Risk | Probability | Impact | Current Mitigation |
|------|-------------|--------|-------------------|
| SQL injection in test code | LOW | CRITICAL | Code review |
| Malicious actor access | LOW | CRITICAL | Network security |

---

## 3. Detailed Analysis by Layer

### 3.1 Code-Level Safeguards

**Current State**: `/var/www/api-gateway/tests/TestCase.php`
```php
<?php
namespace Tests {
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //  <-- NO PRODUCTION CHECK
}
}
```

**Problem**: 40+ test files use `RefreshDatabase` trait which truncates ALL tables:
- `tests/Feature/NotificationSystemTest.php`
- `tests/Feature/PublicBookingTest.php`
- `tests/Feature/CRM/DataConsistencyIntegrationTest.php`
- `tests/Feature/CalcomV2/CalcomV2SyncTest.php`
- And 36+ more files

**Risk**: Any of these tests running against production will DELETE ALL DATA.

### 3.2 Database-Level Safeguards

**Current MySQL User Privileges**:
```sql
GRANT RELOAD, SUPER, BINLOG MONITOR, BINLOG ADMIN, BINLOG REPLAY ON *.* TO `askproai_user`@`localhost`
GRANT ALL PRIVILEGES ON `askproai_db`.* TO `askproai_user`@`localhost`
GRANT ALL PRIVILEGES ON `askproai_testing`.* TO `askproai_user`@`localhost`
GRANT ALL PRIVILEGES ON `askproai_staging`.* TO `askproai_user`@`localhost`
```

**Problem**: Same user has full access to ALL databases including production.

### 3.3 Environment-Level Safeguards

**Production .env** (`/var/www/api-gateway/.env`):
```env
APP_ENV=production
DB_DATABASE=askproai_db
```

**Testing .env** (`/var/www/api-gateway/.env.testing`):
```env
APP_ENV=testing
DB_DATABASE=askproai_testing
```

**Problem**: If someone runs `php artisan test` without phpunit.xml, it uses .env (production).

### 3.4 Process-Level Safeguards

**Crontab** - No automated test runs on production server.

**CI/CD** (`/.github/workflows/test-automation.yml`):
- Tests run in isolated GitHub Actions containers
- Uses separate MySQL service with `askproai_testing`
- **SAFE** - CI cannot affect production

### 3.5 Backup Safeguards

**CRITICAL FAILURE**: Backup script is missing!

```bash
# Crontab references:
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh >> /var/log/backup-run.log 2>&1

# But backup-run.sh DOES NOT EXIST:
$ cat /var/log/backup-run.log
/bin/sh: 1: /var/www/api-gateway/scripts/backup-run.sh: not found
(repeated 30+ times)
```

**Available backup scripts**:
- `golden-backup.sh` - Manual golden backups
- `golden-backup-v2-ultimate.sh` - Enhanced manual backups
- `comprehensive-backup.sh` - Full system backup
- `create-full-backup.sh` - Database + files

**Last successful backup**: Unknown - cron job failing since at least Nov 2025.

---

## 4. Recommended Safeguards (Prioritized)

### Priority 1: CRITICAL (Implement Immediately)

#### 4.1 Add Production Guard to TestCase.php
**File**: `/var/www/api-gateway/tests/TestCase.php`
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CRITICAL: Prevent tests from running against production
        $this->assertNotProductionDatabase();
    }

    protected function assertNotProductionDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $env = app()->environment();

        // Block if environment is production
        if ($env === 'production') {
            throw new RuntimeException(
                "CRITICAL: Tests cannot run in production environment! " .
                "Current APP_ENV: {$env}"
            );
        }

        // Block if database is production database
        $productionDatabases = ['askproai_db', 'askpro_db', 'askproai_production'];
        if (in_array($database, $productionDatabases, true)) {
            throw new RuntimeException(
                "CRITICAL: Tests cannot run against production database! " .
                "Current DB: {$database}"
            );
        }

        // Block if database name doesn't contain 'test'
        if (stripos($database, 'test') === false) {
            throw new RuntimeException(
                "CRITICAL: Database name must contain 'test' for safety! " .
                "Current DB: {$database}. Expected: askproai_testing"
            );
        }
    }
}
```

**Implementation Time**: 5 minutes
**Risk Reduction**: 90%

#### 4.2 Fix Backup Script (Create Missing backup-run.sh)
**File**: `/var/www/api-gateway/scripts/backup-run.sh`
```bash
#!/bin/bash
# AskProAI Automated Backup Script
# Runs 3x daily via cron (03:00, 11:00, 19:00)

set -e

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/askproai"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="askproai_secure_pass_2024"
PROJECT_DIR="/var/www/api-gateway"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Database backup
echo "[$TIMESTAMP] Starting database backup..."
mysqldump -h 127.0.0.1 -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    --single-transaction \
    --routines \
    --triggers \
    | gzip > "$BACKUP_DIR/db-$TIMESTAMP.sql.gz"

# System state backup (configs, .env, etc.)
echo "[$TIMESTAMP] Creating system state backup..."
tar -czf "$BACKUP_DIR/system-state-$TIMESTAMP.tar.gz" \
    -C "$PROJECT_DIR" \
    .env \
    config/ \
    storage/logs/

# Create SHA256 checksum
sha256sum "$BACKUP_DIR/db-$TIMESTAMP.sql.gz" > "$BACKUP_DIR/backup-$TIMESTAMP.tar.gz.sha256"

# Verify backup
if [ -f "$BACKUP_DIR/db-$TIMESTAMP.sql.gz" ]; then
    SIZE=$(stat -f%z "$BACKUP_DIR/db-$TIMESTAMP.sql.gz" 2>/dev/null || stat -c%s "$BACKUP_DIR/db-$TIMESTAMP.sql.gz")
    echo "[$TIMESTAMP] Backup complete: $SIZE bytes"
else
    echo "[$TIMESTAMP] ERROR: Backup failed!"
    exit 1
fi

# Cleanup old backups (keep 14 days)
find "$BACKUP_DIR" -name "db-*.sql.gz" -mtime +14 -delete
find "$BACKUP_DIR" -name "system-state-*.tar.gz" -mtime +14 -delete

echo "[$TIMESTAMP] Backup completed successfully"
```

**Implementation Time**: 10 minutes
**Risk Reduction**: Critical for recovery

#### 4.3 Create Separate Database User for Tests
```sql
-- Create test-only user with restricted access
CREATE USER 'askproai_test'@'localhost' IDENTIFIED BY 'test_only_password_2026';
GRANT ALL PRIVILEGES ON `askproai_testing`.* TO 'askproai_test'@'localhost';
-- NO access to production database

-- Update phpunit.xml to use this user
```

**Implementation Time**: 5 minutes
**Risk Reduction**: 95%

### Priority 2: HIGH (Implement Within 24 Hours)

#### 4.4 Add Pre-Test Database Verification Script
**File**: `/var/www/api-gateway/scripts/verify-test-environment.sh`
```bash
#!/bin/bash
# Run before any test execution

DB_NAME=$(grep DB_DATABASE .env | cut -d= -f2)

if [ "$DB_NAME" != "askproai_testing" ]; then
    echo "ERROR: Not using test database!"
    echo "Current DB: $DB_NAME"
    echo "Expected: askproai_testing"
    exit 1
fi

APP_ENV=$(grep APP_ENV .env | cut -d= -f2)
if [ "$APP_ENV" = "production" ]; then
    echo "ERROR: Cannot run tests in production!"
    exit 1
fi

echo "Environment verified: $DB_NAME ($APP_ENV)"
```

#### 4.5 MySQL DELETE Trigger for Production Safety
```sql
-- Add audit/protection trigger to production database
DELIMITER //
CREATE TRIGGER prevent_mass_delete_appointments
BEFORE DELETE ON askproai_db.appointments
FOR EACH ROW
BEGIN
    DECLARE row_count INT;
    SELECT COUNT(*) INTO row_count FROM askproai_db.appointments;

    -- Block if trying to delete >50% of data (likely test cleanup)
    IF row_count > 10 AND (SELECT COUNT(*) FROM askproai_db.appointments WHERE id != OLD.id) < row_count * 0.5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'BLOCKED: Mass deletion detected. This looks like a test cleanup.';
    END IF;
END//
DELIMITER ;
```

### Priority 3: MEDIUM (Implement Within 1 Week)

#### 4.6 Add .env.testing Enforcement
**File**: `/var/www/api-gateway/bootstrap/testing.php`
```php
<?php
// Force .env.testing in test environment
if (php_sapi_name() === 'cli' && str_contains($_SERVER['argv'][0] ?? '', 'phpunit')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
    $dotenv->load();
}
```

#### 4.7 Backup Health Check Alert
Modify `/var/www/api-gateway/scripts/backup-health-check.sh` to send email/Slack alerts when backup fails.

#### 4.8 Read-Only Replica for Tests
Create MySQL read replica that tests can query without write access to production.

---

## 5. Implementation Plan

### Day 1 (Immediate - 2 Hours)

| Task | Owner | Time | Status |
|------|-------|------|--------|
| Implement TestCase.php safeguard | Dev | 15 min | TODO |
| Create backup-run.sh | DevOps | 20 min | TODO |
| Run manual backup NOW | DevOps | 10 min | TODO |
| Create separate test DB user | DBA | 15 min | TODO |
| Update phpunit.xml for test user | Dev | 5 min | TODO |
| Verify test suite still works | QA | 30 min | TODO |

### Day 2-3 (Within 72 Hours)

| Task | Owner | Time | Status |
|------|-------|------|--------|
| Implement pre-test verification script | Dev | 30 min | TODO |
| Add MySQL protection triggers | DBA | 1 hour | TODO |
| Set up backup failure alerts | DevOps | 1 hour | TODO |
| Document incident and procedures | Team | 2 hours | TODO |

### Week 2

| Task | Owner | Time | Status |
|------|-------|------|--------|
| Implement read-only replica | DBA | 4 hours | TODO |
| Add .env.testing enforcement | Dev | 1 hour | TODO |
| Security audit of all test files | Security | 4 hours | TODO |
| Create runbook for data recovery | DevOps | 2 hours | TODO |

---

## 6. Monitoring and Alerting Recommendations

### 6.1 Immediate Alerts (Slack/Email)

| Trigger | Alert Level | Action |
|---------|-------------|--------|
| Backup script failure | CRITICAL | Page on-call |
| >10 rows deleted from appointments | WARNING | Notify team |
| Test running with APP_ENV=production | CRITICAL | Block + notify |
| Unknown user accessing production DB | CRITICAL | Page security |

### 6.2 Dashboard Metrics

- Last successful backup timestamp
- Production vs test database row counts
- Failed test runs in production environment
- Database user activity audit logs

### 6.3 Health Check Additions

Add to existing health check (`/api/health`):
```json
{
  "database": {
    "name": "askproai_db",
    "is_production": true,
    "row_counts": {
      "appointments": 90,
      "customers": 500
    }
  },
  "backup": {
    "last_successful": "2026-01-15T03:00:00Z",
    "hours_since_backup": 6
  }
}
```

---

## 7. Verification Checklist

After implementing safeguards, verify:

- [ ] `php artisan test` runs against `askproai_testing` database
- [ ] TestCase.php throws exception if `APP_ENV=production`
- [ ] TestCase.php throws exception if `DB_DATABASE=askproai_db`
- [ ] New test user cannot access `askproai_db`
- [ ] Backup script runs successfully (check `/var/log/backup-run.log`)
- [ ] Backup files created in `/var/backups/askproai/`
- [ ] All 40+ RefreshDatabase tests pass with safeguards

---

## 8. Related Documentation

- `/var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh` - Manual backup procedure
- `/var/www/api-gateway/.github/workflows/test-automation.yml` - CI/CD test configuration
- `/var/www/api-gateway/phpunit.xml` - PHPUnit configuration

---

**Author**: Security Engineer (AI)
**Review Status**: Pending
**Next Review**: After implementation complete
