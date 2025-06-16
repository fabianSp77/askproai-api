#!/bin/bash

# Erstelle eine echte V2-Buchung für nächste Woche
echo "Erstelle V2-Buchung für nächste Woche..."

curl -X POST "https://api.cal.com/v2/bookings" \
  -H "Authorization: Bearer cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da" \
  -H "cal-api-version: 2024-08-13" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026302,
    "start": "2025-06-12T15:30:00Z",
    "attendee": {
      "name": "Test Kunde V2",
      "email": "testkunde@askproai.de",
      "timeZone": "Europe/Berlin",
      "phoneNumber": "+491234567890"
    },
    "metadata": {
      "source": "askproai_retell",
      "call_id": "test_123456",
      "agent": "Musterfriseur"
    },
    "language": "de"
  }' | jq '.'
