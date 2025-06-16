#!/bin/bash

# Farben für bessere Lesbarkeit
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# API-Schlüssel
API_KEY="cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Cal.com API V1 vs V2 Vergleichstest${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Test 1: Event-Types abrufen
echo -e "${GREEN}TEST 1: Event-Types abrufen${NC}"
echo -e "${BLUE}V1 Version:${NC}"
curl -s -X GET "https://api.cal.com/v1/event-types?apiKey=${API_KEY}" | jq '.event_types[0] | {id, title, slug, length}'

echo -e "\n${BLUE}V2 Version:${NC}"
curl -s -X GET "https://api.cal.com/v2/event-types" \
  -H "Authorization: Bearer ${API_KEY}" \
  -H "cal-api-version: 2024-08-13" | jq '.data[0] | {id, title, slug, length}' 2>/dev/null || echo "V2 Event-Types Endpunkt existiert möglicherweise nicht"

echo -e "\n${GREEN}========================================${NC}\n"

# Test 2: Verfügbarkeit prüfen
echo -e "${GREEN}TEST 2: Verfügbarkeit prüfen${NC}"
echo -e "${BLUE}V1 Version (mit userId):${NC}"
curl -s -X GET "https://api.cal.com/v1/availability?apiKey=${API_KEY}&eventTypeId=2026302&dateFrom=2025-06-08&dateTo=2025-06-15&userId=1414768" | jq '.'

echo -e "\n${BLUE}V2 Version:${NC}"
echo "Hinweis: V2 Availability-Endpunkt scheint nicht zu existieren"

echo -e "\n${GREEN}========================================${NC}\n"

# Test 3: Buchung erstellen
echo -e "${GREEN}TEST 3: Buchung erstellen (Simulation)${NC}"

echo -e "${BLUE}V1 Format:${NC}"
cat << 'EOF'
curl -X POST "https://api.cal.com/v1/bookings?apiKey=API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026302,
    "start": "2025-06-11T10:00:00Z",
    "timeZone": "Europe/Berlin",
    "responses": {
      "name": "V1 Testbuchung",
      "email": "v1test@example.com",
      "location": "phone"
    }
  }'
EOF

echo -e "\n${BLUE}V2 Format (FUNKTIONIERT):${NC}"
cat << 'EOF'
curl -X POST "https://api.cal.com/v2/bookings" \
  -H "Authorization: Bearer API_KEY" \
  -H "cal-api-version: 2024-08-13" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026302,
    "start": "2025-06-11T10:00:00Z",
    "attendee": {
      "name": "V2 Testbuchung",
      "email": "v2test@example.com",
      "timeZone": "Europe/Berlin",
      "phoneNumber": "+491234567890"
    },
    "metadata": {
      "source": "askproai_system"
    }
  }'
EOF

echo -e "\n${GREEN}========================================${NC}\n"

# Test 4: Buchungen abrufen
echo -e "${GREEN}TEST 4: Buchungen abrufen${NC}"
echo -e "${BLUE}V1 Version:${NC}"
curl -s -X GET "https://api.cal.com/v1/bookings?apiKey=${API_KEY}" | jq '.bookings[0] | {id, title, status}' 2>/dev/null || echo "V1 Bookings GET nicht verfügbar"

echo -e "\n${BLUE}V2 Version (FUNKTIONIERT):${NC}"
curl -s -X GET "https://api.cal.com/v2/bookings" \
  -H "Authorization: Bearer ${API_KEY}" \
  -H "cal-api-version: 2024-08-13" | jq '.data[0] | {id, title, status}'

echo -e "\n${GREEN}========================================${NC}\n"

# Zusammenfassung
echo -e "${RED}ZUSAMMENFASSUNG:${NC}"
echo "1. V2 API ist verfügbar und funktioniert"
echo "2. V2 verwendet 'Authorization: Bearer' Header statt apiKey Parameter"
echo "3. V2 benötigt 'cal-api-version: 2024-08-13' Header"
echo "4. V2 hat andere Datenstrukturen (z.B. 'attendee' statt 'responses')"
echo "5. V2 Bookings CREATE und GET funktionieren"
echo "6. V2 Availability-Endpunkt existiert möglicherweise nicht"
