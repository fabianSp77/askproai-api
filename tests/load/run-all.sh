#!/bin/bash
#
# AskPro AI Gateway - Load Test Runner
#
# Usage:
#   ./run-all.sh              # Run all tests sequentially
#   ./run-all.sh baseline     # Run only baseline
#   ./run-all.sh peak         # Run only peak load
#
# Environment variables:
#   K6_BASE_URL       - API base URL (default: http://localhost)
#   K6_API_KEY        - API authentication key
#   K6_MOCK_MODE      - Set to 'true' for stress tests without Cal.com
#   K6_TEST_COMPANY_ID - Company ID for testing
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RESULTS_DIR="${SCRIPT_DIR}/results"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create results directory
mkdir -p "${RESULTS_DIR}"

# Default configuration
export K6_BASE_URL="${K6_BASE_URL:-http://localhost}"
export K6_TEST_COMPANY_ID="${K6_TEST_COMPANY_ID:-1}"

echo -e "${GREEN}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║           AskPro AI Gateway - Load Testing                    ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"
echo ""
echo "Configuration:"
echo "  Base URL:    ${K6_BASE_URL}"
echo "  Company ID:  ${K6_TEST_COMPANY_ID}"
echo "  Results:     ${RESULTS_DIR}"
echo ""

# Check k6 installation
if ! command -v k6 &> /dev/null; then
    echo -e "${RED}Error: k6 is not installed${NC}"
    echo ""
    echo "Install k6:"
    echo "  Ubuntu: sudo apt install k6"
    echo "  Mac:    brew install k6"
    echo "  Or:     https://k6.io/docs/get-started/installation/"
    exit 1
fi

run_test() {
    local test_name=$1
    local test_file=$2
    local timestamp=$(date +%Y%m%d_%H%M%S)

    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}Running: ${test_name}${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    k6 run \
        --out json="${RESULTS_DIR}/${test_name}_${timestamp}.json" \
        "${test_file}"

    echo ""
    echo -e "${GREEN}✓ ${test_name} completed${NC}"
    echo ""
}

# Parse arguments
TEST_TO_RUN="${1:-all}"

case $TEST_TO_RUN in
    baseline)
        run_test "baseline" "${SCRIPT_DIR}/scenarios/baseline.js"
        ;;
    normal|normal-load)
        run_test "normal-load" "${SCRIPT_DIR}/scenarios/normal-load.js"
        ;;
    peak|peak-load)
        run_test "peak-load" "${SCRIPT_DIR}/scenarios/peak-load.js"
        ;;
    stress|stress-test)
        echo -e "${RED}⚠️  WARNING: Stress test will push system beyond capacity!${NC}"
        echo ""
        read -p "Continue? (y/N) " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            export K6_MOCK_MODE="${K6_MOCK_MODE:-false}"
            run_test "stress-test" "${SCRIPT_DIR}/scenarios/stress-test.js"
        else
            echo "Stress test cancelled."
        fi
        ;;
    all)
        echo "Running all tests in sequence..."
        echo ""

        run_test "baseline" "${SCRIPT_DIR}/scenarios/baseline.js"
        sleep 30  # Cool down between tests

        run_test "normal-load" "${SCRIPT_DIR}/scenarios/normal-load.js"
        sleep 60  # Longer cool down before peak

        run_test "peak-load" "${SCRIPT_DIR}/scenarios/peak-load.js"

        echo -e "${GREEN}"
        echo "╔═══════════════════════════════════════════════════════════════╗"
        echo "║           All tests completed!                                ║"
        echo "╚═══════════════════════════════════════════════════════════════╝"
        echo -e "${NC}"
        echo ""
        echo "Results saved to: ${RESULTS_DIR}"
        echo ""
        echo "Note: Stress test not included in 'all'. Run separately with:"
        echo "  ./run-all.sh stress"
        ;;
    *)
        echo "Usage: $0 [baseline|normal|peak|stress|all]"
        exit 1
        ;;
esac
