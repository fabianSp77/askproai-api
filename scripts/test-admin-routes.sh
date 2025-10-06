#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                  ADMIN ROUTE MIGRATION TEST                      ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

BASE_URL="https://api.askproai.de"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Test new admin routes
echo "Testing NEW /admin routes:"
echo "─────────────────────────────────────"

ADMIN_ROUTES=(
    "/admin/login"
    "/admin"
    "/admin/customers"
    "/admin/calls"
    "/admin/appointments"
    "/admin/companies"
    "/admin/staff"
    "/admin/services"
)

for route in "${ADMIN_ROUTES[@]}"; do
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$route")
    if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ]; then
        echo -e "${GREEN}✅${NC} $route → HTTP $STATUS"
    else
        echo -e "${RED}❌${NC} $route → HTTP $STATUS"
    fi
done

echo ""
echo "Testing OLD /business redirects:"
echo "─────────────────────────────────────"

BUSINESS_ROUTES=(
    "/business/login"
    "/business"
    "/business/customers"
    "/business/calls"
)

for route in "${BUSINESS_ROUTES[@]}"; do
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}|%{redirect_url}" "$BASE_URL$route")
    STATUS=$(echo $RESPONSE | cut -d'|' -f1)
    REDIRECT=$(echo $RESPONSE | cut -d'|' -f2)

    if [ "$STATUS" = "301" ] && [[ "$REDIRECT" == *"/admin"* ]]; then
        echo -e "${GREEN}✅${NC} $route → Redirects to ${REDIRECT:(-20)}"
    else
        echo -e "${RED}❌${NC} $route → No redirect (HTTP $STATUS)"
    fi
done

echo ""
echo "Testing root redirect:"
echo "─────────────────────────────────────"

ROOT_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}|%{redirect_url}" "$BASE_URL/")
ROOT_STATUS=$(echo $ROOT_RESPONSE | cut -d'|' -f1)
ROOT_REDIRECT=$(echo $ROOT_RESPONSE | cut -d'|' -f2)

if [ "$ROOT_STATUS" = "302" ] && [[ "$ROOT_REDIRECT" == *"/admin"* ]]; then
    echo -e "${GREEN}✅${NC} Root (/) redirects to /admin"
else
    echo -e "${RED}❌${NC} Root (/) does not redirect properly"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "TEST COMPLETE"
echo ""
echo "New admin URL: ${GREEN}https://api.askproai.de/admin/login${NC}"
echo "═══════════════════════════════════════════════════════════════════"