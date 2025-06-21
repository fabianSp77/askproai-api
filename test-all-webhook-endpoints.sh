#!/bin/bash

echo "Testing all Retell webhook endpoints..."
echo "======================================="

# Generate test data
CALL_ID=$(uuidgen)
TIMESTAMP=$(date +%s)000

# Test payload
PAYLOAD=$(cat <<EOF
{
  "event": "call_ended",
  "call": {
    "call_id": "$CALL_ID",
    "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
    "from_number": "+4915234567890",
    "to_number": "+493083793369",
    "call_status": "ended",
    "start_timestamp": $((TIMESTAMP - 300000)),
    "end_timestamp": $TIMESTAMP,
    "duration_ms": 300000,
    "transcript": "Test webhook"
  }
}
EOF
)

echo "1. Testing /api/retell/webhook (with signature - should fail currently)"
curl -s -X POST "https://api.askproai.de/api/retell/webhook" \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: test-signature" \
  -H "X-Retell-Timestamp: $TIMESTAMP" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n2. Testing /api/retell/debug-webhook (no signature - should work)"
curl -s -X POST "https://api.askproai.de/api/retell/debug-webhook" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n3. Testing /api/test/webhook (no signature - should work)"
curl -s -X POST "https://api.askproai.de/api/test/webhook" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD" \
  -w "\nHTTP Status: %{http_code}\n"

echo -e "\n======================================="
echo "Test complete. Check results above."