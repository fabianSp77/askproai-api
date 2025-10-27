# UX Fix #1: Conversation State Persistence

## Problem

Agent vergisst bereits gesammelte Daten und fragt 3x:

```
User: "Herrenhaarschnitt für heute fünfzehn Uhr, Hans Schuster"
Agent: "Wie ist Ihr Name?" ❌ Schon gesagt!
Agent: "Welches Datum?" ❌ Schon gesagt!
Agent: "Um wie viel Uhr?" ❌ Schon gesagt!
```

## Root Cause

**Retell Conversation Flow V2 hat KEINE automatische Memory zwischen Nodes.**

Aktueller Flow:
- Intent Router → Buchungsdaten sammeln → Verfügbarkeit prüfen
- Jeder Node startet mit **leerem Context**
- Conversation Transcript ist sichtbar, aber Agent nutzt es nicht systematisch

## Solution: Dynamic Variables

Retell bietet `dynamic_variables` für State Management:

### 1. Variables definieren (Flow-Level)

```json
{
  "dynamic_variables": {
    "customer_name": "",
    "service_name": "",
    "appointment_date": "",
    "appointment_time": "",
    "data_collection_complete": "false"
  }
}
```

### 2. Variables im Node setzen

**Node: "Buchungsdaten sammeln"**

```json
{
  "instruction": {
    "type": "prompt",
    "text": "## Sammle fehlende Informationen

**Bereits bekannt:**
- Name: {{customer_name}}
- Service: {{service_name}}
- Datum: {{appointment_date}}
- Uhrzeit: {{appointment_time}}

**Deine Aufgabe:**
1. PRÜFE welche Variablen LEER sind
2. Frage NUR nach fehlenden Daten
3. AKTUALISIERE die Variablen wenn Kunde antwortet

**Beispiel:**
- Wenn customer_name LEER → Frage: 'Wie ist Ihr Name?'
- Wenn customer_name GEFÜLLT → ÜBERSPRINGE diese Frage!

**Setze Variablen:**
- Wenn Kunde Name nennt: Setze {{customer_name}} = [Name]
- Wenn Kunde Service nennt: Setze {{service_name}} = [Service]
- Wenn Kunde Datum nennt: Setze {{appointment_date}} = [Datum]
- Wenn Kunde Uhrzeit nennt: Setze {{appointment_time}} = [Uhrzeit]

**Transition:**
- Sobald ALLE 4 Variablen gefüllt → func_check_availability"
  }
}
```

### 3. Variables an Functions übergeben

**Node: "Verfügbarkeit prüfen" (Function Call)**

```json
{
  "tool_id": "tool-check-availability",
  "tool_arguments": {
    "name": "{{customer_name}}",
    "dienstleistung": "{{service_name}}",
    "datum": "{{appointment_date}}",
    "uhrzeit": "{{appointment_time}}"
  }
}
```

## Implementation Steps

### Step 1: Add Dynamic Variables to Flow

File: `friseur1_conversation_flow_v5_state_persistence.json`

```json
{
  "global_prompt": "...",
  "dynamic_variables": {
    "customer_name": "",
    "service_name": "",
    "appointment_date": "",
    "appointment_time": "",
    "booking_confirmed": "false"
  },
  "nodes": [...]
}
```

### Step 2: Update "Buchungsdaten sammeln" Node

**Before:**
```
Sammle ALLE 4 Informationen
```

**After:**
```
## Bereits bekannt (prüfe zuerst!)
- Name: {{customer_name}}
- Service: {{service_name}}
- Datum: {{appointment_date}}
- Uhrzeit: {{appointment_time}}

Frage NUR nach fehlenden Daten!
```

### Step 3: Update Transition Condition

**Before:**
```json
"prompt": "All 4 required data collected"
```

**After:**
```json
"prompt": "{{customer_name}} is not empty AND {{service_name}} is not empty AND {{appointment_date}} is not empty AND {{appointment_time}} is not empty"
```

### Step 4: Update Function Calls

**func_check_availability:**
```json
{
  "tool_arguments": {
    "name": "{{customer_name}}",
    "dienstleistung": "{{service_name}}",
    "datum": "{{appointment_date}}",
    "uhrzeit": "{{appointment_time}}"
  }
}
```

**func_book_appointment:**
```json
{
  "tool_arguments": {
    "name": "{{customer_name}}",
    "dienstleistung": "{{service_name}}",
    "datum": "{{appointment_date}}",
    "uhrzeit": "{{appointment_time}}",
    "bestaetigung": true
  }
}
```

## Expected Behavior After Fix

### Scenario 1: User provides all data upfront

```
User: "Herrenhaarschnitt für heute 15 Uhr, Hans Schuster"

Variables set:
- customer_name = "Hans Schuster"
- service_name = "Herrenhaarschnitt"
- appointment_date = "heute"
- appointment_time = "15:00"

Agent: "Einen Moment, ich prüfe die Verfügbarkeit für Herrenhaarschnitt heute um 15 Uhr..."
[Calls check_availability immediately]
```

### Scenario 2: User provides partial data

```
User: "Ich möchte einen Herrenhaarschnitt"

Variables set:
- service_name = "Herrenhaarschnitt"
- customer_name = "" (empty)
- appointment_date = "" (empty)
- appointment_time = "" (empty)

Agent: "Gerne! Wie ist Ihr Name?"
User: "Hans Schuster"

Variables updated:
- customer_name = "Hans Schuster"

Agent: "Danke Hans! Für welchen Tag möchten Sie den Termin?"
...
```

## Testing Plan

1. **Test 1: All data upfront**
   - Input: "Herrenhaarschnitt heute 15 Uhr, Hans Schuster"
   - Expected: Agent asks NO questions, proceeds to availability check

2. **Test 2: Incremental data**
   - Input: "Herrenhaarschnitt"
   - Expected: Agent asks ONLY for: name, date, time (in that order)

3. **Test 3: Out of order**
   - Input: "Heute 15 Uhr möchte ich einen Termin"
   - Expected: Agent asks ONLY for: service, name

## Deployment

1. Create V5 flow with dynamic variables
2. Test in Retell Dashboard
3. Deploy via `deploy_flow_v5_state_persistence.php`
4. Publish agent
5. Make test call
6. Verify no redundant questions

## Risk Assessment

**Low Risk** - Additive change only:
- Adding dynamic_variables (non-breaking)
- Updating prompts (improves behavior)
- Function calls remain compatible

**Rollback**: Republish V4 flow if issues

---

**Status**: READY FOR IMPLEMENTATION
**Priority**: P0 CRITICAL
**Estimated Time**: 30 minutes
