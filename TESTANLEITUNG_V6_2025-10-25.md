# 🧪 Test-Anleitung V6 - Alle Fixes

## 📞 Telefonnummer: +493033081738

---

## ✅ Test 1: Happy Path (alle Fixes gleichzeitig)

**Was wird getestet:**
- ✅ UX #1: Keine redundanten Fragen
- ✅ UX #2: Direktes Buchen nach Bestätigung
- ✅ Bug #9: Korrekte Service-Auswahl
- ✅ Bug #3: Email-Versand

**Durchführung:**

1. **Anrufen:** +493033081738

2. **Sagen:**
   > "Ich möchte einen Herrenhaarschnitt für heute 15 Uhr, mein Name ist Hans Schuster"

3. **Erwartetes Verhalten:**
   - ✅ Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."
   - ❌ Agent fragt NICHT nochmal nach Name/Datum/Uhrzeit

4. **Agent sagt:**
   > "Der Termin am [Datum] um 15:00 Uhr für Herrenhaarschnitt ist verfügbar. Soll ich den Termin für Sie buchen?"

5. **Antworten:**
   > "Ja, bitte"

6. **Erwartetes Verhalten:**
   - ✅ Agent bucht SOFORT (kein erneutes Fragen)
   - ✅ Agent bestätigt: "Termin gebucht, Email gesendet an..."

**Log-Verifikation:**
```bash
# Terminal 1: Logs live verfolgen
tail -f storage/logs/laravel.log | grep -E "(Service matched|Appointment created|Email sent)"

# Erwartete Log-Einträge:
✅ Service matched successfully: Herrenhaarschnitt (ID: 42)
✅ Appointment created successfully
✅ Sending appointment confirmation email
```

**Ergebnis:** PASS / FAIL
**Notizen:** _________________

---

## ✅ Test 2: Service-Auswahl (Bug #9)

**Was wird getestet:**
- ✅ Bug #9: Herrenhaarschnitt → Service ID 42
- ✅ Bug #9: Damenhaarschnitt → Service ID 41

### Test 2a: Herrenhaarschnitt

1. **Anrufen:** +493033081738
2. **Sagen:** "Herrenhaarschnitt für morgen 14 Uhr"
3. **Log checken:**
   ```bash
   grep "Service matched" storage/logs/laravel.log | tail -1
   ```
   Erwartung: `"matched_service_id": 42`

**Ergebnis:** PASS / FAIL
**Service ID:** _______

### Test 2b: Damenhaarschnitt

1. **Anrufen:** +493033081738
2. **Sagen:** "Damenhaarschnitt für morgen 14 Uhr"
3. **Log checken:**
   ```bash
   grep "Service matched" storage/logs/laravel.log | tail -1
   ```
   Erwartung: `"matched_service_id": 41`

**Ergebnis:** PASS / FAIL
**Service ID:** _______

### Test 2c: Fuzzy Match

1. **Anrufen:** +493033081738
2. **Sagen:** "Herren Haarschnitt" (mit Leerzeichen)
3. **Log checken:**
   Erwartung: `"matched_service_id": 42` (Fuzzy Match)

**Ergebnis:** PASS / FAIL

---

## ✅ Test 3: Weekend-Datum (Bug #2)

**Was wird getestet:**
- ✅ Bug #2: Kein 2-Tage-Shift bei Wochenenden

1. **Anrufen:** +493033081738

2. **Sagen:**
   > "Herrenhaarschnitt für Samstag 15 Uhr"

3. **Erwartetes Verhalten:**
   - ✅ Agent bietet Alternativen für Samstag
   - ❌ Agent verschiebt NICHT automatisch auf Montag

4. **Log checken:**
   ```bash
   grep "Skipping NEXT_WORKDAY strategy" storage/logs/laravel.log | tail -1
   ```

**Ergebnis:** PASS / FAIL
**Angebotene Alternativen:** _________________

---

## ✅ Test 4: Email-Versand (Bug #3)

**Was wird getestet:**
- ✅ Bug #3: Bestätigungs-Email

**Vorbedingung:** Erfolgreiche Buchung (Test 1)

1. **Queue worker starten (falls nicht läuft):**
   ```bash
   php artisan queue:work
   ```

2. **Email-Logs checken:**
   ```bash
   grep "Sending appointment confirmation email" storage/logs/laravel.log | tail -1
   ```

3. **Verifikation:**
   - ✅ Email in Logs: "Sending appointment confirmation email"
   - ✅ Queue Job processed (falls async)
   - ✅ Email empfangen (wenn Test-Email konfiguriert)

**Ergebnis:** PASS / FAIL
**Email Status:** _________________

---

## ✅ Test 5: State Persistence (UX #1)

**Was wird getestet:**
- ✅ UX #1: Agent merkt sich bereits gesagte Daten

### Test 5a: Alles auf einmal

1. **Sagen:** "Herrenhaarschnitt heute 15 Uhr, Hans Schuster"
2. **Erwartung:** KEINE weiteren Fragen

**Ergebnis:** PASS / FAIL

### Test 5b: Schrittweise

1. **Sagen:** "Ich möchte einen Herrenhaarschnitt"
2. **Agent fragt:** "Wie ist Ihr Name?"
3. **Sagen:** "Hans Schuster"
4. **Agent fragt:** "Für welchen Tag?"
5. **Sagen:** "Heute"
6. **Agent fragt:** "Um wie viel Uhr?"
7. **Sagen:** "15 Uhr"
8. **Erwartung:** Agent prüft Verfügbarkeit

**Ergebnis:** PASS / FAIL
**Anzahl Fragen:** _______ (sollte 3 sein: Name, Datum, Uhrzeit)

---

## 📊 TEST-ZUSAMMENFASSUNG

| Test | Status | Notizen |
|------|--------|---------|
| Test 1: Happy Path | ☐ PASS ☐ FAIL | |
| Test 2a: Herrenhaarschnitt | ☐ PASS ☐ FAIL | |
| Test 2b: Damenhaarschnitt | ☐ PASS ☐ FAIL | |
| Test 2c: Fuzzy Match | ☐ PASS ☐ FAIL | |
| Test 3: Weekend | ☐ PASS ☐ FAIL | |
| Test 4: Email | ☐ PASS ☐ FAIL | |
| Test 5a: Alles auf einmal | ☐ PASS ☐ FAIL | |
| Test 5b: Schrittweise | ☐ PASS ☐ FAIL | |

**Gesamt:** ____ / 8 Tests bestanden

---

## 🐛 Falls Probleme auftreten:

### Problem: Agent fragt trotzdem 3x nach Daten

**Diagnose:**
```bash
# Check ob dynamic variables gesetzt werden
grep "collected_dynamic_variables" storage/logs/laravel.log | tail -1
```

**Erwartung:** Variables sollten gefüllt sein
```json
{
  "customer_name": "Hans Schuster",
  "service_name": "Herrenhaarschnitt",
  "appointment_date": "heute",
  "appointment_time": "15:00"
}
```

### Problem: Falscher Service gebucht

**Diagnose:**
```bash
# Check service selection
grep "Service matched" storage/logs/laravel.log | tail -3
```

**Erwartung:**
- Herrenhaarschnitt → service_id: 42
- Damenhaarschnitt → service_id: 41

### Problem: Keine Buchung nach "Ja"

**Diagnose:**
```bash
# Check ob book_appointment aufgerufen wird
grep "book_appointment_v17" storage/logs/laravel.log | tail -3
```

**Erwartung:** Nach "Ja" sollte `book_appointment_v17` aufgerufen werden

---

## 🚀 Rollback bei Problemen

Falls V6 nicht funktioniert:

```bash
# Option 1: Zurück zu V4 (vor State Persistence)
# Retell Dashboard → Agent → Conversation Flow → Version 6 auswählen

# Option 2: Service Selection bleibt trotzdem aktiv (Backend-Fix)
# Nur Flow wird gerollt back
```

---

**Test-Datum:** _________________
**Getestet von:** _________________
**Ergebnis:** ☐ ALLE PASS ☐ TEILWEISE ☐ FEHLGESCHLAGEN
