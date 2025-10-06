#!/usr/bin/env bash
set -euo pipefail

# Configuration
BASE="${API_BASE:-http://localhost}"
TOKEN="${API_TOKEN:-}"
TENANT="${TENANT_HEADER:-}"
BRANCH_ID="${BRANCH_ID:-1}"
AUTH="${TOKEN:+Authorization: Bearer ${TOKEN}}"
JSON="Content-Type: application/json"
ACCEPT="Accept: application/json"

echo "======================================================"
echo "DRIFT DETECTION & RESOLUTION CYCLE"
echo "======================================================"
echo ""
echo "SSOT (Single Source of Truth): Our System"
echo ""

echo "[1/4] Current Drift Status"
INITIAL_STATUS=$(curl -sS -X GET "${BASE}/api/v2/calcom/drift-status" \
  ${AUTH:+-H "$AUTH"} \
  -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} 2>/dev/null)

echo "$INITIAL_STATUS" | jq '.data.summary' 2>/dev/null || echo "$INITIAL_STATUS"

echo ""
echo "[2/4] Detect Drift"
DETECT_RESPONSE=$(curl -sS -X POST "${BASE}/api/v2/calcom/detect-drift" \
  ${AUTH:+-H "$AUTH"} \
  -H "$JSON" -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} \
  -d "{\"branch_id\": ${BRANCH_ID}}" 2>/dev/null)

echo "$DETECT_RESPONSE" | jq '.data.summary' 2>/dev/null || echo "$DETECT_RESPONSE"

DRIFT_COUNT=$(echo "$DETECT_RESPONSE" | jq -r '.data.summary.mappings_with_drift // 0' 2>/dev/null || echo "0")

if [ "$DRIFT_COUNT" -gt 0 ]; then
    echo ""
    echo "⚠️ Drift detected: $DRIFT_COUNT mappings"
    echo ""
    echo "[3/4] Resolve Drift (reset to local)"

    RESOLVE_RESPONSE=$(curl -sS -X POST "${BASE}/api/v2/calcom/resolve-drift" \
      ${AUTH:+-H "$AUTH"} \
      -H "$JSON" -H "$ACCEPT" \
      ${TENANT:+-H "$TENANT"} \
      -d "{
        \"branch_id\": ${BRANCH_ID},
        \"mode\": \"reset_to_local\",
        \"force\": true
      }" 2>/dev/null)

    echo "$RESOLVE_RESPONSE" | jq '.' 2>/dev/null || echo "$RESOLVE_RESPONSE"
else
    echo ""
    echo "✅ No drift detected"
    echo ""
    echo "[3/4] Skipping resolution (no drift)"
fi

echo ""
echo "[4/4] Final Status After Resolution"
FINAL_STATUS=$(curl -sS -X GET "${BASE}/api/v2/calcom/drift-status" \
  ${AUTH:+-H "$AUTH"} \
  -H "$ACCEPT" \
  ${TENANT:+-H "$TENANT"} 2>/dev/null)

echo "$FINAL_STATUS" | jq '.data.summary' 2>/dev/null || echo "$FINAL_STATUS"

echo ""
echo "======================================================"
echo "DRIFT POLICIES:"
echo "======================================================"
echo "• WARN: Log discrepancy, continue operation"
echo "• ACCEPT: Accept Cal.com version as truth"
echo "• REJECT: Keep local version, push to Cal.com"
echo ""
echo "Mode: reset_to_local = Our system is SSOT"
echo ""

# Optional: Auto-resolve if configured
if grep -q '^FEATURE_CALCOM_V2_AUTO_RESOLVE=true' /var/www/api-gateway/.env 2>/dev/null; then
    echo "[BONUS] Auto-Resolve Enabled"
    AUTO_RESOLVE=$(curl -sS -X POST "${BASE}/api/v2/calcom/auto-resolve" \
      ${AUTH:+-H "$AUTH"} \
      -H "$JSON" -H "$ACCEPT" \
      ${TENANT:+-H "$TENANT"} \
      -d "{\"branch_id\": ${BRANCH_ID}}" 2>/dev/null)

    echo "$AUTO_RESOLVE" | jq '.data.resolved_count // 0' 2>/dev/null || echo "0"
    echo " mappings auto-resolved"
fi

echo ""
echo "======================================================"
echo "✅ DRIFT CYCLE COMPLETE"
echo "======================================================"