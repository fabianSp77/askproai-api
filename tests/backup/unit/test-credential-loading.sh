#!/bin/bash

# Test: Credential Loading
# Verifies secure credential loading from .env.backup

set -euo pipefail

TEST_NAME="Credential Loading"
BACKUP_CONFIG="/var/www/api-gateway/.env.backup"

# Test 1: Check if .env.backup exists
if [ ! -f "$BACKUP_CONFIG" ]; then
    echo "FAIL: $BACKUP_CONFIG not found"
    exit 1
fi

# Test 2: Check file permissions (should be 600)
permissions=$(stat -c %a "$BACKUP_CONFIG")
if [ "$permissions" != "600" ]; then
    echo "FAIL: Incorrect permissions: $permissions (expected 600)"
    exit 1
fi

# Test 3: Load and verify critical variables
source <(grep -E "^DB_BACKUP_|^BACKUP_" "$BACKUP_CONFIG" | sed 's/^/export /')

# Test required variables
required_vars=(
    "DB_BACKUP_HOST"
    "DB_BACKUP_DATABASE"
    "DB_BACKUP_USERNAME"
    "DB_BACKUP_PASSWORD"
    "BACKUP_BASE_DIR"
    "BACKUP_ADMIN_EMAIL"
)

for var in "${required_vars[@]}"; do
    if [ -z "${!var:-}" ]; then
        echo "FAIL: Required variable $var not set"
        exit 1
    fi
done

# Test 4: Verify no hardcoded passwords in scripts
if grep -r "jobFQcK22EgtKJLEqJNs3pfmS" /var/www/api-gateway/scripts/*.sh 2>/dev/null; then
    echo "FAIL: Hardcoded password found in scripts"
    exit 1
fi

echo "PASS: All credential tests passed"
exit 0