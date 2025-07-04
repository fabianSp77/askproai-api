#!/bin/bash

# Add log rotation to crontab
CRON_JOB="0 0 * * * /var/www/api-gateway/scripts/log-rotation.sh"

# Check if cron job already exists
if ! crontab -l 2>/dev/null | grep -q "log-rotation.sh"; then
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✅ Log rotation cron job added successfully"
else
    echo "ℹ️  Log rotation cron job already exists"
fi

# Show current crontab
echo -e "\nCurrent crontab:"
crontab -l | grep -E "(log-rotation|askproai)" || echo "No AskProAI cron jobs found"