#!/bin/bash

# ========================================
# QUICK HEALTH CHECK
# Fast system health verification
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}QUICK HEALTH CHECK${NC}"
echo "======================================"
echo "Time: $(date)"
echo ""

ISSUES=0

# 1. Services
echo "Services:"
for service in nginx php8.3-fpm mariadb redis; do
    printf "  %-15s: " "$service"
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✓ Running${NC}"
    else
        echo -e "${RED}✗ Stopped${NC}"
        ISSUES=$((ISSUES + 1))
    fi
done

# 2. Web Response
echo -e "\nWeb Response:"
printf "  Dashboard: "
code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin)
if [ "$code" == "200" ] || [ "$code" == "302" ]; then
    echo -e "${GREEN}✓ OK ($code)${NC}"
else
    echo -e "${RED}✗ Error ($code)${NC}"
    ISSUES=$((ISSUES + 1))
fi

# 3. Database
echo -e "\nDatabase:"
printf "  Connection: "
if mysql -u root askproai_db -e "SELECT 1" >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Connected${NC}"
    connections=$(mysql -u root askproai_db -sN -e "SHOW STATUS LIKE 'Threads_connected'" | awk '{print $2}')
    echo "  Active connections: $connections"
else
    echo -e "${RED}✗ Failed${NC}"
    ISSUES=$((ISSUES + 1))
fi

# 4. Disk Space
echo -e "\nDisk Space:"
disk_usage=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')
disk_avail=$(df -h / | tail -1 | awk '{print $4}')
printf "  Usage: "
if [ "$disk_usage" -lt 80 ]; then
    echo -e "${GREEN}✓ ${disk_usage}% (${disk_avail} free)${NC}"
elif [ "$disk_usage" -lt 90 ]; then
    echo -e "${YELLOW}⚠ ${disk_usage}% (${disk_avail} free)${NC}"
else
    echo -e "${RED}✗ ${disk_usage}% (${disk_avail} free)${NC}"
    ISSUES=$((ISSUES + 1))
fi

# 5. Memory
echo -e "\nMemory:"
mem_info=$(free -m | grep "^Mem")
if [ -n "$mem_info" ]; then
    mem_total=$(echo "$mem_info" | awk '{print $2}')
    mem_used=$(echo "$mem_info" | awk '{print $3}')
    if [ -n "$mem_total" ] && [ "$mem_total" -gt 0 ]; then
        mem_percent=$((mem_used * 100 / mem_total))
        printf "  Usage: "
        if [ "$mem_percent" -lt 80 ]; then
            echo -e "${GREEN}✓ ${mem_percent}% (${mem_used}MB/${mem_total}MB)${NC}"
        elif [ "$mem_percent" -lt 90 ]; then
            echo -e "${YELLOW}⚠ ${mem_percent}% (${mem_used}MB/${mem_total}MB)${NC}"
        else
            echo -e "${RED}✗ ${mem_percent}% (${mem_used}MB/${mem_total}MB)${NC}"
            ISSUES=$((ISSUES + 1))
        fi
    else
        echo -e "${YELLOW}⚠ Unable to get memory info${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Unable to get memory info${NC}"
fi

# 6. Recent Errors
echo -e "\nRecent Errors (last 5 min):"
if [ -f /var/www/api-gateway/storage/logs/laravel.log ]; then
    errors=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -5 -exec grep -c "ERROR" {} \; 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo "0")
    printf "  Laravel errors: "
    if [ "$errors" -eq 0 ]; then
        echo -e "${GREEN}✓ None${NC}"
    elif [ "$errors" -lt 10 ]; then
        echo -e "${YELLOW}⚠ $errors${NC}"
    else
        echo -e "${RED}✗ $errors${NC}"
        ISSUES=$((ISSUES + 1))
    fi
fi

# Summary
echo ""
echo "======================================"
if [ $ISSUES -eq 0 ]; then
    echo -e "Status: ${GREEN}✓ HEALTHY${NC}"
    exit 0
else
    echo -e "Status: ${RED}✗ ISSUES FOUND ($ISSUES)${NC}"
    exit 1
fi