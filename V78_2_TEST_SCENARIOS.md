# V78.2 Test Scenarios - Vollständige Validierung

## 🎯 TEST ZIEL

**Validation der V78.2 SILENCE-FIX:**
- Agent schweigt NICHT mehr nach check_customer() Response
- Personalisierte Begrüßung bei bekannten Kunden
- Korrekte Name-Anfrage bei anonymen/neuen Kunden
- Smooth Conversation Flow ohne Unterbrechungen

---

## 📞 TEST SCENARIO 1: ANONYMER ANRUF

### Setup
- **Telefonnummer:** Mit Rufnummernunterdrückung (`*31#` oder `#31#` vor Nummer)
- **Expected check_customer() Response:** `status='anonymous'`
- **Ziel:** Agent fragt sofort nach vollständigem Namen

### Erwarteter Dialog

```
[Anruf startet]

Agent: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"

[Agent ruft check_customer() im Hintergrund auf]
[Pause: max 1-2 Sekunden für Function Response]

Agent (DEBUG MODE): "Einen Moment, ich prüfe Ihre Daten..."
[Kurze Pause]
Agent (DEBUG MODE): "Ihre Nummer ist unterdrückt. Für Buchungen benötige ich Ihren Namen."

Agent: "Wie kann ich Ihnen helfen? Für Terminbuchungen benötige ich allerdings Ihren vollständigen Namen."

User: "Ja, ich hätte gern einen Termin."

Agent: "Gerne! Ihr vollständiger Name bitte?"

User: "Klaus Müller."

Agent: "Vielen Dank, Herr Müller. Für welchen Tag möchten Sie den Termin? Heute, morgen oder nächste Woche?"

[... Terminbuchung läuft weiter ...]
```

### Success Criteria
- ✅ Keine Schweigepausen >2 Sekunden
- ✅ Agent fragt nach vollständigem Namen
- ✅ Name wird validiert (≥2 Wörter, je ≥2 Zeichen)
- ✅ DEBUG Mode zeigt korrekte Status-Meldung
- ✅ Terminbuchung funktioniert vollständig

### Failure Indicators
- ❌ Agent schweigt >2 Sekunden nach Greeting
- ❌ Agent fragt NICHT nach Namen
- ❌ Agent akzeptiert ungültige Namen ("Klaus" ohne Nachname)
- ❌ User legt frustriert auf

---

## 📞 TEST SCENARIO 2: BEKANNTER KUNDE (MIT NUMMER)

### Setup
- **Telefonnummer:** Bekannte Nummer (z.B. +491604366218 = Hansi Hinterseer)
- **Expected check_customer() Response:** `status='found', customer_name='Hansi Hinterseer'`
- **Ziel:** Agent personalisiert Begrüßung mit Vorname

### Erwarteter Dialog

```
[Anruf startet]

Agent: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"

[Agent ruft check_customer() im Hintergrund auf]
[Pause: max 1-2 Sekunden für Function Response]

Agent (DEBUG MODE): "Einen Moment, ich prüfe Ihre Daten..."
[Kurze Pause]
Agent (DEBUG MODE): "Ich sehe Sie sind bereits Kunde bei uns! Schön Sie wieder zu hören!"

Agent: "Schön Sie wieder zu hören, Hansi! Wie kann ich Ihnen helfen?"

User: "Ja, ich hätte gern einen Termin."

Agent: "Sehr gerne! Für welchen Tag möchten Sie den Termin? Heute, morgen oder nächste Woche?"

[... Agent fragt NICHT erneut nach Name ...]

User: "Morgen um 10 Uhr."

Agent: "Das ist Mittwoch, der 16. Um 10 Uhr. Einen Moment, ich prüfe die Verfügbarkeit..."

[... Terminbuchung läuft weiter ...]
```

### Success Criteria
- ✅ Keine Schweigepausen >2 Sekunden
- ✅ Personalisierte Begrüßung mit Vorname ("Hansi")
- ✅ Agent fragt NICHT erneut nach Name
- ✅ DEBUG Mode zeigt korrekte Status-Meldung
- ✅ Direkter Übergang zur Terminbuchung
- ✅ Terminbuchung funktioniert vollständig

### Failure Indicators
- ❌ Agent schweigt >2 Sekunden nach Greeting
- ❌ Agent sagt NICHT den Vornamen
- ❌ Agent fragt erneut nach Name (obwohl bekannt)
- ❌ Generische Begrüßung statt personalisiert

---

## 📞 TEST SCENARIO 3: NEUER KUNDE (UNBEKANNTE NUMMER)

### Setup
- **Telefonnummer:** Unbekannte neue Nummer (z.B. +491234567890)
- **Expected check_customer() Response:** `status='new_customer', customer_exists=false`
- **Ziel:** Agent behandelt als neuen Kunden, fragt nach Name bei Buchung

### Erwarteter Dialog

```
[Anruf startet]

Agent: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"

[Agent ruft check_customer() im Hintergrund auf]
[Pause: max 1-2 Sekunden für Function Response]

Agent (DEBUG MODE): "Einen Moment, ich prüfe Ihre Daten..."
[Kurze Pause]
Agent (DEBUG MODE): "Ich sehe Sie sind neu bei uns! Willkommen!"

Agent: "Wie kann ich Ihnen helfen?"

User: "Ja, ich hätte gern einen Termin."

Agent: "Sehr gerne! Ihr vollständiger Name bitte?"

User: "Maria Schmidt."

Agent: "Vielen Dank, Frau Schmidt. Für welchen Tag möchten Sie den Termin?"

[... Terminbuchung läuft weiter ...]
```

### Success Criteria
- ✅ Keine Schweigepausen >2 Sekunden
- ✅ Normale (nicht personalisierte) Begrüßung
- ✅ Agent fragt nach vollständigem Namen bei Buchung
- ✅ DEBUG Mode zeigt korrekte Status-Meldung
- ✅ Name wird validiert
- ✅ Terminbuchung funktioniert vollständig

### Failure Indicators
- ❌ Agent schweigt >2 Sekunden nach Greeting
- ❌ Agent versucht zu personalisieren (obwohl unbekannt)
- ❌ Agent fragt NICHT nach Namen
- ❌ Agent akzeptiert ungültige Namen

---

## 📞 TEST SCENARIO 4: ERROR HANDLING (CHECK_CUSTOMER FAILURE)

### Setup
- **Telefonnummer:** Beliebig
- **Simulated Error:** Backend gibt `success=false` zurück
- **Ziel:** Agent hat Fallback-Antwort, schweigt nicht

### Erwarteter Dialog

```
[Anruf startet]

Agent: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"

[Agent ruft check_customer() im Hintergrund auf]
[Backend gibt Error zurück]

Agent: "Entschuldigung, da ist kurz etwas schiefgelaufen. Wie kann ich Ihnen helfen?"

User: "Ich hätte gern einen Termin."

Agent: "Gerne! Ihr vollständiger Name bitte?"

[... Terminbuchung läuft weiter ...]
```

### Success Criteria
- ✅ Keine Schweigepausen >2 Sekunden
- ✅ Agent hat Fallback-Antwort bei Error
- ✅ Agent fragt nach Namen (weil Status unklar)
- ✅ Terminbuchung funktioniert trotz Error
- ✅ User merkt Error nicht (smooth recovery)

### Failure Indicators
- ❌ Agent schweigt bei Error
- ❌ Agent crashed oder bricht ab
- ❌ Keine Recovery-Strategie

---

## 📞 TEST SCENARIO 5: VOLLSTÄNDIGE TERMINBUCHUNG (END-TO-END)

### Setup
- **Telefonnummer:** Bekannte Nummer
- **Service:** Beratung (Standard)
- **Ziel:** Kompletter Buchungsflow von Anfang bis Ende

### Erwarteter Dialog

```
[Anruf startet]

Agent: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"
[Pause 1-2s]
Agent: "Schön Sie wieder zu hören, Hansi! Wie kann ich Ihnen helfen?"

User: "Ich hätte gern einen Termin."

Agent: "Sehr gerne! Für welchen Tag möchten Sie den Termin? Heute, morgen oder nächste Woche?"

User: "Morgen um 10 Uhr."

Agent: "Das ist Mittwoch, der 16. Um 10 Uhr. Einen Moment, ich prüfe die Verfügbarkeit..."

[Agent ruft collect_appointment_data() auf]
[V85 Backend macht Double-Check]

Agent: "Mittwoch, der 16. um 10 Uhr ist verfügbar. Soll ich diesen Termin buchen?"

User: "Ja, bitte."

Agent: "Einen Moment, ich buche den Termin für Sie..."

[Agent ruft collect_appointment_data(..., bestaetigung: true) auf]
[Termin wird gebucht]

Agent: "Gebucht! Mittwoch, der 16. um 10 Uhr für Beratung. Sie erhalten eine Bestätigungs-E-Mail. Kann ich noch etwas für Sie tun?"

User: "Nein, das war's. Danke!"

Agent: "Vielen Dank für Ihren Anruf. Auf Wiederhören!"

[Agent ruft end_call() auf]
[Anruf endet]
```

### Success Criteria
- ✅ Keine Schweigepausen >2 Sekunden
- ✅ Personalisierte Begrüßung
- ✅ Name NICHT erneut abgefragt
- ✅ Datum/Uhrzeit korrekt geparst
- ✅ Verfügbarkeitsprüfung funktioniert
- ✅ V85 Double-Check verhindert Race Conditions
- ✅ Bestätigung vor Buchung
- ✅ Termin erfolgreich gebucht
- ✅ Höfliche Verabschiedung

### Failure Indicators
- ❌ Schweigepausen im Flow
- ❌ Name erneut abgefragt
- ❌ Datum falsch interpretiert
- ❌ Verfügbarkeitsprüfung fehlerhaft
- ❌ Buchung fehlgeschlagen
- ❌ Keine Bestätigung

---

## 📞 TEST SCENARIO 6: VERFÜGBARKEIT NICHT VORHANDEN (ALTERNATIVEN)

### Setup
- **Telefonnummer:** Bekannte Nummer
- **Gewünschter Termin:** NICHT verfügbar
- **Ziel:** Agent bietet Alternativen an

### Erwarteter Dialog

```
Agent: "Schön Sie wieder zu hören, Hansi! Wie kann ich Ihnen helfen?"

User: "Ich hätte gern einen Termin morgen um 10 Uhr."

Agent: "Das ist Mittwoch, der 16. Um 10 Uhr. Einen Moment, ich prüfe die Verfügbarkeit..."

[Agent ruft collect_appointment_data() auf]
[Slot ist NICHT verfügbar]

Agent: "Der gewünschte Zeitpunkt ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten:
- Mittwoch, der 16. um 11 Uhr
- Mittwoch, der 16. um 14 Uhr
- Donnerstag, der 17. um 10 Uhr

Welcher Termin passt Ihnen?"

User: "Mittwoch um 11 Uhr."

Agent: "Sehr gut. Einen Moment, ich prüfe..."

[Agent macht Recheck für gewählten Slot]

Agent: "Mittwoch, der 16. um 11 Uhr ist verfügbar. Ich buche diesen Termin für Sie..."

[Terminbuchung ohne zusätzliche Rückfrage, da Alternative gewählt]

Agent: "Gebucht! Mittwoch, der 16. um 11 Uhr für Beratung. Sie erhalten eine Bestätigungs-E-Mail."
```

### Success Criteria
- ✅ Agent sagt klar "nicht verfügbar"
- ✅ Genau 3 Alternativen angeboten
- ✅ Alternativen zeitlich sortiert
- ✅ Format: "Wochentag, TT um HH:MM"
- ✅ Recheck vor finaler Buchung
- ✅ KEINE zusätzliche Rückfrage bei gewählter Alternative
- ✅ Erfolgreiche Buchung

### Failure Indicators
- ❌ Agent sagt "verfügbar" obwohl nicht
- ❌ Keine Alternativen angeboten
- ❌ Falsche Alternativen (außerhalb Öffnungszeiten)
- ❌ Zusätzliche Rückfrage bei Alternative
- ❌ Buchung fehlgeschlagen

---

## 📊 PERFORMANCE METRICS

### Latenz-Messungen

Nach jedem Testanruf notiere:

| Metrik | Target | Gemessen |
|--------|--------|----------|
| Greeting → check_customer() Response | <2s | _____s |
| check_customer() → Agent spricht | <1s | _____s |
| User input → Agent response | <2s | _____s |
| Verfügbarkeitsprüfung Latenz | <3s | _____s |
| Gesamt-Gesprächsdauer | 60-180s | _____s |

### Success Rate

| Test Scenario | Ergebnis | Notizen |
|---------------|----------|---------|
| 1. Anonymer Anruf | ✅/❌ | |
| 2. Bekannter Kunde | ✅/❌ | |
| 3. Neuer Kunde | ✅/❌ | |
| 4. Error Handling | ✅/❌ | |
| 5. End-to-End Booking | ✅/❌ | |
| 6. Alternativen | ✅/❌ | |

**Overall Success Rate:** _____% (Ziel: >90%)

---

## 🐛 COMMON ISSUES & SOLUTIONS

### Issue 1: Agent schweigt nach Greeting

**Symptom:** Agent sagt Greeting, dann lange Pause
**Root Cause:** check_customer() Response wird nicht verarbeitet
**Solution:**
1. Checke Laravel Logs: `tail -f storage/logs/laravel.log | grep "check_customer"`
2. Verifiziere Response Format
3. Prüfe ob V78.2 Prompt korrekt deployed ist

### Issue 2: Agent personalisiert nicht

**Symptom:** Agent sagt "Wie kann ich Ihnen helfen?" statt "Schön Sie wieder zu hören, [Name]!"
**Root Cause:** customer_name fehlt in Response ODER Prompt Logic fehlt
**Solution:**
1. Checke Response: Ist `customer_name` vorhanden?
2. Checke Prompt: Ist INITIALIZATION Sektion korrekt?
3. Verifiziere Kundendaten in DB: `SELECT * FROM customers WHERE phone = '+491604366218';`

### Issue 3: Agent fragt doppelt nach Name

**Symptom:** Agent fragt nach Name obwohl Kunde bekannt
**Root Cause:** CALLER-ID FLOW Logic ignoriert check_customer() Response
**Solution:**
1. Checke Prompt: Ist "A) Nummer übertragen + Kunde bekannt" Regel korrekt?
2. Verifiziere dass Agent `status='found'` erkennt

### Issue 4: Verfügbarkeitsprüfung zu langsam

**Symptom:** >5 Sekunden bis Antwort
**Root Cause:** Cal.com API langsam ODER Cache miss
**Solution:**
1. Checke Cal.com Logs: `tail -f storage/logs/laravel.log | grep "📊 Cal.com API Performance"`
2. Prüfe Cache Hit Rate
3. Bei wiederholt langsamen Antworten: Cal.com Status checken

---

## 📝 TEST REPORT TEMPLATE

Nach Abschluss aller Tests:

```markdown
# V78.2 Test Report

**Test Date:** 2025-10-15
**Tester:** [Name]
**Environment:** Production Retell Agent

## Test Results

### Test Scenario 1: Anonymer Anruf
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

### Test Scenario 2: Bekannter Kunde
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

### Test Scenario 3: Neuer Kunde
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

### Test Scenario 4: Error Handling
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

### Test Scenario 5: End-to-End
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

### Test Scenario 6: Alternativen
- Status: ✅ Bestanden / ❌ Fehlgeschlagen
- Call ID: call_xxxxx
- Latenz: ___s
- Notizen:

## Performance Metrics

- Average Latenz: ___s (Target: <2s)
- Success Rate: ___% (Target: >90%)
- User Satisfaction: Hoch / Mittel / Niedrig

## Critical Issues Found

1. [Issue Description]
   - Severity: Kritisch / Hoch / Mittel / Niedrig
   - Root Cause:
   - Solution:

## Recommendations

- [ ] DEBUG Mode entfernen (V78.3)
- [ ] Weitere Performance-Optimierungen
- [ ] Zusätzliche Test Scenarios

## Conclusion

V78.2 Status: ✅ Production Ready / ❌ Needs Fixes
```

---

**Created:** 2025-10-15
**Version:** V78.2 Test Scenarios
**Status:** Ready for Testing
