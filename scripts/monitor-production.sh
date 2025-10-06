#!/bin/bash
#
# Production Monitoring Script - Phase 1 Security Fixes
# Created: 2025-10-01
# Purpose: Real-time monitoring for new security and reliability components
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/storage/logs"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

print_header() {
    echo ""
    echo "═══════════════════════════════════════════════════"
    print_status "$BLUE" "$1"
    echo "═══════════════════════════════════════════════════"
}

# Check if running as root or with appropriate permissions
check_permissions() {
    if [ ! -r "$LOG_DIR/laravel.log" ]; then
        print_status "$RED" "❌ Cannot read log files. Run with appropriate permissions."
        exit 1
    fi
}

# System Health Check
check_system_health() {
    print_header "🏥 System Health Check"

    # PHP-FPM Status
    if systemctl is-active --quiet php8.3-fpm; then
        print_status "$GREEN" "✅ PHP-FPM: Running"
        WORKERS=$(pgrep -c php-fpm || echo "0")
        echo "   Workers: $WORKERS"
    else
        print_status "$RED" "❌ PHP-FPM: Not running"
    fi

    # Redis Status
    if redis-cli ping > /dev/null 2>&1; then
        print_status "$GREEN" "✅ Redis: Connected"
        REDIS_KEYS=$(redis-cli dbsize | awk '{print $2}')
        echo "   Keys: $REDIS_KEYS"
    else
        print_status "$RED" "❌ Redis: Not responding"
    fi

    # Disk Space
    DISK_USED=$(df -h "$PROJECT_ROOT" | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USED" -lt 80 ]; then
        print_status "$GREEN" "✅ Disk Space: ${DISK_USED}% used"
    else
        print_status "$YELLOW" "⚠️  Disk Space: ${DISK_USED}% used (warning)"
    fi

    # Memory Usage
    MEM_USED=$(free | awk 'NR==2 {printf "%.0f", $3/$2 * 100}')
    if [ "$MEM_USED" -lt 85 ]; then
        print_status "$GREEN" "✅ Memory: ${MEM_USED}% used"
    else
        print_status "$YELLOW" "⚠️  Memory: ${MEM_USED}% used (warning)"
    fi
}

# Security Component Monitoring
check_security_components() {
    print_header "🔐 Security Components"

    # Log Sanitization Check
    print_status "$BLUE" "📝 Log Sanitization:"
    REDACTED_COUNT=$(grep -c "REDACTED" "$LOG_DIR/laravel.log" 2>/dev/null || echo "0")
    if [ "$REDACTED_COUNT" -gt 0 ]; then
        print_status "$GREEN" "   ✅ Active: $REDACTED_COUNT redactions found"
    else
        print_status "$YELLOW" "   ⚠️  No redactions found yet (may be normal if no PII logged)"
    fi

    # Check for PII leakage (should find NONE)
    EMAIL_LEAK=$(grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" "$LOG_DIR/laravel.log" 2>/dev/null | grep -v "REDACTED" | wc -l || echo "0")
    if [ "$EMAIL_LEAK" -eq 0 ]; then
        print_status "$GREEN" "   ✅ No email leakage detected"
    else
        print_status "$RED" "   ❌ CRITICAL: $EMAIL_LEAK potential email leaks found!"
    fi

    # Rate Limiting Check
    print_status "$BLUE" "⏱️  Rate Limiting:"
    RATE_LIMIT_BLOCKS=$(grep "Rate limit exceeded" "$LOG_DIR/laravel.log" 2>/dev/null | wc -l || echo "0")
    if [ "$RATE_LIMIT_BLOCKS" -eq 0 ]; then
        print_status "$GREEN" "   ✅ No rate limit violations"
    else
        print_status "$YELLOW" "   ⚠️  $RATE_LIMIT_BLOCKS rate limit blocks (check if legitimate)"
    fi

    # Multi-Tenant Cache Isolation
    print_status "$BLUE" "🏢 Multi-Tenant Cache:"
    CACHE_KEYS=$(redis-cli keys "*cal_slots*" 2>/dev/null | wc -l || echo "0")
    if [ "$CACHE_KEYS" -gt 0 ]; then
        print_status "$GREEN" "   ✅ Active: $CACHE_KEYS cache keys found"
        # Check for tenant-specific keys
        TENANT_KEYS=$(redis-cli keys "*cal_slots_*_*_*" 2>/dev/null | wc -l || echo "0")
        if [ "$TENANT_KEYS" -gt 0 ]; then
            print_status "$GREEN" "   ✅ Tenant isolation: $TENANT_KEYS tenant-specific keys"
        fi
    else
        print_status "$YELLOW" "   ⚠️  No cache keys yet (normal if no recent bookings)"
    fi
}

# Reliability Component Monitoring
check_reliability_components() {
    print_header "🛡️  Reliability Components"

    # Circuit Breaker Status
    print_status "$BLUE" "🔄 Circuit Breaker:"
    CB_STATE=$(redis-cli get "circuit_breaker:calcom_api:state" 2>/dev/null || echo "unknown")
    case "$CB_STATE" in
        "closed"|"")
            print_status "$GREEN" "   ✅ State: CLOSED (normal operation)"
            ;;
        "half_open")
            print_status "$YELLOW" "   ⚠️  State: HALF_OPEN (testing recovery)"
            ;;
        "open")
            print_status "$RED" "   ❌ State: OPEN (service degraded)"
            ;;
        *)
            print_status "$YELLOW" "   ⚠️  State: Unknown (not initialized yet)"
            ;;
    esac

    CB_FAILURES=$(redis-cli get "circuit_breaker:calcom_api:failures" 2>/dev/null || echo "0")
    echo "   Failures: $CB_FAILURES / 5"

    # Business Hours Adjustments
    print_status "$BLUE" "⏰ Business Hours Adjustments:"
    HOURS_ADJUSTED=$(grep "Auto-adjusted request time" "$LOG_DIR/laravel.log" 2>/dev/null | wc -l || echo "0")
    if [ "$HOURS_ADJUSTED" -gt 0 ]; then
        print_status "$GREEN" "   ✅ Active: $HOURS_ADJUSTED adjustments made"
    else
        print_status "$YELLOW" "   ⚠️  No adjustments yet (normal if all requests within hours)"
    fi

    # Cal.com Error Handling
    print_status "$BLUE" "📡 Cal.com Error Handling:"
    CALCOM_ERRORS=$(grep "CalcomApiException" "$LOG_DIR/laravel.log" 2>/dev/null | wc -l || echo "0")
    if [ "$CALCOM_ERRORS" -eq 0 ]; then
        print_status "$GREEN" "   ✅ No Cal.com API errors"
    else
        print_status "$YELLOW" "   ⚠️  $CALCOM_ERRORS Cal.com errors handled"
    fi
}

# Recent Errors Check
check_recent_errors() {
    print_header "🚨 Recent Errors (Last 50 Lines)"

    CRITICAL_ERRORS=$(tail -50 "$LOG_DIR/laravel.log" | grep "CRITICAL" | wc -l || echo "0")
    ERRORS=$(tail -50 "$LOG_DIR/laravel.log" | grep "ERROR" | wc -l || echo "0")

    if [ "$CRITICAL_ERRORS" -gt 0 ]; then
        print_status "$RED" "❌ CRITICAL: $CRITICAL_ERRORS critical errors found!"
        echo "Recent critical errors:"
        tail -50 "$LOG_DIR/laravel.log" | grep "CRITICAL" | tail -3
    elif [ "$ERRORS" -gt 0 ]; then
        print_status "$YELLOW" "⚠️  $ERRORS errors found (review recommended)"
        # Filter out known non-critical errors (horizon namespace)
        NON_HORIZON=$(tail -50 "$LOG_DIR/laravel.log" | grep "ERROR" | grep -v "horizon" | wc -l)
        if [ "$NON_HORIZON" -gt 0 ]; then
            echo "Recent errors (excluding horizon):"
            tail -50 "$LOG_DIR/laravel.log" | grep "ERROR" | grep -v "horizon" | tail -3
        else
            print_status "$GREEN" "   ✅ All errors are non-critical (horizon namespace)"
        fi
    else
        print_status "$GREEN" "✅ No critical errors in recent logs"
    fi
}

# API Health Check
check_api_health() {
    print_header "🌐 API Health Check"

    HEALTH_RESPONSE=$(curl -s -w "\n%{http_code}" https://api.askproai.de/api/health/detailed 2>/dev/null || echo -e "\n000")
    HTTP_CODE=$(echo "$HEALTH_RESPONSE" | tail -1)

    if [ "$HTTP_CODE" = "200" ]; then
        print_status "$GREEN" "✅ API responding: HTTP $HTTP_CODE"

        # Parse health response
        HEALTH_JSON=$(echo "$HEALTH_RESPONSE" | sed '$ d')

        # Check database
        DB_STATUS=$(echo "$HEALTH_JSON" | grep -o '"database":{"status":"[^"]*"' | cut -d'"' -f6)
        if [ "$DB_STATUS" = "healthy" ]; then
            print_status "$GREEN" "   ✅ Database: $DB_STATUS"
        else
            print_status "$RED" "   ❌ Database: $DB_STATUS"
        fi

        # Check cache
        CACHE_STATUS=$(echo "$HEALTH_JSON" | grep -o '"cache":{"status":"[^"]*"' | cut -d'"' -f6)
        if [ "$CACHE_STATUS" = "healthy" ]; then
            print_status "$GREEN" "   ✅ Cache: $CACHE_STATUS"
        else
            print_status "$RED" "   ❌ Cache: $CACHE_STATUS"
        fi
    else
        print_status "$RED" "❌ API not responding correctly: HTTP $HTTP_CODE"
    fi
}

# Component Integration Status
check_component_integration() {
    print_header "🔧 Component Integration Status"

    # Check if new classes are loaded
    print_status "$BLUE" "📦 New Classes:"

    CLASSES=(
        "App\\Helpers\\LogSanitizer"
        "App\\Http\\Middleware\\RetellCallRateLimiter"
        "App\\Http\\Requests\\CollectAppointmentRequest"
        "App\\Exceptions\\CalcomApiException"
        "App\\Services\\CircuitBreaker"
    )

    for class in "${CLASSES[@]}"; do
        if php -r "class_exists('$class', true);" 2>/dev/null; then
            print_status "$GREEN" "   ✅ $(basename ${class//\\//})"
        else
            print_status "$RED" "   ❌ $(basename ${class//\\//}) not loaded"
        fi
    done

    # Check middleware registration
    print_status "$BLUE" "🔌 Middleware:"
    if grep -q "retell.call.ratelimit" "$PROJECT_ROOT/routes/api.php" 2>/dev/null; then
        print_status "$GREEN" "   ✅ RetellCallRateLimiter applied to routes"
    else
        print_status "$RED" "   ❌ RetellCallRateLimiter not applied"
    fi
}

# Main monitoring function
main() {
    check_permissions

    clear
    print_status "$BLUE" "╔════════════════════════════════════════════════════════╗"
    print_status "$BLUE" "║  Production Monitoring - Phase 1 Security Fixes       ║"
    print_status "$BLUE" "║  Timestamp: $(date '+%Y-%m-%d %H:%M:%S %Z')                    ║"
    print_status "$BLUE" "╚════════════════════════════════════════════════════════╝"

    check_system_health
    check_api_health
    check_security_components
    check_reliability_components
    check_component_integration
    check_recent_errors

    print_header "📊 Monitoring Summary"
    print_status "$GREEN" "✅ Monitoring complete. Check above for any warnings or errors."
    echo ""
    print_status "$BLUE" "💡 Tip: Run 'watch -n 30 $0' for continuous monitoring every 30 seconds"
    echo ""
}

# Run main function
main "$@"
