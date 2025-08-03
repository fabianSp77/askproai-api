#!/bin/bash

# Emergency Fix Test Script for Issue #476
# Tests all critical UI functionality

echo "🧪 Testing Emergency Fixes for Issue #476"
echo "========================================"
echo "Date: $(date)"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results
PASSED=0
FAILED=0

# Function to test endpoint
test_endpoint() {
    local name=$1
    local url=$2
    local expected=$3
    
    status=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    
    if [ "$status" = "$expected" ]; then
        echo -e "${GREEN}✅ $name: HTTP $status${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ $name: HTTP $status (expected $expected)${NC}"
        ((FAILED++))
    fi
}

# Function to check CSS files
check_css() {
    local name=$1
    local file=$2
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✅ $name exists${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ $name missing${NC}"
        ((FAILED++))
    fi
}

# Function to check for patterns in files
check_pattern() {
    local name=$1
    local file=$2
    local pattern=$3
    
    if grep -q "$pattern" "$file" 2>/dev/null; then
        echo -e "${GREEN}✅ $name found${NC}"
        ((PASSED++))
    else
        echo -e "${RED}❌ $name not found${NC}"
        ((FAILED++))
    fi
}

echo "1. Testing Admin Panel Endpoints"
echo "--------------------------------"
test_endpoint "Login Page" "https://api.askproai.de/admin/login" "200"
test_endpoint "Admin Dashboard" "https://api.askproai.de/admin" "302"
test_endpoint "API Health" "https://api.askproai.de/api/health" "200"

echo ""
echo "2. Checking Emergency CSS Fix"
echo "-----------------------------"
check_css "Emergency CSS" "resources/css/filament/admin/emergency-fix-476.css"
check_pattern "Emergency CSS Import" "resources/css/filament/admin/theme.css" "emergency-fix-476.css"

echo ""
echo "3. Checking JavaScript Fix"
echo "--------------------------"
check_css "Emergency JS" "public/js/emergency-framework-fix.js"
check_pattern "JS Include in Base" "resources/views/vendor/filament-panels/components/layout/base.blade.php" "emergency-framework-fix.js"

echo ""
echo "4. Checking CallResource Templates"
echo "----------------------------------"
templates=(
    "resources/views/filament/infolists/call-header-modern-v2-mobile.blade.php"
    "resources/views/filament/modals/share-call.blade.php"
    "resources/views/filament/components/audio-player-enterprise-improved.blade.php"
    "resources/views/filament/infolists/toggleable-transcript.blade.php"
    "resources/views/filament/infolists/toggleable-summary.blade.php"
    "resources/views/filament/infolists/ml-features-list.blade.php"
)

for template in "${templates[@]}"; do
    if [ -f "$template" ]; then
        echo -e "${GREEN}✅ $(basename $template) exists${NC}"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠️  $(basename $template) missing (may not be critical)${NC}"
    fi
done

echo ""
echo "5. Widget Polling Check"
echo "----------------------"
aggressive_widgets=$(grep -r "pollingInterval = '[0-9]s'" app/Filament/Admin/Widgets/ 2>/dev/null | wc -l)
if [ "$aggressive_widgets" -eq 0 ]; then
    echo -e "${GREEN}✅ No aggressive polling widgets found${NC}"
    ((PASSED++))
else
    echo -e "${RED}❌ Found $aggressive_widgets widgets with <10s polling${NC}"
    ((FAILED++))
fi

echo ""
echo "6. CSS Fix File Count"
echo "--------------------"
fix_files=$(find resources/css/filament/admin -name "*fix*.css" -o -name "*issue*.css" | wc -l)
echo -e "${YELLOW}⚠️  Total CSS fix files: $fix_files (target: <5)${NC}"

echo ""
echo "7. Build Status Check"
echo "--------------------"
if [ -f "public/build/manifest.json" ]; then
    echo -e "${GREEN}✅ Build manifest exists${NC}"
    ((PASSED++))
else
    echo -e "${RED}❌ Build manifest missing - run 'npm run build'${NC}"
    ((FAILED++))
fi

echo ""
echo "========================================"
echo "TEST SUMMARY"
echo "========================================"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED - Ready for production${NC}"
    exit 0
else
    echo -e "${RED}❌ TESTS FAILED - Fix issues before deploying${NC}"
    exit 1
fi