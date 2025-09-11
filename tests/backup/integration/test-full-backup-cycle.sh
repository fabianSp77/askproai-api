#!/bin/bash

# Test: Full Backup Cycle
# Tests complete backup, validation, and restore cycle

set -euo pipefail

TEST_NAME="Full Backup Cycle"
SCRIPT_DIR="/var/www/api-gateway/scripts"
TEST_DB="askproai_test_integration"
TEST_BACKUP_DIR="/tmp/test_backup_$$"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)

# Cleanup function
cleanup() {
    rm -rf "$TEST_BACKUP_DIR" 2>/dev/null || true
    mysql -u root -e "DROP DATABASE IF EXISTS $TEST_DB" 2>/dev/null || true
}
trap cleanup EXIT

# Setup
mkdir -p "$TEST_BACKUP_DIR"

# Test 1: Run backup orchestrator
echo "Testing backup orchestrator..."
if ! "$SCRIPT_DIR/sc-backup-orchestrator.sh" &>/dev/null; then
    echo "FAIL: Backup orchestrator failed"
    exit 1
fi

# Test 2: Validate backup was created
echo "Checking for backup files..."
latest_backup=$(find /var/backups/askproai/db -name "*.sql.gz" -mmin -5 | head -1)
if [ -z "$latest_backup" ]; then
    echo "FAIL: No recent backup found"
    exit 1
fi

# Test 3: Run validator
echo "Testing backup validator..."
if ! "$SCRIPT_DIR/sc-backup-validator.sh" --quick &>/dev/null; then
    echo "FAIL: Backup validation failed"
    exit 1
fi

# Test 4: Test self-healer (should find no issues on fresh system)
echo "Testing self-healer..."
if ! "$SCRIPT_DIR/sc-backup-healer.sh" &>/dev/null; then
    echo "WARNING: Self-healer reported issues (non-critical)"
fi

# Test 5: Verify backup integrity
echo "Testing backup integrity..."
if ! gzip -t "$latest_backup" 2>/dev/null; then
    echo "FAIL: Backup file corrupted"
    exit 1
fi

# Test 6: Test restore capability
echo "Testing restore capability..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $TEST_DB" 2>/dev/null

if gunzip -c "$latest_backup" | mysql $TEST_DB 2>/dev/null; then
    # Verify tables were restored
    table_count=$(mysql $TEST_DB -e "SHOW TABLES" -s -N 2>/dev/null | wc -l)
    if [ "$table_count" -gt 0 ]; then
        echo "PASS: Full backup cycle completed successfully ($table_count tables)"
        exit 0
    else
        echo "FAIL: Restore succeeded but no tables found"
        exit 1
    fi
else
    echo "FAIL: Restore failed"
    exit 1
fi