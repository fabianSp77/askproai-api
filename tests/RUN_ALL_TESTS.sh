#!/bin/bash
# Customer Data Integrity Fix - Complete Test Suite Execution Script
# Run this script to execute all tests in the correct order

set -e  # Exit on error

echo "═══════════════════════════════════════════════════════════════"
echo "  Customer Data Integrity Fix - Complete Test Suite"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Total Tests: 82"
echo "Estimated Time: 5-10 minutes"
echo ""

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Track results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run test suite
run_test_suite() {
    local suite_name=$1
    local test_path=$2
    local test_count=$3

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}Running: $suite_name ($test_count tests)${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"

    if php artisan test "$test_path" --stop-on-failure; then
        echo -e "${GREEN}✓ $suite_name PASSED${NC}"
        PASSED_TESTS=$((PASSED_TESTS + test_count))
    else
        echo -e "${RED}✗ $suite_name FAILED${NC}"
        FAILED_TESTS=$((FAILED_TESTS + test_count))

        # Ask if user wants to continue
        read -p "Continue with remaining tests? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi

    TOTAL_TESTS=$((TOTAL_TESTS + test_count))
}

# Phase 1: Pre-Deployment Tests
echo ""
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  PHASE 1: PRE-DEPLOYMENT VALIDATION${NC}"
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"

run_test_suite \
    "1. Pre-Backfill Validation" \
    "tests/Feature/DataIntegrity/CustomerCompanyIdValidationTest.php" \
    7

run_test_suite \
    "2. Backfill Migration Tests" \
    "tests/Unit/Migrations/BackfillCustomerCompanyIdTest.php" \
    8

run_test_suite \
    "3. Constraint Enforcement" \
    "tests/Feature/DataIntegrity/CustomerCompanyIdConstraintTest.php" \
    10

# Phase 2: Post-Deployment Tests
echo ""
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  PHASE 2: POST-DEPLOYMENT VALIDATION${NC}"
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"

run_test_suite \
    "4. Post-Backfill Validation" \
    "tests/Feature/DataIntegrity/CustomerCompanyIdBackfillValidationTest.php" \
    9

run_test_suite \
    "5. Security Regression Tests" \
    "tests/Feature/Security/CustomerIsolationTest.php" \
    8

run_test_suite \
    "6. Integration Tests" \
    "tests/Feature/Integration/CustomerManagementTest.php" \
    5

# Phase 3: Performance & Monitoring
echo ""
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  PHASE 3: PERFORMANCE & MONITORING${NC}"
echo -e "${YELLOW}══════════════════════════════════════════════════════════════${NC}"

run_test_suite \
    "7. Performance Tests" \
    "tests/Performance/CustomerCompanyIdBackfillPerformanceTest.php" \
    4

run_test_suite \
    "8. Monitoring & Alerting Tests" \
    "tests/Feature/Monitoring/CustomerDataIntegrityMonitoringTest.php" \
    5

# Validation Command
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Running Validation Command${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"

if php artisan customers:validate-integrity --detailed; then
    echo -e "${GREEN}✓ Validation Command PASSED${NC}"
else
    echo -e "${RED}✗ Validation Command FAILED${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Summary
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "                      TEST SUMMARY"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Total Test Suites: 8"
echo "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  ✓✓✓ ALL TESTS PASSED - READY FOR DEPLOYMENT ✓✓✓${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    exit 0
else
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}  ✗✗✗ SOME TESTS FAILED - DO NOT DEPLOY ✗✗✗${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    exit 1
fi
