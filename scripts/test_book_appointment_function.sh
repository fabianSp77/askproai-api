#!/bin/bash

# Test book_appointment_v17 Function Call
# This simulates what Retell should send to our backend

echo "🧪 Testing book_appointment_v17 Function Call..."
echo "================================================"
echo ""

curl -X POST https://api.askproai.de/api/webhooks/retell/function \
  -H "Content-Type: application/json" \
  -d '{
    "name": "book_appointment_v17",
    "args": {
      "name": "Test User",
      "datum": "morgen",
      "dienstleistung": "Herrenhaarschnitt",
      "uhrzeit": "15:50"
    },
    "call": {
      "call_id": "test_manual_call_001"
    }
  }' \
  -w "\n\n📊 Status: %{http_code}\n⏱️  Time: %{time_total}s\n" \
  -v

echo ""
echo "================================================"
echo "✅ Test complete! Check logs for processing:"
echo "   tail -f storage/logs/laravel.log | grep 'test_manual_call_001'"
