#!/bin/bash

# ========================================
# REAL-TIME MONITORING DASHBOARD
# Live system and application monitoring
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
NC='\033[0m'
BOLD='\033[1m'

# Config
REFRESH_INTERVAL=2
LOG_FILE="/var/www/api-gateway/storage/logs/laravel.log"
BASE_URL="http://localhost"

# Initialize counters
ITERATION=0
ALERTS=()

# Trap to restore terminal on exit
trap 'tput cnorm; echo -e "\n${NC}Monitoring stopped."; exit' INT TERM

# Hide cursor
tput civis

# Function to get colored status indicator
get_status_color() {
    local value=$1
    local warning=$2
    local critical=$3

    if (( $(echo "$value >= $critical" | bc -l) )); then
        echo "${RED}"
    elif (( $(echo "$value >= $warning" | bc -l) )); then
        echo "${YELLOW}"
    else
        echo "${GREEN}"
    fi
}

# Function to draw a progress bar
draw_bar() {
    local percent=$1
    local width=$2
    local filled=$(echo "scale=0; $percent * $width / 100" | bc)
    local empty=$((width - filled))

    # Color based on percentage
    local color=$(get_status_color $percent 60 80)

    printf "${color}"
    printf '█%.0s' $(seq 1 $filled)
    printf "${NC}"
    printf '░%.0s' $(seq 1 $empty)
    printf " %3d%%" "$percent"
}

# Function to format bytes
format_bytes() {
    local bytes=$1
    if [ $bytes -lt 1024 ]; then
        echo "${bytes}B"
    elif [ $bytes -lt 1048576 ]; then
        echo "$(echo "scale=1; $bytes/1024" | bc)KB"
    elif [ $bytes -lt 1073741824 ]; then
        echo "$(echo "scale=1; $bytes/1048576" | bc)MB"
    else
        echo "$(echo "scale=2; $bytes/1073741824" | bc)GB"
    fi
}

# Main monitoring loop
while true; do
    clear
    ITERATION=$((ITERATION + 1))
    ALERTS=()

    # Header
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${WHITE}                     LIVE MONITORING DASHBOARD                               ${BLUE}║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║${NC} $(date '+%Y-%m-%d %H:%M:%S') | Iteration: #${ITERATION} | Refresh: ${REFRESH_INTERVAL}s                             ${BLUE}║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""

    # System Resources Section
    echo -e "${CYAN}▶ SYSTEM RESOURCES${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"

    # CPU Usage
    cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
    cpu_int=${cpu_usage%.*}
    echo -n "│ CPU:  "
    draw_bar "$cpu_int" 30

    # Alert check
    if (( $(echo "$cpu_usage > 80" | bc -l) )); then
        ALERTS+=("High CPU usage: ${cpu_usage}%")
    fi

    # CPU Cores and Load
    cpu_cores=$(nproc)
    load_avg=$(uptime | awk -F'load average:' '{print $2}')
    echo " │ Load: $load_avg │"

    # Memory Usage
    memory_total=$(free -m | grep "^Mem" | awk '{print $2}')
    memory_used=$(free -m | grep "^Mem" | awk '{print $3}')
    memory_percent=$(echo "scale=0; $memory_used * 100 / $memory_total" | bc)
    echo -n "│ MEM:  "
    draw_bar "$memory_percent" 30
    echo " │ ${memory_used}MB / ${memory_total}MB        │"

    # Alert check
    if [ "$memory_percent" -gt 85 ]; then
        ALERTS+=("High memory usage: ${memory_percent}%")
    fi

    # Swap Usage
    swap_total=$(free -m | grep "^Swap" | awk '{print $2}')
    swap_used=$(free -m | grep "^Swap" | awk '{print $3}')
    if [ "$swap_total" -gt 0 ]; then
        swap_percent=$(echo "scale=0; $swap_used * 100 / $swap_total" | bc)
        echo -n "│ SWAP: "
        draw_bar "$swap_percent" 30
        echo " │ ${swap_used}MB / ${swap_total}MB        │"
    fi

    # Disk Usage
    disk_usage=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')
    disk_available=$(df -h / | tail -1 | awk '{print $4}')
    echo -n "│ DISK: "
    draw_bar "$disk_usage" 30
    echo " │ Free: ${disk_available}            │"

    echo "└──────────────────────────────────────────────────────────────────────────────┘"
    echo ""

    # Services Status
    echo -e "${CYAN}▶ SERVICE STATUS${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"

    # Check services
    services=("nginx" "php8.3-fpm" "mariadb" "redis")
    for service in "${services[@]}"; do
        printf "│ %-15s: " "$service"
        if systemctl is-active --quiet $service; then
            printf "${GREEN}● RUNNING${NC}"

            # Get additional info
            case $service in
                "mariadb")
                    connections=$(mysql -u root -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1 | awk '{print $2}')
                    printf " (Connections: %3s)" "${connections:-0}"
                    ;;
                "php8.3-fpm")
                    if [ -S /run/php/php8.3-fpm.sock ]; then
                        printf " (Socket: Active)   "
                    fi
                    ;;
                "nginx")
                    active_conn=$(ss -tun | grep -c ":80\|:443" 2>/dev/null)
                    printf " (Connections: %3s)" "$active_conn"
                    ;;
                "redis")
                    if command -v redis-cli &> /dev/null; then
                        clients=$(redis-cli client list 2>/dev/null | wc -l)
                        printf " (Clients: %3s)     " "${clients:-0}"
                    fi
                    ;;
            esac
            printf "                      │\n"
        else
            printf "${RED}● STOPPED${NC}                                               │\n"
            ALERTS+=("Service $service is not running")
        fi
    done

    echo "└──────────────────────────────────────────────────────────────────────────────┘"
    echo ""

    # Application Health
    echo -e "${CYAN}▶ APPLICATION HEALTH${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"

    # Response time check
    printf "│ Dashboard Response: "
    start_time=$(date +%s%N)
    http_code=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/admin" 2>/dev/null)
    end_time=$(date +%s%N)
    response_time=$(( (end_time - start_time) / 1000000 ))

    if [ "$http_code" == "200" ]; then
        color=$(get_status_color $response_time 500 1000)
        printf "${color}${response_time}ms${NC} (HTTP ${http_code})"
    else
        printf "${RED}ERROR (HTTP ${http_code})${NC}"
        ALERTS+=("Dashboard returned HTTP ${http_code}")
    fi
    printf "%*s│\n" $((55 - ${#response_time})) ""

    # Database queries (if slow query log exists)
    printf "│ Database Status:    "
    if systemctl is-active --quiet mariadb; then
        slow_queries=$(mysql -u root -e "SHOW STATUS LIKE 'Slow_queries';" 2>/dev/null | tail -1 | awk '{print $2}')
        queries_per_sec=$(mysql -u root -e "SHOW STATUS LIKE 'Questions';" 2>/dev/null | tail -1 | awk '{print $2}')
        uptime_sec=$(mysql -u root -e "SHOW STATUS LIKE 'Uptime';" 2>/dev/null | tail -1 | awk '{print $2}')

        if [ -n "$queries_per_sec" ] && [ -n "$uptime_sec" ] && [ "$uptime_sec" -gt 0 ]; then
            qps=$(echo "scale=2; $queries_per_sec / $uptime_sec" | bc)
            printf "${GREEN}Active${NC} (QPS: ${qps}, Slow: ${slow_queries:-0})"
        else
            printf "${GREEN}Active${NC}"
        fi
    else
        printf "${RED}Offline${NC}"
    fi
    printf "%*s│\n" 35 ""

    # Error rate (last minute)
    if [ -f "$LOG_FILE" ]; then
        recent_errors=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -1 -exec grep -c "ERROR" {} \; 2>/dev/null | paste -sd+ | bc)
        recent_warnings=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -1 -exec grep -c "WARNING" {} \; 2>/dev/null | paste -sd+ | bc)

        printf "│ Recent Errors (1m): "
        if [ "${recent_errors:-0}" -gt 0 ]; then
            printf "${RED}${recent_errors} errors${NC}, ${YELLOW}${recent_warnings} warnings${NC}"
            ALERTS+=("${recent_errors} errors in last minute")
        else
            printf "${GREEN}No errors${NC}, ${YELLOW}${recent_warnings} warnings${NC}"
        fi
        printf "%*s│\n" 32 ""
    fi

    echo "└──────────────────────────────────────────────────────────────────────────────┘"
    echo ""

    # Network Activity
    echo -e "${CYAN}▶ NETWORK ACTIVITY${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"

    # Get main network interface
    main_if=$(ip route | grep default | awk '{print $5}' | head -1)
    if [ -n "$main_if" ]; then
        # Read current bytes
        rx_bytes=$(cat /sys/class/net/$main_if/statistics/rx_bytes 2>/dev/null)
        tx_bytes=$(cat /sys/class/net/$main_if/statistics/tx_bytes 2>/dev/null)

        # Calculate rates if we have previous values
        if [ -f "/tmp/monitor_rx_bytes" ] && [ -f "/tmp/monitor_tx_bytes" ]; then
            prev_rx=$(cat /tmp/monitor_rx_bytes)
            prev_tx=$(cat /tmp/monitor_tx_bytes)

            rx_rate=$(( (rx_bytes - prev_rx) / REFRESH_INTERVAL ))
            tx_rate=$(( (tx_bytes - prev_tx) / REFRESH_INTERVAL ))

            printf "│ Interface %s:  " "$main_if"
            printf "RX: %10s/s  TX: %10s/s" "$(format_bytes $rx_rate)" "$(format_bytes $tx_rate)"
            printf "%*s│\n" 21 ""
        else
            printf "│ Interface %s:  Calculating..." "$main_if"
            printf "%*s│\n" 39 ""
        fi

        # Save current values
        echo $rx_bytes > /tmp/monitor_rx_bytes
        echo $tx_bytes > /tmp/monitor_tx_bytes
    fi

    # Connection counts
    established=$(ss -tun | grep -c ESTAB)
    time_wait=$(ss -tun | grep -c TIME-WAIT)
    listen=$(ss -tln | grep -c LISTEN)

    printf "│ Connections:       "
    printf "ESTABLISHED: %3d  TIME_WAIT: %3d  LISTENING: %3d" "$established" "$time_wait" "$listen"
    printf "%*s│\n" 11 ""

    echo "└──────────────────────────────────────────────────────────────────────────────┘"
    echo ""

    # Top Processes
    echo -e "${CYAN}▶ TOP PROCESSES${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"
    echo "│ CPU Usage:                                                                   │"

    ps aux --sort=-%cpu | head -4 | tail -3 | while read line; do
        user=$(echo $line | awk '{print $1}')
        cpu=$(echo $line | awk '{print $3}')
        mem=$(echo $line | awk '{print $4}')
        cmd=$(echo $line | awk '{for(i=11;i<=NF;i++) printf "%s ", $i; print ""}' | cut -c1-40)
        printf "│   %-40s CPU:%5s%% MEM:%5s%%    │\n" "$cmd" "$cpu" "$mem"
    done

    echo "├──────────────────────────────────────────────────────────────────────────────┤"
    echo "│ Memory Usage:                                                                │"

    ps aux --sort=-%mem | head -4 | tail -3 | while read line; do
        user=$(echo $line | awk '{print $1}')
        cpu=$(echo $line | awk '{print $3}')
        mem=$(echo $line | awk '{print $4}')
        cmd=$(echo $line | awk '{for(i=11;i<=NF;i++) printf "%s ", $i; print ""}' | cut -c1-40)
        printf "│   %-40s MEM:%5s%% CPU:%5s%%    │\n" "$cmd" "$mem" "$cpu"
    done

    echo "└──────────────────────────────────────────────────────────────────────────────┘"
    echo ""

    # Alerts Section
    if [ ${#ALERTS[@]} -gt 0 ]; then
        echo -e "${RED}▶ ALERTS${NC}"
        echo "┌──────────────────────────────────────────────────────────────────────────────┐"
        for alert in "${ALERTS[@]}"; do
            printf "│ ${RED}⚠${NC}  %-72s │\n" "$alert"
        done
        echo "└──────────────────────────────────────────────────────────────────────────────┘"
        echo ""
    fi

    # Recent Log Entries
    echo -e "${CYAN}▶ RECENT LOG ACTIVITY${NC}"
    echo "┌──────────────────────────────────────────────────────────────────────────────┐"

    if [ -f "$LOG_FILE" ]; then
        # Get last 3 non-stack-trace lines
        tail -20 "$LOG_FILE" | grep -E "^\[" | tail -3 | while IFS= read -r line; do
            # Extract timestamp and message
            timestamp=$(echo "$line" | grep -oE '^\[[^]]+\]' | sed 's/[][]//g')
            level=$(echo "$line" | grep -oE 'local\.[A-Z]+' | sed 's/local\.//')
            message=$(echo "$line" | sed 's/^[^:]*: //' | cut -c1-50)

            # Color based on level
            case "$level" in
                ERROR|CRITICAL)
                    color="${RED}"
                    ;;
                WARNING)
                    color="${YELLOW}"
                    ;;
                *)
                    color="${NC}"
                    ;;
            esac

            printf "│ ${color}%-7s${NC} %-68s │\n" "$level" "$message..."
        done
    else
        printf "│ %-76s │\n" "Log file not found"
    fi

    echo "└──────────────────────────────────────────────────────────────────────────────┘"

    # Footer
    echo ""
    echo -e "${WHITE}Press [Ctrl+C] to stop monitoring${NC}"

    # Wait before refresh
    sleep $REFRESH_INTERVAL
done