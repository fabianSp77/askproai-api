# 🎯 Retell.ai Conversation Flow - Erfolgreiche Bearbeitung via API

**Datum:** 2025-10-22
**Flow ID:** `conversation_flow_da76e7c6f3ba`
**Status:** ✅ Erfolgreich aktualisiert

---

## 🔑 Schlüssel zum Erfolg

### Warum Import über Dashboard NICHT funktionierte

Der "Import Agent" im Dashboard erwartet ein **anderes Format** als die API. Die Fehlermeldung "Cannot read properties of undefined (reading 'type')" war irreführend.

**Lösung:** Direkte API-Nutzung für Conversation Flow Updates!

---

## 🛠️ Erfolgreiche Vorgehensweise

### 1. Bestehenden Flow via API abrufen

```php
GET https://api.retellai.com/get-conversation-flow/{flow_id}
Authorization: Bearer {api_key}
```

### 2. Alle Validierungsfehler beheben

**Problem 1: Function Nodes ohne `instruction`**
```json
// ❌ Falsch
{
  "type": "function",
  "tool_id": "tool-xyz"
}

// ✅ Richtig
{
  "type": "function",
  "tool_id": "tool-xyz",
  "instruction": {
    "type": "static_text",
    "text": ""
  }
}
```

**Problem 2: Function Nodes ohne `tool_type`**
```json
// ✅ Erforderlich
{
  "type": "function",
  "tool_id": "tool-xyz",
  "tool_type": "local",  // "local" oder "shared"
  "wait_for_result": true
}
```

**Problem 3: Falsche Equation Transition Struktur**
```json
// ❌ Falsch (String-basiert)
{
  "type": "equation",
  "equations": ["{{customer_status}} == \"found\""]
}

// ✅ Richtig (Objekt-basiert)
{
  "type": "equation",
  "equations": [
    {
      "left": "customer_status",
      "operator": "==",
      "right": "found"
    }
  ],
  "operator": "&&"
}
```

**Problem 4: Skip Response Edge mit falschem Prompt**
```json
// ❌ Falsch
{
  "skip_response_edge": {
    "transition_condition": {
      "type": "prompt",
      "prompt": "Message delivered"  // Beliebiger Text
    }
  }
}

// ✅ Richtig (exakt dieser String!)
{
  "skip_response_edge": {
    "transition_condition": {
      "type": "prompt",
      "prompt": "Skip response"  // MUSS exakt so sein!
    }
  }
}
```

**Problem 5: Self-Loop Edges**
```json
// ❌ Nicht erlaubt
{
  "id": "node_07_datetime_collection",
  "edges": [
    {
      "id": "edge_datetime_invalid",
      "destination_node_id": "node_07_datetime_collection"  // Zeigt auf sich selbst!
    }
  ]
}

// ✅ Lösung: Edge entfernen
// Retell.ai erlaubt KEINE Self-Loops
```

### 3. Flow via API aktualisieren

```php
PATCH https://api.retellai.com/update-conversation-flow/{flow_id}
Authorization: Bearer {api_key}
Content-Type: application/json

{
  "global_prompt": "...",
  "start_node_id": "node_01_greeting",
  "start_speaker": "agent",
  "model_choice": {
    "type": "cascading",
    "model": "gpt-4o-mini"
  },
  "tools": [...],
  "nodes": [...]
}
```

---

## 📊 Finaler Flow (Version 3)

### Statistiken
- **22 Nodes** total
  - 14 Conversation Nodes
  - 4 Function Nodes
  - 3 End Nodes
  - 1 Start Node
- **3 Tools** definiert
- **Model:** gpt-4o-mini (cascading)

### Tools
1. **check_customer** (`tool-check-customer`)
   - URL: `https://api.askproai.de/api/retell/check-customer`
   - Timeout: 4000ms
   - Tool Type: local

2. **current_time_berlin** (`tool-current-time-berlin`)
   - URL: `https://api.askproai.de/api/retell/current-time-berlin`
   - Timeout: 4000ms
   - Tool Type: local

3. **collect_appointment_data** (`tool-collect-appointment-data`)
   - URL: `https://api.askproai.de/api/retell/collect-appointment-data`
   - Timeout: 8000ms
   - Tool Type: local
   - Parameter: `bestaetigung` (boolean) - false=prüfen, true=buchen

### Function Nodes
1. **func_01_current_time** → Holt aktuelle Zeit (Schritt 1)
2. **func_01_check_customer** → Prüft Kunde (Schritt 2)
3. **func_08_availability_check** → Verfügbarkeit (bestaetigung=false)
4. **func_09c_final_booking** → Finale Buchung (bestaetigung=true)

### Conversation Flow
```
node_01_greeting (Start)
  ↓
func_01_current_time (Zeit abrufen)
  ↓
func_01_check_customer (Kunde prüfen)
  ↓
node_02_customer_routing (Routing: bekannt/neu/anonym)
  ├─→ node_03a_known_customer
  ├─→ node_03b_new_customer
  └─→ node_03c_anonymous_customer
  ↓
node_04_intent_capture (Intent erfassen)
  ↓
node_06_service_selection (Dienstleistung)
  ↓
node_07_datetime_collection (Datum & Zeit)
  ↓
func_08_availability_check (Verfügbarkeit prüfen)
  ├─→ node_09a_booking_confirmation (Verfügbar)
  └─→ node_09b_alternative_offering (Nicht verfügbar)
  ↓
func_09c_final_booking (Buchen)
  ↓
node_14_success_goodbye → end_node_success
```

---

## 🔧 PHP Scripts für API-Operationen

### Conversation Flow abrufen
```bash
php get_conversation_flow.php
```

### Conversation Flow aktualisieren
```bash
php update_conversation_flow.php
```

### Alle Fixes anwenden
```bash
php fix_tool_type.php          # Fügt tool_type hinzu
php fix_equations.php           # Korrigiert equation format
php fix_skip_response.php       # Korrigiert skip_response_edge
php fix_self_loop.php          # Entfernt Self-Loops
```

---

## ✅ API Credentials

```env
RETELL_BASE=https://api.retellai.com
RETELL_TOKEN=<REDACTED_RETELL_KEY>
RETELL_AGENT_ID=agent_9a8202a740cd3120d96fcfda1e
```

**Agent URL:**
```
https://dashboard.retellai.com/agents/agent_616d645570ae613e421edb98e7
```

**Flow URL:**
```
https://dashboard.retellai.com/conversation-flow/conversation_flow_da76e7c6f3ba
```

---

## 🚨 Wichtige Erkenntnisse

1. **Dashboard Import ist kaputt** - Verwende stattdessen direkt die API
2. **Equation Format** ist kritisch - Muss Objekt-Struktur haben
3. **tool_type ist Pflicht** für Function Nodes
4. **Skip Response Prompt** muss exakt "Skip response" sein
5. **Keine Self-Loops** erlaubt

---

## 📝 Nächste Schritte

- [ ] Agent mit Flow verknüpfen (automatisch wenn gleiche ID)
- [ ] Webhook URLs testen
- [ ] Live-Test durchführen
- [ ] Performance messen (Latenz, Erfolgsquote)

---

**Status:** ✅ Produktionsbereit
**Letzte Aktualisierung:** 2025-10-22 15:30 CET
