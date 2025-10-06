#!/bin/bash

# ============================================================================
# Cal.com V1/V2 Booking ID Migration Validation Script
# ============================================================================
#
# Usage:
#   ./scripts/validate_calcom_migration.sh pre    # Before migration
#   ./scripts/validate_calcom_migration.sh post   # After migration
#   ./scripts/validate_calcom_migration.sh verify # After cleanup
#
# ============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Database credentials from .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

MYSQL_CMD="mysql -h ${DB_HOST} -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}"

# Functions
print_header() {
    echo -e "${BLUE}============================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Validation functions
validate_pre_migration() {
    print_header "PRE-MIGRATION VALIDATION"

    echo ""
    print_info "1. Overall Booking ID Distribution"
    $MYSQL_CMD -e "
        SELECT
            COUNT(*) as total_appointments,
            COUNT(calcom_booking_id) as has_v1_id,
            COUNT(calcom_v2_booking_id) as has_v2_id,
            SUM(CASE WHEN calcom_booking_id IS NULL AND calcom_v2_booking_id IS NULL THEN 1 ELSE 0 END) as no_calcom_id,
            SUM(CASE WHEN calcom_booking_id IS NOT NULL AND calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_both_ids
        FROM appointments
        WHERE deleted_at IS NULL;
    "

    echo ""
    print_info "2. V1 IDs in Wrong Column (Main Issue)"
    $MYSQL_CMD -e "
        SELECT
            COUNT(*) as v1_ids_in_v2_column
        FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
        AND deleted_at IS NULL;
    "

    V1_IN_V2=$($MYSQL_CMD -sN -e "
        SELECT COUNT(*) FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$' AND deleted_at IS NULL;
    ")

    echo ""
    if [ "$V1_IN_V2" -gt 0 ]; then
        print_warning "Found $V1_IN_V2 appointments with V1 IDs in V2 column - needs migration"
    else
        print_success "No V1 IDs in V2 column - migration may not be needed"
    fi

    echo ""
    print_info "3. Proper V2 UIDs"
    $MYSQL_CMD -e "
        SELECT COUNT(*) as proper_v2_uids
        FROM appointments
        WHERE calcom_v2_booking_id NOT REGEXP '^[0-9]+$'
        AND calcom_v2_booking_id IS NOT NULL
        AND deleted_at IS NULL;
    "

    echo ""
    print_info "4. Sample Affected Records (first 10)"
    $MYSQL_CMD -e "
        SELECT
            id,
            calcom_booking_id,
            calcom_v2_booking_id,
            source,
            created_at
        FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
        AND deleted_at IS NULL
        ORDER BY id
        LIMIT 10;
    "

    echo ""
    print_info "Ready to migrate $V1_IN_V2 appointments"
}

validate_post_migration() {
    print_header "POST-MIGRATION VALIDATION"

    echo ""
    print_info "1. V1 IDs Still in V2 Column (Should be 0)"
    V1_STILL_IN_V2=$($MYSQL_CMD -sN -e "
        SELECT COUNT(*) FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$' AND deleted_at IS NULL;
    ")

    $MYSQL_CMD -e "
        SELECT COUNT(*) as v1_ids_still_in_v2_column
        FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$'
        AND deleted_at IS NULL;
    "

    echo ""
    if [ "$V1_STILL_IN_V2" -eq 0 ]; then
        print_success "No V1 IDs in V2 column - migration successful!"
    else
        print_error "Still found $V1_STILL_IN_V2 V1 IDs in V2 column - migration failed!"
        exit 1
    fi

    echo ""
    print_info "2. Updated Distribution"
    $MYSQL_CMD -e "
        SELECT
            COUNT(*) as total,
            COUNT(calcom_booking_id) as has_v1_id,
            COUNT(calcom_v2_booking_id) as has_v2_id,
            COUNT(_migration_backup_v2_id) as has_backup
        FROM appointments
        WHERE deleted_at IS NULL;
    "

    echo ""
    print_info "3. Backup Column Integrity"
    $MYSQL_CMD -e "
        SELECT
            COUNT(*) as total_backups,
            SUM(CASE WHEN _migration_backup_v2_id REGEXP '^[0-9]+$' AND calcom_booking_id = CAST(_migration_backup_v2_id AS UNSIGNED) THEN 1 ELSE 0 END) as migrated_to_v1,
            SUM(CASE WHEN _migration_backup_v2_id NOT REGEXP '^[0-9]+$' AND calcom_v2_booking_id = _migration_backup_v2_id THEN 1 ELSE 0 END) as unchanged_v2
        FROM appointments
        WHERE _migration_backup_v2_id IS NOT NULL
        AND deleted_at IS NULL;
    "

    echo ""
    print_info "4. Sample Migrated Records"
    $MYSQL_CMD -e "
        SELECT
            id,
            calcom_booking_id as current_v1,
            calcom_v2_booking_id as current_v2,
            _migration_backup_v2_id as original_v2,
            source
        FROM appointments
        WHERE _migration_backup_v2_id REGEXP '^[0-9]+$'
        AND deleted_at IS NULL
        LIMIT 10;
    "

    echo ""
    print_success "Migration validation complete - all checks passed!"
    print_warning "Monitor application for 24-48 hours before running cleanup"
}

validate_cleanup() {
    print_header "POST-CLEANUP VERIFICATION"

    echo ""
    print_info "1. Backup Column Removed"
    BACKUP_EXISTS=$($MYSQL_CMD -sN -e "
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = '${DB_DATABASE}'
        AND TABLE_NAME = 'appointments'
        AND COLUMN_NAME = '_migration_backup_v2_id';
    ")

    if [ "$BACKUP_EXISTS" -eq 0 ]; then
        print_success "Backup column removed - cleanup successful!"
    else
        print_error "Backup column still exists - cleanup may have failed!"
        exit 1
    fi

    echo ""
    print_info "2. Final State Verification"
    $MYSQL_CMD -e "
        SELECT
            COUNT(*) as total,
            COUNT(calcom_booking_id) as has_v1_id,
            COUNT(calcom_v2_booking_id) as has_v2_id,
            SUM(CASE WHEN calcom_v2_booking_id REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as v1_in_v2_column
        FROM appointments
        WHERE deleted_at IS NULL;
    "

    V1_IN_V2=$($MYSQL_CMD -sN -e "
        SELECT COUNT(*) FROM appointments
        WHERE calcom_v2_booking_id REGEXP '^[0-9]+$' AND deleted_at IS NULL;
    ")

    echo ""
    if [ "$V1_IN_V2" -eq 0 ]; then
        print_success "Final validation passed - migration complete!"
    else
        print_error "Found $V1_IN_V2 V1 IDs in V2 column - data integrity issue!"
        exit 1
    fi

    echo ""
    print_info "3. Cal.com Source Distribution"
    $MYSQL_CMD -e "
        SELECT
            source,
            COUNT(*) as count,
            SUM(CASE WHEN calcom_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_v1,
            SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_v2
        FROM appointments
        WHERE deleted_at IS NULL
        AND source IN ('cal.com', 'calcom_import', 'retell_transcript')
        GROUP BY source;
    "

    echo ""
    print_success "Cleanup verification complete!"
}

# Main script
case "$1" in
    pre)
        validate_pre_migration
        ;;
    post)
        validate_post_migration
        ;;
    verify)
        validate_cleanup
        ;;
    *)
        echo "Usage: $0 {pre|post|verify}"
        echo ""
        echo "  pre    - Run before migration to understand current state"
        echo "  post   - Run after migration to validate success"
        echo "  verify - Run after cleanup to confirm final state"
        exit 1
        ;;
esac
