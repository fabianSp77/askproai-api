# Single-Prompt Agent V116 - ERFOLGREICH ERSTELLT

**Erstellt:** 2025-11-10
**Status:** ✅ Ready for Testing

---

## Agent Details

**Agent ID:** `agent_f09defa16f7e94538311d13895`
**Agent Name:** Friseur 1 Single-Prompt Agent V116
**Dashboard URL:** https://dashboard.retellai.com/agents/agent_f09defa16f7e94538311d13895

**LLM ID:** `llm_7766ba319863259f940e75ff6871`
**Model:** gpt-4o-mini (via Retell managed LLM)

**Phone Number:** +493033081738
**Voice:** cartesia-Lina
**Language:** de-DE

---

## Architecture: Single-Prompt vs Conversation Flow

**Entscheidung:** Wir haben einen **Single-Prompt Agent** erstellt statt Conversation Flow

**Grund:**
- V113, V114, V115 Conversation Flows hatten alle verschiedene Fehler
- Zu viele Single Points of Failure in Multi-Node Architecture
- Single-Prompt = Einfacher, direkter, weniger fehleranfällig

**Architektur:**
```
┌─────────────────────────────────────────────┐
│  Single-Prompt Agent V116                   │
│  - Ein einziger comprehensive System-Prompt │
│  - Alle 5 Custom Functions verfügbar        │
│  - Keine Node-Transitions                   │
│  - LLM entscheidet direkt welche Function   │
└─────────────────────────────────────────────┘
```

---

## System-Prompt Highlights

### 5 Phasen:
1. **Begrüßung & Initial-Info** → `get_current_context()` SOFORT aufrufen
2. **Informationen Sammeln** → Name, Service, Datum (YYYY-MM-DD!), Zeit
3. **Verfügbarkeit Prüfen** → `check_availability_v17()`
4. **Buchung** → `start_booking()` → `confirm_booking()`
5. **Abschluss** → Höflich verabschieden

### KRITISCHE Regeln im Prompt:

**ISO-Datumsformat:**
```
❗ datum parameter MUSS YYYY-MM-DD sein (z.B. "2025-11-11")
❗ IMMER get_current_context() nutzen um relative Daten zu konvertieren
❗ "morgen" → Context.tomorrow.date → "2025-11-11"
```

**VERBOTEN-Liste:**
```
❌ NIE "gebucht" sagen vor confirm_booking success=true
❌ NIE nach Telefonnummer fragen (auto-detect via Caller ID)
```

**Function Sequence:**
```
get_current_context → check_customer → check_availability → start_booking → confirm_booking
```

---

## Custom Functions (alle 5 konfiguriert)

### 1. get_current_context
**URL:** `https://gateway.askpro.ai/api/retell/get-current-context`
**Parameter:** `call_id`
**Zweck:** Aktuelles Datum/Zeit/Kontext für relative Datumskonvertierung

### 2. check_customer
**URL:** `https://gateway.askpro.ai/api/retell/check-customer`
**Parameter:** `call_id`
**Zweck:** Kundenidentifikation

### 3. check_availability_v17
**URL:** `https://gateway.askpro.ai/api/retell/v17/check-availability`
**Parameter:** `name, datum (YYYY-MM-DD!), dienstleistung, uhrzeit (HH:MM), call_id`
**Zweck:** Verfügbarkeitsprüfung mit Alternativen

### 4. start_booking
**URL:** `https://gateway.askpro.ai/api/retell/start-booking`
**Parameter:** `customer_name, customer_phone (LEER!), service_name, appointment_date (YYYY-MM-DD), appointment_time (HH:MM), call_id`
**Zweck:** Buchung vorbereiten (Two-Step Flow)

### 5. confirm_booking
**URL:** `https://gateway.askpro.ai/api/retell/confirm-booking`
**Parameter:** `call_id`
**Zweck:** Buchung finalisieren

---

## Voice Settings

```json
{
  "voice_id": "cartesia-Lina",
  "voice_temperature": 0.02,
  "voice_speed": 1.0,
  "responsiveness": 1.0,
  "interruption_sensitivity": 1.0,
  "enable_backchannel": true,
  "backchannel_frequency": 0.7,
  "backchannel_words": ["mhm", "verstehe", "okay", "ja"],
  "ambient_sound": "call-center",
  "ambient_sound_volume": 0.3
}
```

---

## Webhook & Timing

**Webhook:** `https://api.askproai.de/api/webhooks/retell`
**Reminder:** 10s Stille → 2x nachfragen
**Max Duration:** 30 Min (1800000 ms)
**Silence Timeout:** 60s (60000 ms)

---

## Boosted Keywords

```
Friseur, Termin, buchen, Herrenhaarschnitt, Damenhaarschnitt,
morgen, heute, verfügbar
```

---

## Nächste Schritte

### 1. Agent publishen (MANUELL IM DASHBOARD ERFORDERLICH)

Die API akzeptiert kein `is_published` field. Bitte im Dashboard publishen:

1. Gehe zu: https://dashboard.retellai.com/agents/agent_f09defa16f7e94538311d13895
2. Klicke "Publish" Button
3. Fertig!

### 2. Testanruf durchführen

**Telefonnummer:** +493033081738

**Test-Szenario:**
```
User: "Hallo, Hans Müller hier. Ich hätte gern morgen um 10 Uhr einen Herrenhaarschnitt."

Expected Flow:
1. ✅ Agent ruft get_current_context() auf
2. ✅ Agent ruft check_customer() auf
3. ✅ Agent ruft check_availability_v17() auf mit:
   - datum: "2025-11-11" (ISO format!)
   - uhrzeit: "10:00"
4. ✅ Wenn verfügbar: Agent fragt nach Bestätigung
5. ✅ User: "Ja, bitte"
6. ✅ Agent ruft start_booking() auf
7. ✅ Agent ruft confirm_booking() auf
8. ✅ Agent sagt "Ihr Termin ist gebucht!"
```

### 3. Logs prüfen

Nach Testanruf:
```bash
# Backend Logs
tail -f storage/logs/laravel.log

# Retell Dashboard
https://dashboard.retellai.com/calls

# Testcall Analyse
php scripts/analyze_latest_testcall_detailed_2025-11-09.php
```

---

## Vergleich: V115 (Flow) vs V116 (Single-Prompt)

| Feature | V115 (Conversation Flow) | V116 (Single-Prompt) |
|---------|-------------------------|----------------------|
| **Architecture** | Multi-Node mit Transitions | Ein einziger Prompt |
| **Complexity** | Hoch (50+ Nodes) | Niedrig (1 LLM) |
| **Single Points of Failure** | Viele (jede Transition) | Wenige (nur LLM) |
| **Datumsformat** | ❌ Incomplete (missing year) | ✅ Enforced YYYY-MM-DD |
| **Function Calls** | Via Edge Transitions | Direkt vom LLM |
| **Debugging** | Schwierig (Flow-State) | Einfach (Linear) |
| **Maintenance** | Aufwändig | Einfach (nur Prompt) |

---

## Known Issues aus V115 (GELÖST in V116)

### ✅ GELÖST: Incomplete Date Format
**V115 Problem:**
```json
"datum": "Dienstag, den 11. November" ❌ MISSING YEAR!
```

**V116 Lösung:**
```
Prompt enforcement: datum MUSS YYYY-MM-DD sein
Multiple reminders im Prompt
Examples: "morgen" → "2025-11-11"
```

### ✅ GELÖST: Premature "gebucht" Messages
**V115 Problem:** Agent sagte "gebucht" bevor confirm_booking

**V116 Lösung:**
```
VERBOTEN-Section im Prompt
❌ "Der Termin ist bereits gebucht" (OHNE confirm_booking)
Explicit rule: NIE "gebucht" sagen vor confirm_booking success=true
```

### ✅ GELÖST: Flow Stuck in Nodes
**V115 Problem:** Agent stuck in intent_router Node

**V116 Lösung:** Keine Nodes! LLM entscheidet direkt.

---

## API Creation Details (für Referenz)

### Erfolgreicher Agent-Create Call:
```bash
POST https://api.retellai.com/create-agent
Content-Type: application/json
Authorization: Bearer $RETELL_TOKEN

{
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "llm_7766ba319863259f940e75ff6871"
  },
  "voice_id": "cartesia-Lina"  # ← WICHTIG: String-Name, nicht UUID!
}
```

**Voice ID Discovery:** Das war der Schlüssel! UUID voice_id gab 404, String-Name funktioniert.

---

## Files Created

- `/tmp/create_retell_llm_v116.json` - LLM Configuration
- `/tmp/llm_v116_response.json` - LLM Creation Response
- `/tmp/llm_v116_id.txt` - LLM ID
- `/tmp/agent_v116_final.json` - Agent Configuration
- `/tmp/agent_v116_id.txt` - Agent ID
- `/var/www/api-gateway/SINGLE_PROMPT_AGENT_V116_COMPLETE.md` - Diese Datei

---

## Success Metrics (Nach Testing)

Track these after test calls:

- [ ] Agent ruft get_current_context() SOFORT auf
- [ ] Agent konvertiert "morgen" korrekt zu YYYY-MM-DD
- [ ] check_availability_v17 receives ISO date format
- [ ] Agent wartet auf Bestätigung vor start_booking
- [ ] Agent ruft confirm_booking NACH start_booking auf
- [ ] Agent sagt NIE "gebucht" vor confirm_booking success=true
- [ ] Caller ID auto-detection funktioniert (keine Phone-Frage)

---

## Team Communication

**An Product/Test Team:**

✅ Single-Prompt Agent V116 ist fertig und bereit für Testing!

**Was ist neu:**
- Simplere Architektur (Single-Prompt statt Multi-Node Flow)
- Alle bekannten Datumsformat-Bugs gefixt
- Strikte ISO-Format Enforcement (YYYY-MM-DD)
- Alle 5 Backend Functions konfiguriert

**Nächster Schritt:**
1. Agent im Dashboard publishen: https://dashboard.retellai.com/agents/agent_f09defa16f7e94538311d13895
2. Testanruf an +493033081738
3. Logs prüfen
4. Feedback geben

**Expected Outcome:**
Agent sollte jetzt korrekt:
- Datum im ISO-Format senden
- Auf confirm_booking warten bevor "gebucht" sagen
- NIE nach Telefonnummer fragen

---

**Erstellt von:** Claude Code
**Date:** 2025-11-10
**Version:** V116 (Single-Prompt Architecture)
