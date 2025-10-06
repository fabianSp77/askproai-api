#!/bin/bash

#==============================================================================
# Cal.com V2 Integration - Automated Test Runner
#==============================================================================
# This script runs all Cal.com integration tests systematically to ensure
# the middleware works "extrem sauber" (extremely cleanly) with Cal.com API
#
# Usage:
#   ./run-calcom-tests.sh [options]
#
# Options:
#   --all              Run all test suites
#   --unit             Run unit tests only
#   --integration      Run integration tests only
#   --live             Run live API tests (requires valid API key)
#   --performance      Run performance tests
#   --error-handling   Run error handling tests
#   --sync             Run synchronization tests
#   --report           Generate detailed HTML report
#   --ci               CI mode (fail on first error)
#   --verbose          Verbose output
#   --parallel         Run tests in parallel
#   --coverage         Generate code coverage report
#==============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="storage/test-reports/calcom"
LOG_FILE="${REPORT_DIR}/test_run_${TIMESTAMP}.log"
RESULTS_FILE="${REPORT_DIR}/results_${TIMESTAMP}.json"
HTML_REPORT="${REPORT_DIR}/report_${TIMESTAMP}.html"

# Test suites
TEST_SUITES=(
    "CalcomV2ClientTest"
    "CalcomV2IntegrationTest"
    "CalcomV2SyncTest"
    "CalcomV2ErrorHandlingTest"
    "CalcomV2PerformanceTest"
    "CalcomV2ExtendedIntegrationTest"
)

LIVE_TEST_SUITES=(
    "CalcomV2LiveTest"
)

# Flags
RUN_ALL=false
RUN_UNIT=false
RUN_INTEGRATION=false
RUN_LIVE=false
RUN_PERFORMANCE=false
RUN_ERROR_HANDLING=false
RUN_SYNC=false
GENERATE_REPORT=false
CI_MODE=false
VERBOSE=false
PARALLEL=false
COVERAGE=false

#==============================================================================
# Functions
#==============================================================================

show_help() {
    echo "Cal.com V2 Integration Test Runner"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  --all              Run all test suites"
    echo "  --unit             Run unit tests only"
    echo "  --integration      Run integration tests only"
    echo "  --live             Run live API tests (requires valid API key)"
    echo "  --performance      Run performance tests"
    echo "  --error-handling   Run error handling tests"
    echo "  --sync             Run synchronization tests"
    echo "  --report           Generate detailed HTML report"
    echo "  --ci               CI mode (fail on first error)"
    echo "  --verbose          Verbose output"
    echo "  --parallel         Run tests in parallel"
    echo "  --coverage         Generate code coverage report"
    echo "  --help             Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 --all                    # Run all tests"
    echo "  $0 --integration --report   # Run integration tests with report"
    echo "  $0 --live --verbose         # Run live tests with verbose output"
    echo ""
}

log() {
    local message="$1"
    local level="${2:-INFO}"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    case $level in
        ERROR)
            echo -e "${RED}[ERROR]${NC} ${message}"
            ;;
        SUCCESS)
            echo -e "${GREEN}[SUCCESS]${NC} ${message}"
            ;;
        WARNING)
            echo -e "${YELLOW}[WARNING]${NC} ${message}"
            ;;
        INFO)
            echo -e "${BLUE}[INFO]${NC} ${message}"
            ;;
        *)
            echo "${message}"
            ;;
    esac

    echo "[${timestamp}] [${level}] ${message}" >> "$LOG_FILE"
}

setup_environment() {
    log "Setting up test environment..." "INFO"

    # Create report directory
    mkdir -p "$REPORT_DIR"

    # Check if we're in the right directory
    if [ ! -f "artisan" ]; then
        log "Error: Must run from Laravel root directory" "ERROR"
        exit 1
    fi

    # Check PHP version
    PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
    log "PHP Version: $PHP_VERSION" "INFO"

    # Check if PHPUnit is installed
    if [ ! -f "vendor/bin/phpunit" ]; then
        log "PHPUnit not found. Installing dependencies..." "WARNING"
        composer install --no-interaction
    fi

    # Clear caches
    php artisan cache:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true

    # Check database connection
    php artisan migrate:status > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        log "Database connection failed. Setting up test database..." "WARNING"
        php artisan migrate --env=testing --force
    fi

    log "Environment setup complete" "SUCCESS"
}

check_api_credentials() {
    log "Checking Cal.com API credentials..." "INFO"

    # Check for API key in .env.testing
    if [ -f ".env.testing" ]; then
        API_KEY=$(grep "CALCOM_API_KEY" .env.testing | cut -d '=' -f2)
        if [ -z "$API_KEY" ] || [ "$API_KEY" == "null" ]; then
            log "Cal.com API key not configured in .env.testing" "WARNING"
            return 1
        fi
    else
        log ".env.testing file not found" "WARNING"
        return 1
    fi

    log "API credentials found" "SUCCESS"
    return 0
}

run_test_suite() {
    local suite=$1
    local filter=$2
    local options=""

    if [ "$VERBOSE" = true ]; then
        options="$options --verbose"
    fi

    if [ "$CI_MODE" = true ]; then
        options="$options --stop-on-failure"
    fi

    if [ "$COVERAGE" = true ]; then
        options="$options --coverage-html=${REPORT_DIR}/coverage/${suite}"
    fi

    log "Running test suite: $suite" "INFO"

    local test_path="tests/Feature/CalcomV2/${suite}.php"

    if [ ! -f "$test_path" ]; then
        log "Test suite not found: $test_path" "ERROR"
        return 1
    fi

    if [ -n "$filter" ]; then
        options="$options --filter=$filter"
    fi

    # Run the test
    if php vendor/bin/phpunit $test_path $options >> "$LOG_FILE" 2>&1; then
        log "✓ $suite passed" "SUCCESS"
        echo "{\"suite\":\"$suite\",\"status\":\"passed\",\"timestamp\":\"$(date -Iseconds)\"}" >> "$RESULTS_FILE"
        return 0
    else
        log "✗ $suite failed" "ERROR"
        echo "{\"suite\":\"$suite\",\"status\":\"failed\",\"timestamp\":\"$(date -Iseconds)\"}" >> "$RESULTS_FILE"

        if [ "$CI_MODE" = true ]; then
            exit 1
        fi
        return 1
    fi
}

run_parallel_tests() {
    log "Running tests in parallel..." "INFO"

    local pids=()
    local failed=0

    for suite in "${TEST_SUITES[@]}"; do
        run_test_suite "$suite" "" &
        pids+=($!)
    done

    # Wait for all background jobs
    for pid in "${pids[@]}"; do
        if ! wait $pid; then
            failed=$((failed + 1))
        fi
    done

    if [ $failed -eq 0 ]; then
        log "All parallel tests passed" "SUCCESS"
        return 0
    else
        log "$failed test suite(s) failed" "ERROR"
        return 1
    fi
}

run_sequential_tests() {
    local test_filter=""
    local failed=0
    local passed=0

    # Unit Tests
    if [ "$RUN_UNIT" = true ] || [ "$RUN_ALL" = true ]; then
        log "=== Running Unit Tests ===" "INFO"
        if run_test_suite "CalcomV2ClientTest" ""; then
            passed=$((passed + 1))
        else
            failed=$((failed + 1))
        fi
    fi

    # Integration Tests
    if [ "$RUN_INTEGRATION" = true ] || [ "$RUN_ALL" = true ]; then
        log "=== Running Integration Tests ===" "INFO"
        for suite in "CalcomV2IntegrationTest" "CalcomV2ExtendedIntegrationTest"; do
            if run_test_suite "$suite" ""; then
                passed=$((passed + 1))
            else
                failed=$((failed + 1))
            fi
        done
    fi

    # Synchronization Tests
    if [ "$RUN_SYNC" = true ] || [ "$RUN_ALL" = true ]; then
        log "=== Running Synchronization Tests ===" "INFO"
        if run_test_suite "CalcomV2SyncTest" ""; then
            passed=$((passed + 1))
        else
            failed=$((failed + 1))
        fi
    fi

    # Error Handling Tests
    if [ "$RUN_ERROR_HANDLING" = true ] || [ "$RUN_ALL" = true ]; then
        log "=== Running Error Handling Tests ===" "INFO"
        if run_test_suite "CalcomV2ErrorHandlingTest" ""; then
            passed=$((passed + 1))
        else
            failed=$((failed + 1))
        fi
    fi

    # Performance Tests
    if [ "$RUN_PERFORMANCE" = true ] || [ "$RUN_ALL" = true ]; then
        log "=== Running Performance Tests ===" "INFO"
        if run_test_suite "CalcomV2PerformanceTest" ""; then
            passed=$((passed + 1))
        else
            failed=$((failed + 1))
        fi
    fi

    # Live API Tests (only if credentials available)
    if [ "$RUN_LIVE" = true ]; then
        if check_api_credentials; then
            log "=== Running Live API Tests ===" "INFO"
            for suite in "${LIVE_TEST_SUITES[@]}"; do
                if run_test_suite "$suite" ""; then
                    passed=$((passed + 1))
                else
                    failed=$((failed + 1))
                fi
            done
        else
            log "Skipping live tests - API credentials not configured" "WARNING"
        fi
    fi

    log "Test Summary: $passed passed, $failed failed" "INFO"

    if [ $failed -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

generate_html_report() {
    log "Generating HTML report..." "INFO"

    cat > "$HTML_REPORT" << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cal.com V2 Integration Test Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        header {
            background: #2d3748;
            color: white;
            padding: 2rem;
            position: relative;
        }
        header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        header .subtitle {
            color: #a0aec0;
            font-size: 0.875rem;
        }
        header .badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .badge.success { background: #48bb78; }
        .badge.failure { background: #f56565; }
        .badge.warning { background: #ed8936; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 2rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .stat {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3748;
        }

        .results {
            padding: 2rem;
        }
        .test-suite {
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .suite-header {
            padding: 1rem 1.5rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .suite-header:hover {
            background: #edf2f7;
        }
        .suite-name {
            font-weight: 600;
            color: #2d3748;
        }
        .suite-status {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .status-passed { background: #c6f6d5; color: #22543d; }
        .status-failed { background: #fed7d7; color: #742a2a; }
        .status-skipped { background: #feebc8; color: #7c2d12; }

        .test-details {
            padding: 1.5rem;
            display: none;
        }
        .test-details.show {
            display: block;
        }
        .test-case {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f7fafc;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .test-case:last-child {
            border-bottom: none;
        }
        .test-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .test-icon.pass { background: #48bb78; color: white; }
        .test-icon.fail { background: #f56565; color: white; }
        .test-name {
            flex: 1;
            color: #4a5568;
        }
        .test-duration {
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .performance-charts {
            padding: 2rem;
            background: #f7fafc;
        }
        .chart {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .chart h3 {
            margin-bottom: 1rem;
            color: #2d3748;
        }

        footer {
            padding: 2rem;
            text-align: center;
            background: #2d3748;
            color: #a0aec0;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔬 Cal.com V2 Integration Test Report</h1>
            <div class="subtitle">Generated: <span id="timestamp"></span></div>
            <div class="badge" id="overall-status"></div>
        </header>

        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total Tests</div>
                <div class="stat-value" id="total-tests">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Passed</div>
                <div class="stat-value" id="passed-tests" style="color: #48bb78;">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Failed</div>
                <div class="stat-value" id="failed-tests" style="color: #f56565;">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Duration</div>
                <div class="stat-value" id="duration">0s</div>
            </div>
            <div class="stat">
                <div class="stat-label">Coverage</div>
                <div class="stat-value" id="coverage">0%</div>
            </div>
        </div>

        <div class="results" id="results">
            <!-- Test results will be inserted here -->
        </div>

        <div class="performance-charts" id="performance-charts">
            <div class="chart">
                <h3>📊 Performance Metrics</h3>
                <canvas id="performance-chart"></canvas>
            </div>
        </div>

        <footer>
            <p>Cal.com V2 Integration Testing Suite | Ensuring "extrem sauber" middleware functionality</p>
            <p>Generated with ❤️ for robust Cal.com integration</p>
        </footer>
    </div>

    <script>
        // Parse test results from JSON
        fetch('results_${TIMESTAMP}.json')
            .then(response => response.text())
            .then(text => {
                const results = text.split('\n')
                    .filter(line => line.trim())
                    .map(line => JSON.parse(line));

                updateReport(results);
            })
            .catch(err => console.error('Failed to load results:', err));

        function updateReport(results) {
            // Update timestamp
            document.getElementById('timestamp').textContent = new Date().toLocaleString();

            // Calculate stats
            const total = results.length;
            const passed = results.filter(r => r.status === 'passed').length;
            const failed = results.filter(r => r.status === 'failed').length;

            document.getElementById('total-tests').textContent = total;
            document.getElementById('passed-tests').textContent = passed;
            document.getElementById('failed-tests').textContent = failed;

            // Update overall status
            const statusBadge = document.getElementById('overall-status');
            if (failed === 0) {
                statusBadge.textContent = 'ALL PASSED';
                statusBadge.className = 'badge success';
            } else {
                statusBadge.textContent = failed + ' FAILED';
                statusBadge.className = 'badge failure';
            }

            // Generate results HTML
            const resultsContainer = document.getElementById('results');
            results.forEach(result => {
                const suiteDiv = createTestSuite(result);
                resultsContainer.appendChild(suiteDiv);
            });
        }

        function createTestSuite(result) {
            const div = document.createElement('div');
            div.className = 'test-suite';

            const header = document.createElement('div');
            header.className = 'suite-header';
            header.onclick = () => toggleDetails(div);

            const name = document.createElement('div');
            name.className = 'suite-name';
            name.textContent = result.suite;

            const status = document.createElement('div');
            status.className = 'suite-status status-' + result.status;
            status.textContent = result.status.toUpperCase();

            header.appendChild(name);
            header.appendChild(status);

            const details = document.createElement('div');
            details.className = 'test-details';

            div.appendChild(header);
            div.appendChild(details);

            return div;
        }

        function toggleDetails(suite) {
            const details = suite.querySelector('.test-details');
            details.classList.toggle('show');
        }
    </script>
</body>
</html>
EOF

    log "HTML report generated: $HTML_REPORT" "SUCCESS"

    # Try to open in browser if available
    if command -v xdg-open &> /dev/null; then
        xdg-open "$HTML_REPORT"
    elif command -v open &> /dev/null; then
        open "$HTML_REPORT"
    fi
}

cleanup() {
    log "Cleaning up test artifacts..." "INFO"

    # Clear test database
    php artisan migrate:fresh --env=testing --force > /dev/null 2>&1

    # Clear caches
    php artisan cache:clear 2>/dev/null || true

    log "Cleanup complete" "SUCCESS"
}

#==============================================================================
# Main Script
#==============================================================================

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --all)
            RUN_ALL=true
            shift
            ;;
        --unit)
            RUN_UNIT=true
            shift
            ;;
        --integration)
            RUN_INTEGRATION=true
            shift
            ;;
        --live)
            RUN_LIVE=true
            shift
            ;;
        --performance)
            RUN_PERFORMANCE=true
            shift
            ;;
        --error-handling)
            RUN_ERROR_HANDLING=true
            shift
            ;;
        --sync)
            RUN_SYNC=true
            shift
            ;;
        --report)
            GENERATE_REPORT=true
            shift
            ;;
        --ci)
            CI_MODE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --parallel)
            PARALLEL=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Default to running all tests if no specific suite selected
if [ "$RUN_UNIT" = false ] && [ "$RUN_INTEGRATION" = false ] && \
   [ "$RUN_LIVE" = false ] && [ "$RUN_PERFORMANCE" = false ] && \
   [ "$RUN_ERROR_HANDLING" = false ] && [ "$RUN_SYNC" = false ] && \
   [ "$RUN_ALL" = false ]; then
    RUN_ALL=true
fi

# Main execution
main() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════╗"
    echo "║           Cal.com V2 Integration Test Runner                      ║"
    echo "║                                                                    ║"
    echo "║  Ensuring the middleware works 'extrem sauber' with Cal.com       ║"
    echo "╚════════════════════════════════════════════════════════════════════╝"
    echo ""

    START_TIME=$(date +%s)

    # Setup
    setup_environment

    # Run tests
    if [ "$PARALLEL" = true ] && [ "$RUN_ALL" = true ]; then
        run_parallel_tests
        TEST_RESULT=$?
    else
        run_sequential_tests
        TEST_RESULT=$?
    fi

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))

    log "Total execution time: ${DURATION} seconds" "INFO"

    # Generate report if requested
    if [ "$GENERATE_REPORT" = true ]; then
        generate_html_report
    fi

    # Cleanup in non-CI mode
    if [ "$CI_MODE" = false ]; then
        cleanup
    fi

    # Summary
    echo ""
    echo "════════════════════════════════════════════════════════════════════"
    if [ $TEST_RESULT -eq 0 ]; then
        echo -e "${GREEN}✓ All tests passed successfully!${NC}"
        echo "The Cal.com integration is working 'extrem sauber' ✨"
    else
        echo -e "${RED}✗ Some tests failed${NC}"
        echo "Please review the log file: $LOG_FILE"
    fi
    echo "════════════════════════════════════════════════════════════════════"
    echo ""

    exit $TEST_RESULT
}

# Run main function
main