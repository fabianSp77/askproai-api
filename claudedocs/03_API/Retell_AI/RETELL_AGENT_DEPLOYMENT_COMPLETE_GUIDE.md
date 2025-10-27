# 🎯 RETELL AGENT DEPLOYMENT - COMPLETE GUIDE
## Vollständige Anleitung für fehlerfreies Deployment

**Version**: 2025-10-24
**Author**: Claude Code ULTRATHINK
**Purpose**: Schritt-für-Schritt Anleitung für Retell Agent Updates

---

## 📋 ÜBERSICHT

Diese Anleitung deckt ALLE Schritte ab, um einen Retell Agent erfolgreich zu deployen:

1. ✅ Flow aktualisieren/erstellen
2. ✅ Agent Configuration prüfen
3. ✅ Agent publishen
4. ✅ Telefonnummer konfigurieren
5. ✅ Deployment verifizieren
6. ✅ Test durchführen

---

## ⚠️ KRITISCHE PUNKTE (HÄUFIGE FEHLER)

### Fehler #1: Agent published, aber Telefonnummer nicht aktualisiert
**Problem**: Agent V50 deployed, aber Phone nutzt noch V48
**Symptom**: Alte Version läuft weiter trotz Publish
**Fix**: Telefonnummer MUSS auf auto-latest oder neue Version gesetzt werden

### Fehler #2: Flow updated, aber Agent nicht published
**Problem**: Flow V51 existiert, aber Agent nutzt V45
**Symptom**: Neue Features nicht verfügbar
**Fix**: Agent MUSS published werden nach Flow-Änderungen

### Fehler #3: is_published = false wird ignoriert
**Problem**: Agent zeigt is_published=false, funktioniert aber
**Erklärung**: is_published ist nur für Retell Dashboard, NICHT für API calls
**Fix**: Kein Fix nötig - Phone mit auto-latest nutzt immer neueste Version

---

## 🔧 SCHRITT-FÜR-SCHRITT ANLEITUNG

### SCHRITT 1: Umgebungsvariablen setzen

```bash
RETELL_TOKEN=$(grep "^RETELL_TOKEN=" /var/www/api-gateway/.env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
AGENT_ID="agent_f1ce85d06a84afb989dfbb16a9"  # Friseur 1
PHONE_NUMBER="+493033081738"                  # Friseur 1 Testnummer
FLOW_ID="conversation_flow_1607b81c8f93"     # Friseur 1 Flow
```

---

### SCHRITT 2: Aktuelle Konfiguration prüfen

```bash
echo "=== CURRENT STATUS CHECK ==="
echo ""
echo "1. Agent Status:"
curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq '{
  agent_id,
  version,
  conversation_flow_version: .response_engine.version,
  is_published,
  last_modification_timestamp
}'

echo ""
echo "2. Phone Number Configuration:"
curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | {
    phone_number,
    inbound_agent_id,
    inbound_agent_version,
    auto_latest: (.inbound_agent_version == null)
  }"

echo ""
echo "3. Flow Version:"
curl -s -X GET "https://api.retellai.com/get-conversation-flow/${FLOW_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq '{
  conversation_flow_id,
  version,
  nodes: (.nodes | length)
}'
```

**Dokumentiere die Werte**:
- Agent Version: _____
- Flow Version: _____
- Phone Agent Version: _____ (null = auto-latest)

---

### SCHRITT 3: Flow aktualisieren (falls nötig)

**Option A**: Flow bereits in JSON-Datei
```bash
# Upload flow from local file
curl -X PATCH "https://api.retellai.com/update-conversation-flow/${FLOW_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d @/var/www/api-gateway/public/my_new_flow.json
```

**Option B**: Flow im Retell Dashboard bearbeiten
1. Gehe zu https://dashboard.retellai.com/
2. Öffne Flow Editor
3. Mache Änderungen
4. Save (aber NICHT publish!)
5. Notiere neue Flow Version

**Option C**: Flow via API erstellen/kopieren
```bash
# Get existing flow
curl -s -X GET "https://api.retellai.com/get-conversation-flow/${FLOW_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" > /tmp/current_flow.json

# Modify and upload
curl -X POST "https://api.retellai.com/create-conversation-flow" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d @/tmp/modified_flow.json
```

---

### SCHRITT 4: Agent publishen

**WICHTIG**: Publish = Agent Version wird hochgezählt + nutzt neueste Flow Version

```bash
echo "Publishing agent..."
curl -s -X POST "https://api.retellai.com/publish-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json"

# Warte 2 Sekunden
sleep 2

echo "New agent version:"
curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq '{
  version,
  conversation_flow_version: .response_engine.version
}'
```

**Erwartetes Ergebnis**:
- Agent Version: +1 (z.B. 50 → 51)
- Flow Version: Automatisch auf neueste gesetzt

---

### SCHRITT 5: Telefonnummer konfigurieren

**EMPFOHLEN**: Auto-Latest (nutzt immer neueste Version)

```bash
echo "Setting phone to auto-latest..."
curl -s -X PATCH "https://api.retellai.com/update-phone-number/${PHONE_NUMBER}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "{\"inbound_agent_id\": \"${AGENT_ID}\"}"

echo ""
echo "Verification:"
curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | {
    phone_number,
    inbound_agent_id,
    inbound_agent_version,
    auto_latest: (.inbound_agent_version == null)
  }"
```

**Erwartetes Ergebnis**:
```json
{
  "phone_number": "+493033081738",
  "inbound_agent_id": "agent_f1ce85d06a84afb989dfbb16a9",
  "inbound_agent_version": null,  // ✅ null = auto-latest!
  "auto_latest": true
}
```

**Alternative**: Fixe Version (nicht empfohlen)
```bash
curl -s -X PATCH "https://api.retellai.com/update-phone-number/${PHONE_NUMBER}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "{
    \"inbound_agent_id\": \"${AGENT_ID}\",
    \"inbound_agent_version\": 51
  }"
```

---

### SCHRITT 6: Deployment verifizieren

```bash
#!/bin/bash
echo "=========================================="
echo "DEPLOYMENT VERIFICATION"
echo "=========================================="

# Get current state
AGENT_VERSION=$(curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq -r '.version')

FLOW_VERSION=$(curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq -r '.response_engine.version')

PHONE_VERSION=$(curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq -r ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | .inbound_agent_version")

PHONE_AGENT=$(curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq -r ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | .inbound_agent_id")

echo ""
echo "Current Configuration:"
echo "  Agent ID: $AGENT_ID"
echo "  Agent Version: V$AGENT_VERSION"
echo "  Flow Version: V$FLOW_VERSION"
echo "  Phone Number: $PHONE_NUMBER"
echo "  Phone Agent ID: $PHONE_AGENT"
echo "  Phone Version: ${PHONE_VERSION:-auto-latest}"
echo ""

# Verification
ERRORS=0

if [ "$PHONE_AGENT" != "$AGENT_ID" ]; then
    echo "❌ ERROR: Phone not connected to agent!"
    echo "   Expected: $AGENT_ID"
    echo "   Actual: $PHONE_AGENT"
    ERRORS=$((ERRORS + 1))
else
    echo "✅ Phone connected to correct agent"
fi

if [ "$PHONE_VERSION" = "null" ] || [ -z "$PHONE_VERSION" ]; then
    echo "✅ Phone set to auto-latest (recommended)"
elif [ "$PHONE_VERSION" = "$AGENT_VERSION" ]; then
    echo "✅ Phone version matches agent version"
else
    echo "⚠️  WARNING: Phone version mismatch"
    echo "   Phone: V$PHONE_VERSION"
    echo "   Agent: V$AGENT_VERSION"
fi

if [ "$AGENT_VERSION" = "$FLOW_VERSION" ]; then
    echo "✅ Agent uses current flow version"
else
    echo "⚠️  Agent version != Flow version (this is normal after publish)"
    echo "   Agent: V$AGENT_VERSION"
    echo "   Flow: V$FLOW_VERSION"
fi

echo ""
echo "=========================================="
if [ $ERRORS -eq 0 ]; then
    echo "✅ DEPLOYMENT VERIFIED - READY FOR TEST"
else
    echo "❌ DEPLOYMENT HAS ERRORS - FIX REQUIRED"
fi
echo "=========================================="
```

---

### SCHRITT 7: Features verifizieren

Prüfe, dass der Flow die erwarteten Features hat:

```bash
echo "Checking flow features..."
curl -s -X GET "https://api.retellai.com/get-conversation-flow/${FLOW_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" > /tmp/current_flow.json

echo ""
echo "Function Nodes:"
jq '.nodes[] | select(.type == "function") | {id, name, tool_id}' /tmp/current_flow.json

echo ""
echo "Check for specific tools:"
HAS_CHECK=$(jq '[.nodes[] | select(.tool_id == "tool-v17-check-availability")] | length' /tmp/current_flow.json)
HAS_BOOK=$(jq '[.nodes[] | select(.tool_id == "tool-v17-book-appointment")] | length' /tmp/current_flow.json)

echo "  check_availability: $HAS_CHECK nodes"
echo "  book_appointment: $HAS_BOOK nodes"
```

---

## 🧪 TESTING

### Test Call durchführen

1. **Rufe die Testnummer an**: +493033081738

2. **Sage**: "Termin morgen 10 Uhr Herrenhaarschnitt"

3. **Erwarteter Flow**:
   ```
   AI: "Guten Tag! Wie kann ich helfen?"
   User: "Termin morgen 10 Uhr Herrenhaarschnitt"
   AI: "Und wie heißen Sie?"
   User: "Max Mustermann"
   AI: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
   [check_availability wird aufgerufen]
   AI: "Morgen um 10 Uhr ist verfügbar. Soll ich das buchen?"
   User: "Ja"
   AI: "Einen Moment bitte, ich buche den Termin..."
   [book_appointment wird aufgerufen]
   AI: "Ihr Termin ist gebucht! Sie erhalten eine Bestätigung."
   ```

4. **Call in DB prüfen**:
   ```bash
   php artisan tinker --execute="
   \$call = \App\Models\RetellCallSession::latest()->first();
   echo \"Call ID: {\$call->call_id}\n\";
   echo \"Agent Version: {\$call->agent_version}\n\";
   echo \"Duration: {\$call->duration_ms}ms\n\";
   echo \"Transcript: \" . substr(\$call->transcript, 0, 200) . \"...\n\";
   "
   ```

5. **Logs prüfen**:
   ```bash
   tail -50 /var/www/api-gateway/storage/logs/laravel-2025-10-24.log | \
     grep -E "check_availability|book_appointment|function_name"
   ```

---

## 🔍 TROUBLESHOOTING

### Problem: Agent Version erhöht sich nicht beim Publish

**Symptom**: Nach Publish ist Agent immer noch bei gleicher Version

**Ursachen**:
1. API call failed (check HTTP status)
2. Keine Änderungen vorhanden
3. Flow wurde nicht geändert

**Fix**:
```bash
# Check publish response
curl -v -X POST "https://api.retellai.com/publish-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}"

# Look for HTTP 200 and version increment
```

---

### Problem: Phone nutzt alte Version trotz Publish

**Symptom**: Call nutzt V48, aber Agent ist bei V51

**Ursache**: Phone hat fixe Version konfiguriert

**Fix**:
```bash
# Set to auto-latest
curl -X PATCH "https://api.retellai.com/update-phone-number/${PHONE_NUMBER}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "{\"inbound_agent_id\": \"${AGENT_ID}\"}"
```

---

### Problem: check_availability wird nicht aufgerufen

**Symptom**: AI sagt "Ich prüfe Verfügbarkeit" aber keine Funktion wird aufgerufen

**Ursachen**:
1. Flow hat keine Function Nodes für check_availability
2. Flow Transition erreicht Function Node nicht
3. Agent nutzt alte Flow Version

**Debug**:
```bash
# Check flow has function nodes
curl -s -X GET "https://api.retellai.com/get-conversation-flow/${FLOW_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq '.nodes[] | select(.type == "function")'

# Check agent uses correct flow version
curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq '{
  agent_version: .version,
  flow_version: .response_engine.version
}'

# Check call transcript for node transitions
php artisan tinker --execute="
\$call = \App\Models\RetellCallSession::latest()->first();
foreach (\$call->transcript_with_tool_calls as \$item) {
    if (isset(\$item['role']) && \$item['role'] === 'node_transition') {
        echo \"T+{\$item['time_sec']}s: {\$item['former_node_name']} → {\$item['new_node_name']}\n\";
    }
}
"
```

---

### Problem: is_published = false, aber Call funktioniert

**Erklärung**: `is_published` ist nur für Retell Dashboard relevant, NICHT für API calls

**Kein Fix nötig**: Solange Phone auf auto-latest oder korrekte Version gesetzt ist, funktioniert alles

---

## 📝 CHECKLISTE FÜR DEPLOYMENT

Vor Deployment:
- [ ] Flow changes dokumentiert
- [ ] Agent ID korrekt
- [ ] Phone Number korrekt
- [ ] RETELL_TOKEN gesetzt

Nach Flow Update:
- [ ] Agent published
- [ ] Agent Version erhöht
- [ ] Flow Version aktualisiert

Phone Configuration:
- [ ] Phone connected to Agent
- [ ] Phone auf auto-latest ODER korrekte Version
- [ ] Verification durchgeführt

Testing:
- [ ] Test Call durchgeführt
- [ ] check_availability aufgerufen
- [ ] book_appointment aufgerufen
- [ ] Appointment in DB erstellt

Dokumentation:
- [ ] Deployment dokumentiert
- [ ] Version Numbers notiert
- [ ] Test Results dokumentiert

---

## 🚀 QUICK REFERENCE

**Full Deployment Script**:
```bash
#!/bin/bash
RETELL_TOKEN=$(grep "^RETELL_TOKEN=" /var/www/api-gateway/.env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
AGENT_ID="agent_f1ce85d06a84afb989dfbb16a9"
PHONE_NUMBER="+493033081738"

# 1. Publish Agent
curl -s -X POST "https://api.retellai.com/publish-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}"

# 2. Set Phone to Auto-Latest
curl -s -X PATCH "https://api.retellai.com/update-phone-number/${PHONE_NUMBER}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "{\"inbound_agent_id\": \"${AGENT_ID}\"}"

# 3. Verify
sleep 2
echo "Agent Version: $(curl -s -X GET "https://api.retellai.com/get-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | jq -r '.version')"

echo "Phone Config: $(curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer ${RETELL_TOKEN}" | \
  jq ".[] | select(.phone_number == \"${PHONE_NUMBER}\") | .inbound_agent_version")"
```

---

## 📊 VERSION HISTORY

| Date | Agent Version | Flow Version | Changes |
|------|---------------|--------------|---------|
| 2025-10-24 | V51 | V51 | Added check_availability explicit function nodes |
| 2025-10-24 | V50 | V50 | Published after verification |
| 2025-10-24 | V48 | V48 | Phone configured for V48 (outdated) |
| 2025-10-24 | V45 | V45 | Original broken version without check_availability |

---

**Created**: 2025-10-24
**Last Updated**: 2025-10-24
**Maintained by**: Claude Code
**Location**: `/var/www/api-gateway/claudedocs/03_API/Retell_AI/`
