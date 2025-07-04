#!/bin/bash

# SQL Injection Security Check Script
# Run this regularly to check for potential SQL injection vulnerabilities

echo "SQL Injection Security Check"
echo "============================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL=0
ISSUES=0

echo "Checking for potential SQL injection patterns..."
echo ""

# Check for DB::raw with variables
echo "1. Checking DB::raw with variables..."
if grep -r "DB::raw.*\\\$" app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${RED}❌ Found DB::raw with variables${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No DB::raw with variables found${NC}"
fi
((TOTAL++))
echo ""

# Check for whereRaw without parameters
echo "2. Checking whereRaw without parameter binding..."
if grep -r "whereRaw([^,)]*\\\$[^,)]*)" app/ --include="*.php" -E | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${RED}❌ Found whereRaw without proper parameter binding${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ All whereRaw calls use parameter binding${NC}"
fi
((TOTAL++))
echo ""

# Check for string concatenation in DB::select
echo "3. Checking DB::select with string concatenation..."
if grep -r "DB::select.*\." app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${YELLOW}⚠️  Found DB::select with concatenation (review needed)${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No DB::select with concatenation found${NC}"
fi
((TOTAL++))
echo ""

# Check for DB::statement with variables
echo "4. Checking DB::statement with variables..."
if grep -r "DB::statement.*\\\$" app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${RED}❌ Found DB::statement with variables${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No DB::statement with variables found${NC}"
fi
((TOTAL++))
echo ""

# Check for DB::unprepared
echo "5. Checking for DB::unprepared usage..."
if grep -r "DB::unprepared" app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${RED}❌ Found DB::unprepared usage (very dangerous!)${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No DB::unprepared usage found${NC}"
fi
((TOTAL++))
echo ""

# Check for orderByRaw with variables
echo "6. Checking orderByRaw with variables..."
if grep -r "orderByRaw.*\\\$" app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${YELLOW}⚠️  Found orderByRaw with variables (review needed)${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No orderByRaw with variables found${NC}"
fi
((TOTAL++))
echo ""

# Check for havingRaw with variables
echo "7. Checking havingRaw with variables..."
if grep -r "havingRaw.*\\\$" app/ --include="*.php" | grep -v "// @security-reviewed" | grep -v "\.backup\." ; then
    echo -e "${YELLOW}⚠️  Found havingRaw with variables (review needed)${NC}"
    ((ISSUES++))
else
    echo -e "${GREEN}✅ No havingRaw with variables found${NC}"
fi
((TOTAL++))
echo ""

# Summary
echo "============================"
echo "Summary:"
echo "Total checks: $TOTAL"
if [ $ISSUES -eq 0 ]; then
    echo -e "${GREEN}✅ No SQL injection vulnerabilities found!${NC}"
    exit 0
else
    echo -e "${RED}❌ Found $ISSUES potential issues${NC}"
    echo ""
    echo "Recommendations:"
    echo "1. Review each finding carefully"
    echo "2. Use parameter binding: ->whereRaw('column = ?', [\$value])"
    echo "3. Add // @security-reviewed comment after manual review"
    echo "4. Run: php fix-sql-injection-vulnerabilities.php"
    exit 1
fi