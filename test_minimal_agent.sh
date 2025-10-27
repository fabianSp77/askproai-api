#!/bin/bash

RETELL_TOKEN=$(grep RETELL_TOKEN .env | cut -d '=' -f2-)
LLM_ID=$(cat retell_llm_id.txt)

echo "Test 1: Minimal required fields only"
echo "======================================"

curl -s -w "\nHTTP Status: %{http_code}\n" -X POST https://api.retellai.com/create-agent \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"response_engine\": {
      \"type\": \"retell-llm\",
      \"llm_id\": \"$LLM_ID\"
    },
    \"voice_id\": \"11labs-Christopher\"
  }" | jq .

echo ""
echo "Test 2: List all agents to verify API connectivity"
echo "=================================================="

curl -s -w "\nHTTP Status: %{http_code}\n" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  https://api.retellai.com/list-agents | jq '.agents | length'

