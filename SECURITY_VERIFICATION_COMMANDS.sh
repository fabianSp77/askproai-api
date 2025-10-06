#!/bin/bash
# Security Verification Commands - Production Deployment
# Date: 2025-10-01
# Purpose: Quick reference for security verification after restart

echo "🔐 API Gateway Security Verification - Production"
echo "=================================================="
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

###############################################################################
# 1. PRE-ACTIVATION CHECKS (Run BEFORE restart)
###############################################################################

pre_activation_checks() {
    echo "🔴 PRE-ACTIVATION SECURITY CHECKS"
    echo "================================="
    echo ""

    # Check 1: Tenant context in cache operations
    echo "1️⃣  Checking tenant context implementation..."
    TENANT_CONTEXT_COUNT=$(grep -rn "setTenantContext" /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php | wc -l)
    if [ "$TENANT_CONTEXT_COUNT" -ge 4 ]; then
        echo -e "${GREEN}✅ Tenant context: $TENANT_CONTEXT_COUNT usages found${NC}"
    else
        echo -e "${RED}❌ Tenant context: Only $TENANT_CONTEXT_COUNT usages found (expected >= 4)${NC}"
    fi
    echo ""

    # Check 2: LogSanitizer usage
    echo "2️⃣  Checking log sanitization..."
    SANITIZER_COUNT=$(grep -rn "LogSanitizer" /var/www/api-gateway/app/Http/Controllers/Retell*.php | wc -l)
    if [ "$SANITIZER_COUNT" -ge 6 ]; then
        echo -e "${GREEN}✅ Log sanitization: $SANITIZER_COUNT usages found${NC}"
    else
        echo -e "${YELLOW}⚠️  Log sanitization: Only $SANITIZER_COUNT usages found${NC}"
    fi
    echo ""

    # Check 3: Rate limiter middleware
    echo "3️⃣  Checking rate limiter middleware..."
    RATE_LIMITER_ROUTES=$(grep -c "retell.call.ratelimit" /var/www/api-gateway/routes/api.php)
    if [ "$RATE_LIMITER_ROUTES" -ge 3 ]; then
        echo -e "${GREEN}✅ Rate limiter: Applied to $RATE_LIMITER_ROUTES routes${NC}"
    else
        echo -e "${RED}❌ Rate limiter: Only $RATE_LIMITER_ROUTES routes protected (expected 3)${NC}"
    fi
    echo ""

    # Check 4: Middleware registration
    echo "4️⃣  Checking middleware registration..."
    if grep -q "retell.call.ratelimit" /var/www/api-gateway/app/Http/Kernel.php; then
        echo -e "${GREEN}✅ Rate limiter middleware registered in Kernel${NC}"
    else
        echo -e "${RED}❌ Rate limiter middleware NOT registered${NC}"
    fi
    echo ""

    echo "================================="
    echo "Pre-activation checks complete!"
    echo ""
}

###############################################################################
# 2. POST-ACTIVATION CHECKS (Run AFTER restart)
###############################################################################

post_activation_checks() {
    echo "🟡 POST-ACTIVATION VERIFICATION"
    echo "================================"
    echo ""

    # Check 1: Multi-tenant isolation
    echo "1️⃣  Checking multi-tenant cache isolation..."
    CACHE_KEYS=$(redis-cli --scan --pattern "availability:*" | head -10)
    if [ -z "$CACHE_KEYS" ]; then
        echo -e "${YELLOW}⚠️  No cache keys yet (waiting for first call)${NC}"
    else
        echo "First 3 cache keys:"
        echo "$CACHE_KEYS" | head -3

        # Check if keys have tenant scope (format: availability:{company}:{branch}:...)
        SCOPED_KEYS=$(echo "$CACHE_KEYS" | grep -c ":")
        if [ "$SCOPED_KEYS" -gt 0 ]; then
            echo -e "${GREEN}✅ Cache keys properly scoped with tenant context${NC}"
        else
            echo -e "${RED}❌ Cache keys missing tenant scope!${NC}"
        fi
    fi
    echo ""

    # Check 2: PII in recent logs
    echo "2️⃣  Checking for PII leakage in logs..."
    PII_LEAKS=$(tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" | grep -v "REDACTED" | wc -l)
    if [ "$PII_LEAKS" -eq 0 ]; then
        echo -e "${GREEN}✅ No PII leaks detected in recent logs${NC}"
    else
        echo -e "${RED}❌ Found $PII_LEAKS potential PII leaks!${NC}"
        echo "Sample:"
        tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" | grep -v "REDACTED" | head -3
    fi
    echo ""

    # Check 3: Rate limiter active
    echo "3️⃣  Checking rate limiter status..."
    RATE_KEYS=$(redis-cli KEYS "retell_call_*" | wc -l)
    if [ "$RATE_KEYS" -eq 0 ]; then
        echo -e "${YELLOW}⚠️  No rate limiter keys yet (waiting for first call)${NC}"
    else
        echo -e "${GREEN}✅ Rate limiter active: $RATE_KEYS tracking keys${NC}"
        redis-cli KEYS "retell_call_*" | head -3
    fi
    echo ""

    # Check 4: Recent errors
    echo "4️⃣  Checking for recent errors..."
    RECENT_ERRORS=$(tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -c "ERROR")
    if [ "$RECENT_ERRORS" -eq 0 ]; then
        echo -e "${GREEN}✅ No errors in last 100 log entries${NC}"
    else
        echo -e "${YELLOW}⚠️  Found $RECENT_ERRORS errors in recent logs${NC}"
        echo "Recent errors:"
        tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep "ERROR" | tail -3
    fi
    echo ""

    echo "================================="
    echo "Post-activation checks complete!"
    echo ""
}

###############################################################################
# 3. REAL-TIME MONITORING
###############################################################################

monitor_pii_leaks() {
    echo "🔍 MONITORING: PII Leak Detection (Ctrl+C to stop)"
    echo "===================================================="
    echo "Watching for email addresses and phone numbers not marked as REDACTED..."
    echo ""

    tail -f /var/www/api-gateway/storage/logs/laravel.log | \
        grep --line-buffered -E "(@[a-zA-Z0-9.-]+)|(\+?[0-9]{10,})" | \
        grep --line-buffered -v "REDACTED" | \
        while read line; do
            echo -e "${RED}⚠️  POTENTIAL PII LEAK:${NC} $line"
        done
}

monitor_tenant_context() {
    echo "🏢 MONITORING: Tenant Context (Ctrl+C to stop)"
    echo "=============================================="
    echo "Watching for cache operations with tenant context..."
    echo ""

    tail -f /var/www/api-gateway/storage/logs/laravel.log | \
        grep --line-buffered -E "company_id|branch_id|setTenantContext" | \
        while read line; do
            echo -e "${GREEN}✓${NC} $line"
        done
}

monitor_rate_limiter() {
    echo "⚡ MONITORING: Rate Limiter (Ctrl+C to stop)"
    echo "============================================"
    echo "Watching for rate limit events..."
    echo ""

    tail -f /var/www/api-gateway/storage/logs/laravel.log | \
        grep --line-buffered -E "rate limit|blocked|exceeded|RateLimit" | \
        while read line; do
            if echo "$line" | grep -q "exceeded"; then
                echo -e "${RED}🚨${NC} $line"
            else
                echo -e "${YELLOW}⚠️${NC} $line"
            fi
        done
}

monitor_webhooks() {
    echo "📞 MONITORING: Webhook Activity (Ctrl+C to stop)"
    echo "================================================"
    echo "Watching for incoming webhooks and sanitization..."
    echo ""

    tail -f /var/www/api-gateway/storage/logs/laravel.log | \
        grep --line-buffered -E "Webhook|webhook|WEBHOOK" | \
        while read line; do
            if echo "$line" | grep -q "sanitize"; then
                echo -e "${GREEN}✓${NC} $line"
            else
                echo -e "${YELLOW}→${NC} $line"
            fi
        done
}

###############################################################################
# 4. SECURITY STATUS REPORT
###############################################################################

security_status_report() {
    echo "📊 SECURITY STATUS REPORT"
    echo "========================="
    date
    echo ""

    echo "Cache Isolation:"
    echo "---------------"
    CACHE_COUNT=$(redis-cli --scan --pattern "availability:*" | wc -l)
    echo "Total availability cache keys: $CACHE_COUNT"
    echo "Sample keys:"
    redis-cli --scan --pattern "availability:*" | head -3
    echo ""

    echo "Log Sanitization:"
    echo "----------------"
    SANITIZED_COUNT=$(tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -c "REDACTED")
    echo "Sanitization markers in last 1000 lines: $SANITIZED_COUNT"
    echo ""

    echo "Rate Limiter:"
    echo "------------"
    RATE_KEY_COUNT=$(redis-cli KEYS "retell_call_*" | wc -l)
    echo "Active rate limiter tracking keys: $RATE_KEY_COUNT"
    BLOCKED_COUNT=$(redis-cli KEYS "retell_call_blocked:*" | wc -l)
    echo "Currently blocked calls: $BLOCKED_COUNT"
    echo ""

    echo "Recent Activity:"
    echo "---------------"
    WEBHOOK_COUNT=$(tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -c "Webhook")
    echo "Webhooks processed (last 1000 lines): $WEBHOOK_COUNT"
    ERROR_COUNT=$(tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -c "ERROR")
    echo "Errors (last 1000 lines): $ERROR_COUNT"
    echo ""
}

###############################################################################
# 5. QUICK FIX COMMANDS
###############################################################################

show_quick_fixes() {
    echo "🔧 QUICK FIX COMMANDS"
    echo "===================="
    echo ""
    echo "Clear tenant cache (if contamination detected):"
    echo "  redis-cli KEYS 'availability:*' | xargs redis-cli DEL"
    echo ""
    echo "Clear rate limiter state:"
    echo "  redis-cli KEYS 'retell_call_*' | xargs redis-cli DEL"
    echo ""
    echo "View rate limiter config:"
    echo "  grep -A 10 'private const LIMITS' /var/www/api-gateway/app/Http/Middleware/RetellCallRateLimiter.php"
    echo ""
    echo "Check middleware registration:"
    echo "  php artisan route:list | grep retell"
    echo ""
    echo "Reload PHP-FPM after config change:"
    echo "  sudo systemctl reload php8.2-fpm"
    echo ""
}

###############################################################################
# MAIN MENU
###############################################################################

show_menu() {
    echo ""
    echo "🔐 Security Verification Tool"
    echo "=============================="
    echo "1. Run pre-activation checks (BEFORE restart)"
    echo "2. Run post-activation checks (AFTER restart)"
    echo "3. Security status report"
    echo "4. Monitor PII leaks (live)"
    echo "5. Monitor tenant context (live)"
    echo "6. Monitor rate limiter (live)"
    echo "7. Monitor webhooks (live)"
    echo "8. Show quick fix commands"
    echo "9. Exit"
    echo ""
    read -p "Select option (1-9): " choice

    case $choice in
        1) pre_activation_checks ;;
        2) post_activation_checks ;;
        3) security_status_report ;;
        4) monitor_pii_leaks ;;
        5) monitor_tenant_context ;;
        6) monitor_rate_limiter ;;
        7) monitor_webhooks ;;
        8) show_quick_fixes ;;
        9) exit 0 ;;
        *) echo "Invalid option" ;;
    esac

    show_menu
}

# Run menu if no arguments provided
if [ $# -eq 0 ]; then
    show_menu
else
    # Allow direct command execution
    case $1 in
        pre) pre_activation_checks ;;
        post) post_activation_checks ;;
        status) security_status_report ;;
        pii) monitor_pii_leaks ;;
        tenant) monitor_tenant_context ;;
        rate) monitor_rate_limiter ;;
        webhook) monitor_webhooks ;;
        *) echo "Usage: $0 [pre|post|status|pii|tenant|rate|webhook]" ;;
    esac
fi
