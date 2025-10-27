#!/bin/bash
# ==========================================
# RETELL AGENT DEPLOYMENT SCRIPT
# ==========================================
# Usage: ./scripts/retell_deploy.sh [verify|deploy|full]
#
# verify: Only check current status
# deploy: Publish agent + configure phone
# full: Deploy + verify + show next steps

set -e

# Configuration
RETELL_TOKEN=$(grep "^RETELL_TOKEN=" /var/www/api-gateway/.env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
AGENT_ID="agent_f1ce85d06a84afb989dfbb16a9"
PHONE_NUMBER="+493033081738"
FLOW_ID="conversation_flow_1607b81c8f93"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
verify_status() {
    echo "=========================================="
    echo "CURRENT STATUS CHECK"
    echo "=========================================="
    echo ""

    echo "1. Agent Configuration:"
    AGENT_DATA=$(curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
      -H "Authorization: Bearer ${RETELL_TOKEN}")

    AGENT_VERSION=$(echo "$AGENT_DATA" | jq -r '.version')
    FLOW_VERSION=$(echo "$AGENT_DATA" | jq -r '.response_engine.version')
    IS_PUBLISHED=$(echo "$AGENT_DATA" | jq -r '.is_published')

    echo "   Agent ID: $AGENT_ID"
    echo "   Agent Version: V$AGENT_VERSION"
    echo "   Flow Version: V$FLOW_VERSION"
    echo "   Is Published: $IS_PUBLISHED"

    echo ""
    echo "2. Phone Configuration:"
    PHONE_DATA=$(curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
      -H "Authorization: Bearer ${RETELL_TOKEN}" | \
      jq ".[] | select(.phone_number == \"${PHONE_NUMBER}\")")

    PHONE_AGENT=$(echo "$PHONE_DATA" | jq -r '.inbound_agent_id')
    PHONE_VERSION=$(echo "$PHONE_DATA" | jq -r '.inbound_agent_version')

    echo "   Phone Number: $PHONE_NUMBER"
    echo "   Connected Agent: $PHONE_AGENT"
    echo "   Agent Version: ${PHONE_VERSION:-auto-latest}"

    echo ""
    echo "3. Verification:"

    ERRORS=0

    if [ "$PHONE_AGENT" = "$AGENT_ID" ]; then
        echo -e "   ${GREEN}✅${NC} Phone connected to correct agent"
    else
        echo -e "   ${RED}❌${NC} Phone NOT connected to agent!"
        ERRORS=$((ERRORS + 1))
    fi

    if [ "$PHONE_VERSION" = "null" ] || [ -z "$PHONE_VERSION" ]; then
        echo -e "   ${GREEN}✅${NC} Phone set to auto-latest (recommended)"
    elif [ "$PHONE_VERSION" = "$AGENT_VERSION" ]; then
        echo -e "   ${GREEN}✅${NC} Phone version matches agent"
    else
        echo -e "   ${YELLOW}⚠️${NC}  Phone version mismatch (V$PHONE_VERSION vs V$AGENT_VERSION)"
    fi

    echo ""
    echo "=========================================="
    if [ $ERRORS -eq 0 ]; then
        echo -e "${GREEN}✅ STATUS OK - READY FOR DEPLOYMENT${NC}"
    else
        echo -e "${RED}❌ ERRORS FOUND - FIX REQUIRED${NC}"
    fi
    echo "=========================================="
}

deploy_agent() {
    echo "=========================================="
    echo "DEPLOYING AGENT"
    echo "=========================================="
    echo ""

    echo "Step 1: Publishing agent..."
    PUBLISH_RESPONSE=$(curl -w "%{http_code}" -s -X POST "https://api.retellai.com/publish-agent/${AGENT_ID}" \
      -H "Authorization: Bearer ${RETELL_TOKEN}" \
      -H "Content-Type: application/json" \
      -o /tmp/publish_result.txt)

    if [ "$PUBLISH_RESPONSE" = "200" ] || [ "$PUBLISH_RESPONSE" = "201" ]; then
        echo -e "${GREEN}✅${NC} Agent published successfully (HTTP $PUBLISH_RESPONSE)"
    else
        echo -e "${RED}❌${NC} Publish failed (HTTP $PUBLISH_RESPONSE)"
        cat /tmp/publish_result.txt
        exit 1
    fi

    echo ""
    echo "Step 2: Configuring phone to use latest agent..."
    PHONE_RESPONSE=$(curl -w "%{http_code}" -s -X PATCH "https://api.retellai.com/update-phone-number/${PHONE_NUMBER}" \
      -H "Authorization: Bearer ${RETELL_TOKEN}" \
      -H "Content-Type: application/json" \
      -d "{\"inbound_agent_id\": \"${AGENT_ID}\"}" \
      -o /tmp/phone_result.txt)

    if [ "$PHONE_RESPONSE" = "200" ] || [ "$PHONE_RESPONSE" = "201" ]; then
        echo -e "${GREEN}✅${NC} Phone configured successfully (HTTP $PHONE_RESPONSE)"
    else
        echo -e "${RED}❌${NC} Phone update failed (HTTP $PHONE_RESPONSE)"
        cat /tmp/phone_result.txt
        exit 1
    fi

    echo ""
    echo "Waiting 2 seconds for changes to propagate..."
    sleep 2

    echo ""
    echo "Step 3: Verification..."
    NEW_AGENT_VERSION=$(curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
      -H "Authorization: Bearer ${RETELL_TOKEN}" | jq -r '.version')

    NEW_PHONE_VERSION=$(curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
      -H "Authorization: Bearer ${RETELL_TOKEN}" | \
      jq -r ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | .inbound_agent_version")

    echo "   New Agent Version: V$NEW_AGENT_VERSION"
    echo "   Phone Version: ${NEW_PHONE_VERSION:-auto-latest}"

    echo ""
    echo "=========================================="
    echo -e "${GREEN}✅ DEPLOYMENT COMPLETE${NC}"
    echo "=========================================="
}

show_next_steps() {
    echo ""
    echo "=========================================="
    echo "NEXT STEPS"
    echo "=========================================="
    echo ""
    echo "1. Make a test call to: $PHONE_NUMBER"
    echo ""
    echo "2. Say: 'Termin morgen 10 Uhr Herrenhaarschnitt'"
    echo ""
    echo "3. Monitor logs:"
    echo "   tail -f /var/www/api-gateway/storage/logs/laravel-\$(date +%Y-%m-%d).log | grep -E 'check_availability|book_appointment'"
    echo ""
    echo "4. Check latest call:"
    echo "   php artisan tinker --execute='\$call = \App\Models\RetellCallSession::latest()->first(); echo \"Call ID: {\$call->call_id}\nAgent: V{\$call->agent_version}\nDuration: {\$call->duration_ms}ms\n\";'"
    echo ""
    echo "5. Verify check_availability was called:"
    echo "   php artisan tinker --execute='\$call = \App\Models\RetellCallSession::latest()->first(); echo json_encode(\$call->transcript_with_tool_calls, JSON_PRETTY_PRINT);' | grep -A5 check_availability"
    echo ""
    echo "=========================================="
}

# Main
case "${1:-verify}" in
    verify)
        verify_status
        ;;
    deploy)
        deploy_agent
        ;;
    full)
        verify_status
        echo ""
        read -p "Continue with deployment? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            deploy_agent
            show_next_steps
        else
            echo "Deployment cancelled."
        fi
        ;;
    *)
        echo "Usage: $0 [verify|deploy|full]"
        echo ""
        echo "  verify: Check current agent and phone configuration"
        echo "  deploy: Publish agent and configure phone"
        echo "  full:   Verify, deploy, and show next steps"
        exit 1
        ;;
esac
