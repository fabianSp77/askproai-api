#!/bin/bash

echo "========================================="
echo "INTEGRATION 6 & SERVICES FIX VERIFICATION"
echo "========================================="
echo "Time: $(date)"
echo

# Test endpoints
ENDPOINTS=(
    "admin/integrations/6"
    "admin/services"
    "admin/services/30"
    "admin/appointments"
)

# Function to test endpoint
test_endpoint() {
    local endpoint=$1
    local url="https://api.askproai.de/$endpoint"
    echo -n "Testing $endpoint: "

    # Get HTTP status
    status=$(curl -s -o /dev/null -w "%{http_code}" "$url")

    # Check status
    if [ "$status" == "302" ]; then
        echo "✓ 302 Redirect (Expected for unauthenticated)"
    elif [ "$status" == "200" ]; then
        echo "✓ 200 OK (Authenticated user access)"
    elif [ "$status" == "500" ]; then
        echo "✗ 500 ERROR - STILL BROKEN!"
        # Get last error from log
        echo "  Last error:"
        tail -n 20 /var/www/api-gateway/storage/logs/laravel.log | grep -A 2 "ERROR\|Exception" | tail -3
    else
        echo "? Status: $status"
    fi
}

# Test all endpoints
echo "ENDPOINT TESTS:"
echo "---------------"
for endpoint in "${ENDPOINTS[@]}"; do
    test_endpoint "$endpoint"
done

echo
echo "DATABASE CHECK:"
echo "---------------"
# Check if appointments table has correct columns
mysql -u root -p'tL34!kLm#2)K' askpro -e "SHOW COLUMNS FROM appointments LIKE '%start%'" 2>/dev/null | grep -E "start_time|starts_at"

echo
echo "CACHE STATUS:"
echo "-------------"
# Check cache status
if [ -d "/var/www/api-gateway/storage/framework/cache/data" ]; then
    echo "Cache files: $(find /var/www/api-gateway/storage/framework/cache/data -type f | wc -l)"
fi

if [ -d "/var/www/api-gateway/storage/framework/sessions" ]; then
    echo "Session files: $(find /var/www/api-gateway/storage/framework/sessions -type f | wc -l)"
fi

if [ -d "/var/www/api-gateway/storage/framework/views" ]; then
    echo "View cache files: $(find /var/www/api-gateway/storage/framework/views -type f | wc -l)"
fi

echo
echo "RECENT ERRORS:"
echo "--------------"
# Check for recent start_time errors
recent_errors=$(tail -n 100 /var/www/api-gateway/storage/logs/laravel.log | grep -c "start_time")
if [ "$recent_errors" -gt 0 ]; then
    echo "⚠ Found $recent_errors recent 'start_time' errors"
    echo "Last occurrence:"
    tail -n 100 /var/www/api-gateway/storage/logs/laravel.log | grep "start_time" | tail -1
else
    echo "✓ No recent 'start_time' errors"
fi

echo
echo "FILAMENT RESOURCES CHECK:"
echo "-------------------------"
# Check for start_time in Filament resources
if grep -r "start_time" /var/www/api-gateway/app/Filament/Resources/ 2>/dev/null; then
    echo "⚠ Found 'start_time' references in Filament resources"
else
    echo "✓ No 'start_time' references in Filament resources"
fi

echo
echo "========================================="
echo "VERIFICATION COMPLETE"
echo "========================================="
