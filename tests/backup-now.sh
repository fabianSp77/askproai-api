#!/usr/bin/env bash
set -euo pipefail

echo "======================================================"
echo "DATABASE BACKUP"
echo "======================================================"
echo ""

STAMP=$(date +"%Y%m%d-%H%M%S")
BACKUP_DIR="/var/backups/api-gateway"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="AskPro2025Secure"
OUT="${BACKUP_DIR}/askproai-db-${STAMP}.sql.gz"

echo "Creating backup directory..."
sudo mkdir -p "${BACKUP_DIR}"

echo "Starting backup of database: ${DB_NAME}"
echo "Timestamp: ${STAMP}"
echo ""

# Create backup with progress indicator
echo -n "Dumping database... "
mysqldump --single-transaction \
  -u "${DB_USER}" \
  -p"${DB_PASS}" \
  --databases "${DB_NAME}" \
  --routines \
  --triggers \
  --events \
  2>/dev/null | gzip -9 > "${OUT}"

if [ $? -eq 0 ]; then
    echo "✅ Success"
else
    echo "❌ Failed"
    exit 1
fi

# Get file size
SIZE=$(ls -lh "${OUT}" | awk '{print $5}')

echo ""
echo "Backup Details:"
echo "• File: ${OUT}"
echo "• Size: ${SIZE}"
echo "• Tables included: $(zcat "${OUT}" | grep -c '^CREATE TABLE' || echo "N/A")"

# Verify backup
echo ""
echo -n "Verifying backup integrity... "
if zcat "${OUT}" | tail -1 | grep -q "Dump completed"; then
    echo "✅ Valid"
else
    echo "⚠️ May be incomplete"
fi

# Cleanup old backups (keep last 7)
echo ""
echo "Cleaning old backups (keeping last 7)..."
cd "${BACKUP_DIR}"
ls -t askproai-db-*.sql.gz 2>/dev/null | tail -n +8 | xargs -r rm -v

echo ""
echo "======================================================"
echo "✅ BACKUP COMPLETE"
echo "======================================================"
echo ""
echo "To restore:"
echo "zcat ${OUT} | mysql -u root -p"
echo ""