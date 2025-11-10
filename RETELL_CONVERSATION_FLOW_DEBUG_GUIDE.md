# Retell Conversation Flow - Debug Guide
## Friseur1 Agent Konfiguration überprüfen

**Datum**: 2025-11-05
**Agent**: Friseur1 Fixed V2 (parameter_mapping)
**Agent ID**: `agent_45daa54928c5768b52ba3db736`
**Problem**: book_appointment_v17 erreicht Backend nie

---

## 🎯 Ziel dieser Anleitung

Diese Anleitung zeigt wie man die **Conversation Flow Konfiguration** im Retell Dashboard inspiziert um herauszufinden warum Function Calls nicht bei unserem Backend ankommen.

---

## 📋 Schritt 1: Retell Dashboard öffnen

### 1.1 Login
1. Öffne: https://dashboard.retellai.com/
2. Login mit deinem Account
3. Stelle sicher du bist im richtigen Workspace/Organization

### 1.2 Agents Liste
- Navigation: **Dashboard** → **Agents**
- Du siehst eine Liste aller Agents

---

## 🔍 Schritt 2: Friseur1 Agent finden

### 2.1 Agent identifizieren
Suche nach dem Agent mit:
- **Name**: "Friseur1 Fixed V2 (parameter_mapping)"
- **Agent ID**: `agent_45daa54928c5768b52ba3db736`
- **Type**: Conversation Flow

### 2.2 Agent öffnen
- Klicke auf den Agent Namen
- Oder klicke auf den "Edit" Button rechts

### 2.3 Was du sehen solltest
Du siehst jetzt die Agent Configuration Page mit:
- **General Settings** (Name, Voice, etc.)
- **Response Engine**: "Conversation Flow" (NICHT "LLM")
- **Conversation Flow Editor** Button

---

## 🎨 Schritt 3: Conversation Flow Editor öffnen

### 3.1 Flow Editor öffnen
- Finde den Button: **"Edit Conversation Flow"** oder **"Conversation Flow Editor"**
- Klicke darauf
- Ein visueller Flow Editor öffnet sich

### 3.2 Was du siehst
Der Conversation Flow Editor zeigt:
- **Nodes** (Rechtecke/Kreise) die verschiedene Conversation States repräsentieren
- **Edges** (Pfeile) die Transitions zwischen Nodes zeigen
- **Start Node** (normalerweise "Begin" oder "Greeting")

### 3.3 Navigation
- **Zoom**: Mouse wheel oder Zoom Controls
- **Pan**: Click + Drag im leeren Bereich
- **Node Select**: Click auf einen Node

---

## 🎯 Schritt 4: "Termin buchen" Node finden

### 4.1 Suche nach relevanten Nodes
Basierend auf dem Test Call Transcript suchen wir nach:
- **"Termin buchen"** (Booking Node)
- **"Buchungsdaten sammeln"** (Data Collection Node)
- **"Ergebnis zeigen"** (Result Display Node)

Diese Node Namen stammen aus dem Transcript:
```json
"collected_dynamic_variables": {
  "previous_node": "Ergebnis zeigen",
  "current_node": "Termin buchen"
}
```

### 4.2 Node finden
**Methode 1: Visuell suchen**
- Scrolle durch den Flow
- Suche nach Nodes mit Namen die "buchen" oder "booking" enthalten

**Methode 2: Search Function (falls vorhanden)**
- Manche Retell Editors haben eine Search Box
- Suche nach "Termin buchen"

### 4.3 Node auswählen
- Klicke auf die "Termin buchen" Node
- Ein Side Panel oder Modal öffnet sich mit Node Details

---

## 🔧 Schritt 5: Node Configuration inspizieren

### 5.1 Node Type prüfen
Prüfe welcher Node Type "Termin buchen" ist:
- **Function Call Node** ✅ (Das wollen wir)
- **Message Node** ❌
- **Conditional Node** ❌
- **Input Collection Node** ❌

### 5.2 Function Call Configuration

Wenn es ein **Function Call Node** ist, solltest du folgendes sehen:

#### A) Function Name
```
Function: book_appointment_v17
```
✅ **Korrekt** wenn genau dieser Name verwendet wird

#### B) Function Arguments/Parameters
Prüfe ob die Parameter korrekt gemapped sind:

```json
{
  "name": "{{customer_name}}",
  "datum": "{{appointment_date}}",
  "dienstleistung": "{{service_name}}",
  "uhrzeit": "{{appointment_time}}"
}
```

**WICHTIG - Prüfe folgendes:**

1. **Variable Names**: Sind die Variable Names korrekt?
   - `{{customer_name}}` → extrahiert aus User Input
   - `{{appointment_date}}` → extrahiert aus User Input ("morgen", "heute", etc.)
   - `{{service_name}}` → "Herrenhaarschnitt"
   - `{{appointment_time}}` → "15:50", "16:00", etc.

2. **Variable Extraction**: Wie werden diese Variablen gefüllt?
   - Gibt es vorherige Nodes die diese Variablen sammeln?
   - Sind die Extraction Rules korrekt konfiguriert?

#### C) Timeout Settings
```
Timeout: 10000ms (10 seconds)
```
⚠️ **Problem Check**: Ist 10 Sekunden genug?
- Unser Backend braucht normalerweise 2-5 Sekunden
- ABER: Wenn Network langsam ist, könnte Timeout zu kurz sein

**Recommended**: 15000-20000ms (15-20 Sekunden)

#### D) Error Handling
Prüfe ob es einen **Error Handler** gibt:
- Was passiert wenn Function Call fails?
- Gibt es eine Error Transition?
- Wohin führt der Error Path?

**KRITISCH**: Wenn Error Handler zu schnell reagiert, könnte er unterbrechen bevor Backend antwortet!

---

## 🔍 Schritt 6: Function URL & Headers prüfen

### 6.1 Function Definition überprüfen
Zurück im Haupt-Dashboard (nicht Flow Editor):
- Navigation: **Dashboard** → **Functions** (oder **Custom Functions**)
- Suche nach: `book_appointment_v17`
- Klicke auf die Function

### 6.2 Function Configuration Details

#### A) API Endpoint
```
POST https://api.askproai.de/api/webhooks/retell/function
```

**Prüfe:**
- ✅ HTTPS (nicht HTTP)
- ✅ Korrekte Domain: `api.askproai.de`
- ✅ Korrekter Path: `/api/webhooks/retell/function`
- ✅ Method: POST

**Häufiger Fehler:**
- ❌ `http://` statt `https://` (wird geblockt)
- ❌ Trailing slash: `/function/` (404 Error)
- ❌ Falsche Subdomain: `www.askproai.de` oder `askproai.de`

#### B) Headers
```
Content-Type: application/json
```

**Prüfe:**
- ✅ Content-Type ist gesetzt
- ❌ Keine zusätzlichen falschen Headers (z.B. falscher Authorization Header)

#### C) Query Parameters
**Sollte LEER sein!**

Keine Query Parameters sollten konfiguriert sein.

#### D) Request Body Format
```
Payload: args only
```

**Bedeutung**: Retell sendet nur die Function Arguments, nicht den kompletten Call Context.

**Alternative Optionen:**
- `full`: Sendet kompletten Call Context (mehr Daten)
- `args only`: Sendet nur Function Arguments ✅

**Unser Backend erwartet:**
```json
{
  "name": "book_appointment_v17",
  "args": {
    "name": "...",
    "datum": "...",
    "dienstleistung": "...",
    "uhrzeit": "..."
  },
  "call": {
    "call_id": "..."
  }
}
```

---

## 🚨 Schritt 7: Häufige Probleme identifizieren

### Problem 1: Function Call wird nicht ausgeführt

**Symptome:**
- Node wird erreicht (sieht man im Transcript)
- Aber Function Call erscheint nicht in Logs
- Agent sagt sofort "Fehler bei der Buchung"

**Mögliche Ursachen:**

#### A) Function ist nicht mit Node verknüpft
- Node Type ist **nicht** "Function Call"
- Oder Function Name ist falsch

**Fix:**
- Ändere Node Type zu "Function Call"
- Wähle korrekte Function: `book_appointment_v17`

#### B) Parameter Mapping fehlt
- Function Arguments sind leer: `{}`
- Variables sind nicht gemapped

**Fix:**
- Füge Parameter Mapping hinzu
- Verknüpfe mit vorherigen Nodes die Daten sammeln

#### C) Conditional Logic blockiert
- Es gibt eine Condition VOR dem Function Call
- Condition evaluiert zu FALSE
- Function Call wird übersprungen

**Fix:**
- Prüfe Conditions
- Entferne oder korrigiere Conditional Logic

---

### Problem 2: Function Call timeout

**Symptome:**
- Function Call wird gestartet
- Nach 10 Sekunden: "Fehler bei der Buchung"
- Backend Logs zeigen: Request wurde verarbeitet, aber zu langsam

**Mögliche Ursachen:**

#### A) Timeout zu kurz
```
Timeout: 10000ms
```

**Fix:**
- Erhöhe auf 15000ms oder 20000ms
- Gibt Backend mehr Zeit zu antworten

#### B) Backend ist langsam
- Cal.com API ist langsam (3-5 Sekunden)
- DateTimeParser + DB Operations (1-2 Sekunden)
- Total: 5-8 Sekunden (knapp unter 10s, aber manchmal darüber)

**Fix:**
- Backend Optimization (außerhalb dieser Anleitung)
- ODER: Timeout erhöhen

---

### Problem 3: Function erreicht falschen Endpoint

**Symptome:**
- Function Call wird ausgeführt
- Aber Backend empfängt nichts
- Keine Logs

**Mögliche Ursachen:**

#### A) URL ist falsch
```
❌ http://api.askproai.de/...  (HTTP, nicht HTTPS)
❌ https://www.askproai.de/... (www. Subdomain)
❌ https://api.askproai.de/api/retell/function (falscher Path)
```

**Fix:**
- Korrigiere URL zu:
```
✅ https://api.askproai.de/api/webhooks/retell/function
```

#### B) DNS Problem
- Domain kann nicht aufgelöst werden
- Retell kann Server nicht erreichen

**Test:**
```bash
# Von Retell's Server aus (nicht möglich direkt)
# Aber von unserem Server:
curl -I https://api.askproai.de/api/webhooks/retell/function
```

**Fix:**
- DNS Settings überprüfen
- Firewall Rules überprüfen

#### C) Firewall blockiert Retell
- Server erlaubt nur bestimmte IPs
- Retell's IP ist nicht whitelisted

**Fix:**
- Whitelist Retell's IP Ranges
- Oder: Disable IP Whitelist (falls aktiv)

---

### Problem 4: Parameter Extraction schlägt fehl

**Symptome:**
- Function Call wird ausgeführt
- Backend empfängt Request
- Aber Parameter sind leer oder falsch: `"datum": null`

**Mögliche Ursachen:**

#### A) Variables nicht korrekt extrahiert
```json
// Erwartet:
"datum": "morgen"

// Tatsächlich:
"datum": null
```

**Fix:**
- Prüfe "Buchungsdaten sammeln" Node
- Wie wird `appointment_date` extrahiert?
- Ist Entity Recognition korrekt konfiguriert?

#### B) Variable Names stimmen nicht überein
```json
// Node verwendet:
"{{date}}"

// Function erwartet:
"{{appointment_date}}"
```

**Fix:**
- Rename Variables zu korrektem Namen
- Oder: Update Function Parameter Schema

---

## 📊 Schritt 8: Trace Debug mit Retell Logs

### 8.1 Retell Dashboard Logs öffnen
- Navigation: **Dashboard** → **Calls** (oder **Call History**)
- Suche nach Test Call: `call_7cd466e50a6e41fe3bb218b337a`
- Klicke auf Call Details

### 8.2 Call Details inspizieren

Du solltest sehen:
- **Call Transcript**: Komplettes Gespräch
- **Events Timeline**: Alle Events chronologisch
- **Function Calls**: Liste aller Function Calls
- **Errors**: Fehler die aufgetreten sind

### 8.3 Function Call Details

Suche nach `book_appointment_v17` in Events:

**Was zu prüfen ist:**

#### A) Function Call Event existiert?
```
✅ YES: Event zeigt "book_appointment_v17" wurde aufgerufen
❌ NO: Function Call wurde nie getriggered
```

Wenn **NO**: Node Configuration Problem (siehe Problem 1)

#### B) Request Details
```json
{
  "function_name": "book_appointment_v17",
  "arguments": {
    "name": "Hans Schuß",
    "datum": "morgen",
    "dienstleistung": "Herrenhaarschnitt",
    "uhrzeit": "15:50"
  }
}
```

**Prüfe:**
- ✅ Alle Parameter sind vorhanden
- ✅ `"datum": "morgen"` (nicht null, nicht leer)
- ✅ Namen sind korrekt extrahiert

Wenn Parameter **fehlen** oder **null**: Variable Extraction Problem (siehe Problem 4)

#### C) Response Status
```
Status: timeout
ODER
Status: error
ODER
Status: success
```

**Wenn "timeout":**
- Backend hat nicht innerhalb 10s geantwortet
- Siehe Problem 2

**Wenn "error":**
- Backend hat HTTP Error zurückgegeben (4xx, 5xx)
- Prüfe unsere Laravel Logs für Exceptions

**Wenn "success":**
- Backend hat geantwortet
- ABER: Agent hat trotzdem "Fehler" gesagt?
- → Response Format Problem (Backend Response stimmt nicht mit erwartetem Format überein)

#### D) Response Body (falls vorhanden)
```json
{
  "success": true,
  "data": {
    "appointment_id": 123,
    "message": "Termin erfolgreich gebucht"
  }
}
```

Prüfe ob Response korrekt formatiert ist.

---

## 🛠️ Schritt 9: Confirmation Loop Problem

### 9.1 Problem Beschreibung
**User Feedback:**
> "er immer nach einer Bestätigung fragt und noch mal bestätigt und dann durcheinander kommt"

**Evidence aus Transcript:**
```
Agent: "Möchten Sie den Herrenhaarschnitt buchen?"          (1. Bestätigung - 38s)
Agent: "Möchten Sie den Herrenhaarschnitt ... buchen, Hans?" (2. Bestätigung - 57s)
Agent: "Ich wollte nur noch einmal nachfragen, ob Sie..."    (3. Bestätigung - 73s)
User: "Ja, ja, bitte buchen."                                (Endlich - 79s)
```

### 9.2 Wo das Problem liegt

**Node**: "Buchungsdaten sammeln"

Dieser Node fragt vermutlich:
- 1x für Service Bestätigung
- 1x für alle Daten Bestätigung
- 1x extra "Sicherheitsabfrage"

### 9.3 Was zu prüfen ist

#### A) Node Transitions
- Öffne "Buchungsdaten sammeln" Node
- Prüfe Transition Logic:

```
Wenn User sagt "Ja" → Gehe zu "Termin buchen"
```

**Problem**: Loop Condition könnte sein:
```
Wenn User sagt "Ja" → Bleibe in "Buchungsdaten sammeln" (FALSCH!)
Dann frage nochmal
```

#### B) State Management
Prüfe ob Node eine **Loop Counter** Variable hat:
```
confirmation_count = 0

Wenn User sagt "Ja":
  confirmation_count += 1

  Wenn confirmation_count >= 1:
    → Gehe zu "Termin buchen"
  Sonst:
    → Frage nochmal (PROBLEM!)
```

**Fix:**
- Entferne Loop Logic
- Bei erster Bestätigung sofort zu "Termin buchen" gehen

#### C) Conditional Branches
Prüfe ob es mehrere Branches gibt:
```
Branch 1: User sagt "Ja" → Transition zu "Termin buchen"
Branch 2: User sagt "Ja, bitte" → Transition zu "Termin buchen"
Branch 3: User sagt "Ja genau" → Transition zu "Termin buchen"
...
DEFAULT: Frage nochmal (PROBLEM!)
```

Wenn User etwas sagt das NICHT exakt matched, fällt es in DEFAULT → Loop!

**Fix:**
- Füge mehr Intent Patterns hinzu
- Oder: Verwende flexible NLU statt exact match

---

## ✅ Schritt 10: Quick Fixes - Action Items

### Fix 1: Function URL verifizieren
```
✅ https://api.askproai.de/api/webhooks/retell/function
✅ POST Method
✅ Content-Type: application/json
✅ No extra headers
✅ Timeout: 15000ms (erhöhen von 10000ms)
```

### Fix 2: Parameter Mapping verifizieren
```json
{
  "name": "{{customer_name}}",
  "datum": "{{appointment_date}}",
  "dienstleistung": "{{service_name}}",
  "uhrzeit": "{{appointment_time}}"
}
```

**Prüfe:**
- Variable Names sind korrekt
- Variables werden in vorherigen Nodes extrahiert
- Entity Recognition ist aktiv

### Fix 3: Confirmation Loop entfernen
**"Buchungsdaten sammeln" Node:**
- Entferne Loop Logic
- Bei ERSTER Bestätigung → Transition zu "Termin buchen"
- Keine wiederholten Fragen

### Fix 4: Error Handling optimieren
**"Termin buchen" Node:**
- Error Handler sollte WARTEN (15-20s)
- Nicht sofort "Fehler" sagen
- Backend braucht Zeit

### Fix 5: Response Format validieren
**Backend muss zurückgeben:**
```json
{
  "success": true,
  "data": {
    "appointment_id": 123,
    "message": "Termin erfolgreich gebucht für morgen um 15:50 Uhr"
  }
}
```

**Retell erwartet dieses Format!**

Wenn Backend ein anderes Format sendet, kann Retell es nicht parsen → "Error"

---

## 🔬 Schritt 11: Advanced Debugging

### 11.1 Webhook Logs in Retell
Manche Retell Dashboards zeigen **Webhook Logs**:
- Navigation: **Integrations** → **Webhooks** → **Logs**
- Suche nach Requests zu unserem Endpoint
- Siehst du Requests?
- Welche Status Codes?

### 11.2 Network Tab (wenn verfügbar)
Falls Retell einen Network Inspector hat:
- Öffne Call Details
- Suche nach "Network" oder "Debug" Tab
- Siehst du HTTP Requests?

### 11.3 Test Function direkt
Retell hat manchmal einen **"Test Function"** Button:
- Öffne Function: `book_appointment_v17`
- Klicke "Test" oder "Try It"
- Gib Test Parameter ein:
```json
{
  "name": "Test User",
  "datum": "morgen",
  "dienstleistung": "Herrenhaarschnitt",
  "uhrzeit": "15:50"
}
```
- Klicke "Execute"
- Siehst du eine Response?

**Wenn JA:** Function URL ist korrekt, Backend ist erreichbar
**Wenn NEIN:** Network/Configuration Problem

---

## 📝 Schritt 12: Dokumentation erstellen

### 12.1 Screenshots machen
Während du den Flow inspizierst, mache Screenshots von:
1. Conversation Flow Übersicht (komplett)
2. "Termin buchen" Node Configuration
3. Function Call Details (URL, Timeout, Parameters)
4. "Buchungsdaten sammeln" Node Configuration
5. Error Handling Logic

### 12.2 Notizen erstellen
Dokumentiere folgendes:

```markdown
## Findings from Retell Dashboard Inspection

### Agent Configuration
- Agent Type: [Conversation Flow / LLM]
- Agent Version: [31]
- Last Updated: [Date]

### "Termin buchen" Node
- Node Type: [Function Call / Message / etc.]
- Function Name: [book_appointment_v17]
- Timeout: [10000ms]
- Parameter Mapping:
  - name: {{...}}
  - datum: {{...}}
  - dienstleistung: {{...}}
  - uhrzeit: {{...}}

### Function Configuration
- URL: [https://api.askproai.de/...]
- Method: [POST]
- Headers: [...]
- Timeout: [10000ms]

### Issues Found
1. [Issue description]
2. [Issue description]
3. [...]

### Recommended Fixes
1. [Fix description]
2. [Fix description]
3. [...]
```

---

## 🎯 Schritt 13: Priority Action Items

Basierend auf deinen Findings, hier ist die Priorität:

### 🔴 CRITICAL (Fix sofort)
1. **Function URL korrekt?**
   - Wenn falsch: Korrigiere sofort

2. **Timeout zu kurz?**
   - Wenn 10s: Erhöhe auf 15-20s

3. **Parameter Mapping fehlt?**
   - Wenn `datum: null`: Fixe Variable Extraction

### 🟡 HIGH (Fix bald)
4. **Confirmation Loop**
   - Entferne wiederholte Bestätigungen
   - Flow sollte smooth sein

5. **Error Handling**
   - Verbessere User-facing Error Messages
   - Nicht generisch "Es gab einen Fehler"

### 🟢 MEDIUM (Nice to have)
6. **Logging verbessern**
   - Mehr Debug Info in Retell Logs

7. **Response Format**
   - Validiere Backend Response Format

---

## 📞 Schritt 14: Test nach Änderungen

Nach jeder Änderung:

### 14.1 Save & Publish
- Klicke "Save" im Conversation Flow Editor
- Klicke "Publish" um Changes live zu schalten
- **WICHTIG**: Changes sind NICHT sofort live ohne Publish!

### 14.2 Test Call machen
1. Rufe die Nummer an
2. Sage: "Ich möchte einen Termin für morgen 15:50 Uhr buchen"
3. Beobachte:
   - Wie oft fragt Agent nach Bestätigung?
   - Funktioniert Booking?
   - Kommt Request bei Backend an?

### 14.3 Logs überprüfen
**Parallel in Terminal:**
```bash
tail -f storage/logs/laravel.log | grep -E "(🚨|book_appointment|RETELL)"
```

**Erwartete Logs:**
```
🚨 ===== RETELL FUNCTION CALL RECEIVED =====
📞 ===== RETELL WEBHOOK RECEIVED =====
🔧 Function call received from Retell
✅ parseDateTime SUCCESS
About to create appointment...
✅ Appointment created successfully
```

**Wenn diese Logs erscheinen:** ✅ **FIX ERFOLGREICH!**

---

## 📚 Zusätzliche Ressourcen

### Retell Documentation
- **Conversation Flow Guide**: https://docs.retellai.com/conversation-flow
- **Custom Functions**: https://docs.retellai.com/custom-functions
- **Debugging Guide**: https://docs.retellai.com/debugging

### Unser Backend Documentation
- **Function Handler Code**: `app/Http/Controllers/RetellFunctionCallHandler.php`
- **DateTimeParser**: `app/Services/Retell/DateTimeParser.php`
- **API Routes**: `routes/api.php` (Line 60)

---

## 🚨 Wenn nichts funktioniert

Falls alle Fixes fehlschlagen:

### Option 1: Retell Support kontaktieren
```
Support Email: support@retellai.com
Include:
- Agent ID: agent_45daa54928c5768b52ba3db736
- Call ID: call_7cd466e50a6e41fe3bb218b337a
- Problem: Function calls not reaching our backend
- Screenshots von Configuration
```

### Option 2: Neuen Agent erstellen
Manchmal ist es einfacher einen neuen Agent zu erstellen:
1. Duplicate "Friseur1" Agent
2. Benenne zu "Friseur1 V2"
3. Reconfigure von Grund auf
4. Test

### Option 3: LLM Agent statt Conversation Flow
Falls Conversation Flow zu komplex/buggy ist:
1. Erstelle einen **LLM Agent** (nicht Conversation Flow)
2. Konfiguriere mit Function Calling
3. LLM Agents sind flexibler und haben weniger Configuration Issues

---

## ✅ Summary Checklist

Gehe diese Checklist durch:

- [ ] Retell Dashboard geöffnet
- [ ] Friseur1 Agent gefunden
- [ ] Conversation Flow Editor geöffnet
- [ ] "Termin buchen" Node gefunden und inspiziert
- [ ] Node Type ist "Function Call"
- [ ] Function Name ist "book_appointment_v17"
- [ ] Parameter Mapping ist korrekt
- [ ] Function URL ist korrekt: `https://api.askproai.de/api/webhooks/retell/function`
- [ ] Timeout ist ausreichend (≥15s)
- [ ] Confirmation Loop in "Buchungsdaten sammeln" identifiziert
- [ ] Error Handling Logic überprüft
- [ ] Screenshots gemacht
- [ ] Findings dokumentiert
- [ ] Fixes implementiert
- [ ] Changes published
- [ ] Test Call durchgeführt
- [ ] Backend Logs überprüft
- [ ] Success! 🎉

---

**Nächste Schritte nach dieser Anleitung:**
1. Dokumentiere deine Findings
2. Implementiere Priority Fixes
3. Test Call durchführen
4. Falls immer noch Probleme: Manual Backend Test ausführen (Script bereits erstellt)

---

**Erstellt**: 2025-11-05 07:05 CET
**Version**: 1.0
**Autor**: Claude Code Assistant
**Status**: Ready for use
