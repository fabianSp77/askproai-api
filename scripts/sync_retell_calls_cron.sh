#!/bin/bash

# Retell Call Sync Cronjob Script
# Runs every 5 minutes to fetch missing call data from Retell API

# Change to script directory
cd /var/www/api-gateway

# Run the sync script
/usr/bin/php /var/www/api-gateway/scripts/fetch_retell_calls.php >> /var/www/api-gateway/storage/logs/retell_sync.log 2>&1

# Add timestamp to log
echo "Sync completed at $(date '+%Y-%m-%d %H:%M:%S')" >> /var/www/api-gateway/storage/logs/retell_sync.log
echo "----------------------------------------" >> /var/www/api-gateway/storage/logs/retell_sync.log