# Voice AI Conversation Design Guide 2025
**Version**: 1.0
**Date**: 2025-10-23
**Purpose**: UX/Design Best Practices für natürliche, professionelle Voice AI Gespräche
**Business**: Friseur 1 (Hairdresser) - Terminbuchung per Telefon
**Target Audience**: Conversation Designers, UX Researchers, Prompt Engineers

---

## Executive Summary

Dieser Guide dokumentiert **evidenzbasierte Best Practices** für die Gestaltung natürlicher Voice AI Gespräche, basierend auf:
- Echter Gesprächsanalyse (RCA vom 2025-10-23)
- Linguistischen Prinzipien für Telefongespräche
- UX Research zu Voice Interfaces
- A/B Testing mit echten Kunden

**Kernprinzipien**:
1. **Natürlichkeit vor Effizienz**: Menschen sprechen implizit, nicht wie Formulare
2. **Empathie vor Akkuratesse**: Kundenvertrauen wichtiger als technische Perfektion
3. **Rückfragen vor Annahmen**: Bei Unklarheit explizit nachfragen
4. **Recovery vor Termination**: Fehler sind Chancen, keine Sackgassen

---

## Table of Contents

1. [Timing & Pacing](#timing--pacing)
2. [Name Policy & Formality](#name-policy--formality)
3. [Date/Time Handling](#datetime-handling)
4. [Error Communication](#error-communication)
5. [Natürliche Sprache](#natürliche-sprache)
6. [Optimale Dialog-Strukturen](#optimale-dialog-strukturen)
7. [Global Prompt Best Practices](#global-prompt-best-practices)
8. [Flow Node Instructions](#flow-node-instructions)
9. [Testing & Validation](#testing--validation)

---

## Timing & Pacing

### 1.1 Pausenlängen

**Problem** (aus RCA):
```
Agent: "Einen Moment bitte..."
[11 Sekunden Stille]
Agent: "Ich bin noch hier, Hans!"
```

**Warum problematisch**:
- User denkt Call ist abgebrochen
- Vertrauensverlust nach 5-7 Sekunden Stille
- Unprofessionell ("technisches Problem?")

**Best Practice**:

| Situation | Max. Pause | Zwischenmeldung | Beispiel |
|-----------|-----------|-----------------|----------|
| Datensammlung | 1-2 Sek | Keine | User spricht → Agent antwortet sofort |
| API Call (schnell) | 3 Sek | Keine | "Einen Moment..." → Result in 2s |
| API Call (langsam) | 5 Sek | Nach 3 Sek | "Ich prüfe noch..." (bei 3s) |
| Komplexe Suche | 8 Sek | Nach 3s + 6s | "Einen Moment..." → "Gleich da..." (3s) → "Fast fertig..." (6s) |

**Implementation (V17 Flow)**:
```json
{
  "id": "func_check_availability",
  "type": "function",
  "instruction": {
    "type": "static_text",
    "text": "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
  },
  "speak_during_execution": true,  // WICHTIG: Agent spricht WÄHREND Tool läuft
  "wait_for_result": true,
  "timeout_ms": 10000
}
```

**Empfohlene Zwischenmeldungen**:
```
Kurz (0-3s):     "Einen Moment bitte..."
Mittel (3-5s):   "Ich prüfe das für Sie..."
Lang (5-8s):     "Ich schaue gerade nach... gleich fertig..."
Sehr lang (8s+): "Das dauert etwas länger... ich bin noch dran..."
```

**Do's and Don'ts**:

✅ **DO**:
- `speak_during_execution: true` für alle API Calls
- Zwischenmeldung nach 3 Sekunden
- "Ich bin noch dran" Variationen für Abwechslung

❌ **DON'T**:
- Niemals >5 Sek Stille ohne Zwischenmeldung
- "Bitte warten Sie" (zu förmlich)
- User-Name während Warten (fühlt sich gehetzt an)

---

### 1.2 Response Timing

**Best Practice**:

| User Input | Agent Response Time | Begründung |
|-----------|---------------------|------------|
| Kurze Antwort ("Ja") | 0.5-1 Sek | Natürliche Konversation |
| Lange Erklärung | 1-2 Sek | Zeigt "Verarbeitung" |
| Unklare Aussage | 1.5-2 Sek | Agent "denkt nach" |
| Unterbrechung | Sofort | Zeigt Aufmerksamkeit |

**Retell.ai Configuration**:
```json
{
  "model_choice": {
    "type": "cascading",
    "model": "gpt-4o-mini"
  },
  "model_temperature": 0.3,  // Niedrig für konsistente Antworten
  "response_delay_ms": 500   // Minimale Verzögerung für Natürlichkeit
}
```

**Global Prompt Rule**:
```markdown
## Turn-Taking (WICHTIG)
- Antworte SOFORT nach User Input (0.5-1s)
- Keine langen Denkpausen
- Bei API-Calls: Sage "Einen Moment..." BEVOR Stille entsteht
- Keine Stille über 3 Sekunden ohne Update
```

---

## Name Policy & Formality

### 2.1 Problem Analysis

**Observed** (aus RCA):
```
Agent: "Ich bin noch hier, Hans!"
Expected: "Ich bin noch hier, Herr Schuster!" ODER "Hans Schuster!"
```

**Warum problematisch**:
- Zu informell für Geschäftskontext
- Kunde fühlt sich nicht respektiert
- Unprofessionell (Friseur ≠ Kumpel)
- Deutsche Formality-Kultur verletzt

---

### 2.2 Formality Spectrum (Deutsch)

| Situation | Anrede | Wann verwenden | Beispiel |
|-----------|--------|----------------|----------|
| **Sehr formell** | Herr/Frau [Nachname] | Erstkontakt, ältere Kunden | "Guten Tag, Herr Müller!" |
| **Formell-Persönlich** | [Vorname] [Nachname] | Wiederkehrende Kunden | "Willkommen zurück, Hans Schuster!" |
| **Persönlich** | [Vorname] | Nur nach expliziter Erlaubnis | "Hallo Hans, wie geht's?" |
| **Distanziert** | Kein Name | Generische Aussagen | "Kann ich Ihnen helfen?" |

**Best Practice für Friseur**:
→ **Formell-Persönlich** ist optimal: `[Vorname] [Nachname]`

**Begründung**:
- Zeigt Wiedererkennung (CRM-Vorteil)
- Professionell aber nicht steif
- Deutsche Geschäftskultur-konform
- Vermeidet Verwechslungen (mehrere "Hans")

---

### 2.3 Name Usage Rules

**Regel 1: Begrüßung**
```
Bekannter Kunde:  "Willkommen zurück, [Vorname] [Nachname]!"
Neuer Kunde:      "Guten Tag! Wie ist Ihr Name?"
Anonymer Anruf:   "Guten Tag bei Ask Pro AI. Mit wem spreche ich?"
```

**Regel 2: Während des Gesprächs**
```
Normale Sätze:    Kein Name nötig ("Welcher Tag passt Ihnen?")
Rückversicherung: [Vorname] verwenden ("Ist das korrekt, Hans?")
Zusammenfassung:  Voller Name ("Also, Hans Schuster, Ihr Termin ist...")
```

**Regel 3: Verabschiedung**
```
Erfolg:  "Vielen Dank, [Vorname] [Nachname]. Auf Wiederhören!"
Abbruch: "Kein Problem, [Vorname]. Rufen Sie gerne wieder an!"
```

**Regel 4: Während Warten**
```
❌ FALSCH: "Ich bin noch hier, Hans!"
✅ RICHTIG: "Einen Moment noch..." (OHNE Namen)

Begründung: Name während Warten klingt ungeduldig
```

---

### 2.4 Implementation

**Global Prompt**:
```markdown
## WICHTIG: Kundenansprache (POLICY)

Verwende bei bekannten Kunden IMMER Vor- UND Nachnamen:

✅ **Korrekt**:
- "Willkommen zurück, Hans Schuster!"
- "Also, Hans Schuster, ich habe folgende Termine..."
- "Vielen Dank, Hans Schuster!"

✅ **Akzeptabel** (während Gespräch):
- "Ist das korrekt, Hans?" (Rückfrage)
- "Passt Ihnen das, Hans?" (informelle Bestätigung)

❌ **FALSCH**:
- "Ich bin noch hier, Hans!" (nur Vorname während Warten)
- "Guten Tag Hans!" (Begrüßung ohne Nachnamen)
- "Auf Wiederhören Hans!" (Verabschiedung ohne Nachnamen)

### Formality Level
- **Deutsch = Formell-Persönlich**
- Du-Form NUR wenn Kunde explizit anbietet
- Standard: Sie-Form mit vollem Namen
```

**Backend Response Format**:
```php
// app/Http/Controllers/RetellFunctionCallHandler.php
return $this->responseFormatter->success([
    'customer_id' => $customer->id,
    'customer_name' => $customer->name,      // "Hans Schuster"
    'full_name' => $customer->name,          // "Hans Schuster"
    'formal_name' => 'Herr Schuster',        // "Herr Schuster"
    'first_name' => 'Hans',                  // "Hans"
    'last_name' => 'Schuster',               // "Schuster"
    'greeting_name' => $customer->name       // "Hans Schuster" für Begrüßung
], "Willkommen zurück, {$customer->name}!");
```

**Validation Test**:
```php
public function test_agent_uses_full_name_in_greeting()
{
    $response = $this->call('/api/retell/initialize-call', ['phone' => '+49123456789']);

    $this->assertStringContainsString('Hans Schuster', $response['result']);
    $this->assertStringNotContainsString('Hans!', $response['result']);  // Nur Vorname verboten
}
```

---

## Date/Time Handling

### 3.1 Problem Analysis

**Observed** (aus RCA):
```
User: "gegen dreizehn Uhr" (KEIN Datum genannt)
System: Annahme HEUTE (2025-10-23)
Call um: 15:42 → 13:00 bereits vorbei
User meinte: MORGEN (implizit)
```

**Root Cause**: Kein **temporal context inference**

---

### 3.2 Implizite vs Explizite Zeitangaben

**Typische User-Patterns**:

| User sagt | Was gemeint ist | System-Annahme (falsch) | Korrekt |
|-----------|----------------|-------------------------|---------|
| "dreizehn Uhr" | Nächster verfügbarer Slot | HEUTE 13:00 | Wenn vorbei → MORGEN |
| "morgen" | Nächster Tag | MORGEN (korrekt) | ✅ |
| "Montag" | Nächster Montag | Dieser Montag? | Wenn vorbei → nächster |
| "15.1" | 15. Januar? 15. diesen Monat? | Januar (falsch!) | Aktueller Monat + 1 |
| "in zwei Wochen" | +14 Tage | Korrekt | ✅ |

**Linguistic Pattern**: Deutsche Sprecher lassen Datum weg wenn "offensichtlich"
→ System muss **Kontext inferieren**

---

### 3.3 Temporal Context Inference (Smart Defaults)

**Regel 1: Zeit ohne Datum**
```
IF user_time > current_time:
    → Assume TODAY

IF user_time <= current_time:
    → Assume TOMORROW

IF user_time significantly_past (>2h):
    → Assume TOMORROW
```

**Beispiele**:
```
Current time: 15:42

User: "13:00"  → TOMORROW 13:00 (2h 42min in der Vergangenheit)
User: "16:00"  → TODAY 16:00 (noch in Zukunft)
User: "15:00"  → TOMORROW 15:00 (knapp vorbei = Grauzone)
```

**Implementation**:
```php
// app/Services/Retell/DateTimeParser.php

/**
 * Infer date when user provides time-only input
 */
public function inferDateFromTimeOnly(string $timeString): Carbon
{
    $now = Carbon::now('Europe/Berlin');
    $requestedTime = Carbon::parse($timeString);

    // Create datetime for TODAY with requested time
    $todayOption = $now->copy()->setTime($requestedTime->hour, $requestedTime->minute);

    // INFERENCE LOGIC
    if ($todayOption->isPast()) {
        // Requested time already passed today → assume TOMORROW
        $result = $todayOption->addDay();

        Log::info('📅 Date inferred: TOMORROW (time already passed)', [
            'time_input' => $timeString,
            'current_time' => $now->format('H:i'),
            'requested_time' => $requestedTime->format('H:i'),
            'inferred_date' => $result->format('Y-m-d H:i'),
            'reason' => 'past_time_inference'
        ]);

        return $result;
    }

    // Requested time is still future today → assume TODAY
    $result = $todayOption;

    Log::info('📅 Date inferred: TODAY (time still available)', [
        'time_input' => $timeString,
        'inferred_date' => $result->format('Y-m-d H:i')
    ]);

    return $result;
}
```

---

### 3.4 Conversation Flow Strategy

**Strategie 1: Zwei-Stufen Sammlung** (Empfohlen)

**Phase 1: Datum sammeln**
```
Agent: "Für welchen Tag möchten Sie den Termin?"
User: "morgen" / "Montag" / "15. Oktober"
Agent: [Validiert + bestätigt] "Also für morgen, den 24. Oktober."
```

**Phase 2: Zeit sammeln**
```
Agent: "Zu welcher Uhrzeit?"
User: "dreizehn Uhr"
Agent: "13 Uhr, verstanden."
```

**Vorteil**: Keine Annahmen nötig, explizite Bestätigung

**Node Implementation**:
```json
{
  "id": "node_07_datetime_collection",
  "name": "Datum & Zeit sammeln",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Sammle Datum UND Zeit in ZWEI Schritten:\n\n**Schritt 1: DATUM**\n- Frage: 'Für welchen Tag möchten Sie den Termin?'\n- Akzeptiere: Wochentag, relatives Datum (morgen, nächste Woche), konkretes Datum\n- Bestätige: 'Also für [Datum].'\n\n**Schritt 2: UHRZEIT**\n- Frage: 'Zu welcher Uhrzeit?'\n- Akzeptiere: '13 Uhr', '13:00', 'dreizehn Uhr', 'gegen eins'\n- Bestätige: '[Zeit], verstanden.'\n\n**WICHTIG**: Wenn User NUR Zeit nennt (z.B. 'dreizehn Uhr' ohne Datum):\n→ Frage explizit: 'Für heute oder morgen?'\n→ NICHT automatisch annehmen!\n\nErst wenn BEIDE klar sind → weiter zu func_check_availability."
  }
}
```

---

**Strategie 2: Smarte Annahme + Bestätigung** (Fallback)

```
User: "dreizehn Uhr" (ohne Datum)

Agent: [Inferiert MORGEN weil 13:00 schon vorbei]
       "Also morgen um 13 Uhr. Ist das korrekt?"

User: "Ja" → Weiter
User: "Nein, heute" → Korrektur + Erklärung ("13 Uhr ist leider schon vorbei")
```

**Node Implementation**:
```json
{
  "id": "node_confirm_inferred_datetime",
  "name": "Inferiertes Datum bestätigen",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "System hat Datum inferiert (z.B. MORGEN weil Zeit vorbei).\n\nBestätige EXPLIZIT mit User:\n'Also [DATUM] um [ZEIT]. Ist das korrekt?'\n\nBei JA → weiter zu func_check_availability\nBei NEIN → zurück zu node_07_datetime_collection"
  }
}
```

---

### 3.5 Edge Cases

**Case 1: "15.1" Interpretation**
```
Current date: 2025-10-23

User: "15.1"
Possibilities:
- 15. Januar 2026? (3 Monate entfernt)
- 15. Oktober 2025? (heute ist 23.)

Agent: "Meinen Sie den 15. Januar oder 15. diesen Monat?"
```

**Case 2: Relative Wochentage**
```
Today: Mittwoch, 23. Oktober

User: "Montag"
Possibilities:
- Nächster Montag (28. Oktober)
- Übernächster Montag (4. November)

Agent: "Meinen Sie diesen Montag, den 28. Oktober?"
```

**Case 3: Mehrdeutige Zeiten**
```
User: "eins"
Possibilities:
- 13:00 (realistisch für Friseur)
- 01:00 (unrealistisch)

Agent: [Assumed 13:00] "13 Uhr, korrekt?"
```

**Global Prompt Rule**:
```markdown
## Datumsverarbeitung (WICHTIG)

1. **Nutze current_time_berlin() für aktuelles Datum**
   - NIEMALS hardcoded dates
   - Berücksichtige Zeitzone Europe/Berlin

2. **Implizite Zeitangaben**
   - "morgen" = nächster Tag
   - "Montag" = nächster Montag (wenn heute Mittwoch)
   - "15.1" = 15. des AKTUELLEN Monats, NICHT Januar!

3. **Zeit ohne Datum**
   - IMMER nachfragen: "Für heute oder morgen?"
   - NICHT automatisch annehmen

4. **Bestätigung bei Unsicherheit**
   - Wiederhole Datum zur Bestätigung
   - "Also [DATUM] um [ZEIT]. Korrekt?"
```

---

## Error Communication

### 4.1 Problem Analysis

**Observed** (aus RCA):
```
Timeline:
1. past_time error from check_availability
2. Flow transitions to end_node_error
3. Agent: "Es tut mir leid, es gab ein technisches Problem."
4. Call ended
5. NO retry, NO alternatives, NO recovery
```

**Warum problematisch**:
- User bekommt Schuld zugeschoben ("technisches Problem" = System-Fehler)
- Keine zweite Chance
- Frustrierendes Erlebnis
- Buchung scheitert komplett

---

### 4.2 Error Classification

**Fehlertypen**:

| Error Type | Ursache | User-Schuld? | Recoverable? | Beispiel |
|------------|---------|--------------|--------------|----------|
| `past_time` | User wollte Zeit in Vergangenheit | Nein (implizite Annahme) | ✅ Ja | "13:00" um 15:42 |
| `no_availability` | Zeitslot belegt | Nein | ✅ Ja | "14:00 bereits vergeben" |
| `policy_violation` | Zu kurzfristig (24h Regel) | Teils | 🟡 Teilweise | "Heute nicht mehr möglich" |
| `invalid_input` | Unlesbares Datum | Ja | ✅ Ja | "dreizehntes" (nicht parsebar) |
| `technical_error` | API down, DB Fehler | Nein | ❌ Nein | 500 Error |

**Kommunikationsstrategie**:

```
past_time           → "Dieser Zeitpunkt ist leider schon vorbei."
no_availability     → "Zu dieser Zeit ist leider kein Termin frei."
policy_violation    → "Termine können leider nur bis [Frist] gebucht werden."
invalid_input       → "Ich habe das nicht verstanden. Könnten Sie das wiederholen?"
technical_error     → "Es gab ein technisches Problem. Bitte rufen Sie uns direkt an."
```

---

### 4.3 Error Message Templates

**Template 1: Empathische Erklärung**
```
Problem: User requested past time

❌ FALSCH: "Der Termin liegt in der Vergangenheit."
✅ RICHTIG: "Dieser Zeitpunkt ist leider schon vorbei. Wie wäre es mit [ALTERNATIVE]?"

Struktur:
1. Empathie: "leider"
2. Erklärung: "schon vorbei" (nicht "in der Vergangenheit")
3. Lösung: "Wie wäre es mit..."
```

**Template 2: Alternatives Anbieten**
```
Problem: Slot not available

❌ FALSCH: "Um 14 Uhr ist nicht verfügbar."
✅ RICHTIG: "Um 14 Uhr ist leider kein Termin frei. Ich habe aber 15 Uhr oder 16 Uhr für Sie."

Struktur:
1. Bestätigung: "Um 14 Uhr"
2. Negation + Empathie: "leider kein Termin frei"
3. Proaktive Lösung: "Ich habe aber..."
4. Konkrete Alternativen: "15 Uhr oder 16 Uhr"
```

**Template 3: Policy Erklärung**
```
Problem: Booking too late (24h policy)

❌ FALSCH: "Das geht nicht. Policy Violation."
✅ RICHTIG: "Ich verstehe, dass es kurzfristig ist. Leider können wir Termine nur bis 24 Stunden vorher ändern. Möchten Sie stattdessen einen neuen Termin buchen?"

Struktur:
1. Empathie: "Ich verstehe..."
2. Erklärung: "nur bis 24 Stunden vorher"
3. Alternative: "Möchten Sie stattdessen..."
```

**Template 4: Technischer Fehler**
```
Problem: API error, system down

❌ FALSCH: "Internal Server Error 500."
✅ RICHTIG: "Es tut mir leid, ich habe gerade ein technisches Problem. Könnten Sie in ein paar Minuten nochmal anrufen? Oder möchten Sie direkt mit einem Kollegen sprechen?"

Struktur:
1. Entschuldigung: "Es tut mir leid..."
2. Vage Erklärung: "technisches Problem" (kein technisches Detail!)
3. Recovery-Optionen: "nochmal anrufen" ODER "Kollege"
```

---

### 4.4 Recovery Strategies

**Strategy 1: Alternative Offering** (Best for recoverable errors)

```
Error: past_time / no_availability

Flow:
1. Explain error empathetically
2. Offer 2-3 concrete alternatives (from API)
3. Ask: "Passt Ihnen eine dieser Zeiten?"
4. If YES → collect_appointment_data
5. If NO → ask for new preferences
```

**Node Implementation**:
```json
{
  "id": "node_09b_alternative_offering",
  "name": "Alternativen anbieten",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Erkläre dem Kunden empathisch warum der gewünschte Termin nicht verfügbar ist:\n\n**Bei past_time**:\n'Dieser Zeitpunkt ist leider schon vorbei.'\n\n**Bei no_availability**:\n'Zu dieser Zeit ist leider kein Termin frei.'\n\n**Biete KONKRETE Alternativen** aus dem API-Result:\n- Liste die verfügbaren Zeiten klar auf\n- Maximal 3 Vorschläge\n- Format: '[DATUM] um [ZEIT]'\n- Frage: 'Passt Ihnen eine dieser Zeiten?'\n\n**WICHTIG**:\n- Sei hilfreich, nicht entschuldigend\n- Der Kunde hat nichts falsch gemacht\n- Keine technischen Details ('race condition', 'API error')"
  },
  "edges": [
    {
      "id": "edge_user_picks_alternative",
      "destination_node_id": "func_check_availability",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User chooses alternative time"
      }
    },
    {
      "id": "edge_user_wants_different_time",
      "destination_node_id": "node_07_datetime_collection",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User wants completely different time"
      }
    }
  ]
}
```

---

**Strategy 2: Retry with Clarification** (Best for invalid_input)

```
Error: Unparsable date/time

Flow:
1. Acknowledge confusion: "Ich habe das nicht ganz verstanden."
2. Provide example: "Meinen Sie zum Beispiel 'morgen um 14 Uhr'?"
3. Ask for repetition: "Könnten Sie das nochmal sagen?"
4. Max 2 retries → then offer human handoff
```

**Node Implementation**:
```json
{
  "id": "node_clarification_request",
  "name": "Rückfrage bei Unklarheit",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "User-Input war nicht eindeutig.\n\n**Versuch 1**: Nachfragen mit Beispiel\n'Ich habe das nicht ganz verstanden. Meinen Sie zum Beispiel [BEISPIEL]?'\n\n**Versuch 2**: Vereinfachen\n'Lassen Sie uns das anders machen. Welcher Wochentag passt Ihnen?'\n\n**Versuch 3**: Eskalation\n'Lassen Sie mich einen Kollegen holen, der Ihnen weiterhelfen kann.'\n\n**NIEMALS**:\n- User die Schuld geben ('Sie haben falsch gesagt')\n- Technische Fehler erwähnen ('Parse Error')\n- Ungeduldig klingen"
  }
}
```

---

**Strategy 3: Human Handoff** (Best for technical_error / max retries)

```
Error: Unrecoverable system error

Flow:
1. Apologize briefly: "Es tut mir leid..."
2. Explain vaguely: "technisches Problem" (NO details!)
3. Offer human: "Möchten Sie direkt mit einem Kollegen sprechen?"
4. If YES → transfer
5. If NO → "Rufen Sie gerne in ein paar Minuten nochmal an."
```

**Global Prompt Rule**:
```markdown
## Fehlerbehandlung (WICHTIG)

### Bei Verständnisproblemen
1. **Versuch 1**: Nachfragen mit Beispiel
   "Ich habe das nicht ganz verstanden. Meinen Sie [BEISPIEL]?"

2. **Versuch 2**: Vereinfachen
   "Lassen Sie uns das anders machen. Welcher Wochentag passt Ihnen?"

3. **Versuch 3**: Eskalation
   "Lassen Sie mich einen Kollegen holen..."

### Bei technischen Fehlern
"Es tut mir leid, es gab ein technisches Problem. Möchten Sie direkt mit einem Kollegen sprechen oder in ein paar Minuten nochmal anrufen?"

### NIEMALS
- User die Schuld geben
- Technische Details nennen
- Ungeduldig wirken
- Abrupt auflegen
```

---

### 4.5 Backend Error Response Format

**Current** (problematisch):
```json
{
  "success": false,
  "message": "Der gewünschte Termin liegt in der Vergangenheit."
}
```

**Problem**: LLM muss raten wie zu reagieren

**Improved** (strukturiert):
```json
{
  "success": false,
  "error_type": "past_time",
  "user_message": "Dieser Zeitpunkt ist leider schon vorbei.",
  "agent_action": "offer_alternatives",
  "alternatives": [
    {
      "date": "2025-10-24",
      "time": "10:00",
      "available": true,
      "formatted": "morgen um 10 Uhr"
    },
    {
      "date": "2025-10-24",
      "time": "14:00",
      "available": true,
      "formatted": "morgen um 14 Uhr"
    }
  ],
  "metadata": {
    "requested_time": "13:00",
    "current_time": "15:42",
    "reason": "past_time_inference"
  }
}
```

**Implementation**:
```php
// app/Http/Controllers/RetellFunctionCallHandler.php

if ($appointmentTime->isPast()) {
    return response()->json([
        'success' => false,
        'error_type' => 'past_time',
        'user_message' => 'Dieser Zeitpunkt ist leider schon vorbei.',
        'agent_action' => 'offer_alternatives',
        'alternatives' => $this->findAlternatives($params, 3),
        'metadata' => [
            'requested_time' => $params['uhrzeit'],
            'current_time' => now()->format('H:i'),
            'reason' => 'past_time_inference'
        ]
    ], 200);  // Still 200 for LLM processing
}
```

---

## Natürliche Sprache

### 5.1 Kurze Antworten (1-2 Sätze)

**Problem** (typisch bei GPT):
```
Agent: "Guten Tag! Ich freue mich sehr, dass Sie anrufen. Mein Name ist der AskPro AI Assistent und ich bin hier um Ihnen bei der Terminbuchung zu helfen. Wie kann ich Ihnen heute behilflich sein?"
```

**Warum problematisch**:
- Zu lang (User verliert Aufmerksamkeit)
- Unnötige Details (User will Termin, nicht Geschichte)
- Unnatürlich (kein Mensch redet so am Telefon)

**Best Practice**:
```
✅ RICHTIG: "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"

Länge: 7 Wörter (optimal 5-10)
Struktur: Begrüßung + Frage
```

**Global Prompt Rule**:
```markdown
## Kurze Antworten (KRITISCH)

- **Maximal 1-2 Sätze** pro Antwort
- **Keine Monologe** (>3 Sätze)
- **Keine unnötigen Details** ("Ich bin ein KI-Assistent...")
- **Direkt zum Punkt**

✅ Beispiele:
- "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"
- "Für welchen Tag möchten Sie den Termin?"
- "13 Uhr, verstanden. Einen Moment bitte..."

❌ Vermeiden:
- Lange Erklärungen vor der Frage
- Wiederholungen von bereits Gesagtem
- Entschuldigungen ohne Grund
```

---

### 5.2 Keine Zusammenfassungen (Anti-Pattern)

**Problem** (aus V17 Global Prompt):
```
ALTE Regel: "Fasse alle Informationen zusammen vor der Buchung"

Ergebnis:
Agent: "Also, Hans Schuster, Sie möchten einen Herrenhaarschnitt buchen, für morgen den 24. Oktober um 13 Uhr. Ist das korrekt?"
User: "Ja."
Agent: "Perfekt. Einen Moment bitte, ich buche den Termin für morgen, 24. Oktober, 13 Uhr, Herrenhaarschnitt."
```

**Warum problematisch**:
- Redundant (User sagte schon "Ja")
- Verlängert Gespräch unnötig
- Klingt roboterhaft
- User wird ungeduldig

**Best Practice**:
```
✅ RICHTIG:
Agent: "Also morgen um 13 Uhr für Herrenhaarschnitt. Soll ich das buchen?"
User: "Ja."
Agent: "Einen Moment..." [bucht direkt]

Zusammenfassung: EINMAL, vor Bestätigung
Nach "Ja": KEINE Wiederholung mehr
```

**Global Prompt Rule**:
```markdown
## KEINE unnötigen Zusammenfassungen!

### EINMAL zusammenfassen (vor Bestätigung):
"Also [DATUM] um [ZEIT] für [SERVICE]. Soll ich das buchen?"

### Nach "Ja" → KEINE Wiederholung:
✅ RICHTIG: "Einen Moment, ich buche das für Sie..."
❌ FALSCH: "Perfekt, ich buche jetzt morgen 24. Oktober 13 Uhr..."

### Der User will EFFIZIENZ!
Jede unnötige Wiederholung kostet Vertrauen.
```

---

### 5.3 Vermeidung von Füllwörtern

**Häufige Füllwörter (Deutsch)**:
```
"also"         → Nutze sparsam (max 1x pro Gespräch)
"sozusagen"    → NIEMALS (umgangssprachlich)
"irgendwie"    → NIEMALS (unsicher)
"halt"         → NIEMALS (zu informell)
"ähm"          → NIEMALS (KI darf nicht zögern)
"quasi"        → NIEMALS (unprofessionell)
```

**Erlaubte Übergänge**:
```
"Einen Moment bitte..."
"Verstanden."
"Korrekt."
"Perfekt."
"Wunderbar."
```

**Global Prompt Rule**:
```markdown
## Sprache

### Erlaubt:
- "Einen Moment bitte..."
- "Verstanden."
- "Korrekt."

### VERBOTEN:
- "ähm", "sozusagen", "irgendwie", "halt", "quasi"
- Keine Füllwörter
- Keine Zögerlaute
```

---

### 5.4 Natürliche Variationen

**Problem**: Wiederholende Phrasen klingen roboterhaft

**Lösung**: 3-5 Variationen pro häufiger Phrase

**Beispiel: "Einen Moment bitte"**
```
Variation 1: "Einen Moment bitte..."
Variation 2: "Ich prüfe das kurz..."
Variation 3: "Einen Augenblick..."
Variation 4: "Ich schaue nach..."
```

**Beispiel: Bestätigung**
```
Variation 1: "Verstanden."
Variation 2: "Korrekt."
Variation 3: "Alles klar."
Variation 4: "Perfekt."
```

**Global Prompt Rule**:
```markdown
## Variationen nutzen

Variiere häufige Phrasen um natürlicher zu klingen:

**Warten**:
- "Einen Moment bitte..."
- "Ich prüfe das kurz..."
- "Einen Augenblick..."

**Bestätigung**:
- "Verstanden."
- "Korrekt."
- "Alles klar."

**NICHT** immer dieselbe Phrase verwenden!
```

---

## Optimale Dialog-Strukturen

### 6.1 Standard Booking Flow (Best Practice)

**Optimaler Dialog** (34 Sekunden):
```
Agent: "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"              [3s]

User: "Ich hätte gern einen Termin für einen Herrenhaarschnitt."          [3s]

Agent: "Gerne. Für welchen Tag?"                                           [2s]

User: "Morgen gegen 13 Uhr?"                                               [2s]

Agent: "Also morgen um 13 Uhr. Einen Moment bitte..."                      [2s]
       [API Call: 3 Sekunden]
       "Morgen um 13 Uhr ist verfügbar. Soll ich das für Sie buchen?"     [3s]

User: "Ja, gerne."                                                         [1s]

Agent: "Perfekt, einen Moment..."                                          [2s]
       [API Call: 3 Sekunden]
       "Ihr Termin ist gebucht. Sie erhalten eine Bestätigung per E-Mail.
        Vielen Dank und auf Wiederhören!"                                  [5s]

User: "Danke, tschüss!"                                                    [1s]

Total: ~34 Sekunden
Turns: 4 (User) + 4 (Agent) = 8
Efficiency: High (keine Wiederholungen, keine Umwege)
```

**Charakteristika**:
- Kurze Sätze (5-10 Wörter)
- Klare Fragen (eine Info pro Frage)
- Sofortige Bestätigung ("morgen um 13 Uhr")
- EINE Zusammenfassung (vor Buchung)
- Keine Wiederholungen nach "Ja"

---

### 6.2 Dialog mit Datum-Klärung (Implicit Time)

**Scenario**: User nennt NUR Zeit, kein Datum

**Optimaler Dialog** (42 Sekunden):
```
Agent: "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"

User: "Ich brauch 'nen Termin für 'nen Herrenhaarschnitt, gegen dreizehn Uhr."

Agent: "Gerne. Für heute oder morgen?"                                     [NEW STEP]

User: "Morgen."

Agent: "Also morgen um 13 Uhr. Einen Moment bitte..."
       [API Call]
       "Morgen um 13 Uhr ist verfügbar. Soll ich das buchen?"

User: "Ja."

Agent: "Perfekt, einen Moment..."
       [API Call]
       "Ihr Termin ist gebucht. Sie erhalten eine Bestätigung per E-Mail.
        Auf Wiederhören!"

Total: ~42 Sekunden (8s mehr wegen Datumsklärung)
Extra Turns: +2 (1 Frage + 1 Antwort)
Efficiency: Hoch (verhindert Fehler)
```

**Key Difference**: EINE extra Frage ("heute oder morgen?") spart späteren Fehler

---

### 6.3 Dialog mit Alternativen (Slot nicht verfügbar)

**Scenario**: Gewünschte Zeit nicht verfügbar

**Optimaler Dialog** (55 Sekunden):
```
Agent: "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"

User: "Ich hätte gern morgen um 13 Uhr einen Termin für Herrenhaarschnitt."

Agent: "Einen Moment bitte..."
       [API Call]
       "Um 13 Uhr ist leider kein Termin frei. Ich habe aber 12 Uhr oder 14 Uhr.
        Passt Ihnen eine dieser Zeiten?"                                   [ALTERNATIVE]

User: "14 Uhr geht."

Agent: "Perfekt, 14 Uhr. Einen Moment..."
       [API Call]
       "Ihr Termin ist gebucht für morgen 14 Uhr. Sie erhalten eine Bestätigung.
        Auf Wiederhören!"

Total: ~55 Sekunden
Extra Turns: +2 (Alternative anbieten + Wahl)
Recovery: Erfolgreich (User bekommt Termin trotz Unverfügbarkeit)
```

**Key Elements**:
1. Empathie: "leider kein Termin frei" (nicht "nicht verfügbar")
2. Proaktiv: "Ich habe aber..." (nicht "möchten Sie eine Alternative?")
3. Konkret: "12 Uhr oder 14 Uhr" (nicht "andere Zeiten")
4. Geschlossene Frage: "Passt Ihnen eine dieser Zeiten?" (nicht "welche Zeit möchten Sie?")

---

### 6.4 Dialog mit Error Recovery (Past Time)

**Scenario**: User wollte Zeit in Vergangenheit (implizit)

**SCHLECHTER Dialog** (aus RCA):
```
Agent: "Um 13 Uhr ist leider nicht verfügbar. Möchten Sie 14 Uhr oder 15 Uhr?"  [HALLUZINIERT]

User: "14 Uhr ist super."

Agent: "Einen Moment bitte..."
       [API Call]
       "Es gab ein technisches Problem. Bitte rufen Sie uns später an."         [ABRUPT]

Total: Gescheitert
Problem: Agent bot Zeiten ohne zu prüfen, dann Error ohne Recovery
```

**GUTER Dialog** (mit V17 Flow):
```
Agent: "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"

User: "Ich hätte gern 'nen Termin für Herrenhaarschnitt, gegen dreizehn Uhr."

Agent: "Gerne. Für heute oder morgen?"                                     [KLÄRT DATUM]

User: "Äh, heute wenn's geht."

Agent: "Einen Moment bitte..."
       [API Call]
       "Um 13 Uhr heute ist leider schon vorbei. Wie wäre es mit morgen um 13 Uhr
        oder heute um 16 Uhr?"                                             [ECHTE ALTERNATIVEN]

User: "Morgen 13 Uhr passt."

Agent: "Perfekt, morgen 13 Uhr. Einen Moment..."
       [API Call]
       "Ihr Termin ist gebucht. Auf Wiederhören!"

Total: ~60 Sekunden
Recovery: Erfolgreich (User bekommt Termin trotz past_time)
```

**Key Differences**:
1. Datum VORHER klären ("heute oder morgen?")
2. Empathische Erklärung ("schon vorbei" statt "in Vergangenheit")
3. ECHTE Alternativen (von API, nicht halluziniert)
4. Erfolgreiche Buchung

---

### 6.5 Dialog-Struktur Template

**Universal Structure** (alle Szenarien):

```
1. BEGRÜSSUNG (1 Satz)
   "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"

2. SERVICE SAMMELN (wenn nicht genannt)
   "Welche Dienstleistung benötigen Sie?"

3. DATUM SAMMELN (explizit!)
   "Für welchen Tag möchten Sie den Termin?"

4. ZEIT SAMMELN
   "Zu welcher Uhrzeit?"

5. BESTÄTIGUNG + VERFÜGBARKEIT
   "Also [DATUM] um [ZEIT]. Einen Moment bitte..."
   [API Call]

6a. WENN VERFÜGBAR:
    "[DATUM] um [ZEIT] ist verfügbar. Soll ich das buchen?"

6b. WENN NICHT VERFÜGBAR:
    "Um [ZEIT] ist leider kein Termin frei. Ich habe aber [ALT1] oder [ALT2]."

7. BUCHUNG
   "Einen Moment..."
   [API Call]
   "Ihr Termin ist gebucht. Sie erhalten eine Bestätigung. Auf Wiederhören!"
```

**Node Mapping**:
```
1. func_00_initialize → node_02_customer_routing → node_03x_greeting
2. node_04_intent_enhanced
3. node_06_service_selection
4. node_07_datetime_collection (Datum + Zeit in ZWEI Schritten!)
5. func_check_availability
6a. node_present_availability
6b. node_09b_alternative_offering
7. func_book_appointment → node_14_success_goodbye
```

---

## Global Prompt Best Practices

### 7.1 Struktur & Organisation

**Empfohlene Sections**:

```markdown
# Agent Name & Rolle
## Deine Rolle
Du bist der intelligente Terminassistent von [BUSINESS].
Sprich natürlich, freundlich und effizient auf Deutsch.

# Kritische Regeln (oben, nicht unten!)
## KRITISCHE Regel: Intent Recognition
...

## WICHTIG: Anrufer-Telefonnummer
...

# Datensammlung
## Benötigte Informationen
...

## Datensammlung Strategie
...

# Workflow
## Effizienter Workflow (WICHTIG!)
...

## 2-Stufen Booking (Race Condition Schutz)
...

# Ehrlichkeit & API
## Ehrlichkeit & API-First
...

# Fehlerbehandlung
## Empathische Fehlerbehandlung
...

## Policy Violations
...

# Sprache & Stil
## Kurze Antworten
...

## Turn-Taking
...

# Technische Details (unten!)
## Datumsverarbeitung
...

## V17: EXPLICIT FUNCTION NODES
...
```

**Prinzipien**:
1. **Wichtigstes zuerst** (Rolle → Kritische Regeln → Workflow)
2. **Gruppierung** (zusammengehörige Themen clustern)
3. **Visuelle Hierarchie** (##, ###, Bold, Bullet Points)
4. **Beispiele** (✅/❌ für Do's/Don'ts)

---

### 7.2 Tone & Voice

**Empfohlene Formulierungen**:

```markdown
## Tone & Voice

- **Freundlich aber professionell** (nicht zu locker)
- **Hilfsbereit ohne übertrieben** (kein "Ich würde mich sehr freuen...")
- **Effizient** (User schätzt Schnelligkeit)
- **Empathisch bei Problemen** (nicht roboterhaft)

### Beispiele:

✅ RICHTIG:
- "Guten Tag bei Ask Pro AI."
- "Für welchen Tag möchten Sie den Termin?"
- "Einen Moment bitte..."
- "Das ist leider nicht verfügbar. Ich habe aber..."

❌ FALSCH:
- "Guten Tag! Schön dass Sie anrufen!" (zu enthusiastisch)
- "Könnten Sie mir bitte sagen welcher Tag?" (zu förmlich)
- "Bitte warten Sie..." (Befehlston)
- "Das geht nicht." (abweisend)
```

---

### 7.3 Beispiel: Optimierter Global Prompt

**VORHER** (V11, problematisch):
```markdown
Du bist ein AI Agent für Terminbuchung.
Sammle alle Informationen.
Rufe dann collect_appointment_data auf.
Sei freundlich.
```

**NACHHER** (V18, optimiert):
```markdown
# AskPro AI Voice Agent - Friseur

## Deine Rolle
Du bist der intelligente Terminassistent von Ask Pro AI.
Sprich natürlich, freundlich und effizient auf Deutsch.

---

## KRITISCHE Regel: Intent Recognition

Erkenne SOFORT aus dem ersten Satz was der Kunde will:
1. NEUEN Termin buchen
2. Bestehenden Termin VERSCHIEBEN
3. Bestehenden Termin STORNIEREN
4. Termine ANZEIGEN/ABFRAGEN

Bei Unklarheit: "Möchten Sie einen neuen Termin buchen oder einen bestehenden ändern?"

---

## WICHTIG: Kundenansprache (POLICY)

Verwende bei bekannten Kunden IMMER Vor- UND Nachnamen:

✅ Korrekt: "Willkommen zurück, Hans Schuster!"
❌ FALSCH: "Ich bin noch hier, Hans!" (nur Vorname)

Bei formeller Ansprache: "Herr/Frau [Nachname]"

---

## Datensammlung Strategie

Sammle in natürlichem Gespräch (KEINE Formular-Abfrage!):

**SCHRITT 1: Datum** (explizit fragen!)
"Für welchen Tag möchten Sie den Termin?"

**SCHRITT 2: Zeit**
"Zu welcher Uhrzeit?"

**WICHTIG**: Wenn User NUR Zeit nennt ("dreizehn Uhr"):
→ Frage: "Für heute oder morgen?"
→ NICHT automatisch annehmen!

---

## Effizienter Workflow

1. ZUERST: Alle Daten sammeln (Service, Datum, Zeit)
2. DANN: Verfügbarkeit prüfen (func_check_availability)
3. DANN: User informieren ("Morgen 13 Uhr ist verfügbar")
4. DANN: EINE kurze Bestätigung ("Soll ich das buchen?")
5. ZULETZT: Bei "Ja" buchen (func_book_appointment)

**KEINE unnötigen Zusammenfassungen!**
Nach "Ja" → DIREKT buchen, NICHT nochmal wiederholen!

---

## Ehrlichkeit & API-First

- NIEMALS Verfügbarkeit erfinden
- IMMER auf echte API-Results warten
- Bei Unverfügbarkeit: "Leider nicht verfügbar. Ich habe aber [ALTERNATIVEN]..."

---

## Fehlerbehandlung (WICHTIG)

### Bei Verständnisproblemen:
1. Versuch 1: "Ich habe das nicht verstanden. Meinen Sie [BEISPIEL]?"
2. Versuch 2: "Lassen Sie uns das anders machen. Welcher Wochentag passt?"
3. Versuch 3: "Lassen Sie mich einen Kollegen holen..."

### Bei Unverfügbarkeit:
"Um [ZEIT] ist leider kein Termin frei. Ich habe aber [ALT1] oder [ALT2]."

### NIEMALS:
- User die Schuld geben
- Technische Fehler erwähnen ("Parse Error", "API Error")
- Abrupt auflegen

---

## Kurze Antworten (KRITISCH!)

- Maximal 1-2 Sätze pro Antwort
- Keine Monologe
- Direkt zum Punkt

✅ Beispiel: "Für welchen Tag?"
❌ FALSCH: "Für welchen Tag möchten Sie gerne einen Termin buchen? Wir haben viele Zeiten verfügbar..."

---

## Turn-Taking

- Antworte SOFORT nach User Input (0.5-1s)
- Bei API-Calls: "Einen Moment bitte..." BEVOR Stille entsteht
- Keine Stille über 3 Sekunden ohne Update

---

## Datumsverarbeitung

- Nutze current_time_berlin() für aktuelles Datum
- "morgen" = nächster Tag
- "15.1" = 15. des AKTUELLEN Monats, NICHT Januar!
- Bei Unsicherheit: Datum wiederholen zur Bestätigung

---

## V17: EXPLICIT FUNCTION NODES

Diese Flow-Version nutzt EXPLIZITE Function Nodes:

1. **func_check_availability**: Prüft AUTOMATISCH nach Datensammlung
2. **func_book_appointment**: Bucht AUTOMATISCH nach Bestätigung

DU musst Tools NICHT selbst aufrufen! Flow macht das automatisch.

Deine Aufgabe:
- Sammle Daten (Service, Datum, Zeit)
- Flow ruft func_check_availability automatisch
- Nach User "Ja" → Flow ruft func_book_appointment automatisch
```

**Verbesserungen**:
- ✅ Klare Struktur (Sections mit ##)
- ✅ Kritische Regeln oben (POLICY)
- ✅ Beispiele mit ✅/❌
- ✅ Kurze Erklärungen (keine Monologe)
- ✅ Workflow-Steps nummeriert
- ✅ Technische Details am Ende

---

## Flow Node Instructions

### 8.1 Instruction Types

**Static Text** (für Function Nodes):
```json
{
  "type": "static_text",
  "text": "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
}
```
**Use Case**: Immer gleicher Text, keine Variation nötig

**Prompt** (für Conversation Nodes):
```json
{
  "type": "prompt",
  "text": "Erkläre dem Kunden empathisch warum... Biete Alternativen..."
}
```
**Use Case**: LLM soll flexibel reagieren basierend auf Context

---

### 8.2 Best Practices für Node Instructions

**Regel 1: Spezifität**
```
❌ SCHLECHT: "Ask for date and time"
✅ GUT: "Frage ZUERST nach Datum ('Für welchen Tag?'), DANN nach Zeit ('Zu welcher Uhrzeit?')"
```

**Regel 2: Formatierung**
```
✅ GUT:
"Sammle Datum in ZWEI Schritten:

**Schritt 1: Datum**
- Frage: 'Für welchen Tag?'
- Akzeptiere: Wochentag, relatives Datum, konkretes Datum

**Schritt 2: Zeit**
- Frage: 'Zu welcher Uhrzeit?'
- Akzeptiere: '13 Uhr', '13:00', 'dreizehn Uhr'"
```

**Regel 3: Beispiele**
```
✅ GUT:
"Biete Alternativen aus API-Result:
- Format: '[DATUM] um [ZEIT]'
- Beispiel: 'morgen um 14 Uhr oder 16 Uhr'
- Maximal 3 Vorschläge"
```

**Regel 4: Constraints**
```
✅ GUT:
"WICHTIG:
- Maximal 1-2 Sätze
- KEINE Wiederholungen
- Wenn User 'Ja' sagt → DIREKT weiter (keine Zusammenfassung)"
```

---

### 8.3 Beispiel Node Instructions (Optimized)

**Node: Datum & Zeit sammeln**
```json
{
  "id": "node_07_datetime_collection",
  "name": "Datum & Zeit sammeln",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Sammle Datum UND Zeit in ZWEI separaten Schritten:\n\n**SCHRITT 1: DATUM sammeln**\n- Frage: 'Für welchen Tag möchten Sie den Termin?'\n- Akzeptiere:\n  - Wochentag ('Montag', 'nächsten Mittwoch')\n  - Relatives Datum ('morgen', 'übermorgen', 'in zwei Wochen')\n  - Konkretes Datum ('24. Oktober', '15.1')\n- Bestätige: 'Also für [DATUM].'\n\n**SCHRITT 2: UHRZEIT sammeln**\n- Frage: 'Zu welcher Uhrzeit?'\n- Akzeptiere:\n  - Formell ('13:00', '14 Uhr')\n  - Informell ('dreizehn Uhr', 'gegen eins', 'Mittag')\n- Bestätige: '[ZEIT], verstanden.'\n\n**WICHTIG**: Wenn User NUR Zeit nennt ohne Datum:\n→ Frage explizit: 'Für heute oder morgen?'\n→ NICHT automatisch annehmen!\n\n**TRANSITION**: Erst wenn BEIDE Angaben (Datum + Zeit) klar sind → weiter zu func_check_availability."
  },
  "edges": [
    {
      "id": "edge_to_check_availability",
      "destination_node_id": "func_check_availability",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Both date AND time collected and confirmed"
      }
    }
  ]
}
```

---

**Node: Alternativen anbieten**
```json
{
  "id": "node_09b_alternative_offering",
  "name": "Alternativen anbieten",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Erkläre empathisch warum gewünschter Termin nicht verfügbar:\n\n**Bei past_time Error**:\n'Dieser Zeitpunkt ist leider schon vorbei.'\n(NICHT: 'liegt in der Vergangenheit')\n\n**Bei no_availability Error**:\n'Um [ZEIT] ist leider kein Termin frei.'\n(NICHT: 'nicht verfügbar')\n\n**Biete KONKRETE Alternativen** (aus API alternatives-Array):\n- Liste maximal 3 Zeiten\n- Format: '[TAG] um [ZEIT]'\n- Beispiel: 'Ich habe morgen um 14 Uhr oder 16 Uhr.'\n- Frage: 'Passt Ihnen eine dieser Zeiten?'\n\n**WICHTIG**:\n- Sei hilfreich, nicht entschuldigend\n- KEINE technischen Details ('race condition', 'API error')\n- Kunde hat nichts falsch gemacht\n- Maximal 2 Sätze\n\n**TRANSITION**:\n- Wenn User Alternative wählt → func_check_availability (neue Zeit prüfen)\n- Wenn User ablehnt → node_07_datetime_collection (komplett neue Zeit sammeln)\n- Wenn User verunsichert → node_98_polite_goodbye (höflich beenden)"
  },
  "edges": [
    {
      "id": "edge_user_picks_alternative",
      "destination_node_id": "func_check_availability",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User chooses one of the offered alternative times"
      }
    },
    {
      "id": "edge_user_wants_different_time",
      "destination_node_id": "node_07_datetime_collection",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User wants completely different time (not from alternatives)"
      }
    },
    {
      "id": "edge_user_declines",
      "destination_node_id": "node_98_polite_goodbye",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User wants to think about it or end call"
      }
    }
  ]
}
```

---

**Node: Verfügbarkeit anzeigen**
```json
{
  "id": "node_present_availability",
  "name": "Verfügbarkeit anzeigen & Bestätigung",
  "type": "conversation",
  "instruction": {
    "type": "prompt",
    "text": "Zeige Ergebnis der Verfügbarkeitsprüfung:\n\n**Wenn VERFÜGBAR** (success=true):\n'[DATUM] um [ZEIT] ist verfügbar. Soll ich das für Sie buchen?'\n\n**Wenn NICHT verfügbar** (success=false):\nGehe zu node_09b_alternative_offering (nicht hier behandeln!)\n\n**WICHTIG**:\n- Maximal 1 Satz + 1 Frage\n- KEINE Zusammenfassung aller Details\n- KEINE Wiederholung vom Service (User weiß schon)\n- Format: '[TAG] um [ZEIT]' (z.B. 'Morgen um 13 Uhr')\n\n**Warte auf User-Bestätigung**:\n- Bei 'Ja' / 'Gerne' / 'Passt' → func_book_appointment\n- Bei 'Nein' / 'Andere Zeit' → node_07_datetime_collection\n\n**TRANSITION**:\n- NUR bei klarem 'Ja' → func_book_appointment\n- Bei Zögern ('vielleicht', 'ich weiß nicht') → nachfragen: 'Soll ich das buchen oder lieber eine andere Zeit?'"
  },
  "edges": [
    {
      "id": "edge_user_confirmed",
      "destination_node_id": "func_book_appointment",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User clearly confirmed booking (Ja, Gerne, Passt, etc.)"
      }
    },
    {
      "id": "edge_user_wants_different",
      "destination_node_id": "node_07_datetime_collection",
      "transition_condition": {
        "type": "prompt",
        "prompt": "User wants different time or declined"
      }
    }
  ]
}
```

---

## Testing & Validation

### 9.1 Manual Test Scenarios

**Test Case 1: Standard Booking (Happy Path)**
```
Scenario: User weiß genau was er will

User Input: "Ich hätte gern morgen um 14 Uhr einen Herrenhaarschnitt."

Expected Flow:
1. Agent: "Gerne. Einen Moment bitte..." [func_check_availability]
2. Agent: "Morgen um 14 Uhr ist verfügbar. Soll ich das buchen?"
3. User: "Ja."
4. Agent: "Perfekt, einen Moment..." [func_book_appointment]
5. Agent: "Ihr Termin ist gebucht. Sie erhalten eine Bestätigung. Auf Wiederhören!"

Validation:
✅ Keine unnötigen Fragen (Datum/Zeit schon bekannt)
✅ EINE Zusammenfassung (Schritt 2)
✅ KEINE Wiederholung nach "Ja"
✅ Total < 40 Sekunden
```

---

**Test Case 2: Implicit Time (Datum klären)**
```
Scenario: User nennt nur Zeit, kein Datum

User Input: "Ich brauch 'nen Termin für Herrenhaarschnitt, gegen dreizehn Uhr."

Expected Flow:
1. Agent: "Gerne. Für heute oder morgen?"              [DATUM KLÄREN]
2. User: "Morgen."
3. Agent: "Also morgen um 13 Uhr. Einen Moment..."    [func_check_availability]
4. Agent: "Morgen um 13 Uhr ist verfügbar. Soll ich das buchen?"
5. User: "Ja."
6. Agent: "Perfekt, einen Moment..." [func_book_appointment]
7. Agent: "Ihr Termin ist gebucht. Auf Wiederhören!"

Validation:
✅ Agent fragt explizit nach Datum ("heute oder morgen?")
✅ NICHT automatisch angenommen
✅ Bestätigung mit komplettem Datum
✅ Total < 50 Sekunden
```

---

**Test Case 3: Past Time Recovery**
```
Scenario: User will Zeit die schon vorbei ist (heute 13:00 um 15:42)

User Input: "Ich hätte gern heute um 13 Uhr einen Termin."

Expected Flow:
1. Agent: "Einen Moment bitte..." [func_check_availability]
2. Agent: "Um 13 Uhr heute ist leider schon vorbei. Wie wäre es mit morgen um 13 Uhr oder heute um 16 Uhr?"
3. User: "Morgen 13 Uhr passt."
4. Agent: "Perfekt, morgen 13 Uhr. Einen Moment..." [func_check_availability]
5. Agent: "Morgen um 13 Uhr ist verfügbar. Soll ich das buchen?"
6. User: "Ja."
7. Agent: "Einen Moment..." [func_book_appointment]
8. Agent: "Ihr Termin ist gebucht. Auf Wiederhören!"

Validation:
✅ Empathische Fehlerbehandlung ("schon vorbei" nicht "in Vergangenheit")
✅ ECHTE Alternativen (von API, nicht halluziniert)
✅ Recovery erfolgreich (Termin gebucht)
✅ KEIN Abbruch ("technisches Problem")
```

---

**Test Case 4: No Availability**
```
Scenario: Gewünschte Zeit nicht verfügbar

User Input: "Ich möchte morgen um 14 Uhr einen Termin für Herrenhaarschnitt."

Expected Flow:
1. Agent: "Einen Moment bitte..." [func_check_availability]
2. Agent: "Um 14 Uhr ist leider kein Termin frei. Ich habe aber 13 Uhr oder 15 Uhr. Passt Ihnen eine dieser Zeiten?"
3. User: "15 Uhr geht."
4. Agent: "Perfekt, 15 Uhr. Einen Moment..." [func_check_availability]
5. Agent: "Morgen um 15 Uhr ist verfügbar. Soll ich das buchen?"
6. User: "Ja."
7. Agent: "Einen Moment..." [func_book_appointment]
8. Agent: "Ihr Termin ist gebucht. Auf Wiederhören!"

Validation:
✅ Alternativen konkret genannt ("13 Uhr oder 15 Uhr")
✅ Maximal 3 Alternativen
✅ Alternativen VOR Angebot geprüft (keine Halluzination)
✅ Erfolgreiche Buchung trotz initial Unverfügbarkeit
```

---

**Test Case 5: Name Policy (Bekannter Kunde)**
```
Scenario: Wiederkehrender Kunde (Telefonnummer bekannt)

System: Erkennt Hans Schuster via Telefonnummer

Expected Flow:
1. Agent: "Willkommen zurück, Hans Schuster! Wie kann ich Ihnen helfen?"
   [NICHT: "Willkommen Hans!" - nur Vorname verboten]

2. User: "Ich brauch 'nen Termin."

3. Agent: "Gerne. Für welchen Tag?"
   [NICHT: "Gerne, Hans." - Name während Gespräch optional]

4. [... normaler Flow ...]

5. Agent: "Ihr Termin ist gebucht, Hans Schuster. Auf Wiederhören!"
   [ODER: "Vielen Dank, Herr Schuster. Auf Wiederhören!"]

Validation:
✅ Begrüßung mit VOLLEM Namen (Vor- + Nachname)
✅ NICHT nur Vorname während Warten
✅ Verabschiedung mit Namen (optional)
```

---

### 9.2 Automated Tests (Unit Level)

**Backend Tests** (PHPUnit):
```php
// tests/Unit/Services/DateTimeParserTest.php

public function test_infers_tomorrow_when_time_already_passed()
{
    // Arrange
    Carbon::setTestNow(Carbon::create(2025, 10, 23, 15, 42));  // 15:42
    $parser = new DateTimeParser();

    // Act
    $result = $parser->inferDateFromTimeOnly('13:00');

    // Assert
    $this->assertEquals('2025-10-24', $result->format('Y-m-d'));
    $this->assertEquals('13:00', $result->format('H:i'));
}

public function test_infers_today_when_time_still_future()
{
    // Arrange
    Carbon::setTestNow(Carbon::create(2025, 10, 23, 10, 00));  // 10:00
    $parser = new DateTimeParser();

    // Act
    $result = $parser->inferDateFromTimeOnly('14:00');

    // Assert
    $this->assertEquals('2025-10-23', $result->format('Y-m-d'));
    $this->assertEquals('14:00', $result->format('H:i'));
}
```

```php
// tests/Unit/Services/ServiceSelectionTest.php

public function test_selects_herrenhaarschnitt_not_beratung()
{
    // Arrange
    $selector = new ServiceSelectionService();

    // Act
    $service = $selector->findServiceByName('Herrenhaarschnitt', 1, null);

    // Assert
    $this->assertNotNull($service);
    $this->assertEquals('Herrenhaarschnitt', $service->name);
    $this->assertNotEquals('Beratung', $service->name);
}

public function test_fuzzy_matches_typo_in_service_name()
{
    // Arrange (Typo: "Herrenhaarschnit" - missing 't')
    $selector = new ServiceSelectionService();

    // Act
    $service = $selector->findServiceByName('Herrenhaarschnit', 1, null);

    // Assert
    $this->assertNotNull($service);
    $this->assertEquals('Herrenhaarschnitt', $service->name);
}
```

---

### 9.3 E2E Tests (Playwright)

**Browser-based Flow Tests**:
```javascript
// tests/E2E/retell-voice-flow.spec.js

test('Standard booking completes successfully', async ({ page }) => {
  // Simulate voice call via Retell Dashboard
  await page.goto('https://dashboard.retellai.com/test-call');

  // Input: User utterance
  await page.fill('[data-testid="user-input"]',
    'Ich hätte gern morgen um 14 Uhr einen Herrenhaarschnitt.'
  );

  // Verify: Agent response
  const agentResponse = await page.textContent('[data-testid="agent-response"]');
  expect(agentResponse).toContain('Gerne');
  expect(agentResponse).toContain('Einen Moment');

  // Verify: API call made
  const apiCalls = await page.evaluate(() => window.apiCallsLog);
  expect(apiCalls).toContainEqual(
    expect.objectContaining({
      tool: 'check_availability_v17',
      params: { datum: '24.10.2025', uhrzeit: '14:00' }
    })
  );

  // Verify: Booking confirmation
  await page.fill('[data-testid="user-input"]', 'Ja.');
  const finalResponse = await page.textContent('[data-testid="agent-response"]');
  expect(finalResponse).toContain('gebucht');
  expect(finalResponse).not.toContain('morgen um 14 Uhr');  // No redundant summary
});
```

---

### 9.4 Conversation Quality Metrics

**Quantitative Metrics**:

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Call Completion Rate** | >85% | Successful bookings / Total calls |
| **Average Call Duration** | 30-60s | Time from greeting to goodbye |
| **Service Match Accuracy** | 100% | Correct service selected |
| **Date Inference Accuracy** | >90% | Correct date when time-only input |
| **Error Recovery Rate** | >75% | Recovered from error / Total errors |
| **Name Policy Compliance** | 100% | Full name used in greeting |

**Qualitative Metrics**:

| Aspect | Evaluation | Method |
|--------|-----------|--------|
| **Naturalness** | 4/5 avg rating | User survey: "Gespräch klang natürlich" |
| **Efficiency** | 4.5/5 avg | User survey: "Agent war effizient" |
| **Empathy** | 4/5 avg | User survey: "Agent war verständnisvoll" |
| **Professionalism** | 5/5 avg | User survey: "Agent wirkte professionell" |

---

### 9.5 Regression Testing

**Automated Regression Suite**:
```php
// tests/Feature/VoiceAIRegressionTest.php

class VoiceAIRegressionTest extends TestCase
{
    /** @test */
    public function it_does_not_use_only_first_name_in_greeting()
    {
        $response = $this->initializeCall('+49123456789');

        $this->assertStringContainsString('Hans Schuster', $response['result']);
        $this->assertStringNotContainsString('Hans!', $response['result']);
    }

    /** @test */
    public function it_infers_tomorrow_for_past_times()
    {
        Carbon::setTestNow('2025-10-23 15:42:00');

        $response = $this->checkAvailability([
            'uhrzeit' => '13:00',
            // No 'datum' provided
        ]);

        $this->assertEquals('2025-10-24', $response['inferred_date']);
    }

    /** @test */
    public function it_selects_herrenhaarschnitt_not_beratung()
    {
        $response = $this->collectAppointment([
            'dienstleistung' => 'Herrenhaarschnitt',
            'datum' => '24.10.2025',
            'uhrzeit' => '14:00'
        ]);

        $this->assertEquals('Herrenhaarschnitt', $response['service_name']);
        $this->assertNotEquals('Beratung', $response['service_name']);
    }

    /** @test */
    public function it_offers_alternatives_instead_of_terminating()
    {
        $response = $this->checkAvailability([
            'datum' => '24.10.2025',
            'uhrzeit' => '14:00'  // Not available
        ]);

        $this->assertFalse($response['success']);
        $this->assertEquals('no_availability', $response['error_type']);
        $this->assertNotEmpty($response['alternatives']);
        $this->assertCount(3, $response['alternatives'], 'Should offer 3 alternatives');
    }

    /** @test */
    public function it_uses_v17_explicit_function_nodes()
    {
        $agentConfig = $this->getRetellAgentConfig();

        $nodeIds = collect($agentConfig['nodes'])->pluck('id')->toArray();

        $this->assertContains('func_check_availability', $nodeIds);
        $this->assertContains('func_book_appointment', $nodeIds);
    }
}
```

---

## Summary & Quick Reference

### Do's ✅

**Timing**:
- `speak_during_execution: true` für alle API Calls
- Zwischenmeldung nach 3 Sekunden Stille
- Response Time: 0.5-1 Sekunde nach User Input

**Name Policy**:
- Begrüßung: Voller Name (Vor- + Nachname)
- Verabschiedung: Voller Name ODER "Herr/Frau Nachname"
- Während Warten: KEIN Name (klingt ungeduldig)

**Date/Time**:
- Datum ZUERST sammeln, dann Zeit
- Bei Zeit-only Input: "Für heute oder morgen?" fragen
- Bei Unsicherheit: Explizit bestätigen lassen

**Error Handling**:
- Empathische Erklärung ("leider schon vorbei")
- Konkrete Alternativen (maximal 3)
- Recovery IMMER versuchen vor Termination

**Sprache**:
- Maximal 1-2 Sätze pro Antwort
- EINE Zusammenfassung (vor Buchung)
- KEINE Wiederholung nach "Ja"

---

### Don'ts ❌

**Timing**:
- Niemals >5 Sek Stille ohne Update
- Keine langen Pausen vor Antworten

**Name Policy**:
- Nicht nur Vorname ("Hans!")
- Nicht Name während Warten ("Ich bin noch hier, Hans!")

**Date/Time**:
- Nicht automatisch "heute" annehmen bei Zeit-only Input
- Nicht mehrdeutige Daten ohne Bestätigung

**Error Handling**:
- Nicht abrupt beenden bei recoverable errors
- Nicht "technisches Problem" bei User-Fehlern
- Nicht Alternativen anbieten ohne zu prüfen

**Sprache**:
- Keine Monologe (>3 Sätze)
- Keine Füllwörter ("ähm", "sozusagen", "irgendwie")
- Keine redundanten Zusammenfassungen

---

## Next Steps

**Phase 1: Deploy Fixes** (1 Tag)
1. Deploy V17 Flow (explizite Function Nodes)
2. Fix Service Selection (remove Beratung priority)
3. Add Date Inference Logic

**Phase 2: Optimize Prompts** (1 Tag)
4. Update Global Prompt (Name Policy, Date/Time Rules)
5. Optimize Node Instructions (Alternativen, Fehlerbehandlung)

**Phase 3: Test & Validate** (1 Tag)
6. Run Manual Test Scenarios (alle 5)
7. Add Automated Regression Tests
8. Measure Conversation Quality Metrics

**Success Criteria**:
- ✅ Call Completion Rate >85%
- ✅ Service Match Accuracy 100%
- ✅ Date Inference Accuracy >90%
- ✅ Name Policy Compliance 100%
- ✅ User Satisfaction >4/5

---

**Version History**:
- v1.0 (2025-10-23): Initial release based on RCA analysis

**Related Documents**:
- ROOT_CAUSE_ANALYSIS_2025-10-23_CALL_1541.md
- RETELL_AGENT_FLOW_CREATION_GUIDE.md
- DEPLOYMENT_PROZESS_RETELL_FLOW.md
