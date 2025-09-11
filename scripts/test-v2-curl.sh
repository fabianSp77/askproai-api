#!/bin/bash

# Read API key from .env
API_KEY=$(grep "^CALCOM_API_KEY=" .env | cut -d'=' -f2)

echo "Testing Cal.com V2 API with direct curl..."
echo "==========================================="
echo ""
echo "Test 1: Get Event Type 2026979"
echo "-------------------------------"

curl -s -X GET "https://api.cal.com/v2/event-types/2026979" \
  -H "Authorization: Bearer $API_KEY" \
  -H "cal-api-version: 2025-01-07" \
  -H "Content-Type: application/json" | python3 -m json.tool

echo ""
echo ""
echo "Test 2: Check Schedules endpoint (V2 alternative to availability)"
echo "------------------------------------------------------------------"

curl -s -X GET "https://api.cal.com/v2/schedules" \
  -H "Authorization: Bearer $API_KEY" \
  -H "cal-api-version: 2025-01-07" \
  -H "Content-Type: application/json" | python3 -m json.tool | head -30