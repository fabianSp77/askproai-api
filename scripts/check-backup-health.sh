#!/bin/bash

# Check backup health and report issues

BACKUP_DIR="/var/www/api-gateway/backups"
MIN_DB_SIZE=1000000  # Minimum expected size in bytes (1MB)
ADMIN_EMAIL="fabian@v2202503255565320322.happysrv.de"

echo "=== AskProAI Backup Health Check ==="
echo "Date: $(date)"
echo ""

# Check last 7 days of backups
echo "Recent backups (last 7 days):"
echo "-----------------------------"

for i in {0..6}; do
    DATE=$(date -d "$i days ago" +%Y%m%d)
    DB_FILE=$(find "$BACKUP_DIR" -name "db_backup_${DATE}_*.sql.gz" -type f 2>/dev/null | head -1)
    
    if [ -n "$DB_FILE" ]; then
        SIZE=$(stat -c%s "$DB_FILE" 2>/dev/null || echo "0")
        SIZE_MB=$(( SIZE / 1048576 ))
        
        if [ $SIZE -lt $MIN_DB_SIZE ]; then
            STATUS="❌ FAILED (too small: ${SIZE_MB}MB)"
            echo "$DATE: $STATUS - $DB_FILE"
            
            # Check if it's a corrupted gzip
            if ! gunzip -t "$DB_FILE" 2>/dev/null; then
                echo "  └─ Corrupted gzip file!"
            fi
        else
            STATUS="✅ OK (${SIZE_MB}MB)"
            echo "$DATE: $STATUS"
        fi
    else
        echo "$DATE: ❌ MISSING"
    fi
done

echo ""
echo "Backup directory disk usage:"
df -h "$BACKUP_DIR"

echo ""
echo "Total backup files:"
find "$BACKUP_DIR" -name "*.gz" -o -name "*.sql" | wc -l

echo ""
echo "Oldest backup:"
find "$BACKUP_DIR" -name "*.gz" -o -name "*.sql" | sort | head -1

echo ""
echo "Failed backup analysis for 2025-08-02:"
echo "-------------------------------------"
FAILED_BACKUP="/var/www/api-gateway/backups/db_backup_20250802_030001.sql.gz"

if [ -f "$FAILED_BACKUP" ]; then
    echo "File exists: Yes"
    echo "Size: $(ls -lh $FAILED_BACKUP | awk '{print $5}')"
    echo "Content preview:"
    zcat "$FAILED_BACKUP" 2>&1 | head -20
    echo ""
    echo "Checking for error logs around that time:"
    grep -A5 -B5 "2025-08-02 03:00" /var/log/cron.log 2>/dev/null || echo "No cron logs found"
fi

# Test current database connectivity
echo ""
echo "Testing current database connection:"
if mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema='askproai_db';" askproai_db 2>/dev/null; then
    echo "✅ Database connection OK"
else
    echo "❌ Database connection FAILED"
fi