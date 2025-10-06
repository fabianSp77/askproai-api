#!/bin/bash

echo "=== Testing All Routes for 500 Errors ==="
echo "=========================================="
echo ""

ROUTES=(
    "/"
    "/admin"
    "/admin/login"
    "/admin/appointments"
    "/admin/balance-topups"
    "/admin/branches"
    "/admin/calls"
    "/admin/companies"
    "/admin/customers"
    "/admin/integrations"
    "/admin/phone-numbers"
    "/admin/services"
    "/admin/staff"
    "/admin/users"
    "/api/health"
    "/monitor/health"
    "/monitor/dashboard"
    "/api/v1/customers"
    "/api/v1/calls"
    "/api/v1/appointments"
    "/business"
    "/business/login"
)

ERROR_ROUTES=()
SUCCESS_ROUTES=()
REDIRECT_ROUTES=()

for route in "${ROUTES[@]}"; do
    echo -n "Testing $route ... "

    # Get HTTP status code
    status=$(curl -s -o /dev/null -w "%{http_code}" -k "https://api.askproai.de$route")

    if [ "$status" = "500" ]; then
        echo "❌ ERROR 500"
        ERROR_ROUTES+=("$route")
    elif [ "$status" = "200" ]; then
        echo "✅ OK ($status)"
        SUCCESS_ROUTES+=("$route")
    elif [ "$status" = "301" ] || [ "$status" = "302" ]; then
        echo "↗️ REDIRECT ($status)"
        REDIRECT_ROUTES+=("$route")
    else
        echo "⚠️ Status: $status"
    fi

    # Small delay to avoid overwhelming
    sleep 0.1
done

echo ""
echo "=========================================="
echo "RESULTS SUMMARY"
echo "=========================================="
echo ""
echo "❌ Routes with 500 Errors: ${#ERROR_ROUTES[@]}"
if [ ${#ERROR_ROUTES[@]} -gt 0 ]; then
    for route in "${ERROR_ROUTES[@]}"; do
        echo "   - $route"
    done
fi

echo ""
echo "✅ Successful Routes: ${#SUCCESS_ROUTES[@]}"
echo "↗️ Redirect Routes: ${#REDIRECT_ROUTES[@]}"

echo ""
echo "=========================================="
echo "ERROR RATE: $(( ${#ERROR_ROUTES[@]} * 100 / ${#ROUTES[@]} ))%"
echo "=========================================="