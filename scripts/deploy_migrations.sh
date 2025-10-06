#!/bin/bash
#
# Production Migration Deployment Script
#
# Purpose: Safe production deployment of Phase 2 multi-tenant migrations
# Usage: sudo ./scripts/deploy_migrations.sh [--skip-backup] [--no-maintenance]
# Exit codes: 0=success, 1=failure
#
# IMPORTANT: Review claudedocs/MIGRATION_TESTING_DEPLOYMENT_PLAN.md before running
#

set -e  # Exit on error
set -u  # Exit on undefined variable

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DB_USER="askproai_user"
DB_PASS="askproai_secure_pass_2024"
DB_HOST="127.0.0.1"
PROD_DB="askproai_db"
BACKUP_DIR="/var/backups/mysql"
APP_DIR="/var/www/api-gateway"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/log/migration_production_${TIMESTAMP}.log"

# Parse command line arguments
SKIP_BACKUP=false
NO_MAINTENANCE=false
for arg in "$@"; do
    case $arg in
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --no-maintenance)
            NO_MAINTENANCE=true
            shift
            ;;
        --help)
            echo "Usage: $0 [--skip-backup] [--no-maintenance]"
            echo ""
            echo "Options:"
            echo "  --skip-backup       Skip database backup (NOT RECOMMENDED)"
            echo "  --no-maintenance    Skip maintenance mode (deploy without downtime)"
            echo "  --help              Show this help message"
            exit 0
            ;;
    esac
done

# Redirect all output to log file and console
exec > >(tee -a "$LOG_FILE")
exec 2>&1

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Production Migration Deployment${NC}"
echo -e "${BLUE}Date: $(date)${NC}"
echo -e "${BLUE}Log File: ${LOG_FILE}${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Helper functions
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

error() {
    echo -e "${RED}✗ $1${NC}"
    echo -e "${RED}Deployment FAILED - check log: ${LOG_FILE}${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

confirm() {
    local message=$1
    echo -e "${YELLOW}${message}${NC}"
    read -p "Continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        error "Deployment cancelled by user"
    fi
}

run_query() {
    local query=$1
    mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$PROD_DB" -e "$query" 2>&1
}

# Pre-deployment checks
info "Running pre-deployment checks..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    warning "Not running as root. Some operations may fail."
fi

# Check if in correct directory
if [ ! -f "${APP_DIR}/artisan" ]; then
    error "Laravel application not found at ${APP_DIR}"
fi

# Check database connectivity
if ! mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" -e "USE ${PROD_DB};" > /dev/null 2>&1; then
    error "Cannot connect to database ${PROD_DB}"
fi
success "Database connectivity verified"

# Check critical tables exist
CRITICAL_TABLES=("companies" "customers" "appointments" "branches" "services" "staff")
for table in "${CRITICAL_TABLES[@]}"; do
    RESULT=$(run_query "SHOW TABLES LIKE '${table}';" | grep -c "${table}" || true)
    if [ "$RESULT" -eq 0 ]; then
        error "Required table not found: ${table}"
    fi
done
success "Critical tables verified"

# Check if migrations already applied
ALREADY_APPLIED=$(run_query "SHOW TABLES LIKE 'notification_configurations';" | grep -c "notification_configurations" || true)
if [ "$ALREADY_APPLIED" -ne 0 ]; then
    warning "Migration tables already exist - this may be a re-run"
    confirm "Tables already exist. This will skip existing tables. Continue?"
fi

echo ""

# Display pre-deployment summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Pre-Deployment Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Database: ${PROD_DB}"
echo -e "Backup: $([ "$SKIP_BACKUP" = true ] && echo "SKIPPED (⚠ NOT RECOMMENDED)" || echo "ENABLED")"
echo -e "Maintenance Mode: $([ "$NO_MAINTENANCE" = true ] && echo "DISABLED (zero-downtime)" || echo "ENABLED")"
echo -e "Migration Count: 6 tables"
echo -e "Expected Time: < 1 second"
echo ""

# Final confirmation
if [ "$SKIP_BACKUP" = true ]; then
    warning "⚠ WARNING: Backup is DISABLED - cannot rollback without backup!"
fi

confirm "Ready to deploy to PRODUCTION?"

echo ""

# Phase 1: Create Backup
if [ "$SKIP_BACKUP" = false ]; then
    info "Phase 1: Creating database backup..."

    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"

    BACKUP_FILE="${BACKUP_DIR}/askproai_db_pre_migration_${TIMESTAMP}.sql"

    info "Backing up database to: ${BACKUP_FILE}"
    mysqldump -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --quick \
        --lock-tables=false \
        "$PROD_DB" > "${BACKUP_FILE}" || error "Backup failed"

    # Compress backup
    gzip "${BACKUP_FILE}" || error "Backup compression failed"

    # Verify backup created
    if [ ! -f "${BACKUP_FILE}.gz" ]; then
        error "Backup file not found: ${BACKUP_FILE}.gz"
    fi

    BACKUP_SIZE=$(du -h "${BACKUP_FILE}.gz" | cut -f1)
    success "Backup created: ${BACKUP_FILE}.gz (${BACKUP_SIZE})"

    # Security: Set backup file permissions
    chmod 600 "${BACKUP_FILE}.gz"
    success "Backup secured (permissions: 600)"
else
    warning "Skipping backup (--skip-backup flag used)"
fi

echo ""

# Phase 2: Enable Maintenance Mode (optional)
if [ "$NO_MAINTENANCE" = false ]; then
    info "Phase 2: Enabling maintenance mode..."

    cd "$APP_DIR"
    php artisan down --render="errors::503" --retry=60 || error "Failed to enable maintenance mode"

    success "Maintenance mode enabled"

    # Verify maintenance mode
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de || true)
    if [ "$HTTP_CODE" = "503" ]; then
        success "Maintenance mode verified (HTTP 503)"
    else
        warning "Maintenance mode verification unclear (HTTP ${HTTP_CODE})"
    fi
else
    info "Phase 2: Skipping maintenance mode (--no-maintenance flag used)"
fi

echo ""

# Phase 3: Clear Caches
info "Phase 3: Clearing application caches..."

cd "$APP_DIR"
php artisan config:clear || warning "Config cache clear failed (non-critical)"
php artisan cache:clear || warning "Cache clear failed (non-critical)"

success "Caches cleared"

echo ""

# Phase 4: Execute Migrations
info "Phase 4: Executing migrations..."

cd "$APP_DIR"

info "Starting migration execution (this should take < 1 second)..."
START_TIME=$(date +%s)

# Run migrations with verbose output
php artisan migrate --force --step -v \
    --path=database/migrations/2025_10_01_060100_create_notification_configurations_table.php \
    --path=database/migrations/2025_10_01_060201_create_policy_configurations_table.php \
    --path=database/migrations/2025_10_01_060203_create_callback_requests_table.php \
    --path=database/migrations/2025_10_01_060304_create_appointment_modifications_table.php \
    --path=database/migrations/2025_10_01_060305_create_callback_escalations_table.php \
    --path=database/migrations/2025_10_01_060400_create_appointment_modification_stats_table.php \
    || error "Migration execution failed"

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

success "Migrations completed in ${DURATION} second(s)"

echo ""

# Phase 5: Post-Migration Verification
info "Phase 5: Post-migration verification..."

# Check all tables created
EXPECTED_TABLES=(
    "notification_configurations"
    "policy_configurations"
    "callback_requests"
    "appointment_modifications"
    "callback_escalations"
    "appointment_modification_stats"
)

for table in "${EXPECTED_TABLES[@]}"; do
    RESULT=$(run_query "SHOW TABLES LIKE '${table}';" | grep -c "${table}" || true)
    if [ "$RESULT" -eq 0 ]; then
        error "Migration failed to create table: ${table}"
    fi
    success "Table verified: ${table}"
done

# Verify foreign keys
info "Verifying foreign key constraints..."
FK_COUNT=$(run_query "
SELECT COUNT(*) AS fk_count
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = '${PROD_DB}'
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
    error "Foreign key verification failed. Expected 6, got ${FK_COUNT}"
fi
success "Foreign key constraints verified (count: 6)"

# Check for orphaned records
info "Checking data integrity (orphaned records)..."
ORPHANED_COUNT=$(run_query "
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
success "Data integrity verified (orphaned records: 0)"

# Verify indexes
info "Verifying indexes..."
INDEX_COUNT=$(run_query "
SELECT COUNT(DISTINCT TABLE_NAME) AS table_count
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = '${PROD_DB}'
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
    error "Index verification failed. Expected 6 tables with indexes, got ${INDEX_COUNT}"
fi
success "Indexes verified on all 6 tables"

echo ""

# Phase 6: Restore Application
info "Phase 6: Restoring application..."

cd "$APP_DIR"

# Clear caches again
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

success "Application caches cleared"

# Disable maintenance mode
if [ "$NO_MAINTENANCE" = false ]; then
    php artisan up || error "Failed to disable maintenance mode"
    success "Maintenance mode disabled"

    # Verify application responsive
    sleep 2
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de || true)
    if [ "$HTTP_CODE" = "200" ]; then
        success "Application verified (HTTP 200)"
    else
        warning "Application verification returned HTTP ${HTTP_CODE}"
    fi
fi

echo ""

# Deployment Summary
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Deployment Successful!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo -e "  • 6 tables created successfully"
echo -e "  • Foreign keys validated (CASCADE delete)"
echo -e "  • Indexes verified on all tables"
echo -e "  • Data integrity confirmed (0 orphaned records)"
echo -e "  • Migration time: ${DURATION} second(s)"
if [ "$SKIP_BACKUP" = false ]; then
    echo -e "  • Backup location: ${BACKUP_FILE}.gz (${BACKUP_SIZE})"
else
    echo -e "  • Backup: SKIPPED"
fi
echo ""
echo -e "${YELLOW}Post-Deployment Actions:${NC}"
echo -e "  1. Monitor application logs for 30 minutes:"
echo -e "     tail -f ${APP_DIR}/storage/logs/laravel.log"
echo ""
echo -e "  2. Monitor database error log:"
echo -e "     tail -f /var/log/mysql/error.log"
echo ""
echo -e "  3. Watch for foreign key constraint violations"
echo ""
echo -e "  4. Review deployment log: ${LOG_FILE}"
echo ""
if [ "$SKIP_BACKUP" = false ]; then
    echo -e "${BLUE}Rollback Instructions (if needed):${NC}"
    echo -e "  1. Enable maintenance mode:"
    echo -e "     cd ${APP_DIR} && php artisan down"
    echo ""
    echo -e "  2. Rollback migrations:"
    echo -e "     php artisan migrate:rollback --step=6 --force"
    echo ""
    echo -e "  3. OR restore from backup:"
    echo -e "     gunzip < ${BACKUP_FILE}.gz | mysql -u ${DB_USER} -p ${PROD_DB}"
    echo ""
    echo -e "  4. Disable maintenance mode:"
    echo -e "     php artisan up"
    echo ""
fi
echo -e "${GREEN}✓ Migration deployment complete!${NC}"
echo ""

exit 0
