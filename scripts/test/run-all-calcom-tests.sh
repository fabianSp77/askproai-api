#!/bin/bash

#################################################
# Cal.com V2 - Complete Test Suite Runner
# Runs all Cal.com integration tests
#################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOG_DIR="$PROJECT_ROOT/storage/logs/tests"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SUMMARY_LOG="$LOG_DIR/test-summary-$TIMESTAMP.log"

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Test results
TOTAL_TESTS=0
TOTAL_PASSED=0
TOTAL_FAILED=0

# Header
echo -e "${CYAN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║      Cal.com V2 Integration - Complete Test Suite     ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Starting at:${NC} $(date)"
echo -e "${BLUE}Log directory:${NC} $LOG_DIR"
echo ""

# Function to run a test section
run_test_section() {
    local section_name=$1
    local test_command=$2
    local log_file="$LOG_DIR/${section_name}-$TIMESTAMP.log"

    echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}Running:${NC} $section_name"
    echo -e "${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Run the test and capture output
    if eval "$test_command" > "$log_file" 2>&1; then
        echo -e "${GREEN}✓ PASSED${NC} - $section_name"
        TOTAL_PASSED=$((TOTAL_PASSED + 1))
        echo "PASSED: $section_name" >> "$SUMMARY_LOG"
    else
        echo -e "${RED}✗ FAILED${NC} - $section_name (check $log_file)"
        TOTAL_FAILED=$((TOTAL_FAILED + 1))
        echo "FAILED: $section_name - Log: $log_file" >> "$SUMMARY_LOG"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
}

# 1. Unit Tests
echo -e "\n${CYAN}═══ SECTION 1: Unit Tests ═══${NC}"

run_test_section "CalcomV2Client-Unit-Tests" \
    "cd '$PROJECT_ROOT' && php artisan test tests/Feature/CalcomV2/CalcomV2ClientTest.php"

# 2. Integration Tests
echo -e "\n${CYAN}═══ SECTION 2: Integration Tests ═══${NC}"

run_test_section "CalcomV2-Integration-Tests" \
    "cd '$PROJECT_ROOT' && php artisan test tests/Feature/CalcomV2/CalcomV2IntegrationTest.php"

# 3. API Availability Tests
echo -e "\n${CYAN}═══ SECTION 3: API Availability Tests ═══${NC}"

if [ -f "$SCRIPT_DIR/test-calcom-availability.sh" ]; then
    run_test_section "API-Availability-Tests" \
        "$SCRIPT_DIR/test-calcom-availability.sh"
else
    echo -e "${YELLOW}⚠ Availability test script not found${NC}"
fi

# 4. API Booking Tests
echo -e "\n${CYAN}═══ SECTION 4: API Booking Tests ═══${NC}"

if [ -f "$SCRIPT_DIR/test-calcom-booking.sh" ]; then
    run_test_section "API-Booking-Tests" \
        "$SCRIPT_DIR/test-calcom-booking.sh"
else
    echo -e "${YELLOW}⚠ Booking test script not found${NC}"
fi

# 5. Health Check Tests
echo -e "\n${CYAN}═══ SECTION 5: Health Check Tests ═══${NC}"

run_test_section "Health-Check-Quick" \
    "curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/api/health/calcom | grep -q '200'"

run_test_section "Health-Check-Detailed" \
    "curl -s http://localhost:8000/api/health/calcom/detailed | grep -q '\"status\"'"

# 6. Mock Server Tests
echo -e "\n${CYAN}═══ SECTION 6: Mock Server Tests ═══${NC}"

run_test_section "Mock-Server-Tests" \
    "cd '$PROJECT_ROOT' && php artisan test --filter CalcomV2MockServer"

# 7. Webhook Tests (if available)
echo -e "\n${CYAN}═══ SECTION 7: Webhook Tests ═══${NC}"

run_test_section "Webhook-Signature-Tests" \
    "cd '$PROJECT_ROOT' && php artisan test --filter CalcomIntegrationTest::test_webhook"

# Summary
echo ""
echo -e "${CYAN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                    TEST SUMMARY                       ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Calculate percentage
if [ $TOTAL_TESTS -gt 0 ]; then
    PASS_PERCENTAGE=$((TOTAL_PASSED * 100 / TOTAL_TESTS))
else
    PASS_PERCENTAGE=0
fi

echo -e "Total Tests Run:    ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Tests Passed:       ${GREEN}$TOTAL_PASSED${NC}"
echo -e "Tests Failed:       ${RED}$TOTAL_FAILED${NC}"
echo -e "Pass Rate:          ${YELLOW}${PASS_PERCENTAGE}%${NC}"
echo ""
echo -e "Summary Log:        $SUMMARY_LOG"
echo -e "Detailed Logs:      $LOG_DIR/*-$TIMESTAMP.log"
echo ""

# Final status
if [ $TOTAL_FAILED -eq 0 ] && [ $TOTAL_TESTS -gt 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║              ALL TESTS PASSED! ✓                      ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
    exit 0
else
    echo -e "${RED}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║            SOME TESTS FAILED! ✗                       ║${NC}"
    echo -e "${RED}║         Check logs for details                        ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════╝${NC}"
    exit 1
fi