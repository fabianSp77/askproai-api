#!/bin/bash

###############################################################################
# AskProAI Comprehensive System Health Check
# Version: 1.0
# Created: 2025-09-03
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="/var/www/api-gateway/storage/logs/health_check_$(date +%Y%m%d_%H%M%S).log"
ERROR_COUNT=0
WARNING_COUNT=0

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

# Function to log messages
log_message() {
    local level="$1"
    local message="$2"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$level] $message" >> "$LOG_FILE"
}

# Function to print status
print_status() {
    local status="$1"
    local message="$2"
    local details="$3"
    
    case "$status" in
        "PASS")
            echo -e "${GREEN}✓${NC} $message"
            [ -n "$details" ] && echo "  $details"
            log_message "INFO" "PASS: $message - $details"
            ;;
        "FAIL")
            echo -e "${RED}✗${NC} $message"
            [ -n "$details" ] && echo "  $details"
            log_message "ERROR" "FAIL: $message - $details"
            ((ERROR_COUNT++))
            ;;
        "WARN")
            echo -e "${YELLOW}⚠${NC} $message"
            [ -n "$details" ] && echo "  $details"
            log_message "WARN" "WARN: $message - $details"
            ((WARNING_COUNT++))
            ;;
        "INFO")
            echo -e "${BLUE}ℹ${NC} $message"
            [ -n "$details" ] && echo "  $details"
            log_message "INFO" "INFO: $message - $details"
            ;;
    esac
}

# Function to check HTTP endpoint
check_endpoint() {
    local url="$1"
    local expected_status="$2"
    local timeout="${3:-10}"
    
    local response
    response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout "$timeout" "$url" 2>/dev/null)
    
    if [ "$response" = "$expected_status" ]; then
        print_status "PASS" "HTTP $url" "Status: $response"
        return 0
    else
        print_status "FAIL" "HTTP $url" "Expected: $expected_status, Got: $response"
        return 1
    fi
}

# Function to check service status
check_service() {
    local service="$1"
    
    if systemctl is-active --quiet "$service"; then
        local uptime
        uptime=$(systemctl show "$service" --property=ActiveEnterTimestamp --value)
        print_status "PASS" "Service $service" "Active since: $uptime"
        return 0
    else
        print_status "FAIL" "Service $service" "Service is not running"
        return 1
    fi
}

# Function to check database connectivity
check_database() {
    cd "$PROJECT_ROOT"
    
    # Test basic connection
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" >/dev/null 2>&1; then
        # Get table counts
        local customers_count
        local calls_count
        local appointments_count
        
        customers_count=$(php artisan tinker --execute="echo DB::table('customers')->count();" 2>/dev/null | tail -1)
        calls_count=$(php artisan tinker --execute="echo DB::table('calls')->count();" 2>/dev/null | tail -1)
        appointments_count=$(php artisan tinker --execute="echo DB::table('appointments')->count();" 2>/dev/null | tail -1)
        
        print_status "PASS" "Database connection" "Tables: customers($customers_count), calls($calls_count), appointments($appointments_count)"
        return 0
    else
        print_status "FAIL" "Database connection" "Cannot connect to database"
        return 1
    fi
}

# Function to check Redis connectivity
check_redis() {
    if redis-cli ping >/dev/null 2>&1; then
        local info
        info=$(redis-cli info memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
        print_status "PASS" "Redis connection" "Memory usage: $info"
        return 0
    else
        print_status "FAIL" "Redis connection" "Cannot connect to Redis"
        return 1
    fi
}

# Function to check disk usage
check_disk_space() {
    local usage
    usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$usage" -lt 80 ]; then
        print_status "PASS" "Disk space" "Usage: ${usage}%"
        return 0
    elif [ "$usage" -lt 90 ]; then
        print_status "WARN" "Disk space" "Usage: ${usage}% (Warning: >80%)"
        return 1
    else
        print_status "FAIL" "Disk space" "Usage: ${usage}% (Critical: >90%)"
        return 1
    fi
}

# Function to check memory usage
check_memory() {
    local mem_info
    mem_info=$(free -h | awk 'NR==2{printf "Used: %s/%s (%.1f%%)", $3,$2,$3*100/$2}')
    print_status "INFO" "Memory usage" "$mem_info"
}

# Function to check missing assets
check_missing_assets() {
    local missing_count=0
    local assets_dir="$PROJECT_ROOT/public/build/assets"
    
    # Check if build directory exists
    if [ ! -d "$assets_dir" ]; then
        print_status "WARN" "Build assets directory" "Directory $assets_dir does not exist"
        return 1
    fi
    
    # Check for common missing assets from nginx logs
    local missing_files=(
        "wizard-progress-enhancer-BntUnTIW.js"
        "askproai-state-manager-BtNc_89J.js"
        "responsive-zoom-handler-DaecGYuG.js"
    )
    
    for file in "${missing_files[@]}"; do
        if [ ! -f "$assets_dir/$file" ]; then
            print_status "WARN" "Missing asset" "$file"
            ((missing_count++))
        fi
    done
    
    if [ $missing_count -eq 0 ]; then
        print_status "PASS" "Asset check" "All checked assets present"
        return 0
    else
        print_status "WARN" "Asset check" "$missing_count missing assets found"
        return 1
    fi
}

# Function to check Laravel configuration
check_laravel_config() {
    cd "$PROJECT_ROOT"
    
    # Check if .env exists and has required keys
    if [ ! -f ".env" ]; then
        print_status "FAIL" "Laravel .env file" "File not found"
        return 1
    fi
    
    # Check critical environment variables
    local required_vars=(
        "APP_KEY"
        "DB_CONNECTION"
        "CALCOM_API_KEY"
    )
    
    local missing_vars=()
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" .env; then
            missing_vars+=("$var")
        fi
    done
    
    if [ ${#missing_vars[@]} -eq 0 ]; then
        print_status "PASS" "Laravel configuration" "All required environment variables present"
        return 0
    else
        print_status "FAIL" "Laravel configuration" "Missing variables: ${missing_vars[*]}"
        return 1
    fi
}

# Function to check security issues
check_security() {
    local issues=0
    
    # Check for exposed .env in web root
    if curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de/.env" | grep -q "403"; then
        print_status "PASS" "Security: .env protection" "File is properly protected"
    else
        print_status "FAIL" "Security: .env protection" ".env file may be exposed"
        ((issues++))
    fi
    
    # Check for exposed .git
    if curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de/.git/config" | grep -q "403"; then
        print_status "PASS" "Security: .git protection" "Directory is properly protected"
    else
        print_status "FAIL" "Security: .git protection" ".git directory may be exposed"
        ((issues++))
    fi
    
    return $issues
}

# Function to check log file sizes
check_log_sizes() {
    local log_dir="$PROJECT_ROOT/storage/logs"
    local large_logs=()
    
    # Find logs larger than 100MB
    while IFS= read -r -d '' file; do
        large_logs+=("$(basename "$file")")
    done < <(find "$log_dir" -name "*.log" -size +100M -print0 2>/dev/null)
    
    if [ ${#large_logs[@]} -eq 0 ]; then
        print_status "PASS" "Log file sizes" "No oversized log files"
        return 0
    else
        print_status "WARN" "Log file sizes" "Large logs: ${large_logs[*]}"
        return 1
    fi
}

# Function to check queue status
check_queue_status() {
    cd "$PROJECT_ROOT"
    
    # Check if Horizon is running (if configured)
    if command -v supervisorctl >/dev/null 2>&1; then
        if supervisorctl status | grep -q horizon; then
            local horizon_status
            horizon_status=$(supervisorctl status | grep horizon | awk '{print $2}')
            if [ "$horizon_status" = "RUNNING" ]; then
                print_status "PASS" "Queue processor (Horizon)" "Status: $horizon_status"
                return 0
            else
                print_status "WARN" "Queue processor (Horizon)" "Status: $horizon_status"
                return 1
            fi
        fi
    fi
    
    # Fallback: Check queue connection
    if php artisan queue:monitor --once >/dev/null 2>&1; then
        print_status "PASS" "Queue connection" "Queue system accessible"
        return 0
    else
        print_status "WARN" "Queue connection" "Queue monitoring failed"
        return 1
    fi
}

###############################################################################
# MAIN EXECUTION
###############################################################################

echo "=========================================="
echo "  AskProAI System Health Check"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo

log_message "INFO" "Starting comprehensive health check"

# System Services
echo -e "${BLUE}=== System Services ===${NC}"
check_service "nginx"
check_service "php8.3-fpm"
check_service "mariadb"
check_service "redis-server"
echo

# HTTP Endpoints
echo -e "${BLUE}=== HTTP Endpoints ===${NC}"
check_endpoint "https://api.askproai.de" "200"
check_endpoint "https://api.askproai.de/admin" "200"
check_endpoint "https://api.askproai.de/api/health" "200"
echo

# Database & Cache
echo -e "${BLUE}=== Database & Cache ===${NC}"
check_database
check_redis
echo

# Laravel Application
echo -e "${BLUE}=== Laravel Application ===${NC}"
check_laravel_config
check_queue_status
echo

# Security
echo -e "${BLUE}=== Security Checks ===${NC}"
check_security
echo

# System Resources
echo -e "${BLUE}=== System Resources ===${NC}"
check_disk_space
check_memory
echo

# Asset Management
echo -e "${BLUE}=== Asset Management ===${NC}"
check_missing_assets
check_log_sizes
echo

# Summary
echo "=========================================="
if [ $ERROR_COUNT -eq 0 ] && [ $WARNING_COUNT -eq 0 ]; then
    echo -e "${GREEN}✓ System Health: EXCELLENT${NC}"
    log_message "INFO" "Health check completed: EXCELLENT (0 errors, 0 warnings)"
elif [ $ERROR_COUNT -eq 0 ]; then
    echo -e "${YELLOW}⚠ System Health: GOOD (${WARNING_COUNT} warnings)${NC}"
    log_message "INFO" "Health check completed: GOOD ($ERROR_COUNT errors, $WARNING_COUNT warnings)"
else
    echo -e "${RED}✗ System Health: ISSUES DETECTED (${ERROR_COUNT} errors, ${WARNING_COUNT} warnings)${NC}"
    log_message "ERROR" "Health check completed: ISSUES ($ERROR_COUNT errors, $WARNING_COUNT warnings)"
fi
echo "=========================================="
echo "Log file: $LOG_FILE"
echo

# Exit with appropriate code
if [ $ERROR_COUNT -gt 0 ]; then
    exit 1
elif [ $WARNING_COUNT -gt 0 ]; then
    exit 2
else
    exit 0
fi