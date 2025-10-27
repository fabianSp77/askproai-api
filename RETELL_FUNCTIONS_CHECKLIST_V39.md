# ✅ RETELL FUNCTIONS CHECKLIST V39

**Agent:** Conversation Flow Agent Friseur 1
**Flow Version:** V39
**Datum:** 2025-10-24

---

## 🎯 QUICK START VALIDATION

### PHASE 1: Dashboard Functions prüfen (5 Minuten)

Gehe zu: `https://dashboard.retellai.com` → Global Settings → Functions Tab

Prüfe ob ALLE 6 Functions vorhanden sind:

- [ ] ✅ `check_availability_v17` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 10000ms
  - [ ] Parameters: datum, uhrzeit, dienstleistung (alle required)

- [ ] ✅ `book_appointment_v17` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 10000ms
  - [ ] Parameters: name, datum, uhrzeit, dienstleistung (required)

- [ ] ✅ `get_appointments` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 6000ms
  - [ ] Parameters: telefonnummer (optional)

- [ ] ✅ `get_alternatives` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 10000ms
  - [ ] Parameters: datum, dienstleistung (both required)

- [ ] ✅ `reschedule_appointment` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 10000ms
  - [ ] Parameters: neue_datum, neue_uhrzeit (required)

- [ ] ✅ `cancel_appointment` vorhanden
  - [ ] URL: `https://api.askproai.de/api/webhooks/retell/function`
  - [ ] Timeout: 8000ms
  - [ ] Parameters: datum (optional: grund)

**initialize_call NICHT in Liste?**
→ ✅ NORMAL! Ist hardcoded im PHP, braucht kein Dashboard Tool.

---

### PHASE 2: Flow Canvas Nodes prüfen (5 Minuten)

Öffne: `Conversation Flow Agent Friseur 1` → Flow Canvas

Prüfe Function Nodes:

- [ ] ✅ `func_00_initialize` (Initialize Call)
  - [ ] tool_id: `tool-initialize-call`
  - [ ] speak_during_execution: `false`
  - [ ] wait_for_result: `true`
  - [ ] Edge zu: `node_02_customer_routing`

- [ ] ✅ `func_check_availability` (Verfügbarkeit prüfen Explicit)
  - [ ] tool_id: `tool-v17-check-availability`
  - [ ] speak_during_execution: `true` ✅ WICHTIG!
  - [ ] wait_for_result: `true`
  - [ ] Edge zu: `node_present_availability`

- [ ] ✅ `func_book_appointment` (Termin buchen Explicit)
  - [ ] tool_id: `tool-v17-book-appointment`
  - [ ] speak_during_execution: `true` ✅ WICHTIG!
  - [ ] wait_for_result: `true`
  - [ ] Edges zu: `node_14_success_goodbye` und `end_node_error`

- [ ] ✅ `func_get_appointments` (Termine abrufen)
  - [ ] tool_id: `tool-get-appointments`
  - [ ] speak_during_execution: `true`
  - [ ] wait_for_result: `true`
  - [ ] Edge zu: `node_appointments_display`

- [ ] ✅ `func_reschedule_execute` (Verschieben ausführen)
  - [ ] tool_id: `tool-reschedule-appointment`
  - [ ] speak_during_execution: `true`
  - [ ] wait_for_result: `true`
  - [ ] Edges zu: `node_reschedule_success`, `node_policy_violation_handler`, `node_99_error_goodbye`

- [ ] ✅ `func_cancel_execute` (Stornierung ausführen)
  - [ ] tool_id: `tool-cancel-appointment`
  - [ ] speak_during_execution: `true`
  - [ ] wait_for_result: `true`
  - [ ] Edges zu: `node_cancel_success`, `node_policy_violation_handler`, `node_99_error_goodbye`

**Legacy Nodes (können ignoriert werden):**
- `func_08_availability_check` (alt: verwendet tool-collect-appointment)
- `func_09c_final_booking` (alt: verwendet tool-collect-appointment)

---

### PHASE 3: Test Call durchführen (10 Minuten)

#### Test 1: Neue Buchung (P0 Critical)

- [ ] Anruf starten: `+493033081738`
- [ ] Agent begrüßt dich (initialize_call funktioniert)
- [ ] Sage: "Termin morgen um 11 Uhr für Herrenhaarschnitt"
- [ ] Agent prüft Verfügbarkeit (check_availability_v17 wird aufgerufen)
- [ ] Agent sagt ob verfügbar
- [ ] Bestätige mit "Ja"
- [ ] Agent bucht Termin (book_appointment_v17 wird aufgerufen)
- [ ] Agent bestätigt Buchung

**Erwartetes Ergebnis:**
- ✅ Kein "Einen Moment bitte..." länger als 3 Sekunden
- ✅ Termin erscheint in Admin Panel
- ✅ Function Traces in Database

**Wenn fehlschlägt:**
→ Siehe TROUBLESHOOTING Abschnitt in `RETELL_CONVERSATION_FLOW_FUNCTIONS_COMPLETE_GUIDE.md`

#### Test 2: Termine abfragen (P1 High)

- [ ] Anruf starten
- [ ] Sage: "Welche Termine habe ich?"
- [ ] Agent ruft get_appointments auf
- [ ] Agent listet deine Termine

**Erwartetes Ergebnis:**
- ✅ Termine werden korrekt aufgelistet
- ✅ Datum und Uhrzeit sind verständlich

#### Test 3: Alternativen (P1 High)

- [ ] Anruf starten
- [ ] Sage: "Termin morgen um 7 Uhr" (sollte nicht verfügbar sein)
- [ ] Agent prüft Verfügbarkeit
- [ ] Agent bietet Alternativen an

**Erwartetes Ergebnis:**
- ✅ Agent sagt "nicht verfügbar"
- ✅ Agent bietet 3-5 alternative Zeiten an

---

### PHASE 4: Logs & Monitoring prüfen (5 Minuten)

#### Admin Panel Check:

Gehe zu: `https://api.askproai.de/admin/retell-call-sessions`

- [ ] Dein Test Call erscheint in Liste
- [ ] Status: "ended"
- [ ] Duration: ~30-60 Sekunden
- [ ] Klicke auf Call → Detail View
- [ ] Sehe Function Traces:
  - [ ] initialize_call (✅ success)
  - [ ] check_availability_v17 (✅ success)
  - [ ] book_appointment_v17 (✅ success)

#### Laravel Logs Check:

```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

- [ ] Keine ERROR Level Messages
- [ ] Function Calls zeigen:
  ```
  🔧 Function routing
  original_name: check_availability_v17
  base_name: check_availability
  version_stripped: true
  ```
- [ ] Keine "Unknown function" Errors

#### Database Check (Optional):

```sql
SELECT * FROM retell_function_traces
WHERE call_id = 'call_xxx'
ORDER BY created_at DESC;
```

- [ ] Alle aufgerufenen Functions haben Einträge
- [ ] `status` = 'success' für alle
- [ ] `latency_ms` < 2000ms für die meisten

---

## 🚨 HÄUFIGE PROBLEME & QUICK FIXES

### Problem: Functions nicht im Dashboard sichtbar

**Check:**
```
❓ Bist du im richtigen Workspace?
   → Oben rechts: Workspace Dropdown

❓ Hast du die richtige Permission?
   → Nur Admins können Functions sehen

❓ Verwendest du Conversation Flow Agent?
   → Single/Multi Prompt Agents haben andere UI
```

**Fix:**
```
1. Wechsle zu korrektem Workspace
2. Oder: Erstelle Functions neu (siehe Guide)
```

---

### Problem: Function wird aufgerufen aber 404 Error

**Check:**
```
❓ URL korrekt?
   ✅ RICHTIG: https://api.askproai.de/api/webhooks/retell/function
   ❌ FALSCH: https://api.askproai.de/webhooks/retell (ohne /api/)
   ❌ FALSCH: https://api.askproai.de/api/webhooks/retell (ohne /function)

❓ Endpoint erreichbar?
   curl https://api.askproai.de/api/webhooks/retell/function
   (sollte 405 Method Not Allowed zurückgeben, NICHT 404)
```

**Fix:**
```
1. Korrigiere URL in Dashboard Function Settings
2. Save
3. Re-publish Agent
4. Test Call
```

---

### Problem: Parameters fehlen im PHP Handler

**Check:**
```
❓ Extract Dynamic Variable Node vorhanden?
   → Muss VOR Function Node kommen!
   → Variablen Namen EXAKT wie in Function Parameters

❓ Parameters required aber User hat nicht geliefert?
   → Setze nur wirklich required als required
   → Optionals in Function aber nicht in required[]

❓ Type Mismatch?
   ✅ RICHTIG: "type": "string" für alle Datums/Zeit Werte
   ❌ FALSCH: "type": "date" (gibt's nicht in JSON Schema!)
```

**Fix:**
```
1. Füge Extract Dynamic Variable Node hinzu:
   - Name: datum, Type: string
   - Name: uhrzeit, Type: string
   - Name: dienstleistung, Type: string (oder enum)

2. Verbinde Conversation Node → Extract Node → Function Node

3. Test Call
```

---

### Problem: Agent spricht nicht während langer Functions

**Check:**
```
❓ speak_during_execution = false?
   → Sollte true sein für lange Operations!

❓ Instruction leer?
   → Setze Text wie "Einen Moment bitte..."
```

**Fix:**
```
1. Selektiere Function Node
2. speak_during_execution: ✅ AN
3. Instruction: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
4. Save
5. Re-publish
```

---

## 📊 SUCCESS CRITERIA

### ✅ Du bist DONE wenn:

- [ ] Alle 6 Functions im Dashboard vorhanden
- [ ] Alle Function Nodes im Flow korrekt verknüpft
- [ ] Test Call 1 (Buchung) erfolgreich
- [ ] Test Call 2 (Abfrage) erfolgreich
- [ ] Test Call 3 (Alternativen) erfolgreich
- [ ] Admin Panel zeigt alle Calls mit Function Traces
- [ ] Laravel Logs zeigen keine Errors
- [ ] Du verstehst wie Functions hinzugefügt/bearbeitet werden

### 🎉 CELEBRATION TIME!

Wenn alle Checkboxen ✅ sind:
→ Dein Retell Conversation Flow Agent ist PRODUCTION READY!
→ Du kannst jetzt eigenständig Functions verwalten!
→ Du verstehst das Zwei-Schritt-System (Global Functions + Function Nodes)!

---

## 📚 NEXT STEPS

### Weitere Optimierungen:

1. **Legacy Nodes entfernen** (optional)
   - `func_08_availability_check` löschen
   - `func_09c_final_booking` löschen
   - `tool-collect-appointment` löschen

2. **Error Handling verbessern**
   - Mehr Error Nodes für specific Errors
   - User-freundlichere Error Messages

3. **Monitoring einrichten**
   - Alert bei Function Failures
   - Dashboard für Success Rate
   - Latency Tracking

4. **A/B Testing**
   - Test verschiedene Instruction Texts
   - Optimiere speak_during_execution Settings
   - Measure User Satisfaction

---

**Erstellt:** 2025-10-24 08:50
**Version:** 1.0
**Für:** Agent Friseur 1 V39
**Status:** Production Checklist
