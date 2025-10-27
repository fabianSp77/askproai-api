#!/bin/bash

RETELL_TOKEN=$(grep RETELL_TOKEN .env | cut -d '=' -f2-)
LLM_ID=$(cat retell_llm_id.txt)

echo "Testing agent creation with curl..."
echo "LLM ID: $LLM_ID"
echo ""

curl -s -X POST https://api.retellai.com/create-agent \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"agent_name\": \"Friseur1 AI LLM Test\",
    \"response_engine\": {
      \"type\": \"retell-llm\",
      \"llm_id\": \"$LLM_ID\",
      \"version\": 0
    },
    \"voice_id\": \"11labs-Christopher\"
  }" | jq .
