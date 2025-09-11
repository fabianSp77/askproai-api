#!/bin/bash

# AskProAI Health Check Script
# =============================

echo "═══════════════════════════════════════════════════════"
echo "           ASKPROAI SYSTEM HEALTH CHECK                "
echo "═══════════════════════════════════════════════════════"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Server metrics
echo "📊 SERVER METRICS:"
echo -n "  CPU Load: "
uptime | awk '{print $10,$11,$12}'
echo -n "  Memory: "
free -h | grep Mem | awk '{print "Used: "$3" / Total: "$2}'
echo -n "  Disk: "
df -h / | tail -1 | awk '{print "Used: "$3" / "$2" ("$5")"}'
echo ""

# Service status
echo "🚀 SERVICE STATUS:"
services=("nginx" "php8.3-fpm" "mysql" "redis-server")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        echo -e "  $service: ${GREEN}✓ Running${NC}"
    else
        echo -e "  $service: ${RED}✗ Not running${NC}"
    fi
done
echo ""

# Database check
echo "🗄️ DATABASE STATUS:"
if mysql -u root -proot -e "SELECT 1" &>/dev/null; then
    echo -e "  MySQL: ${GREEN}✓ Connected${NC}"
    db_size=$(mysql -u root -proot askproai_db -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema='askproai_db';" -s 2>/dev/null)
    echo "  Database size: ${db_size} MB"
else
    echo -e "  MySQL: ${RED}✗ Connection failed${NC}"
fi
echo ""

# Redis check
echo "📦 REDIS STATUS:"
if redis-cli ping &>/dev/null; then
    echo -e "  Redis: ${GREEN}✓ Connected${NC}"
    memory=$(redis-cli info memory | grep used_memory_human | cut -d: -f2)
    echo "  Memory used: $memory"
else
    echo -e "  Redis: ${RED}✗ Connection failed${NC}"
fi
echo ""

# Laravel check
echo "🎨 LARAVEL STATUS:"
cd /var/www/api-gateway
if php artisan --version &>/dev/null; then
    echo -e "  Laravel: ${GREEN}✓ Working${NC}"
    echo -n "  Version: "
    php artisan --version | grep Laravel
else
    echo -e "  Laravel: ${RED}✗ Not working${NC}"
fi

# Queue status
jobs=$(mysql -u root -proot askproai_db -e "SELECT COUNT(*) FROM jobs;" -s 2>/dev/null)
failed=$(mysql -u root -proot askproai_db -e "SELECT COUNT(*) FROM failed_jobs;" -s 2>/dev/null)
echo "  Pending jobs: $jobs"
echo "  Failed jobs: $failed"
echo ""

# HTTP check
echo "🌐 HTTP STATUS:"
response=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/health 2>/dev/null)
if [ "$response" = "200" ]; then
    echo -e "  API Health: ${GREEN}✓ OK (HTTP $response)${NC}"
else
    echo -e "  API Health: ${RED}✗ Failed (HTTP $response)${NC}"
fi
echo ""

# Recent errors
echo "⚠️ RECENT ERRORS (Last 5):"
tail -5 /var/www/api-gateway/storage/logs/laravel.log | grep -i error || echo "  No recent errors"
echo ""

# Overall health score
health_score=100
[ "$response" != "200" ] && health_score=$((health_score - 30))
[ "$jobs" -gt "100" ] && health_score=$((health_score - 10))
[ "$failed" -gt "50" ] && health_score=$((health_score - 20))

echo "═══════════════════════════════════════════════════════"
if [ $health_score -ge 80 ]; then
    echo -e "OVERALL HEALTH: ${GREEN}✅ HEALTHY ($health_score/100)${NC}"
elif [ $health_score -ge 50 ]; then
    echo -e "OVERALL HEALTH: ${YELLOW}⚠️ WARNING ($health_score/100)${NC}"
else
    echo -e "OVERALL HEALTH: ${RED}🔴 CRITICAL ($health_score/100)${NC}"
fi
echo "═══════════════════════════════════════════════════════"