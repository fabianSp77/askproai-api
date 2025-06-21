#!/bin/bash

# AskProAI Backup Cron-Jobs einrichten

echo "Setting up AskProAI backup cron jobs..."

# Create cron entries
CRON_ENTRIES="
# AskProAI Automated Backups
# Full backup daily at 2 AM
0 2 * * * cd /var/www/api-gateway && /usr/bin/php artisan askproai:backup --type=full --compress >> /var/log/askproai-backup.log 2>&1

# Incremental backup every hour
0 * * * * cd /var/www/api-gateway && /usr/bin/php artisan askproai:backup --type=incremental >> /var/log/askproai-backup.log 2>&1

# Critical data backup every 6 hours
0 */6 * * * cd /var/www/api-gateway && /usr/bin/php artisan askproai:backup --type=critical --compress >> /var/log/askproai-backup.log 2>&1

# Backup monitoring daily at 8:30 AM
30 8 * * * cd /var/www/api-gateway && /usr/bin/php artisan askproai:backup-monitor >> /var/log/askproai-backup.log 2>&1
"

# Add to crontab
(crontab -l 2>/dev/null; echo "$CRON_ENTRIES") | crontab -

# Create log file with proper permissions
touch /var/log/askproai-backup.log
chmod 664 /var/log/askproai-backup.log
chown www-data:www-data /var/log/askproai-backup.log

echo "✅ Backup cron jobs have been set up!"
echo ""
echo "View current crontab:"
crontab -l | grep -A 10 "AskProAI"

echo ""
echo "Log file location: /var/log/askproai-backup.log"
echo ""
echo "Test backup command:"
echo "php artisan askproai:backup --type=critical --compress"