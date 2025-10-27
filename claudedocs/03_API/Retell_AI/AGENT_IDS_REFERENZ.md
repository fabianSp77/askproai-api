# Retell AI Agent IDs - Referenz

**WICHTIG: Immer die richtige Agent ID verwenden!**

## Production Agents

### ✅ Conversation Flow Agent (AKTUELL AKTIV)

```
Name:    Conversation Flow Agent
ID:      agent_616d645570ae613e421edb98e7
Type:    conversation-flow
Flow ID: conversation_flow_da76e7c6f3ba
Status:  🟢 AKTIV - Production
Zweck:   Terminbuchung mit strukturiertem Conversation Flow
```

**Das ist der RICHTIGE Agent für Flow-Updates!**

---

### ❌ Alte Agents (NICHT verwenden!)

```
Name:    Online: Assistent für Fabian Spitzer Rechtliches/V133
ID:      agent_9a8202a740cd3120d96fcfda1e
Type:    retell-llm (kein conversation-flow!)
Status:  ⚠️ ALT - Nicht für Flow-Updates verwenden
```

---

## Welchen Agent nutzen?

### Für Conversation Flow Updates:
```bash
Agent ID: agent_616d645570ae613e421edb98e7
```

### Prüfen, ob richtiger Agent:
```bash
curl -X GET "https://api.retellai.com/get-agent/agent_616d645570ae613e421edb98e7" \
  -H "Authorization: Bearer $RETELL_TOKEN"
```

Achte auf:
- `"response_engine": { "type": "conversation-flow" }` ✅
- `"conversation_flow_id": "conversation_flow_da76e7c6f3ba"` ✅

---

## Alle Scripts aktualisieren

Alle Deployment-Scripts **MÜSSEN** diese Agent ID nutzen:

```php
$AGENT_ID = 'agent_616d645570ae613e421edb98e7'; // Conversation Flow Agent
```

**Betroffene Scripts:**
- ✅ deploy_flow_master.php (korrigiert)
- deploy_v12_fixed.php
- deploy_v13_phone_fix.php
- deploy_v14_final_fix.php

---

## Quick Test

```bash
# Liste alle Agents und finde den richtigen
php -r "
\$agents = json_decode(file_get_contents('https://api.retellai.com/list-agents', false, stream_context_create([
    'http' => ['header' => 'Authorization: Bearer ' . getenv('RETELL_TOKEN')]
])), true);

foreach (\$agents as \$agent) {
    if (\$agent['agent_name'] === 'Conversation Flow Agent' &&
        \$agent['response_engine']['type'] === 'conversation-flow') {
        echo \"✅ RICHTIGER Agent gefunden:\\n\";
        echo \"   Name: \" . \$agent['agent_name'] . \"\\n\";
        echo \"   ID: \" . \$agent['agent_id'] . \"\\n\";
        echo \"   Flow: \" . \$agent['response_engine']['conversation_flow_id'] . \"\\n\";
        break;
    }
}
"
```

---

**Letzte Aktualisierung:** 2025-10-22
**Wichtigkeit:** 🔴 KRITISCH - Falsche Agent ID = Updates gehen ins Leere!
