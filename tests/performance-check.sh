#!/bin/bash

# ========================================
# PERFORMANCE MONITORING & ANALYSIS
# Comprehensive system and application performance check
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Performance thresholds
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
DISK_THRESHOLD=90
LOAD_THRESHOLD=4.0
RESPONSE_TIME_THRESHOLD=1000  # milliseconds

# Counters
PERFORMANCE_ISSUES=0
WARNINGS=0

echo -e "${BLUE}╔══════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   PERFORMANCE MONITORING & ANALYSIS   ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════╝${NC}"
echo "Started at: $(date)"
echo ""

# Function to check status
check_status() {
    local value=$1
    local threshold=$2
    local metric=$3
    local unit=$4

    if (( $(echo "$value > $threshold" | bc -l) )); then
        echo -e "${RED}✗ CRITICAL${NC} ($value$unit > $threshold$unit)"
        PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
    elif (( $(echo "$value > $threshold * 0.8" | bc -l) )); then
        echo -e "${YELLOW}⚠ WARNING${NC} ($value$unit)"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}✓ OK${NC} ($value$unit)"
    fi
}

# 1. System Resources
echo -e "${BLUE}1. SYSTEM RESOURCES${NC}"
echo "--------------------------------------"

# CPU Usage
echo -n "  CPU Usage: "
cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
check_status "$cpu_usage" "$CPU_THRESHOLD" "CPU" "%"

# Memory Usage
echo -n "  Memory Usage: "
memory_total=$(free -m | grep "^Mem" | awk '{print $2}')
memory_used=$(free -m | grep "^Mem" | awk '{print $3}')
memory_percent=$(echo "scale=2; $memory_used * 100 / $memory_total" | bc)
check_status "$memory_percent" "$MEMORY_THRESHOLD" "Memory" "%"
echo "    Total: ${memory_total}MB | Used: ${memory_used}MB | Free: $((memory_total - memory_used))MB"

# Swap Usage
echo -n "  Swap Usage: "
swap_total=$(free -m | grep "^Swap" | awk '{print $2}')
swap_used=$(free -m | grep "^Swap" | awk '{print $3}')
if [ "$swap_total" -gt 0 ]; then
    swap_percent=$(echo "scale=2; $swap_used * 100 / $swap_total" | bc)
    if [ "$swap_used" -gt 100 ]; then
        echo -e "${YELLOW}⚠ Active${NC} (${swap_used}MB of ${swap_total}MB)"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}✓ Minimal${NC} (${swap_used}MB of ${swap_total}MB)"
    fi
else
    echo -e "${GREEN}✓ No swap configured${NC}"
fi

# Load Average
echo -n "  Load Average: "
load_avg=$(uptime | awk -F'load average:' '{print $2}')
load_1min=$(echo $load_avg | cut -d',' -f1 | xargs)
cpu_cores=$(nproc)
load_per_core=$(echo "scale=2; $load_1min / $cpu_cores" | bc)
check_status "$load_per_core" "$LOAD_THRESHOLD" "Load/Core" "")
echo "    Load: $load_avg | Cores: $cpu_cores"

echo ""

# 2. Disk Performance
echo -e "${BLUE}2. DISK PERFORMANCE${NC}"
echo "--------------------------------------"

# Disk Usage
echo "  Disk Usage:"
df -h | grep -E '^/dev/' | while read line; do
    filesystem=$(echo $line | awk '{print $1}')
    mount=$(echo $line | awk '{print $6}')
    usage=$(echo $line | awk '{print $5}' | sed 's/%//')
    available=$(echo $line | awk '{print $4}')

    printf "    %-20s: " "$mount"
    check_status "$usage" "$DISK_THRESHOLD" "Disk" "%"
    echo "      Available: $available"
done

# Disk I/O
echo -e "\n  Disk I/O Statistics:"
if command -v iostat &> /dev/null; then
    iostat -x 1 2 | tail -n +4 | head -5 | awk '{printf "    %-10s: Read: %6.2f MB/s | Write: %6.2f MB/s | Util: %5.1f%%\n", $1, $6/1024, $7/1024, $NF}'
else
    echo "    iostat not installed - skipping I/O analysis"
fi

echo ""

# 3. Database Performance
echo -e "${BLUE}3. DATABASE PERFORMANCE${NC}"
echo "--------------------------------------"

# MySQL Status
echo -n "  MySQL Status: "
if systemctl is-active --quiet mariadb; then
    echo -e "${GREEN}✓ Running${NC}"

    # Connection count
    echo -n "  Active Connections: "
    connections=$(mysql -u root -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1 | awk '{print $2}')
    max_connections=$(mysql -u root -e "SHOW VARIABLES LIKE 'max_connections';" 2>/dev/null | tail -1 | awk '{print $2}')
    connection_percent=$(echo "scale=2; $connections * 100 / $max_connections" | bc)
    check_status "$connection_percent" "80" "Connections" "%")
    echo "    Current: $connections | Max: $max_connections"

    # Query statistics
    echo "  Query Statistics:"
    queries=$(mysql -u root -e "SHOW STATUS LIKE 'Queries';" 2>/dev/null | tail -1 | awk '{print $2}')
    slow_queries=$(mysql -u root -e "SHOW STATUS LIKE 'Slow_queries';" 2>/dev/null | tail -1 | awk '{print $2}')
    echo "    Total Queries: $queries"
    echo -n "    Slow Queries: "
    if [ "$slow_queries" -gt 100 ]; then
        echo -e "${RED}$slow_queries (needs optimization)${NC}"
        PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
    else
        echo -e "${GREEN}$slow_queries${NC}"
    fi

    # Buffer pool usage
    echo "  InnoDB Buffer Pool:"
    buffer_stats=$(mysql -u root -e "SHOW STATUS LIKE 'Innodb_buffer_pool%';" 2>/dev/null)
    pages_total=$(echo "$buffer_stats" | grep "pages_total" | awk '{print $2}')
    pages_free=$(echo "$buffer_stats" | grep "pages_free" | awk '{print $2}')
    if [ -n "$pages_total" ] && [ -n "$pages_free" ]; then
        buffer_usage=$(echo "scale=2; ($pages_total - $pages_free) * 100 / $pages_total" | bc)
        echo "    Usage: ${buffer_usage}%"
    fi
else
    echo -e "${RED}✗ Not running${NC}"
    PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
fi

echo ""

# 4. Web Server Performance
echo -e "${BLUE}4. WEB SERVER PERFORMANCE${NC}"
echo "--------------------------------------"

# PHP-FPM Status
echo -n "  PHP-FPM Status: "
if systemctl is-active --quiet php8.3-fpm; then
    echo -e "${GREEN}✓ Running${NC}"

    # Check PHP-FPM pool status
    if [ -S /run/php/php8.3-fpm.sock ]; then
        echo "    Socket: Active"

        # Get PHP-FPM status page if configured
        php_status=$(curl -s "http://localhost/php-status" 2>/dev/null || echo "")
        if [ -n "$php_status" ]; then
            active_processes=$(echo "$php_status" | grep "active processes" | awk '{print $3}')
            idle_processes=$(echo "$php_status" | grep "idle processes" | awk '{print $3}')
            echo "    Active processes: $active_processes"
            echo "    Idle processes: $idle_processes"
        fi
    fi
else
    echo -e "${RED}✗ Not running${NC}"
    PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
fi

# Nginx Status
echo -n "  Nginx Status: "
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✓ Running${NC}"

    # Check Nginx connections
    nginx_status=$(curl -s "http://localhost/nginx-status" 2>/dev/null || echo "")
    if [ -n "$nginx_status" ]; then
        active_conn=$(echo "$nginx_status" | grep "Active" | awk '{print $3}')
        echo "    Active connections: $active_conn"
    fi
else
    echo -e "${RED}✗ Not running${NC}"
    PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
fi

echo ""

# 5. Laravel Application Performance
echo -e "${BLUE}5. APPLICATION PERFORMANCE${NC}"
echo "--------------------------------------"

# Response Time Test
echo "  Testing response times:"
endpoints=(
    "/"
    "/admin"
    "/api/health"
    "/admin/customers"
    "/admin/calls"
    "/admin/appointments"
)

total_time=0
endpoint_count=0

for endpoint in "${endpoints[@]}"; do
    printf "    %-25s: " "$endpoint"
    response_time=$(curl -s -o /dev/null -w "%{time_total}" "http://localhost$endpoint" 2>/dev/null)
    response_time_ms=$(echo "$response_time * 1000" | bc)
    response_time_int=${response_time_ms%.*}

    if [ -z "$response_time_int" ]; then
        response_time_int=0
    fi

    check_status "$response_time_int" "$RESPONSE_TIME_THRESHOLD" "Response" "ms")

    total_time=$(echo "$total_time + $response_time_ms" | bc)
    endpoint_count=$((endpoint_count + 1))
done

avg_response=$(echo "scale=2; $total_time / $endpoint_count" | bc)
echo -e "\n  Average Response Time: ${avg_response}ms"

# Cache Status
echo -e "\n  Cache Performance:"
echo -n "    Redis Status: "
if systemctl is-active --quiet redis; then
    echo -e "${GREEN}✓ Running${NC}"

    # Redis memory usage
    redis_info=$(redis-cli INFO memory 2>/dev/null)
    if [ -n "$redis_info" ]; then
        used_memory=$(echo "$redis_info" | grep "used_memory_human" | cut -d':' -f2 | tr -d '\r')
        echo "      Memory Usage: $used_memory"
    fi
else
    echo -e "${YELLOW}⚠ Not running (cache disabled)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Laravel Cache
echo -n "    Laravel Cache: "
cache_driver=$(grep "^CACHE_DRIVER" /var/www/api-gateway/.env 2>/dev/null | cut -d'=' -f2)
if [ -n "$cache_driver" ]; then
    echo "$cache_driver"
else
    echo "file (default)"
fi

echo ""

# 6. Error Rate Analysis
echo -e "${BLUE}6. ERROR RATE ANALYSIS${NC}"
echo "--------------------------------------"

if [ -f /var/www/api-gateway/storage/logs/laravel.log ]; then
    echo "  Recent errors (last hour):"

    # Count errors by level
    error_count=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -60 -exec grep -c "ERROR" {} \; 2>/dev/null | paste -sd+ | bc)
    warning_count=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -60 -exec grep -c "WARNING" {} \; 2>/dev/null | paste -sd+ | bc)
    critical_count=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -60 -exec grep -c "CRITICAL\|EMERGENCY\|ALERT" {} \; 2>/dev/null | paste -sd+ | bc)

    echo -n "    Critical: "
    if [ "$critical_count" -gt 0 ]; then
        echo -e "${RED}$critical_count${NC}"
        PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
    else
        echo -e "${GREEN}0${NC}"
    fi

    echo -n "    Errors: "
    if [ "$error_count" -gt 10 ]; then
        echo -e "${RED}$error_count (high)${NC}"
        PERFORMANCE_ISSUES=$((PERFORMANCE_ISSUES + 1))
    elif [ "$error_count" -gt 5 ]; then
        echo -e "${YELLOW}$error_count (moderate)${NC}"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}$error_count${NC}"
    fi

    echo "    Warnings: $warning_count"
else
    echo "  Log file not found"
fi

echo ""

# 7. Network Performance
echo -e "${BLUE}7. NETWORK PERFORMANCE${NC}"
echo "--------------------------------------"

# Check network interfaces
echo "  Network Interfaces:"
for interface in $(ip -o link show | awk -F': ' '{print $2}' | grep -v "^lo$"); do
    if ip addr show $interface | grep -q "state UP"; then
        printf "    %-10s: " "$interface"
        echo -e "${GREEN}UP${NC}"

        # Get interface statistics
        rx_bytes=$(cat /sys/class/net/$interface/statistics/rx_bytes 2>/dev/null)
        tx_bytes=$(cat /sys/class/net/$interface/statistics/tx_bytes 2>/dev/null)
        if [ -n "$rx_bytes" ] && [ -n "$tx_bytes" ]; then
            rx_mb=$(echo "scale=2; $rx_bytes / 1024 / 1024" | bc)
            tx_mb=$(echo "scale=2; $tx_bytes / 1024 / 1024" | bc)
            echo "      RX: ${rx_mb}MB | TX: ${tx_mb}MB"
        fi
    fi
done

# Check open connections
echo -e "\n  Connection Statistics:"
established=$(ss -tun | grep -c ESTAB)
time_wait=$(ss -tun | grep -c TIME-WAIT)
echo "    Established: $established"
echo "    TIME_WAIT: $time_wait"

echo ""

# 8. Process Analysis
echo -e "${BLUE}8. PROCESS ANALYSIS${NC}"
echo "--------------------------------------"

echo "  Top CPU Consumers:"
ps aux --sort=-%cpu | head -6 | tail -5 | awk '{printf "    %-20s: CPU: %5s%% | MEM: %5s%%\n", substr($11,0,20), $3, $4}'

echo -e "\n  Top Memory Consumers:"
ps aux --sort=-%mem | head -6 | tail -5 | awk '{printf "    %-20s: MEM: %5s%% | CPU: %5s%%\n", substr($11,0,20), $4, $3}'

echo ""

# 9. Recommendations
echo -e "${BLUE}9. PERFORMANCE RECOMMENDATIONS${NC}"
echo "--------------------------------------"

if [ $PERFORMANCE_ISSUES -gt 0 ] || [ $WARNINGS -gt 0 ]; then
    if (( $(echo "$cpu_usage > $CPU_THRESHOLD" | bc -l) )); then
        echo -e "  ${RED}►${NC} High CPU usage detected - consider scaling or optimization"
    fi

    if (( $(echo "$memory_percent > $MEMORY_THRESHOLD" | bc -l) )); then
        echo -e "  ${RED}►${NC} High memory usage - check for memory leaks or increase RAM"
    fi

    if [ "$slow_queries" -gt 100 ]; then
        echo -e "  ${YELLOW}►${NC} Many slow queries - run query optimization"
    fi

    if [ "$swap_used" -gt 100 ]; then
        echo -e "  ${YELLOW}►${NC} Swap usage detected - consider increasing RAM"
    fi

    if (( $(echo "$avg_response > $RESPONSE_TIME_THRESHOLD" | bc -l) )); then
        echo -e "  ${YELLOW}►${NC} Slow response times - enable caching or optimize code"
    fi
else
    echo -e "  ${GREEN}✓${NC} System is performing within normal parameters"
fi

# Summary
echo ""
echo "======================================"
echo -e "${BLUE}PERFORMANCE SUMMARY${NC}"
echo "--------------------------------------"
echo -e "Critical Issues: ${RED}$PERFORMANCE_ISSUES${NC}"
echo -e "Warnings: ${YELLOW}$WARNINGS${NC}"

if [ $PERFORMANCE_ISSUES -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "\n${GREEN}✓ SYSTEM HEALTHY${NC} - All performance metrics within normal range"
    exit_code=0
elif [ $PERFORMANCE_ISSUES -eq 0 ]; then
    echo -e "\n${YELLOW}⚠ MINOR ISSUES${NC} - System operational with some warnings"
    exit_code=0
else
    echo -e "\n${RED}✗ CRITICAL ISSUES${NC} - Immediate attention required"
    exit_code=1
fi

echo "--------------------------------------"
echo "Completed at: $(date)"
echo "======================================"

exit $exit_code