# Migration Testing & Deployment Plan - Multi-Tenant Database Schema

**Document Version**: 1.0
**Date**: 2025-10-02
**Environment**: Laravel 11 + MySQL 8.0
**Purpose**: Production-ready migration testing and deployment strategy for Phase 2 multi-tenant schema updates

---

## Executive Summary

**Migrations to Deploy**: 6 new tables with company_id foreign keys
- `notification_configurations` (polymorphic, hierarchical)
- `policy_configurations` (polymorphic, hierarchical with soft deletes)
- `callback_requests` (UUID foreign keys to branches/staff)
- `appointment_modifications` (audit trail with soft deletes)
- `callback_escalations` (escalation tracking)
- `appointment_modification_stats` (materialized view for O(1) lookups)

**Critical Dependencies**:
- `companies` table (exists ✓)
- `customers` table (exists ✓)
- `appointments` table (exists ✓)
- `branches` table (UUID primary key)
- `services` table (BIGINT primary key)
- `staff` table (UUID primary key)

**Estimated Time**: 2-3 hours (including verification and rollback testing)

---

## Phase 1: Pre-Migration Preparation (30 minutes)

### 1.1 Test Database Setup

```bash
# Create test database
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 <<EOF
CREATE DATABASE IF NOT EXISTS askproai_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES LIKE 'askproai_test';
EOF
```

**Expected Output**:
```
Database
askproai_test
```

**Verification**: Database created successfully

---

### 1.2 Clone Production Schema to Test Database

```bash
# Export production schema (structure only, no data)
mysqldump -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 \
  --no-data \
  --skip-add-drop-table \
  --skip-comments \
  askproai_db > /tmp/production_schema.sql

# Import into test database
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test < /tmp/production_schema.sql

# Verify table count matches
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 -e "
SELECT 'Production' AS env, COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = 'askproai_db'
UNION ALL
SELECT 'Test' AS env, COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = 'askproai_test';
"
```

**Expected Output**:
```
env         table_count
Production  XX
Test        XX
```

**Verification**: Both counts should match

---

### 1.3 Verify Critical Dependencies Exist

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<EOF
-- Check required parent tables
SELECT
  TABLE_NAME,
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'askproai_test'
  AND TABLE_NAME IN ('companies', 'customers', 'appointments', 'branches', 'services', 'staff')
  AND COLUMN_NAME = 'id'
ORDER BY TABLE_NAME;
EOF
```

**Expected Output**:
```
TABLE_NAME   COLUMN_NAME  COLUMN_TYPE       IS_NULLABLE
appointments id           bigint unsigned   NO
branches     id           char(36)          NO
companies    id           bigint unsigned   NO
customers    id           bigint unsigned   NO
services     id           bigint unsigned   NO
staff        id           char(36)          NO
```

**Verification**: All 6 tables exist with correct ID types
- `branches.id` → UUID (char(36))
- `staff.id` → UUID (char(36))
- All others → BIGINT UNSIGNED

---

### 1.4 Create Test Environment Configuration

```bash
# Create test-specific .env file
cat > /var/www/api-gateway/.env.testing <<'EOF'
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_test
DB_USERNAME=askproai_user
DB_PASSWORD=askproai_secure_pass_2024

# Disable external services during testing
MAIL_MAILER=log
QUEUE_CONNECTION=sync
CACHE_DRIVER=array
SESSION_DRIVER=array
EOF

chmod 600 /var/www/api-gateway/.env.testing
```

**Verification**: Test configuration created and secured

---

## Phase 2: Migration Testing in Test Database (45 minutes)

### 2.1 Execute Migrations with Monitoring

```bash
cd /var/www/api-gateway

# Run migrations with verbose output
php artisan migrate --database=mysql --env=testing --path=database/migrations --force --step -v

# Alternative: Run specific migrations only
php artisan migrate --database=mysql --env=testing --force --step -v \
  --path=database/migrations/2025_10_01_060100_create_notification_configurations_table.php \
  --path=database/migrations/2025_10_01_060201_create_policy_configurations_table.php \
  --path=database/migrations/2025_10_01_060203_create_callback_requests_table.php \
  --path=database/migrations/2025_10_01_060304_create_appointment_modifications_table.php \
  --path=database/migrations/2025_10_01_060305_create_callback_escalations_table.php \
  --path=database/migrations/2025_10_01_060400_create_appointment_modification_stats_table.php
```

**Expected Output** (per migration):
```
Migrating: 2025_10_01_060100_create_notification_configurations_table
Migrated:  2025_10_01_060100_create_notification_configurations_table (45.23ms)
```

**Failure Handling**:
- If migration fails → Check error message
- Common errors:
  - Foreign key constraint fails → Parent table doesn't exist
  - Duplicate column → Table already exists (check Schema::hasTable() guard)
  - Syntax error → Review migration SQL

**Time Estimate**: 5-10 seconds per migration

---

### 2.2 Verify Table Structure

```bash
# Verify all 6 tables created
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test -e "
SHOW TABLES LIKE '%notification_configurations%';
SHOW TABLES LIKE '%policy_configurations%';
SHOW TABLES LIKE '%callback_requests%';
SHOW TABLES LIKE '%appointment_modifications%';
SHOW TABLES LIKE '%callback_escalations%';
SHOW TABLES LIKE '%appointment_modification_stats%';
"
```

**Expected Output**:
```
Tables_in_askproai_test (%notification_configurations%)
notification_configurations

Tables_in_askproai_test (%policy_configurations%)
policy_configurations

... (all 6 tables listed)
```

---

### 2.3 Validate company_id Foreign Keys

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Check company_id foreign key constraints
SELECT
  TABLE_NAME,
  COLUMN_NAME,
  CONSTRAINT_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME,
  DELETE_RULE,
  UPDATE_RULE
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'askproai_test'
  AND COLUMN_NAME = 'company_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  )
ORDER BY TABLE_NAME;
EOF
```

**Expected Output**:
```
TABLE_NAME                         COLUMN_NAME  CONSTRAINT_NAME                 REFERENCED_TABLE_NAME  REFERENCED_COLUMN_NAME  DELETE_RULE  UPDATE_RULE
appointment_modification_stats     company_id   FK_stats_company                companies              id                      CASCADE      CASCADE
appointment_modifications          company_id   FK_mods_company                 companies              id                      CASCADE      CASCADE
callback_escalations               company_id   FK_escalations_company          companies              id                      CASCADE      CASCADE
callback_requests                  company_id   FK_callback_company             companies              id                      CASCADE      CASCADE
notification_configurations        company_id   FK_notif_config_company         companies              id                      CASCADE      CASCADE
policy_configurations              company_id   FK_policy_company               companies              id                      CASCADE      CASCADE
```

**Verification Checklist**:
- ✓ All 6 tables have company_id foreign key
- ✓ All reference companies.id
- ✓ All use CASCADE on delete (critical for multi-tenancy)
- ✓ All use CASCADE on update

**Critical Safety Check**:
If DELETE_RULE is NOT CASCADE → **STOP DEPLOYMENT**
This would break multi-tenant data isolation when companies are deleted.

---

### 2.4 Validate Indexes Created

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Check indexes on company_id columns
SELECT
  TABLE_NAME,
  INDEX_NAME,
  COLUMN_NAME,
  SEQ_IN_INDEX,
  NON_UNIQUE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'askproai_test'
  AND COLUMN_NAME = 'company_id'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  )
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
EOF
```

**Expected Output** (example for notification_configurations):
```
TABLE_NAME                      INDEX_NAME                    COLUMN_NAME  SEQ_IN_INDEX  NON_UNIQUE
notification_configurations     notif_config_company_idx      company_id   1             1
notification_configurations     notif_config_lookup_idx       company_id   1             1
notification_configurations     notif_config_event_enabled_idx company_id   1             1
notification_configurations     notif_config_unique_constraint company_id   1             0
```

**Verification**:
- ✓ Each table has at least 1 index starting with company_id
- ✓ Unique constraints exist where defined (NON_UNIQUE = 0)
- ✓ Composite indexes have correct column order (SEQ_IN_INDEX)

---

### 2.5 Validate Unique Constraints

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Check unique constraints
SELECT
  TABLE_NAME,
  CONSTRAINT_NAME,
  GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS columns
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'askproai_test'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'appointment_modification_stats'
  )
  AND CONSTRAINT_NAME LIKE '%unique%'
GROUP BY TABLE_NAME, CONSTRAINT_NAME
ORDER BY TABLE_NAME;
EOF
```

**Expected Output**:
```
TABLE_NAME                         CONSTRAINT_NAME                  columns
appointment_modification_stats     unique_customer_stat_period      company_id,customer_id,stat_type,period_start
notification_configurations        notif_config_unique_constraint   company_id,configurable_type,configurable_id,event_type,channel
policy_configurations              unique_policy_per_entity         company_id,configurable_type,configurable_id,policy_type,deleted_at
```

**Verification**:
- ✓ All unique constraints start with company_id (multi-tenant isolation)
- ✓ policy_configurations includes deleted_at (supports soft deletes)

---

### 2.6 Validate Polymorphic Indexes

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Check polymorphic indexes (configurable_type + configurable_id)
SELECT
  TABLE_NAME,
  INDEX_NAME,
  GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'askproai_test'
  AND TABLE_NAME IN ('notification_configurations', 'policy_configurations')
  AND INDEX_NAME LIKE '%polymorphic%'
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME;
EOF
```

**Expected Output**:
```
TABLE_NAME                      INDEX_NAME                      columns
notification_configurations     notif_config_polymorphic_idx    configurable_type,configurable_id
policy_configurations           idx_polymorphic_config          company_id,configurable_type,configurable_id
```

**Verification**:
- ✓ Polymorphic indexes exist for efficient relationship lookups
- ✓ Column order optimized for query patterns

---

## Phase 3: Data Integrity Testing (30 minutes)

### 3.1 Test Cascade Delete Behavior

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Create test company
INSERT INTO companies (id, name, created_at, updated_at)
VALUES (999999, 'Test Company for Deletion', NOW(), NOW());

-- Create test records in each table
INSERT INTO notification_configurations
  (company_id, configurable_type, configurable_id, event_type, channel, created_at, updated_at)
VALUES
  (999999, 'App\\Models\\Company', 999999, 'test_event', 'email', NOW(), NOW());

INSERT INTO policy_configurations
  (company_id, configurable_type, configurable_id, policy_type, config, created_at, updated_at)
VALUES
  (999999, 'App\\Models\\Company', '999999', 'cancellation', '{"hours_before": 24}', NOW(), NOW());

-- Verify records created
SELECT 'Before Delete' AS stage, COUNT(*) AS notification_count
FROM notification_configurations WHERE company_id = 999999;

SELECT 'Before Delete' AS stage, COUNT(*) AS policy_count
FROM policy_configurations WHERE company_id = 999999;

-- Delete company (should cascade)
DELETE FROM companies WHERE id = 999999;

-- Verify cascade deletion
SELECT 'After Delete' AS stage, COUNT(*) AS notification_count
FROM notification_configurations WHERE company_id = 999999;

SELECT 'After Delete' AS stage, COUNT(*) AS policy_count
FROM policy_configurations WHERE company_id = 999999;
EOF
```

**Expected Output**:
```
stage          notification_count
Before Delete  1
After Delete   0

stage          policy_count
Before Delete  1
After Delete   0
```

**Verification**: Cascade delete works correctly (count = 0 after delete)

**Failure Scenario**:
If count > 0 after delete → Foreign key CASCADE not working → **STOP DEPLOYMENT**

---

### 3.2 Test Soft Delete Behavior (policy_configurations)

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Create test company and policy
INSERT INTO companies (id, name, created_at, updated_at)
VALUES (999998, 'Test Soft Delete Company', NOW(), NOW());

INSERT INTO policy_configurations
  (company_id, configurable_type, configurable_id, policy_type, config, created_at, updated_at)
VALUES
  (999998, 'App\\Models\\Company', '999998', 'cancellation', '{"hours_before": 24}', NOW(), NOW());

-- Get the policy ID
SET @policy_id = LAST_INSERT_ID();

-- Soft delete the policy
UPDATE policy_configurations
SET deleted_at = NOW()
WHERE id = @policy_id;

-- Verify soft delete
SELECT
  id,
  company_id,
  policy_type,
  deleted_at IS NOT NULL AS is_soft_deleted
FROM policy_configurations
WHERE id = @policy_id;

-- Test unique constraint with soft delete (should allow duplicate after soft delete)
INSERT INTO policy_configurations
  (company_id, configurable_type, configurable_id, policy_type, config, created_at, updated_at)
VALUES
  (999998, 'App\\Models\\Company', '999998', 'cancellation', '{"hours_before": 48}', NOW(), NOW());

-- Cleanup
DELETE FROM companies WHERE id = 999998;
EOF
```

**Expected Output**:
```
id      company_id  policy_type   is_soft_deleted
XXXXX   999998      cancellation  1
```

**Verification**:
- ✓ Soft delete sets deleted_at timestamp
- ✓ Unique constraint allows duplicate after soft delete

---

### 3.3 Test NULL Constraint Violations

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Create test company
INSERT INTO companies (id, name, created_at, updated_at)
VALUES (999997, 'Test NULL Constraint Company', NOW(), NOW());

-- Test 1: Missing required company_id (should fail)
INSERT INTO notification_configurations
  (configurable_type, configurable_id, event_type, channel, created_at, updated_at)
VALUES
  ('App\\Models\\Company', 999997, 'test_event', 'email', NOW(), NOW());
EOF
```

**Expected Output**:
```
ERROR 1364 (HY000): Field 'company_id' doesn't have a default value
```

**Verification**: Constraint prevents NULL company_id (critical for multi-tenancy)

---

### 3.4 Test Foreign Key Constraint Violations

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test <<'EOF'
-- Test: Invalid company_id reference (should fail)
INSERT INTO notification_configurations
  (company_id, configurable_type, configurable_id, event_type, channel, created_at, updated_at)
VALUES
  (88888888, 'App\\Models\\Company', 999997, 'test_event', 'email', NOW(), NOW());
EOF
```

**Expected Output**:
```
ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails
```

**Verification**: Foreign key prevents orphaned records

---

## Phase 4: Rollback Testing (20 minutes)

### 4.1 Test Individual Migration Rollback

```bash
cd /var/www/api-gateway

# Rollback last migration
php artisan migrate:rollback --database=mysql --env=testing --step=1 -v

# Verify table dropped
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test -e "
SHOW TABLES LIKE 'appointment_modification_stats';
"
```

**Expected Output**:
```
(empty result set - table dropped)
```

**Verification**: Migration rolled back successfully

---

### 4.2 Test Full Rollback and Re-Migration

```bash
# Rollback all 6 migrations
php artisan migrate:rollback --database=mysql --env=testing --step=6 -v

# Verify all tables dropped
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test -e "
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_test'
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

**Expected Output**:
```
(empty result set - all tables dropped)
```

**Verification**: Full rollback successful

---

### 4.3 Re-Apply Migrations

```bash
# Re-run migrations to verify repeatability
php artisan migrate --database=mysql --env=testing --force --step -v

# Verify tables recreated
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_test -e "
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_test'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  )
ORDER BY TABLE_NAME;
"
```

**Expected Output**:
```
TABLE_NAME
appointment_modification_stats
appointment_modifications
callback_escalations
callback_requests
notification_configurations
policy_configurations
```

**Verification**: All 6 tables recreated successfully

---

## Phase 5: Production Migration Execution (20 minutes)

### 5.1 Pre-Deployment Checklist

**STOP** - Do not proceed unless ALL items checked:

- [ ] Test database migrations completed successfully
- [ ] Foreign key constraints validated (all CASCADE)
- [ ] Indexes verified on all tables
- [ ] Unique constraints tested
- [ ] Cascade delete behavior confirmed
- [ ] Soft delete functionality validated
- [ ] Rollback tested and successful
- [ ] Database backup created (see 5.2)
- [ ] Maintenance mode planned
- [ ] Rollback window defined (recommended: 2 hours)

---

### 5.2 Create Production Backup

```bash
# Create timestamped backup
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="/var/backups/mysql/askproai_db_pre_migration_${BACKUP_DATE}.sql"

# Full database backup
mysqldump -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --quick \
  --lock-tables=false \
  askproai_db > "${BACKUP_FILE}"

# Compress backup
gzip "${BACKUP_FILE}"

# Verify backup created
ls -lh "${BACKUP_FILE}.gz"
```

**Expected Output**:
```
-rw-r--r-- 1 root root 45M Oct 2 15:30 /var/backups/mysql/askproai_db_pre_migration_20251002_153000.sql.gz
```

**Verification**: Backup file exists and size is reasonable (>10MB)

**Critical**: Do NOT proceed without valid backup

---

### 5.3 Enable Maintenance Mode (Optional but Recommended)

```bash
cd /var/www/api-gateway

# Enable maintenance mode
php artisan down --render="errors::503" --retry=60

# Verify maintenance mode active
curl -I https://api.askproai.de
```

**Expected Output**:
```
HTTP/2 503
```

**Note**: Only enable if downtime is acceptable during migration

---

### 5.4 Execute Production Migration

```bash
cd /var/www/api-gateway

# Clear cache before migration
php artisan config:clear
php artisan cache:clear

# Run migrations with monitoring
time php artisan migrate --force --step -v 2>&1 | tee /var/log/migration_$(date +%Y%m%d_%H%M%S).log

# Alternative: Run only new migrations
time php artisan migrate --force --step -v \
  --path=database/migrations/2025_10_01_060100_create_notification_configurations_table.php \
  --path=database/migrations/2025_10_01_060201_create_policy_configurations_table.php \
  --path=database/migrations/2025_10_01_060203_create_callback_requests_table.php \
  --path=database/migrations/2025_10_01_060304_create_appointment_modifications_table.php \
  --path=database/migrations/2025_10_01_060305_create_callback_escalations_table.php \
  --path=database/migrations/2025_10_01_060400_create_appointment_modification_stats_table.php \
  2>&1 | tee /var/log/migration_$(date +%Y%m%d_%H%M%S).log
```

**Expected Output**:
```
Migrating: 2025_10_01_060100_create_notification_configurations_table
Migrated:  2025_10_01_060100_create_notification_configurations_table (52.34ms)
Migrating: 2025_10_01_060201_create_policy_configurations_table
Migrated:  2025_10_01_060201_create_policy_configurations_table (48.12ms)
Migrating: 2025_10_01_060203_create_callback_requests_table
Migrated:  2025_10_01_060203_create_callback_requests_table (61.45ms)
Migrating: 2025_10_01_060304_create_appointment_modifications_table
Migrated:  2025_10_01_060304_create_appointment_modifications_table (55.23ms)
Migrating: 2025_10_01_060305_create_callback_escalations_table
Migrated:  2025_10_01_060305_create_callback_escalations_table (49.87ms)
Migrating: 2025_10_01_060400_create_appointment_modification_stats_table
Migrated:  2025_10_01_060400_create_appointment_modification_stats_table (53.91ms)

real    0m0.412s
user    0m0.315s
sys     0m0.062s
```

**Time Estimate**: <1 second for all migrations

**Failure Response**:
1. Note exact error message
2. Do NOT attempt to fix in production
3. Execute rollback procedure (Section 5.7)
4. Investigate in test environment

---

### 5.5 Post-Migration Verification

```bash
# Verify all tables created
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db <<'EOF'
SELECT
  TABLE_NAME,
  ENGINE,
  TABLE_ROWS,
  CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_db'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  )
ORDER BY TABLE_NAME;
EOF
```

**Expected Output**:
```
TABLE_NAME                         ENGINE  TABLE_ROWS  CREATE_TIME
appointment_modification_stats     InnoDB  0           2025-10-02 15:45:12
appointment_modifications          InnoDB  0           2025-10-02 15:45:11
callback_escalations               InnoDB  0           2025-10-02 15:45:11
callback_requests                  InnoDB  0           2025-10-02 15:45:10
notification_configurations        InnoDB  0           2025-10-02 15:45:09
policy_configurations              InnoDB  0           2025-10-02 15:45:10
```

**Verification Checklist**:
- ✓ All 6 tables present
- ✓ ENGINE = InnoDB (supports foreign keys)
- ✓ TABLE_ROWS = 0 (empty tables)
- ✓ CREATE_TIME recent (within last few minutes)

---

### 5.6 Validate Production Foreign Keys

```bash
# Quick foreign key validation
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
SELECT
  TABLE_NAME,
  COUNT(*) AS fk_count
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'askproai_db'
  AND REFERENCED_TABLE_NAME IS NOT NULL
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  )
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;
"
```

**Expected Output**:
```
TABLE_NAME                         fk_count
appointment_modification_stats     2
appointment_modifications          3
callback_escalations               4
callback_requests                  5
notification_configurations        1
policy_configurations              2
```

**Verification**: Each table has expected foreign key count

---

### 5.7 Disable Maintenance Mode

```bash
cd /var/www/api-gateway

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Bring application back up
php artisan up

# Verify application responsive
curl -I https://api.askproai.de
```

**Expected Output**:
```
HTTP/2 200
```

---

### 5.8 Monitor Application Logs

```bash
# Monitor for errors (run in separate terminal)
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Check for database errors
tail -f /var/log/mysql/error.log

# Monitor application performance
htop
```

**Watch For**:
- Database connection errors
- Foreign key constraint violations
- Slow query warnings
- Memory usage spikes

**Monitoring Period**: 30 minutes post-deployment

---

## Phase 6: Rollback Procedures (If Needed)

### 6.1 Emergency Rollback (Production)

**Use Case**: Migration failed or critical errors detected

```bash
cd /var/www/api-gateway

# Enable maintenance mode immediately
php artisan down

# Rollback migrations (adjust --step count based on how many succeeded)
php artisan migrate:rollback --force --step=6 -v

# Verify tables dropped
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
SELECT TABLE_NAME
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

# Clear caches
php artisan config:clear
php artisan cache:clear

# Disable maintenance mode
php artisan up
```

**Expected Outcome**: Application restored to pre-migration state

**Time Estimate**: 2-5 minutes

---

### 6.2 Full Database Restore (Catastrophic Failure)

**Use Case**: Rollback failed or data corruption detected

```bash
# Enable maintenance mode
cd /var/www/api-gateway
php artisan down

# Stop application services
systemctl stop php8.3-fpm
systemctl stop nginx

# Restore from backup
BACKUP_FILE="/var/backups/mysql/askproai_db_pre_migration_20251002_153000.sql.gz"

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

**Time Estimate**: 10-20 minutes (depends on backup size)

---

## Phase 7: Post-Deployment Validation (15 minutes)

### 7.1 Multi-Tenant Isolation Test

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db <<'EOF'
-- Get first 2 company IDs
SELECT id, name FROM companies LIMIT 2;

-- For each company, verify data isolation
-- (This is a READ-ONLY query to verify structure, not creating test data)

-- Example: Check if company_id exists and is indexed
EXPLAIN SELECT * FROM notification_configurations WHERE company_id = 1;
EXPLAIN SELECT * FROM policy_configurations WHERE company_id = 1;
EXPLAIN SELECT * FROM callback_requests WHERE company_id = 1;
EXPLAIN SELECT * FROM appointment_modifications WHERE company_id = 1;
EXPLAIN SELECT * FROM callback_escalations WHERE company_id = 1;
EXPLAIN SELECT * FROM appointment_modification_stats WHERE company_id = 1;
EOF
```

**Expected Output** (for each EXPLAIN):
```
type: ref
possible_keys: idx_company, notif_config_company_idx (or similar)
key: idx_company (or similar)
rows: 0-X
Extra: Using where
```

**Verification**:
- ✓ type = ref (index used, not full table scan)
- ✓ key shows company_id index is used
- ✓ No "Using filesort" in Extra (good performance)

---

### 7.2 Index Performance Test

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db <<'EOF'
-- Test composite index performance
EXPLAIN SELECT *
FROM notification_configurations
WHERE company_id = 1
  AND event_type = 'booking_confirmed'
  AND is_enabled = 1;

-- Test polymorphic index
EXPLAIN SELECT *
FROM policy_configurations
WHERE company_id = 1
  AND configurable_type = 'App\\Models\\Service'
  AND configurable_id = '123';

-- Test rolling window stats lookup
EXPLAIN SELECT *
FROM appointment_modification_stats
WHERE company_id = 1
  AND customer_id = 456
  AND stat_type = 'cancellation_count'
  AND period_start >= CURDATE() - INTERVAL 30 DAY;
EOF
```

**Verification**: All queries use indexes (no full table scans)

---

### 7.3 Foreign Key Integrity Verification

```bash
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db <<'EOF'
-- Check for orphaned records (should be 0)
SELECT 'notification_configurations' AS table_name, COUNT(*) AS orphaned_count
FROM notification_configurations nc
LEFT JOIN companies c ON nc.company_id = c.id
WHERE c.id IS NULL

UNION ALL

SELECT 'policy_configurations', COUNT(*)
FROM policy_configurations pc
LEFT JOIN companies c ON pc.company_id = c.id
WHERE c.id IS NULL

UNION ALL

SELECT 'callback_requests', COUNT(*)
FROM callback_requests cr
LEFT JOIN companies c ON cr.company_id = c.id
WHERE c.id IS NULL

UNION ALL

SELECT 'appointment_modifications', COUNT(*)
FROM appointment_modifications am
LEFT JOIN companies c ON am.company_id = c.id
WHERE c.id IS NULL

UNION ALL

SELECT 'callback_escalations', COUNT(*)
FROM callback_escalations ce
LEFT JOIN companies c ON ce.company_id = c.id
WHERE c.id IS NULL

UNION ALL

SELECT 'appointment_modification_stats', COUNT(*)
FROM appointment_modification_stats ams
LEFT JOIN companies c ON ams.company_id = c.id
WHERE c.id IS NULL;
EOF
```

**Expected Output**:
```
table_name                         orphaned_count
notification_configurations        0
policy_configurations              0
callback_requests                  0
appointment_modifications          0
callback_escalations               0
appointment_modification_stats     0
```

**Critical**: If orphaned_count > 0 for any table → **DATA INTEGRITY ISSUE**

---

## Post-Migration Checklist

**Complete this checklist after migration:**

### Database Structure
- [ ] All 6 tables created successfully
- [ ] Foreign key constraints validated (CASCADE on delete)
- [ ] Indexes created on company_id columns
- [ ] Unique constraints tested and working
- [ ] Polymorphic indexes created
- [ ] No orphaned records detected

### Functionality
- [ ] Cascade delete behavior confirmed
- [ ] Soft delete functionality validated (policy_configurations)
- [ ] NULL constraints prevent missing company_id
- [ ] Foreign keys prevent invalid references
- [ ] Multi-tenant isolation verified

### Performance
- [ ] All queries use indexes (no full table scans)
- [ ] Composite indexes optimize lookup patterns
- [ ] Rollback tested and functional

### Safety
- [ ] Database backup created and verified
- [ ] Rollback procedures documented and tested
- [ ] Monitoring active for 30 minutes post-deployment
- [ ] No errors in application logs
- [ ] No errors in database logs

### Documentation
- [ ] Migration log saved: /var/log/migration_YYYYMMDD_HHMMSS.log
- [ ] Backup location documented: /var/backups/mysql/askproai_db_pre_migration_*.sql.gz
- [ ] Any issues encountered documented
- [ ] Team notified of successful deployment

---

## Common Issues and Solutions

### Issue 1: Foreign Key Constraint Fails During Migration

**Symptom**:
```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

**Cause**: Parent table doesn't exist or column type mismatch

**Solution**:
```bash
# Check parent table exists
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SHOW TABLES LIKE 'companies';"

# Check column types match
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
DESCRIBE companies;
DESCRIBE notification_configurations;
"

# Verify InnoDB engine (required for foreign keys)
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "
SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'askproai_db';
"
```

---

### Issue 2: Duplicate Key Error on Unique Constraint

**Symptom**:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

**Cause**: Duplicate data violates unique constraint

**Solution**:
```bash
# This shouldn't happen on fresh tables, but if it does:
# Identify duplicate records
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db <<'EOF'
SELECT
  company_id,
  configurable_type,
  configurable_id,
  event_type,
  channel,
  COUNT(*) AS duplicate_count
FROM notification_configurations
GROUP BY company_id, configurable_type, configurable_id, event_type, channel
HAVING COUNT(*) > 1;
EOF

# Remove duplicates (keep first, delete rest) - ONLY IF NEEDED
# This should NOT be needed on fresh migrations
```

---

### Issue 3: Migration Timeout

**Symptom**: Migration hangs or times out

**Cause**: Large table or locked tables

**Solution**:
```bash
# Check for locked tables
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SHOW OPEN TABLES WHERE In_use > 0;"

# Check running processes
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SHOW PROCESSLIST;"

# Kill blocking process (if safe)
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "KILL <process_id>;"

# Increase PHP timeout (if needed)
php -d max_execution_time=300 artisan migrate --force
```

---

### Issue 4: Table Already Exists

**Symptom**:
```
SQLSTATE[42S01]: Base table or view already exists
```

**Cause**: Table was created in previous failed migration attempt

**Solution**:
```bash
# Check if table exists
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SHOW TABLES LIKE 'notification_configurations';"

# If exists, check migrations table
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "SELECT * FROM migrations WHERE migration LIKE '%notification%';"

# Option 1: Drop table manually and re-run
mysql -u askproai_user -p'askproai_secure_pass_2024' -h 127.0.0.1 askproai_db -e "DROP TABLE IF EXISTS notification_configurations;"

# Option 2: Skip migration if table exists (already handled by Schema::hasTable() in migrations)
# No action needed - migrations have guards
```

---

## Performance Benchmarks

**Expected Migration Times** (empty tables):
- notification_configurations: ~50ms
- policy_configurations: ~50ms
- callback_requests: ~60ms
- appointment_modifications: ~55ms
- callback_escalations: ~50ms
- appointment_modification_stats: ~55ms

**Total Time**: <500ms

**Index Creation Time**: Included in migration time (negligible for empty tables)

**Rollback Time**: ~200ms (faster than migration)

---

## Security Considerations

### Multi-Tenant Data Isolation

1. **Foreign Key CASCADE**: All tables use CASCADE on delete to prevent orphaned records when companies are deleted
2. **Company ID Required**: All tables enforce company_id NOT NULL
3. **Unique Constraints**: All unique constraints include company_id to prevent cross-tenant conflicts
4. **Indexes**: All primary indexes start with company_id for query performance and isolation

### Access Control

1. **Database Credentials**: Stored in .env file (not committed to version control)
2. **Backup Security**: Backups contain production data - store securely
3. **Migration Logs**: May contain sensitive data - review before sharing

---

## Next Steps After Deployment

### Immediate (Day 1)
1. Monitor application logs for 24 hours
2. Watch database performance metrics
3. Verify no foreign key constraint violations in production usage
4. Document any issues encountered

### Short-term (Week 1)
1. Implement model observers (Phase 3 already complete)
2. Create policy enforcement service layer
3. Build notification configuration UI
4. Add monitoring/alerting for constraint violations

### Medium-term (Month 1)
1. Populate default notification configurations for existing companies
2. Create policy configuration templates
3. Implement callback escalation workflow
4. Build materialized stats calculation job
5. Performance tune based on production query patterns

---

## Contact and Escalation

**Database Issues**: Check MySQL error log: /var/log/mysql/error.log
**Application Issues**: Check Laravel log: /var/www/api-gateway/storage/logs/laravel.log
**Migration Support**: Reference this document for troubleshooting

**Emergency Rollback Decision Tree**:
1. Minor errors, no data loss → Monitor, fix in next deployment
2. Moderate errors, affecting some users → Rollback migrations (Section 6.1)
3. Critical errors, data corruption → Full database restore (Section 6.2)

---

**Document End**

*Generated: 2025-10-02*
*Version: 1.0*
*Author: Backend Architect*
