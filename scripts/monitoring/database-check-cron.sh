#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/database-integrity.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

echo "====== Database Check: $(date) ======" >> "$LOG_FILE"

# Check for orphaned records
orphaned_calls=$(mysql -u root askproai_db -sN -e "SELECT COUNT(*) FROM calls WHERE staff_id IS NOT NULL AND staff_id NOT IN (SELECT id FROM staff)" 2>/dev/null)

if [ "$orphaned_calls" -gt 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Found $orphaned_calls orphaned call records" >> "$ALERT_FILE"
    echo "Orphaned calls: $orphaned_calls" >> "$LOG_FILE"
fi

# Check for duplicate emails
duplicates=$(mysql -u root askproai_db -sN -e "SELECT COUNT(*) FROM (SELECT email, COUNT(*) as cnt FROM customers WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1) as t" 2>/dev/null)

if [ "$duplicates" -gt 0 ]; then
    echo "Duplicate customer emails: $duplicates" >> "$LOG_FILE"
fi

# Check slow queries
slow_queries=$(mysql -u root askproai_db -sN -e "SHOW STATUS LIKE 'Slow_queries'" 2>/dev/null | awk '{print $2}')
echo "Slow queries: $slow_queries" >> "$LOG_FILE"

echo "" >> "$LOG_FILE"
