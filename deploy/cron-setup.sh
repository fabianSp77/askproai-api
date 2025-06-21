#!/bin/bash

# AskProAI Cron Job Setup Script
# Version: 1.0
# Date: 2025-06-18

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Setting up AskProAI cron jobs...${NC}"

# Backup script path
BACKUP_SCRIPT="/var/www/api-gateway/deploy/backup-automation.sh"

# Laravel schedule
LARAVEL_PATH="/var/www/api-gateway"

# Create cron entries
CRON_FILE="/etc/cron.d/askproai"

# Write cron file
cat > "$CRON_FILE" << 'EOF'
# AskProAI Automated Tasks
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Laravel Scheduler (every minute)
* * * * * deploy cd /var/www/api-gateway && php artisan schedule:run >> /var/log/askproai-schedule.log 2>&1

# Daily backup at 2:00 AM
0 2 * * * deploy /var/www/api-gateway/deploy/backup-automation.sh daily >> /var/log/askproai-backup.log 2>&1

# Weekly backup on Sunday at 3:00 AM
0 3 * * 0 deploy /var/www/api-gateway/deploy/backup-automation.sh weekly >> /var/log/askproai-backup.log 2>&1

# Monthly backup on 1st day at 4:00 AM
0 4 1 * * deploy /var/www/api-gateway/deploy/backup-automation.sh monthly >> /var/log/askproai-backup.log 2>&1

# Health check every 5 minutes
*/5 * * * * deploy /var/www/api-gateway/deploy/health-monitor.sh >> /var/log/askproai-health.log 2>&1

# Queue monitoring every 10 minutes
*/10 * * * * deploy cd /var/www/api-gateway && php artisan queue:monitor >> /var/log/askproai-queue.log 2>&1

# Clear old logs weekly
0 0 * * 0 deploy find /var/log -name "askproai-*.log" -mtime +30 -delete

# Restart Horizon daily at 5:00 AM (prevent memory leaks)
0 5 * * * deploy cd /var/www/api-gateway && php artisan horizon:terminate && sleep 10 && php artisan horizon >> /var/log/askproai-horizon.log 2>&1
EOF

# Set permissions
chmod 644 "$CRON_FILE"

# Create log rotation config
cat > "/etc/logrotate.d/askproai" << 'EOF'
/var/log/askproai-*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 deploy deploy
    sharedscripts
    postrotate
        # Signal services if needed
        systemctl reload nginx > /dev/null 2>&1 || true
    endscript
}
EOF

# Create health monitoring script
cat > "/var/www/api-gateway/deploy/health-monitor.sh" << 'EOF'
#!/bin/bash

# Health monitoring script
HEALTH_URL="http://localhost/api/health"
MAX_RETRIES=3
RETRY_DELAY=10

for i in $(seq 1 $MAX_RETRIES); do
    RESPONSE=$(curl -s -w "\n%{http_code}" "$HEALTH_URL")
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" = "200" ]; then
        STATUS=$(echo "$BODY" | jq -r '.status' 2>/dev/null || echo "unknown")
        if [ "$STATUS" = "healthy" ]; then
            echo "[$(date)] Health check passed"
            exit 0
        fi
    fi
    
    echo "[$(date)] Health check failed (attempt $i/$MAX_RETRIES, HTTP $HTTP_CODE)"
    
    if [ $i -lt $MAX_RETRIES ]; then
        sleep $RETRY_DELAY
    fi
done

# Health check failed - send alert
echo "[$(date)] CRITICAL: Health check failed after $MAX_RETRIES attempts"

# Restart services if needed
cd /var/www/api-gateway
php artisan horizon:terminate
sleep 5
php artisan horizon &

# Send notification (implement your notification method)
# Example: Send to Slack, email, SMS, etc.
EOF

chmod +x "/var/www/api-gateway/deploy/health-monitor.sh"

# Create queue monitoring command
cat > "/var/www/api-gateway/app/Console/Commands/QueueMonitor.php" << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueMonitor extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitor queue health and alert on issues';

    public function handle()
    {
        $issues = [];
        
        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $issues[] = "High number of failed jobs: $failedJobs";
        }
        
        // Check queue size
        $queueSize = DB::table('jobs')->count();
        if ($queueSize > 1000) {
            $issues[] = "Large queue backlog: $queueSize jobs";
        }
        
        // Check old jobs
        $oldJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subHours(2))
            ->count();
        if ($oldJobs > 0) {
            $issues[] = "Stuck jobs detected: $oldJobs jobs older than 2 hours";
        }
        
        if (empty($issues)) {
            $this->info('Queue health check passed');
            Log::info('Queue monitor: All checks passed');
        } else {
            $this->error('Queue issues detected:');
            foreach ($issues as $issue) {
                $this->error("- $issue");
                Log::error("Queue monitor: $issue");
            }
            
            // Send alerts (implement your alerting)
        }
        
        return empty($issues) ? 0 : 1;
    }
}
EOF

echo -e "${GREEN}Cron jobs have been set up successfully!${NC}"
echo ""
echo "Created cron jobs:"
echo "- Laravel scheduler (every minute)"
echo "- Daily backups (2:00 AM)"
echo "- Weekly backups (Sunday 3:00 AM)"
echo "- Monthly backups (1st day 4:00 AM)"
echo "- Health monitoring (every 5 minutes)"
echo "- Queue monitoring (every 10 minutes)"
echo "- Log cleanup (weekly)"
echo "- Horizon restart (daily 5:00 AM)"
echo ""
echo "Logs will be written to /var/log/askproai-*.log"