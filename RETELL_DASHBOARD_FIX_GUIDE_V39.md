# 🔧 Retell Dashboard Fix Guide: V39 Flow Canvas Edges

**Ziel:** Flow Canvas Edges hinzufügen damit check_availability aufgerufen wird
**Zeit:** ~15 Minuten
**Schwierigkeit:** Medium
**Voraussetzung:** Retell Dashboard Admin Zugang

---

## 📋 QUICK START CHECKLIST

```
□ Retell Dashboard Login
□ Agent öffnen: "Conversation Flow Agent Friseur 1"
□ Flow Canvas Editor öffnen
□ Node finden: node_03c_anonymous_customer
□ Extract Dynamic Variable Node hinzufügen (falls fehlend)
□ Function Node hinzufügen/prüfen: func_check_availability
□ Edges verbinden
□ Tool Configuration prüfen
□ Agent re-publishen
□ Test Call durchführen
```

---

## STEP 1: RETELL DASHBOARD ÖFFNEN

### 1.1 Login

```
URL: https://dashboard.retellai.com
Email: [your email]
Password: [your password]
```

### 1.2 Workspace Wählen

Nach Login:
- Oben rechts: Workspace Dropdown
- Wähle: [Dein Workspace Name]

### 1.3 Agent Öffnen

Navigation:
```
Dashboard → Agents (linkes Menü)
  ↓
Liste der Agents
  ↓
Finde: "Conversation Flow Agent Friseur 1"
  ↓
Click auf Agent Name
```

---

## STEP 2: FLOW CANVAS EDITOR ÖFFNEN

### 2.1 Agent Detail Seite

Du siehst:
```
┌─────────────────────────────────┐
│ Conversation Flow Agent Friseur│
│ Version: 40                     │
├─────────────────────────────────┤
│ [Overview] [Settings] [Logs]   │
│ [Analytics] [Flow Canvas]       │← Hier klicken!
└─────────────────────────────────┘
```

### 2.2 Flow Canvas Öffnen

Click auf "Flow Canvas" Tab

Du siehst jetzt:
```
┌─────────────────────────────────────────┐
│ [Save] [Publish] [Zoom In] [Zoom Out]  │
├─────────────────────────────────────────┤
│                                         │
│  [begin]                                │
│    ↓                                    │
│  [func_00_initialize]                   │
│    ↓                                    │
│  [node_02_customer_routing]             │
│    ↓                                    │
│  [node_03c_anonymous_customer]          │← DAS IST UNSER PROBLEM!
│                                         │
│  ... (more nodes)                       │
│                                         │
└─────────────────────────────────────────┘
```

### 2.3 Edit Mode Aktivieren

**WICHTIG:** Oben rechts sollte ein "Edit" Button sein.
- Falls sichtbar → Click drauf
- Falls nicht sichtbar → Du bist bereits im Edit Mode

---

## STEP 3: NODE FINDEN

### 3.1 node_03c_anonymous_customer Lokalisieren

**Option A: Scrollen**
- Scroll im Canvas nach unten
- Suche nach Node mit Label: "Anonymer Kunde" oder "node_03c_anonymous_customer"

**Option B: Suche verwenden**
- Oben links: Search Box
- Tippe: "anonymer kunde" oder "anonymous"
- Canvas springt zum Node

**Option C: Minimap**
- Unten rechts: kleine Übersichtskarte
- Click auf entsprechende Position

### 3.2 Node Markieren

- Click auf `node_03c_anonymous_customer`
- Node sollte jetzt highlighted/selected sein
- Rahmen wird blau/grün (je nach Theme)

---

## STEP 4: OUTGOING EDGES PRÜFEN

### 4.1 Aktuelle Edges Anschauen

Vom Node `node_03c_anonymous_customer`:
- Schau nach AUSGEHENDEN Pfeilen (arrows)
- **Frage:** Wohin führen diese Pfeile?

**Erwartung (BROKEN):**
```
[node_03c_anonymous_customer]
         │
         ↓ ???
    [Nichts oder falsches Ziel]
```

**Soll-Zustand (CORRECT):**
```
[node_03c_anonymous_customer]
         │
         ↓
[Extract Dynamic Variable Node]
         │
         ↓
[func_check_availability]
```

### 4.2 Falls Edges Fehlen

**Symptom:** Keine ausgehenden Pfeile ODER Pfeile führen direkt zu End Node

**Action:** Wir müssen Edges hinzufügen!

---

## STEP 5: EXTRACT DYNAMIC VARIABLE NODE HINZUFÜGEN

### 5.1 Prüfen ob Node Existiert

**Suche nach Node:**
- Name könnte sein:
  - "Extract Appointment Data"
  - "extract_dv"
  - "Collect Variables"

**Falls NICHT gefunden → Node erstellen:**

### 5.2 Node Erstellen (Falls Nötig)

**Click:** Rechtsclick im Canvas → "Add Node" → "Extract Dynamic Variable"

**Node Configuration:**
```
Name: extract_appointment_data
Description: Extract booking parameters from conversation

Variables to Extract:
┌─────────────┬────────┬──────────────────────────────┐
│ Variable    │ Type   │ Description                  │
├─────────────┼────────┼──────────────────────────────┤
│ datum       │ string │ Datum im Format TT.MM.JJJJ   │
│ uhrzeit     │ string │ Zeit im Format HH:MM         │
│ dienstleistung│string│ Name der Dienstleistung      │
└─────────────┴────────┴──────────────────────────────┘
```

**WICHTIG:** Variable names MÜSSEN exakt so heißen wie oben!

### 5.3 Node Positionieren

Drag & Drop:
```
[node_03c_anonymous_customer]
         │
         ↓
[extract_appointment_data] ← hier positionieren
         │
         ↓
[func_check_availability]
```

---

## STEP 6: FUNCTION NODE PRÜFEN/ERSTELLEN

### 6.1 func_check_availability Suchen

**Suche:**
- Canvas durchscannen
- Suche nach: "func_check_availability" oder "Verfügbarkeit prüfen"

**Falls gefunden:**
- ✅ Skip to Step 6.3

**Falls NICHT gefunden:**
- ❌ Wir müssen ihn erstellen!

### 6.2 Function Node Erstellen (Falls Nötig)

**Click:** Rechtsclick im Canvas → "Add Node" → "Function"

**Configuration:**
```
┌────────────────────────────────────────────────┐
│ Function Node Configuration                    │
├────────────────────────────────────────────────┤
│ Name: func_check_availability                  │
│                                                │
│ Tool Selection:                                │
│   ○ Select from registered tools              │← Wähle dies
│   • Create inline tool                        │
│                                                │
│ Tool: [Dropdown]                              │
│   → check_availability_v17                    │← Wähle dies
│   OR                                          │
│   → check_availability                        │← Falls v17 fehlt
│                                                │
│ ☑ Speak During Execution                     │← WICHTIG: AN!
│   Instruction:                                │
│   "Einen Moment bitte, ich prüfe die         │
│    Verfügbarkeit für Sie..."                 │
│                                                │
│ ☑ Wait for Result                            │← WICHTIG: AN!
│                                                │
│ Timeout: 10000 ms                             │
└────────────────────────────────────────────────┘
```

**KRITISCH:**
- ✅ speak_during_execution = ON
- ✅ wait_for_result = ON
- ✅ tool_id = check_availability oder check_availability_v17

### 6.3 Function Node Positionieren

Drag & Drop unter Extract DV Node:
```
[extract_appointment_data]
         │
         ↓
[func_check_availability] ← hier
```

---

## STEP 7: EDGES VERBINDEN

### 7.1 Edge #1: Anonymous → Extract DV

**Action:**
1. Hover über `node_03c_anonymous_customer`
2. Kleine Kreise erscheinen am Rand (connection points)
3. Click auf unteren Kreis und hold
4. Drag zu `extract_appointment_data`
5. Release
6. Edge sollte erscheinen: `─────→`

**Verify:**
```
[node_03c_anonymous_customer]
         │ ← Pfeil sichtbar?
         ↓
[extract_appointment_data]
```

### 7.2 Edge #2: Extract DV → Function Node

**Action:**
1. Hover über `extract_appointment_data`
2. Click auf unteren connection point
3. Drag zu `func_check_availability`
4. Release

**Verify:**
```
[extract_appointment_data]
         │
         ↓
[func_check_availability]
```

### 7.3 Edge #3: Function Node → Result Nodes

**Function Node braucht 2 ausgehende Edges:**

**Success Path:**
1. Hover über `func_check_availability`
2. Click auf rechten connection point
3. Drag zu einem Node der verfügbare Slots präsentiert
   - Name könnte sein: "node_present_availability" oder "show_available_times"
4. Release

**Error Path:**
1. Hover über `func_check_availability`
2. Click auf einen anderen connection point (unten oder links)
3. Drag zu einem Error Handler Node
   - Name könnte sein: "node_error_handler" oder "error_fallback"
4. Release

**Final Structure:**
```
[func_check_availability]
         ├→ (success) → [node_present_availability]
         └→ (error)   → [node_error_handler]
```

---

## STEP 8: GLOBAL TOOLS PRÜFEN

### 8.1 Tools Tab Öffnen

**Navigation:**
```
Dashboard (oben links) → Settings → Tools
OR
Agent Detail → Global Settings Tab
```

### 8.2 Prüfe ob Tool Existiert

**Suche in Liste:**
- Name: `check_availability` oder `check_availability_v17`

**Falls GEFUNDEN:**
- ✅ Prüfe Configuration (siehe 8.3)

**Falls NICHT GEFUNDEN:**
- ❌ Tool muss erstellt werden (siehe 8.4)

### 8.3 Tool Configuration Prüfen

**Click auf Tool Name → Configuration:**

**MUSS SO AUSSEHEN:**
```
┌─────────────────────────────────────────────┐
│ Tool: check_availability                    │
├─────────────────────────────────────────────┤
│ Type: HTTP Custom Tool                      │
│                                             │
│ Endpoint URL:                               │
│ https://api.askproai.de/api/webhooks/retell│
│ /function                                   │← EXAKT SO!
│                                             │
│ Method: POST                                │
│                                             │
│ Timeout: 10000 ms                           │
│                                             │
│ Parameters:                                 │
│ ┌─────────────┬────────┬──────────────┐    │
│ │ Name        │ Type   │ Required     │    │
│ ├─────────────┼────────┼──────────────┤    │
│ │ datum       │ string │ ✓ Yes        │    │
│ │ uhrzeit     │ string │ ✓ Yes        │    │
│ │ dienstleistung│string│ ✓ Yes        │    │
│ └─────────────┴────────┴──────────────┘    │
└─────────────────────────────────────────────┘
```

**Falls FALSCH:**
- Edit Tool
- Korrigiere URL
- Korrigiere Parameters
- Save

### 8.4 Tool Erstellen (Falls Fehlend)

**Click:** "Add Tool" oder "+ New Tool"

**Configuration:**
```
Name: check_availability
Description: Check availability for appointment booking

Type: HTTP Custom Tool

Endpoint: https://api.askproai.de/api/webhooks/retell/function
Method: POST
Timeout: 10000

Parameters (JSON Schema):
{
  "type": "object",
  "properties": {
    "datum": {
      "type": "string",
      "description": "Datum im Format TT.MM.JJJJ oder DD.MM.YYYY"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Zeit im Format HH:MM (24h)"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Name der gewünschten Dienstleistung"
    }
  },
  "required": ["datum", "uhrzeit", "dienstleistung"]
}
```

**Click:** "Save Tool"

---

## STEP 9: FLOW SPEICHERN & PUBLISHEN

### 9.1 Flow Speichern

**Oben rechts:** Click "Save" Button

**Verify:**
- Grüne Success Message: "Flow saved successfully"
- Falls Error: Lies Error Message und behebe

### 9.2 Flow Publishen

**WICHTIG:** Änderungen sind NICHT live bis du publishst!

**Oben rechts:** Click "Publish" Button

**Confirmation Dialog:**
```
┌────────────────────────────────────────┐
│ Publish Conversation Flow?            │
├────────────────────────────────────────┤
│ This will deploy changes to           │
│ production. All active calls will use  │
│ the new version.                       │
│                                        │
│ Version will increment: 40 → 41       │
│                                        │
│ [Cancel]         [Publish] ←  Click!  │
└────────────────────────────────────────┘
```

**Click:** "Publish"

**Wait:** ~30-60 seconds für Deployment

---

## STEP 10: VERIFICATION

### 10.1 Prüfe Version Number

Nach Publish:
```
Agent Detail Page
┌─────────────────────────────┐
│ Version: 41  ← incremented! │← VERIFY!
└─────────────────────────────┘
```

### 10.2 Prüfe Flow Visually

**Re-open Flow Canvas:**
- Verify alle Edges existieren
- Verify keine roten Error Markers

**Expected Structure:**
```
[node_03c_anonymous_customer]
         │
         ↓
[extract_appointment_data]
    datum, uhrzeit, dienstleistung
         │
         ↓
[func_check_availability]
    tool: check_availability
    speak_during: true
         ├→ success → [present_availability]
         └→ error → [error_handler]
```

---

## STEP 11: TEST CALL

### 11.1 Make Test Call

```bash
# From your phone:
Call: +493033081738
```

### 11.2 Test Scenario

**Say exactly:**
```
"Guten Tag, ich hätte gern einen Termin heute um 16 Uhr für Herrenhaarschnitt"
```

### 11.3 Expected Behavior (SUCCESS)

```
[0-2s]  Agent: "Guten Tag! ..."
[10s]   Agent: "Danke! Welche Dienstleistung möchten Sie?"
[12s]   You: "Herrenhaarschnitt"
[14s]   Agent: "Vielen Dank! Wie darf ich Sie ansprechen?"
[16s]   You: "Hans Müller"
[18s]   Agent: "Danke Hans! Lassen Sie mich prüfen..."
[20s]   Agent: *PAUSE 2-3 seconds* ← Function executing!
[23s]   Agent: "Ja, um 16 Uhr ist verfügbar! Soll ich buchen?"
         OR
         Agent: "Leider ist 16 Uhr nicht verfügbar. Wie wäre 17 Uhr?"
```

**Key Success Indicators:**
- ✅ Agent pausiert beim "prüfen"
- ✅ Agent gibt korrekte Verfügbarkeit
- ✅ Antwort matched Cal.com Realität
- ✅ Falls nicht verfügbar: Alternativen angeboten

### 11.4 Verify in Admin Panel

**URL:** https://api.askproai.de/admin/retell-call-sessions

**Find Latest Call:**
- Sort by: Created At (descending)
- Click on: Your test call

**Verify Function Traces:**
```
Function Traces Section:
┌──────────────────────┬─────────┬─────────┐
│ Function             │ Status  │ Latency │
├──────────────────────┼─────────┼─────────┤
│ initialize_call      │ success │ 250ms   │
│ check_availability   │ success │ 1200ms  │← MUST BE HERE!
│ book_appointment     │ success │ 1500ms  │
└──────────────────────┴─────────┴─────────┘
```

**If check_availability is MISSING:**
- ❌ Fix didn't work
- ❌ Go back to Step 7 (Edges)
- ❌ Verify all edges connected

---

## 🚨 TROUBLESHOOTING

### Problem: "Tool not found" Error

**Symptom:** Function Node shows red error: "Tool check_availability not found"

**Solution:**
1. Go to Global Settings → Tools
2. Verify tool exists with EXACT name
3. If name mismatch: Edit Function Node → Update tool_id
4. Re-save and re-publish

---

### Problem: Edges Won't Connect

**Symptom:** Can't drag edge from one node to another

**Solutions:**
- Try different connection points (top/bottom/left/right)
- Zoom out (maybe nodes too far apart)
- Refresh page and try again
- Check if nodes are locked (unlock in properties)

---

### Problem: Changes Not Taking Effect

**Symptom:** Test call still shows old behavior

**Solutions:**
1. Did you click "Publish"? (not just "Save")
2. Wait 60 seconds after publish
3. Clear phone call cache (hang up, wait, call again)
4. Check version number incremented
5. Verify in Flow Canvas that changes are visible

---

### Problem: Agent Still Hallucinates

**Symptom:** Agent still says "nicht verfügbar" without checking

**Root Causes:**
1. **Edges not connected properly**
   - Re-verify Step 7
   - Check BOTH edges (anonymous→extract, extract→function)

2. **Function Node wrong tool_id**
   - Check Step 6.2
   - Must match Global Tools name EXACTLY

3. **Tool URL wrong**
   - Check Step 8.3
   - Must be: https://api.askproai.de/api/webhooks/retell/function

4. **Not published**
   - Check Step 9.2
   - Version must increment

---

## ✅ SUCCESS CRITERIA

**You know it's fixed when:**

```
✅ Test call: Agent pauses during "prüfen"
✅ Admin Panel: check_availability in Function Traces
✅ Agent response: Matches Cal.com availability
✅ No hallucinations: Agent never guesses
✅ Booking works: If available, booking succeeds
✅ Alternatives offered: If not available, other times suggested
```

---

## 📚 NEXT STEPS AFTER FIX

1. **Test Multiple Scenarios:**
   - Available time → Should book
   - Unavailable time → Should offer alternatives
   - Different services → Should check each

2. **Monitor for 24h:**
   - Check Admin Panel regularly
   - Verify all calls show function traces
   - Watch for any remaining issues

3. **Document Changes:**
   - Update internal wiki
   - Note version number (V41 or higher)
   - Record what was changed

---

**Guide Erstellt:** 2025-10-24 10:35
**For:** V39 → V41 Migration
**Priority:** 🔴 P0 CRITICAL
**Duration:** ~15 minutes hands-on work
**Difficulty:** Medium (requires Dashboard access)
