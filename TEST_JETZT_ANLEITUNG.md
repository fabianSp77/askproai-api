# TEST JETZT - V17 Draft testen (ohne Publish)

## ✅ Was funktioniert SOFORT

**Dashboard Tests verwenden die neueste Draft-Version!**
- Draft Version 25 mit V17 Flow ist deployed ✅
- Dashboard Test kann JETZT ausgeführt werden ✅
- Kein Publish nötig für Dashboard Tests ✅

---

## 🧪 Test JETZT ausführen

### Schritt 1: Dashboard öffnen
```
https://app.retellai.com/agent/agent_616d645570ae613e421edb98e7
```

### Schritt 2: Test Tab öffnen
- Oben im Interface: **"Test"** Tab klicken

### Schritt 3: Test Case kopieren
```
https://api.askproai.de/retell-test-cases.html
```

**Test Case 1: V17 Tool-Invocation Test (KRITISCH)**
- Name kopieren
- User Prompt kopieren (kompletter Block)
- Success Criteria kopieren
- Variables einzeln hinzufügen:
  - from_number: +491000000001
  - customer_name: Test User
  - test_date: 23.10.2025
  - test_time: 13:00
  - service: Beratung

### Schritt 4: Test ausführen
- "Run Test" klicken
- Warten (~60-90 Sekunden)

### Schritt 5: Ergebnis prüfen

**Im Dashboard:**
- Node Transitions → func_check_availability sichtbar? ✅
- Tool Invocations → check_availability_v17 aufgerufen? ✅
- Tool Invocations → book_appointment_v17 aufgerufen? ✅

**Laravel Logs parallel:**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "V17:"
```

**Erwartete Logs:**
```
[2025-10-22 22:XX:XX] 🔍 V17: Check Availability (bestaetigung=false)
[2025-10-22 22:XX:XX] ✅ V17: Book Appointment (bestaetigung=true)
```

---

## 🎯 Success Criteria (V17 Test)

### ALLE müssen erfüllt sein:
1. ✅ Agent ruft check_availability_v17 auf (im Dashboard sichtbar)
2. ✅ Agent sagt: "Der Termin ist verfügbar. Soll ich das buchen?"
3. ✅ Agent WARTET auf Bestätigung (bucht NICHT automatisch!)
4. ✅ Nach "Ja": Agent ruft book_appointment_v17 auf
5. ✅ Laravel Logs zeigen "🔍 V17:" und "✅ V17:"
6. ✅ Dauer < 90 Sekunden

### FAILURE wenn:
❌ Kein Tool-Call (wie V15/V16 Bug)
❌ Agent sagt "ich prüfe" aber Tool wird nicht aufgerufen
❌ Automatische Buchung ohne Bestätigung
❌ Falscher Node (z.B. func_08_availability_check statt func_check_availability)

---

## 📊 Erwartetes Verhalten

### V17 Flow-Ablauf:
```
begin
  ↓
func_00_initialize
  ↓ Tool Call: initialize_call
  ↓
node_02_customer_routing
  ↓
node_03c_anonymous_customer (anonymous caller)
  ↓
node_04_intent_enhanced
  ↓
node_07_datetime_collection (sammelt: datum, uhrzeit)
  ↓
func_check_availability (V17 FUNCTION NODE!) ← KRITISCHER CHECK!
  ↓ Tool Call: check_availability_v17
  ↓ Backend: bestaetigung=false
  ↓
node_present_availability
  Agent: "Der Termin ist verfügbar. Soll ich das buchen?"
  ↓
User: "Ja"
  ↓
func_book_appointment (V17 FUNCTION NODE!)
  ↓ Tool Call: book_appointment_v17
  ↓ Backend: bestaetigung=true
  ↓
node_13_booking_confirmation
  Agent: "Perfekt! Ich habe den Termin gebucht."
```

---

## 📝 Für Produktions-Calls später

**Wenn Dashboard Test erfolgreich:**

1. Im Dashboard: **Publish Button** klicken (oben rechts)
2. Bestätigen
3. 15 Min warten (CDN Propagation)
4. Dann funktioniert V17 auch für echte Telefon-Calls

**Aber JETZT: Test im Dashboard ausführen!**

---

## 🔍 Debug bei Problemen

**Problem: Falscher Node verwendet**
```bash
# Check Flow Version im Dashboard
# Node Transitions sollten func_check_availability zeigen, nicht func_08_availability_check
```

**Problem: Kein Tool Call**
```bash
# Laravel Logs prüfen
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -A 5 -B 5 "V17:"

# Wenn KEIN "V17:" Log → Tool wurde nicht aufgerufen (V16 Bug noch aktiv)
```

**Problem: Double Greeting**
```bash
# Check Call Recording im Dashboard
# Sollte EINE Begrüßung sein, keine zwei mit Pause
```

---

**Status:** V17 Draft Version 25 deployed ✅
**Action:** Dashboard Test JETZT ausführen 🧪
**URL:** https://app.retellai.com/agent/agent_616d645570ae613e421edb98e7
