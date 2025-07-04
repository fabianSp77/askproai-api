#!/bin/bash

# AskProAI Preflight Check Script
# ================================

echo "🚀 AskProAI Production Readiness Check"
echo "====================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. System Check
echo "1️⃣ Running System Checks..."
php artisan askproai:preflight --quick --json > /tmp/preflight_result.json
RESULT=$?

if [ $RESULT -eq 0 ]; then
    echo -e "${GREEN}✅ System checks passed${NC}"
else
    echo -e "${RED}❌ System checks failed${NC}"
fi

# 2. Parse results
ERRORS=$(jq -r '.summary.errors' /tmp/preflight_result.json)
WARNINGS=$(jq -r '.summary.warnings' /tmp/preflight_result.json)

echo ""
echo "📊 Results Summary:"
echo "  Errors: $ERRORS"
echo "  Warnings: $WARNINGS"

# 3. Company-specific check
echo ""
echo "2️⃣ Checking specific company..."
read -p "Enter Company ID (or press Enter to skip): " COMPANY_ID

if [ ! -z "$COMPANY_ID" ]; then
    php artisan askproai:preflight --company=$COMPANY_ID
fi

# 4. Fix attempt
echo ""
read -p "Attempt to auto-fix issues? (y/n): " FIX

if [ "$FIX" = "y" ]; then
    echo "🔧 Running auto-fix..."
    php artisan askproai:preflight --fix
fi

# 5. Final recommendation
echo ""
echo "📋 Final Recommendation:"
if [ $ERRORS -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}✅ System is READY for production!${NC}"
    else
        echo -e "${YELLOW}⚠️  System is CONDITIONALLY ready. Review warnings.${NC}"
    fi
else
    echo -e "${RED}❌ System is NOT ready. Fix all errors first!${NC}"
fi

echo ""
echo "For detailed results, check: /tmp/preflight_result.json"