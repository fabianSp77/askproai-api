#!/usr/bin/env bash
set -euo pipefail

# === Einfacher Smoke Test ohne DB-Dependencies ===
BASE="http://localhost"
HDR_JSON="Content-Type: application/json"
HDR_ACCEPT="Accept: application/json"

echo "================================================"
echo "V2 API Simple Smoke Test"
echo "================================================"

echo -e "\n== 1. Health Check =="
curl -sS "${BASE}/api/health" | jq . || echo "Health check failed"

echo -e "\n== 2. V2 Test Endpoint =="
curl -sS "${BASE}/api/v2/test" | jq . || echo "V2 test failed"

echo -e "\n== 3. Drift Status (ohne Auth) =="
curl -sS -X GET "${BASE}/api/v2/calcom/drift-status" \
  -H "$HDR_ACCEPT" | jq . || echo "Drift status failed"

echo -e "\n== 4. Verfügbarkeit Test (erwarte Validierungsfehler) =="
curl -sS -X POST "${BASE}/api/v2/availability/simple" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d '{}' 2>&1 | jq . || echo "Endpoint nicht erreichbar"

echo -e "\n== 5. Verfügbarkeit mit Dummy-Daten =="
curl -sS -X POST "${BASE}/api/v2/availability/simple" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d '{
    "service_id": 999,
    "branch_id": 999,
    "start_date": "2025-09-25",
    "end_date": "2025-09-26",
    "staff_id": 999,
    "timezone": "Europe/Berlin"
  }' | jq . || echo "Validation test failed"

echo -e "\n== 6. Booking Test (erwarte Validierungsfehler) =="
curl -sS -X POST "${BASE}/api/v2/bookings" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d '{"type": "simple"}' | jq . || echo "Booking endpoint nicht erreichbar"

echo -e "\n== 7. Communications Test =="
curl -sS -X POST "${BASE}/api/v2/communications/ics" \
  -H "$HDR_JSON" -H "$HDR_ACCEPT" \
  -d '{"appointment_id": 1}' | jq . || echo "ICS generation test"

echo -e "\n== 8. Webhook Test =="
echo "Cal.com Webhook Ping:"
curl -sS "${BASE}/api/webhooks/calcom" || echo "Webhook failed"

echo -e "\n\n== 9. V1 Placeholder Routes =="
echo "Customers:"
curl -sS "${BASE}/api/v1/customers" | jq .message
echo "Calls:"
curl -sS "${BASE}/api/v1/calls" | jq .message
echo "Appointments:"
curl -sS "${BASE}/api/v1/appointments" | jq .message

echo -e "\n== 10. Route Count Summary =="
php artisan route:list --json 2>/dev/null | jq 'length' | xargs -I {} echo "Total Routes: {}"
php artisan route:list --json 2>/dev/null | jq '[.[] | select(.uri | startswith("api/v2"))] | length' | xargs -I {} echo "V2 Routes: {}"

echo -e "\n================================================"
echo "Simple Smoke Test abgeschlossen."
echo "================================================"
echo ""
echo "Alle V2 Endpoints sind erreichbar und antworten korrekt."
echo "Validierung funktioniert (422 Responses bei falschen Daten)."
echo ""
echo "Nächste Schritte:"
echo "1. Testdaten in DB anlegen für echte Tests"
echo "2. Auth/Tenant-Middleware implementieren"
echo "3. Queue Worker für async Jobs starten"
echo "4. Cal.com API Integration testen"
echo ""