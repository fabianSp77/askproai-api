#!/bin/bash
#
# Migration Testing Script - Multi-Tenant Database Schema
#
# Purpose: Automated testing of Phase 2 migrations in test database
# Usage: ./scripts/test_migrations.sh
# Exit codes: 0=success, 1=failure
#

set -e  # Exit on error
set -u  # Exit on undefined variable

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Database credentials
DB_USER="askproai_user"
DB_PASS="askproai_secure_pass_2024"
DB_HOST="127.0.0.1"
TEST_DB="askproai_test"
PROD_DB="askproai_db"

# Logging
LOG_FILE="/var/log/migration_test_$(date +%Y%m%d_%H%M%S).log"
exec > >(tee -a "$LOG_FILE")
exec 2>&1

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Migration Testing Script - Started${NC}"
echo -e "${BLUE}Date: $(date)${NC}"
echo -e "${BLUE}Log File: ${LOG_FILE}${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function: Print success message
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Function: Print error message and exit
error() {
    echo -e "${RED}✗ $1${NC}"
    exit 1
}

# Function: Print warning message
warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Function: Print info message
info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Function: Run MySQL query
run_query() {
    local database=$1
    local query=$2
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$database" -e "$query" 2>&1
}

# Function: Check command exists
check_command() {
    if ! command -v "$1" &> /dev/null; then
        error "Required command not found: $1"
    fi
}

# Check prerequisites
info "Checking prerequisites..."
check_command mysql
check_command php
success "Prerequisites check passed"
echo ""

# Phase 1: Setup Test Database
info "Phase 1: Setting up test database..."

# Create test database
info "Creating test database: ${TEST_DB}..."
mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" <<EOF
CREATE DATABASE IF NOT EXISTS ${TEST_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
success "Test database created"

# Clone production schema
info "Cloning production schema..."
mysqldump -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" \
    --no-data \
    --skip-add-drop-table \
    --skip-comments \
    "${PROD_DB}" > /tmp/production_schema.sql

mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "${TEST_DB}" < /tmp/production_schema.sql
rm -f /tmp/production_schema.sql
success "Production schema cloned to test database"

# Verify critical tables exist
info "Verifying critical dependencies..."
TABLES=("companies" "customers" "appointments" "branches" "services" "staff")
for table in "${TABLES[@]}"; do
    RESULT=$(run_query "${TEST_DB}" "SHOW TABLES LIKE '${table}';" | grep -c "${table}" || true)
    if [ "$RESULT" -eq 0 ]; then
        error "Required table not found: ${table}"
    fi
    success "Table exists: ${table}"
done

echo ""

# Phase 2: Run Migrations
info "Phase 2: Executing migrations..."

cd /var/www/api-gateway

# Create test environment config
cat > .env.testing <<EOF
DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_DATABASE=${TEST_DB}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
MAIL_MAILER=log
QUEUE_CONNECTION=sync
CACHE_DRIVER=array
SESSION_DRIVER=array
EOF

chmod 600 .env.testing

# Run migrations
info "Running migrations (this may take a few seconds)..."
php artisan migrate --database=mysql --env=testing --force --step -v || error "Migration failed"
success "Migrations completed successfully"

echo ""

# Phase 3: Validate Schema
info "Phase 3: Validating database schema..."

# Check tables created
EXPECTED_TABLES=(
    "notification_configurations"
    "policy_configurations"
    "callback_requests"
    "appointment_modifications"
    "callback_escalations"
    "appointment_modification_stats"
)

for table in "${EXPECTED_TABLES[@]}"; do
    RESULT=$(run_query "${TEST_DB}" "SHOW TABLES LIKE '${table}';" | grep -c "${table}" || true)
    if [ "$RESULT" -eq 0 ]; then
        error "Migration failed to create table: ${table}"
    fi
    success "Table created: ${table}"
done

# Validate foreign keys
info "Validating foreign key constraints..."
FK_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(*) AS fk_count
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = '${TEST_DB}'
  AND COLUMN_NAME = 'company_id'
  AND REFERENCED_TABLE_NAME = 'companies'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );" | tail -1)

if [ "$FK_COUNT" -ne 6 ]; then
    error "Foreign key validation failed. Expected 6, got ${FK_COUNT}"
fi
success "All 6 foreign keys validated"

# Validate indexes
info "Validating indexes on company_id..."
INDEX_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(DISTINCT TABLE_NAME) AS table_count
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = '${TEST_DB}'
  AND COLUMN_NAME = 'company_id'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );" | tail -1)

if [ "$INDEX_COUNT" -ne 6 ]; then
    error "Index validation failed. Expected 6 tables with company_id indexes, got ${INDEX_COUNT}"
fi
success "Indexes validated on all 6 tables"

echo ""

# Phase 4: Test Cascade Delete
info "Phase 4: Testing cascade delete behavior..."

# Create test company
run_query "${TEST_DB}" "
INSERT INTO companies (id, name, created_at, updated_at)
VALUES (999999, 'Test Company for Deletion', NOW(), NOW());" > /dev/null

# Create test records
run_query "${TEST_DB}" "
INSERT INTO notification_configurations
  (company_id, configurable_type, configurable_id, event_type, channel, created_at, updated_at)
VALUES
  (999999, 'App\\\\Models\\\\Company', 999999, 'test_event', 'email', NOW(), NOW());" > /dev/null

run_query "${TEST_DB}" "
INSERT INTO policy_configurations
  (company_id, configurable_type, configurable_id, policy_type, config, created_at, updated_at)
VALUES
  (999999, 'App\\\\Models\\\\Company', '999999', 'cancellation', '{\"hours_before\": 24}', NOW(), NOW());" > /dev/null

# Count before delete
BEFORE_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(*) FROM notification_configurations WHERE company_id = 999999
UNION ALL
SELECT COUNT(*) FROM policy_configurations WHERE company_id = 999999;" | awk '{sum+=$1} END {print sum}')

if [ "$BEFORE_COUNT" -ne 2 ]; then
    error "Test data creation failed. Expected 2 records, got ${BEFORE_COUNT}"
fi
success "Test records created (count: ${BEFORE_COUNT})"

# Delete company (should cascade)
run_query "${TEST_DB}" "DELETE FROM companies WHERE id = 999999;" > /dev/null

# Count after delete
AFTER_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(*) FROM notification_configurations WHERE company_id = 999999
UNION ALL
SELECT COUNT(*) FROM policy_configurations WHERE company_id = 999999;" | awk '{sum+=$1} END {print sum}')

if [ "$AFTER_COUNT" -ne 0 ]; then
    error "Cascade delete failed. Expected 0 records, got ${AFTER_COUNT}"
fi
success "Cascade delete works correctly (orphaned records: ${AFTER_COUNT})"

echo ""

# Phase 5: Test Rollback
info "Phase 5: Testing migration rollback..."

# Rollback last migration
php artisan migrate:rollback --database=mysql --env=testing --step=1 --force > /dev/null || error "Rollback failed"
success "Rollback step 1 completed"

# Verify table dropped
STATS_EXISTS=$(run_query "${TEST_DB}" "SHOW TABLES LIKE 'appointment_modification_stats';" | grep -c "appointment_modification_stats" || true)
if [ "$STATS_EXISTS" -ne 0 ]; then
    error "Rollback failed - table still exists: appointment_modification_stats"
fi
success "Table dropped successfully: appointment_modification_stats"

# Rollback remaining migrations
php artisan migrate:rollback --database=mysql --env=testing --step=5 --force > /dev/null || error "Full rollback failed"
success "Full rollback completed"

# Verify all tables dropped
REMAINING_TABLES=$(run_query "${TEST_DB}" "
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '${TEST_DB}'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );" | tail -1)

if [ "$REMAINING_TABLES" -ne 0 ]; then
    error "Rollback incomplete - ${REMAINING_TABLES} tables remain"
fi
success "All migration tables dropped"

# Re-apply migrations
info "Re-applying migrations to test repeatability..."
php artisan migrate --database=mysql --env=testing --force --step -v > /dev/null || error "Re-migration failed"
success "Migrations re-applied successfully"

echo ""

# Phase 6: Final Verification
info "Phase 6: Final verification..."

# Check all tables exist again
FINAL_TABLE_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '${TEST_DB}'
  AND TABLE_NAME IN (
    'notification_configurations',
    'policy_configurations',
    'callback_requests',
    'appointment_modifications',
    'callback_escalations',
    'appointment_modification_stats'
  );" | tail -1)

if [ "$FINAL_TABLE_COUNT" -ne 6 ]; then
    error "Final verification failed. Expected 6 tables, got ${FINAL_TABLE_COUNT}"
fi
success "Final table count verified: ${FINAL_TABLE_COUNT}"

# Check for orphaned records
ORPHANED_COUNT=$(run_query "${TEST_DB}" "
SELECT COUNT(*) FROM notification_configurations nc
LEFT JOIN companies c ON nc.company_id = c.id
WHERE c.id IS NULL
UNION ALL
SELECT COUNT(*) FROM policy_configurations pc
LEFT JOIN companies c ON pc.company_id = c.id
WHERE c.id IS NULL
UNION ALL
SELECT COUNT(*) FROM callback_requests cr
LEFT JOIN companies c ON cr.company_id = c.id
WHERE c.id IS NULL
UNION ALL
SELECT COUNT(*) FROM appointment_modifications am
LEFT JOIN companies c ON am.company_id = c.id
WHERE c.id IS NULL
UNION ALL
SELECT COUNT(*) FROM callback_escalations ce
LEFT JOIN companies c ON ce.company_id = c.id
WHERE c.id IS NULL
UNION ALL
SELECT COUNT(*) FROM appointment_modification_stats ams
LEFT JOIN companies c ON ams.company_id = c.id
WHERE c.id IS NULL;" | awk '{sum+=$1} END {print sum}')

if [ "$ORPHANED_COUNT" -ne 0 ]; then
    error "Data integrity check failed. Orphaned records found: ${ORPHANED_COUNT}"
fi
success "Data integrity verified (orphaned records: ${ORPHANED_COUNT})"

echo ""

# Cleanup
info "Cleanup: Removing test environment configuration..."
rm -f /var/www/api-gateway/.env.testing
success "Test configuration removed"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ All Tests Passed!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo -e "  • Test database created and populated"
echo -e "  • 6 migrations executed successfully"
echo -e "  • Foreign keys validated (CASCADE delete)"
echo -e "  • Indexes verified on all tables"
echo -e "  • Cascade delete behavior confirmed"
echo -e "  • Rollback and re-migration tested"
echo -e "  • Data integrity verified"
echo ""
echo -e "${GREEN}✓ Migrations are ready for production deployment!${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo -e "  1. Review deployment plan: claudedocs/MIGRATION_TESTING_DEPLOYMENT_PLAN.md"
echo -e "  2. Create production backup: Section 5.2"
echo -e "  3. Execute production migration: Section 5.4"
echo -e "  4. Monitor application for 30 minutes post-deployment"
echo ""
echo -e "${BLUE}Log File: ${LOG_FILE}${NC}"
echo ""

exit 0
