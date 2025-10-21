#!/bin/bash
###############################################################################
# EMERGENCY AGENT RESTORATION SCRIPT
# Purpose: Restore Retell Agent V117 to working V33 configuration
# Created: 2025-10-19 22:30 UTC
# Severity: P0 - CRITICAL
# Usage: bash EMERGENCY_RESTORATION_SCRIPT_2025_10_19.sh
###############################################################################

set -e  # Exit on error

echo "🚨 EMERGENCY AGENT RESTORATION - STARTING 🚨"
echo "================================================"
echo "Incident: Agent V117 complete freeze (silence after greeting)"
echo "Target: Restore working V33 configuration"
echo "Impact: 100% call failure rate"
echo "================================================"
echo ""

# Change to application directory
cd /var/www/api-gateway

# Configuration
AGENT_ID="agent_9a8202a740cd3120d96fcfda1e"
API_KEY=$(php artisan tinker --execute='echo config("services.retellai.api_key");' 2>/dev/null | tail -1)

if [ -z "$API_KEY" ]; then
  echo "❌ ERROR: Could not retrieve Retell API key from config"
  exit 1
fi

echo "✅ Configuration loaded"
echo "   Agent ID: $AGENT_ID"
echo "   API Key: ${API_KEY:0:10}..."
echo ""

# Step 1: Extract working prompt from database
echo "📥 Step 1: Extracting working V33 prompt from database..."
php artisan tinker --execute="
\$agent = \DB::table('retell_agents')
    ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
    ->first();
\$config = json_decode(\$agent->configuration, true);
file_put_contents('/tmp/v33_working_prompt.txt', \$config['prompt']);
file_put_contents('/tmp/v33_first_sentence.txt', \$config['first_sentence'] ?? 'Guten Tag! Willkommen bei AskProAI. Wie kann ich Ihnen heute helfen?');
echo 'OK';
" 2>/dev/null | tail -1

if [ ! -f /tmp/v33_working_prompt.txt ]; then
  echo "❌ ERROR: Could not extract working prompt from database"
  exit 1
fi

PROMPT=$(cat /tmp/v33_working_prompt.txt)
FIRST_SENTENCE=$(cat /tmp/v33_first_sentence.txt)
PROMPT_LENGTH=$(echo -n "$PROMPT" | wc -c)

echo "✅ Prompt extracted ($PROMPT_LENGTH characters)"
echo "   First sentence: ${FIRST_SENTENCE:0:50}..."
echo ""

# Step 2: Prepare API payload
echo "📦 Step 2: Preparing Retell API update payload..."

# Escape prompt for JSON (basic escaping - handles quotes and newlines)
PROMPT_ESCAPED=$(echo "$PROMPT" | php -r "echo json_encode(file_get_contents('php://stdin'));")
FIRST_SENTENCE_ESCAPED=$(echo "$FIRST_SENTENCE" | php -r "echo json_encode(file_get_contents('php://stdin'));")

cat > /tmp/retell_update_payload.json <<EOF
{
  "agent_name": "Online: Assistent für Fabian Spitzer Rechtliches/V33",
  "llm_config": {
    "model": "gemini-2.5-flash",
    "system_prompt": $PROMPT_ESCAPED,
    "initial_message": $FIRST_SENTENCE_ESCAPED,
    "temperature": 0.7
  },
  "language": "de-DE"
}
EOF

echo "✅ Payload prepared (/tmp/retell_update_payload.json)"
echo ""

# Step 3: Update Retell agent via API
echo "🚀 Step 3: Updating Retell agent via API..."
echo "   URL: https://api.retellai.com/update-agent/$AGENT_ID"
echo ""

HTTP_STATUS=$(curl -w "%{http_code}" -o /tmp/retell_api_response.json -X PATCH \
  "https://api.retellai.com/update-agent/$AGENT_ID" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d @/tmp/retell_update_payload.json)

echo "   HTTP Status: $HTTP_STATUS"

if [ "$HTTP_STATUS" -ge 200 ] && [ "$HTTP_STATUS" -lt 300 ]; then
  echo "✅ Retell API update SUCCESSFUL"
  echo ""
  echo "📄 API Response:"
  cat /tmp/retell_api_response.json | jq . 2>/dev/null || cat /tmp/retell_api_response.json
  echo ""
else
  echo "❌ Retell API update FAILED"
  echo "   HTTP Status: $HTTP_STATUS"
  echo "   Response:"
  cat /tmp/retell_api_response.json
  echo ""
  exit 1
fi

# Step 4: Update database sync status
echo "💾 Step 4: Updating database sync status..."
php artisan tinker --execute="
\DB::table('retell_agents')
  ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
  ->update([
    'is_published' => 1,
    'sync_status' => 'synced',
    'last_synced_at' => now(),
    'updated_at' => now()
  ]);
echo 'OK';
" 2>/dev/null | tail -1

echo "✅ Database updated"
echo ""

# Step 5: Verification
echo "🔍 Step 5: Verifying agent status..."
php artisan tinker --execute="
\$agent = \DB::table('retell_agents')
  ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
  ->first();
echo 'Version: ' . \$agent->version . PHP_EOL;
echo 'Sync Status: ' . \$agent->sync_status . PHP_EOL;
echo 'Last Synced: ' . \$agent->last_synced_at . PHP_EOL;
" 2>/dev/null

echo ""

# Cleanup
echo "🧹 Step 6: Cleanup temporary files..."
rm -f /tmp/v33_working_prompt.txt
rm -f /tmp/v33_first_sentence.txt
rm -f /tmp/retell_update_payload.json
rm -f /tmp/retell_api_response.json
echo "✅ Cleanup complete"
echo ""

echo "================================================"
echo "✅ EMERGENCY RESTORATION COMPLETE"
echo "================================================"
echo ""
echo "NEXT STEPS:"
echo "1. Make a test call to: +493083793369"
echo "2. Verify agent RESPONDS after greeting (not silence)"
echo "3. Check that multi-turn conversation works"
echo "4. Monitor next 5 calls for stability"
echo ""
echo "TEST CALL CHECKLIST:"
echo "- [  ] Greeting plays successfully"
echo "- [  ] User provides appointment request"
echo "- [  ] Agent RESPONDS (NO SILENCE!) ← CRITICAL"
echo "- [  ] Agent calls parse_date function"
echo "- [  ] Agent checks availability"
echo "- [  ] Conversation continues naturally"
echo "- [  ] Call duration > 45 seconds"
echo ""
echo "VERIFICATION COMMAND:"
echo "php artisan tinker --execute='"
echo "\$lastCall = \DB::table('calls')->orderBy('created_at', 'desc')->first();"
echo "echo 'LLM Requests: ' . json_decode(\$lastCall->llm_token_usage, true)['num_requests'] . PHP_EOL;"
echo "echo 'Success: ' . (\$lastCall->call_successful ? 'YES' : 'NO') . PHP_EOL;"
echo "echo 'Duration: ' . \$lastCall->call_time . 's' . PHP_EOL;"
echo "'"
echo ""
echo "SUCCESS CRITERIA:"
echo "- LLM Requests: >= 3 (multi-turn conversation)"
echo "- Call Success: YES"
echo "- Duration: >= 45 seconds"
echo ""
echo "If test call FAILS, escalate to Retell Support immediately!"
echo "Support: support@retellai.com"
echo ""
echo "🚨 SERVICE RESTORATION IN PROGRESS - TEST NOW! 🚨"
