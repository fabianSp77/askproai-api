#!/bin/bash

echo "=== TESTING ALL ADMIN PAGES ==="
echo "Testing as unauthenticated (expecting 302 redirects)"
echo ""

BASE_URL="https://api.askproai.de/admin"
PAGES=(
    ""
    "dashboard"
    "integrations"
    "integrations/6"
    "integrations/6/edit"
    "services" 
    "appointments"
    "branches"
    "companies"
    "customers"
    "staff"
    "users"
    "roles"
    "permissions"
)

for page in "${PAGES[@]}"; do
    url="${BASE_URL}/${page}"
    status=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    if [ "$status" == "500" ]; then
        echo "❌ $url: $status - SERVER ERROR!"
    elif [ "$status" == "302" ] || [ "$status" == "200" ]; then
        echo "✅ $url: $status"
    else
        echo "⚠️ $url: $status"
    fi
done

echo ""
echo "=== TEST COMPLETE ==="
