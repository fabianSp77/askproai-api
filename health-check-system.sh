#!/bin/bash

echo "=== AskProAI System Health Check ==="
echo "Date: $(date)"
echo ""

# Check for multiple JS fix files
echo "1. Checking for fix scripts..."
FIX_COUNT=$(find public/js -name "*fix*.js" -o -name "*debug*.js" -o -name "*workaround*.js" 2>/dev/null | wc -l)
if [ $FIX_COUNT -gt 1 ]; then
    echo "⚠️  WARNING: Found $FIX_COUNT fix scripts (should be max 1)"
    find public/js -name "*fix*.js" -o -name "*debug*.js" -o -name "*workaround*.js" 2>/dev/null
else
    echo "✅ Fix scripts OK ($FIX_COUNT found)"
fi
echo ""

# Check for service workers
echo "2. Checking for service workers..."
SW_COUNT=$(find public -name "*service-worker*.js" -o -name "*sw.js" 2>/dev/null | grep -v ".disabled" | wc -l)
if [ $SW_COUNT -gt 0 ]; then
    echo "⚠️  WARNING: Found $SW_COUNT active service worker files"
    find public -name "*service-worker*.js" -o -name "*sw.js" 2>/dev/null | grep -v ".disabled"
else
    echo "✅ No active service workers"
fi
echo ""

# Check Laravel log for errors
echo "3. Checking Laravel log for recent errors..."
ERROR_COUNT=$(tail -1000 storage/logs/laravel.log 2>/dev/null | grep -c "ERROR\|Exception" || echo "0")
if [ $ERROR_COUNT -gt 10 ]; then
    echo "⚠️  WARNING: Found $ERROR_COUNT errors in recent logs"
else
    echo "✅ Error count acceptable ($ERROR_COUNT)"
fi
echo ""

# Check for duplicate auth logs
echo "4. Checking for duplicate auth logs..."
DUPLICATE_COUNT=$(tail -100 storage/logs/laravel.log 2>/dev/null | grep "AUTH EVENT" | wc -l || echo "0")
if [ $DUPLICATE_COUNT -gt 50 ]; then
    echo "⚠️  WARNING: Many auth events logged ($DUPLICATE_COUNT in last 100 lines)"
else
    echo "✅ Auth logging OK"
fi
echo ""

# Check session configuration
echo "5. Checking session configuration..."
SECURE_COOKIE=$(grep "SESSION_SECURE_COOKIE" .env | grep -c "true" || echo "0")
if [ $SECURE_COOKIE -eq 1 ]; then
    echo "✅ Session secure cookie enabled"
else
    echo "⚠️  WARNING: SESSION_SECURE_COOKIE not set to true"
fi
echo ""

# Check for test files in public
echo "6. Checking for test files in public..."
TEST_COUNT=$(find public -name "*test*.php" -o -name "*debug*.php" -o -name "*fix*.php" 2>/dev/null | wc -l)
if [ $TEST_COUNT -gt 5 ]; then
    echo "⚠️  WARNING: Found $TEST_COUNT test/debug PHP files in public"
    echo "   Consider removing old test files"
else
    echo "✅ Test file count acceptable ($TEST_COUNT)"
fi
echo ""

echo "=== Health Check Complete ==="