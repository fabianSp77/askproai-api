#!/bin/bash

# AskProAI Security Test Runner
# Comprehensive security vulnerability testing script
# 
# Usage: ./security-test-runner.sh [options]
# Options:
#   --all          Run all security tests
#   --critical     Run only critical severity tests
#   --fast         Run quick security checks only
#   --coverage     Generate test coverage report
#   --report       Generate detailed security report
#   --continuous   Run in continuous monitoring mode
#   --parallel     Run tests in parallel (faster)
#   --verbose      Verbose output
#   --help         Show this help message

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
SECURITY_LOG_DIR="$PROJECT_DIR/storage/logs/security"
REPORT_DIR="$PROJECT_DIR/security-reports"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

# Test categories
CRITICAL_TESTS=(
    "CrossTenantAuthenticationTest"
    "AdminApiAccessControlTest"
    "WebhookDataContaminationTest"
    "AuthenticationBypassTest"
    "DatabaseSecurityTest"
)

MEDIUM_TESTS=(
    "SessionIsolationTest"
    "InputValidationSecurityTest"
    "DataLeakageTest"
    "ApiSecurityVulnerabilitiesTest"
    "FileSystemSecurityTest"
)

FAST_TESTS=(
    "CrossTenantAuthenticationTest::test_admin_users_cannot_access_other_company_data"
    "AdminApiAccessControlTest::test_all_admin_api_endpoints_require_authentication"
    "SessionIsolationTest::test_sessions_are_isolated_between_companies"
    "InputValidationSecurityTest::test_sql_injection_protection_across_all_endpoints"
)

# Default values
RUN_ALL=false
RUN_CRITICAL=false
RUN_FAST=false
GENERATE_COVERAGE=false
GENERATE_REPORT=false
CONTINUOUS_MODE=false
PARALLEL=false
VERBOSE=false

# Functions
print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║                    AskProAI Security Test Runner                 ║"
    echo "║                                                                  ║"
    echo "║  Comprehensive security vulnerability testing for AskProAI       ║"
    echo "║  Platform. Tests critical security aspects including:           ║"
    echo "║                                                                  ║"
    echo "║  • Cross-tenant authentication & authorization                   ║"
    echo "║  • API access control & privilege escalation                    ║"
    echo "║  • Session isolation & management                                ║"
    echo "║  • Input validation & injection attacks                         ║"
    echo "║  • Webhook data contamination                                    ║"
    echo "║  • File system & database security                              ║"
    echo "║                                                                  ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_help() {
    echo -e "${WHITE}AskProAI Security Test Runner${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC} ./security-test-runner.sh [options]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo -e "  ${GREEN}--all${NC}          Run all security tests (recommended for CI/CD)"
    echo -e "  ${GREEN}--critical${NC}     Run only critical severity tests"
    echo -e "  ${GREEN}--fast${NC}         Run quick security checks only"
    echo -e "  ${GREEN}--coverage${NC}     Generate test coverage report"
    echo -e "  ${GREEN}--report${NC}       Generate detailed security report"
    echo -e "  ${GREEN}--continuous${NC}   Run in continuous monitoring mode"
    echo -e "  ${GREEN}--parallel${NC}     Run tests in parallel (faster execution)"
    echo -e "  ${GREEN}--verbose${NC}      Verbose output with detailed logging"
    echo -e "  ${GREEN}--help${NC}         Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo -e "  ${CYAN}./security-test-runner.sh --critical${NC}          # Quick critical tests"
    echo -e "  ${CYAN}./security-test-runner.sh --all --coverage${NC}    # Full test with coverage"
    echo -e "  ${CYAN}./security-test-runner.sh --fast --parallel${NC}   # Fast parallel testing"
    echo -e "  ${CYAN}./security-test-runner.sh --continuous${NC}        # Continuous monitoring"
    echo ""
    echo -e "${YELLOW}Test Categories:${NC}"
    echo -e "  ${RED}Critical:${NC} Cross-tenant, Admin API, Webhooks, Auth bypass, Database"
    echo -e "  ${YELLOW}Medium:${NC}   Sessions, Input validation, Data leakage, API security, Files"
    echo -e "  ${GREEN}Fast:${NC}     Essential checks that run in under 30 seconds"
}

log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${GREEN}[INFO]${NC} $message"
            ;;
        "WARN")
            echo -e "${YELLOW}[WARN]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
        "DEBUG")
            if [ "$VERBOSE" = true ]; then
                echo -e "${BLUE}[DEBUG]${NC} $message"
            fi
            ;;
    esac
    
    # Log to file
    mkdir -p "$SECURITY_LOG_DIR"
    echo "[$timestamp] [$level] $message" >> "$SECURITY_LOG_DIR/security-tests.log"
}

check_prerequisites() {
    log_message "INFO" "Checking prerequisites..."
    
    # Check if we're in a Laravel project
    if [ ! -f "$PROJECT_DIR/artisan" ]; then
        log_message "ERROR" "Not in a Laravel project directory"
        exit 1
    fi
    
    # Check if composer dependencies are installed
    if [ ! -d "$PROJECT_DIR/vendor" ]; then
        log_message "ERROR" "Composer dependencies not installed. Run 'composer install'"
        exit 1
    fi
    
    # Check if PHPUnit is available
    if [ ! -f "$PROJECT_DIR/vendor/bin/phpunit" ]; then
        log_message "ERROR" "PHPUnit not found. Install via composer"
        exit 1
    fi
    
    # Check database connection
    if ! php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
        log_message "WARN" "Database connection failed. Some tests may fail"
    fi
    
    # Check if security test directory exists
    if [ ! -d "$PROJECT_DIR/tests/Feature/Security" ]; then
        log_message "ERROR" "Security test directory not found"
        exit 1
    fi
    
    log_message "INFO" "Prerequisites check completed"
}

setup_test_environment() {
    log_message "INFO" "Setting up test environment..."
    
    # Create necessary directories
    mkdir -p "$SECURITY_LOG_DIR"
    mkdir -p "$REPORT_DIR"
    
    # Set environment to testing
    export APP_ENV=testing
    
    # Clear caches
    if [ "$VERBOSE" = true ]; then
        php artisan config:clear
        php artisan cache:clear
        php artisan route:clear
    else
        php artisan config:clear >/dev/null 2>&1
        php artisan cache:clear >/dev/null 2>&1
        php artisan route:clear >/dev/null 2>&1
    fi
    
    log_message "INFO" "Test environment setup completed"
}

run_security_tests() {
    local test_category=$1
    local tests_to_run=()
    
    case $test_category in
        "critical")
            tests_to_run=("${CRITICAL_TESTS[@]}")
            log_message "INFO" "Running CRITICAL security tests..."
            ;;
        "medium")
            tests_to_run=("${MEDIUM_TESTS[@]}")
            log_message "INFO" "Running MEDIUM priority security tests..."
            ;;
        "fast")
            log_message "INFO" "Running FAST security checks..."
            run_fast_tests
            return $?
            ;;
        "all")
            tests_to_run=("${CRITICAL_TESTS[@]}" "${MEDIUM_TESTS[@]}")
            log_message "INFO" "Running ALL security tests..."
            ;;
    esac
    
    local failed_tests=()
    local passed_tests=()
    local total_tests=${#tests_to_run[@]}
    local current_test=0
    
    for test in "${tests_to_run[@]}"; do
        current_test=$((current_test + 1))
        log_message "INFO" "Running test [$current_test/$total_tests]: $test"
        
        local test_command="vendor/bin/phpunit tests/Feature/Security/${test}.php"
        
        if [ "$GENERATE_COVERAGE" = true ]; then
            test_command="$test_command --coverage-html $REPORT_DIR/coverage"
        fi
        
        if [ "$PARALLEL" = true ]; then
            test_command="$test_command --processes=4"
        fi
        
        if [ "$VERBOSE" = true ]; then
            test_command="$test_command --verbose"
        else
            test_command="$test_command --quiet"
        fi
        
        local start_time=$(date +%s)
        
        if eval "$test_command"; then
            local end_time=$(date +%s)
            local duration=$((end_time - start_time))
            passed_tests+=("$test")
            log_message "INFO" "✅ PASSED: $test (${duration}s)"
        else
            local end_time=$(date +%s)
            local duration=$((end_time - start_time))
            failed_tests+=("$test")
            log_message "ERROR" "❌ FAILED: $test (${duration}s)"
        fi
    done
    
    # Print summary
    echo -e "\n${WHITE}Security Test Summary:${NC}"
    echo -e "${GREEN}Passed: ${#passed_tests[@]}${NC}"
    echo -e "${RED}Failed: ${#failed_tests[@]}${NC}"
    echo -e "${BLUE}Total:  $total_tests${NC}"
    
    if [ ${#failed_tests[@]} -gt 0 ]; then
        echo -e "\n${RED}Failed tests:${NC}"
        for test in "${failed_tests[@]}"; do
            echo -e "  ${RED}• $test${NC}"
        done
        return 1
    fi
    
    return 0
}

run_fast_tests() {
    log_message "INFO" "Running fast security checks..."
    
    local failed_tests=()
    local passed_tests=()
    
    for test in "${FAST_TESTS[@]}"; do
        log_message "DEBUG" "Running: $test"
        
        if vendor/bin/phpunit --filter="$test" tests/Feature/Security/ --quiet; then
            passed_tests+=("$test")
            log_message "INFO" "✅ PASSED: $test"
        else
            failed_tests+=("$test")
            log_message "ERROR" "❌ FAILED: $test"
        fi
    done
    
    echo -e "\n${WHITE}Fast Security Check Summary:${NC}"
    echo -e "${GREEN}Passed: ${#passed_tests[@]}${NC}"
    echo -e "${RED}Failed: ${#failed_tests[@]}${NC}"
    
    if [ ${#failed_tests[@]} -gt 0 ]; then
        return 1
    fi
    
    return 0
}

generate_security_report() {
    if [ "$GENERATE_REPORT" = false ]; then
        return 0
    fi
    
    log_message "INFO" "Generating security report..."
    
    local report_file="$REPORT_DIR/security-report-$TIMESTAMP.html"
    
    cat > "$report_file" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Security Test Report - $TIMESTAMP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .critical { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
        .test-result { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
        .passed { border-left-color: #28a745; background: #f8fff8; }
        .failed { border-left-color: #dc3545; background: #fff8f8; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AskProAI Security Test Report</h1>
        <p><strong>Generated:</strong> $(date)</p>
        <p><strong>Environment:</strong> $(php artisan --version)</p>
        <p><strong>Test Suite:</strong> Security Vulnerability Tests</p>
    </div>
    
    <h2>Executive Summary</h2>
    <p>This report contains the results of comprehensive security testing for the AskProAI platform.</p>
    
    <h2>Test Categories Covered</h2>
    <ul>
        <li><strong>Cross-Tenant Authentication:</strong> Multi-company data isolation</li>
        <li><strong>Admin API Access Control:</strong> Administrative privilege protection</li>
        <li><strong>Session Management:</strong> Session isolation and security</li>
        <li><strong>Input Validation:</strong> Injection attack protection</li>
        <li><strong>Webhook Security:</strong> Data contamination prevention</li>
        <li><strong>File System Security:</strong> Upload and access control</li>
        <li><strong>Database Security:</strong> SQL injection and data protection</li>
    </ul>
    
    <h2>Test Results</h2>
    <div class="test-result passed">
        <h3>✅ Security tests completed</h3>
        <p>For detailed test results, check the log files in storage/logs/security/</p>
    </div>
    
    <h2>Recommendations</h2>
    <ul>
        <li>Run security tests regularly in CI/CD pipeline</li>
        <li>Monitor security logs for anomalies</li>
        <li>Keep dependencies updated</li>
        <li>Review and update security policies quarterly</li>
    </ul>
    
    <h2>Next Steps</h2>
    <p>1. Address any failed security tests immediately</p>
    <p>2. Implement automated security monitoring</p>
    <p>3. Schedule regular penetration testing</p>
    <p>4. Update security documentation</p>
    
</body>
</html>
EOF
    
    log_message "INFO" "Security report generated: $report_file"
    
    # Also generate a JSON summary
    local json_report="$REPORT_DIR/security-summary-$TIMESTAMP.json"
    cat > "$json_report" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "version": "1.0",
    "platform": "AskProAI",
    "test_suite": "Security Vulnerability Tests",
    "environment": "$(php artisan --version | head -n1)",
    "summary": {
        "total_tests": "Variable",
        "status": "Completed",
        "report_file": "$report_file"
    },
    "test_categories": [
        "Cross-Tenant Authentication",
        "Admin API Access Control", 
        "Session Management",
        "Input Validation",
        "Webhook Security",
        "File System Security",
        "Database Security"
    ]
}
EOF
    
    log_message "INFO" "JSON summary generated: $json_report"
}

run_continuous_monitoring() {
    log_message "INFO" "Starting continuous security monitoring..."
    log_message "INFO" "Press Ctrl+C to stop monitoring"
    
    while true; do
        echo -e "\n${CYAN}$(date): Running security check...${NC}"
        
        if run_security_tests "fast"; then
            log_message "INFO" "✅ Security check passed"
        else
            log_message "ERROR" "❌ Security issues detected!"
            
            # Send alert (implement notification system)
            # send_security_alert "Security issues detected in continuous monitoring"
        fi
        
        log_message "INFO" "Next check in 300 seconds (5 minutes)..."
        sleep 300
    done
}

cleanup() {
    log_message "INFO" "Cleaning up..."
    
    # Reset environment
    php artisan config:clear >/dev/null 2>&1
    
    log_message "INFO" "Cleanup completed"
}

main() {
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --all)
                RUN_ALL=true
                shift
                ;;
            --critical)
                RUN_CRITICAL=true
                shift
                ;;
            --fast)
                RUN_FAST=true
                shift
                ;;
            --coverage)
                GENERATE_COVERAGE=true
                shift
                ;;
            --report)
                GENERATE_REPORT=true
                shift
                ;;
            --continuous)
                CONTINUOUS_MODE=true
                shift
                ;;
            --parallel)
                PARALLEL=true
                shift
                ;;
            --verbose)
                VERBOSE=true
                shift
                ;;
            --help)
                print_help
                exit 0
                ;;
            *)
                echo -e "${RED}Unknown option: $1${NC}"
                print_help
                exit 1
                ;;
        esac
    done
    
    # Default to fast tests if no option specified
    if [ "$RUN_ALL" = false ] && [ "$RUN_CRITICAL" = false ] && [ "$RUN_FAST" = false ] && [ "$CONTINUOUS_MODE" = false ]; then
        RUN_FAST=true
    fi
    
    print_banner
    
    # Setup trap for cleanup
    trap cleanup EXIT
    
    check_prerequisites
    setup_test_environment
    
    local exit_code=0
    
    if [ "$CONTINUOUS_MODE" = true ]; then
        run_continuous_monitoring
    elif [ "$RUN_ALL" = true ]; then
        run_security_tests "all" || exit_code=$?
    elif [ "$RUN_CRITICAL" = true ]; then
        run_security_tests "critical" || exit_code=$?
    elif [ "$RUN_FAST" = true ]; then
        run_security_tests "fast" || exit_code=$?
    fi
    
    generate_security_report
    
    if [ $exit_code -eq 0 ]; then
        echo -e "\n${GREEN}🔒 Security tests completed successfully!${NC}"
        log_message "INFO" "All security tests passed"
    else
        echo -e "\n${RED}🚨 Security issues detected! Please review failed tests.${NC}"
        log_message "ERROR" "Security tests failed - immediate attention required"
    fi
    
    exit $exit_code
}

# Make sure script is executable
chmod +x "$0"

# Run main function with all arguments
main "$@"