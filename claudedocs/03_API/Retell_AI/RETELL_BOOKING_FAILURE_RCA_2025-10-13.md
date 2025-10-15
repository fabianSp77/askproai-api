# Root Cause Analysis: Retell AI Terminbuchung Fehler
**Datum:** 2025-10-13
**Analysiert:** Call 863 & Call 864
**Status:** 🚨 KRITISCH - Terminbuchung funktioniert nicht zuverlässig

---

## Executive Summary

**Problem:** Letzte 2 Anrufe konnten keine Termine buchen
- **Call 863 (15:05):** Agent erfand "heute 09:00" → Vergangenheit → Buchung schlug fehl
- **Call 864 (18:28):** Agent erkannte "Freitag Nachmittag" korrekt, aber Duplicate Handling

**Root Causes:**
1. ❌ Agent hat KEINE `{{current_weekday}}` / `{{current_date}}` Variablen
2. ❌ Agent erfindet willkürlich Datum/Zeit wenn User keine angibt
3. ❌ Keine Validation für Termine in der Vergangenheit
4. ⚠️ `current_time_berlin` Function nicht implementiert (aber wurde in Call 864 aufgerufen)

---

## Detaillierte Analyse

### Call 863: Totaler Datums-/Zeit-Failure

#### Transcript
```
User: "Ja, guten Tag. Ich hätte gern Termin gebucht für eine Beratung."
Agent: "Ich prüfe den Terminwunsch"
Agent: "Der Termin am heute um 9 Uhr ist leider nicht verfügbar."
```

#### Function Call
```json
{
  "name": "collect_appointment_data",
  "arguments": {
    "datum": "heute",          ← User gab KEIN Datum an!
    "uhrzeit": "09:00",        ← User gab KEINE Zeit an!
    "name": "Unbekannt",
    "dienstleistung": "Beratung"
  }
}
```

#### Problem-Analyse

**1. Agent halluziniert Datum/Zeit**
- User sagte NUR: "Termin für eine Beratung"
- Agent erfand: "heute 09:00"
- Zeit: 15:05 Uhr → "09:00" ist bereits 6 Stunden in der Vergangenheit!

**2. Prompt-Verstoß**
Laut Prompt V81, Zeile 83-84:
```
Fehlende Infos? Frage KOMBINIERT:
"Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"
```

Agent hätte nachfragen MÜSSEN, tat es aber nicht!

**3. System akzeptierte Vergangenheitstermin**
- System prüfte "heute 09:00" (15:05 war aktuell)
- Slot war "unavailable" (richtig, da vergangen)
- System bot Alternativen: 17:30, 18:00
- User wählte 17:30
- **Buchung schlug fehl**: "Es ist ein unerwarteter Fehler aufgetreten"

**4. Keine Past-Time-Validation**
```bash
grep -r "isPast\|past time\|Vergangenheit" app/Services/Retell/
# → Keine Treffer!
```

System hat KEINE Validation für Termine in der Vergangenheit.

---

### Call 864: Besseres Verhalten, aber Duplicate-Issue

#### Transcript
```
User: "Ja, ich möchte einen Termin diese Woche Freitag am Nachmittag."
Agent: "Ich prüfe den Terminwunsch"
```

#### Function Calls
```json
[
  {
    "name": "current_time_berlin",
    "arguments": {},
    "result": {
      "date": "13.10.2025",
      "time": "20:28",
      "weekday": "Montag",
      "iso_date": "2025-10-13",
      "week_number": "42"
    }
  },
  {
    "name": "collect_appointment_data",
    "arguments": {
      "datum": "2025-10-17",      ← Korrekt: Freitag 17.10.
      "uhrzeit": "14:00",         ← Vernünftig: "Nachmittag"
      "dienstleistung": "Beratung"
    },
    "result": {
      "status": "duplicate_detected",
      "message": "Sie haben bereits einen Termin am 17.10.2025 um 14:00 Uhr"
    }
  }
]
```

#### Analyse

**✅ Positiv:**
1. Agent rief `current_time_berlin` auf → bekam aktuelles Datum
2. Agent berechnete "diese Woche Freitag" korrekt → 17.10.2025
3. Agent interpretierte "Nachmittag" als 14:00 Uhr

**⚠️ Problem:**
1. Duplicate detected - aber DB zeigt `appointment_made: true`
2. Inkonsistenz zwischen Function Response und DB-Status

**❓ Mysteriös:**
`current_time_berlin` Function ist **NICHT** im Code implementiert!
```bash
grep -r "current_time_berlin" app/ --include="*.php"
# → Keine Treffer!
```

Aber Call 864 hat diese Function aufgerufen und Response erhalten. Möglichkeiten:
- Function wurde vor kurzem entfernt
- Function ist Retell-seitig definiert (unwahrscheinlich, wegen spezifischer Response)
- Function ist in einem anderen Modul implementiert

---

## Root Causes

### RC1: Fehlende Dynamic Variables in Retell Agent
**Schweregrad:** 🔴 KRITISCH

**Problem:**
```json
// AKTUELL in Calls:
"llm_dynamic_variables": {
  "twilio-accountsid": "...",
  "twilio-callsid": "..."
}

// FEHLEN:
"current_weekday": "Montag",
"current_date": "13.10.2025"
```

**Impact:**
- Agent kennt aktuelles Datum nicht
- Prompt referenziert `{{current_weekday}}` und `{{current_date}}`, aber Variablen werden nicht gesetzt
- Agent kann relative Berechnungen nicht durchführen

**Prompt V81, Zeile 42:**
```
Heute ist {{current_weekday}}, der {{current_date}}.
```

Diese Variablen werden **NICHT** beim Call-Start gesetzt!

---

### RC2: Agent erfindet Datum/Zeit statt nachzufragen
**Schweregrad:** 🔴 KRITISCH

**Problem:**
Agent halluziniert Datum/Zeit wenn User keine angibt, statt nachzufragen.

**Call 863 Beispiel:**
- User: "Termin für Beratung" (KEINE Zeit/Datum)
- Agent sollte: "Für welchen Tag und welche Uhrzeit?"
- Agent tat: Erfand "heute 09:00"

**Prompt-Verstoß:**
Prompt V81, Zeilen 82-84:
```
Fehlende Infos? Frage KOMBINIERT:
"Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"
```

**Mögliche Ursachen:**
1. Prompt-Anweisung nicht klar genug
2. LLM ignoriert Anweisung wegen fehlender Variablen
3. Agent versucht, "smart" zu sein und Kontext zu erschließen

---

### RC3: Keine Past-Time-Validation
**Schweregrad:** 🟡 HOCH

**Problem:**
System akzeptiert Termine in der Vergangenheit ohne Warnung.

**Code-Analyse:**
```bash
grep -r "isPast\|past time\|Vergangenheit" app/Services/Retell/AppointmentCreationService.php
# → Keine Treffer!
```

**Impact:**
- Call 863: "heute 09:00" um 15:05 → 6 Stunden vergangen
- System prüfte Slot, fand "unavailable" (richtig)
- Aber KEIN spezifischer Fehler "Termin liegt in der Vergangenheit"
- User bekam generische "nicht verfügbar" Nachricht

**Expected Behavior:**
```php
if ($appointmentDate->isPast()) {
    return [
        'success' => false,
        'status' => 'past_time',
        'message' => 'Dieser Termin liegt in der Vergangenheit. Bitte wählen Sie einen zukünftigen Zeitpunkt.'
    ];
}
```

---

### RC4: `current_time_berlin` Function nicht implementiert
**Schweregrad:** 🟡 MITTEL

**Problem:**
Call 864 rief `current_time_berlin` auf und bekam Response, aber Function existiert nicht im Code.

**Code-Analyse:**
```bash
grep -r "current_time_berlin" app/ --include="*.php"
# → 0 Treffer

# RetellFunctionCallHandler.php match() statement:
match($functionName) {
    'check_availability' => ...,
    'book_appointment' => ...,
    // KEIN 'current_time_berlin' Case!
    default => handleUnknownFunction()
}
```

**Aber Call 864 bekam Response:**
```json
{
  "successful": true,
  "content": {
    "date": "13.10.2025",
    "time": "20:28",
    "weekday": "Montag",
    "iso_date": "2025-10-13",
    "week_number": "42"
  }
}
```

**Hypothesen:**
1. Function wurde kürzlich entfernt (Git History prüfen)
2. Function ist Retell-seitig definiert (unwahrscheinlich)
3. Function ist in einem anderen Modul (z.B. RetellWebhookController)

**Impact:**
- Agent V81 Prompt sagt: "NIEMALS getCurrentDateTimeInfo() aufrufen!"
- Aber Agent rief `current_time_berlin` in Call 864 auf
- Inkonsistenz zwischen Prompt-Anweisung und Agent-Verhalten

---

### RC5: Prompt-Instruktion nicht befolgt
**Schweregrad:** 🟡 MITTEL

**Problem:**
Prompt V81 sagt explizit:

```
Zeile 44: ⚠️ NIEMALS getCurrentDateTimeInfo() aufrufen! Du rechnest selbst!
```

Aber:
- Call 863: Agent rechnete NICHT selbst, erfand willkürlich "09:00"
- Call 864: Agent rief `current_time_berlin` auf (ähnlich zu getCurrentDateTimeInfo)

**Root Cause:**
Prompt verlässt sich auf Variablen `{{current_weekday}}` und `{{current_date}}`, die nicht gesetzt werden.

---

## Empfehlungen

### 🔴 PRIORITY 1: Dynamic Variables setzen

**Action:**
Retell Agent Konfiguration muss `llm_dynamic_variables` beim Call-Start setzen:

```json
{
  "llm_dynamic_variables": {
    "current_weekday": "Montag",
    "current_date": "13.10.2025",
    "current_time": "15:05",
    "twilio-accountsid": "...",
    "twilio-callsid": "..."
  }
}
```

**Implementation:**
1. Prüfe Retell Agent Konfiguration (Dashboard oder API)
2. Füge Dynamic Variables zum Agent hinzu
3. Variables müssen beim Call-Start berechnet werden (Berlin Timezone)

**Expected Outcome:**
- Agent kennt aktuelles Datum
- Prompt `{{current_weekday}}` und `{{current_date}}` werden ersetzt
- Agent kann relative Berechnungen durchführen

---

### 🔴 PRIORITY 2: Past-Time-Validation implementieren

**Action:**
Füge Validation in `AppointmentCreationService` oder `RetellFunctionCallHandler` hinzu:

```php
// In collectAppointment() NACH parseDateTime():
if ($appointmentDate->isPast()) {
    Log::warning('🚨 Attempt to book appointment in the past', [
        'requested_time' => $appointmentDate->format('Y-m-d H:i'),
        'current_time' => Carbon::now('Europe/Berlin')->format('Y-m-d H:i'),
        'call_id' => $callId
    ]);

    return $this->responseFormatter->error(
        'Dieser Termin liegt in der Vergangenheit. Bitte wählen Sie einen zukünftigen Zeitpunkt.',
        ['status' => 'past_time']
    );
}
```

**Expected Outcome:**
- System lehnt Vergangenheitstermine ab
- User bekommt klare Fehlermeldung
- Agent fragt nach zukünftigem Zeitpunkt

---

### 🟡 PRIORITY 3: Prompt V81 verbessern

**Action:**
Verschärfe Anweisungen für fehlende Datum/Zeit:

```markdown
**ABSOLUT VERBOTEN:**
❌ Datum/Zeit erfinden wenn User keine angibt
❌ "heute" oder "09:00" als Default verwenden
❌ collect_appointment_data aufrufen OHNE vollständiges Datum+Zeit

**STATTDESSEN:**
✅ SOFORT zurückfragen: "Für welchen Tag und welche Uhrzeit?"
✅ Warten auf User-Antwort
✅ Erst dann collect_appointment_data aufrufen
```

**Alternative:** Prüfung auf Server-Seite:

```php
// In collectAppointment():
if (empty($datum) || empty($uhrzeit)) {
    return $this->responseFormatter->error(
        'Bitte fragen Sie nach Datum und Uhrzeit bevor Sie einen Termin prüfen.',
        [
            'status' => 'missing_required_fields',
            'missing_fields' => [
                'datum' => empty($datum),
                'uhrzeit' => empty($uhrzeit)
            ]
        ]
    );
}
```

---

### 🟡 PRIORITY 4: `current_time_berlin` Function implementieren

**Action:**
Implementiere fehlende Function im `RetellFunctionCallHandler`:

```php
// Add to match() statement in handleFunctionCall():
match($functionName) {
    'current_time_berlin' => $this->getCurrentTimeBerlin(),
    'check_customer' => $this->checkCustomer($parameters, $callId),
    // ... existing cases
}

// New method:
private function getCurrentTimeBerlin(): JsonResponse
{
    $now = Carbon::now('Europe/Berlin');

    return response()->json([
        'success' => true,
        'data' => [
            'date' => $now->format('d.m.Y'),
            'time' => $now->format('H:i'),
            'weekday' => $now->locale('de')->dayName,
            'iso_date' => $now->format('Y-m-d'),
            'week_number' => $now->weekOfYear
        ]
    ]);
}
```

**Expected Outcome:**
- Agent kann aktuelles Datum jederzeit abrufen
- Backup für fehlende Dynamic Variables
- Konsistent mit Call 864 Verhalten

---

### 🟢 PRIORITY 5: Monitoring & Alerting

**Action:**
Füge Monitoring für Datum/Zeit-Probleme hinzu:

```php
// Log warning wenn Agent keine Zeit/Datum angibt:
if ($datum === null || $uhrzeit === null) {
    Log::warning('⚠️ PROMPT-VIOLATION: Agent called collect_appointment without date/time', [
        'call_id' => $callId,
        'datum' => $datum,
        'uhrzeit' => $uhrzeit,
        'transcript_last_100_chars' => substr($transcript, -100)
    ]);
}

// Log critical wenn Vergangenheitstermin:
if ($appointmentDate->isPast()) {
    Log::critical('🚨 PAST-TIME-BOOKING-ATTEMPT', [
        'call_id' => $callId,
        'requested' => $appointmentDate->format('Y-m-d H:i'),
        'diff_hours' => $appointmentDate->diffInHours(Carbon::now(), false)
    ]);
}
```

**Dashboard Metrics:**
- Count of calls without date/time
- Count of past-time booking attempts
- Average time from call start to first `collect_appointment_data`

---

## Testing Plan

### Test Case 1: Agent fragt nach Datum/Zeit
```
User: "Ich möchte einen Termin buchen."
Expected:
Agent: "Gerne! Für welchen Tag und welche Uhrzeit?"

User: "Freitag um 14 Uhr"
Agent: [prüft Verfügbarkeit]
```

### Test Case 2: Past-Time-Rejection
```
User: "Heute um 9 Uhr"
Current Time: 15:00
Expected:
Agent: "Dieser Termin liegt in der Vergangenheit. Welchen zukünftigen Zeitpunkt möchten Sie?"
```

### Test Case 3: Dynamic Variables funktionieren
```
Retell Agent startet Call mit:
{
  "current_weekday": "Montag",
  "current_date": "13.10.2025"
}

User: "Morgen um 10 Uhr"
Expected:
Agent berechnet: 14.10.2025 (Dienstag) 10:00
```

### Test Case 4: current_time_berlin Function
```
Agent ruft current_time_berlin auf
Expected Response:
{
  "success": true,
  "data": {
    "date": "13.10.2025",
    "time": "15:05",
    "weekday": "Montag",
    "iso_date": "2025-10-13",
    "week_number": "42"
  }
}
```

---

## Deployment Plan

### Phase 1: Quick Fixes (2-4 Stunden)
1. ✅ Past-Time-Validation implementieren
2. ✅ `current_time_berlin` Function implementieren
3. ✅ Server-side Validation für fehlende date/time
4. ✅ Monitoring Logs hinzufügen

### Phase 2: Retell Agent Configuration (1-2 Stunden)
1. 🔧 Dynamic Variables in Retell Dashboard setzen
2. 🧪 Test mit neuem Call
3. ✅ Verify Variables werden korrekt gesetzt

### Phase 3: Prompt Improvements (2-3 Stunden)
1. ✏️ Prompt V82 erstellen mit verschärften Anweisungen
2. 🧪 A/B Test mit V81 vs V82
3. 📊 Metrics vergleichen

### Phase 4: Verification (1-2 Tage)
1. 📞 10+ Test Calls durchführen
2. 📊 Metrics analysieren
3. ✅ Success Rate >95%

---

## Success Metrics

**Ziel:**
- **Booking Success Rate:** >95% (aktuell: 50% basierend auf letzten 2 Calls)
- **Date/Time Hallucination Rate:** 0% (aktuell: 50%)
- **Past-Time-Booking-Attempts:** 0 (mit klarem Fehler)
- **Prompt Compliance Rate:** 100% (fragt nach bei fehlenden Infos)

**Monitoring:**
- Count of calls per day
- Success rate per hour
- Average time to first collect_appointment_data
- Rate of calls with missing date/time parameters

---

## Anhang: Call Details

### Call 863 Full Details
```json
{
  "id": 863,
  "retell_call_id": "call_f6038c53e0df72d2cb05bc11bee",
  "customer_name": "Unbekannt",
  "call_successful": false,
  "appointment_made": false,
  "session_outcome": "appointment_booked",
  "created_at": "2025-10-13T15:05:23",
  "transcript": "Agent: Guten Tag! Möchten Sie einen Termin buchen oder haben Sie eine Frage?\nUser: Ja, guten Tag. Ich hätte gern Termin gebucht für eine Beratung.\nAgent: Ich prüfe den TerminwunschDer Termin am heute um 9 Uhr ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: heute um 17:30 Uhr oder heute um 18:00 Uhr. Welcher Termin würde Ihnen besser passen?\nUser: Ja, siebzehn Uhr dreißig ist perfekt.\nAgent: Ich buche den TerminEs ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es später erneut.",
  "function_calls": [
    {
      "name": "collect_appointment_data",
      "arguments": {
        "datum": "heute",
        "uhrzeit": "09:00",
        "name": "Unbekannt",
        "dienstleistung": "Beratung",
        "call_id": "call_f6038c53e0df72d2cb05bc11bee"
      },
      "result": {
        "success": false,
        "status": "unavailable",
        "message": "Der Termin am heute um 09:00 ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: am gleichen Tag, 17:30 Uhr oder am gleichen Tag, 18:00 Uhr.",
        "alternatives": [
          {"time": "17:30", "date": "13.10.2025", "description": "am gleichen Tag, 17:30 Uhr"},
          {"time": "18:00", "date": "13.10.2025", "description": "am gleichen Tag, 18:00 Uhr"}
        ]
      }
    }
  ]
}
```

### Call 864 Full Details
```json
{
  "id": 864,
  "retell_call_id": "call_c6340bb2fc0a132d2529270c092",
  "customer_name": "Hansi Hinterseer",
  "call_successful": true,
  "appointment_made": true,
  "session_outcome": "appointment_booked",
  "created_at": "2025-10-13T18:28:29",
  "transcript": "Agent: Guten Tag! Möchten Sie einen Termin buchen oder haben Sie eine Frage?\nUser: Ja, guten Tag, ich hätte gern Termin gebucht. Wenn möglich diese Woche Freitag am Nachmittag.\nUser: Hallo?\nAgent: Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag! Möchten Sie einen Termin buchen oder haben Sie eine Frage?\nUser: Ja, ähm, ich möchte einen Termin diese Woche Freitag am Nachmittag. Ist das möglich?\nAgent: Ich prüfe den Terminwunsch",
  "function_calls": [
    {
      "name": "current_time_berlin",
      "arguments": {},
      "result": {
        "date": "13.10.2025",
        "time": "20:28",
        "weekday": "Montag",
        "iso_date": "2025-10-13",
        "week_number": "42"
      }
    },
    {
      "name": "check_customer",
      "arguments": {
        "call_id": "call_c6340bb2fc0a132d2529270c092"
      },
      "result": {
        "success": true,
        "status": "new_customer",
        "customer_exists": false
      }
    },
    {
      "name": "collect_appointment_data",
      "arguments": {
        "datum": "2025-10-17",
        "dienstleistung": "Beratung",
        "call_id": "call_c6340bb2fc0a132d2529270c092",
        "name": "Unbekannt",
        "uhrzeit": "14:00"
      },
      "result": {
        "success": false,
        "status": "duplicate_detected",
        "message": "Sie haben bereits einen Termin am 17.10.2025 um 14:00 Uhr",
        "existing_appointment": {
          "id": 699,
          "date": "17.10.2025",
          "time": "14:00",
          "status": "scheduled"
        }
      }
    }
  ]
}
```

---

**Erstellt:** 2025-10-13
**Analyst:** Claude (via /sc:troubleshoot)
**Review:** Pending
**Status:** 🔴 CRITICAL - Immediate Action Required
