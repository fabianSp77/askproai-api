#!/bin/bash
#
# Activate Best Practices for AskProAI
# Run this after pulling the latest changes
#

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Activating AskProAI Best Practices...${NC}"
echo ""

# Check if we're in the project root
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Please run this script from the project root!${NC}"
    exit 1
fi

# Step 1: Install/Update Dependencies
echo -e "${BLUE}1. Installing dependencies...${NC}"
composer install --no-interaction
if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to install composer dependencies${NC}"
    exit 1
fi

# Step 2: Run Migrations
echo -e "${BLUE}2. Running migrations...${NC}"
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}Warning: Migration failed (may already be up to date)${NC}"
fi

# Step 3: Setup Git Hooks
echo -e "${BLUE}3. Setting up Git hooks...${NC}"
if [ -f "./scripts/setup-git-hooks.sh" ]; then
    ./scripts/setup-git-hooks.sh
else
    echo -e "${YELLOW}Git hooks setup script not found${NC}"
fi

# Step 4: Clear Caches
echo -e "${BLUE}4. Clearing caches...${NC}"
php artisan optimize:clear

# Step 5: Run Initial Quality Check
echo -e "${BLUE}5. Running initial quality check...${NC}"

# Pint
echo -n "  • Code formatting... "
if ./vendor/bin/pint --test > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠️  (run 'composer pint' to fix)${NC}"
fi

# PHPStan
echo -n "  • Static analysis... "
if ./vendor/bin/phpstan analyse --no-progress > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠️  (run 'composer stan' to check)${NC}"
fi

# Documentation
echo -n "  • Documentation health... "
if php artisan docs:check-updates --json > /tmp/doc-health.json 2>&1; then
    HEALTH=$(cat /tmp/doc-health.json | grep -o '"health_score":[0-9]*' | cut -d: -f2)
    if [ "$HEALTH" -ge 80 ]; then
        echo -e "${GREEN}✓ ($HEALTH%)${NC}"
    else
        echo -e "${YELLOW}⚠️  ($HEALTH%)${NC}"
    fi
    rm -f /tmp/doc-health.json
else
    echo -e "${YELLOW}⚠️  (check manually)${NC}"
fi

# Step 6: Display Available Commands
echo ""
echo -e "${BLUE}6. New commands available:${NC}"
echo "  • composer quality       - Run all quality checks"
echo "  • composer pint          - Format code"
echo "  • composer stan          - Static analysis"
echo "  • composer test          - Run tests"
echo "  • composer docs:check    - Check documentation"
echo "  • composer impact        - Analyze git changes"
echo ""
echo "  • php artisan mcp:discover <task>     - Find best MCP server"
echo "  • php artisan analyze:impact          - Analyze changes"
echo "  • php artisan docs:check-updates      - Check documentation"
echo ""

# Step 7: Quick MCP Test
echo -e "${BLUE}7. Testing MCP Discovery...${NC}"
php artisan mcp:discover "test connection" --json > /tmp/mcp-test.json 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ MCP Discovery is working${NC}"
else
    echo -e "${RED}✗ MCP Discovery failed${NC}"
fi
rm -f /tmp/mcp-test.json

# Summary
echo ""
echo -e "${GREEN}✅ Best Practices Activated!${NC}"
echo ""
echo -e "${BLUE}What's new:${NC}"
echo "  🤖 Automatic MCP server discovery"
echo "  📊 Complete data flow tracking"
echo "  🔍 Impact analysis before deployment"
echo "  🎨 Automatic code formatting"
echo "  🧪 Comprehensive testing"
echo "  📚 Self-updating documentation"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Review BEST_PRACTICES_IMPLEMENTATION.md"
echo "  2. Try: php artisan mcp:discover 'your task'"
echo "  3. Commit with conventional format: feat|fix|docs: message"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"