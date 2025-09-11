#!/bin/bash

# Read API key from .env
API_KEY=$(grep "^CALCOM_API_KEY=" .env | cut -d'=' -f2)

echo "Testing Cal.com V1 API to verify key validity..."
echo "================================================"
echo ""
echo "Test: Get Event Type using V1 API"
echo "----------------------------------"

# V1 API call with query parameter authentication
curl -s -X GET "https://api.cal.com/v1/event-types/2026979?apiKey=$API_KEY" \
  -H "Content-Type: application/json" | python3 -m json.tool | head -30

echo ""
echo "If the above shows event type data, the API key is valid for V1."
echo "This means we may need to:"
echo "1. Request a new V2-compatible API key from Cal.com"
echo "2. Or check if there's a different authentication method for V2"