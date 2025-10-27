# 🧪 Test-Anleitung V7 - Service Pinning Fix

## 📞 Telefonnummer: +493033081738

---

## ✅ PRIORITY 1: Bug #10 Verification (MUST TEST)

### Test 1: Herrenhaarschnitt Service Selection

**Was wird getestet:**
- ✅ Bug #10: Service Pinning verwendet korrekten Service
- ✅ Service ID 42 für "Herrenhaarschnitt" (nicht ID 41)
- ✅ Keine Cal.com 400 Fehler

**Durchführung:**

1. **Anrufen:** +493033081738

2. **Sagen:**
   > "Ich möchte einen Herrenhaarschnitt für heute 19 Uhr, mein Name ist Hans Schuster"

3. **Erwartetes Verhalten:**
   - ✅ Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."
   - ❌ Agent fragt NICHT nochmal nach Name/Datum/Uhrzeit

4. **Agent sagt:**
   > "Der Termin am [Datum] um 19:00 Uhr für Herrenhaarschnitt ist verfügbar. Soll ich den Termin für Sie buchen?"

5. **Antworten:**
   > "Ja, bitte"

6. **Erwartetes Verhalten:**
   - ✅ Agent bucht SOFORT (kein erneutes Fragen)
   - ✅ KEIN Cal.com Fehler
   - ✅ Agent bestätigt: "Termin gebucht, Email gesendet an..."

**Log-Verifikation:**
```bash
# Terminal 1: Service Selection überwachen
tail -f storage/logs/laravel.log | grep "Service matched by name"

# Erwartete Log-Einträge:
✅ Service matched by name (Bug #10 fix)
   matched_service_id: 42
   matched_service_name: "Herrenhaarschnitt"

✅ Service pinned for future calls in session
   service_id: 42
   pinned_from: "name_match"

✅ Using pinned service from call session
   pinned_service_id: 42
   service_name: "Herrenhaarschnitt"

✅ Appointment created successfully
```

**Ergebnis:** PASS / FAIL
**Service ID:** _______ (sollte 42 sein)
**Cal.com Error:** _______ (sollte KEINER sein)

---

## ✅ PRIORITY 2: Complete Happy Path (ALL Fixes)

### Test 2: Alle Fixes gleichzeitig

**Was wird getestet:**
- ✅ Bug #10: Korrekte Service-Auswahl (ID 42)
- ✅ UX #1: Keine redundanten Fragen
- ✅ UX #2: Direktes Buchen nach Bestätigung
- ✅ Bug #3: Email-Versand

**Durchführung:**

1. **Anrufen:** +493033081738

2. **Sagen:**
   > "Herrenhaarschnitt für heute 15 Uhr, Hans Schuster"

3. **Erwartetes Verhalten:**
   - ✅ Agent prüft OHNE weitere Fragen
   - ✅ Agent bietet Termin an
   - ❌ KEINE Fragen nach bereits gesagten Daten

4. **Sagen:** "Ja"

5. **Erwartetes Verhalten:**
   - ✅ Sofortige Buchung
   - ✅ Email-Bestätigung
   - ✅ Keine Fehler

**Log-Verifikation:**
```bash
tail -f storage/logs/laravel.log | grep -E "(Service matched|Appointment created|Email sent)"

# Erwartete Reihenfolge:
1. ✅ Service matched by name (Bug #10 fix): ID 42
2. ✅ Service pinned: ID 42
3. ✅ Using pinned service: ID 42
4. ✅ Appointment created successfully
5. ✅ Sending appointment confirmation email
```

**Ergebnis:** PASS / FAIL
**Anzahl Fragen:** _______ (sollte 0 sein)

---

## ✅ PRIORITY 3: Service-Unterscheidung

### Test 3a: Herrenhaarschnitt

1. **Anrufen:** +493033081738
2. **Sagen:** "Herrenhaarschnitt für morgen 14 Uhr"
3. **Log checken:**
   ```bash
   grep "matched_service_id" storage/logs/laravel.log | tail -1
   ```
   Erwartung: `"matched_service_id": 42`

**Ergebnis:** PASS / FAIL
**Service ID:** _______

### Test 3b: Damenhaarschnitt

1. **Anrufen:** +493033081738
2. **Sagen:** "Damenhaarschnitt für morgen 14 Uhr"
3. **Log checken:**
   ```bash
   grep "matched_service_id" storage/logs/laravel.log | tail -1
   ```
   Erwartung: `"matched_service_id": 41`

**Ergebnis:** PASS / FAIL
**Service ID:** _______

### Test 3c: Fuzzy Match

1. **Anrufen:** +493033081738
2. **Sagen:** "Herren Haarschnitt" (mit Leerzeichen)
3. **Log checken:**
   Erwartung: `"matched_service_id": 42` (Fuzzy Match funktioniert)

**Ergebnis:** PASS / FAIL

---

## ✅ PRIORITY 4: Weekend & Email

### Test 4: Weekend-Datum (Bug #2)

**Durchführung:**

1. **Anrufen:** +493033081738

2. **Sagen:**
   > "Herrenhaarschnitt für Samstag 15 Uhr"

3. **Erwartetes Verhalten:**
   - ✅ Agent bietet Samstag-Alternativen
   - ❌ Agent verschiebt NICHT automatisch auf Montag

4. **Log checken:**
   ```bash
   grep "Skipping NEXT_WORKDAY strategy" storage/logs/laravel.log | tail -1
   ```

**Ergebnis:** PASS / FAIL
**Angebotener Tag:** _______ (sollte Samstag sein)

### Test 5: Email-Versand (Bug #3)

**Vorbedingung:** Erfolgreiche Buchung (Test 1 oder 2)

1. **Email-Logs checken:**
   ```bash
   grep "Sending appointment confirmation email" storage/logs/laravel.log | tail -1
   ```

2. **Verifikation:**
   - ✅ Email in Logs: "Sending appointment confirmation email"
   - ✅ Email empfangen (wenn Test-Email konfiguriert)

**Ergebnis:** PASS / FAIL
**Email Status:** _________________

---

## 📊 TEST-ZUSAMMENFASSUNG

| Test | Status | Service ID | Notizen |
|------|--------|------------|---------|
| Test 1: Herrenhaarschnitt 19:00 | ☐ PASS ☐ FAIL | _____ | |
| Test 2: Happy Path komplett | ☐ PASS ☐ FAIL | _____ | |
| Test 3a: Herrenhaarschnitt | ☐ PASS ☐ FAIL | _____ | |
| Test 3b: Damenhaarschnitt | ☐ PASS ☐ FAIL | _____ | |
| Test 3c: Fuzzy Match | ☐ PASS ☐ FAIL | _____ | |
| Test 4: Weekend | ☐ PASS ☐ FAIL | N/A | |
| Test 5: Email | ☐ PASS ☐ FAIL | N/A | |

**Gesamt:** ____ / 7 Tests bestanden

---

## 🐛 DEBUGGING BEI PROBLEMEN

### Problem: Service ID 41 statt 42

**Diagnose:**
```bash
# Check ob Fix aktiv ist
grep "Bug #10 fix" storage/logs/laravel.log | tail -3

# Check Cache
php artisan cache:clear
echo "Cache cleared, try again"
```

**Erwartung:** Nach Cache-Clear sollte neuer Code greifen

### Problem: Cal.com 400 Error

**Diagnose:**
```bash
# Check welcher Service verwendet wurde
grep "Using pinned service" storage/logs/laravel.log | tail -1

# Check Cal.com Error Details
grep "Cal.com API request failed" storage/logs/laravel.log | tail -5
```

**Erwartung:**
- Service ID sollte 42 sein (nicht 41)
- Kein 400 Error wenn richtiger Service

### Problem: Buchung funktioniert nicht

**Diagnose:**
```bash
# Full flow trace
grep "call_60b8d08c" storage/logs/laravel.log | grep -E "(Service|Appointment|booking)"
```

**Erwartung:**
1. Service matched: ID 42
2. Service pinned: ID 42
3. Appointment created
4. Email sent

---

## 🚀 QUICK START

### Schnellster Test (30 Sekunden)

```bash
# Terminal 1: Logs live verfolgen
tail -f storage/logs/laravel.log | grep -E "(Service matched|matched_service_id|Appointment created)"

# Terminal 2: Testanruf machen
# Call: +493033081738
# Say: "Herrenhaarschnitt für heute 19 Uhr, Hans Schuster"

# Erwartete Ausgabe in Terminal 1:
✅ Service matched by name (Bug #10 fix)
   matched_service_id: 42
✅ Appointment created successfully
```

---

## ✅ ERFOLGS-KRITERIEN

**V7 ist erfolgreich wenn:**
- ☐ "Herrenhaarschnitt" → Service ID 42 (nicht 41)
- ☐ Keine Cal.com 400 Fehler
- ☐ Buchung erfolgreich erstellt
- ☐ Email versendet
- ☐ Keine redundanten Fragen
- ☐ Direktes Buchen nach "Ja"

**Akzeptanz:** 6/6 Kriterien erfüllt = V7 erfolgreich deployed

---

## 📋 ROLLBACK BEI FEHLSCHLAG

Falls V7 nicht funktioniert:

```bash
# Code rollback
git checkout HEAD~1 app/Http/Controllers/RetellFunctionCallHandler.php

# Cache clear
php artisan cache:clear

# Result: Bug #10 returns, aber V6 Fixes bleiben aktiv
```

---

**Test-Datum:** _________________
**Getestet von:** _________________
**Ergebnis:** ☐ ALLE PASS ☐ TEILWEISE ☐ FEHLGESCHLAGEN
**Service ID bei Herrenhaarschnitt:** _______ (Soll: 42)
