#!/bin/bash
#
# Run CRM Customer History E2E Test
# Tests appointment history display in Filament Admin panel
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================"
echo "CRM Customer History E2E Test Runner"
echo -e "======================================${NC}\n"

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SCREENSHOTS_DIR="$PROJECT_ROOT/screenshots"

# Ensure screenshots directory exists
mkdir -p "$SCREENSHOTS_DIR"

# Check if Node.js is available
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Error: Node.js is not installed${NC}"
    exit 1
fi

# Check if Puppeteer is installed
if [ ! -d "$PROJECT_ROOT/node_modules/puppeteer" ]; then
    echo -e "${YELLOW}⚠️  Puppeteer not found in node_modules${NC}"
    echo -e "${BLUE}→ Installing Puppeteer...${NC}"
    cd "$PROJECT_ROOT"
    npm install puppeteer
fi

# Load environment variables if .env exists
if [ -f "$PROJECT_ROOT/.env" ]; then
    echo -e "${BLUE}→ Loading environment from .env${NC}"
    export $(grep -v '^#' "$PROJECT_ROOT/.env" | xargs)
fi

# Check for admin password
if [ -z "$ADMIN_PASSWORD" ]; then
    echo -e "${YELLOW}⚠️  ADMIN_PASSWORD not set in environment${NC}"
    echo -e "${YELLOW}   Test may require manual password entry${NC}"
    echo -e "${YELLOW}   Set it with: export ADMIN_PASSWORD=your_password${NC}\n"

    # Prompt for password if interactive
    if [ -t 0 ]; then
        read -s -p "Enter admin password (or press Enter to skip): " ADMIN_PASSWORD
        echo
        export ADMIN_PASSWORD
    fi
fi

# Parse command line arguments
HEADLESS=true
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --no-headless)
            HEADLESS=false
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --no-headless    Run browser in visible mode"
            echo "  --verbose        Show detailed output"
            echo "  --help           Show this help message"
            echo ""
            echo "Environment Variables:"
            echo "  APP_URL          Application URL (default: https://api.askproai.de)"
            echo "  ADMIN_EMAIL      Admin email (default: fabian@askproai.de)"
            echo "  ADMIN_PASSWORD   Admin password (required)"
            echo ""
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Export test configuration
export HEADLESS=$HEADLESS
export APP_URL=${APP_URL:-https://api.askproai.de}
export ADMIN_EMAIL=${ADMIN_EMAIL:-fabian@askproai.de}

# Print configuration
echo -e "${BLUE}Test Configuration:${NC}"
echo "  APP_URL:      $APP_URL"
echo "  ADMIN_EMAIL:  $ADMIN_EMAIL"
echo "  HEADLESS:     $HEADLESS"
echo "  SCREENSHOTS:  $SCREENSHOTS_DIR"
echo ""

# Run the test
echo -e "${BLUE}→ Running E2E test...${NC}\n"

cd "$PROJECT_ROOT"

if [ "$VERBOSE" = true ]; then
    node tests/puppeteer/crm-customer-history-e2e.cjs
else
    node tests/puppeteer/crm-customer-history-e2e.cjs 2>&1
fi

TEST_EXIT_CODE=$?

# Check results
echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"

    # Show screenshots
    if [ -d "$SCREENSHOTS_DIR" ]; then
        echo -e "\n${BLUE}Screenshots saved:${NC}"
        ls -lh "$SCREENSHOTS_DIR"/*.png 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'
    fi

    exit 0
else
    echo -e "${RED}❌ Tests failed with exit code $TEST_EXIT_CODE${NC}"

    # Show error screenshots
    if [ -d "$SCREENSHOTS_DIR" ]; then
        ERROR_SCREENSHOTS=$(ls -1 "$SCREENSHOTS_DIR"/error-*.png 2>/dev/null)
        if [ -n "$ERROR_SCREENSHOTS" ]; then
            echo -e "\n${RED}Error screenshots:${NC}"
            echo "$ERROR_SCREENSHOTS" | while read screenshot; do
                echo "  $screenshot"
            done
        fi
    fi

    exit $TEST_EXIT_CODE
fi
