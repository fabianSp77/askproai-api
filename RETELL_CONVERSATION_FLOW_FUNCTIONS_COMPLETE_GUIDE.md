# 🎓 RETELL CONVERSATION FLOW FUNCTIONS - KOMPLETTANLEITUNG

**Agent:** Conversation Flow Agent Friseur 1 (V39)
**Erstellt:** 2025-10-24
**Status:** Production Guide

---

## 📚 INHALTSVERZEICHNIS

1. [Das Zwei-Schritt-System verstehen](#das-zwei-schritt-system)
2. [Dashboard Navigation](#dashboard-navigation)
3. [Functions im Dashboard verwalten](#functions-verwalten)
4. [Function Nodes im Flow Canvas](#function-nodes-im-flow)
5. [V39 Tool-Validierung](#v39-validierung)
6. [Troubleshooting](#troubleshooting)

---

## 🔑 DAS ZWEI-SCHRITT-SYSTEM

### WICHTIG: Functions ≠ Function Nodes!

Retell verwendet ein **zweistufiges System**:

```
┌─────────────────────────────────────────────────┐
│  SCHRITT 1: GLOBAL FUNCTIONS (Dashboard)        │
│  ↓                                              │
│  Hier definierst du WAS die Function macht     │
│  - Name, URL, Parameters, Description          │
│  - Diese sind GLOBAL für alle Flows            │
│  - Wiederverwendbar                            │
└─────────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────┐
│  SCHRITT 2: FUNCTION NODES (Flow Canvas)        │
│  ↓                                              │
│  Hier bestimmst du WANN die Function läuft     │
│  - Platzierung im Flow                         │
│  - speak_during_execution                      │
│  - wait_for_result                             │
│  - Transition Conditions                       │
└─────────────────────────────────────────────────┘
```

### Analogie für Verständnis:

**Global Functions** = Werkzeuge in deinem Werkzeugkasten
**Function Nodes** = Wann du welches Werkzeug in einem bestimmten Projekt verwendest

---

## 🗺️ DASHBOARD NAVIGATION

### Wo finde ich was?

#### A) Global Functions verwalten:

```
1. Gehe zu: https://dashboard.retellai.com
2. Login
3. Links in der Sidebar: "Tools" oder "Functions" Menü
4. Dort siehst du ALLE verfügbaren Custom Functions
5. Hier kannst du neue Functions erstellen oder bestehende bearbeiten
```

**Alternative (falls kein "Tools" Menü):**
```
1. Öffne einen beliebigen Conversation Flow Agent
2. Klicke oben auf "Settings" oder "Global Settings"
3. Tab "Functions" oder "Custom Functions"
```

#### B) Function Nodes im Flow Canvas:

```
1. Öffne deinen Conversation Flow Agent (Friseur 1)
2. Du siehst den Flow Canvas (Graph mit Nodes und Edges)
3. Links: Spalte mit Node-Typen
   - Conversation
   - Function ← HIER!
   - Call Transfer
   - Press Digit
   - Logic Split
   - End
   - SMS
   - Extract Dynamic Variable
   - Agent Transfer
   - MCP
4. Rechts: Properties Panel (wenn Node ausgewählt)
```

---

## ⚙️ FUNCTIONS IM DASHBOARD VERWALTEN

### 1️⃣ NEUE FUNCTION HINZUFÜGEN

**Schritt-für-Schritt:**

```
1. Gehe zu Global Settings → Functions Tab
2. Klicke "+ Create Function" oder "+ New Tool"
3. Fülle folgende Felder aus:

   ┌─────────────────────────────────────────────┐
   │ Name: check_availability_v17                │
   │      (exakt mit Unterstrichen _!)           │
   ├─────────────────────────────────────────────┤
   │ Type: Custom Function                       │
   │      (aus Dropdown wählen)                  │
   ├─────────────────────────────────────────────┤
   │ Description:                                │
   │ Prüft die Verfügbarkeit für einen          │
   │ bestimmten Termin                           │
   ├─────────────────────────────────────────────┤
   │ HTTP Method: POST                           │
   ├─────────────────────────────────────────────┤
   │ URL:                                        │
   │ https://api.askproai.de/api/webhooks/retell│
   │ /function                                   │
   ├─────────────────────────────────────────────┤
   │ Timeout: 10000 ms (10 Sekunden)            │
   ├─────────────────────────────────────────────┤
   │ Parameters (JSON Schema):                   │
   │                                             │
   │ {                                           │
   │   "type": "object",                         │
   │   "properties": {                           │
   │     "datum": {                              │
   │       "type": "string",                     │
   │       "description": "Datum TT.MM.JJJJ"     │
   │     },                                      │
   │     "uhrzeit": {                            │
   │       "type": "string",                     │
   │       "description": "Uhrzeit HH:MM"        │
   │     },                                      │
   │     "dienstleistung": {                     │
   │       "type": "string",                     │
   │       "description": "Name Dienstleistung"  │
   │     }                                       │
   │   },                                        │
   │   "required": ["datum", "uhrzeit",          │
   │                "dienstleistung"]            │
   │ }                                           │
   └─────────────────────────────────────────────┘

4. Klicke "Save" oder "Create"
5. Die Function ist jetzt GLOBAL verfügbar
```

**⚠️ WICHTIGE FELDER:**

- **Name:** MUSS exakt mit Unterstrichen sein (`check_availability_v17`, NICHT `check-availability-v17`)
- **URL:** MUSS komplett sein inkl. `/function` am Ende
- **Parameters:** MUSS valides JSON Schema sein
- **Timeout:** Empfohlen 10000ms (10 Sek) für API-Calls

### 2️⃣ BESTEHENDE FUNCTION BEARBEITEN

**Schritt-für-Schritt:**

```
1. Gehe zu Global Settings → Functions Tab
2. Finde die Function in der Liste (z.B. check_availability_v17)
3. Klicke auf die Function oder auf "Edit" Icon (Stift-Symbol)
4. Bearbeite die gewünschten Felder:
   - Description aktualisieren
   - Parameters hinzufügen/ändern
   - Timeout anpassen
   - URL korrigieren
5. Klicke "Save Changes"
6. WICHTIG: Eventuell Agent neu publishen!
```

**⚠️ VORSICHT BEI:**

- **Name ändern:** Wenn du den Name änderst, musst du ALLE Function Nodes im Flow updaten!
- **Parameters ändern:** Prüfe ob der PHP Handler (RetellFunctionCallHandler.php) kompatibel ist
- **URL ändern:** Teste IMMER nach URL-Änderung!

### 3️⃣ FUNCTION LÖSCHEN

```
1. Gehe zu Global Settings → Functions Tab
2. Finde die Function
3. Klicke "Delete" oder Papierkorb-Icon
4. Bestätige Löschung

⚠️ WARNUNG: Wenn Function Nodes diese Function noch verwenden,
   wird der Flow BRECHEN! Prüfe zuerst im Flow Canvas!
```

---

## 🎨 FUNCTION NODES IM FLOW CANVAS

### 1️⃣ FUNCTION NODE HINZUFÜGEN

**Schritt-für-Schritt:**

```
1. Öffne Conversation Flow Agent (Friseur 1)
2. Im Flow Canvas: Klicke "+ Add Node" oder ziehe aus linker Spalte
3. Wähle "Function" Node-Type
4. Es erscheint eine neue Function Node im Canvas
5. Selektiere die Node (klicken)
6. Rechts öffnet sich Properties Panel:

   ┌─────────────────────────────────────────────┐
   │ FUNCTION NODE PROPERTIES                    │
   ├─────────────────────────────────────────────┤
   │ Node Name:                                  │
   │ ✏️ Verfügbarkeit prüfen                     │
   │    (Freundlicher Name für Canvas)           │
   ├─────────────────────────────────────────────┤
   │ Select Function: [Dropdown ▼]              │
   │ 📌 HIER wählst du eine EXISTIERENDE        │
   │    Global Function aus!                     │
   │                                             │
   │ Optionen:                                   │
   │ ○ initialize_call                           │
   │ ○ check_availability_v17 ← WÄHLE DIESE     │
   │ ○ book_appointment_v17                      │
   │ ○ get_appointments                          │
   │ ○ ... (alle Global Functions)              │
   ├─────────────────────────────────────────────┤
   │ Instruction:                                │
   │ [Type] Prompt / Static Text                │
   │                                             │
   │ ✏️ "Einen Moment bitte, ich prüfe die      │
   │     Verfügbarkeit..."                       │
   │                                             │
   │ (Was der Agent WÄHREND Function sagt)       │
   ├─────────────────────────────────────────────┤
   │ ☑️ Speak During Execution                   │
   │    (Agent spricht während Function läuft)   │
   ├─────────────────────────────────────────────┤
   │ ☑️ Wait for Result                          │
   │    (Warte auf Function Result vor           │
   │     Transition zum nächsten Node)           │
   └─────────────────────────────────────────────┘

7. Konfiguriere Edges (Verbindungen zu nächsten Nodes):
   - Klicke auf Node Handle (kleiner Kreis am Rand)
   - Ziehe Verbindung zu Target Node
   - Definiere Transition Condition

8. Klicke "Save" (oben rechts im Canvas)
```

**🔑 WICHTIGE EINSTELLUNGEN:**

#### Speak During Execution:
```
✅ AN (true) für:
   - check_availability_v17 (Verfügbarkeit prüfen dauert)
   - book_appointment_v17 (Buchung dauert)
   - reschedule_appointment (Verschieben dauert)
   - cancel_appointment (Stornieren dauert)

❌ AUS (false) für:
   - initialize_call (sofort fertig)
   - get_appointments (schnell)
   - get_alternatives (schnell)
```

**Grund:** User sollen nicht in Stille warten bei langen API-Calls!

#### Wait for Result:
```
✅ AN (true) für:
   - ALLE Function Nodes!

❌ AUS (false):
   - Nur wenn du Fire-and-Forget brauchst (selten)
```

**Grund:** Du brauchst das Result für Transition Conditions und nächste Nodes!

### 2️⃣ FUNCTION NODE BEARBEITEN

```
1. Klicke auf existierende Function Node im Canvas
2. Rechts öffnet sich Properties Panel
3. Ändere gewünschte Settings:
   - Node Name
   - Selected Function (VORSICHT!)
   - Instruction Text
   - Speak During Execution
   - Wait for Result
4. Ändere Edges/Transitions wenn nötig:
   - Klicke auf Edge
   - Bearbeite Transition Condition
5. Klicke "Save"
```

**⚠️ VORSICHT BEI:**

- **Selected Function ändern:** Prüfe ob neue Function kompatible Parameters hat!
- **Edges löschen:** Node braucht mindestens 1 Edge (außer End Nodes)

### 3️⃣ FUNCTION NODE LÖSCHEN

```
1. Selektiere Function Node im Canvas
2. Drücke DELETE oder Backspace
   ODER
   Rechtsklick → Delete
3. Bestätige Löschung
4. WICHTIG: Reconnecte vorherige Nodes zu neuen Targets!
```

---

## ✅ V39 TOOL-VALIDIERUNG

### Aktueller Stand V39:

**Im conversationFlow.tools Array sind 8 Tools definiert:**

| # | Tool ID | Function Name | Status | Priorität |
|---|---------|---------------|---------|-----------|
| 1 | tool-initialize-call | initialize_call | ✅ OK | P0 |
| 2 | tool-collect-appointment | collect_appointment_data | ⚠️ Legacy | P0 |
| 3 | tool-get-appointments | get_appointments | ✅ OK | P1 |
| 4 | tool-cancel-appointment | cancel_appointment | ✅ OK | P2 |
| 5 | tool-reschedule-appointment | reschedule_appointment | ✅ OK | P2 |
| 6 | tool-v17-check-availability | check_availability_v17 | ✅ OK | P0 |
| 7 | tool-v17-book-appointment | book_appointment_v17 | ✅ OK | P0 |
| 8 | tool-1761287781516 | get_alternatives | ✅ OK | P1 |

### ⚠️ LEGACY TOOL: tool-collect-appointment

**Problem:** `tool-collect-appointment` kombiniert check UND book in EINER Function!

**Warum das problematisch ist:**
```
- Benutzt "bestaetigung" Parameter um zwischen check/book zu unterscheiden
- bestaetigung=false → check availability
- bestaetigung=true → book appointment
- Macht Flow weniger lesbar
- Fehleranfällig (ein Tool, zwei Funktionen)
```

**Empfehlung:**
✅ **BENUTZE stattdessen:**
- `tool-v17-check-availability` (nur prüfen)
- `tool-v17-book-appointment` (nur buchen)

**Status im V39 Flow:**
- ✅ Beide neue Tools sind vorhanden
- ✅ Function Nodes `func_check_availability` und `func_book_appointment` verwenden die neuen Tools
- ⚠️ Legacy `tool-collect-appointment` ist noch da aber wird nicht mehr verwendet

**Action Required:**
❌ KEINE - Legacy Tool kann drin bleiben (schadet nicht)
✅ Falls du aufräumen willst: Lösche `tool-collect-appointment` und `func_08_availability_check` Node

### 🔍 WIE PRÜFST DU OB TOOLS IM DASHBOARD VORHANDEN SIND?

**Methode 1: Dashboard UI**
```
1. Gehe zu Global Settings → Functions Tab
2. Schaue ob diese 6 Functions in der Liste sind:
   - check_availability_v17
   - book_appointment_v17
   - get_appointments
   - get_alternatives
   - reschedule_appointment
   - cancel_appointment
3. (initialize_call muss NICHT dabei sein - ist hardcoded im PHP)
```

**Methode 2: Test Call**
```
1. Mache einen Test Call
2. Schaue ob Function Calls erfolgreich sind
3. Prüfe Logs in Admin Panel:
   https://api.askproai.de/admin/retell-call-sessions
4. Schaue nach Function Traces (RetellFunctionTrace Model)
```

**Methode 3: Retell API** (Advanced)
```bash
curl -X GET "https://api.retellai.com/v2/list-tools" \
  -H "Authorization: Bearer YOUR_RETELL_API_KEY"

# Erwartete Response: JSON Array mit allen Tools
# Prüfe ob die 6 Tools dabei sind
```

### 📋 V39 FUNCTION NODES MAPPING:

| Node ID | Node Name | Tool ID | Function Name | Verwendet? |
|---------|-----------|---------|---------------|-----------|
| func_00_initialize | Initialize Call | tool-initialize-call | initialize_call | ✅ JA |
| func_08_availability_check | Verfügbarkeit prüfen | tool-collect-appointment | collect_appointment_data | ⚠️ LEGACY |
| func_check_availability | Verfügbarkeit prüfen (Explicit) | tool-v17-check-availability | check_availability_v17 | ✅ JA |
| func_book_appointment | Termin buchen (Explicit) | tool-v17-book-appointment | book_appointment_v17 | ✅ JA |
| func_09c_final_booking | Termin buchen | tool-collect-appointment | collect_appointment_data | ⚠️ LEGACY |
| func_get_appointments | Termine abrufen | tool-get-appointments | get_appointments | ✅ JA |
| func_reschedule_execute | Verschieben ausführen | tool-reschedule-appointment | reschedule_appointment | ✅ JA |
| func_cancel_execute | Stornierung ausführen | tool-cancel-appointment | cancel_appointment | ✅ JA |

**✅ ZUSAMMENFASSUNG:**
- 8 Function Nodes im Flow
- 2 davon nutzen Legacy Tool (func_08, func_09c)
- 6 davon nutzen moderne dedizierte Tools
- Alle modernen Tools sind korrekt konfiguriert

---

## 🔧 TROUBLESHOOTING

### Problem 1: "Function not found" Error

**Symptome:**
```
- Agent sagt "Einen Moment bitte..." und hängt
- Im Log: 404 Error für Function
- Test Call zeigt keine Function Execution
```

**Ursache:**
Function ist im Flow definiert aber NICHT im Dashboard Global Settings!

**Lösung:**
```
1. Gehe zu Global Settings → Functions Tab
2. Prüfe ob Function in Liste ist
3. Falls NICHT: Erstelle Function neu (siehe Abschnitt "Neue Function hinzufügen")
4. Falls JA aber trotzdem Error: Prüfe Tool Name (exakte Schreibweise!)
```

### Problem 2: Function wird aufgerufen aber Agent reagiert nicht

**Symptome:**
```
- Function Call erscheint in Logs
- Agent spricht aber nicht über Result
- Flow hängt in Function Node
```

**Ursache:**
- `wait_for_result` ist OFF → Agent wartet nicht auf Result
- ODER: Keine Edge/Transition zu nächstem Node
- ODER: Transition Condition nie erfüllt

**Lösung:**
```
1. Selektiere Function Node
2. Rechts in Properties: ✅ Wait for Result AN
3. Prüfe Edges: Mind. 1 Edge zu nächstem Node
4. Prüfe Transition Condition: Muss erfüllbar sein
   Beispiel:
   ❌ FALSCH: "result.success == true" (wenn API "status": "success" returned)
   ✅ RICHTIG: "result.status == 'success'"
```

### Problem 3: Agent spricht während Function aber sollte nicht

**Symptome:**
```
- Agent sagt "Einen Moment bitte..." bei schnellen Functions
- Unnatürliche Pausen
```

**Ursache:**
`speak_during_execution` ist ON für schnelle Functions

**Lösung:**
```
1. Selektiere Function Node
2. Rechts in Properties: ❌ Speak During Execution AUS
3. Für get_appointments, get_alternatives: Speak OFF
4. Für check_availability, book_appointment: Speak ON
```

### Problem 4: Parameters kommen nicht im PHP Handler an

**Symptome:**
```
- Function wird aufgerufen
- PHP Log zeigt: "Missing parameter: datum"
- Agent sagt "Technisches Problem"
```

**Ursache:**
- Parameter Name im Dashboard ≠ Parameter Name im PHP
- ODER: Extract Dynamic Variable Node hat Parameter nicht extrahiert
- ODER: Parameter ist required im JSON Schema aber nicht im Conversation

**Lösung:**
```
1. Prüfe Parameter Names:
   Dashboard Function: "datum", "uhrzeit", "dienstleistung"
   PHP Handler: Erwartet exakt diese Namen!

2. Prüfe Extract Dynamic Variable Nodes:
   - Variablen Name MUSS exakt matchen
   - Type MUSS korrekt sein (string, boolean, etc.)

3. Prüfe Required Array:
   - Nur wirklich required Parameter markieren
   - Optional Parameter NICHT in required[]

4. Add Debugging im PHP:
   Log::info('Function params', ['params' => $parameters]);
```

### Problem 5: Tool ID Mismatch

**Symptome:**
```
- Flow im Dashboard zeigt Function Node
- Aber beim Export fehlt tool_id
- Oder tool_id ist "undefined"
```

**Ursache:**
Function wurde gelöscht aber Node noch da

**Lösung:**
```
1. Selektiere Function Node
2. Dropdown "Select Function" → Wähle neu
3. Save
4. Neu publishen
```

### Problem 6: Version Suffix wird nicht erkannt

**Symptome:**
```
- Tool Name im Dashboard: check_availability_v17
- PHP Log zeigt: "Unknown function: check_availability_v17"
```

**Ursache:**
PHP Handler strippt _v17 automatisch aber Match Case Failed

**Lösung:**
```php
// RetellFunctionCallHandler.php:190
$baseFunctionName = preg_replace('/_v\d+$/', '', $functionName);

// Prüfe ob Match Case den base name hat:
match($baseFunctionName) {
    'check_availability' => ..., // ✅ RICHTIG
    'check_availability_v17' => ..., // ❌ FALSCH
}
```

**Fix:**
- Verwende IMMER base name ohne _v17 im Match Case
- Das Versioning wird automatisch gehandelt

---

## 🎓 BEST PRACTICES

### 1. Function Naming Conventions

```
✅ EMPFOHLEN:
- snake_case mit Unterstrichen
- Beschreibender Name (check_availability, nicht ca)
- Version Suffix optional (_v17 für Breaking Changes)

❌ VERMEIDEN:
- Bindestriche (check-availability)
- camelCase (checkAvailability)
- Zu kurze Namen (ca, ba)
```

### 2. Parameters Design

```
✅ EMPFOHLEN:
- Deutsche Parameter Namen für deutschen Agent
- Klare Descriptions ("Datum im Format TT.MM.JJJJ")
- Nur WIRKLICH required Parameters als required markieren

❌ VERMEIDEN:
- Gemischte Sprachen (datum, time, serviceName)
- Unklare Descriptions ("The date")
- Zu viele required Parameters (macht Flow unflexibel)
```

### 3. speak_during_execution

```
✅ AN für:
- Lange API Calls (>2 Sekunden)
- Booking/Cancellation/Reschedule Operations
- Availability Checks

❌ AUS für:
- Schnelle Lookups (<1 Sekunde)
- initialize_call
- get_appointments (wenn gecacht)
```

### 4. Timeouts

```
Empfohlene Werte:
- Initialize: 2000ms (2 Sek)
- Check Availability: 10000ms (10 Sek)
- Book Appointment: 10000ms (10 Sek)
- Get Appointments: 6000ms (6 Sek)
- Reschedule: 10000ms (10 Sek)
- Cancel: 8000ms (8 Sek)

Regel: API Call Expected Time + 5 Sekunden Buffer
```

### 5. Error Handling

```
✅ IMMER im PHP Handler:
- Try-Catch Blöcke
- Detailliertes Error Logging
- User-Freundliche Error Messages zurückgeben

✅ IMMER im Flow:
- Separate Edges für Success und Error
- Error Nodes für User-Kommunikation
- Fallback zu Human Handoff bei kritischen Errors
```

---

## 📚 WEITERFÜHRENDE RESOURCES

### Offizielle Retell Docs:
- [Function Node Overview](https://docs.retellai.com/build/conversation-flow/function-node)
- [Custom Function Guide](https://docs.retellai.com/build/conversation-flow/custom-function)
- [Conversation Flow Overview](https://docs.retellai.com/build/conversation-flow/overview)

### Interne Docs:
- `ALLE_BENOETIGTEN_TOOLS.md` - Komplette Tool-Liste
- `tools-anleitung.html` - Web-basierte Anleitung
- `RetellFunctionCallHandler.php` - PHP Implementation

### Logs & Monitoring:
- Admin Panel: https://api.askproai.de/admin/retell-call-sessions
- Laravel Logs: `storage/logs/laravel.log`
- Retell Dashboard: https://dashboard.retellai.com

---

**Erstellt:** 2025-10-24 08:45
**Version:** 1.0
**Status:** Production Ready
**Autor:** Claude Code Assistant
