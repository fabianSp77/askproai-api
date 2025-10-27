# 📞 V4 Testanleitung - Sofort bereit!

## ✅ Status: DEPLOYED & LIVE

Agent verwendet jetzt **Flow Version 5** (V4 mit Intent Detection)

---

## 🎯 Schnelltest (2 Minuten)

### Test 1: Intent Detection funktioniert?

**Anrufen und sagen**: "Guten Tag, ich möchte einen Termin buchen"

**Erwartetes Verhalten**:
1. ✅ AI begrüßt dich
2. ✅ AI erkennt Intent "Termin buchen"
3. ✅ AI fragt nach Name, Dienstleistung, Datum, Uhrzeit
4. ✅ AI prüft Verfügbarkeit
5. ✅ AI bucht Termin

**Zu prüfen in Logs**:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep intent_router
```

**Erwartete Log-Zeile**:
```json
{
  "node_transition": {
    "former_node_id": "node_greeting",
    "new_node_id": "intent_router",  // ✅ Das muss drin sein!
    "new_node_name": "Intent Erkennung"
  }
}
```

**Falls du siehst** `"new_node_id": "node_collect_info"` → ❌ Agent nutzt alten Flow!

---

## 🧪 Ausführliche Tests

### Szenario 1: Termin Buchen (V3 Path - MUSS funktionieren!)

**Dialog**:
```
Du:  "Termin buchen bitte"
AI:  "Gerne! Wie ist Ihr Name?"
Du:  "Max Mustermann"
AI:  "Welche Dienstleistung möchten Sie?"
Du:  "Herrenhaarschnitt"
AI:  "An welchem Datum?"
Du:  "Morgen"
AI:  "Um wie viel Uhr?"
Du:  "14 Uhr"
AI:  [Prüft Verfügbarkeit]
AI:  "Passt! Soll ich buchen?"
Du:  "Ja"
AI:  "Gebucht! Bestätigung folgt."
```

**Erfolgskriterium**: ✅ Termin wird in Cal.com angelegt

---

### Szenario 2: Termine Anzeigen (NEU!)

**Dialog**:
```
Du:  "Welche Termine habe ich?"
AI:  [Listet deine Termine auf]
     "Sie haben folgende Termine:
      - 26.10.2025 um 14:00 Uhr - Herrenhaarschnitt
      - 02.11.2025 um 10:00 Uhr - Färben"
```

**Erfolgskriterium**: ✅ Alle Termine werden angezeigt

---

### Szenario 3: Termin Stornieren (NEU!)

**Dialog**:
```
Du:  "Ich möchte einen Termin stornieren"
AI:  "Welchen Termin möchten Sie stornieren?"
Du:  "Den am 26.10. um 14 Uhr"
AI:  "Soll ich den Termin am 26.10.2025 um 14:00 Uhr wirklich stornieren?"
Du:  "Ja"
AI:  "Storniert!"
```

**Erfolgskriterium**: ✅ Termin wird in Cal.com gelöscht

---

### Szenario 4: Termin Verschieben (NEU - KRITISCH!)

**Dialog**:
```
Du:  "Termin verschieben"
AI:  "Welchen Termin möchten Sie verschieben?"
Du:  "Den am 26.10. um 14 Uhr"
AI:  "Auf welches neue Datum?"
Du:  "27.10."
AI:  "Um wie viel Uhr?"
Du:  "15 Uhr"
AI:  [Führt Transaction durch]
AI:  "Verschoben! Neuer Termin: 27.10.2025 um 15:00 Uhr"
```

**Erfolgskriterium**:
- ✅ Alter Termin in Cal.com gelöscht
- ✅ Neuer Termin in Cal.com angelegt
- ✅ Bei Fehler: Rollback (nichts verändert)

---

### Szenario 5: Services Anzeigen (NEU!)

**Dialog**:
```
Du:  "Was bieten Sie an?"
AI:  "Wir bieten folgende Dienstleistungen:
      - Herrenhaarschnitt: 25€, 30 Minuten
      - Damenhaarschnitt: 35€, 45 Minuten
      - Färben: 65€, 90 Minuten
      ..."
```

**Erfolgskriterium**: ✅ Alle Services mit Preisen angezeigt

---

## 🔍 Analyse nach Testanruf

Nach jedem Anruf:

```bash
php analyze_latest_call.php
```

**Wichtige Checks**:

1. **Intent Router verwendet?**
   ```json
   {
     "node_transition": {
       "new_node_id": "intent_router"  // ✅ Muss hier sein!
     }
   }
   ```

2. **Richtiger Intent erkannt?**
   ```json
   {
     "dynamic_variables": {
       "detected_intent": "book_new_appointment"  // oder check, cancel, reschedule, services
     }
   }
   ```

3. **Call erfolgreich?**
   ```json
   {
     "call_successful": true
   }
   ```

---

## ❌ Fehlersuche

### Problem: Agent nutzt alten Flow

**Symptom**: Log zeigt `node_collect_info` statt `intent_router`

**Lösung**:
```bash
php publish_agent_v4_force.php
```

---

### Problem: Intent falsch erkannt

**Symptom**: "Termin buchen" → Geht zu "Services"

**Lösung**: Edge Conditions in Flow anpassen

```bash
# Flow JSON editieren
vim friseur1_conversation_flow_v4_complete.json

# Intent Prompts schärfen
# Dann neu deployen
php deploy_flow_v4.php
```

---

### Problem: Reschedule schlägt fehl

**Symptom**: Alter Termin gelöscht, aber neuer nicht angelegt

**Check**:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "DB::beginTransaction"
```

**Erwartung**: Transaction rollback bei Fehler

---

## 📊 Monitoring während Test

**Terminal 1**: Logs live verfolgen
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E 'V4|intent|appointment'
```

**Terminal 2**: Anruf machen
```
[Telefon anrufen]
```

**Terminal 3**: Nach Anruf analysieren
```bash
php analyze_latest_call.php
```

---

## ✅ Erfolgskriterien

### Muss funktionieren:
- ✅ Intent Detection: >90% korrekt
- ✅ Booking (V3 Path): 100% wie vorher
- ✅ Check Appointments: Listet korrekt
- ✅ Cancel: Synct mit Cal.com
- ✅ Reschedule: Transaction-safe
- ✅ Services: Zeigt alle an

### Performance:
- ✅ Intent Router: <500ms
- ✅ Booking: <3s
- ✅ Reschedule: <5s

---

## 🚨 Rollback (Falls nötig)

Wenn V4 Probleme macht:

```bash
# Zurück zu V3
php deploy_flow_v3.php

# Agent neu publishen
php publish_agent_v4_force.php
```

---

## 📞 Jetzt testen!

**Status**: ✅ Agent ist LIVE mit Flow V5 (V4)

**Nächster Schritt**: Testanruf machen und Logs prüfen!

```bash
# Logs vorbereiten
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E 'intent_router|V4'

# Dann: Anrufen! 📞
```

---

**Viel Erfolg beim Testen! 🎉**
