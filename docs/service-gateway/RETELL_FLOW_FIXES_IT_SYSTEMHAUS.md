# Retell Flow Fixes - IT-Systemhaus Ticket Support Agent

**Agent**: IT-Systemhaus Ticket Support Agent (MVP)
**Inbound Number**: +493041735870
**Conversation Flow ID**: conversation_flow_b1ec58739eeb
**Datum**: 2025-12-21

---

## Kritische Fehler (5 Fixes erforderlich)

| # | Problem | Risiko | Fix |
|---|---------|--------|-----|
| 1 | Timeout 5000ms zu kurz | API-Timeout bei Last | 15000ms |
| 2 | Kein Error-Handler | Call hängt bei Fehler | Error-Node hinzufügen |
| 3 | Kein Timeout-Handler | User wartet ewig | Timeout-Node hinzufügen |
| 4 | Error-Edge fehlt | Kein Fehler-Routing | Edge zu Error-Handler |
| 5 | Korrekturschleife | "Nein" führt nirgendwo hin | Edge-Condition fixen |

---

## Fix 1: Timeout erhöhen (5000 → 15000ms)

### Retell Dashboard
1. Öffne den Agent "IT-Systemhaus Ticket Support Agent"
2. Gehe zu **Conversation Flow** → **Tools**
3. Klicke auf **finalize_ticket**
4. Ändere **Timeout** von `5000` auf `15000`
5. Speichern

### JSON (für API/Import)
```json
{
  "path": "/conversationFlow/tools/0/timeout_ms",
  "op": "replace",
  "value": 15000
}
```

---

## Fix 2: Error-Handler Node hinzufügen

### Retell Dashboard
1. Öffne den Conversation Flow Editor
2. Erstelle neuen **Conversation Node**:
   - **Name**: `IT Ticket: Fehler-Handler`
   - **ID**: `node_it_error_handler`
   - **Position**: Rechts neben `func_it_finalize_ticket` (x: 8800, y: 250)
3. **Instruction** (Prompt):
```
Entschuldige dich kurz für das technische Problem.
Sage: 'Es tut mir leid, es gab ein technisches Problem bei der Ticket-Erstellung.
Ich notiere Ihre Daten und ein Kollege wird sich bei Ihnen melden.'
Dann verabschiede dich freundlich.
```
4. **Edge** hinzufügen:
   - **Label**: `Ende`
   - **Ziel**: `node_it_end`
   - **Condition**: Prompt → "Beende das Gespräch."

### JSON (für API/Import)
```json
{
  "op": "add",
  "path": "/conversationFlow/nodes/-",
  "value": {
    "name": "IT Ticket: Fehler-Handler",
    "id": "node_it_error_handler",
    "type": "conversation",
    "display_position": {"x": 8800, "y": 250},
    "instruction": {
      "type": "prompt",
      "text": "Entschuldige dich kurz für das technische Problem. Sage: 'Es tut mir leid, es gab ein technisches Problem bei der Ticket-Erstellung. Ich notiere Ihre Daten und ein Kollege wird sich bei Ihnen melden.' Dann verabschiede dich freundlich."
    },
    "edges": [
      {
        "id": "edge-error-to-end",
        "label": "Ende",
        "transition_condition": {"type": "prompt", "prompt": "Beende das Gespräch."},
        "destination_node_id": "node_it_end"
      }
    ]
  }
}
```

---

## Fix 3: Timeout-Handler Node hinzufügen

### Retell Dashboard
1. Erstelle neuen **Conversation Node**:
   - **Name**: `IT Ticket: Timeout-Handler`
   - **ID**: `node_it_timeout_handler`
   - **Position**: Unter Error-Handler (x: 8800, y: 400)
2. **Instruction** (Prompt):
```
Sage: 'Das dauert gerade etwas länger als erwartet.
Ich habe Ihre Daten notiert und Sie erhalten in Kürze eine Bestätigung per E-Mail.'
Dann verabschiede dich freundlich.
```
3. **Edge** hinzufügen:
   - **Label**: `Ende`
   - **Ziel**: `node_it_end`
   - **Condition**: Prompt → "Beende das Gespräch."

### JSON (für API/Import)
```json
{
  "op": "add",
  "path": "/conversationFlow/nodes/-",
  "value": {
    "name": "IT Ticket: Timeout-Handler",
    "id": "node_it_timeout_handler",
    "type": "conversation",
    "display_position": {"x": 8800, "y": 400},
    "instruction": {
      "type": "prompt",
      "text": "Sage: 'Das dauert gerade etwas länger als erwartet. Ich habe Ihre Daten notiert und Sie erhalten in Kürze eine Bestätigung per E-Mail.' Dann verabschiede dich freundlich."
    },
    "edges": [
      {
        "id": "edge-timeout-to-end",
        "label": "Ende",
        "transition_condition": {"type": "prompt", "prompt": "Beende das Gespräch."},
        "destination_node_id": "node_it_end"
      }
    ]
  }
}
```

---

## Fix 4: Error-Edge zu finalize_ticket hinzufügen

### Retell Dashboard
1. Öffne Node `Finalize Ticket` (func_it_finalize_ticket)
2. Füge neue **Edge** hinzu:
   - **Label**: `Fehler`
   - **Ziel**: `node_it_error_handler`
   - **Condition**: Prompt → "Bei API-Fehler oder wenn success=false zum Fehler-Handler."

### JSON (für API/Import)
```json
{
  "op": "add",
  "path": "/conversationFlow/nodes/10/edges/-",
  "value": {
    "id": "edge-finalize-error",
    "label": "Fehler",
    "transition_condition": {
      "type": "prompt",
      "prompt": "Bei API-Fehler oder wenn success=false zum Fehler-Handler."
    },
    "destination_node_id": "node_it_error_handler"
  }
}
```

**Hinweis**: Node-Index 10 entspricht `func_it_finalize_ticket`. Bei Import prüfen!

---

## Fix 5: Korrekturschleife fixen

### Retell Dashboard
1. Öffne Node `Extract: Ticket-Bestätigung` (node_it_extract_confirm)
2. Finde die Edge mit `ticket_confirm_no`
3. Ändere die **Transition Condition**:
   - **Type**: Equation
   - **Left**: `ticket_confirm_no`
   - **Operator**: `==`
   - **Right**: `true`
4. Prüfe dass **Ziel** = `node_it_ticket_intro` ist

### JSON (für API/Import)
```json
{
  "op": "replace",
  "path": "/conversationFlow/nodes/12/edges/1",
  "value": {
    "destination_node_id": "node_it_ticket_intro",
    "id": "edge_it_confirm_no_c17bf91858",
    "label": "Korrektur",
    "transition_condition": {
      "type": "equation",
      "equations": [
        {"left": "ticket_confirm_no", "operator": "==", "right": "true"}
      ],
      "operator": "&&"
    }
  }
}
```

**Hinweis**: Node-Index 12 entspricht `node_it_extract_confirm`. Bei Import prüfen!

---

## Vollständiger Export nach Fixes

Nach Anwendung aller Fixes sollte der Flow folgende Nodes enthalten:

| Node ID | Name | Typ |
|---------|------|-----|
| node_it_ticket_intro | IT Ticket: Einstieg | conversation |
| node_it_extract_problem | Extract: problem_description | extract_dynamic_variables |
| node_it_ask_others_affected | IT Ticket: Mehrere betroffen? | conversation |
| node_it_extract_others_affected | Extract: others_affected | extract_dynamic_variables |
| node_it_ask_problem_since | IT Ticket: Seit wann? | conversation |
| node_it_extract_problem_since | Extract: problem_since | extract_dynamic_variables |
| node_it_ask_location | IT Ticket: Standort | conversation |
| node_it_extract_location | Extract: customer_location | extract_dynamic_variables |
| node_it_collect_name | IT Ticket: Name | conversation |
| node_it_extract_name_for_ticket | Extract: customer_name | extract_dynamic_variables |
| node_it_collect_contact | IT Ticket: Kontakt (optional) | conversation |
| node_it_extract_contact | Extract: customer_phone/email | extract_dynamic_variables |
| node_it_summary_confirm | IT Ticket: Zusammenfassung | conversation |
| node_it_extract_confirm | Extract: Ticket-Bestätigung | extract_dynamic_variables |
| func_it_finalize_ticket | Finalize Ticket | function |
| node_it_extract_ticket_id | Extract: ticket_id | extract_dynamic_variables |
| node_it_ticket_success | IT Ticket: Bestätigung | conversation |
| **node_it_error_handler** | **IT Ticket: Fehler-Handler** | **conversation** (NEU) |
| **node_it_timeout_handler** | **IT Ticket: Timeout-Handler** | **conversation** (NEU) |
| node_it_end | End | end |

---

## Test-Checkliste nach Fixes

### Happy Path
- [ ] Anruf → Problem beschreiben → Alle Fragen beantworten → Bestätigen → Ticket-ID erhalten

### Korrekturschleife
- [ ] Bei Zusammenfassung "Nein" sagen → Zurück zu Einstieg → Erneut durchlaufen

### Error-Handler
- [ ] API-Fehler simulieren → Freundliche Fehlermeldung → Sauberes Ende

### Timeout-Handler
- [ ] Lange API-Antwort (>15s) → Timeout-Nachricht → Sauberes Ende

---

## Backend-Konfiguration (bereits erledigt)

| Item | Status | Details |
|------|--------|---------|
| Company | ✅ | ID 1658: IT-Systemhaus Test GmbH |
| Phone | ✅ | +493041735870 → Company 1658 |
| Policy | ✅ | gateway_mode: service_desk |
| Backend-Felder | ✅ | problem_since + customer_email in ai_metadata |

---

## Ansprechpartner

- **Retell Flow**: Fabian (Dashboard-Änderungen)
- **Backend**: API Gateway Team
- **Integration**: Thomas (Visionary Data)
