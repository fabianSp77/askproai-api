#!/bin/bash

# SuperClaude Backup Validator
# Version: 2.0.0
# Comprehensive backup validation and integrity checking
# Includes test restore capabilities and detailed reporting

set -euo pipefail

# =============================================================================
# CONFIGURATION
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Load configuration
BACKUP_CONFIG="${SCRIPT_DIR}/../.env.backup"
if [ -f "$BACKUP_CONFIG" ]; then
    export $(grep -v '^#' "$BACKUP_CONFIG" | xargs)
else
    echo -e "${RED}✗ Critical: .env.backup not found!${NC}"
    exit 1
fi

# Paths
BACKUP_DIR="${BACKUP_BASE_DIR:-/var/backups/askproai}"
DB_BACKUP_DIR="$BACKUP_DIR/db"
FILES_BACKUP_DIR="$BACKUP_DIR/files"
VALIDATION_DIR="/tmp/backup_validation_$TIMESTAMP"
VALIDATION_LOG="$BACKUP_DIR/logs/validation_$TIMESTAMP.log"

# Test database (for restore testing)
TEST_DB_NAME="askproai_test_restore"

# Validation results
declare -A VALIDATION_RESULTS
declare -A VALIDATION_METRICS

# =============================================================================
# LOGGING
# =============================================================================

log() {
    local level="$1"
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" >> "$VALIDATION_LOG"
    
    case "$level" in
        ERROR)   echo -e "${RED}✗${NC} $message" ;;
        WARNING) echo -e "${YELLOW}⚠${NC} $message" ;;
        SUCCESS) echo -e "${GREEN}✓${NC} $message" ;;
        INFO)    echo -e "${BLUE}ℹ${NC} $message" ;;
        CHECK)   echo -e "${CYAN}▸${NC} $message" ;;
        *)       echo "$message" ;;
    esac
}

# =============================================================================
# VALIDATION FUNCTIONS
# =============================================================================

validate_backup_file() {
    local file="$1"
    local type="$2"
    
    if [ ! -f "$file" ]; then
        log ERROR "File not found: $file"
        return 1
    fi
    
    local size=$(stat -c%s "$file")
    local human_size=$(du -h "$file" | cut -f1)
    
    # Check minimum size (1KB)
    if [ $size -lt 1024 ]; then
        log ERROR "File too small: $file ($human_size)"
        return 1
    fi
    
    # Type-specific validation
    case "$type" in
        "sql.gz")
            if ! gzip -t "$file" 2>/dev/null; then
                log ERROR "Gzip integrity check failed: $file"
                return 1
            fi
            log SUCCESS "Valid SQL backup: $(basename "$file") ($human_size)"
            ;;
        "tar.gz")
            if ! tar tzf "$file" &>/dev/null; then
                log ERROR "Tar integrity check failed: $file"
                return 1
            fi
            log SUCCESS "Valid tar backup: $(basename "$file") ($human_size)"
            ;;
        *)
            log WARNING "Unknown file type: $type"
            ;;
    esac
    
    VALIDATION_METRICS["${file}_size"]="$human_size"
    return 0
}

test_database_restore() {
    local backup_file="$1"
    
    log INFO "Testing database restore capability..."
    
    # Create test database
    mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
          -p"${DB_BACKUP_PASSWORD}" \
          -e "DROP DATABASE IF EXISTS $TEST_DB_NAME; CREATE DATABASE $TEST_DB_NAME;" 2>/dev/null
    
    if [ $? -ne 0 ]; then
        log ERROR "Failed to create test database"
        return 1
    fi
    
    # Test restore
    if gunzip -c "$backup_file" | \
       mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
             -p"${DB_BACKUP_PASSWORD}" "$TEST_DB_NAME" 2>/dev/null; then
        
        # Verify tables exist
        table_count=$(mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
                           -p"${DB_BACKUP_PASSWORD}" "$TEST_DB_NAME" \
                           -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEST_DB_NAME';" \
                           -s -N 2>/dev/null)
        
        if [ "$table_count" -gt 0 ]; then
            log SUCCESS "Database restore successful: $table_count tables restored"
            VALIDATION_METRICS["restored_tables"]="$table_count"
            
            # Clean up test database
            mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
                  -p"${DB_BACKUP_PASSWORD}" \
                  -e "DROP DATABASE IF EXISTS $TEST_DB_NAME;" 2>/dev/null
            return 0
        else
            log ERROR "Restore completed but no tables found"
            return 1
        fi
    else
        log ERROR "Database restore failed"
        return 1
    fi
}

test_file_restore() {
    local backup_file="$1"
    
    log INFO "Testing file restore capability..."
    
    mkdir -p "$VALIDATION_DIR"
    
    # Test extraction
    if tar xzf "$backup_file" -C "$VALIDATION_DIR" 2>/dev/null; then
        # Count restored files
        file_count=$(find "$VALIDATION_DIR" -type f | wc -l)
        dir_count=$(find "$VALIDATION_DIR" -type d | wc -l)
        
        log SUCCESS "File restore successful: $file_count files, $dir_count directories"
        VALIDATION_METRICS["restored_files"]="$file_count"
        VALIDATION_METRICS["restored_dirs"]="$dir_count"
        
        # Clean up
        rm -rf "$VALIDATION_DIR"
        return 0
    else
        log ERROR "File extraction failed"
        return 1
    fi
}

check_backup_consistency() {
    log INFO "Checking backup consistency..."
    
    # Get latest backups
    local latest_db=$(ls -t "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | head -1)
    local latest_files=$(ls -t "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | head -1)
    
    if [ -z "$latest_db" ] || [ -z "$latest_files" ]; then
        log ERROR "Missing backup files"
        return 1
    fi
    
    # Check age difference
    local db_time=$(stat -c %Y "$latest_db")
    local files_time=$(stat -c %Y "$latest_files")
    local time_diff=$((db_time > files_time ? db_time - files_time : files_time - db_time))
    
    # If backups are more than 1 hour apart, warn
    if [ $time_diff -gt 3600 ]; then
        log WARNING "Backup time mismatch: $(($time_diff / 3600)) hours apart"
        VALIDATION_RESULTS["consistency"]="warning"
    else
        log SUCCESS "Backups are consistent (within 1 hour)"
        VALIDATION_RESULTS["consistency"]="passed"
    fi
    
    return 0
}

check_backup_age() {
    log INFO "Checking backup age..."
    
    local latest_backup=$(find "$BACKUP_DIR" -name "*.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [ -z "$latest_backup" ]; then
        log ERROR "No backups found"
        VALIDATION_RESULTS["age"]="failed"
        return 1
    fi
    
    local age_hours=$(( ($(date +%s) - $(stat -c %Y "$latest_backup")) / 3600 ))
    
    if [ $age_hours -gt ${BACKUP_CRITICAL_HOURS:-50} ]; then
        log ERROR "Latest backup is critically old: ${age_hours}h"
        VALIDATION_RESULTS["age"]="critical"
        return 1
    elif [ $age_hours -gt ${BACKUP_WARNING_HOURS:-26} ]; then
        log WARNING "Latest backup is old: ${age_hours}h"
        VALIDATION_RESULTS["age"]="warning"
        return 0
    else
        log SUCCESS "Latest backup age: ${age_hours}h"
        VALIDATION_RESULTS["age"]="passed"
        return 0
    fi
}

calculate_backup_checksums() {
    log INFO "Calculating checksums..."
    
    local checksum_file="$BACKUP_DIR/checksums_$TIMESTAMP.txt"
    
    # Calculate MD5 for all recent backups
    find "$BACKUP_DIR" -name "*.gz" -mtime -1 -exec md5sum {} \; > "$checksum_file" 2>/dev/null
    
    local checksum_count=$(wc -l < "$checksum_file")
    log SUCCESS "Generated $checksum_count checksums"
    VALIDATION_METRICS["checksums_generated"]="$checksum_count"
    
    return 0
}

# =============================================================================
# COMPREHENSIVE VALIDATION
# =============================================================================

run_full_validation() {
    log INFO "=== Starting Comprehensive Backup Validation ==="
    
    local errors=0
    
    # 1. Check backup files exist and are valid
    log CHECK "Validating backup files..."
    
    for db_backup in "$DB_BACKUP_DIR"/*.sql.gz; do
        [ -f "$db_backup" ] || continue
        validate_backup_file "$db_backup" "sql.gz" || ((errors++))
    done
    
    for file_backup in "$FILES_BACKUP_DIR"/*.tar.gz; do
        [ -f "$file_backup" ] || continue
        validate_backup_file "$file_backup" "tar.gz" || ((errors++))
    done
    
    # 2. Test restore capability
    log CHECK "Testing restore capability..."
    
    local latest_db=$(ls -t "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | head -1)
    if [ -n "$latest_db" ]; then
        test_database_restore "$latest_db" || ((errors++))
    fi
    
    local latest_files=$(ls -t "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | head -1)
    if [ -n "$latest_files" ]; then
        test_file_restore "$latest_files" || ((errors++))
    fi
    
    # 3. Check consistency
    check_backup_consistency || ((errors++))
    
    # 4. Check age
    check_backup_age || ((errors++))
    
    # 5. Generate checksums
    calculate_backup_checksums
    
    # Generate report
    generate_validation_report
    
    return $errors
}

generate_validation_report() {
    log INFO "Generating validation report..."
    
    {
        echo "================================================"
        echo "Backup Validation Report - $TIMESTAMP"
        echo "================================================"
        echo ""
        echo "Validation Results:"
        for key in "${!VALIDATION_RESULTS[@]}"; do
            printf "  %-20s: %s\n" "$key" "${VALIDATION_RESULTS[$key]}"
        done
        echo ""
        echo "Metrics:"
        for key in "${!VALIDATION_METRICS[@]}"; do
            printf "  %-20s: %s\n" "$key" "${VALIDATION_METRICS[$key]}"
        done
        echo ""
        echo "Recent Backups:"
        ls -lah "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -3
        ls -lah "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | tail -3
        echo ""
    } | tee -a "$VALIDATION_LOG"
}

# =============================================================================
# QUICK VALIDATION MODE
# =============================================================================

run_quick_validation() {
    log INFO "Running quick validation..."
    
    local latest_db=$(ls -t "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | head -1)
    local latest_files=$(ls -t "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | head -1)
    
    local errors=0
    
    [ -n "$latest_db" ] && validate_backup_file "$latest_db" "sql.gz" || ((errors++))
    [ -n "$latest_files" ] && validate_backup_file "$latest_files" "tar.gz" || ((errors++))
    
    check_backup_age || ((errors++))
    
    return $errors
}

# =============================================================================
# MAIN
# =============================================================================

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --full    Run comprehensive validation with restore tests"
    echo "  --quick   Run quick validation (default)"
    echo "  --help    Show this help message"
    echo ""
}

main() {
    local mode="quick"
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --full)
                mode="full"
                shift
                ;;
            --quick)
                mode="quick"
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Create log directory
    mkdir -p "$(dirname "$VALIDATION_LOG")"
    
    # Run validation
    case "$mode" in
        "full")
            run_full_validation
            ;;
        "quick")
            run_quick_validation
            ;;
    esac
    
    local exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        log SUCCESS "=== Validation Completed Successfully ==="
    else
        log ERROR "=== Validation Failed with $exit_code errors ==="
    fi
    
    exit $exit_code
}

# Run main
main "$@"