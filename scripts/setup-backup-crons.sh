#!/bin/bash

# SuperClaude Backup System - Cron Job Setup
# Sets up automated backup, monitoring, and self-healing schedules

set -euo pipefail

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${GREEN}=== SuperClaude Backup System - Cron Setup ===${NC}"
echo ""

# Backup current crontab
echo -e "${YELLOW}Backing up current crontab...${NC}"
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true

# Create new cron entries
cat << 'EOF' > /tmp/new_crons

# ================================================
# SuperClaude Backup System v2.0
# ================================================

# Main backup orchestrator - runs daily at 3:00 AM
0 3 * * * /var/www/api-gateway/scripts/sc-backup-orchestrator.sh >> /var/www/api-gateway/storage/logs/backup.log 2>&1

# Quick validation - runs every 6 hours
0 */6 * * * /var/www/api-gateway/scripts/sc-backup-validator.sh --quick >> /var/www/api-gateway/storage/logs/validation.log 2>&1

# Full validation - runs weekly on Sunday at 4:00 AM
0 4 * * 0 /var/www/api-gateway/scripts/sc-backup-validator.sh --full >> /var/www/api-gateway/storage/logs/validation.log 2>&1

# Self-healing - runs every hour
0 * * * * /var/www/api-gateway/scripts/sc-backup-healer.sh >> /var/www/api-gateway/storage/logs/healing.log 2>&1

# Backup monitor - runs every 30 minutes
*/30 * * * * /var/www/api-gateway/scripts/backup-monitor.sh >> /var/www/api-gateway/storage/logs/monitor.log 2>&1

# Test suite - runs weekly on Saturday at 5:00 AM
0 5 * * 6 /var/www/api-gateway/tests/backup/run-tests.sh --all --coverage >> /var/www/api-gateway/storage/logs/tests.log 2>&1

# Health check - runs every 15 minutes
*/15 * * * * /var/www/api-gateway/scripts/health-monitor.sh >> /var/www/api-gateway/storage/logs/health.log 2>&1

# Cleanup old logs - runs daily at 1:00 AM
0 1 * * * find /var/www/api-gateway/storage/logs -name "*.log" -mtime +30 -delete

# Laravel scheduled tasks (if needed)
* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1

# ================================================
# End of SuperClaude Backup System
# ================================================

EOF

# Check if cron entries already exist
echo -e "${YELLOW}Checking for existing SuperClaude cron entries...${NC}"
if crontab -l 2>/dev/null | grep -q "SuperClaude Backup System"; then
    echo -e "${YELLOW}Found existing entries. Updating...${NC}"
    
    # Remove old SuperClaude entries
    crontab -l 2>/dev/null | sed '/SuperClaude Backup System/,/End of SuperClaude Backup System/d' | crontab - 2>/dev/null || true
fi

# Add new entries
echo -e "${GREEN}Adding SuperClaude backup cron jobs...${NC}"
(crontab -l 2>/dev/null || true; cat /tmp/new_crons) | crontab -

# Verify installation
echo ""
echo -e "${GREEN}Cron jobs installed successfully!${NC}"
echo ""
echo "Active backup-related cron jobs:"
echo "================================"
crontab -l | grep -E "(backup|monitor|heal|valid|test)" | while read line; do
    echo "  $line"
done

echo ""
echo -e "${GREEN}Schedule Summary:${NC}"
echo "  • Main Backup: Daily at 3:00 AM"
echo "  • Quick Validation: Every 6 hours"
echo "  • Full Validation: Weekly (Sunday 4:00 AM)"
echo "  • Self-Healing: Every hour"
echo "  • Monitoring: Every 30 minutes"
echo "  • Test Suite: Weekly (Saturday 5:00 AM)"
echo "  • Health Check: Every 15 minutes"
echo ""

# Test cron service
if systemctl is-active --quiet cron; then
    echo -e "${GREEN}✓ Cron service is running${NC}"
else
    echo -e "${RED}✗ Cron service is not running!${NC}"
    echo "  Starting cron service..."
    systemctl start cron
fi

echo ""
echo -e "${GREEN}Setup complete!${NC}"
echo ""
echo "Monitor logs at:"
echo "  • /var/www/api-gateway/storage/logs/backup.log"
echo "  • /var/www/api-gateway/storage/logs/healing.log"
echo "  • /var/www/api-gateway/storage/logs/validation.log"
echo ""
echo "View dashboard at:"
echo "  • https://api.askproai.de/admin/backup-monitor"
echo ""

# Clean up
rm -f /tmp/new_crons

exit 0