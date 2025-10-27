# Retell Test Cases für V17 - AskPro AI Terminbuchung

## Test Case 1: Erfolgreiche Terminbuchung (Neukunde)

**Name:** Erfolgreiche Terminbuchung - Neukunde

**User Prompt:**
```
## Identität
Dein Name ist Michael Schmidt.
Du rufst zum ersten Mal bei AskPro AI an.
Deine Telefonnummer ist +491234567890.

## Ziel
Du möchtest einen Termin für eine Beratung buchen.
Wunschtermin: Morgen um 14:00 Uhr

## Persönlichkeit
Du bist ein freundlicher und geduldiger Kunde. Du beantwortest Fragen klar und direkt.
Du bist kooperativ und folgst den Anweisungen des Agenten.

## Verhalten
- Nenne deinen Namen, wenn du danach gefragt wirst
- Gib die Dienstleistung an: "Beratung"
- Bestätige den Termin, wenn er verfügbar ist
- Sage "Ja" oder "Gerne", wenn der Agent fragt, ob er buchen soll
```

**Success Criteria:**
```
1. Agent begrüßt freundlich und fragt nach dem Anliegen
2. Agent sammelt Name, Dienstleistung, Datum und Uhrzeit
3. Agent ruft TOOL auf: check_availability_v17 (Log: "🔍 V17: Check Availability")
4. Agent präsentiert Verfügbarkeit: "Der Termin ist verfügbar. Soll ich das für Sie buchen?"
5. Nach Bestätigung: Agent ruft TOOL auf: book_appointment_v17 (Log: "✅ V17: Book Appointment")
6. Agent bestätigt erfolgreiche Buchung mit allen Details
7. Termin wird in Datenbank angelegt
8. Gesamtdauer < 90 Sekunden
```

**Test Variables:**
- `from_number`: +491234567890
- `customer_name`: Michael Schmidt
- `test_date`: [MORGEN im Format DD.MM.YYYY]
- `test_time`: 14:00

---

## Test Case 2: Bekannter Kunde (Hansi) - Direkte Buchung

**Name:** Bekannter Kunde - Schnelle Buchung

**User Prompt:**
```
## Identität
Dein Name ist Hansi (bekannter Stammkunde).
Deine Telefonnummer wird übertragen: +491604366218

## Ziel
Du möchtest schnell einen Termin buchen.
Wunschtermin: Übermorgen um 10:00 Uhr für eine Beratung

## Persönlichkeit
Du bist ein Stammkunde und kennst den Prozess. Du bist ungeduldig und möchtest es schnell erledigen.
Du erwartest, dass der Agent dich erkennt und NICHT nach deinem Namen fragt.

## Verhalten
- Sage direkt: "Ich brauche einen Termin für eine Beratung übermorgen um 10 Uhr"
- Wenn der Agent nach deinem Namen fragt: Sei überrascht ("Ihr kennt mich doch?")
- Bestätige schnell mit "Ja" wenn verfügbar
```

**Success Criteria:**
```
1. Agent erkennt Telefonnummer und begrüßt OHNE nach Namen zu fragen
2. Agent sagt etwas wie: "Guten Tag! Wie kann ich Ihnen helfen?"
3. Agent sammelt Dienstleistung, Datum, Uhrzeit (KEIN Name!)
4. Tool-Aufruf: check_availability_v17
5. Agent präsentiert Verfügbarkeit
6. Tool-Aufruf: book_appointment_v17 nach Bestätigung
7. Erfolgreiche Buchung
8. Gesamtdauer < 60 Sekunden (schneller wegen bekanntem Kunden)
```

**Test Variables:**
- `from_number`: +491604366218
- `customer_name`: Hansi (wird automatisch erkannt)
- `test_date`: [ÜBERMORGEN im Format DD.MM.YYYY]
- `test_time`: 10:00

---

## Test Case 3: Anonymer Anrufer - Name-Collection

**Name:** Anonymer Anrufer - Name erfassen

**User Prompt:**
```
## Identität
Du rufst mit unterdrückter Nummer an (anonym).
Dein Name ist Julia Meier.

## Ziel
Du möchtest einen Termin für eine Beratung buchen.
Wunschtermin: Nächste Woche Montag um 15:00 Uhr

## Persönlichkeit
Du bist etwas vorsichtig und zurückhaltend. Du zögerst kurz, bevor du deinen Namen nennst.

## Verhalten
- Sage zuerst nur: "Ich möchte einen Termin buchen"
- Wenn nach dem Namen gefragt wird: Zögere kurz, dann nenne "Julia Meier"
- Bestätige den Termin, wenn verfügbar
```

**Success Criteria:**
```
1. Agent erkennt anonymen Anruf
2. Agent fragt IMMER nach dem Namen (Critical!)
3. Agent sammelt Name, Dienstleistung, Datum, Uhrzeit
4. Tool-Aufruf: check_availability_v17
5. Agent präsentiert Verfügbarkeit
6. Tool-Aufruf: book_appointment_v17 nach Bestätigung
7. Erfolgreiche Buchung mit erfasstem Namen
```

**Test Variables:**
- `from_number`: anonymous
- `customer_name`: Julia Meier (muss erfasst werden)
- `test_date`: [NÄCHSTER MONTAG im Format DD.MM.YYYY]
- `test_time`: 15:00

---

## Test Case 4: Termin nicht verfügbar - Alternativen

**Name:** Ausgebuchter Termin - Alternative anbieten

**User Prompt:**
```
## Identität
Dein Name ist Thomas Weber.
Deine Telefonnummer ist +491555123456.

## Ziel
Du möchtest einen Termin für eine Beratung buchen.
Wunschtermin: Heute um 11:00 Uhr (wahrscheinlich ausgebucht)

## Persönlichkeit
Du bist flexibel und offen für Alternativen.

## Verhalten
- Nenne deinen Wunschtermin: "Heute um 11 Uhr"
- Wenn nicht verfügbar: Frage nach Alternativen
- Wähle eine Alternative aus den Vorschlägen
- Bestätige die Alternative
```

**Success Criteria:**
```
1. Agent sammelt Name, Dienstleistung, Datum, Uhrzeit
2. Tool-Aufruf: check_availability_v17
3. Agent sagt: "Leider ist dieser Termin nicht verfügbar"
4. Agent bietet Alternativen an (aus API-Response)
5. User wählt Alternative
6. Tool-Aufruf: book_appointment_v17 für Alternative
7. Erfolgreiche Buchung des alternativen Termins
```

**Test Variables:**
- `from_number`: +491555123456
- `customer_name`: Thomas Weber
- `test_date`: [HEUTE im Format DD.MM.YYYY]
- `test_time`: 11:00

---

## Test Case 5: Termin umbuchen

**Name:** Bestehenden Termin umbuchen

**User Prompt:**
```
## Identität
Dein Name ist Sarah Müller.
Du bist eine bekannte Kundin mit bestehendem Termin.
Deine Telefonnummer ist +491777888999.

## Ziel
Du möchtest deinen bestehenden Termin verschieben.
Aktueller Termin: Morgen um 14:00 Uhr
Neuer Wunschtermin: Übermorgen um 16:00 Uhr

## Persönlichkeit
Du bist höflich aber bestimmt. Du hast einen wichtigen Grund für die Umbuchung.

## Verhalten
- Sage: "Ich muss meinen Termin verschieben"
- Nenne den aktuellen Termin wenn gefragt
- Gib den neuen Wunschtermin an
- Bestätige die Umbuchung
```

**Success Criteria:**
```
1. Agent erkennt Umbuchungs-Absicht
2. Agent fragt nach aktuellem Termin
3. Agent fragt nach neuem Wunschtermin
4. Tool-Aufruf: reschedule_appointment
5. Agent bestätigt erfolgreiche Umbuchung
6. Beide Termine (alt & neu) werden genannt
```

**Test Variables:**
- `from_number`: +491777888999
- `customer_name`: Sarah Müller
- `old_date`: [MORGEN im Format DD.MM.YYYY]
- `old_time`: 14:00
- `new_date`: [ÜBERMORGEN im Format DD.MM.YYYY]
- `new_time`: 16:00

---

## Test Case 6: Termin stornieren

**Name:** Termin stornieren

**User Prompt:**
```
## Identität
Dein Name ist Klaus Fischer.
Du bist ein bekannter Kunde mit bestehendem Termin.
Deine Telefonnummer ist +491666555444.

## Ziel
Du möchtest deinen Termin stornieren.
Termin: Übermorgen um 11:00 Uhr

## Persönlichkeit
Du bist sachlich und direkt. Du bedauerst die Absage.

## Verhalten
- Sage direkt: "Ich muss meinen Termin absagen"
- Nenne den Termin wenn gefragt: "Übermorgen um 11 Uhr"
- Bestätige die Stornierung
- Sage "Nein danke" wenn nach einem neuen Termin gefragt wird
```

**Success Criteria:**
```
1. Agent erkennt Stornierungsabsicht
2. Agent fragt nach dem zu stornierenden Termin
3. Tool-Aufruf: cancel_appointment
4. Agent bestätigt Stornierung
5. Agent bietet optional neuen Termin an
6. Agent verabschiedet sich höflich
```

**Test Variables:**
- `from_number`: +491666555444
- `customer_name`: Klaus Fischer
- `appointment_date`: [ÜBERMORGEN im Format DD.MM.YYYY]
- `appointment_time`: 11:00

---

## Test Case 7: Außerhalb Öffnungszeiten

**Name:** Anruf außerhalb Geschäftszeiten

**User Prompt:**
```
## Identität
Dein Name ist Anna Becker.
Du rufst am Sonntagabend um 20:00 Uhr an.
Deine Telefonnummer ist +491888999000.

## Ziel
Du möchtest einen Termin buchen, weißt aber nicht, dass geschlossen ist.

## Persönlichkeit
Du bist verständnisvoll, aber auch etwas enttäuscht.

## Verhalten
- Versuche normal einen Termin zu buchen
- Akzeptiere die Information über Geschäftszeiten
- Verabschiede dich höflich
```

**Success Criteria:**
```
1. Agent erkennt Anruf außerhalb Öffnungszeiten (Policy Check)
2. Agent informiert höflich über Geschäftszeiten
3. Agent bietet an, während Geschäftszeiten anzurufen
4. Agent verabschiedet sich freundlich
5. KEINE Terminbuchung erfolgt
```

**Test Variables:**
- `from_number`: +491888999000
- `customer_name`: Anna Becker
- `current_time`: 20:00 (Sonntag)
- `policy_active`: office_hours_redirect

---

## Test Case 8: V17 Tool-Invocation Stress Test

**Name:** V17 Funktionstest - Explizite Tool-Aufrufe

**User Prompt:**
```
## Identität
Dein Name ist Test User.
Deine Telefonnummer ist +491000000001.

## Ziel
Dieser Test soll EXPLIZIT verifizieren, dass die V17 Tools aufgerufen werden.
Wunschtermin: Morgen um 13:00 Uhr für eine Beratung

## Persönlichkeit
Du bist kooperativ und folgst exakt den Anweisungen.

## Verhalten
- Antworte präzise und schnell
- Bestätige sofort mit "Ja, bitte buchen"
```

**Success Criteria:**
```
CRITICAL - Diese Punkte MÜSSEN alle erfüllt sein:

1. ✅ Tool-Aufruf erfolgt: check_availability_v17
   - Log-Eintrag: "🔍 V17: Check Availability (bestaetigung=false)"
   - API-Call an: /api/retell/v17/check-availability

2. ✅ Agent präsentiert Ergebnis:
   - "Der Termin ist verfügbar. Soll ich das für Sie buchen?"
   - WARTET auf Bestätigung (bucht NICHT automatisch!)

3. ✅ Tool-Aufruf erfolgt: book_appointment_v17
   - Log-Eintrag: "✅ V17: Book Appointment (bestaetigung=true)"
   - API-Call an: /api/retell/v17/book-appointment

4. ✅ Buchung erfolgreich:
   - Termin in Datenbank angelegt
   - Alle Parameter korrekt (Name, Datum, Uhrzeit, Dienstleistung)

5. ✅ Performance:
   - Check-Tool Response < 2 Sekunden
   - Book-Tool Response < 2 Sekunden

FAILURE CRITERIA (Test schlägt fehl wenn):
❌ Tool wird NICHT aufgerufen (wie in V15/V16: 0% Success Rate)
❌ Agent sagt "ich prüfe" aber Tool-Call fehlt in Logs
❌ Automatische Buchung ohne Bestätigung
❌ Falsche Parameter an Tools übergeben
```

**Test Variables:**
- `from_number`: +491000000001
- `customer_name`: Test User
- `test_date`: [MORGEN im Format DD.MM.YYYY]
- `test_time`: 13:00
- `service`: Beratung

---

## Import-Format für Retell Dashboard

Für jeden Test Case oben:

1. **Name** = "Name" Feld
2. **User Prompt** = Kompletter Text unter "User Prompt"
3. **Success Criteria** = Kompletter Text unter "Success Criteria"
4. **Test Variables** = Als Dynamic Variables eintragen:

Beispiel für Test Case 1:
```
Variable Name: from_number
Test Value: +491234567890

Variable Name: customer_name
Test Value: Michael Schmidt

Variable Name: test_date
Test Value: 23.10.2025

Variable Name: test_time
Test Value: 14:00
```

---

## Priorität der Tests

**Must-Run (nach jedem Deployment):**
1. Test Case 8 (V17 Tool-Invocation) - KRITISCH!
2. Test Case 1 (Erfolgreiche Buchung Neukunde)
3. Test Case 2 (Bekannter Kunde)

**Should-Run (wöchentlich):**
4. Test Case 3 (Anonymer Anrufer)
5. Test Case 4 (Alternativen)
6. Test Case 5 (Umbuchung)

**Nice-to-Have (monatlich):**
7. Test Case 6 (Stornierung)
8. Test Case 7 (Außerhalb Öffnungszeiten)

---

## Expected Results Matrix

| Test Case | Agent Version | Tool Calls | Duration | Status |
|-----------|---------------|------------|----------|--------|
| TC1 - Neukunde | ≥14 | 2 (check, book) | <90s | ✅ Pass |
| TC2 - Bekannt | ≥14 | 2 (check, book) | <60s | ✅ Pass |
| TC3 - Anonym | ≥14 | 2 (check, book) | <90s | ✅ Pass |
| TC4 - Alternative | ≥14 | 2 (check, book) | <120s | ✅ Pass |
| TC5 - Umbuchung | ≥14 | 1 (reschedule) | <60s | ✅ Pass |
| TC6 - Stornierung | ≥14 | 1 (cancel) | <45s | ✅ Pass |
| TC7 - Closed | ≥14 | 0 (policy block) | <30s | ✅ Pass |
| TC8 - V17 Critical | ≥14 | 2 (check, book) | <90s | ✅ MUST PASS |

---

## Debugging Failed Tests

### If Tool NOT called (V15/V16 Problem):
```bash
# Check agent version
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "agent_version"

# Expected: agent_version >= 14
# If = 13: CDN propagation not complete yet, wait 15 more minutes
```

### If Tool called but wrong parameters:
```bash
# Check V17 wrapper logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "V17:"

# Should see:
# "🔍 V17: Check Availability (bestaetigung=false)"
# "✅ V17: Book Appointment (bestaetigung=true)"
```

### If booking fails:
```bash
# Check database
psql -U postgres -d askproai_db -c "SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5;"
```

---

**Erstellt:** 2025-10-22
**Version:** V17 Flow Version 18
**Nächstes Update:** Nach User-Feedback aus ersten Tests
