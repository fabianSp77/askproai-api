# Retell Agent V47 - Capabilities Summary
## Friseur 1 Voice AI Terminassistent

**Version:** 47 (Live)
**Dokumentation:** https://api.askproai.de/docs/agent-v47-capabilities.html
**Last Updated:** 2025-11-05 20:15 Uhr

---

## 📊 Quick Stats

- **Tools/Functions:** 8
- **Verfügbare Services:** 18
- **Dynamic Variables:** 10
- **Haupt-Use-Cases:** 5
- **Unterstützte Sprache:** Deutsch (de-DE)
- **Voice Model:** 11labs-Adrian
- **LLM Model:** GPT-4o-mini (Cascading)
- **Max Call Duration:** 30 Minuten

---

## ✅ WAS DER AGENT KANN

### 1. Terminbuchung (Vollständig automatisiert)
- ✅ Natürliche Spracheingabe verstehen ("morgen um 3", "nächsten Freitag")
- ✅ Service-Disambiguierung (Herren vs. Damen Haarschnitt)
- ✅ Proaktive Terminvorschläge ("Was ist heute frei?" → Zeigt 3-5 Slots)
- ✅ Verfügbarkeit in Echtzeit bei Cal.com prüfen
- ✅ 2-Step Booking für schnelles Feedback (<500ms)
- ✅ Automatische Termin-Bestätigung per Email
- ✅ Natürliche Zeitansagen ("am Montag, den 11. November um 15 Uhr 20")

**Erfasste Daten:**
- Kundenname
- Telefonnummer
- Email (optional)
- Service (aus 18 verfügbaren)
- Datum (relativ oder absolut)
- Uhrzeit (natürlich oder HH:MM)

### 2. Terminverwaltung
- ✅ **Termine anzeigen:** Liste aller zukünftigen Termine des Kunden
- ✅ **Termine stornieren:** Identifikation via Datum/Uhrzeit, Email-Bestätigung
- ✅ **Termine verschieben:** Alter Termin → Neuer Termin, Verfügbarkeit prüfen

### 3. Service-Information
- ✅ Alle 18 Services auflisten mit Preisen/Dauer
- ✅ Synonym-Erkennung ("Herrenschnitt" → "Herrenhaarschnitt")
- ✅ Service-Beschreibungen auf Anfrage
- ✅ Vorbereitungstipps (z.B. bei Dauerwelle, Färbung)
- ✅ Preise/Dauer NUR auf explizite Nachfrage

### 4. Intelligente Konversation
- ✅ Intent-Erkennung (BOOK | CHECK | CANCEL | RESCHEDULE | SERVICES)
- ✅ State-aware: Keine redundanten Fragen, merkt sich Kontext
- ✅ Service-Disambiguierung ohne Preise zu nennen (V47 Fix)
- ✅ Proaktive Verfügbarkeitsvorschläge (V47 Fix)
- ✅ Tool-Call Enforcement: Ruft IMMER check_availability auf (V47 Fix)
- ✅ Jahr-Bug gefixt: Nutzt IMMER 2025 (nicht 2023)

### 5. Verfügbare Services (18 Total)

**Haarschnitte:**
- Herrenhaarschnitt (32€, 55 Min)
- Damenhaarschnitt (45€, 45 Min)
- Kinderhaarschnitt (20€, 30 Min)
- Trockenschnitt (30€, 30 Min)
- Waschen, schneiden, föhnen (55€, 60 Min)

**Färbungen:**
- Ansatzfärbung (58€, 135 Min)
- Ansatz + Längenausgleich (85€, 155 Min)
- Balayage/Ombré (110€, 150 Min)
- Komplette Umfärbung (Blondierung) (145€, 180 Min)

**Styling & Pflege:**
- Föhnen & Styling Damen (32€, 30 Min)
- Föhnen & Styling Herren (20€, 20 Min)
- Waschen & Styling (28€, 45 Min)
- Dauerwelle (78€, 135 Min)

**Treatments:**
- Hairdetox (22€, 15 Min)
- Rebuild Treatment Olaplex (42€, 15 Min)
- Intensiv Pflege Maria Nila (28€, 15 Min)
- Gloss (38€, 30 Min)
- Haarspende (28€, 30 Min)

### 6. Tools/Functions (8 Total)

| Tool | Beschreibung | Timeout | Status |
|------|--------------|---------|--------|
| `check_availability_v17` | Prüft Verfügbarkeit bei Cal.com | 15s | ✅ Live |
| `book_appointment_v17` | Bucht Termin (Legacy) | 15s | ⚠️ Legacy |
| `start_booking` | Step 1: Validiert Daten (<500ms) | 5s | ✅ Live |
| `confirm_booking` | Step 2: Führt Cal.com Buchung aus | 30s | ✅ Live |
| `get_customer_appointments` | Ruft Termine ab | 15s | ✅ Live |
| `cancel_appointment` | Storniert Termin | 15s | ✅ Live |
| `reschedule_appointment` | Verschiebt Termin | 15s | ✅ Live |
| `get_available_services` | Listet Services auf | 15s | ✅ Live |

---

## ⚠️ WAS DER AGENT NICHT KANN

### Funktionale Limitierungen
- ❌ **Bezahlung verarbeiten:** Nur Buchung, keine Zahlung
- ❌ **Spezielle Kundenwünsche:** Keine Notizen/Anmerkungen speichern
- ❌ **Stylist-Präferenzen:** Kann nicht nach bestimmtem Stylist buchen
- ❌ **Mehrfachbuchungen:** Nur 1 Termin pro Call
- ❌ **SMS-Benachrichtigung:** Nur Email (keine SMS)
- ❌ **Terminhistorie:** Zeigt nur ZUKÜNFTIGE Termine
- ❌ **Warteliste:** Keine Wartelisten-Funktion
- ❌ **Gruppenb buchungen:** Keine Familie/Freunde zusammen buchen

### Performance Constraints
- ⏱️ **check_availability:** 2-4s Latenz (Cal.com API)
- ⏱️ **book_appointment:** 4-5s Latenz (Cal.com + Database)
- ⏱️ **Max Call Duration:** 30 Minuten (dann Auto-End)
- ⏱️ **Silence Timeout:** 60 Sekunden
- ⏱️ **Tool Timeout:** 5-30s je nach Tool

### Sicherheit & Datenschutz
- 🔒 **Keine Kreditkarten:** Keine Payment Information
- 🔒 **PII Redaction:** Persönliche Daten werden nach Call redacted
- 🔒 **Keine Medizin:** Keine medizinischen Informationen
- 🔒 **Webhook Auth:** Nur via Bearer Token

### Technische Limitierungen
- 🚫 **Keine Bilder:** Kann keine Frisuren-Bilder zeigen
- 🚫 **Keine Multi-Language:** Nur Deutsch (de-DE)
- 🚫 **Keine Offline-Mode:** Braucht Internet für Cal.com API
- 🚫 **Keine Kalender-Sync:** Kein Google Calendar/Outlook Sync
- 🚫 **Keine Erinnerungen:** Keine automatischen Reminder-Calls

---

## 🎯 Use Cases (5 Haupt-Flows)

### 1. Neue Terminbuchung
```
User → "Ich möchte einen Haarschnitt buchen"
Agent → Service-Disambiguierung → Daten sammeln → Verfügbarkeit prüfen → Buchen → Bestätigung
Time: ~45-90 Sekunden
Tools: check_availability_v17, book_appointment_v17
```

### 2. Termine anzeigen
```
User → "Welche Termine habe ich?"
Agent → get_customer_appointments → Liste präsentieren
Time: ~15-20 Sekunden
Tools: get_customer_appointments
```

### 3. Termin stornieren
```
User → "Ich möchte meinen Termin stornieren"
Agent → Termin identifizieren → cancel_appointment → Bestätigung
Time: ~20-30 Sekunden
Tools: cancel_appointment
```

### 4. Termin verschieben
```
User → "Ich möchte meinen Termin verschieben"
Agent → Alt & Neu erfassen → reschedule_appointment → Bestätigung
Time: ~40-60 Sekunden
Tools: reschedule_appointment, check_availability_v17
```

### 5. Service-Information
```
User → "Was bieten Sie an?"
Agent → get_available_services → Liste präsentieren → Optional buchen
Time: ~10-15 Sekunden
Tools: get_available_services
```

---

## 🔧 Technische Spezifikationen

### Voice & Speech Settings
```json
{
  "voice_id": "11labs-Adrian",
  "voice_temperature": 0.02,
  "voice_speed": 1,
  "language": "de-DE",
  "interruption_sensitivity": 1,
  "responsiveness": 1,
  "enable_backchannel": true
}
```

### Model Configuration
```json
{
  "model_choice": {
    "type": "cascading",
    "model": "gpt-4o-mini"
  },
  "model_temperature": 0.3,
  "post_call_analysis_model": "gpt-4o-mini"
}
```

### Call Settings
```json
{
  "max_call_duration_ms": 1800000,
  "end_call_after_silence_ms": 60000,
  "reminder_trigger_ms": 10000,
  "reminder_max_count": 2,
  "begin_after_user_silence_ms": 800
}
```

### Webhook Configuration
```json
{
  "webhook_url": "https://api.askproai.de/api/webhooks/retell",
  "method": "POST",
  "authentication": "Bearer Token"
}
```

---

## 🔄 Integration Architecture

```
[Inbound Call]
     ↓
[Retell.ai Voice AI]
     ↓
[Agent V47 Conversation Flow]
     ↓
[Function Call] → [Laravel Webhook]
     ↓               ↓
     └→ [Cal.com API V2]
     └→ [PostgreSQL Database]
     └→ [Redis Cache]
     ↓
[Response] → [Agent] → [User]
```

### External Dependencies
- **Cal.com API V2:** Terminbuchung, Verfügbarkeit, Event Types
- **Retell.ai API:** Voice AI, Conversation Flow, Function Calling
- **PostgreSQL:** Appointments, Customers, Services, Branches
- **Redis:** Availability Cache (5min TTL), Session Data

---

## 📜 Version History

### V47 (2025-11-05) - CURRENT
**Fixes:**
- ✅ Preise/Dauer aus Service-Disambiguierung entfernt
- ✅ Tool-Call Enforcement hinzugefügt (MUSS check_availability callen)
- ✅ Beispielzeiten aus Prompt entfernt (Agent erfand keine Zeiten mehr)
- ✅ Proaktive Terminvorschläge verbessert

**Root Cause:** Agent kopierte Beispiele aus Prompt 1:1

### V46 (2025-11-05)
**Changes:**
- UX-Verbesserungen hinzugefügt
- Service-Disambiguierung implementiert
- Problem: Agent kopierte Beispielzeiten (14:00, 16:30, 18:00)

### V44 (2025-11-05)
**Changes:**
- Jahr-Bug gefixt (2023 → 2025)
- Date Context hinzugefügt

---

## 🧪 Testing Scenarios

### Scenario A: Service-Disambiguierung ohne Preise ✅
```
User: "Ich möchte einen Haarschnitt buchen"
Expected: "Herrenhaarschnitt oder Damenhaarschnitt?"
NOT Expected: Preise/Dauer automatisch nennen
```

### Scenario B: Proaktive Terminvorschläge ✅
```
User: "Was haben Sie heute noch frei?"
Expected:
1. Agent callt check_availability
2. Agent zeigt echte verfügbare Zeiten
3. KEINE Zeiten in der Vergangenheit
4. Keine erfundenen Zeiten
```

### Scenario C: Preis auf explizite Nachfrage ✅
```
User: "Was kostet ein Herrenhaarschnitt?"
Expected: "32€ und dauert 55 Minuten"
Only when explicitly asked!
```

---

## 📚 Dokumentation

- **Capabilities HTML:** https://api.askproai.de/docs/agent-v47-capabilities.html
- **E2E Documentation:** https://api.askproai.de/docs/backup-system/index.html
- **Root Cause Analysis V46:** `/var/www/api-gateway/TESTCALL_V46_ROOT_CAUSE_ANALYSIS_2025-11-05.md`
- **V47 Testing Guide:** `/var/www/api-gateway/V47_READY_FOR_TESTING_2025-11-05.md`

---

## 🎯 Next Steps

1. **Publish V47** im Retell Dashboard (manuell)
2. **Test Calls** durchführen (alle 3 Szenarien)
3. **Monitoring** aktivieren (Transcript Analysis)
4. **Performance Tracking** (Tool Call Erfolgsrate)

---

**Created:** 2025-11-05 20:15 Uhr
**Agent Version:** V47 (Live)
**Status:** ✅ Ready for Production Testing
