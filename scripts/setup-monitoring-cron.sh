#!/bin/bash
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Setup Monitoring Cron Jobs
# Installiert automatische Überwachung und Selbstheilung
#━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

WORKING_DIR="/var/www/api-gateway"
SCRIPT_DIR="$WORKING_DIR/scripts"

echo "🔧 Setting up monitoring cron jobs..."

# Make scripts executable
chmod +x "$SCRIPT_DIR/proactive-monitor.sh" 2>/dev/null
chmod +x "$SCRIPT_DIR/auto-fix-cache.sh" 2>/dev/null

# Backup existing crontab
crontab -l > /tmp/crontab.backup 2>/dev/null || true
echo "📋 Backed up existing crontab to /tmp/crontab.backup"

# Remove old monitoring entries
crontab -l 2>/dev/null | grep -v "proactive-monitor\|health:monitor\|auto-fix-cache" > /tmp/crontab.new || true

# Add new monitoring jobs
cat >> /tmp/crontab.new << EOF

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Laravel Application Health Monitoring
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# Every minute: Quick health check with auto-fix
* * * * * cd $WORKING_DIR && php artisan health:monitor --auto-fix --silent >> storage/logs/health-cron.log 2>&1

# Every 5 minutes: Comprehensive health check
*/5 * * * * cd $WORKING_DIR && bash scripts/proactive-monitor.sh --once >> storage/logs/monitoring.log 2>&1

# Every 15 minutes: Cache optimization
*/15 * * * * cd $WORKING_DIR && php artisan optimize --quiet 2>&1

# Every hour: Clean old sessions and logs
0 * * * * cd $WORKING_DIR && find storage/framework/sessions -type f -mtime +1 -delete 2>&1
0 * * * * cd $WORKING_DIR && find storage/logs -name "*.log" -mtime +7 -delete 2>&1

# Daily at 3 AM: Full system optimization
0 3 * * * cd $WORKING_DIR && bash scripts/auto-fix-cache.sh >> storage/logs/daily-maintenance.log 2>&1

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOF

# Install new crontab
crontab /tmp/crontab.new
echo "✅ Cron jobs installed"

# Create log files if they don't exist
touch "$WORKING_DIR/storage/logs/health-cron.log"
touch "$WORKING_DIR/storage/logs/monitoring.log"
touch "$WORKING_DIR/storage/logs/daily-maintenance.log"
chmod 664 "$WORKING_DIR/storage/logs/"*.log
chown www-data:www-data "$WORKING_DIR/storage/logs/"*.log

echo "📁 Log files created"

# Show installed cron jobs
echo ""
echo "📋 Installed cron jobs:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
crontab -l | grep -A20 "Laravel Application Health Monitoring"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "✅ Monitoring setup complete!"
echo ""
echo "📊 Monitor logs at:"
echo "  • storage/logs/health-cron.log     - Minute-by-minute health checks"
echo "  • storage/logs/monitoring.log      - Detailed monitoring results"
echo "  • storage/logs/daily-maintenance.log - Daily optimization tasks"
echo ""
echo "🔍 Test monitoring with:"
echo "  • php artisan health:monitor"
echo "  • bash scripts/proactive-monitor.sh --once"
echo ""
echo "🔄 Remove monitoring with:"
echo "  • crontab -l | grep -v 'Laravel Application Health Monitoring' -A20 | crontab -"