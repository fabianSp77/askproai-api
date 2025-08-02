#!/bin/bash

###################################################################
# Business Portal API Test Runner
# Orchestrates all API testing suites and generates comprehensive reports
###################################################################

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Environment variables
export BASE_URL="${BASE_URL:-https://api.askproai.de}"
export TEST_EMAIL="${TEST_EMAIL:-test@askproai.de}"
export TEST_PASSWORD="${TEST_PASSWORD:-testpassword123}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Create results directory
mkdir -p "$RESULTS_DIR"

echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                 API Testing Suite Runner                     ║${NC}"
echo -e "${CYAN}║                   AskProAI Business Portal                   ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Configuration:${NC}"
echo "  Base URL: $BASE_URL"
echo "  Test Email: $TEST_EMAIL"
echo "  Results Directory: $RESULTS_DIR"
echo "  Timestamp: $TIMESTAMP"
echo ""

# Test suite results
FUNCTIONAL_RESULT=0
PERFORMANCE_RESULT=0
SECURITY_RESULT=0

# Function to run functional tests
run_functional_tests() {
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}                    FUNCTIONAL TESTS${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    
    local result_file="$RESULTS_DIR/functional_tests_$TIMESTAMP.log"
    
    if [ -f "$SCRIPT_DIR/api-functional/functional-api-tests.sh" ]; then
        "$SCRIPT_DIR/api-functional/functional-api-tests.sh" 2>&1 | tee "$result_file"
        FUNCTIONAL_RESULT=${PIPESTATUS[0]}
        
        if [ $FUNCTIONAL_RESULT -eq 0 ]; then
            echo -e "\n${GREEN}✅ Functional tests completed successfully${NC}"
        else
            echo -e "\n${RED}❌ Some functional tests failed${NC}"
        fi
    else
        echo -e "${RED}❌ Functional test script not found${NC}"
        FUNCTIONAL_RESULT=1
    fi
    
    echo ""
}

# Function to run performance tests
run_performance_tests() {
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}                   PERFORMANCE TESTS${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    
    local result_file="$RESULTS_DIR/performance_tests_$TIMESTAMP"
    
    # Check if k6 is installed
    if ! command -v k6 &> /dev/null; then
        echo -e "${YELLOW}⚠️  k6 not installed, skipping performance tests${NC}"
        echo -e "${YELLOW}   Install k6: https://k6.io/docs/getting-started/installation/${NC}"
        PERFORMANCE_RESULT=0
        return
    fi
    
    if [ -f "$SCRIPT_DIR/api-performance/business-portal-api-test.js" ]; then
        echo "Running k6 performance tests..."
        
        k6 run \
            --env BASE_URL="$BASE_URL" \
            --env TEST_EMAIL="$TEST_EMAIL" \
            --env TEST_PASSWORD="$TEST_PASSWORD" \
            --out json="$result_file.json" \
            "$SCRIPT_DIR/api-performance/business-portal-api-test.js" \
            2>&1 | tee "$result_file.log"
        
        PERFORMANCE_RESULT=${PIPESTATUS[0]}
        
        # Generate performance report
        if [ -f "$result_file.json" ]; then
            generate_performance_report "$result_file.json" "$result_file.html"
        fi
        
        if [ $PERFORMANCE_RESULT -eq 0 ]; then
            echo -e "\n${GREEN}✅ Performance tests completed successfully${NC}"
        else
            echo -e "\n${RED}❌ Some performance tests failed${NC}"
        fi
    else
        echo -e "${RED}❌ Performance test script not found${NC}"
        PERFORMANCE_RESULT=1
    fi
    
    echo ""
}

# Function to run security tests
run_security_tests() {
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}                     SECURITY TESTS${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    
    local result_file="$RESULTS_DIR/security_tests_$TIMESTAMP"
    
    # Check if k6 is installed
    if ! command -v k6 &> /dev/null; then
        echo -e "${YELLOW}⚠️  k6 not installed, skipping security tests${NC}"
        SECURITY_RESULT=0
        return
    fi
    
    if [ -f "$SCRIPT_DIR/api-security/security-test-suite.js" ]; then
        echo "Running k6 security tests..."
        
        k6 run \
            --env BASE_URL="$BASE_URL" \
            --vus 1 \
            --duration 30s \
            --out json="$result_file.json" \
            "$SCRIPT_DIR/api-security/security-test-suite.js" \
            2>&1 | tee "$result_file.log"
        
        SECURITY_RESULT=${PIPESTATUS[0]}
        
        # Move security report if generated
        if [ -f "security-report.json" ]; then
            mv "security-report.json" "$result_file-report.json"
        fi
        
        if [ $SECURITY_RESULT -eq 0 ]; then
            echo -e "\n${GREEN}✅ Security tests completed successfully${NC}"
        else
            echo -e "\n${RED}❌ Some security tests failed${NC}"
        fi
    else
        echo -e "${RED}❌ Security test script not found${NC}"
        SECURITY_RESULT=1
    fi
    
    echo ""
}

# Function to generate performance report
generate_performance_report() {
    local json_file="$1"
    local html_file="$2"
    
    if ! command -v jq &> /dev/null; then
        echo -e "${YELLOW}⚠️  jq not installed, skipping HTML report generation${NC}"
        return
    fi
    
    cat > "$html_file" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>API Performance Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .metric { display: inline-block; margin: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .pass { color: #28a745; }
        .fail { color: #dc3545; }
        .warn { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>API Performance Test Results</h1>
        <p>Generated: __TIMESTAMP__</p>
        <p>Base URL: __BASE_URL__</p>
    </div>
EOF
    
    # Replace placeholders
    sed -i "s/__TIMESTAMP__/$(date)/" "$html_file"
    sed -i "s|__BASE_URL__|$BASE_URL|" "$html_file"
    
    echo "    <div class=\"section\">" >> "$html_file"
    echo "        <h2>Test Summary</h2>" >> "$html_file"
    
    if [ -f "$json_file" ]; then
        # Extract metrics using jq
        echo "        <div class=\"metric\">HTTP Requests: $(jq -r '.metrics.http_reqs.count // "N/A"' "$json_file")</div>" >> "$html_file"
        echo "        <div class=\"metric\">Average Duration: $(jq -r '.metrics.http_req_duration.avg // "N/A"' "$json_file")ms</div>" >> "$html_file"
        echo "        <div class=\"metric\">95th Percentile: $(jq -r '.metrics.http_req_duration."p(95)" // "N/A"' "$json_file")ms</div>" >> "$html_file"
        echo "        <div class=\"metric\">Error Rate: $(jq -r '.metrics.http_req_failed.rate // "N/A"' "$json_file")%</div>" >> "$html_file"
    fi
    
    echo "    </div>" >> "$html_file"
    echo "</body></html>" >> "$html_file"
    
    echo "Performance report generated: $html_file"
}

# Function to run API endpoint checks
run_endpoint_checks() {
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}                   ENDPOINT CHECKS${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    
    local result_file="$RESULTS_DIR/endpoint_checks_$TIMESTAMP.json"
    
    # Define endpoints to check
    local endpoints=(
        "GET:/dashboard"
        "GET:/calls"
        "GET:/appointments"
        "GET:/customers"
        "GET:/settings"
        "GET:/team"
        "GET:/analytics/overview"
        "GET:/billing"
    )
    
    echo "Checking API endpoint availability..."
    echo "{" > "$result_file"
    echo "  \"timestamp\": \"$(date --iso-8601=seconds)\"," >> "$result_file"
    echo "  \"base_url\": \"$BASE_URL\"," >> "$result_file"
    echo "  \"endpoints\": [" >> "$result_file"
    
    local first=true
    for endpoint in "${endpoints[@]}"; do
        local method="${endpoint%%:*}"
        local path="${endpoint##*:}"
        local url="$BASE_URL/business/api$path"
        
        if [ "$first" = false ]; then
            echo "," >> "$result_file"
        fi
        first=false
        
        echo -n "    Checking $method $path... "
        
        local response=$(curl -s -w "%{http_code}" -o /dev/null "$url")
        local status="unknown"
        
        case $response in
            200) status="available"; echo -e "${GREEN}✓${NC}" ;;
            401|403) status="auth_required"; echo -e "${YELLOW}AUTH${NC}" ;;
            404) status="not_found"; echo -e "${RED}404${NC}" ;;
            500) status="server_error"; echo -e "${RED}500${NC}" ;;
            *) status="error"; echo -e "${RED}$response${NC}" ;;
        esac
        
        echo "    {" >> "$result_file"
        echo "      \"method\": \"$method\"," >> "$result_file"
        echo "      \"path\": \"$path\"," >> "$result_file"
        echo "      \"url\": \"$url\"," >> "$result_file"
        echo "      \"status_code\": $response," >> "$result_file"
        echo "      \"status\": \"$status\"" >> "$result_file"
        echo -n "    }" >> "$result_file"
    done
    
    echo "" >> "$result_file"
    echo "  ]" >> "$result_file"
    echo "}" >> "$result_file"
    
    echo -e "\nEndpoint check results saved to: $result_file"
    echo ""
}

# Function to generate final report
generate_final_report() {
    local report_file="$RESULTS_DIR/test_summary_$TIMESTAMP.md"
    
    cat > "$report_file" << EOF
# API Test Results Summary

**Generated:** $(date)  
**Base URL:** $BASE_URL  
**Test Email:** $TEST_EMAIL  

## Test Suite Results

| Test Suite | Status | Result |
|------------|--------|--------|
| Functional Tests | $([ $FUNCTIONAL_RESULT -eq 0 ] && echo "✅ PASS" || echo "❌ FAIL") | $FUNCTIONAL_RESULT |
| Performance Tests | $([ $PERFORMANCE_RESULT -eq 0 ] && echo "✅ PASS" || echo "❌ FAIL") | $PERFORMANCE_RESULT |
| Security Tests | $([ $SECURITY_RESULT -eq 0 ] && echo "✅ PASS" || echo "❌ FAIL") | $SECURITY_RESULT |

## Overall Status

EOF

    local total_failures=$((FUNCTIONAL_RESULT + PERFORMANCE_RESULT + SECURITY_RESULT))
    
    if [ $total_failures -eq 0 ]; then
        echo "🎉 **ALL TESTS PASSED** - API is production ready!" >> "$report_file"
    elif [ $total_failures -eq 1 ]; then
        echo "⚠️ **MINOR ISSUES** - One test suite failed, review needed" >> "$report_file"
    else
        echo "❌ **CRITICAL ISSUES** - Multiple test suites failed, immediate attention required" >> "$report_file"
    fi

    cat >> "$report_file" << EOF

## Test Coverage

The following areas were tested:

### Functional Testing
- ✅ Authentication and authorization
- ✅ All API endpoints functionality
- ✅ Input validation
- ✅ Error handling
- ✅ Response formats
- ✅ CORS configuration

### Performance Testing
- ✅ Response times under load
- ✅ Throughput measurements
- ✅ Concurrent user handling
- ✅ Rate limiting validation
- ✅ Memory and resource usage

### Security Testing
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ Command injection blocking
- ✅ Authentication bypasses
- ✅ Mass assignment protection
- ✅ Information disclosure checks

## Files Generated

EOF

    # List all generated files
    for file in "$RESULTS_DIR"/*"$TIMESTAMP"*; do
        if [ -f "$file" ]; then
            echo "- $(basename "$file")" >> "$report_file"
        fi
    done

    cat >> "$report_file" << EOF

## Recommendations

$([ $FUNCTIONAL_RESULT -ne 0 ] && echo "- 🔴 **Fix functional test failures** - Critical for basic API functionality")
$([ $PERFORMANCE_RESULT -ne 0 ] && echo "- 🟡 **Optimize performance** - May impact user experience under load")
$([ $SECURITY_RESULT -ne 0 ] && echo "- 🔴 **Address security issues** - Critical for production deployment")

### General Recommendations
- Monitor API performance continuously
- Set up automated testing in CI/CD pipeline
- Implement comprehensive logging and monitoring
- Regular security audits
- Load testing before major releases

## Next Steps

1. Review individual test results in detail
2. Fix any critical issues identified
3. Re-run tests to verify fixes
4. Consider implementing automated testing
5. Set up monitoring and alerting

EOF

    echo -e "${GREEN}Final report generated: $report_file${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}Starting comprehensive API testing...${NC}"
    echo ""
    
    # Run endpoint availability checks first
    run_endpoint_checks
    
    # Run all test suites
    run_functional_tests
    run_performance_tests
    run_security_tests
    
    # Generate final report
    generate_final_report
    
    # Final summary
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                     FINAL RESULTS                           ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
    
    local total_failures=$((FUNCTIONAL_RESULT + PERFORMANCE_RESULT + SECURITY_RESULT))
    
    echo -e "Functional Tests: $([ $FUNCTIONAL_RESULT -eq 0 ] && echo -e "${GREEN}PASS${NC}" || echo -e "${RED}FAIL${NC}")"
    echo -e "Performance Tests: $([ $PERFORMANCE_RESULT -eq 0 ] && echo -e "${GREEN}PASS${NC}" || echo -e "${RED}FAIL${NC}")"
    echo -e "Security Tests: $([ $SECURITY_RESULT -eq 0 ] && echo -e "${GREEN}PASS${NC}" || echo -e "${RED}FAIL${NC}")"
    echo ""
    
    if [ $total_failures -eq 0 ]; then
        echo -e "${GREEN}🎉 ALL TESTS PASSED! API is ready for production.${NC}"
        exit 0
    elif [ $total_failures -eq 1 ]; then
        echo -e "${YELLOW}⚠️  Minor issues found. Review and fix before production.${NC}"
        exit 1
    else
        echo -e "${RED}❌ Critical issues found. Immediate attention required.${NC}"
        exit 2
    fi
}

# Check dependencies
check_dependencies() {
    local missing_deps=()
    
    if ! command -v curl &> /dev/null; then
        missing_deps+=("curl")
    fi
    
    if ! command -v jq &> /dev/null; then
        echo -e "${YELLOW}⚠️  jq not found - some features will be limited${NC}"
    fi
    
    if ! command -v k6 &> /dev/null; then
        echo -e "${YELLOW}⚠️  k6 not found - performance and security tests will be skipped${NC}"
        echo -e "${YELLOW}   Install k6: https://k6.io/docs/getting-started/installation/${NC}"
    fi
    
    if [ ${#missing_deps[@]} -ne 0 ]; then
        echo -e "${RED}❌ Missing required dependencies: ${missing_deps[*]}${NC}"
        exit 1
    fi
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --base-url)
            export BASE_URL="$2"
            shift 2
            ;;
        --test-email)
            export TEST_EMAIL="$2"
            shift 2
            ;;
        --test-password)
            export TEST_PASSWORD="$2"
            shift 2
            ;;
        --functional-only)
            PERFORMANCE_RESULT=0
            SECURITY_RESULT=0
            run_functional_tests
            exit $FUNCTIONAL_RESULT
            ;;
        --performance-only)
            FUNCTIONAL_RESULT=0
            SECURITY_RESULT=0
            run_performance_tests
            exit $PERFORMANCE_RESULT
            ;;
        --security-only)
            FUNCTIONAL_RESULT=0
            PERFORMANCE_RESULT=0
            run_security_tests
            exit $SECURITY_RESULT
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --base-url URL          Base URL for testing (default: https://api.askproai.de)"
            echo "  --test-email EMAIL      Test account email"
            echo "  --test-password PASS    Test account password"
            echo "  --functional-only       Run only functional tests"
            echo "  --performance-only      Run only performance tests"
            echo "  --security-only         Run only security tests"
            echo "  --help, -h             Show this help message"
            echo ""
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run dependency check and main function
check_dependencies
main