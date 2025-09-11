#!/bin/bash

# Test: Database Connection
# Verifies database connectivity with secure credentials

set -euo pipefail

TEST_NAME="Database Connection"
BACKUP_CONFIG="/var/www/api-gateway/.env.backup"

# Load credentials
if [ -f "$BACKUP_CONFIG" ]; then
    export $(grep -E "^DB_BACKUP_" "$BACKUP_CONFIG" | xargs)
else
    echo "FAIL: Cannot load backup config"
    exit 1
fi

# Test 1: Basic connectivity
if ! mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
          -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
          -e "SELECT 1" &>/dev/null; then
    echo "FAIL: Database connection failed"
    exit 1
fi

# Test 2: Check required privileges
privileges_needed=("SELECT" "LOCK TABLES" "SHOW VIEW" "TRIGGER" "EVENT")
for priv in "${privileges_needed[@]}"; do
    result=$(mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
                  -p"${DB_BACKUP_PASSWORD}" \
                  -e "SHOW GRANTS FOR CURRENT_USER()" -s -N 2>/dev/null)
    
    if ! echo "$result" | grep -q "$priv"; then
        echo "WARNING: Missing privilege: $priv"
    fi
done

# Test 3: Table accessibility
table_count=$(mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
                   -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
                   -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_BACKUP_DATABASE}'" \
                   -s -N 2>/dev/null)

if [ "$table_count" -eq 0 ]; then
    echo "FAIL: No tables accessible in database"
    exit 1
fi

echo "PASS: Database connection tests passed (${table_count} tables accessible)"
exit 0