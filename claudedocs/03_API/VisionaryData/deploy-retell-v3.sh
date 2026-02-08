#!/bin/bash
# =============================================================================
# VisionaryData IT Support Agent v3.4 - Retell Deployment Script
# =============================================================================
#
# WICHTIG: {{FIRMENNAME}} im Flow-JSON vor dem Deploy durch den echten
#          Firmennamen ersetzen!
#
# Verwendung:
#   Option A: Bestehenden Conversation Flow aktualisieren
#     ./deploy-retell-v3.sh update <conversation_flow_id>
#
#   Option B: Neuen Conversation Flow + Agent erstellen
#     ./deploy-retell-v3.sh create
#
# Voraussetzung: RETELL_API_KEY als Umgebungsvariable setzen
#   export RETELL_API_KEY="key_xxxxxxxxxxxxxxxx"
#
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FLOW_JSON="$SCRIPT_DIR/retell-agent-v3.0.json"
API_BASE="https://api.retellai.com"

# Agent settings
VOICE_ID="custom_voice_8e4c6d5a408f81563a7e5c310b"
VOICE_MODEL="eleven_turbo_v2_5"
AGENT_NAME="IT-Systemhaus Ticket Support v3.4"

# --- Validation ---

if [ -z "${RETELL_API_KEY:-}" ]; then
    echo "ERROR: RETELL_API_KEY nicht gesetzt."
    echo "  export RETELL_API_KEY=\"key_xxxxxxxxxxxxxxxx\""
    exit 1
fi

if [ ! -f "$FLOW_JSON" ]; then
    echo "ERROR: Flow-JSON nicht gefunden: $FLOW_JSON"
    exit 1
fi

# Check placeholder
if grep -q '{{FIRMENNAME}}' "$FLOW_JSON"; then
    echo "WARNUNG: {{FIRMENNAME}} Platzhalter noch im JSON!"
    read -p "Firmennamen eingeben (oder Enter zum Abbrechen): " FIRMA
    if [ -z "$FIRMA" ]; then
        echo "Abgebrochen. Bitte {{FIRMENNAME}} im JSON ersetzen."
        exit 1
    fi
    echo "Ersetze {{FIRMENNAME}} durch '$FIRMA'..."
    TEMP_JSON=$(mktemp)
    sed "s/{{FIRMENNAME}}/$FIRMA/g" "$FLOW_JSON" > "$TEMP_JSON"
    FLOW_JSON="$TEMP_JSON"
    echo "OK"
fi

# --- Functions ---

verify_flow() {
    local flow_id="$1"
    echo ""
    echo "=== Verifiziere Conversation Flow: $flow_id ==="

    VERIFY_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET \
        "$API_BASE/get-conversation-flow/$flow_id" \
        -H "Authorization: Bearer $RETELL_API_KEY")

    VERIFY_CODE=$(echo "$VERIFY_RESPONSE" | tail -1)
    VERIFY_BODY=$(echo "$VERIFY_RESPONSE" | sed '$d')

    if [ "$VERIFY_CODE" -ge 200 ] && [ "$VERIFY_CODE" -lt 300 ]; then
        # Count edges on classify node
        CLASSIFY_EDGES=$(echo "$VERIFY_BODY" | python3 -c "
import sys, json
data = json.load(sys.stdin)
nodes = data.get('nodes', [])
for n in nodes:
    if n['id'] == 'node_it_classify_issue_v3':
        print(len(n.get('edges', [])))
        break
else:
    print(0)
" 2>/dev/null || echo "0")

        NODE_COUNT=$(echo "$VERIFY_BODY" | python3 -c "
import sys, json
data = json.load(sys.stdin)
print(len(data.get('nodes', [])))
" 2>/dev/null || echo "0")

        echo "  Nodes: $NODE_COUNT"
        echo "  Classify-Edges: $CLASSIFY_EDGES"

        if [ "$CLASSIFY_EDGES" -ne 7 ]; then
            echo ""
            echo "FEHLER: Classify-Node hat $CLASSIFY_EDGES statt 7 Edges!"
            echo "WARNUNG: Flow NICHT publishen! Manuell pruefen!"
            return 1
        fi

        if [ "$NODE_COUNT" -lt 25 ]; then
            echo ""
            echo "WARNUNG: Nur $NODE_COUNT Nodes gefunden (erwartet >= 27)."
            echo "Bitte manuell pruefen ob alle Nodes vorhanden sind."
        fi

        echo "  Verifikation OK: 7 Classify-Edges, $NODE_COUNT Nodes"
    else
        echo "WARNUNG: Flow-Verifikation fehlgeschlagen (HTTP $VERIFY_CODE)"
        echo "Bitte manuell pruefen!"
    fi
}

update_flow() {
    local flow_id="$1"
    echo "=== Aktualisiere Conversation Flow: $flow_id ==="

    RESPONSE=$(curl -s -w "\n%{http_code}" -X PATCH \
        "$API_BASE/update-conversation-flow/$flow_id" \
        -H "Authorization: Bearer $RETELL_API_KEY" \
        -H "Content-Type: application/json" \
        -d @"$FLOW_JSON")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
        echo "Conversation Flow aktualisiert!"
        echo "  Flow ID: $flow_id"
        echo "  Version: $(echo "$BODY" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("version","?"))' 2>/dev/null || echo '?')"

        verify_flow "$flow_id"

        echo ""
        echo "NAECHSTER SCHRITT: Im Retell Dashboard den Agent publishen (Deployment Button)."
    else
        echo "FEHLER ($HTTP_CODE):"
        echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
        exit 1
    fi
}

create_new() {
    echo "=== Erstelle neuen Conversation Flow ==="

    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
        "$API_BASE/create-conversation-flow" \
        -H "Authorization: Bearer $RETELL_API_KEY" \
        -H "Content-Type: application/json" \
        -d @"$FLOW_JSON")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
        FLOW_ID=$(echo "$BODY" | python3 -c 'import sys,json; print(json.load(sys.stdin)["conversation_flow_id"])' 2>/dev/null)
        echo "Conversation Flow erstellt!"
        echo "  Flow ID: $FLOW_ID"
    else
        echo "FEHLER beim Erstellen des Flows ($HTTP_CODE):"
        echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
        exit 1
    fi

    echo ""
    echo "=== Erstelle Agent ==="

    AGENT_JSON=$(cat <<AGENT_EOF
{
    "response_engine": {
        "type": "conversation-flow",
        "conversation_flow_id": "$FLOW_ID"
    },
    "voice_id": "$VOICE_ID",
    "agent_name": "$AGENT_NAME",
    "voice_model": "$VOICE_MODEL",
    "language": "de-DE",
    "voice_temperature": 1,
    "voice_speed": 1,
    "responsiveness": 0.9,
    "interruption_sensitivity": 0.8,
    "enable_backchannel": true,
    "backchannel_frequency": 0.5,
    "backchannel_words": ["mhm", "verstehe", "ja"],
    "end_call_after_silence_ms": 45000,
    "boosted_keywords": ["VPN", "WLAN", "DNS", "MFA", "Outlook", "Teams", "SharePoint", "Azure", "Active Directory", "Citrix", "VMware", "Firewall", "BitLocker", "OneDrive", "Exchange", "Drucker", "Scanner", "Ransomware", "Phishing", "VisionaryData"]
}
AGENT_EOF
)

    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
        "$API_BASE/create-agent" \
        -H "Authorization: Bearer $RETELL_API_KEY" \
        -H "Content-Type: application/json" \
        -d "$AGENT_JSON")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
        AGENT_ID=$(echo "$BODY" | python3 -c 'import sys,json; print(json.load(sys.stdin)["agent_id"])' 2>/dev/null)
        echo "Agent erstellt!"
        echo "  Agent ID: $AGENT_ID"
        echo "  Flow ID:  $FLOW_ID"
        echo "  Voice:    $VOICE_ID"
        echo ""
        verify_flow "$FLOW_ID"

        echo ""
        echo "FERTIG! Agent im Retell Dashboard pruefen und publishen."
        echo "Agent URL: https://dashboard.retellai.com/agents/$AGENT_ID"
    else
        echo "FEHLER beim Erstellen des Agents ($HTTP_CODE):"
        echo "$BODY" | python3 -m json.tool 2>/dev/null || echo "$BODY"
        echo ""
        echo "Der Conversation Flow wurde bereits erstellt (ID: $FLOW_ID)."
        echo "Agent manuell im Dashboard erstellen und diesen Flow zuweisen."
        exit 1
    fi
}

# --- Main ---

case "${1:-help}" in
    update)
        if [ -z "${2:-}" ]; then
            echo "Usage: $0 update <conversation_flow_id>"
            echo ""
            echo "Die conversation_flow_id findest du im Retell Dashboard"
            echo "unter dem bestehenden Agent -> Conversation Flow -> Settings"
            exit 1
        fi
        update_flow "$2"
        ;;
    create)
        create_new
        ;;
    *)
        echo "VisionaryData IT Support Agent v3.4 - Retell Deploy"
        echo ""
        echo "Usage:"
        echo "  $0 update <conversation_flow_id>   Bestehenden Flow aktualisieren"
        echo "  $0 create                           Neuen Flow + Agent erstellen"
        echo ""
        echo "Voraussetzung:"
        echo "  export RETELL_API_KEY=\"key_xxxxxxxxxxxxxxxx\""
        echo ""
        echo "WICHTIG: {{FIRMENNAME}} im JSON vorher ersetzen!"
        ;;
esac

# Cleanup temp file
if [ -n "${TEMP_JSON:-}" ] && [ -f "${TEMP_JSON:-}" ]; then
    rm -f "$TEMP_JSON"
fi
