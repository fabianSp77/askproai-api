#!/usr/bin/env bash
set -euo pipefail

# === Konfiguration ===
BASE="http://localhost"  # Für lokale Tests, später: https://api.askproai.de
# TOKEN würde später aus auth kommen, aktuell haben wir keine Auth implementiert
BRANCH_ID="1"
SERVICE_SIMPLE_ID="1"
SERVICE_COMPOSITE_ID="2"
STAFF_ID="1"
TIMEZONE="Europe/Berlin"
CUST_NAME="Max Mustermann"
CUST_MAIL="max@example.com"
CUST_PHONE="+491701234567"

# Headers (ohne Auth erstmal, da noch nicht implementiert)
HDR_JSON="Content-Type: application/json"
HDR_ACCEPT="Accept: application/json"

echo "================================================"
echo "V2 API End-to-End Smoke Test"
echo "================================================"

echo -e "\n== 1. Health Check =="
curl -sS "${BASE}/api/health" | jq .

echo -e "\n== 2. Event-Typen PUSH (System -> Cal.com) =="
curl -sS -X POST "${BASE}/api/v2/calcom/push-event-types" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d "{\"branch_id\": ${BRANCH_ID}}" | jq . || echo "Push fehlgeschlagen (noch nicht implementiert)"

echo -e "\n== 3. Drift Detection =="
curl -sS -X POST "${BASE}/api/v2/calcom/detect-drift" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d "{\"branch_id\": ${BRANCH_ID}}" | jq . || echo "Drift detection fehlgeschlagen"

echo -e "\n== 4. Drift Status =="
curl -sS -X GET "${BASE}/api/v2/calcom/drift-status" \
  -H "$HDR_ACCEPT" | jq .

echo -e "\n== 5. Verfügbarkeit: Einfacher Termin =="
# Nächste 7 Tage
START_DATE=$(date +"%Y-%m-%d")
END_DATE=$(date -d "+7 days" +"%Y-%m-%d")

curl -sS -X POST "${BASE}/api/v2/availability/simple" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | tee /tmp/simple_slots.json | jq .
{
  "service_id": ${SERVICE_SIMPLE_ID},
  "branch_id": ${BRANCH_ID},
  "start_date": "${START_DATE}",
  "end_date": "${END_DATE}",
  "staff_id": ${STAFF_ID},
  "timezone": "${TIMEZONE}"
}
EOF

# Extrahiere ersten Slot
if [ -f /tmp/simple_slots.json ]; then
    SLOT_DATA=$(jq -r '.data.slots[0] // empty' /tmp/simple_slots.json 2>/dev/null || echo "")
    if [ -n "$SLOT_DATA" ] && [ "$SLOT_DATA" != "null" ]; then
        SLOT_START=$(echo "$SLOT_DATA" | jq -r '.start')
        SLOT_END=$(echo "$SLOT_DATA" | jq -r '.end')
        echo "Gefundener Slot: $SLOT_START - $SLOT_END"
    else
        echo "Keine Simple-Slots in Response gefunden. Dummy-Daten für Test verwenden."
        # Dummy slot für morgen 10:00
        SLOT_START=$(date -d "tomorrow 10:00" --iso-8601=seconds)
        SLOT_END=$(date -d "tomorrow 11:00" --iso-8601=seconds)
    fi
else
    echo "Response-Datei nicht gefunden. Dummy-Daten verwenden."
    SLOT_START=$(date -d "tomorrow 10:00" --iso-8601=seconds)
    SLOT_END=$(date -d "tomorrow 11:00" --iso-8601=seconds)
fi

echo -e "\n== 6. Buchung: Einfacher Termin =="
curl -sS -X POST "${BASE}/api/v2/bookings" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | tee /tmp/booking_simple.json | jq .
{
  "type": "simple",
  "service_id": ${SERVICE_SIMPLE_ID},
  "branch_id": ${BRANCH_ID},
  "staff_id": ${STAFF_ID},
  "start_time": "${SLOT_START}",
  "end_time": "${SLOT_END}",
  "timezone": "${TIMEZONE}",
  "customer": {
    "name": "${CUST_NAME}",
    "email": "${CUST_MAIL}",
    "phone": "${CUST_PHONE}"
  },
  "notes": "Smoke test booking"
}
EOF

# Extrahiere Appointment ID
APPT_ID_SIMPLE=$(jq -r '.data.appointment.id // empty' /tmp/booking_simple.json 2>/dev/null || echo "")
if [ -z "$APPT_ID_SIMPLE" ] || [ "$APPT_ID_SIMPLE" = "null" ]; then
    echo "Simple-Buchung fehlgeschlagen oder ID nicht gefunden. Verwende Dummy-ID."
    APPT_ID_SIMPLE="1"
fi

echo -e "\n== 7. Verfügbarkeit: Komposit-Termin (A→Pause→B) =="
curl -sS -X POST "${BASE}/api/v2/availability/composite" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | tee /tmp/comp_slots.json | jq .
{
  "service_id": ${SERVICE_COMPOSITE_ID},
  "branch_id": ${BRANCH_ID},
  "start_date": "${START_DATE}",
  "end_date": "${END_DATE}",
  "staff_ids": [${STAFF_ID}],
  "timezone": "${TIMEZONE}"
}
EOF

# Extrahiere ersten Composite Slot
if [ -f /tmp/comp_slots.json ]; then
    COMP_SLOT=$(jq -r '.data.composite_slots[0] // empty' /tmp/comp_slots.json 2>/dev/null || echo "")
    if [ -n "$COMP_SLOT" ] && [ "$COMP_SLOT" != "null" ]; then
        SEGMENT_A_START=$(echo "$COMP_SLOT" | jq -r '.segment_a.start')
        SEGMENT_A_END=$(echo "$COMP_SLOT" | jq -r '.segment_a.end')
        SEGMENT_B_START=$(echo "$COMP_SLOT" | jq -r '.segment_b.start')
        SEGMENT_B_END=$(echo "$COMP_SLOT" | jq -r '.segment_b.end')
        echo "Gefundener Composite Slot:"
        echo "  Segment A: $SEGMENT_A_START - $SEGMENT_A_END"
        echo "  Segment B: $SEGMENT_B_START - $SEGMENT_B_END"
    else
        echo "Keine Composite-Slots gefunden. Dummy-Daten verwenden."
        # Dummy composite slot
        SEGMENT_A_START=$(date -d "tomorrow 09:00" --iso-8601=seconds)
        SEGMENT_A_END=$(date -d "tomorrow 10:00" --iso-8601=seconds)
        SEGMENT_B_START=$(date -d "tomorrow 14:00" --iso-8601=seconds)
        SEGMENT_B_END=$(date -d "tomorrow 15:00" --iso-8601=seconds)
    fi
else
    echo "Response-Datei nicht gefunden. Dummy-Daten verwenden."
    SEGMENT_A_START=$(date -d "tomorrow 09:00" --iso-8601=seconds)
    SEGMENT_A_END=$(date -d "tomorrow 10:00" --iso-8601=seconds)
    SEGMENT_B_START=$(date -d "tomorrow 14:00" --iso-8601=seconds)
    SEGMENT_B_END=$(date -d "tomorrow 15:00" --iso-8601=seconds)
fi

echo -e "\n== 8. Buchung: Komposit-Termin =="
COMPOSITE_GROUP_UID=$(uuidgen || echo "test-composite-$(date +%s)")

curl -sS -X POST "${BASE}/api/v2/bookings" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | tee /tmp/booking_comp.json | jq .
{
  "type": "composite",
  "service_id": ${SERVICE_COMPOSITE_ID},
  "branch_id": ${BRANCH_ID},
  "composite_group_uid": "${COMPOSITE_GROUP_UID}",
  "segments": [
    {
      "segment_type": "A",
      "staff_id": ${STAFF_ID},
      "start_time": "${SEGMENT_A_START}",
      "end_time": "${SEGMENT_A_END}"
    },
    {
      "segment_type": "B",
      "staff_id": ${STAFF_ID},
      "start_time": "${SEGMENT_B_START}",
      "end_time": "${SEGMENT_B_END}"
    }
  ],
  "timezone": "${TIMEZONE}",
  "customer": {
    "name": "${CUST_NAME}",
    "email": "${CUST_MAIL}",
    "phone": "${CUST_PHONE}"
  },
  "notes": "Composite smoke test booking"
}
EOF

# Extrahiere Composite Appointment ID
APPT_ID_COMP=$(jq -r '.data.appointments[0].id // empty' /tmp/booking_comp.json 2>/dev/null || echo "")
if [ -z "$APPT_ID_COMP" ] || [ "$APPT_ID_COMP" = "null" ]; then
    echo "Composite-Buchung fehlgeschlagen oder ID nicht gefunden. Verwende Dummy-ID."
    APPT_ID_COMP="2"
fi

echo -e "\n== 9. Bestätigung senden (Mail + ICS) =="
curl -sS -X POST "${BASE}/api/v2/communications/confirmation" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | jq .
{
  "appointment_id": "${APPT_ID_COMP}",
  "send_email": true,
  "attach_ics": true
}
EOF

echo -e "\n== 10. Erinnerung senden =="
curl -sS -X POST "${BASE}/api/v2/communications/reminder" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | jq .
{
  "appointment_id": "${APPT_ID_COMP}",
  "hours_before": 24
}
EOF

echo -e "\n== 11. ICS-Datei generieren =="
curl -sS -X POST "${BASE}/api/v2/communications/ics" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | jq .
{
  "appointment_id": "${APPT_ID_COMP}",
  "format": "composite"
}
EOF

echo -e "\n== 12. Drift-Status nach Buchungen =="
curl -sS -X GET "${BASE}/api/v2/calcom/drift-status" \
  -H "$HDR_ACCEPT" | jq .

echo -e "\n== 13. Umbuchung testen =="
NEW_START=$(date -d "tomorrow 15:00" --iso-8601=seconds)
NEW_END=$(date -d "tomorrow 16:00" --iso-8601=seconds)

curl -sS -X PATCH "${BASE}/api/v2/bookings/${APPT_ID_SIMPLE}/reschedule" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | jq .
{
  "new_start_time": "${NEW_START}",
  "new_end_time": "${NEW_END}",
  "reason": "Customer request"
}
EOF

echo -e "\n== 14. Stornierung testen =="
curl -sS -X DELETE "${BASE}/api/v2/bookings/${APPT_ID_SIMPLE}" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d '{"reason": "Smoke test cancellation"}' | jq .

echo -e "\n== 15. Storno-Benachrichtigung senden =="
curl -sS -X POST "${BASE}/api/v2/communications/cancellation" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d @- <<EOF | jq .
{
  "appointment_id": "${APPT_ID_SIMPLE}",
  "reason": "Test completed"
}
EOF

echo -e "\n== 16. Auto-Resolve für Drift =="
curl -sS -X POST "${BASE}/api/v2/calcom/auto-resolve" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d "{\"branch_id\": ${BRANCH_ID}}" | jq .

echo -e "\n================================================"
echo "Smoke-Test abgeschlossen."
echo "================================================"
echo ""
echo "Prüfe folgende Punkte manuell:"
echo "1. Queue Worker läuft: php artisan queue:work"
echo "2. Redis läuft: redis-cli ping"
echo "3. Logs prüfen: tail -f storage/logs/laravel.log"
echo "4. Mail-Log prüfen: tail -f storage/logs/mail.log"
echo ""

# Cleanup
rm -f /tmp/simple_slots.json /tmp/booking_simple.json /tmp/comp_slots.json /tmp/booking_comp.json 2>/dev/null || true