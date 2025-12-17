# Flow V116 Update - COPY & PASTE Anleitung
**Zeit**: 10 Minuten
**Wichtig**: NUR 2 NODES müssen geändert werden!

---

## Schritt 1: Retell Dashboard öffnen

1. Gehe zu: https://beta.retellai.com/dashboard
2. Click: **Agents** (linke Sidebar)
3. Finde: **Friseur 1 Agent V116 - Direct Booking Fix**
4. Click: **Edit** (rechts)
5. Click: **Response Engine** Tab
6. Click: **Edit Flow** Button

---

## Schritt 2: Node 1 updaten - "Zeit aktualisieren"

### Node finden:
- Suche im Flow Editor nach Node: **"Zeit aktualisieren"**
- Oder suche nach ID: `node_update_time`

### Node öffnen:
- Click auf die Node
- Click **Instruction** Tab (rechts)

### Text ersetzen:

**LÖSCHE DEN KOMPLETTEN TEXT** und ersetze mit:

```
Aktualisiere {{appointment_time}} mit {{selected_alternative_time}}.
Wenn {{selected_alternative_date}} vorhanden: Aktualisiere auch {{appointment_date}}.

WICHTIG - NIEMALS "ist gebucht" sagen!

Sage: "Perfekt! Soll ich den [service_name] für [date] um [time] Uhr dann für Sie buchen?"

VERBOTEN:
- "ist gebucht"
- "Termin gebucht"
- "ist fest"

Transition zu node_collect_final_booking_data.
```

**Save** klicken!

---

## Schritt 3: Node 2 updaten - "Finale Buchungsdaten sammeln"

### Node finden:
- Suche im Flow Editor nach Node: **"Finale Buchungsdaten sammeln"**
- Oder suche nach ID: `node_collect_final_booking_data`

### Node öffnen:
- Click auf die Node
- Click **Instruction** Tab (rechts)

### Text ersetzen:

**LÖSCHE DEN KOMPLETTEN TEXT** und ersetze mit:

```
SAMMLE FEHLENDE PFLICHTDATEN:

Pflicht für Buchung:
- customer_name

Optional (Fallback erlaubt):
- customer_phone (Fallback: '0151123456')
- customer_email (Fallback: 'termin@askproai.de')

LOGIK:
1. Prüfe was bereits aus check_customer vorhanden:
   - {{customer_name}} gefüllt → NICHT fragen
   - {{customer_phone}} gefüllt → NICHT fragen

2. Bei Neukunde:
   "Darf ich noch Ihren Namen erfragen?"

3. Telefon/Email OPTIONAL:
   "Möchten Sie eine Telefonnummer angeben?" → nur fragen wenn explizit gewünscht

REGELN:
- KEINE wiederholten Fragen
- Sobald customer_name vorhanden → SOFORT zu func_start_booking
- NIEMALS sagen "ist gebucht" oder "Termin fest"
- NUR sagen: "Einen Moment, ich buche das für Sie..."

KRITISCH - VERBOTEN:
- "Ihr Termin ist gebucht"
- "Termin ist fest"
- "Termin ist bestätigt"
- Jede Formulierung die impliziert die Buchung ist bereits erfolgt!

NUR ERLAUBT:
- "Ich buche jetzt für Sie"
- "Einen Moment, ich erstelle die Buchung"
- "Perfekt, ich kümmere mich darum"
```

**Save** klicken!

---

## Schritt 4: Flow Publishen

1. Click: **Publish** Button (oben rechts im Flow Editor)
2. Bestätige: **Yes, Publish**
3. **Warte 2 Minuten** bis Agent V116 die neue Flow-Version geladen hat

---

## Validation Checklist

Prüfe ob beide Nodes updated wurden:

- [ ] `node_update_time` enthält **"NIEMALS 'ist gebucht' sagen!"**
- [ ] `node_collect_final_booking_data` enthält **"VERBOTEN: ist gebucht"**
- [ ] Flow ist **Published** (Status oben rechts zeigt "Published")
- [ ] 2 Minuten gewartet

---

## ✅ FERTIG!

Du hast erfolgreich Flow V116 gefixt!

**Nächster Schritt**: Testanruf machen

---

## Testanruf Szenario

1. **Ruf an**: +493033081738
2. **Sage**: "Hans Müller, Herrenhaarschnitt morgen um 10 Uhr"
3. **Agent antwortet**: "nicht frei, aber ich kann..." (Alternativen)
4. **Du sagst**: "11 Uhr 55"
5. **Agent SOLLTE SAGEN**: "Soll ich buchen?" ✅ (NICHT "ist gebucht")
6. **Du sagst**: "Ja bitte"
7. **Agent SOLLTE SAGEN**: "Einen Moment..." 🔄 (start_booking wird aufgerufen)
8. **Agent SOLLTE SAGEN**: "Ihr Termin ist gebucht..." ✅ (NACH booking success)

**Erwartetes Ergebnis**:
- Kein "ist gebucht" VOR dem tatsächlichen Booking ✅
- Termin wird erfolgreich erstellt ✅
- Kein "title" Error mehr ✅
- Kein "technical problem" Error ✅

---

## Validierung nach Testanruf

Check Database:
```bash
php artisan tinker --execute="
\$lastCall = \\App\\Models\\Call::orderBy('created_at', 'desc')->first();
echo 'Call ID: ' . \$lastCall->retell_call_id . PHP_EOL;

\$appts = \\App\\Models\\Appointment::where('call_id', \$lastCall->id)->get();
echo 'Appointments: ' . \$appts->count() . PHP_EOL;

if (\$appts->count() > 0) {
  \$appt = \$appts[0];
  echo 'Service: ' . \$appt->service->name . PHP_EOL;
  echo 'Customer: ' . \$appt->customer->name . PHP_EOL;
  echo 'Start: ' . \$appt->start_time . PHP_EOL;
  echo 'Cal.com ID: ' . \$appt->calcom_booking_id . PHP_EOL;
  echo 'Status: ' . \$appt->status . PHP_EOL;
}
"
```

**Expected Output**:
```
Call ID: call_xxxxx
Appointments: 1
Service: Herrenhaarschnitt
Customer: Hans Müller
Start: 2025-11-14 11:55:00
Cal.com ID: 123456
Status: confirmed
```

✅ **SUCCESS** = 1 Appointment created mit allen Details!

---

## Troubleshooting

### Problem: Agent sagt immer noch "ist gebucht" zu früh

**Lösung**:
1. Check: Wurden BEIDE Nodes updated? (nicht nur eine!)
2. Check: Flow wirklich published? (Status = "Published")
3. Warte 3 Minuten und versuche nochmal
4. Clear Agent Cache:
   - Retell Dashboard → Agent V116 → Advanced → Clear Cache
   - Warte 1 Minute
   - Neuer Testanruf

### Problem: Booking schlägt fehl mit "title" Error

**Lösung**: Das sollte NICHT mehr passieren (Backend Fix ist deployed)

Wenn doch:
```bash
# Check ob Code wirklich deployed ist
git log -1 --oneline
# Should show: "fix(agent-v116): Fix title field missing..."

# Check CalcomService
grep -A 5 "Add title field directly" app/Services/CalcomService.php
# Should show the title fix
```

### Problem: Keine Appointments in Database

**Check Logs**:
```bash
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -E "start_booking|bookAppointment|Error|Exception"
```

---

**Zeit Insgesamt**: ~10 Minuten
**Status**: Backend ✅ | Flow ⏳ (du machst jetzt) | Test ⏳ (danach)
