#!/bin/bash

# SuperClaude Backup Test Suite Runner
# Version: 2.0.0
# Comprehensive testing framework for backup system
# Integrates with /sc:test capabilities

set -euo pipefail

# =============================================================================
# CONFIGURATION
# =============================================================================

TEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
RESULTS_FILE="$TEST_DIR/results_$TIMESTAMP.txt"
COVERAGE_FILE="$TEST_DIR/coverage_$TIMESTAMP.txt"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Test statistics
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
SKIPPED_TESTS=0
TEST_RESULTS=()

# Test types
TEST_TYPES=("unit" "integration" "chaos")

# =============================================================================
# TEST FRAMEWORK
# =============================================================================

log() {
    local level="$1"
    shift
    local message="$@"
    
    case "$level" in
        PASS)    echo -e "${GREEN}✓${NC} $message" ;;
        FAIL)    echo -e "${RED}✗${NC} $message" ;;
        SKIP)    echo -e "${YELLOW}○${NC} $message" ;;
        INFO)    echo -e "${BLUE}ℹ${NC} $message" ;;
        TEST)    echo -e "${CYAN}▶${NC} $message" ;;
        SECTION) echo -e "${MAGENTA}═══ $message ═══${NC}" ;;
        *)       echo "$message" ;;
    esac
    
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$level] $message" >> "$RESULTS_FILE"
}

run_test() {
    local test_file="$1"
    local test_name=$(basename "$test_file" .sh)
    
    ((TOTAL_TESTS++))
    log TEST "Running: $test_name"
    
    local start_time=$(date +%s)
    
    if [ -x "$test_file" ]; then
        if timeout 60 "$test_file" &>/dev/null; then
            local exit_code=$?
            local end_time=$(date +%s)
            local duration=$((end_time - start_time))
            
            if [ $exit_code -eq 0 ]; then
                log PASS "$test_name (${duration}s)"
                ((PASSED_TESTS++))
                TEST_RESULTS+=("PASS|$test_name|${duration}s")
            else
                log FAIL "$test_name (exit: $exit_code, ${duration}s)"
                ((FAILED_TESTS++))
                TEST_RESULTS+=("FAIL|$test_name|exit:$exit_code|${duration}s")
            fi
        else
            log FAIL "$test_name (timeout)"
            ((FAILED_TESTS++))
            TEST_RESULTS+=("FAIL|$test_name|timeout")
        fi
    else
        log SKIP "$test_name (not executable)"
        ((SKIPPED_TESTS++))
        TEST_RESULTS+=("SKIP|$test_name|not_executable")
    fi
}

run_test_suite() {
    local suite="$1"
    local suite_dir="$TEST_DIR/$suite"
    
    if [ -d "$suite_dir" ]; then
        log SECTION "Running $suite Tests"
        
        for test_file in "$suite_dir"/test-*.sh; do
            [ -f "$test_file" ] && run_test "$test_file"
        done
    else
        log SKIP "Suite not found: $suite"
    fi
}

calculate_coverage() {
    log INFO "Calculating test coverage..."
    
    local scripts_dir="/var/www/api-gateway/scripts"
    local total_functions=0
    local tested_functions=0
    
    # Count functions in scripts
    for script in "$scripts_dir"/sc-backup-*.sh; do
        [ -f "$script" ] || continue
        local functions=$(grep -c "^[[:space:]]*[a-z_]*()[[:space:]]*{" "$script" 2>/dev/null || echo 0)
        total_functions=$((total_functions + functions))
    done
    
    # Estimate tested functions based on test count
    tested_functions=$((PASSED_TESTS * 3)) # Rough estimate
    
    local coverage=0
    if [ $total_functions -gt 0 ]; then
        coverage=$((tested_functions * 100 / total_functions))
    fi
    
    {
        echo "=== Test Coverage Report ==="
        echo "Total Functions: $total_functions"
        echo "Tested Functions: $tested_functions (estimated)"
        echo "Coverage: ${coverage}%"
        echo ""
        echo "Test Files:"
        find "$TEST_DIR" -name "test-*.sh" -type f | while read -r test_file; do
            echo "  - $(basename "$test_file")"
        done
    } > "$COVERAGE_FILE"
    
    log INFO "Coverage: ${coverage}% (estimated)"
}

generate_report() {
    local success_rate=0
    if [ $TOTAL_TESTS -gt 0 ]; then
        success_rate=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    fi
    
    {
        echo "================================================"
        echo "SuperClaude Backup Test Report - $TIMESTAMP"
        echo "================================================"
        echo ""
        echo "Summary:"
        echo "  Total Tests:   $TOTAL_TESTS"
        echo "  Passed:        $PASSED_TESTS"
        echo "  Failed:        $FAILED_TESTS"
        echo "  Skipped:       $SKIPPED_TESTS"
        echo "  Success Rate:  ${success_rate}%"
        echo ""
        echo "Test Results:"
        for result in "${TEST_RESULTS[@]}"; do
            echo "  $result"
        done
        echo ""
        echo "================================================"
    } | tee -a "$RESULTS_FILE"
    
    # Print to console
    echo ""
    if [ $FAILED_TESTS -eq 0 ] && [ $TOTAL_TESTS -gt 0 ]; then
        echo -e "${GREEN}═══ ALL TESTS PASSED ═══${NC}"
    elif [ $FAILED_TESTS -gt 0 ]; then
        echo -e "${RED}═══ ${FAILED_TESTS} TESTS FAILED ═══${NC}"
    fi
}

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --all        Run all test suites"
    echo "  --unit       Run unit tests only"
    echo "  --integration Run integration tests only"
    echo "  --chaos      Run chaos engineering tests only"
    echo "  --coverage   Generate coverage report"
    echo "  --help       Show this help message"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local run_unit=false
    local run_integration=false
    local run_chaos=false
    local run_coverage=false
    
    # Parse arguments
    if [ $# -eq 0 ]; then
        run_unit=true
        run_integration=true
    fi
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --all)
                run_unit=true
                run_integration=true
                run_chaos=true
                shift
                ;;
            --unit)
                run_unit=true
                shift
                ;;
            --integration)
                run_integration=true
                shift
                ;;
            --chaos)
                run_chaos=true
                shift
                ;;
            --coverage)
                run_coverage=true
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    log SECTION "SuperClaude Backup Test Suite v2.0"
    log INFO "Starting test execution at $TIMESTAMP"
    echo ""
    
    # Run selected test suites
    [ "$run_unit" = true ] && run_test_suite "unit"
    [ "$run_integration" = true ] && run_test_suite "integration"
    [ "$run_chaos" = true ] && run_test_suite "chaos"
    
    # Calculate coverage if requested
    [ "$run_coverage" = true ] && calculate_coverage
    
    # Generate report
    generate_report
    
    # Exit code based on failures
    [ $FAILED_TESTS -eq 0 ] && exit 0 || exit 1
}

# Run main
main "$@"