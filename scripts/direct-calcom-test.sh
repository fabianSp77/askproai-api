#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}=== Testing Direct Cal.com API Integration ===${NC}"
echo -e "${YELLOW}1. Checking Availability...${NC}"

RESPONSE=$(curl -s -X POST "http://localhost/api/direct-calcom/check-availability" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "dateFrom": "2025-03-30T09:00:00+02:00",
    "dateTo": "2025-03-30T20:00:00+02:00",
    "eventTypeId": 2026901
  }')

echo "Response: $RESPONSE"

if [[ $RESPONSE == *"error"* ]]; then
  echo -e "${RED}✘ Test failed${NC}"
else
  echo -e "${GREEN}✓ Test successful${NC}"
fi

echo ""
echo -e "${YELLOW}2. Creating Booking Test...${NC}"

RESPONSE=$(curl -s -X POST "http://localhost/api/direct-calcom/create-booking" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026901,
    "start": "2025-03-30T14:00:00+02:00",
    "end": "2025-03-30T14:30:00+02:00",
    "name": "Test User",
    "email": "test@example.com"
  }')

echo "Response: $RESPONSE"

if [[ $RESPONSE == *"error"* ]]; then
  echo -e "${RED}✘ Test failed${NC}"
else
  echo -e "${GREEN}✓ Test successful${NC}"
fi

echo -e "\n${YELLOW}=== Tests completed ===${NC}"1~#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}=== Testing Direct Cal.com API Integration ===${NC}"
echo -e "${YELLOW}1. Checking Availability...${NC}"

RESPONSE=$(curl -s -X POST "http://localhost/api/direct-calcom/check-availability" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "dateFrom": "2025-03-30T09:00:00+02:00",
    "dateTo": "2025-03-30T20:00:00+02:00",
    "eventTypeId": 2026901
  }')

echo "Response: $RESPONSE"

if [[ $RESPONSE == *"error"* ]]; then
  echo -e "${RED}✘ Test failed${NC}"
else
  echo -e "${GREEN}✓ Test successful${NC}"
fi

echo ""
echo -e "${YELLOW}2. Creating Booking Test...${NC}"

RESPONSE=$(curl -s -X POST "http://localhost/api/direct-calcom/create-booking" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "eventTypeId": 2026901,
    "start": "2025-03-30T14:00:00+02:00",
    "end": "2025-03-30T14:30:00+02:00",
    "name": "Test User",
    "email": "test@example.com"
  }')

echo "Response: $RESPONSE"

if [[ $RESPONSE == *"error"* ]]; then
  echo -e "${RED}✘ Test failed${NC}"
else
  echo -e "${GREEN}✓ Test successful${NC}"
fi

echo -e "\n${YELLOW}=== Tests completed ===${NC}"
