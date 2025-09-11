#!/bin/bash

###############################################################################
# AskProAI Master Test Runner & Checklist
# Version: 1.0
# Created: 2025-09-03
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
MASTER_LOG="/var/www/api-gateway/storage/logs/master_test_$(date +%Y%m%d_%H%M%S).log"
RESULTS_DIR="/var/www/api-gateway/storage/logs/test_results_$(date +%Y%m%d_%H%M%S)"

# Test suite configuration
RUN_HEALTH_CHECK=true
RUN_SECURITY_AUDIT=true
RUN_FUNCTIONAL_TESTS=true
RUN_PERFORMANCE_TESTS=true
RUN_ASSET_DETECTION=true

# Results tracking
TOTAL_SUITES=0
PASSED_SUITES=0
FAILED_SUITES=0
WARNINGS=0

# Function to print section headers
print_header() {
    local title="$1"
    local color="${2:-$CYAN}"
    
    echo
    echo -e "${color}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${color} $title${NC}"
    echo -e "${color}═══════════════════════════════════════════════════════════════════${NC}"
    echo
}

# Function to print test suite status
print_suite_status() {
    local suite_name="$1"
    local status="$2"
    local details="$3"
    local log_file="$4"
    
    ((TOTAL_SUITES++))
    
    case "$status" in
        "PASS")
            echo -e "${GREEN}✓ $suite_name${NC}"
            [ -n "$details" ] && echo "  $details"
            [ -n "$log_file" ] && echo "  Log: $log_file"
            ((PASSED_SUITES++))
            ;;
        "FAIL")
            echo -e "${RED}✗ $suite_name${NC}"
            [ -n "$details" ] && echo "  $details"
            [ -n "$log_file" ] && echo "  Log: $log_file"
            ((FAILED_SUITES++))
            ;;
        "WARN")
            echo -e "${YELLOW}⚠ $suite_name${NC}"
            [ -n "$details" ] && echo "  $details"
            [ -n "$log_file" ] && echo "  Log: $log_file"
            ((WARNINGS++))
            ;;
        "SKIP")
            echo -e "${BLUE}⚪ $suite_name (SKIPPED)${NC}"
            [ -n "$details" ] && echo "  $details"
            ;;
    esac
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$status] $suite_name - $details" >> "$MASTER_LOG"
}

# Function to run a test script and capture results
run_test_suite() {
    local script_path="$1"
    local suite_name="$2"
    local timeout="${3:-300}"  # 5 minute default timeout
    
    if [ ! -f "$script_path" ]; then
        print_suite_status "$suite_name" "SKIP" "Script not found: $script_path"
        return 1
    fi
    
    if [ ! -x "$script_path" ]; then
        chmod +x "$script_path"
    fi
    
    echo "Running $suite_name..."
    local start_time
    start_time=$(date +%s)
    
    # Run the test script with timeout
    local output_file="$RESULTS_DIR/${suite_name,,}_output.log"
    local exit_code
    
    timeout "$timeout" "$script_path" > "$output_file" 2>&1
    exit_code=$?
    
    local end_time
    end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # Analyze results based on exit code
    case $exit_code in
        0)
            print_suite_status "$suite_name" "PASS" "Completed in ${duration}s" "$output_file"
            return 0
            ;;
        1|2)
            # Check if it's warnings vs failures
            if grep -q "WARN\|WARNING\|⚠" "$output_file" && ! grep -q "FAIL\|ERROR\|✗.*FAIL" "$output_file"; then
                print_suite_status "$suite_name" "WARN" "Completed with warnings in ${duration}s" "$output_file"
                return 1
            else
                print_suite_status "$suite_name" "FAIL" "Failed after ${duration}s (exit code: $exit_code)" "$output_file"
                return 2
            fi
            ;;
        124)
            print_suite_status "$suite_name" "FAIL" "Timeout after ${timeout}s" "$output_file"
            return 3
            ;;
        *)
            print_suite_status "$suite_name" "FAIL" "Error after ${duration}s (exit code: $exit_code)" "$output_file"
            return 4
            ;;
    esac
}

# Function to generate comprehensive report
generate_report() {
    local report_file="$RESULTS_DIR/comprehensive_test_report.md"
    
    cat > "$report_file" << EOF
# AskProAI Comprehensive Test Report

**Generated:** $(date '+%Y-%m-%d %H:%M:%S')  
**Test Session:** $(basename "$RESULTS_DIR")

## Executive Summary

- **Total Test Suites:** $TOTAL_SUITES
- **Passed:** $PASSED_SUITES
- **Failed:** $FAILED_SUITES  
- **Warnings:** $WARNINGS
- **Success Rate:** $(echo "scale=1; $PASSED_SUITES * 100 / $TOTAL_SUITES" | bc 2>/dev/null || echo "0")%

## Test Results

EOF

    # Add detailed results for each suite
    local suites=("Health Check" "Security Audit" "Functional Tests" "Performance Tests" "Asset Detection")
    
    for suite in "${suites[@]}"; do
        local suite_file="$RESULTS_DIR/${suite,,//[^a-z0-9]/_}_output.log"
        if [ -f "$suite_file" ]; then
            echo "### $suite" >> "$report_file"
            echo "" >> "$report_file"
            
            # Extract summary from log file
            local summary
            if grep -q "✓.*passed\|✓.*EXCELLENT\|✓.*operational" "$suite_file"; then
                summary="✅ **PASSED** - All checks completed successfully"
            elif grep -q "⚠.*warnings\|⚠.*GOOD" "$suite_file"; then
                summary="⚠️ **WARNINGS** - Issues detected but not critical"
            elif grep -q "✗.*failed\|✗.*CRITICAL\|✗.*ISSUES" "$suite_file"; then
                summary="❌ **FAILED** - Critical issues require attention"
            else
                summary="❓ **UNKNOWN** - Status unclear from logs"
            fi
            
            echo "$summary" >> "$report_file"
            echo "" >> "$report_file"
            
            # Add key metrics if available
            if grep -q "Memory usage:\|Disk usage:\|Response time:" "$suite_file"; then
                echo "**Key Metrics:**" >> "$report_file"
                grep -E "Memory usage:|Disk usage:|Response time:|Status:|requests per second:" "$suite_file" | sed 's/^/- /' >> "$report_file"
                echo "" >> "$report_file"
            fi
            
            # Add critical issues
            local critical_issues
            critical_issues=$(grep -E "✗.*|FAIL.*|ERROR.*|CRITICAL.*" "$suite_file" | head -5)
            if [ -n "$critical_issues" ]; then
                echo "**Critical Issues:**" >> "$report_file"
                echo '```' >> "$report_file"
                echo "$critical_issues" >> "$report_file"
                echo '```' >> "$report_file"
                echo "" >> "$report_file"
            fi
            
            echo "**Full Log:** \`$suite_file\`" >> "$report_file"
            echo "" >> "$report_file"
        fi
    done
    
    # Add recommendations
    cat >> "$report_file" << EOF
## Recommendations

### Immediate Actions Required
EOF
    
    if [ $FAILED_SUITES -gt 0 ]; then
        echo "- ⚠️ **$FAILED_SUITES test suites failed** - Review failed test logs immediately" >> "$report_file"
        echo "- 🔍 Check system logs for underlying issues: \`tail -f /var/log/nginx/error.log\`" >> "$report_file"
    fi
    
    if [ $WARNINGS -gt 0 ]; then
        echo "- ⚡ **$WARNINGS test suites have warnings** - Schedule maintenance to address" >> "$report_file"
    fi
    
    cat >> "$report_file" << EOF

### Monitoring Commands

\`\`\`bash
# Quick health check
cd /var/www/api-gateway/scripts && ./comprehensive_health_check.sh

# Security scan
cd /var/www/api-gateway/scripts && ./security_audit.sh

# Performance check
cd /var/www/api-gateway/scripts && ./performance_test_suite.sh

# Asset verification
cd /var/www/api-gateway/scripts && ./missing_asset_detector.sh
\`\`\`

### SuperClaude Commands

For automated fixes and deeper analysis:

\`\`\`bash
# Load comprehensive testing framework
/sc:load testing-framework

# Run automated system optimization  
/sc:optimize --target=performance --scope=system

# Generate security recommendations
/sc:secure --audit --recommendations

# Asset management and optimization
/sc:assets --detect-missing --optimize --rebuild
\`\`\`

## Files Generated

- **Master Log:** \`$MASTER_LOG\`
- **Results Directory:** \`$RESULTS_DIR\`
- **This Report:** \`$report_file\`

---

*Report generated by AskProAI Master Test Runner v1.0*
EOF

    echo "Comprehensive report generated: $report_file"
}

# Function to show usage
show_usage() {
    cat << EOF
AskProAI Master Test Runner

Usage: $0 [OPTIONS]

Options:
    --health-only       Run only health checks
    --security-only     Run only security audit
    --functional-only   Run only functional tests  
    --performance-only  Run only performance tests
    --assets-only       Run only asset detection
    --skip-health       Skip health checks
    --skip-security     Skip security audit
    --skip-functional   Skip functional tests
    --skip-performance  Skip performance tests
    --skip-assets       Skip asset detection
    --quick             Run with reduced timeouts for quick feedback
    --help              Show this help message

Examples:
    $0                      # Run all test suites
    $0 --quick             # Quick run with reduced timeouts
    $0 --security-only     # Only run security audit
    $0 --skip-performance  # Run all except performance tests

SuperClaude Integration:
    /sc:test --comprehensive --report
    /sc:health --full-check --security-audit
    /sc:monitor --system-status --performance-check
EOF
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --health-only)
                RUN_HEALTH_CHECK=true
                RUN_SECURITY_AUDIT=false
                RUN_FUNCTIONAL_TESTS=false
                RUN_PERFORMANCE_TESTS=false
                RUN_ASSET_DETECTION=false
                shift
                ;;
            --security-only)
                RUN_HEALTH_CHECK=false
                RUN_SECURITY_AUDIT=true
                RUN_FUNCTIONAL_TESTS=false
                RUN_PERFORMANCE_TESTS=false
                RUN_ASSET_DETECTION=false
                shift
                ;;
            --functional-only)
                RUN_HEALTH_CHECK=false
                RUN_SECURITY_AUDIT=false
                RUN_FUNCTIONAL_TESTS=true
                RUN_PERFORMANCE_TESTS=false
                RUN_ASSET_DETECTION=false
                shift
                ;;
            --performance-only)
                RUN_HEALTH_CHECK=false
                RUN_SECURITY_AUDIT=false
                RUN_FUNCTIONAL_TESTS=false
                RUN_PERFORMANCE_TESTS=true
                RUN_ASSET_DETECTION=false
                shift
                ;;
            --assets-only)
                RUN_HEALTH_CHECK=false
                RUN_SECURITY_AUDIT=false
                RUN_FUNCTIONAL_TESTS=false
                RUN_PERFORMANCE_TESTS=false
                RUN_ASSET_DETECTION=true
                shift
                ;;
            --skip-health)
                RUN_HEALTH_CHECK=false
                shift
                ;;
            --skip-security)
                RUN_SECURITY_AUDIT=false
                shift
                ;;
            --skip-functional)
                RUN_FUNCTIONAL_TESTS=false
                shift
                ;;
            --skip-performance)
                RUN_PERFORMANCE_TESTS=false
                shift
                ;;
            --skip-assets)
                RUN_ASSET_DETECTION=false
                shift
                ;;
            --quick)
                QUICK_MODE=true
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
}

###############################################################################
# MAIN EXECUTION
###############################################################################

# Parse command line arguments
parse_args "$@"

# Print banner
print_header "AskProAI Comprehensive Testing Suite" "$MAGENTA"

cat << EOF
🚀 **System Under Test:** AskProAI API Gateway
📅 **Test Session:** $(date '+%Y-%m-%d %H:%M:%S')
🌐 **Environment:** $(grep "^APP_ENV=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d= -f2 || echo "unknown")
📍 **Base URL:** $(grep "^APP_URL=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d= -f2 || echo "unknown")

**Test Configuration:**
- Health Check: $([ "$RUN_HEALTH_CHECK" = true ] && echo "✅ Enabled" || echo "❌ Disabled")
- Security Audit: $([ "$RUN_SECURITY_AUDIT" = true ] && echo "✅ Enabled" || echo "❌ Disabled")  
- Functional Tests: $([ "$RUN_FUNCTIONAL_TESTS" = true ] && echo "✅ Enabled" || echo "❌ Disabled")
- Performance Tests: $([ "$RUN_PERFORMANCE_TESTS" = true ] && echo "✅ Enabled" || echo "❌ Disabled")
- Asset Detection: $([ "$RUN_ASSET_DETECTION" = true ] && echo "✅ Enabled" || echo "❌ Disabled")
EOF

# Initialize logging and results directory
mkdir -p "$RESULTS_DIR"
echo "Master Test Runner Started - $(date)" > "$MASTER_LOG"
echo "Results will be stored in: $RESULTS_DIR"

# Set timeouts based on mode
local DEFAULT_TIMEOUT=300
local QUICK_TIMEOUT=60
local TIMEOUT=$DEFAULT_TIMEOUT

if [ "$QUICK_MODE" = true ]; then
    TIMEOUT=$QUICK_TIMEOUT
    echo "🚀 **Quick Mode:** Reduced timeouts for faster feedback"
fi

echo

# Run test suites
print_header "Executing Test Suites" "$BLUE"

# 1. System Health Check
if [ "$RUN_HEALTH_CHECK" = true ]; then
    run_test_suite "$SCRIPT_DIR/comprehensive_health_check.sh" "Health Check" $TIMEOUT
fi

# 2. Security Audit
if [ "$RUN_SECURITY_AUDIT" = true ]; then
    run_test_suite "$SCRIPT_DIR/security_audit.sh" "Security Audit" $TIMEOUT
fi

# 3. Functional Tests
if [ "$RUN_FUNCTIONAL_TESTS" = true ]; then
    run_test_suite "$SCRIPT_DIR/functional_test_suite.sh" "Functional Tests" $TIMEOUT
fi

# 4. Performance Tests
if [ "$RUN_PERFORMANCE_TESTS" = true ]; then
    run_test_suite "$SCRIPT_DIR/performance_test_suite.sh" "Performance Tests" $((TIMEOUT * 2))
fi

# 5. Asset Detection
if [ "$RUN_ASSET_DETECTION" = true ]; then
    run_test_suite "$SCRIPT_DIR/missing_asset_detector.sh" "Asset Detection" $TIMEOUT
fi

# Generate comprehensive report
print_header "Generating Comprehensive Report" "$CYAN"
generate_report

# Final summary
print_header "Test Session Summary" "$MAGENTA"

echo "📊 **Results Overview:**"
echo "   Total Suites Run: $TOTAL_SUITES"
echo -e "   ${GREEN}✓ Passed: $PASSED_SUITES${NC}"
echo -e "   ${RED}✗ Failed: $FAILED_SUITES${NC}"
echo -e "   ${YELLOW}⚠ Warnings: $WARNINGS${NC}"

if [ $TOTAL_SUITES -gt 0 ]; then
    local success_rate
    success_rate=$(echo "scale=1; $PASSED_SUITES * 100 / $TOTAL_SUITES" | bc 2>/dev/null || echo "0")
    echo "   📈 Success Rate: ${success_rate}%"
fi

echo
echo "📁 **Generated Files:**"
echo "   Master Log: $MASTER_LOG"
echo "   Results Directory: $RESULTS_DIR"
echo "   Comprehensive Report: $RESULTS_DIR/comprehensive_test_report.md"

echo

# Final recommendations
if [ $FAILED_SUITES -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}🎉 All systems are operating optimally!${NC}"
    echo "✅ No critical issues detected"
    echo "📋 Regular monitoring recommended"
elif [ $FAILED_SUITES -eq 0 ]; then
    echo -e "${YELLOW}⚠️ System is stable with minor warnings${NC}"
    echo "📝 Review warning details in individual test logs"
    echo "📅 Schedule maintenance for optimal performance"
else
    echo -e "${RED}🚨 Critical issues detected requiring immediate attention${NC}"
    echo "🔧 Review failed test logs for detailed remediation steps"
    echo "📞 Consider escalating to system administrator"
fi

echo
echo "🔗 **Next Steps:**"
if [ $FAILED_SUITES -gt 0 ]; then
    echo "   1. Review failed test logs immediately"
    echo "   2. Address critical issues using provided recommendations"
    echo "   3. Re-run tests after fixes: $0"
elif [ $WARNINGS -gt 0 ]; then
    echo "   1. Review warning details in test logs"
    echo "   2. Plan maintenance to address warnings"
    echo "   3. Monitor system regularly: $0 --quick"
else
    echo "   1. Implement regular testing schedule"
    echo "   2. Monitor system performance trends"
    echo "   3. Keep testing suite updated"
fi

print_header "Testing Complete" "$MAGENTA"

# Exit with appropriate code
if [ $FAILED_SUITES -gt 0 ]; then
    exit 1
elif [ $WARNINGS -gt 0 ]; then
    exit 2
else
    exit 0
fi