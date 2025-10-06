#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

clear
echo -e "${BLUE}MONITORING DASHBOARD${NC}"
echo "======================================"
echo "Time: $(date)"
echo ""

# Recent alerts
echo -e "${RED}Recent Alerts:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/alerts.log ]; then
    tail -5 /var/www/api-gateway/storage/monitoring/alerts.log
else
    echo "  No alerts"
fi
echo ""

# Last health check
echo -e "${BLUE}Last Health Check:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/health-check.log ]; then
    tail -20 /var/www/api-gateway/storage/monitoring/health-check.log | head -15
fi
echo ""

# Recent performance
echo -e "${BLUE}Recent Performance:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/performance.log ]; then
    tail -10 /var/www/api-gateway/storage/monitoring/performance.log
fi
echo ""

# Recent errors
echo -e "${YELLOW}Recent Errors:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/error-monitor.log ]; then
    tail -5 /var/www/api-gateway/storage/monitoring/error-monitor.log
fi
