#!/bin/bash

# Setup View Cache Monitoring
echo "Setting up automated view cache monitoring..."

# Add cron job to monitor and fix view cache every 5 minutes
CRON_JOB="*/5 * * * * cd /var/www/api-gateway && php artisan view:monitor --fix >> /var/log/view-cache-monitor.log 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "view:monitor"; then
    echo "✓ View cache monitoring cron job already exists"
else
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✓ Added view cache monitoring cron job (runs every 5 minutes)"
fi

# Create log file with proper permissions
touch /var/log/view-cache-monitor.log
chown www-data:www-data /var/log/view-cache-monitor.log
chmod 664 /var/log/view-cache-monitor.log

echo "✓ View cache monitoring setup complete"
echo "  Monitor logs at: /var/log/view-cache-monitor.log"