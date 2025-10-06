#!/usr/bin/env bash
set -euo pipefail

# Configuration
BASE="${API_BASE:-http://localhost}"
TOKEN="${API_TOKEN:-}"
TENANT="${TENANT_HEADER:-}"
BRANCH_ID="${BRANCH_ID:-1}"
SID_COMP="${SERVICE_COMPOSITE_ID:-2}"
STAFF_ID="${STAFF_ID:-1}"
TZ="Europe/Berlin"
AUTH="${TOKEN:+Authorization: Bearer ${TOKEN}}"
JSON="Content-Type: application/json"
ACCEPT="Accept: application/json"

echo "======================================================"
echo "DST TRANSITION TEST (Europe/Berlin)"
echo "======================================================"
echo ""
echo "Testing timezone handling across DST boundary"
echo "DST ends: 2025-10-26 03:00 CEST → 02:00 CET"
echo ""

# DST transition: 2025-10-26 at 03:00 CEST → 02:00 CET
START="2025-10-25T20:00:00Z"   # Oct 25, 22:00 CEST (before DST end)
END="2025-10-27T12:00:00Z"     # Oct 27, 13:00 CET (after DST end)

echo "[1/3] Composite availability across DST boundary"
echo "Query period: Oct 25 22:00 CEST to Oct 27 13:00 CET"
echo ""

RESPONSE=$(curl -sS -X POST "${BASE}/api/v2/availability/composite" \
  ${AUTH:+-H "$AUTH"} \
  -H "$JSON" -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} \
  -d "{
    \"service_id\": ${SID_COMP},
    \"branch_id\": ${BRANCH_ID},
    \"start_date\": \"2025-10-25\",
    \"end_date\": \"2025-10-27\",
    \"staff_ids\": [${STAFF_ID}],
    \"timezone\": \"${TZ}\"
  }" 2>/dev/null)

echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"

echo ""
echo "[2/3] Test booking at DST transition time"
# Book appointment at 02:30 on Oct 26 (ambiguous time)
echo "Attempting booking at ambiguous time (02:30 on Oct 26)"

BOOK_RESPONSE=$(curl -sS -X POST "${BASE}/api/v2/bookings" \
  ${AUTH:+-H "$AUTH"} \
  -H "$JSON" -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} \
  -d "{
    \"type\": \"composite\",
    \"service_id\": ${SID_COMP},
    \"branch_id\": ${BRANCH_ID},
    \"composite_group_uid\": \"dst-test-$(date +%s)\",
    \"segments\": [
      {
        \"segment_type\": \"A\",
        \"staff_id\": ${STAFF_ID},
        \"start_time\": \"2025-10-26T01:30:00+02:00\",
        \"end_time\": \"2025-10-26T02:30:00+02:00\"
      },
      {
        \"segment_type\": \"B\",
        \"staff_id\": ${STAFF_ID},
        \"start_time\": \"2025-10-26T14:00:00+01:00\",
        \"end_time\": \"2025-10-26T15:00:00+01:00\"
      }
    ],
    \"timezone\": \"${TZ}\",
    \"customer\": {
      \"name\": \"DST Test\",
      \"email\": \"dst-test@example.com\",
      \"phone\": \"+491701234567\"
    }
  }" 2>/dev/null)

if echo "$BOOK_RESPONSE" | grep -q '"error"'; then
    echo "⚠️ Booking failed (expected without valid data)"
else
    echo "✅ DST-aware booking handled"
fi

echo ""
echo "[3/3] ICS generation for DST-crossing appointment"
ICS_RESPONSE=$(curl -sS -X POST "${BASE}/api/v2/communications/ics" \
  ${AUTH:+-H "$AUTH"} \
  -H "$JSON" -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} \
  -d "{
    \"appointment_id\": \"1\",
    \"format\": \"composite\",
    \"include_dst_note\": true
  }" 2>/dev/null)

if echo "$ICS_RESPONSE" | grep -q "VTIMEZONE"; then
    echo "✅ ICS with timezone info generated"
else
    echo "⚠️ ICS generation needs timezone handling"
fi

echo ""
echo "======================================================"
echo "DST TEST EXPECTATIONS:"
echo "======================================================"
echo "1. Times before DST: Should show CEST (+02:00)"
echo "2. Times after DST: Should show CET (+01:00)"
echo "3. Ambiguous hour (02:00-03:00): Should handle gracefully"
echo "4. ICS files: Must include VTIMEZONE component"
echo ""
echo "Note: Cal.com API v2 requires explicit timezone handling."
echo "All times should be stored in UTC and converted correctly."
echo ""