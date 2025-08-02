#!/bin/bash

# Emergency Test Suite Runner
# Runs critical tests for Business Portal functionality

set -e

echo "🚨 Running Emergency Test Suite for Business Portal..."
echo "=================================================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to run test and show result
run_test() {
    local test_name=$1
    local test_file=$2
    
    echo -e "\n${YELLOW}Running: $test_name${NC}"
    
    if php artisan test "$test_file" --stop-on-failure; then
        echo -e "${GREEN}✅ $test_name passed${NC}"
        return 0
    else
        echo -e "${RED}❌ $test_name failed${NC}"
        return 1
    fi
}

# Track failures
FAILED_TESTS=0
TOTAL_TESTS=0

# Clear test cache
echo -e "${YELLOW}Clearing test cache...${NC}"
php artisan config:clear --env=testing
php artisan cache:clear --env=testing

# Run individual test suites
echo -e "\n${YELLOW}1. Critical Path Tests${NC}"
echo "Testing login, authentication, and basic navigation..."
((TOTAL_TESTS++))
if ! run_test "Critical Path" "tests/Feature/Portal/Emergency/CriticalPathTest.php"; then
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}2. API Contract Tests${NC}"
echo "Testing API response formats and contracts..."
((TOTAL_TESTS++))
if ! run_test "API Contract" "tests/Feature/Portal/Emergency/APIContractTest.php"; then
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}3. Performance Tests${NC}"
echo "Testing response times and resource usage..."
((TOTAL_TESTS++))
if ! run_test "Performance" "tests/Feature/Portal/Emergency/PerformanceTest.php"; then
    ((FAILED_TESTS++))
fi

# Summary
echo -e "\n${YELLOW}======================================${NC}"
echo -e "${YELLOW}Test Summary${NC}"
echo -e "${YELLOW}======================================${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed! ($TOTAL_TESTS/$TOTAL_TESTS)${NC}"
    echo -e "${GREEN}The Business Portal critical functionality is working correctly.${NC}"
    EXIT_CODE=0
else
    echo -e "${RED}❌ $FAILED_TESTS/$TOTAL_TESTS test suites failed${NC}"
    echo -e "${RED}Please fix the failing tests before deployment.${NC}"
    EXIT_CODE=1
fi

# Quick health check
echo -e "\n${YELLOW}Quick API Health Check:${NC}"
curl -s -o /dev/null -w "Login Page: %{http_code}\n" https://api.askproai.de/business/login
curl -s -o /dev/null -w "API Customers: %{http_code}\n" https://api.askproai.de/business/api/customers
curl -s -o /dev/null -w "API Stats: %{http_code}\n" https://api.askproai.de/business/api/stats

# Coverage report (optional)
if [ "$1" == "--coverage" ]; then
    echo -e "\n${YELLOW}Generating coverage report...${NC}"
    php artisan test tests/Feature/Portal/Emergency --coverage --min=40
fi

# Exit with appropriate code
exit $EXIT_CODE