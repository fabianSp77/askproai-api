# Retell Dashboard Test - Quick Start Guide

## So testest du V17 im Retell Dashboard

### 1. Dashboard öffnen
```
https://dashboard.retellai.com/
```

### 2. Zum Test-Bereich navigieren
- Agent auswählen: "Conversation Flow Agent" (agent_616d645570ae613e421edb98e7)
- Tab: "Test" oder "Simulations"

### 3. Test Case hinzufügen

Klicke auf **"+ Add Test Case"** oder **"+ Test Case"** Button

---

## KRITISCHER TEST (ZUERST AUSFÜHREN!)

### Test Case 8: V17 Tool-Invocation Test

**Name:**
```
V17 Tool-Invocation Test
```

**User Prompt:**
```
## Identität
Dein Name ist Test User.
Deine Telefonnummer ist +491000000001.

## Ziel
Termin buchen für eine Beratung morgen um 13:00 Uhr.

## Persönlichkeit
Kooperativ, antwortet präzise und schnell.

## Verhalten
- Sage: "Ich möchte einen Termin für eine Beratung buchen"
- Nenne deinen Namen wenn gefragt: "Test User"
- Sage den Wunschtermin: "Morgen um 13 Uhr"
- Bestätige sofort mit "Ja, bitte buchen" wenn der Agent fragt
```

**Success Criteria:**
```
CRITICAL - ALLE Punkte MÜSSEN erfüllt sein:

✅ 1. Agent ruft check_availability_v17 Tool auf
✅ 2. Agent sagt: "Der Termin ist verfügbar. Soll ich das für Sie buchen?"
✅ 3. Agent WARTET auf Bestätigung (bucht NICHT automatisch!)
✅ 4. Nach "Ja": Agent ruft book_appointment_v17 Tool auf
✅ 5. Agent bestätigt erfolgreiche Buchung
✅ 6. Gesamtdauer < 90 Sekunden

FAILURE wenn:
❌ Tool wird NICHT aufgerufen (wie in V15/V16)
❌ Agent sagt "ich prüfe" aber kein Tool-Call
❌ Automatische Buchung ohne Bestätigung
```

**Test Variables:**
```
Variable Name: from_number
Test Value: +491000000001

Variable Name: customer_name
Test Value: Test User

Variable Name: test_date
Test Value: 23.10.2025      (Morgen anpassen!)

Variable Name: test_time
Test Value: 13:00

Variable Name: service
Test Value: Beratung
```

**LLM Setting:** GPT-4o Mini (Standard)

---

## EINFACHER ERFOLGS-TEST

### Test Case 1: Erfolgreiche Buchung Neukunde

**Name:**
```
Erfolgreiche Terminbuchung - Neukunde
```

**User Prompt:**
```
## Identität
Dein Name ist Michael Schmidt.
Telefonnummer: +491234567890
Du rufst zum ersten Mal an.

## Ziel
Termin buchen für eine Beratung morgen um 14:00 Uhr.

## Persönlichkeit
Freundlich, geduldig, kooperativ.

## Verhalten
- Sage: "Guten Tag, ich möchte einen Termin buchen"
- Nenne deinen Namen wenn gefragt: "Michael Schmidt"
- Sage: "Für eine Beratung"
- Wunschtermin: "Morgen um 14 Uhr"
- Bei Verfügbarkeit: "Ja, gerne buchen"
```

**Success Criteria:**
```
✅ Agent begrüßt freundlich
✅ Agent sammelt: Name, Dienstleistung, Datum, Uhrzeit
✅ Tool-Aufruf: check_availability_v17
✅ Agent präsentiert Verfügbarkeit
✅ Nach Bestätigung: Tool-Aufruf book_appointment_v17
✅ Agent bestätigt Buchung mit allen Details
✅ Dauer < 90 Sekunden
```

**Test Variables:**
```
Variable Name: from_number
Test Value: +491234567890

Variable Name: customer_name
Test Value: Michael Schmidt

Variable Name: test_date
Test Value: 23.10.2025      (Morgen anpassen!)

Variable Name: test_time
Test Value: 14:00

Variable Name: service
Test Value: Beratung
```

---

## BEKANNTER KUNDE TEST

### Test Case 2: Hansi (Stammkunde)

**Name:**
```
Bekannter Kunde - Hansi Schnellbuchung
```

**User Prompt:**
```
## Identität
Dein Name ist Hansi (Stammkunde).
Deine Telefonnummer wird übertragen: +491604366218

## Ziel
Schnell einen Termin buchen für übermorgen 10:00 Uhr, Beratung.

## Persönlichkeit
Ungeduldig, kennst den Prozess, möchtest es schnell erledigen.
Du erwartest, dass der Agent dich erkennt.

## Verhalten
- Sage direkt: "Ich brauche einen Termin für eine Beratung übermorgen um 10 Uhr"
- Wenn nach Namen gefragt wird: Sei überrascht ("Ihr kennt mich doch?")
- Bestätige schnell mit "Ja"
```

**Success Criteria:**
```
✅ Agent erkennt Telefonnummer
✅ Agent fragt NICHT nach Namen (weil bekannter Kunde!)
✅ Agent sammelt nur: Dienstleistung, Datum, Uhrzeit
✅ Tool-Aufruf: check_availability_v17
✅ Tool-Aufruf: book_appointment_v17
✅ Erfolgreiche Buchung
✅ Dauer < 60 Sekunden (schneller als Neukunde)
```

**Test Variables:**
```
Variable Name: from_number
Test Value: +491604366218

Variable Name: customer_name
Test Value: Hansi         (wird automatisch erkannt)

Variable Name: test_date
Test Value: 24.10.2025      (Übermorgen anpassen!)

Variable Name: test_time
Test Value: 10:00

Variable Name: service
Test Value: Beratung
```

---

## ANONYMER ANRUFER TEST

### Test Case 3: Unterdrückte Nummer

**Name:**
```
Anonymer Anrufer - Name erfassen
```

**User Prompt:**
```
## Identität
Du rufst mit unterdrückter Nummer an (anonym).
Dein Name ist Julia Meier.

## Ziel
Termin buchen für eine Beratung nächste Woche Montag 15:00 Uhr.

## Persönlichkeit
Etwas vorsichtig und zurückhaltend.

## Verhalten
- Sage: "Ich möchte einen Termin buchen"
- Wenn nach Namen gefragt: Zögere kurz, dann sage "Julia Meier"
- Nenne Dienstleistung: "Beratung"
- Termin: "Nächsten Montag um 15 Uhr"
- Bestätige mit "Ja"
```

**Success Criteria:**
```
✅ Agent erkennt anonymen Anruf
✅ Agent fragt IMMER nach dem Namen (CRITICAL!)
✅ Agent sammelt: Name, Dienstleistung, Datum, Uhrzeit
✅ Tool-Aufruf: check_availability_v17
✅ Tool-Aufruf: book_appointment_v17
✅ Erfolgreiche Buchung mit erfasstem Namen
```

**Test Variables:**
```
Variable Name: from_number
Test Value: anonymous

Variable Name: customer_name
Test Value: Julia Meier      (muss erfasst werden!)

Variable Name: test_date
Test Value: 27.10.2025      (Nächster Montag anpassen!)

Variable Name: test_time
Test Value: 15:00

Variable Name: service
Test Value: Beratung
```

---

## So verwendest du das Dashboard

### Test ausführen:

1. Test Case erstellen (siehe oben)
2. **"Save"** klicken
3. **"Run Test"** oder **"Start Simulation"** klicken
4. Warten (~60-120 Sekunden)
5. Ergebnis ansehen

### Ergebnis interpretieren:

**✅ PASS (Grün):**
- Alle Success Criteria erfüllt
- Tool-Aufrufe sichtbar in Logs
- Conversation Flow korrekt

**⚠️ WARNING (Gelb):**
- Teilweise erfolgreich
- Manche Criteria nicht erfüllt
- Prüfe Details

**❌ FAIL (Rot):**
- Test fehlgeschlagen
- Tool-Aufrufe fehlen (V15/V16 Problem!)
- Flow-Problem

### Logs überprüfen:

Im Test-Ergebnis:
1. **Transcript** ansehen - Was wurde gesagt?
2. **Tool Calls** prüfen - Wurden Tools aufgerufen?
3. **Latency** checken - Wie schnell war es?

Parallel in Laravel Logs:
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "V17:"
```

Erwarte:
```
🔍 V17: Check Availability (bestaetigung=false)
✅ V17: Book Appointment (bestaetigung=true)
```

---

## Test-Reihenfolge (Empfohlen)

**Nach V17 Deployment (HEUTE):**
1. ⭐ Test Case 8 (V17 Tool-Invocation) - KRITISCH!
2. ⭐ Test Case 1 (Erfolgreiche Buchung)
3. ⭐ Test Case 2 (Hansi bekannter Kunde)

**Wenn alle 3 PASS → V17 funktioniert!**

**Erweiterte Tests (optional):**
4. Test Case 3 (Anonymer Anrufer)
5. Test Case 4 (Alternativen bei Nicht-Verfügbarkeit)
6. Test Case 5 (Umbuchung)
7. Test Case 6 (Stornierung)
8. Test Case 7 (Außerhalb Öffnungszeiten)

---

## Troubleshooting

### "Tool wird nicht aufgerufen" (V15/V16 Problem)

**Check 1: Agent Version**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "agent_version"
```

Erwarte: `agent_version >= 14`
Wenn `= 13`: CDN Propagation noch nicht fertig → 15 Min warten

**Check 2: Flow Version**
Im Dashboard:
- Conversation Flow → conversation_flow_da76e7c6f3ba
- Version sollte: **18** sein
- Wenn älter: Re-deploy nötig

**Check 3: Backend Endpoints**
```bash
curl -X POST https://api.askproai.de/api/retell/v17/check-availability \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","datum":"23.10.2025","uhrzeit":"13:00","dienstleistung":"Beratung"}'
```

Sollte Response zurückgeben (nicht 404!)

---

## Erfolgs-Kriterien für V17

**V15/V16 Performance (VORHER):**
- Tool Invocation Success: **0% (0/2 Calls)**
- Problem: Agent sagt "ich prüfe" aber Tool wird nicht aufgerufen

**V17 Target (NACHHER):**
- Tool Invocation Success: **100%**
- Explicit Function Nodes garantieren Tool-Aufrufe
- Deterministic, reliable, debuggable

**Wenn Test Case 8 PASS zeigt:**
✅ check_availability_v17 wurde aufgerufen
✅ book_appointment_v17 wurde aufgerufen
✅ Beide Logs sichtbar ("🔍 V17:" und "✅ V17:")
✅ Buchung erfolgreich in Datenbank

**→ Problem gelöst! V17 funktioniert! 🎉**

---

## Nächste Schritte

1. **JETZT:** Warte bis ~21:35 Uhr (CDN Propagation)
2. **DANN:** Führe Test Case 8 im Dashboard aus
3. **CHECK:** Logs parallel überwachen
4. **RESULT:** Wenn PASS → Echten Testanruf machen
5. **CONFIRM:** User-Test durchführen lassen

---

**Erstellt:** 2025-10-22 21:20
**Version:** V17 (Flow Version 18)
**Deployment:** Aktiv, CDN Propagation läuft
