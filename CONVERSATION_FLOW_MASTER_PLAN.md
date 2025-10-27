# 🎯 AskPro AI - Vollständiger Conversation Flow Master Plan

**Version:** 2.0 COMPLETE
**Datum:** 2025-10-22
**Ziel:** Alle Appointment-Funktionen mit perfekter Gesprächsführung

---

## 📊 USE CASES

### 1. Neue Buchung ✅ (bereits vorhanden)
- Kunde möchte neuen Termin buchen
- V85 Race Condition Schutz
- Alternative anbieten bei Nichtverfügbarkeit

### 2. Terminverschiebung 🆕 (neu)
- Kunde hat Termin, möchte verschieben
- Policy-Check: Ist Verschiebung erlaubt?
- Frist-Prüfung (z.B. mindestens 24h vorher)
- Neuen Termin finden und buchen

### 3. Stornierung 🆕 (neu)
- Kunde möchte Termin absagen
- Policy-Check: Ist Stornierung erlaubt?
- Frist-Prüfung (z.B. mindestens 24h vorher)
- Bestätigung einholen

### 4. Terminabfrage 🆕 (neu)
- Kunde fragt: "Wann ist mein Termin?"
- Alle Termine des Kunden anzeigen
- Details nennen (Datum, Zeit, Service)

---

## 🗣️ GESPRÄCHSFÜHRUNG - KRITISCHE PUNKTE

### Intent Recognition (SEHR WICHTIG!)

**Problem:** Kunde kann unklar formulieren:
- ❌ "Ich hab da n Termin..." → Buchen? Verschieben? Abfragen?
- ❌ "Geht das auch früher?" → Neu buchen oder verschieben?
- ❌ "Ich brauch was anderes" → Neuer Termin oder Service ändern?

**Lösung:**
```
Agent fragt IMMER nach bei Unklarheit:
"Möchten Sie einen NEUEN Termin vereinbaren oder einen BESTEHENDEN Termin ändern?"

Optionen:
1. Neuen Termin buchen
2. Bestehenden Termin verschieben
3. Bestehenden Termin absagen
4. Termin-Information abfragen
```

### Policy Violations (WICHTIG!)

**Szenario:** Kunde will 2 Stunden vorher stornieren, Policy erlaubt nur 24h+

**FALSCH:** ❌
```
"Das geht nicht, die Frist ist abgelaufen."
```

**RICHTIG:** ✅
```
"Ich verstehe, dass Sie den Termin absagen möchten. Leider können wir kurzfristige
Stornierungen erst ab 24 Stunden vor dem Termin kostenlos vornehmen.

Ihr Termin ist morgen um 14 Uhr. Sie haben folgende Möglichkeiten:

1. Ich kann den Termin trotzdem stornieren, es fällt aber eine Gebühr von 30€ an
2. Ich kann den Termin auf einen späteren Zeitpunkt verschieben
3. Der Termin bleibt bestehen

Was möchten Sie tun?"
```

### Unerwartete Reaktionen

**1. Kunde bricht ab während Buchung**
```
Agent: "Für welchen Tag möchten Sie den Termin?"
Kunde: "Ach wissen Sie was, ich rufe später nochmal an"

→ Node: polite_abort
→ "Kein Problem! Rufen Sie gerne jederzeit wieder an. Auf Wiederhören!"
```

**2. Kunde wird unsicher**
```
Agent: "Soll ich den Termin am Montag um 14 Uhr verbindlich buchen?"
Kunde: "Hmm... ich weiß nicht... vielleicht doch lieber..."

→ Node: uncertainty_handler
→ "Kein Problem, nehmen Sie sich Zeit. Möchten Sie lieber einen anderen Termin
   oder soll ich Ihnen die verfügbaren Zeiten nochmal nennen?"
```

**3. Kunde ändert Meinung während Prozess**
```
Agent: "Ich verschiebe Ihren Termin auf Dienstag..."
Kunde: "Moment, eigentlich will ich lieber stornieren"

→ Intent-Wechsel erlauben!
→ Zurück zu Intent Recognition
```

**4. Kunde kennt Datum nicht**
```
Agent: "Wann ist Ihr bestehender Termin?"
Kunde: "Keine Ahnung, können Sie nachschauen?"

→ Node: lookup_appointments
→ Alle Termine auflisten
```

---

## 🏗️ FLOW ARCHITEKTUR (NEU)

### Phase 1: Begrüßung & Kontext ✅
```
START
  ↓
Begrüßung
  ↓
Zeit & Kunde prüfen (parallel)
  ↓
Kunden-Routing (bekannt/neu/anonym)
```

### Phase 2: Intent Recognition 🆕
```
Intent-Erkennung
  ├─→ "Neuen Termin buchen" → PHASE 3A
  ├─→ "Termin verschieben" → PHASE 3B
  ├─→ "Termin stornieren" → PHASE 3C
  └─→ "Termin abfragen" → PHASE 3D
```

### Phase 3A: Neue Buchung ✅ (vorhanden)
```
Service auswählen → Datum/Zeit → Verfügbarkeit → Buchen
```

### Phase 3B: Verschiebung 🆕
```
1. Alten Termin identifizieren
   ├─→ Kunde kennt Datum → Direkt suchen
   └─→ Kunde kennt nicht → Alle Termine zeigen

2. Policy prüfen (func: check_reschedule_policy)
   ├─→ Erlaubt → Weiter
   └─→ Nicht erlaubt → Alternativen anbieten
       ├─→ Mit Gebühr verschieben
       ├─→ Stornieren und neu buchen
       └─→ Abbrechen

3. Neues Datum/Zeit erfragen

4. Verfügbarkeit prüfen

5. Verschieben (func: reschedule_appointment)
   ├─→ Erfolg → Bestätigung
   └─→ Fehler → Error Handling
```

### Phase 3C: Stornierung 🆕
```
1. Termin identifizieren
   ├─→ Kunde kennt Datum → Direkt suchen
   └─→ Kunde kennt nicht → Alle Termine zeigen

2. Policy prüfen (func: check_cancellation_policy)
   ├─→ Kostenlos möglich → Weiter
   ├─→ Mit Gebühr möglich → Bestätigung einholen
   └─→ Nicht möglich → Erklärung & Alternativen

3. Bestätigung einholen
   "Möchten Sie den Termin am {{date}} um {{time}} wirklich absagen?"

4. Stornieren (func: cancel_appointment)
   ├─→ Erfolg → Bestätigung
   └─→ Fehler → Error Handling
```

### Phase 3D: Terminabfrage 🆕
```
1. Alle Termine abrufen (func: get_customer_appointments)

2. Termine vorlesen
   ├─→ Keine Termine → "Sie haben aktuell keine Termine"
   ├─→ 1 Termin → Details nennen
   └─→ Mehrere → Liste vorlesen

3. Nachfrage
   "Möchten Sie einen dieser Termine ändern oder einen neuen vereinbaren?"
```

---

## 🔧 NEUE TOOLS

### Tool 4: check_reschedule_policy
```json
{
  "tool_id": "tool-check-reschedule-policy",
  "name": "check_reschedule_policy",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/check-reschedule-policy",
  "parameters": {
    "appointment_id": "string",
    "old_date": "string (YYYY-MM-DD)",
    "new_date": "string (YYYY-MM-DD)"
  },
  "returns": {
    "allowed": "boolean",
    "reason": "string",
    "fee": "number (optional)",
    "deadline": "string (datetime)"
  }
}
```

### Tool 5: reschedule_appointment
```json
{
  "tool_id": "tool-reschedule-appointment",
  "name": "reschedule_appointment",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/reschedule-appointment",
  "parameters": {
    "call_id": "string",
    "old_date": "string",
    "new_date": "string",
    "new_time": "string",
    "customer_name": "string"
  }
}
```

### Tool 6: check_cancellation_policy
```json
{
  "tool_id": "tool-check-cancellation-policy",
  "name": "check_cancellation_policy",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/check-cancellation-policy",
  "parameters": {
    "appointment_id": "string",
    "appointment_date": "string"
  },
  "returns": {
    "allowed": "boolean",
    "free": "boolean",
    "fee": "number (optional)",
    "reason": "string"
  }
}
```

### Tool 7: cancel_appointment
```json
{
  "tool_id": "tool-cancel-appointment",
  "name": "cancel_appointment",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/cancel-appointment",
  "parameters": {
    "call_id": "string",
    "appointment_date": "string",
    "customer_name": "string",
    "reason": "string"
  }
}
```

### Tool 8: get_customer_appointments
```json
{
  "tool_id": "tool-get-appointments",
  "name": "get_customer_appointments",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/get-customer-appointments",
  "parameters": {
    "call_id": "string",
    "customer_name": "string (optional)"
  },
  "returns": {
    "appointments": [
      {
        "id": "number",
        "date": "string",
        "time": "string",
        "service": "string",
        "status": "string"
      }
    ]
  }
}
```

---

## 📝 NEUE NODES (Übersicht)

### Intent & Routing
1. **node_04_intent_capture_enhanced** - Erweiterte Intent-Erkennung
2. **node_04a_intent_new_booking** - Weiterleitung zu Buchung
3. **node_04b_intent_reschedule** - Weiterleitung zu Verschiebung
4. **node_04c_intent_cancellation** - Weiterleitung zu Stornierung
5. **node_04d_intent_lookup** - Weiterleitung zu Abfrage

### Terminabfrage
6. **func_lookup_appointments** - Termine abrufen
7. **node_lookup_display** - Termine anzeigen
8. **node_lookup_action** - "Was möchten Sie tun?"

### Verschiebung
9. **node_reschedule_identify** - Alten Termin identifizieren
10. **func_check_reschedule_policy** - Policy prüfen
11. **node_reschedule_policy_result** - Policy Ergebnis mitteilen
12. **node_reschedule_datetime** - Neues Datum/Zeit erfragen
13. **func_reschedule_execute** - Verschiebung durchführen
14. **node_reschedule_success** - Erfolgsbestätigung

### Stornierung
15. **node_cancel_identify** - Termin identifizieren
16. **func_check_cancellation_policy** - Policy prüfen
17. **node_cancel_policy_result** - Policy Ergebnis mitteilen
18. **node_cancel_confirmation** - Bestätigung einholen
19. **func_cancel_execute** - Stornierung durchführen
20. **node_cancel_success** - Erfolgsbestätigung

### Edge Cases
21. **node_unknown_date** - Kunde kennt Datum nicht
22. **node_uncertainty_handler** - Kunde ist unsicher
23. **node_intent_change** - Intent-Wechsel während Prozess
24. **node_policy_violation_handler** - Policy nicht erfüllt

---

## 🎯 GESAMT-STATISTIK (NEU)

**Nodes:** 22 → **46 Nodes** (+24)
**Tools:** 3 → **8 Tools** (+5)
**End Nodes:** 3 → **6 End Nodes** (+3)
**Function Nodes:** 4 → **11 Function Nodes** (+7)
**Conversation Nodes:** 15 → **29 Conversation Nodes** (+14)

---

## ✅ QUALITÄTS-KRITERIEN

### Gesprächsführung
- ✅ Empathisch bei Stornierungen
- ✅ Klar bei Policy-Verletzungen
- ✅ Geduldig bei Unsicherheit
- ✅ Flexibel bei Intent-Wechsel

### Technisch
- ✅ Alle API-Endpoints vorhanden
- ✅ Policy Engine Integration
- ✅ Error Handling vollständig
- ✅ Multi-Tenant Security

### User Experience
- ✅ Keine Sackgassen
- ✅ Immer Alternativen anbieten
- ✅ Höfliche Abbruch-Möglichkeit
- ✅ Klare Bestätigungen

---

## 🚀 NÄCHSTE SCHRITTE

1. Backend API-Endpoints prüfen/erstellen
2. Tools definieren
3. Alle Nodes erstellen
4. Flow zusammenbauen
5. Validieren
6. Testen

**Start:** JETZT!
