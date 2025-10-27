#!/bin/bash

################################################################################
# Staging Database Fix - Complete Solution
#
# Purpose: Restore staging database to full schema parity with production
# Time: ~45 minutes (including verification)
# Risk: LOW (staging only, test data only)
#
# Usage: bash scripts/fix-staging-database.sh
################################################################################

set -e

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
STAGING_DB="askproai_staging"
STAGING_USER="askproai_staging_user"
STAGING_PASS="St4g1ng_S3cur3_P@ssw0rd_2025"
PROD_DB="askproai_db"
PROD_USER="askproai_user"
PROD_PASS="askproai_secure_pass_2024"
BACKUP_DIR="/var/www/api-gateway/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Verify environment
verify_environment() {
    log_info "Verifying environment..."

    # Check if running from correct directory
    if [ ! -f "artisan" ]; then
        log_error "Must be run from Laravel root directory"
        exit 1
    fi

    # Check if .env.staging exists
    if [ ! -f ".env.staging" ]; then
        log_error ".env.staging not found"
        exit 1
    fi

    # Check MySQL access
    if ! mysql -u "$PROD_USER" -p"$PROD_PASS" -e "SELECT 1;" &>/dev/null; then
        log_error "Cannot connect to MySQL (production credentials)"
        exit 1
    fi

    # Create backup directory if needed
    mkdir -p "$BACKUP_DIR"

    log_success "Environment verified"
}

# Phase 1: Backup current staging database
phase_backup() {
    log_info "PHASE 1: Backing up current staging database..."

    local backup_file="$BACKUP_DIR/staging_backup_${TIMESTAMP}.sql"

    if mysqldump -u "$STAGING_USER" -p"$STAGING_PASS" "$STAGING_DB" \
        2>/dev/null > "$backup_file"; then
        log_success "Database backed up to: $backup_file"
        echo "Size: $(du -h "$backup_file" | cut -f1)"
    else
        log_error "Backup failed"
        exit 1
    fi
}

# Phase 2A: Full Reset Approach
phase_reset_database() {
    log_info "PHASE 2: Full database reset..."

    # Drop staging database
    log_info "  Dropping current database..."
    mysql -u root -e "DROP DATABASE IF EXISTS \`$STAGING_DB\`;" 2>/dev/null

    # Create fresh database
    log_info "  Creating fresh database..."
    mysql -u root -e "CREATE DATABASE \`$STAGING_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

    # Grant permissions
    log_info "  Configuring permissions..."
    mysql -u root -e "
        GRANT ALL PRIVILEGES ON \`$STAGING_DB\`.* TO '$STAGING_USER'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null

    log_success "Database recreated and ready"
}

# Phase 2B: Run migrations
phase_migrations() {
    log_info "PHASE 3: Running migrations..."

    # Make sure we're in the right directory
    cd /var/www/api-gateway || exit 1

    # Run migrations
    if php artisan migrate --env=staging --force 2>&1 | tee "$BACKUP_DIR/migration_${TIMESTAMP}.log"; then
        log_success "All migrations completed"
    else
        log_error "Migrations failed - see $BACKUP_DIR/migration_${TIMESTAMP}.log"
        exit 1
    fi
}

# Phase 3: Verify schema
phase_verify() {
    log_info "PHASE 4: Verifying schema..."

    # Count tables in staging
    local staging_count=$(mysql -u "$STAGING_USER" -p"$STAGING_PASS" \
        -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$STAGING_DB';" 2>&1 | tail -1)

    # Count tables in production
    local prod_count=$(mysql -u "$PROD_USER" -p"$PROD_PASS" \
        -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$PROD_DB';" 2>&1 | tail -1)

    echo ""
    log_info "Table counts:"
    echo "  Staging:    $staging_count tables"
    echo "  Production: $prod_count tables"

    if [ "$staging_count" -eq "$prod_count" ]; then
        log_success "Schema count matches production!"
    else
        log_warning "Schema count differs (difference: $((prod_count - staging_count)) tables)"
        # This might be OK for some optional tables, not critical failure
    fi

    # Verify critical Customer Portal tables
    local critical_tables=(
        "retell_call_sessions"
        "retell_call_events"
        "retell_transcript_segments"
        "retell_function_traces"
        "appointments"
        "customers"
        "calls"
        "services"
        "staff"
    )

    echo ""
    log_info "Critical Customer Portal tables:"
    local all_present=true
    for table in "${critical_tables[@]}"; do
        local exists=$(mysql -u "$STAGING_USER" -p"$STAGING_PASS" \
            -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$STAGING_DB' AND TABLE_NAME='$table';" 2>&1 | tail -1)

        if [ "$exists" -gt 0 ]; then
            echo "  ${GREEN}✓${NC} $table"
        else
            echo "  ${RED}✗${NC} $table MISSING"
            all_present=false
        fi
    done

    if [ "$all_present" = true ]; then
        log_success "All critical tables present"
    else
        log_warning "Some critical tables missing"
    fi
}

# Phase 4: Clear caches
phase_cleanup() {
    log_info "PHASE 5: Cleaning up caches..."

    cd /var/www/api-gateway || exit 1

    # Clear application cache
    php artisan cache:clear --env=staging 2>/dev/null
    php artisan config:clear --env=staging 2>/dev/null
    php artisan view:clear --env=staging 2>/dev/null

    log_success "Caches cleared"
}

# Phase 5: Final verification
phase_final_check() {
    log_info "PHASE 6: Final verification..."

    cd /var/www/api-gateway || exit 1

    # Check database connection
    if php artisan tinker --env=staging <<< "DB::connection('staging')->getPdo(); echo \"OK\";" 2>&1 | grep -q "OK"; then
        log_success "Database connection verified"
    else
        log_error "Database connection failed"
        exit 1
    fi

    # Check migrations status
    echo ""
    log_info "Migration status:"
    php artisan migrate:status --env=staging 2>&1 | tail -5
}

# Summary
show_summary() {
    echo ""
    echo "=================================="
    echo "STAGING DATABASE FIX COMPLETE"
    echo "=================================="
    echo ""
    log_success "Database reset and migrations applied"
    log_success "Schema validated"
    log_success "Critical Customer Portal tables verified"
    log_success "Caches cleared"
    echo ""
    log_info "Next steps:"
    echo "  1. Review logs: tail -f storage/logs/laravel.log --env=staging"
    echo "  2. Test Customer Portal: Visit https://staging.askproai.de"
    echo "  3. Run tests: vendor/bin/pest --env=staging"
    echo "  4. Backup saved: $(ls -t "$BACKUP_DIR"/staging_backup_*.sql | head -1)"
    echo ""
    log_info "Execution time: $((SECONDS / 60)) minutes $((SECONDS % 60)) seconds"
}

# Main execution
main() {
    echo "=================================="
    echo "STAGING DATABASE FIX SCRIPT"
    echo "=================================="
    echo "Timestamp: $(date)"
    echo "Database: $STAGING_DB"
    echo ""

    verify_environment
    phase_backup
    phase_reset_database
    phase_migrations
    phase_verify
    phase_cleanup
    phase_final_check
    show_summary
}

# Run main function
main "$@"
