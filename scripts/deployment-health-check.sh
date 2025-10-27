#!/bin/bash

################################################################################
# DEPLOYMENT HEALTH CHECK SCRIPT
#
# Purpose: Verify that all deployment fixes are in place and working
# Usage: bash scripts/deployment-health-check.sh
#
# This script checks:
# 1. File ownership correctness
# 2. PHP-FPM is running
# 3. OPCache is functional
# 4. Bootstrap cache is writable and fresh
# 5. Routes cache is recent
# 6. FastCGI socket is accessible
# 7. Nginx configuration is valid
# 8. Laravel can write logs
################################################################################

set +e  # Don't exit on errors, we want to report all issues

APP_DIR="/var/www/api-gateway"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
ISSUES_FOUND=0
WARNINGS=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "==============================================="
echo "DEPLOYMENT HEALTH CHECK"
echo "==============================================="
echo "Time: $TIMESTAMP"
echo "Environment: $(hostname)"
echo ""

# Helper functions
check_pass() {
    echo -e "${GREEN}✅ PASS${NC}: $1"
}

check_fail() {
    echo -e "${RED}❌ FAIL${NC}: $1"
    ((ISSUES_FOUND++))
}

check_warn() {
    echo -e "${YELLOW}⚠️  WARN${NC}: $1"
    ((WARNINGS++))
}

################################################################################
# CHECK 1: File Ownership
################################################################################
echo ""
echo "1. FILE OWNERSHIP CHECKS"
echo "========================"

# Check RetellFunctionCallHandler
OWNER=$(stat -c '%U:%G' "$APP_DIR/app/Http/Controllers/RetellFunctionCallHandler.php" 2>/dev/null)
if [ "$OWNER" = "www-data:www-data" ]; then
    check_pass "RetellFunctionCallHandler.php is www-data:www-data"
else
    check_fail "RetellFunctionCallHandler.php is $OWNER (should be www-data:www-data)"
fi

# Check bootstrap/cache
OWNER=$(stat -c '%U:%G' "$APP_DIR/bootstrap/cache" 2>/dev/null)
if [[ "$OWNER" =~ "www-data" ]]; then
    check_pass "bootstrap/cache directory is writable by www-data"
else
    check_fail "bootstrap/cache ownership is $OWNER"
fi

# Count root-owned files in app directory
ROOT_FILES=$(find "$APP_DIR/app" -type f -user root 2>/dev/null | wc -l)
if [ "$ROOT_FILES" -eq 0 ]; then
    check_pass "No root-owned PHP files found in app/"
else
    check_warn "$ROOT_FILES files in app/ are still owned by root"
fi

# Count root-owned files in bootstrap
ROOT_BOOTSTRAP=$(find "$APP_DIR/bootstrap" -type f -user root 2>/dev/null | wc -l)
if [ "$ROOT_BOOTSTRAP" -eq 0 ]; then
    check_pass "No root-owned files in bootstrap/"
else
    check_warn "$ROOT_BOOTSTRAP files in bootstrap/ are still owned by root"
fi

################################################################################
# CHECK 2: PHP-FPM Status
################################################################################
echo ""
echo "2. PHP-FPM STATUS CHECKS"
echo "========================"

# Check if PHP-FPM is running
if systemctl is-active --quiet php8.3-fpm; then
    check_pass "PHP-FPM service is running"
else
    check_fail "PHP-FPM service is NOT running"
fi

# Check number of PHP-FPM workers
WORKER_COUNT=$(ps aux | grep -c "php-fpm: pool www")
if [ "$WORKER_COUNT" -ge 5 ]; then
    check_pass "PHP-FPM has $WORKER_COUNT worker processes"
else
    check_warn "PHP-FPM has only $WORKER_COUNT workers (expected 5+)"
fi

# Check PHP version
PHP_VERSION=$(php -v 2>/dev/null | head -1 | grep -oP 'PHP \K[0-9]+\.[0-9]+\.[0-9]+')
check_pass "PHP version: $PHP_VERSION"

################################################################################
# CHECK 3: OPCache Status
################################################################################
echo ""
echo "3. OPCACHE STATUS CHECKS"
echo "========================"

# Check OPCache enabled
OPCACHE_STATUS=$(php -r "echo (function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled']) ? 'enabled' : 'disabled';" 2>/dev/null)
if [ "$OPCACHE_STATUS" = "enabled" ]; then
    check_pass "OPCache is enabled"

    # Get cache stats
    CACHED=$(php -r "if(function_exists('opcache_get_status'))\$s=opcache_get_status();echo(\$s?'Y':'N');" 2>/dev/null)
    if [ "$CACHED" = "Y" ]; then
        check_pass "OPCache is caching PHP files"
    fi
else
    check_warn "OPCache is disabled or not available"
fi

################################################################################
# CHECK 4: Bootstrap Cache
################################################################################
echo ""
echo "4. BOOTSTRAP CACHE CHECKS"
echo "========================="

# Check if cache directory exists
if [ -d "$APP_DIR/bootstrap/cache" ]; then
    check_pass "bootstrap/cache directory exists"
else
    check_fail "bootstrap/cache directory does not exist"
fi

# Check if cache directory is writable
if [ -w "$APP_DIR/bootstrap/cache" ]; then
    check_pass "bootstrap/cache is writable"
else
    check_fail "bootstrap/cache is NOT writable"
fi

# Check for cached files
CONFIG_CACHE="$APP_DIR/bootstrap/cache/config.php"
ROUTES_CACHE="$APP_DIR/bootstrap/cache/routes-v7.php"
PACKAGES_CACHE="$APP_DIR/bootstrap/cache/packages.php"

if [ -f "$CONFIG_CACHE" ]; then
    SIZE=$(du -h "$CONFIG_CACHE" | cut -f1)
    MTIME=$(stat -c '%y' "$CONFIG_CACHE" | cut -d. -f1)
    check_pass "Config cache exists ($SIZE, modified: $MTIME)"
else
    check_warn "Config cache not found"
fi

if [ -f "$ROUTES_CACHE" ]; then
    SIZE=$(du -h "$ROUTES_CACHE" | cut -f1)
    MTIME=$(stat -c '%y' "$ROUTES_CACHE" | cut -d. -f1)
    check_pass "Routes cache exists ($SIZE, modified: $MTIME)"
else
    check_warn "Routes cache not found"
fi

################################################################################
# CHECK 5: FastCGI Socket
################################################################################
echo ""
echo "5. FASTCGI SOCKET CHECKS"
echo "========================"

SOCKET="/run/php/php8.3-fpm.sock"

if [ -S "$SOCKET" ]; then
    check_pass "FastCGI socket exists"

    # Check socket permissions
    PERMS=$(stat -c '%a' "$SOCKET")
    if [ "$PERMS" = "660" ] || [ "$PERMS" = "644" ]; then
        check_pass "FastCGI socket has correct permissions ($PERMS)"
    else
        check_warn "FastCGI socket permissions are $PERMS (expected 660 or 644)"
    fi
else
    check_fail "FastCGI socket does not exist"
fi

################################################################################
# CHECK 6: Nginx Configuration
################################################################################
echo ""
echo "6. NGINX CONFIGURATION CHECKS"
echo "============================="

# Test nginx config
if nginx -t 2>&1 | grep -q "successful"; then
    check_pass "Nginx configuration is valid"
else
    check_fail "Nginx configuration is invalid"
fi

# Check if nginx is running
if systemctl is-active --quiet nginx; then
    check_pass "Nginx is running"
else
    check_fail "Nginx is NOT running"
fi

################################################################################
# CHECK 7: Laravel Logs
################################################################################
echo ""
echo "7. LARAVEL LOG CHECKS"
echo "====================="

LOG_DIR="$APP_DIR/storage/logs"
LOG_FILE="$LOG_DIR/laravel.log"

if [ -d "$LOG_DIR" ]; then
    if [ -w "$LOG_DIR" ]; then
        check_pass "Laravel logs directory is writable"
    else
        check_fail "Laravel logs directory is NOT writable"
    fi
else
    check_fail "Laravel logs directory does not exist"
fi

if [ -f "$LOG_FILE" ]; then
    SIZE=$(du -h "$LOG_FILE" | cut -f1)
    ERRORS=$(grep -c "ERROR\|CRITICAL" "$LOG_FILE" 2>/dev/null || echo "0")
    check_pass "Laravel log file exists ($SIZE, $ERRORS errors)"

    # Check for recent errors
    RECENT_ERRORS=$(grep "ERROR\|CRITICAL" "$LOG_FILE" 2>/dev/null | tail -1)
    if [ ! -z "$RECENT_ERRORS" ]; then
        check_warn "Recent errors in log: $(echo $RECENT_ERRORS | cut -c1-80)..."
    fi
else
    check_warn "Laravel log file does not exist"
fi

################################################################################
# CHECK 8: Recent Code Changes
################################################################################
echo ""
echo "8. RECENT CODE CHANGES"
echo "======================"

RETELL_FILE="$APP_DIR/app/Http/Controllers/RetellFunctionCallHandler.php"
if [ -f "$RETELL_FILE" ]; then
    MTIME=$(stat -c '%y' "$RETELL_FILE" | cut -d. -f1)
    check_pass "RetellFunctionCallHandler.php last modified: $MTIME"
fi

################################################################################
# SUMMARY
################################################################################
echo ""
echo "==============================================="
echo "SUMMARY"
echo "==============================================="

if [ "$ISSUES_FOUND" -eq 0 ] && [ "$WARNINGS" -eq 0 ]; then
    echo -e "${GREEN}✅ ALL CHECKS PASSED${NC}"
    echo ""
    echo "Your deployment is healthy and ready."
    echo "All critical fixes are in place."
    exit 0
elif [ "$ISSUES_FOUND" -eq 0 ]; then
    echo -e "${YELLOW}⚠️  CHECKS PASSED WITH $WARNINGS WARNING(S)${NC}"
    echo ""
    echo "Your deployment is functional but has some warnings."
    echo "Consider addressing the warnings above."
    exit 0
else
    echo -e "${RED}❌ CHECKS FAILED: $ISSUES_FOUND ISSUE(S) FOUND${NC}"
    echo ""
    echo "Your deployment has critical issues that need attention."
    echo "See details above and refer to DEPLOYMENT_VERIFICATION_CHECKLIST.md"
    exit 1
fi
