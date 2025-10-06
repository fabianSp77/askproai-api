#!/bin/bash

# Comprehensive Filament Admin Routes Test
echo "========================================="
echo "Testing All Filament Admin Routes"
echo "========================================="
echo ""

# Array of all admin routes
declare -a routes=(
    "/admin"
    "/admin/login"
    "/admin/activity-logs"
    "/admin/appointments"
    "/admin/appointments/create"
    "/admin/balance-bonus-tiers"
    "/admin/balance-bonus-tiers/create"
    "/admin/balance-topups"
    "/admin/balance-topups/create"
    "/admin/branches"
    "/admin/branches/create"
    "/admin/calls"
    "/admin/calls/create"
    "/admin/companies"
    "/admin/companies/create"
    "/admin/customer-notes"
    "/admin/customer-notes/create"
    "/admin/customers"
    "/admin/customers/create"
    "/admin/integrations"
    "/admin/integrations/create"
    "/admin/invoices"
    "/admin/invoices/create"
    "/admin/permissions"
    "/admin/phone-numbers"
    "/admin/phone-numbers/create"
    "/admin/pricing-plans"
    "/admin/pricing-plans/create"
    "/admin/retell-agents"
    "/admin/retell-agents/create"
    "/admin/roles"
    "/admin/roles/create"
    "/admin/services"
    "/admin/services/create"
    "/admin/staff"
    "/admin/staff/create"
    "/admin/system-settings"
    "/admin/tenants"
    "/admin/tenants/create"
    "/admin/transactions"
    "/admin/transactions/create"
    "/admin/users"
    "/admin/users/create"
    "/admin/working-hours"
    "/admin/working-hours/create"
)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Counters
TOTAL=0
SUCCESS=0
REDIRECT=0
ERROR=0

# Test each route
for route in "${routes[@]}"; do
    TOTAL=$((TOTAL + 1))

    # Make request and capture status code
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost${route}")

    # Check status and format output
    if [ "$STATUS" = "200" ]; then
        echo -e "${GREEN}✓${NC} ${route}: ${GREEN}${STATUS}${NC}"
        SUCCESS=$((SUCCESS + 1))
    elif [ "$STATUS" = "301" ] || [ "$STATUS" = "302" ]; then
        echo -e "${YELLOW}→${NC} ${route}: ${YELLOW}${STATUS}${NC} (Redirect)"
        REDIRECT=$((REDIRECT + 1))
    elif [ "$STATUS" = "404" ]; then
        echo -e "${YELLOW}?${NC} ${route}: ${YELLOW}${STATUS}${NC} (Not Found)"
    elif [ "$STATUS" = "500" ] || [ "$STATUS" = "502" ] || [ "$STATUS" = "503" ]; then
        echo -e "${RED}✗${NC} ${route}: ${RED}${STATUS}${NC} (ERROR)"
        ERROR=$((ERROR + 1))

        # Get error details if 500
        if [ "$STATUS" = "500" ]; then
            echo "  Fetching error details..."
            RESPONSE=$(curl -s "http://localhost${route}" | head -100)

            # Check if it's a generic error page or detailed error
            if echo "$RESPONSE" | grep -q "Server Error"; then
                echo "  → Generic 500 error page displayed"
            fi

            # Check latest log entry for this route
            echo "  → Checking logs..."
            tail -5 /var/www/api-gateway/storage/logs/laravel.log | grep -A2 "$route" || echo "    No recent log entries"
        fi
    else
        echo -e "${YELLOW}?${NC} ${route}: ${YELLOW}${STATUS}${NC}"
    fi
done

# Summary
echo ""
echo "========================================="
echo "Test Summary"
echo "========================================="
echo -e "Total Routes Tested: ${TOTAL}"
echo -e "${GREEN}Successful (200):${NC} ${SUCCESS}"
echo -e "${YELLOW}Redirects (301/302):${NC} ${REDIRECT}"
echo -e "${RED}Errors (500):${NC} ${ERROR}"

if [ $ERROR -eq 0 ]; then
    echo -e "\n${GREEN}✅ All routes are working! No 500 errors found.${NC}"
else
    echo -e "\n${RED}⚠️ Found ${ERROR} routes with 500 errors that need fixing.${NC}"
fi