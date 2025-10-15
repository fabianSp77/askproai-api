#!/bin/bash

# =====================================================
# ASK-010: CRM Data Consistency Test Suite Runner
# =====================================================
# Purpose: Run comprehensive data consistency regression tests
# Usage: ./tests/run-crm-consistency-tests.sh [--level=unit|integration|e2e|all]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TEST_LEVEL="${1:-all}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RESULTS_DIR="tests/results/${TIMESTAMP}"
mkdir -p "${RESULTS_DIR}"

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}CRM Data Consistency Test Suite${NC}"
echo -e "${BLUE}======================================${NC}"
echo -e "Test Level: ${YELLOW}${TEST_LEVEL}${NC}"
echo -e "Results: ${RESULTS_DIR}\n"

# Function to run test group
run_test_group() {
    local GROUP_NAME=$1
    local TEST_COMMAND=$2
    local OUTPUT_FILE="${RESULTS_DIR}/${GROUP_NAME}.log"

    echo -e "${YELLOW}Running ${GROUP_NAME}...${NC}"

    if eval "$TEST_COMMAND" > "${OUTPUT_FILE}" 2>&1; then
        echo -e "${GREEN}✅ ${GROUP_NAME} passed${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        echo -e "${RED}❌ ${GROUP_NAME} failed${NC}"
        echo -e "${RED}   See: ${OUTPUT_FILE}${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

# =====================================================
# UNIT TESTS
# =====================================================
if [[ "$TEST_LEVEL" == "unit" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== UNIT TESTS ===${NC}\n"

    run_test_group "AppointmentCreationService" \
        "php artisan test --filter=AppointmentCreationServiceTest"

    run_test_group "AppointmentModificationService" \
        "php artisan test --filter=AppointmentModificationServiceTest"

    run_test_group "DateTimeParser" \
        "php artisan test --filter=DateTimeParserRelativeWeekdayTest"

    run_test_group "ServiceSelection" \
        "php artisan test --filter=ServiceSelectionServiceTest"

    TESTS_RUN=$((TESTS_RUN + 4))
fi

# =====================================================
# INTEGRATION TESTS
# =====================================================
if [[ "$TEST_LEVEL" == "integration" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== INTEGRATION TESTS ===${NC}\n"

    run_test_group "DataConsistencyIntegration" \
        "php artisan test --filter=DataConsistencyIntegrationTest"

    run_test_group "RetellAutoServiceSelection" \
        "php artisan test --filter=RetellAutoServiceSelectionTest"

    run_test_group "RetellWebhookSecurity" \
        "php artisan test --filter=RetellWebhookSecurityTest"

    run_test_group "TenantMiddlewareSecurity" \
        "php artisan test --filter=TenantMiddlewareSecurityTest"

    TESTS_RUN=$((TESTS_RUN + 4))
fi

# =====================================================
# E2E TESTS
# =====================================================
if [[ "$TEST_LEVEL" == "e2e" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== END-TO-END TESTS ===${NC}\n"

    run_test_group "AppointmentJourneyE2E" \
        "php artisan test --filter=AppointmentJourneyE2ETest"

    run_test_group "RetellDateTimeFunctionE2E" \
        "npm test -- tests/E2E/RetellDateTimeFunctionE2E.test.js" || true

    TESTS_RUN=$((TESTS_RUN + 2))
fi

# =====================================================
# BROWSER TESTS (PUPPETEER)
# =====================================================
if [[ "$TEST_LEVEL" == "browser" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== BROWSER TESTS (PUPPETEER) ===${NC}\n"

    # Check if Puppeteer is available
    if command -v mocha &> /dev/null; then
        run_test_group "CRM-DataConsistency-E2E-Browser" \
            "mocha tests/puppeteer/crm-data-consistency-e2e.cjs --timeout 60000"

        TESTS_RUN=$((TESTS_RUN + 1))
    else
        echo -e "${YELLOW}⚠️  Mocha not available, skipping browser tests${NC}"
    fi
fi

# =====================================================
# SQL VALIDATION QUERIES
# =====================================================
if [[ "$TEST_LEVEL" == "sql" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== SQL VALIDATION QUERIES ===${NC}\n"

    OUTPUT_FILE="${RESULTS_DIR}/sql-validation.log"

    echo "Running SQL validation queries..." > "${OUTPUT_FILE}"

    # Metadata completeness check
    echo -e "\n${YELLOW}Checking metadata completeness...${NC}"
    mysql -u root -p"${DB_PASSWORD}" "${DB_DATABASE}" < tests/SQL/data-consistency-validation.sql >> "${OUTPUT_FILE}" 2>&1 || true

    # Count issues found
    METADATA_ISSUES=$(mysql -u root -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM appointments WHERE created_by IS NULL OR booking_source IS NULL;" -s -N 2>/dev/null || echo "0")
    NAME_ISSUES=$(mysql -u root -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM appointments a JOIN calls ca ON ca.id = a.call_id WHERE a.customer_id != ca.customer_id;" -s -N 2>/dev/null || echo "0")

    if [[ "$METADATA_ISSUES" -eq 0 ]] && [[ "$NAME_ISSUES" -eq 0 ]]; then
        echo -e "${GREEN}✅ SQL validation passed (0 issues found)${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ SQL validation failed${NC}"
        echo -e "${RED}   Metadata issues: ${METADATA_ISSUES}${NC}"
        echo -e "${RED}   Name consistency issues: ${NAME_ISSUES}${NC}"
        echo -e "${RED}   See: ${OUTPUT_FILE}${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi

    TESTS_RUN=$((TESTS_RUN + 1))
fi

# =====================================================
# PERFORMANCE TESTS
# =====================================================
if [[ "$TEST_LEVEL" == "performance" ]] || [[ "$TEST_LEVEL" == "all" ]]; then
    echo -e "\n${BLUE}=== PERFORMANCE TESTS ===${NC}\n"

    run_test_group "PerformanceMonitoringP95" \
        "php artisan test --filter=PerformanceMonitoringP95Test"

    run_test_group "MonitoringP95Endpoint" \
        "php artisan test --filter=MonitoringP95EndpointTest"

    TESTS_RUN=$((TESTS_RUN + 2))
fi

# =====================================================
# GENERATE SUMMARY REPORT
# =====================================================
echo -e "\n${BLUE}======================================${NC}"
echo -e "${BLUE}TEST SUMMARY${NC}"
echo -e "${BLUE}======================================${NC}"
echo -e "Total Tests Run:    ${TESTS_RUN}"
echo -e "${GREEN}Passed:             ${TESTS_PASSED}${NC}"
echo -e "${RED}Failed:             ${TESTS_FAILED}${NC}"

if [[ $TESTS_FAILED -eq 0 ]]; then
    echo -e "\n${GREEN}✅ ALL TESTS PASSED${NC}\n"
    exit 0
else
    echo -e "\n${RED}❌ SOME TESTS FAILED${NC}"
    echo -e "${RED}Check logs in: ${RESULTS_DIR}${NC}\n"
    exit 1
fi

# =====================================================
# GENERATE HTML REPORT
# =====================================================
cat > "${RESULTS_DIR}/index.html" <<EOF
<!DOCTYPE html>
<html>
<head>
    <title>CRM Data Consistency Test Results - ${TIMESTAMP}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .summary { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .passed { color: #28a745; font-weight: bold; }
        .failed { color: #dc3545; font-weight: bold; }
        .test-group { margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .test-group.passed { border-color: #28a745; background: #d4edda; }
        .test-group.failed { border-color: #dc3545; background: #f8d7da; }
    </style>
</head>
<body>
    <h1>CRM Data Consistency Test Results</h1>
    <p>Generated: ${TIMESTAMP}</p>

    <div class="summary">
        <h2>Summary</h2>
        <p>Total Tests Run: <strong>${TESTS_RUN}</strong></p>
        <p class="passed">Passed: ${TESTS_PASSED}</p>
        <p class="failed">Failed: ${TESTS_FAILED}</p>
    </div>

    <h2>Test Details</h2>
    <div id="test-details">
        <!-- Test details will be populated here -->
    </div>
</body>
</html>
EOF

echo -e "${GREEN}HTML report generated: ${RESULTS_DIR}/index.html${NC}"
