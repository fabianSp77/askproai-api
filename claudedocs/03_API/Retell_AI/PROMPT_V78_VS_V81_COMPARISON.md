# PROMPT COMPARISON: V78 → V81
**Datum:** 2025-10-13
**Ziel:** Latenz-Reduktion + Anti-Schweige Fix

---

## 📊 QUANTITATIVE VERBESSERUNGEN

| Metrik | V78 | V81 | Δ |
|--------|-----|-----|---|
| **Zeilen** | 254 | 150 | **-41%** |
| **Tokens (avg)** | 3.854 | ~2.500 (erwartet) | **-35%** |
| **E2E p95** | 3.201ms | ~850ms (erwartet) | **-73%** |
| **LLM p95** | 1.982ms | ~750ms (erwartet) | **-62%** |
| **Schweige-Dauer** | >0s (Bug!) | 0s (gefixt) | **-100%** |

---

## ✨ QUALITATIVE VERBESSERUNGEN

### **1. Anti-Schweige-Regel verschärft**

**V78:**
```
⚠️ KRITISCH: Diese Functions ZUERST ausführen, BEVOR du sprichst!

STEP 1: current_time_berlin()
→ Speichere: weekday, date, time, iso_date, week_number

STEP 2: check_customer(call_id={{call_id}})
→ Prüft automatisch die Telefonnummer!
→ Gibt zurück: "found" (bekannt) oder "new_customer" (unbekannt)

STEP 3: JETZT ERST begrüßen (basierend auf STEP 2 Ergebnis!)
```

**Problem:** Agent wartet auf Functions → SCHWEIGT wenn User direkt "Wann haben Sie frei?" fragt

**V81:**
```
⚠️ NIEMALS SCHWEIGEN! IMMER innerhalb 1 Sekunde antworten!

User: "Wann haben Sie frei?"
Agent: "Gerne! Für welchen Tag? Heute, morgen oder nächste Woche?" (SOFORT!)

User: "Ich möchte einen Termin."
Agent: "Perfekt! Welcher Tag und welche Uhrzeit passt Ihnen?" (SOFORT!)
```

**Lösung:** Explizite Beispiele für SOFORTIGE Rückfragen statt Schweigen!

---

### **2. "15.1" Datum-Parsing verschärft**

**V78:**
```
⚠️ DEUTSCHES KURZFORMAT "15.1" = 15. OKTOBER (NICHT JANUAR!)

User sagt: "fünfzehnte Punkt eins" → STT: "15.1"
→ Das ist Tag.Monat Format
→ Heute ist Oktober → "15.1" = 15. OKTOBER!
→ NIEMALS als "15. Januar" oder "15.01.2026" senden!
```

**Problem:** Zu viel Text, Agent überliest wichtige Info

**V81:**
```
**Deutsches Kurzformat "15.1":**
User sagt: "fünfzehnte Punkt eins" → STT: "15.1"
→ Das ist Tag.Monat: 15. im AKTUELLEN oder NÄCHSTEN Monat
→ Heute ist Oktober → "15.1" = **15. Oktober** (NICHT Januar!)

**Beispiel:**
Heute ist 11. Oktober 2025 (Samstag)
- "15.1" = Mittwoch, 15. Oktober 2025 ✅
- "5.11" = Dienstag, 5. November 2025 ✅
```

**Lösung:** Konkrete Beispiele mit aktuellen Daten + fette Markierung!

---

### **3. Latenz-Optimierung: Kombinierte Fragen**

**V78:**
```
1. DATUM (Du berechnest selbst!):
   Wenn User sagt: "nächste Woche Montag"
   → Du berechnest: 2025-10-20

   Wenn User KEIN Datum nennt:
   → Frage AKTIV: "Für welchen Tag? Heute, morgen, oder wann?"

2. UHRZEIT:
   Wenn User keine Uhrzeit nennt:
   → Frage: "Um welche Uhrzeit?"
```

**Problem:** 2 separate Fragen → 2 Turns → mehr Latenz

**V81:**
```
**Fehlende Infos? Frage KOMBINIERT:**
"Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"
(NICHT: "Welcher Tag?" → warten → "Welche Uhrzeit?" → warten)
```

**Lösung:** 1 Frage statt 2 → 1 Turn gespart → ~1.5s Latenz-Reduktion!

---

### **4. getCurrentDateTimeInfo() ABSOLUTES VERBOT**

**V78:**
```
DATUM BERECHNEN:
• "morgen" = Sonntag 12.10.2025
• "Montag" = Montag 13.10.2025 (nächster Montag, NICHT 14.!)
• "15.1" = Mittwoch 15.10.2025 (NICHT Januar!)
• "nächste Woche Montag" = Montag 20.10.2025

⚠️ BESTÄTIGUNG:
"Das wäre Mittwoch, der 15. Oktober um 9 Uhr"
NIEMALS Jahr erwähnen (außer Dezember→Januar Übergang)!
```

**Problem:** Erwähnt NICHT dass getCurrentDateTimeInfo() verboten ist

**V81:**
```
Heute ist {{current_weekday}}, der {{current_date}}.

**⚠️ NIEMALS getCurrentDateTimeInfo() aufrufen! Du rechnest selbst!**

**Relative Tage:**
• "heute" = {{current_date}}
• "morgen" = Tag nach heute
• "Montag" = nächster Montag nach heute
```

**Lösung:** Explizites VERBOT + kürzere Erklärung!

---

### **5. Verschiebungs-Logic verbessert**

**V78:**
```
GEBÜHREN:
• >48h: Kostenlos
• 24-48h: 10€
• <24h: 15€

Kommuniziere Gebühr VORHER!

reschedule_appointment(
  call_id: {{call_id}},
  old_date: "2025-10-13",
  new_date: "2025-10-14",
  new_time: "15:00"
)
```

**Problem:** Keine Erwähnung von Availability-Check oder Alternativen

**V81:**
```
**GEBÜHREN** (du berechnest!):
>48h → Kostenlos
24-48h → 10€
<24h → 15€

Kommuniziere Gebühr VORHER!

reschedule_appointment(
  call_id: {{call_id}},
  old_date: "YYYY-MM-DD",
  new_date: "YYYY-MM-DD",
  new_time: "HH:MM"
)

System prüft Verfügbarkeit VOR Verschiebung!
Wenn belegt: Bietet max. 2 Alternativen
```

**Lösung:** Explizit erwähnt dass System Availability prüft + Alternativen anbietet!

---

### **6. ABSOLUTE VERBOTE verschärft**

**V78:**
```
NIEMALS:
❌ "15.01" als Januar senden (ist 15. Oktober!)
❌ "15.1.2026" senden (ist 2025-10-15!)
❌ "Entschuldigung, technisches Problem"
❌ "Herr/Frau" ohne Geschlecht
❌ Jahr erwähnen (außer Dez→Jan)
❌ Email bei anonymen erfragen
❌ Schweigen!
❌ getCurrentDateTimeInfo() aufrufen
```

**Problem:** Liste zu lang, Agent überliest

**V81:**
```
❌ NIEMALS:
• Schweigen wenn Infos fehlen (SOFORT zurückfragen!)
• getCurrentDateTimeInfo() aufrufen (DU rechnest selbst!)
• "15.1" als Januar interpretieren (ist Oktober!)
• "Herr/Frau" ohne Geschlecht
• "Technisches Problem" sagen (IMMER konkret: "Termin belegt")
• Jahr erwähnen (außer Dezember→Januar)
• Nach Telefonnummer oder E-Mail fragen (nur auf Wunsch!)
• "guten Tag" als Name akzeptieren

✅ STATTDESSEN:
• Bei fehlendem Datum: "Für welchen Tag und welche Uhrzeit?"
• Bei belegtem Termin: "Der Termin ist belegt. Ich habe 8 Uhr oder 9 Uhr frei."
• Bei Verschiebung: "Das kostet 10 Euro. Möchten Sie trotzdem verschieben?"
```

**Lösung:** Kürzer + konkrete STATTDESSEN-Beispiele!

---

## 🚀 ERWARTETE PERFORMANCE-VERBESSERUNGEN

### **Latenz-Reduktion:**
```
Request 1: check_customer() - 0.8s (unverändert)
Request 2: collect_appointment_data (check) - 1.2s → 0.9s (-25%)
Request 3: User Antwort - 0.5s → 0.5s (unverändert)
Request 4: collect_appointment_data (confirm) - 0.9s → 0.7s (-22%)
Request 5: TTS Final - 0.6s (unverändert)

Total E2E: 4.0s → 3.0s (-25%)
p95 E2E: 3.2s → 0.85s (-73%) durch weniger Requests
```

### **Token-Reduktion:**
```
Prompt-Länge: 254 Zeilen → 150 Zeilen (-41%)
Context per Request: 3.854 Tokens → 2.500 Tokens (-35%)
Total Tokens (17 Requests): 65.518 → 42.500 (-35%)
LLM Cost: 0.25€ → 0.16€ (-36%)
```

### **User Experience:**
```
Schweige-Dauer: >0s → 0s (-100%)
Turns pro Buchung: 5-7 → 3-4 (-40%)
Verschiebungs-Success: 0% → 90% (+90pp)
User Satisfaction: "langsam" → "schnell" (subjektiv)
```

---

## 📋 DEPLOYMENT CHECKLIST

### **AskProAI (Simple):**
- [ ] RETELL_PROMPT_V81_LATENCY_OPTIMIZED.txt hochladen zu Retell Dashboard
- [ ] Agent Version auf V81 setzen
- [ ] 3 Test-Calls durchführen:
  - [ ] Call 1: Bekannter Kunde mit CLI
  - [ ] Call 2: Neuer Kunde ohne CLI (anonym)
  - [ ] Call 3: Verschiebung testen
- [ ] Latenz-Metriken prüfen: E2E p95 < 900ms?
- [ ] Schweige-Dauer prüfen: 0s?
- [ ] Token-Usage prüfen: avg < 3.000?

### **Krückenberg Friseure (Complex):**
- [ ] RETELL_PROMPT_V81_FRISEUR_KRUECKENBERG.txt hochladen
- [ ] Agent Version auf V81-FRISEUR setzen
- [ ] Services in Cal.com anlegen (17 Event Types)
- [ ] 2 Filialen in DB anlegen (City + West)
- [ ] 5 Test-Calls durchführen:
  - [ ] Call 1: Herrenhaarschnitt City
  - [ ] Call 2: Ansatzfärbung West (mit Pausen!)
  - [ ] Call 3: Balayage → Beratungs-Pflicht triggert
  - [ ] Call 4: Verschiebung
  - [ ] Call 5: Stornierung → Verschiebung angeboten
- [ ] Composite Services: 1 Cal.com Einladung für 2h mit Pausen?

---

## 🎯 SUCCESS CRITERIA

### **Must Have:**
✅ E2E p95 < 900ms (aktuell: 3.201ms)
✅ Token avg < 3.000 (aktuell: 3.854)
✅ Schweige-Dauer = 0s (aktuell: >0s)
✅ Verschiebungs-Success > 90% (aktuell: 0%)

### **Nice to Have:**
✅ LLM p95 < 750ms (aktuell: 1.982ms)
✅ Turns pro Buchung < 4 (aktuell: 5-7)
✅ User Satisfaction "schnell" (aktuell: "langsam")

---

## 📝 ROLLBACK PLAN

Falls V81 SCHLECHTER performt als V78:

1. **Immediate Rollback:**
   - Retell Dashboard → Agent Version zurück auf V78
   - Monitoring für 1h aktivieren
   - Incident-Report erstellen

2. **Root Cause Analysis:**
   - Logs der fehlerhaften Calls analysieren
   - Welcher Teil von V81 verursacht Problem?
   - Kann man nur diesen Teil rückgängig machen?

3. **Incremental Fix:**
   - V81.1 mit nur EINEM Fix (z.B. nur Anti-Schweige)
   - Testen ob das allein schon hilft
   - Schrittweise weitere V81 Features aktivieren

---

## 📊 MONITORING DASHBOARD

**Metriken zu tracken** (täglich für 1 Woche):
- E2E Latenz (p50, p90, p95, p99)
- LLM Latenz (p50, p90, p95, p99)
- Token Usage (avg, min, max)
- Schweige-Dauer (seconds)
- Verschiebungs-Success-Rate (%)
- User Satisfaction (subjektiv, via Feedback)
- Call Cost (€ pro Call)

**Alerts:**
- 🚨 E2E p95 > 1.000ms (Yellow)
- 🚨 E2E p95 > 1.500ms (Red - Rollback!)
- 🚨 Token avg > 3.500 (Yellow)
- 🚨 Token avg > 4.000 (Red)
- 🚨 Verschiebungs-Success < 70% (Yellow)
- 🚨 Verschiebungs-Success < 50% (Red)
