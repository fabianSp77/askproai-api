#!/bin/bash

echo "=== Manual Retell Webhook Test ==="
echo "This simulates a Retell webhook call"
echo ""

# Test webhook endpoint
URL="https://api.askproai.de/api/retell/webhook"

# Sample payload
PAYLOAD='{
  "event_type": "call_ended",
  "call": {
    "call_id": "test_' $(date +%s) '",
    "from_number": "+491234567890",
    "to_number": "+493083793369",
    "call_status": "ended",
    "start_time": "2025-06-24T15:00:00Z",
    "end_time": "2025-06-24T15:05:00Z"
  }
}'

echo "Testing webhook without signature..."
curl -X POST "$URL" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo ""
echo "Check logs with: tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell"