#!/bin/bash
#
# Appointment #702 Fix Verification Script
# Tests that appointment editing is working after PHP-FPM restart
#
# Usage: bash tests/verify-appointment-702-fix.sh
#

set -e

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Appointment #702 Fix Verification"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test result
test_result() {
    local name="$1"
    local expected="$2"
    local actual="$3"

    if [ "$expected" == "$actual" ]; then
        echo -e "${GREEN}✅ PASS${NC}: $name"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ FAIL${NC}: $name"
        echo "   Expected: $expected"
        echo "   Actual: $actual"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

# Test 1: PHP-FPM Service Status
echo "Test 1: PHP-FPM Service Status..."
if systemctl is-active --quiet php8.3-fpm; then
    test_result "PHP-FPM is running" "active" "active"
else
    test_result "PHP-FPM is running" "active" "inactive"
fi
echo ""

# Test 2: Check for error in logs (last 100 lines)
echo "Test 2: No 'inline does not exist' errors in recent logs..."
ERROR_COUNT=$(tail -100 /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | \
    grep -c "inline does not exist" || echo "0")

if [ "$ERROR_COUNT" -eq 0 ]; then
    test_result "No inline method errors" "0" "0"
else
    test_result "No inline method errors" "0" "$ERROR_COUNT"
    echo -e "   ${YELLOW}Note: Check if these are old cached errors${NC}"
fi
echo ""

# Test 3: PHP OPcache Status
echo "Test 3: PHP OPcache Status..."
OPCACHE_STATUS=$(php -r "echo (function_exists('opcache_get_status') && opcache_get_status()) ? 'enabled' : 'disabled';")
echo "   OPcache: $OPCACHE_STATUS"
if [ "$OPCACHE_STATUS" == "enabled" ]; then
    OPCACHE_MEMORY=$(php -r "\$s = opcache_get_status(); echo isset(\$s['memory_usage']['used_memory']) ? round(\$s['memory_usage']['used_memory']/1024/1024, 2) . 'MB' : 'unknown';")
    echo "   Memory Used: $OPCACHE_MEMORY"
fi
echo ""

# Test 4: Laravel Cache Status
echo "Test 4: Laravel Cache Status..."
CACHE_DRIVER=$(php artisan tinker --execute="echo config('cache.default');" 2>/dev/null | tail -1)
echo "   Cache Driver: $CACHE_DRIVER"
echo ""

# Test 5: AppointmentResource.php exists and is readable
echo "Test 5: AppointmentResource.php file integrity..."
FILE_PATH="/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php"
if [ -f "$FILE_PATH" ]; then
    FILE_SIZE=$(stat -f%z "$FILE_PATH" 2>/dev/null || stat -c%s "$FILE_PATH" 2>/dev/null)
    LINE_COUNT=$(wc -l < "$FILE_PATH")

    test_result "AppointmentResource.php exists" "true" "true"
    echo "   File size: $FILE_SIZE bytes"
    echo "   Lines: $LINE_COUNT"

    # Check for inline method in current file
    if grep -q "->inline()" "$FILE_PATH"; then
        echo -e "   ${RED}⚠️  WARNING: Found ->inline() in current file!${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    else
        echo -e "   ${GREEN}✓${NC} No ->inline() method found in current file"
    fi
else
    test_result "AppointmentResource.php exists" "true" "false"
fi
echo ""

# Test 6: Database connection
echo "Test 6: Database Connection..."
DB_STATUS=$(php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'connected';
} catch (Exception \$e) {
    echo 'failed: ' . \$e->getMessage();
}
" 2>/dev/null | tail -1)

if [ "$DB_STATUS" == "connected" ]; then
    test_result "Database connection" "connected" "connected"
else
    test_result "Database connection" "connected" "$DB_STATUS"
fi
echo ""

# Test 7: Appointment #702 exists in database
echo "Test 7: Appointment #702 in Database..."
APPT_EXISTS=$(php artisan tinker --execute="
try {
    \$appt = App\\Models\\Appointment::find(702);
    if (\$appt) {
        echo 'exists|' . \$appt->id . '|' . \$appt->customer->name . '|' . \$appt->starts_at;
    } else {
        echo 'not_found';
    }
} catch (Exception \$e) {
    echo 'error: ' . \$e->getMessage();
}
" 2>/dev/null | tail -1)

if [[ "$APPT_EXISTS" == exists* ]]; then
    IFS='|' read -r status id customer starts_at <<< "$APPT_EXISTS"
    test_result "Appointment #702 exists" "exists" "exists"
    echo "   ID: $id"
    echo "   Customer: $customer"
    echo "   Starts: $starts_at"
else
    test_result "Appointment #702 exists" "exists" "$APPT_EXISTS"
fi
echo ""

# Test 8: Filament version check
echo "Test 8: Filament Version Check..."
FILAMENT_VERSION=$(php artisan tinker --execute="
try {
    \$composer = json_decode(file_get_contents(base_path('composer.json')), true);
    echo \$composer['require']['filament/filament'] ?? 'not_found';
} catch (Exception \$e) {
    echo 'error';
}
" 2>/dev/null | tail -1)

echo "   Filament Version: $FILAMENT_VERSION"
if [[ "$FILAMENT_VERSION" == ^3* ]]; then
    echo -e "   ${GREEN}✓${NC} Filament 3.x detected"
    echo -e "   ${YELLOW}Note: DatePicker->inline() is NOT available in Filament 3.x${NC}"
else
    echo -e "   ${YELLOW}⚠️  Unknown Filament version${NC}"
fi
echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Test Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED${NC}"
    echo ""
    echo "System Status: OPERATIONAL"
    echo ""
    echo "Next Steps:"
    echo "1. 🌐 Navigate to: https://api.askproai.de/admin/appointments/702/edit"
    echo "2. 👀 Verify page loads without 500 error"
    echo "3. ✏️  Try changing the appointment date/time"
    echo "4. 💾 Save and verify changes persist"
    echo ""
    echo "If manual testing succeeds: ✅ Fix confirmed"
    echo "If manual testing fails: 🔍 Check logs and rerun this script"
    echo ""
    exit 0
else
    echo -e "${RED}❌ SOME TESTS FAILED${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check PHP-FPM status: sudo systemctl status php8.3-fpm"
    echo "2. Restart PHP-FPM: sudo systemctl restart php8.3-fpm"
    echo "3. Clear caches: php artisan optimize:clear"
    echo "4. Check logs: tail -50 storage/logs/laravel.log"
    echo ""
    exit 1
fi
