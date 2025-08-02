#!/bin/bash

# Portal Testing Script
# Runs comprehensive tests for critical portal functionality

echo "🚀 Starting Portal Test Suite..."
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to run tests and capture results
run_test_suite() {
    local suite_name=$1
    local test_path=$2
    
    echo -e "${BLUE}📋 Running $suite_name Tests...${NC}"
    
    if php artisan test "$test_path" --no-coverage --stop-on-failure 2>/dev/null; then
        echo -e "${GREEN}✅ $suite_name: PASSED${NC}"
        return 0
    else
        echo -e "${RED}❌ $suite_name: FAILED${NC}"
        return 1
    fi
}

# Initialize counters
TOTAL_SUITES=0
PASSED_SUITES=0

# Test Authentication
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Authentication" "tests/Feature/Auth/AdminPortalAuthTest.php tests/Feature/Auth/BusinessPortalAuthEnhancedTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test Multi-Tenant Security
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Multi-Tenant Security" "tests/Feature/Security/MultiTenantSecurityTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test CRUD Operations
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "CRUD Operations" "tests/Feature/Portal/CriticalEntityCrudTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test API Security
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "API Security" "tests/Feature/Security/ApiEndpointSecurityTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test Session Management
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Session Management" "tests/Feature/Session/PortalSessionManagementTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test Two-Factor Authentication
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Two-Factor Authentication" "tests/Feature/Auth/TwoFactorAuthenticationTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test Middleware
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Middleware" "tests/Feature/Middleware/PortalMiddlewareTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""

# Test Integration Workflows
TOTAL_SUITES=$((TOTAL_SUITES + 1))
if run_test_suite "Integration Workflows" "tests/Feature/Integration/PortalWorkflowIntegrationTest.php"; then
    PASSED_SUITES=$((PASSED_SUITES + 1))
fi

echo ""
echo "=================================="
echo -e "${BLUE}📊 Test Results Summary${NC}"
echo "=================================="

if [ $PASSED_SUITES -eq $TOTAL_SUITES ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED! ($PASSED_SUITES/$TOTAL_SUITES)${NC}"
    echo -e "${GREEN}✅ Portal functionality is secure and working correctly${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Some tests failed: $PASSED_SUITES/$TOTAL_SUITES passed${NC}"
    echo -e "${RED}❌ Review failed tests and fix issues before deployment${NC}"
    exit 1
fi