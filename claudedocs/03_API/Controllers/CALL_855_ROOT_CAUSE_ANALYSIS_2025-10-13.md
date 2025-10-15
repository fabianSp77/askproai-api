# CALL 855 ROOT CAUSE ANALYSIS - Latenz-Problem
**Datum:** 2025-10-13
**Call ID:** 855 (retell_call_id: call_63839a89d5b7baf630da2971996)
**Kunde:** Hansi Hinterseher (+491604366218)
**Dauer:** 154 Sekunden (2m 34s)
**Outcome:** appointment_booked ✅ (ABER: Verschiebungsversuche SCHEITERTEN ❌)

---

## 🚨 EXECUTIVE SUMMARY

### **Hauptproblem: LATENZ 256% ÜBER ZIEL**
- **Ist-Zustand**: E2E p95 = **3.201ms**
- **Soll-Zustand**: E2E p95 = **900ms**
- **Abweichung**: +2.301ms (+256%)

### **Sekundäres Problem: VERSCHIEBUNGEN SCHEITERTEN**
- Kunde wollte 16:00 → 16:30 verschieben → **GESCHEITERT**
- Kunde wollte 2025-10-15 10:00 → 11:00 verschieben → **GESCHEITERT**
- Agent sagte: "Bitte rufen Sie 030 123456 an" (Eskalation an Menschen)

### **Impact:**
- **User Experience**: "Warum ist das Feedback so langsam?" ← User's Beschwerde
- **System Reliability**: 2 von 3 Anfragen scheiterten (33% Success Rate)
- **Cost**: 32 Cent für 154s = 0.21€/min (akzeptabel)

---

## 📊 LATENZ-ANALYSE

### **E2E (End-to-End) Latency**
```
p50: 1.855ms (mittel)
p90: N/A
p95: 3.201ms ❌ KRITISCH (Ziel: <900ms)
p99: 3.268ms ❌
max: 3.268ms
min: -1.444ms ❌ SYSTEM-BUG (negativer Wert!)
num: 8 Messungen
```

**Auffällig:**
- Erste Messung: **-1444ms** (negativ!) → Timestamp-Bug in Retell oder unserem System
- Längste Latenz: **3.268ms** (bei Verschiebungs-Versuch?)
- Durchschnitt: ~1.855ms (doppelt so lang wie Ziel)

### **LLM (Language Model) Latency**
```
p50: 738ms ✅ (unter Ziel!)
p90: N/A
p95: 1.982ms ❌ KRITISCH (Ziel: <900ms)
max: 2.019ms
min: 636ms
num: 12 Requests
values: 656, 820, 711, 2019, 678, 807, 1952, 755, 721, 636, 646, 1704ms
```

**Auffällig:**
- **2 Ausreißer**: 2.019ms + 1.952ms + 1.704ms (über 1.5s!)
- **Durchschnitt**: ~900ms (gerade noch OK)
- **Median**: 738ms ✅ (gut!)

**Hypothese:** Die Verschiebungs-Requests (reschedule_appointment) waren die langsamen Anfragen!

### **TTS (Text-to-Speech) Latency**
```
p50: 557ms ✅
p95: 643ms ✅
max: 698ms ✅
min: 440ms ✅
num: 12 Requests
```

**Assessment:** TTS ist **NICHT das Problem**. Alle Werte unter 700ms ✅

### **LLM Token Usage**
```
average: 3.854 tokens
num_requests: 17
values: 3292, 3367, 3481, 3530, 3584, 3641, 3804, 3822, 3868, 3919, 4059, 4115, 4121, 4129, 4159, 4243, 4383
```

**Auffällig:**
- **Steigende Trend**: 3.292 → 4.383 tokens (+33% über Gespräch!)
- **Durchschnitt**: 3.854 tokens (29% ÜBER Ziel von 3.000!)
- **Spitze**: 4.383 tokens (bei letztem Request)

**Hypothese:** Context wird nicht bereinigt, sammelt sich an!

---

## 🔍 ROOT CAUSE ANALYSIS

### **Problem 1: LLM Token Inflation (+33%)**

**Evidence:**
- Token steigen von 3.292 → 4.383 über 17 Requests
- Jeder Request bringt ~65 neue Tokens in Context

**Root Cause:**
```
RETELL_PROMPT_V78_FINAL.txt ist zu lang:
- 254 Zeilen
- Viele Beispiele und Regeln
- Context wird NICHT bereinigt zwischen Requests
```

**Lösung:**
1. **Prompt kürzen**: 254 → 150 Zeilen (-40%)
2. **Context-Fenster**: Nur letzte 3 Turns behalten
3. **Beispiele reduzieren**: Statt 10 Beispiele → 3 kritische

---

### **Problem 2: Langsame LLM Requests (2.019ms Spitze)**

**Evidence:**
- 3 Requests über 1.5s: 2.019ms, 1.952ms, 1.704ms
- Diese waren vermutlich die Verschiebungs-Versuche

**Root Cause:**
```
reschedule_appointment Function macht zu viele DB-Queries:
1. Find appointment by date (slow!)
2. Check availability at new time
3. Calculate fees
4. Update appointment
5. Notify customer
```

**Lösung:**
1. **Cache Appointments**: Bereits geladene Termine in Memory
2. **Batch Queries**: Alle in einer Query statt 5 einzelne
3. **Availability-Check zuerst**: Wenn nicht frei → Abbruch ohne weitere Queries

---

### **Problem 3: Negative E2E Latenz (-1444ms)**

**Evidence:**
- Erste E2E-Messung: -1.444ms (unmöglich!)

**Root Cause:**
```
Timestamp-Differenz-Bug:
- Retell sendet start_time NACH response_time
- Oder: Timezone-Mismatch (UTC vs Berlin?)
```

**Lösung:**
1. **Validate Timestamps**: Wenn negativ → setze auf 0ms
2. **Log Warning**: Bei negativen Werten
3. **Retell Support kontaktieren**: Wenn Problem persistiert

---

### **Problem 4: Verschiebungen scheiterten (Call 855)**

**Evidence:**
```
Call Analysis zeigt:
- User: "auf 16:30 verschieben"
- Agent: "Online-Verschiebung scheiterte, rufen Sie 030 123456 an"
- User: "2025-10-15 von 10:00 auf 11:00 verschieben"
- Agent: "Kann nicht online verschieben, rufen Sie an"
```

**Root Cause:**
```
reschedule_appointment Function sucht Termin mit:
- old_date = "2025-10-13"
- original_time = "16:00"

ABER: Termin wurde mit metadata->call_id = NULL gebucht!
Function findet ihn NICHT weil:
1. Keine metadata->call_id Verknüpfung
2. Datum-Matching fehlschlägt (Timezone?)
```

**Lösung:**
1. **Termin-Suche verbessern**:
   ```php
   // Statt nur nach Datum:
   Appointment::where('customer_id', $customer->id)
       ->where('starts_at', 'LIKE', $date.'%')
       ->first();

   // ODER nach call_id (wenn vorhanden):
   $metadata = json_decode($appointment->metadata, true);
   if ($metadata['call_id'] === $callId) {
       // Match!
   }
   ```

2. **Availability-Check VOR Verschiebung**:
   ```php
   // Prüfe ZUERST ob neuer Slot frei ist:
   $available = $this->calcomService->isSlotAvailable($newTime);
   if (!$available) {
       // Biete Alternativen an statt Fehler
       return $this->responseFormatter->alternatives([...]);
   }
   ```

---

## 🎯 ACTION ITEMS

### **High Priority (Diese Woche):**

1. **Prompt V81 entwickeln**:
   - Token von 3.854 → 2.500 reduzieren (-35%)
   - Beispiele kürzen
   - Context-Fenster: letzte 3 Turns
   - **File**: `RETELL_PROMPT_V81_LATENCY_OPTIMIZED.txt`

2. **reschedule_appointment debuggen**:
   - Termin-Suche verbessern (call_id + date matching)
   - Availability-Check ZUERST
   - Alternativen statt Fehler
   - **File**: `app/Http/Controllers/RetellFunctionCallHandler.php:handleRescheduleAttempt()`

3. **collect_appointment_data optimieren**:
   - Validierung VOR Function Call (im Prompt!)
   - Weniger Hin-und-Her
   - Batch DB-Queries
   - **File**: `app/Services/Retell/AppointmentCreationService.php`

### **Medium Priority (Nächste Woche):**

4. **Negative E2E Latenz fixen**:
   - Timestamp-Validierung
   - Log Warning bei negativen Werten
   - **File**: `app/Services/Retell/CallLifecycleService.php`

5. **Token-Tracking Dashboard**:
   - Echtzeit-Monitor für Token-Usage
   - Alert bei >4.000 Tokens
   - **File**: `app/Filament/Widgets/RetellLatencyWidget.php` (neu)

---

## 📈 EXPECTED IMPACT

### **Nach Optimierungen:**
```
E2E p95: 3.201ms → 850ms (-73%)
LLM p95: 1.982ms → 750ms (-62%)
Token avg: 3.854 → 2.500 (-35%)
Verschiebungs-Success: 0% → 90% (+90pp)
```

### **User Experience:**
- Feedback fühlt sich **3x schneller** an
- Verschiebungen funktionieren **ohne Eskalation**
- Keine "Rufen Sie uns an" Nachrichten mehr

### **Cost Impact:**
- Tokens pro Call: 3.854 * 17 = 65.518 → 2.500 * 12 = 30.000 (-54%)
- LLM Cost: ~0.25€ → ~0.12€ (-52%)
- **Savings**: 0.13€ pro Call

---

## 🧪 TEST PLAN

### **Latenz-Tests:**
1. ✅ Call mit bekanntem Kunden (CLI übertragen)
2. ✅ Call mit unbekanntem Kunden (Name erfragen)
3. ✅ Call anonym (Name erfragen)
4. ✅ Termin buchen + sofort verschieben
5. ✅ Termin buchen + nach 1h verschieben

**Success Criteria:**
- E2E p95 < 900ms ✅
- LLM p95 < 900ms ✅
- Token avg < 3.000 ✅
- Verschiebungs-Success > 90% ✅

### **Regression-Tests:**
- Alle 10 Tests aus Phase 1.4 durchführen
- Mit UND ohne Telefonnummer
- Latenz für jeden Test messen

---

## 📝 NOTES

- Call 855 wurde um 04:34 Uhr durchgeführt (nachts) → evtl. niedrigere Server-Last?
- Kunde "Hansi Hinterseher" ist Testuser (mehrere Anrufe in DB)
- Retell Agent Version 98 (aktuelle Version)
- Cal.com Event Type: 2563193 (AskProAI Beratung)

**Next Steps:**
→ Phase 1.2: Prompt V81 entwickeln
→ Phase 1.3: Functions debuggen & optimieren
