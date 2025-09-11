#!/bin/bash

# Test: Disk Full Recovery
# Chaos test to verify self-healing when disk is full

set -euo pipefail

TEST_NAME="Disk Full Recovery"
SCRIPT_DIR="/var/www/api-gateway/scripts"
BACKUP_DIR="/var/backups/askproai"
CHAOS_FILE="/tmp/chaos_disk_filler_$$"

# Cleanup function
cleanup() {
    rm -f "$CHAOS_FILE" 2>/dev/null || true
    # Remove test files created during chaos
    find "$BACKUP_DIR" -name "chaos_test_*" -delete 2>/dev/null || true
}
trap cleanup EXIT

# Test 1: Check initial disk usage
initial_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
echo "Initial disk usage: ${initial_usage}%"

# Test 2: Simulate disk filling (create large files)
echo "Simulating disk pressure..."
# Create files until disk is >85% full (but not completely full to avoid system issues)
target_usage=85
current_usage=$initial_usage

while [ $current_usage -lt $target_usage ]; do
    # Create 100MB file
    dd if=/dev/zero of="$BACKUP_DIR/chaos_test_$(date +%s).tmp" bs=1M count=100 &>/dev/null || break
    current_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    echo "Current usage: ${current_usage}%"
    
    # Safety check - don't fill beyond 90%
    if [ $current_usage -gt 90 ]; then
        break
    fi
done

# Test 3: Run self-healer
echo "Running self-healer to recover disk space..."
if "$SCRIPT_DIR/sc-backup-healer.sh" &>/dev/null; then
    echo "Self-healer completed"
else
    echo "Self-healer reported issues (expected)"
fi

# Test 4: Check if disk space was recovered
final_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
echo "Final disk usage: ${final_usage}%"

# Test 5: Verify recovery
if [ $final_usage -lt 80 ]; then
    echo "PASS: Disk space recovered successfully (${initial_usage}% -> ${current_usage}% -> ${final_usage}%)"
    exit 0
else
    echo "FAIL: Failed to recover disk space (final: ${final_usage}%)"
    exit 1
fi