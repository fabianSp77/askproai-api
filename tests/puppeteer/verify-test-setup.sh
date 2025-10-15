#!/bin/bash
#
# Verify E2E Test Setup
# Checks all prerequisites before running the test
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}======================================"
echo "E2E Test Setup Verification"
echo -e "======================================${NC}\n"

ERRORS=0
WARNINGS=0

# Check 1: Node.js
echo -n "→ Checking Node.js... "
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo -e "${GREEN}✅ Found: $NODE_VERSION${NC}"
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 2: NPM
echo -n "→ Checking NPM... "
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo -e "${GREEN}✅ Found: v$NPM_VERSION${NC}"
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 3: Puppeteer
echo -n "→ Checking Puppeteer... "
if [ -d "node_modules/puppeteer" ]; then
    PUPPETEER_VERSION=$(node -p "require('./node_modules/puppeteer/package.json').version")
    echo -e "${GREEN}✅ Found: v$PUPPETEER_VERSION${NC}"
else
    echo -e "${RED}❌ Not installed${NC}"
    echo -e "   ${YELLOW}Run: npm install puppeteer${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 4: Test files
echo -n "→ Checking test script... "
if [ -f "tests/puppeteer/crm-customer-history-e2e.cjs" ]; then
    echo -e "${GREEN}✅ Found${NC}"
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 5: Runner script
echo -n "→ Checking runner script... "
if [ -f "tests/run-customer-history-test.sh" ]; then
    if [ -x "tests/run-customer-history-test.sh" ]; then
        echo -e "${GREEN}✅ Found and executable${NC}"
    else
        echo -e "${YELLOW}⚠️  Found but not executable${NC}"
        echo -e "   ${YELLOW}Run: chmod +x tests/run-customer-history-test.sh${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 6: Screenshots directory
echo -n "→ Checking screenshots directory... "
if [ -d "screenshots" ]; then
    echo -e "${GREEN}✅ Exists${NC}"
else
    echo -e "${YELLOW}⚠️  Not found (will be created)${NC}"
    mkdir -p screenshots
    echo -e "   ${GREEN}✅ Created${NC}"
fi

# Check 7: Environment variables
echo -e "\n${BLUE}Environment Configuration:${NC}"

echo -n "→ APP_URL: "
if [ -n "$APP_URL" ]; then
    echo -e "${GREEN}✅ Set: $APP_URL${NC}"
else
    echo -e "${YELLOW}⚠️  Not set (will use default: https://api.askproai.de)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

echo -n "→ ADMIN_EMAIL: "
if [ -n "$ADMIN_EMAIL" ]; then
    echo -e "${GREEN}✅ Set: $ADMIN_EMAIL${NC}"
else
    echo -e "${YELLOW}⚠️  Not set (will use default: fabian@askproai.de)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

echo -n "→ ADMIN_PASSWORD: "
if [ -n "$ADMIN_PASSWORD" ]; then
    echo -e "${GREEN}✅ Set: [HIDDEN]${NC}"
else
    echo -e "${RED}❌ Not set${NC}"
    echo -e "   ${YELLOW}Set with: export ADMIN_PASSWORD=your_password${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 8: Test data in database
echo -e "\n${BLUE}Test Data Verification:${NC}"

echo -n "→ Customer #461 (Hansi Hinterseer)... "
CUSTOMER_CHECK=$(php artisan tinker --execute="echo json_encode(\App\Models\Customer::find(461, ['id', 'name']) ?? null);" 2>/dev/null || echo "null")
if [ "$CUSTOMER_CHECK" != "null" ] && [ "$CUSTOMER_CHECK" != "" ]; then
    echo -e "${GREEN}✅ Exists${NC}"
    echo "   Data: $CUSTOMER_CHECK"
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

echo -n "→ Appointments #672, #673... "
APPOINTMENTS_CHECK=$(php artisan tinker --execute="echo \App\Models\Appointment::whereIn('id', [672, 673])->count();" 2>/dev/null || echo "0")
if [ "$APPOINTMENTS_CHECK" == "2" ]; then
    echo -e "${GREEN}✅ Both found${NC}"
elif [ "$APPOINTMENTS_CHECK" == "1" ]; then
    echo -e "${YELLOW}⚠️  Only 1 found${NC}"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "${RED}❌ Not found${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check 9: Application accessibility
echo -e "\n${BLUE}Application Accessibility:${NC}"

APP_URL=${APP_URL:-https://api.askproai.de}

echo -n "→ Admin login page... "
LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/admin/login" 2>/dev/null || echo "000")
if [ "$LOGIN_STATUS" == "200" ]; then
    echo -e "${GREEN}✅ Accessible (HTTP $LOGIN_STATUS)${NC}"
elif [ "$LOGIN_STATUS" == "302" ]; then
    echo -e "${GREEN}✅ Accessible (HTTP $LOGIN_STATUS - redirected)${NC}"
else
    echo -e "${RED}❌ Not accessible (HTTP $LOGIN_STATUS)${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Summary
echo -e "\n${BLUE}======================================"
echo "Verification Summary"
echo -e "======================================${NC}"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed!${NC}"
    echo -e "${GREEN}You can now run the test:${NC}"
    echo -e "   ./tests/run-customer-history-test.sh"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  $WARNINGS warning(s) found${NC}"
    echo -e "${YELLOW}Test may work but check warnings above${NC}"
    exit 0
else
    echo -e "${RED}❌ $ERRORS error(s) and $WARNINGS warning(s) found${NC}"
    echo -e "${RED}Please fix errors before running test${NC}"
    exit 1
fi
