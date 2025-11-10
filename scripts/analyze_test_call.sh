#!/bin/bash

# Analyze Test Call Logs
# Usage: ./scripts/analyze_test_call.sh <call_id>

CALL_ID=$1
LOG_FILE="storage/logs/laravel.log"

if [ -z "$CALL_ID" ]; then
  echo "Usage: $0 <call_id>"
  echo "Example: $0 call_793088ed"
  echo ""
  echo "Recent call IDs:"
  grep "call_id" $LOG_FILE | grep -oP 'call_[a-f0-9]+' | sort -u | tail -5
  exit 1
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  TEST CALL ANALYSIS: $CALL_ID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Check if jq is available
if ! command -v jq &> /dev/null; then
  echo "⚠️  Warning: jq not installed. Output will be raw JSON."
  echo "   Install with: sudo apt-get install jq"
  echo ""
  USE_JQ=false
else
  USE_JQ=true
fi

echo "📋 1. CALL TIMELINE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API)" | \
    sed 's/.*\(WEBHOOK\|FUNCTION_CALL\|CALCOM_API\) //' | \
    jq -r '"\(.timestamp) | \(.event // .function // .endpoint) | \(.data_flow)"' 2>/dev/null || \
    grep "$CALL_ID" $LOG_FILE | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API)"
else
  grep "$CALL_ID" $LOG_FILE | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API)" | \
    grep -oP '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}'
fi
echo ""

echo "🔔 2. WEBHOOK EVENTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep "WEBHOOK" | \
    sed 's/.*WEBHOOK //' | \
    jq -r 'select(.event != null) | "\(.timestamp) | \(.event) | Payload: \(.payload_size) bytes"' 2>/dev/null || \
    echo "No webhook events found"
else
  grep "$CALL_ID" $LOG_FILE | grep "WEBHOOK" | wc -l | xargs echo "Webhook events:"
fi
echo ""

echo "📤 3. DYNAMIC VARIABLES SENT TO AGENT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep "DYNAMIC_VARS" | \
    sed 's/.*DYNAMIC_VARS //' | \
    jq '.variables' 2>/dev/null || \
    echo "No dynamic variables found"
else
  grep "$CALL_ID" $LOG_FILE | grep "DYNAMIC_VARS" | head -1
fi
echo ""

echo "⚡ 4. FUNCTION CALLS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep "FUNCTION_CALL" | \
    sed 's/.*FUNCTION_CALL //' | \
    jq -r 'select(.function != null) | "\(.timestamp) | \(.function) | Duration: \(.duration_ms // "N/A")ms"' 2>/dev/null || \
    echo "No function calls found"
else
  grep "$CALL_ID" $LOG_FILE | grep "FUNCTION_CALL" | \
    grep -oP '"function":"[^"]+"|"duration_ms":[0-9.]+' | \
    paste - - || echo "No function calls found"
fi
echo ""

echo "🔗 5. CAL.COM API CALLS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep "CALCOM_API" | \
    sed 's/.*CALCOM_API //' | \
    jq -r 'select(.endpoint != null) | "\(.timestamp) | \(.method) \(.endpoint) | Status: \(.status_code // "N/A") | Duration: \(.duration_ms // "N/A")ms"' 2>/dev/null || \
    echo "No Cal.com API calls found"
else
  grep "$CALL_ID" $LOG_FILE | grep "CALCOM_API" | \
    grep -oP '"method":"[^"]+"|"endpoint":"[^"]+"|"status_code":\d+' | \
    paste - - - || echo "No Cal.com API calls found"
fi
echo ""

echo "❌ 6. ERRORS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  grep "$CALL_ID" $LOG_FILE | grep "ERROR" | \
    sed 's/.*ERROR //' | \
    jq -r 'select(.error_message != null) | "\(.timestamp) | \(.context): \(.error_message)"' 2>/dev/null || \
    echo "✅ No errors found"
else
  ERROR_COUNT=$(grep "$CALL_ID" $LOG_FILE | grep "ERROR" | wc -l)
  if [ "$ERROR_COUNT" -eq 0 ]; then
    echo "✅ No errors found"
  else
    echo "⚠️  $ERROR_COUNT errors found:"
    grep "$CALL_ID" $LOG_FILE | grep "ERROR" | grep -oP '"error_message":"[^"]+"'
  fi
fi
echo ""

echo "📊 7. PERFORMANCE METRICS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$USE_JQ" = true ]; then
  echo "Function Call Durations:"
  grep "$CALL_ID" $LOG_FILE | grep "FUNCTION_CALL" | \
    sed 's/.*FUNCTION_CALL //' | \
    jq -r 'select(.duration_ms != null) | "  \(.function): \(.duration_ms)ms"' 2>/dev/null || \
    echo "  No timing data"

  echo ""
  echo "Cal.com API Durations:"
  grep "$CALL_ID" $LOG_FILE | grep "CALCOM_API" | \
    sed 's/.*CALCOM_API //' | \
    jq -r 'select(.duration_ms != null) | "  \(.method) \(.endpoint): \(.duration_ms)ms"' 2>/dev/null || \
    echo "  No timing data"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  ANALYSIS COMPLETE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Export full logs:"
echo "  grep '$CALL_ID' $LOG_FILE > testcall_${CALL_ID}.log"
echo ""
echo "View specific log type:"
echo "  grep '$CALL_ID' $LOG_FILE | grep FUNCTION_CALL | less"
echo ""
