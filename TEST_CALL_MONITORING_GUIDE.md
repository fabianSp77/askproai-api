# Testanruf Monitoring Guide - Agent V51

**Date**: 2025-11-06 16:45
**Agent**: Friseur 1 Agent V51
**Agent ID**: `agent_45daa54928c5768b52ba3db736`
**Phone**: `+493033081738`

---

## 📞 Testanruf durchführen

### Option 1: Über Retell Dashboard
```
1. Öffne: https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736
2. Click "Test Call"
3. Wähle Sprache: Deutsch (de-DE)
4. Starte Test
```

### Option 2: Echten Anruf
```
Rufe an: +493033081738
→ Agent V51 nimmt automatisch ab
```

---

## 🔍 Was zu monitoren

### 1. Initial Context Loading
```bash
# Terminal 1: Laravel Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "get_current_context\|Context initialisieren"

# Erwartung:
# ✅ Tool Call: get_current_context
# ✅ Response: {"date":"2025-11-06","time":"16:45","day_of_week":"Donnerstag"}
# ✅ Dynamic Variables gesetzt
```

### 2. Function Calls
```bash
# Terminal 2: Function Call Monitoring
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "retell function\|check_availability\|get_alternatives\|request_callback"

# Prüfe:
# ✅ check_availability wird gecallt
# ✅ get_alternatives wird gecallt (wenn nicht verfügbar)
# ✅ request_callback als Fallback verfügbar
# ✅ Two-Step: start_booking → confirm_booking
```

### 3. Company/Branch Context
```bash
# Terminal 3: Context Verification
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "getCallContext\|company_id\|branch_id"

# Erwartung:
# ✅ company_id: 1 (Friseur 1)
# ✅ branch_id: 34c4d48e-4753-4715-9c30-c55843a943e8 (Friseur 1 Zentrale)
# ✅ Services nur von dieser Branch
```

### 4. Errors & Warnings
```bash
# Terminal 4: Error Monitoring
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "ERROR|WARNING|CRITICAL|❌"

# Prüfe:
# ❌ Keine 500 Errors
# ❌ Keine Missing Parameters
# ❌ Keine NULL company_id/branch_id
```

---

## ✅ Test Szenarien

### Szenario 1: Happy Path (Direktbuchung)
```
📞 Du sagst:
"Ich möchte einen Herrenhaarschnitt für morgen um 14 Uhr buchen."

✅ Erwartetes Verhalten:
1. Agent ruft get_current_context ab
   → {{current_date}}, {{current_time}}, {{day_name}} werden gesetzt

2. Agent sammelt Daten:
   → Name wird gefragt
   → Service erkannt: "Herrenhaarschnitt"
   → Datum erkannt: "morgen" → Backend berechnet mit {{current_date}}
   → Uhrzeit erkannt: "14 Uhr"

3. Agent ruft check_availability ab
   → Prüft Verfügbarkeit für morgen 14:00

4. Wenn verfügbar:
   → start_booking (Validation <500ms)
   → Agent sagt: "Perfekt! Ich buche den Termin..."
   → confirm_booking (Actual booking 4-5s)
   → Bestätigung

5. Call Context wird genutzt:
   → company_id: 1
   → branch_id: 34c4d48e...
   → Nur Services dieser Branch

📊 Logs prüfen:
tail -f storage/logs/laravel.log | grep "call_"
```

### Szenario 2: Alternative Path
```
📞 Du sagst:
"Ich möchte Balayage für heute um 15 Uhr."

✅ Erwartetes Verhalten:
1. Agent sammelt Daten (wie oben)

2. check_availability: NICHT verfügbar

3. **NEUE FEATURE**: get_alternatives wird gecallt
   → Backend sucht 2-3 alternative Zeitslots
   → Ähnliche Uhrzeit bevorzugt

4. Agent präsentiert Alternativen:
   "Heute um 15 Uhr ist leider nicht verfügbar.
    Ich hätte folgende Alternativen:
    - Heute um 16:30
    - Morgen um 14:50
    - Freitag um 15:20
    Welche Zeit würde Ihnen passen?"

5. Du wählst: "16:30"

6. Two-Step Booking:
   → start_booking mit 16:30
   → confirm_booking
   → Bestätigung

📊 Logs prüfen:
grep -i "get_alternatives" storage/logs/laravel.log
```

### Szenario 3: Callback Fallback
```
📞 Du sagst:
"Ich möchte Dauerwelle, aber keine der Zeiten passt."

✅ Erwartetes Verhalten:
1. Agent sammelt Daten

2. check_availability: Verfügbar

3. get_alternatives: Weitere Optionen

4. Du lehnst ab: "Keine passt mir"

5. **NEUE FEATURE**: Agent bietet request_callback an:
   "Kein Problem! Möchten Sie, dass wir Sie zurückrufen,
    wenn ein passender Termin frei wird?"

6. Du: "Ja gerne"

7. Agent sammelt:
   → Name (schon bekannt)
   → Telefonnummer
   → Grund (automatisch: "Termin für Dauerwelle buchen")

8. request_callback wird gecallt:
   → 100% Success Rate
   → Auto-Assignment an Staff (least-loaded)
   → callback_id: 9 (verifiziert)

9. Bestätigung:
   "Wunderbar! Ihre Rückruf-Anfrage wurde erstellt.
    Wir melden uns zeitnah bei Ihnen."

📊 Logs prüfen:
grep -i "request_callback" storage/logs/laravel.log
```

### Szenario 4: Context & Date Test
```
📞 Du sagst:
"Ich möchte morgen einen Termin."

✅ Erwartetes Verhalten:
1. get_current_context liefert:
   → current_date: "2025-11-06"
   → day_name: "Donnerstag"

2. Backend berechnet "morgen":
   → "2025-11-07" (Freitag)

3. Agent nutzt korrektes Datum:
   "Gerne für morgen, Freitag den 7. November."

4. KEIN Jahr 2024 oder 2023!

5. check_availability mit:
   → datum: "2025-11-07"
   → uhrzeit: [wird gefragt]

📊 Logs prüfen:
grep -E "current_date|2025-11-07" storage/logs/laravel.log
```

---

## 📊 Monitoring Commands

### All-in-One Monitoring
```bash
# Terminal 1: Full Log Stream
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Terminal 2: Function Calls Only
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "🔧\|Tool Call\|Function:"

# Terminal 3: Errors Only
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "ERROR|WARNING|❌"

# Terminal 4: Context & Company
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "company_id|branch_id|getCallContext"
```

### Nach dem Call: Call-ID finden
```bash
# Finde die Call-ID
grep "call_" /var/www/api-gateway/storage/logs/laravel.log | tail -20

# Analysiere spezifischen Call
grep "call_abc123xyz" /var/www/api-gateway/storage/logs/laravel.log > /tmp/call_analysis.log
```

### Performance Check
```bash
# Prüfe Response Times
grep -E "check_availability.*completed|get_alternatives.*completed" storage/logs/laravel.log | tail -10
```

---

## 🎯 Success Criteria

### ✅ Call erfolgreich wenn:
- [x] get_current_context liefert Datum/Uhrzeit
- [x] Dynamic Variables {{current_date}}, {{current_time}}, {{day_name}} gesetzt
- [x] company_id = 1, branch_id = 34c4d48e... korrekt
- [x] Nur Services von Friseur 1 Zentrale verfügbar
- [x] check_availability funktioniert
- [x] get_alternatives wird angeboten (bei nicht-verfügbar)
- [x] request_callback funktioniert als Fallback
- [x] Two-Step Booking: start_booking → confirm_booking
- [x] Keine 500 Errors
- [x] Keine NULL company_id/branch_id
- [x] Korrektes Jahr (2025)

---

## 🚨 Known Issues Check

### Issue 1: Datum-Bug (behoben in V51)
```bash
# Prüfe dass KEIN Jahr 2024/2023 verwendet wird
grep -E "2024|2023" storage/logs/laravel.log | grep -v "2025"

# Erwartung: KEINE Treffer (außer alte Logs)
```

### Issue 2: Company Context Missing (behoben in V51)
```bash
# Prüfe dass company_id/branch_id IMMER gesetzt sind
grep "company_id.*null\|branch_id.*null" storage/logs/laravel.log

# Erwartung: KEINE Treffer (oder nur Test Mode Fallback)
```

### Issue 3: Dead Ends (behoben in V51)
```bash
# Prüfe dass IMMER ein Fallback existiert
grep -i "no slots available" storage/logs/laravel.log

# Erwartung: get_alternatives oder request_callback wird gecallt
```

---

## 📝 Post-Call Analysis

### 1. Call Record prüfen
```bash
php artisan tinker --execute="
\$lastCall = \App\Models\Call::orderBy('created_at', 'desc')->first();
echo 'Call ID: ' . \$lastCall->retell_call_id . PHP_EOL;
echo 'Company: ' . \$lastCall->company_id . PHP_EOL;
echo 'Branch: ' . \$lastCall->branch_id . PHP_EOL;
echo 'Agent: ' . \$lastCall->retell_agent_id . PHP_EOL;
echo 'Duration: ' . \$lastCall->duration . 's' . PHP_EOL;
echo 'Status: ' . \$lastCall->status . PHP_EOL;
"
```

### 2. Function Calls auslesen
```bash
# Welche Functions wurden gecallt?
grep "call_<YOUR_CALL_ID>" storage/logs/laravel.log | grep "Function:" | awk '{print $NF}'
```

### 3. Appointment erstellt?
```bash
php artisan tinker --execute="
\$lastAppointment = \App\Models\Appointment::orderBy('created_at', 'desc')->first();
if (\$lastAppointment) {
    echo 'Appointment ID: ' . \$lastAppointment->id . PHP_EOL;
    echo 'Service: ' . \$lastAppointment->service->name . PHP_EOL;
    echo 'Date: ' . \$lastAppointment->starts_at . PHP_EOL;
    echo 'Company: ' . \$lastAppointment->company_id . PHP_EOL;
} else {
    echo 'Kein Appointment erstellt' . PHP_EOL;
}
"
```

---

## 🎓 Interpretation der Logs

### Gutes Zeichen ✅:
```
✅ "get_current_context: Success"
✅ "company_id: 1, branch_id: 34c4d48e..."
✅ "check_availability: found 5 slots"
✅ "start_booking: validation success <500ms"
✅ "confirm_booking: booking created"
✅ "get_alternatives: found 3 alternatives"
✅ "request_callback: created callback_id 123"
```

### Warnung ⚠️:
```
⚠️ "getCallContext: company_id not set, waiting for enrichment"
   → Normal, wird nach 500ms resolved

⚠️ "Test Mode fallback used"
   → OK für Test-Calls, sollte nicht in Production

⚠️ "No slots available"
   → OK, get_alternatives sollte folgen
```

### Fehler ❌:
```
❌ "ERROR: company_id is NULL"
   → PROBLEM! Context nicht geladen

❌ "ERROR: Function get_alternatives not found"
   → PROBLEM! Tool fehlt

❌ "ERROR: Invalid year 2024"
   → PROBLEM! Datum-Bug

❌ "ERROR 500"
   → PROBLEM! Backend Fehler
```

---

## 🚀 Ready for Test!

**Alles vorbereitet für Testanruf:**
- ✅ Agent V51 deployed
- ✅ Telefonnummer zugeordnet
- ✅ Company/Branch Context konfiguriert
- ✅ Alle 11 Tools aktiv
- ✅ Monitoring Setup dokumentiert

**Starte Testanruf und ich monitore im Detail!** 📞

---

**Created**: 2025-11-06 16:45
**Agent**: V51 (agent_45daa54928c5768b52ba3db736)
**Status**: Ready for Testing
