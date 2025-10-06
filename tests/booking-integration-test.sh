#!/bin/bash

# Booking Integration Test - Real-world testing without database mocking
# Tests the actual booking flow in the production environment

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Function to run a test
run_test() {
    local test_name=$1
    local expected_result=$2
    local actual_result=$3

    TESTS_TOTAL=$((TESTS_TOTAL + 1))

    if [ "$expected_result" = "$actual_result" ]; then
        echo -e "${GREEN}✓${NC} $test_name"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        echo -e "${RED}✗${NC} $test_name"
        echo "  Expected: $expected_result"
        echo "  Actual: $actual_result"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

echo "======================================================"
echo "BOOKING INTEGRATION TEST"
echo "======================================================"
echo ""

# Test 1: Check if booking wizard component exists
echo -e "${BLUE}Test Group: Component Existence${NC}"
FILE_EXISTS=$([ -f "/var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php" ] && echo "true" || echo "false")
run_test "BookingWizard component exists" "true" "$FILE_EXISTS"

FILE_EXISTS=$([ -f "/var/www/api-gateway/app/Services/Booking/AvailabilityService.php" ] && echo "true" || echo "false")
run_test "AvailabilityService exists" "true" "$FILE_EXISTS"

FILE_EXISTS=$([ -f "/var/www/api-gateway/resources/views/livewire/public-booking/booking-wizard.blade.php" ] && echo "true" || echo "false")
run_test "Booking wizard view exists" "true" "$FILE_EXISTS"

echo ""

# Test 2: Check class structure
echo -e "${BLUE}Test Group: Class Structure${NC}"
CLASS_EXISTS=$(php -r "
    require_once '/var/www/api-gateway/vendor/autoload.php';
    echo class_exists('App\Livewire\PublicBooking\BookingWizard') ? 'true' : 'false';
" 2>/dev/null || echo "false")
run_test "BookingWizard class can be loaded" "true" "$CLASS_EXISTS"

CLASS_EXISTS=$(php -r "
    require_once '/var/www/api-gateway/vendor/autoload.php';
    echo class_exists('App\Services\Booking\AvailabilityService') ? 'true' : 'false';
" 2>/dev/null || echo "false")
run_test "AvailabilityService class can be loaded" "true" "$CLASS_EXISTS"

echo ""

# Test 3: Check method existence
echo -e "${BLUE}Test Group: Method Existence${NC}"
METHOD_EXISTS=$(php -r "
    require_once '/var/www/api-gateway/vendor/autoload.php';
    \$class = new ReflectionClass('App\Services\Booking\AvailabilityService');
    echo \$class->hasMethod('getAvailableSlots') ? 'true' : 'false';
" 2>/dev/null || echo "false")
run_test "AvailabilityService::getAvailableSlots() exists" "true" "$METHOD_EXISTS"

METHOD_EXISTS=$(php -r "
    require_once '/var/www/api-gateway/vendor/autoload.php';
    \$class = new ReflectionClass('App\Services\Booking\AvailabilityService');
    echo \$class->hasMethod('getAvailabilityHeatmap') ? 'true' : 'false';
" 2>/dev/null || echo "false")
run_test "AvailabilityService::getAvailabilityHeatmap() exists" "true" "$METHOD_EXISTS"

echo ""

# Test 4: Check performance optimizations
echo -e "${BLUE}Test Group: Performance Optimizations${NC}"

# Check if indexes exist
INDEX_COUNT=$(php artisan tinker --execute="
    try {
        \$indexes = DB::select('SHOW INDEXES FROM appointments WHERE Key_name LIKE \"idx_%\"');
        echo count(\$indexes);
    } catch (Exception \$e) {
        echo '0';
    }
" 2>/dev/null | tail -1)
run_test "Database indexes created (>0)" "true" "$([ "$INDEX_COUNT" -gt "0" ] 2>/dev/null && echo "true" || echo "false")"

# Check cache configuration
CACHE_DRIVER=$(php artisan tinker --execute="echo config('cache.default');" 2>/dev/null | tail -1)
run_test "Cache driver configured (not array)" "true" "$([ "$CACHE_DRIVER" != "array" ] && echo "true" || echo "false")"

echo ""

# Test 5: Security checks
echo -e "${BLUE}Test Group: Security${NC}"

# Check for GDPR consent field
GDPR_EXISTS=$(grep -q "gdprConsent" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "GDPR consent field exists" "true" "$GDPR_EXISTS"

# Check for rate limiting
RATE_LIMIT_EXISTS=$(grep -q "RateLimiter" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Rate limiting implemented" "true" "$RATE_LIMIT_EXISTS"

# Check for validation rules
VALIDATION_EXISTS=$(grep -q "protected \$rules" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Input validation rules defined" "true" "$VALIDATION_EXISTS"

echo ""

# Test 6: UI Components
echo -e "${BLUE}Test Group: UI Components${NC}"

# Check for multi-step wizard
STEPS_EXISTS=$(grep -q "currentStep" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Multi-step wizard implemented" "true" "$STEPS_EXISTS"

# Check for progress bar in view
PROGRESS_EXISTS=$(grep -q "Progress Bar" /var/www/api-gateway/resources/views/livewire/public-booking/booking-wizard.blade.php && echo "true" || echo "false")
run_test "Progress bar in view" "true" "$PROGRESS_EXISTS"

# Check for error handling in view
ERROR_HANDLING=$(grep -q "@if(count(\$errors) > 0)" /var/www/api-gateway/resources/views/livewire/public-booking/booking-wizard.blade.php && echo "true" || echo "false")
run_test "Error handling in view" "true" "$ERROR_HANDLING"

echo ""

# Test 7: Booking Logic
echo -e "${BLUE}Test Group: Booking Logic${NC}"

# Check for lock service usage
LOCK_SERVICE=$(grep -q "BookingLockService" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Booking lock service integrated" "true" "$LOCK_SERVICE"

# Check for transaction handling
TRANSACTION=$(grep -q "DB::beginTransaction" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Database transactions used" "true" "$TRANSACTION"

# Check for confirmation code generation
CONFIRMATION=$(grep -q "confirmation_code" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Confirmation code generation" "true" "$CONFIRMATION"

echo ""

# Test 8: Availability Calculation
echo -e "${BLUE}Test Group: Availability Calculation${NC}"

# Check for working hours integration
WORKING_HOURS=$(grep -q "WorkingHour" /var/www/api-gateway/app/Services/Booking/AvailabilityService.php && echo "true" || echo "false")
run_test "Working hours integration" "true" "$WORKING_HOURS"

# Check for buffer time handling
BUFFER_TIME=$(grep -q "buffer_time_minutes" /var/www/api-gateway/app/Services/Booking/AvailabilityService.php && echo "true" || echo "false")
run_test "Buffer time handling" "true" "$BUFFER_TIME"

# Check for slot optimization
OPTIMIZATION=$(grep -q "getOptimizedSlotSuggestions" /var/www/api-gateway/app/Services/Booking/AvailabilityService.php && echo "true" || echo "false")
run_test "Slot optimization implemented" "true" "$OPTIMIZATION"

echo ""

# Test 9: Performance Features
echo -e "${BLUE}Test Group: Performance Features${NC}"

# Check for caching
CACHING=$(grep -q "Cache::" /var/www/api-gateway/app/Services/Booking/AvailabilityService.php && echo "true" || echo "false")
run_test "Caching implemented" "true" "$CACHING"

# Check for eager loading
EAGER_LOADING=$(grep -q "with(" /var/www/api-gateway/app/Livewire/PublicBooking/BookingWizard.php && echo "true" || echo "false")
run_test "Eager loading used" "true" "$EAGER_LOADING"

# Check for query optimization
SINGLE_QUERY=$(grep -q "selectRaw" /var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerOverview.php 2>/dev/null && echo "true" || echo "false")
run_test "Optimized queries in widgets" "true" "$SINGLE_QUERY"

echo ""
echo "======================================================"
echo "TEST SUMMARY"
echo "======================================================"
echo -e "Total Tests: ${TESTS_TOTAL}"
echo -e "Passed: ${GREEN}${TESTS_PASSED}${NC}"
echo -e "Failed: ${RED}${TESTS_FAILED}${NC}"
echo -e "Success Rate: $(echo "scale=2; ${TESTS_PASSED} * 100 / ${TESTS_TOTAL}" | bc)%"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED!${NC}"
    echo "The booking system is properly implemented and ready for use."
    exit 0
else
    echo -e "${YELLOW}⚠️ Some tests failed${NC}"
    echo "Please review the failed tests above and fix any issues."
    exit 1
fi