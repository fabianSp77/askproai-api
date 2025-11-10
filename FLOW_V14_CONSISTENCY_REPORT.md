# Conversation Flow V14 - Konsistenzanalyse Report

**Datum**: 2025-11-03
**Flow ID**: conversation_flow_a58405e3f67a
**Version**: 14
**Status**: ⚠️ **KRITISCHE PROBLEME GEFUNDEN**

---

## Executive Summary

Die Konsistenzanalyse hat **kritische Probleme** identifiziert, die verhindern dass **Stornierung** und **Verschiebung** funktionieren. Der Buchungs-Flow ist korrekt konfiguriert.

### Status Overview

| Kategorie | Status | Details |
|-----------|--------|---------|
| Flow Struktur | ✅ OK | 18 Nodes, alle erreichbar, keine Sackgassen |
| Tool URLs | ✅ OK | Alle 6 Tools nutzen korrekte zentrale URL |
| Tool Parameter Mapping | ✅ OK | Alle required Parameter gemapped |
| **Buchungs-Flow** | ✅ OK | State Management funktioniert korrekt |
| **Stornierung-Flow** | ❌ BROKEN | Variables nicht deklariert/gesammelt |
| **Verschiebung-Flow** | ❌ BROKEN | Variables nicht deklariert/gesammelt |
| Variable Konsistenz | ⚠️ WARNING | 7 undeklarierte, 1 ungenutzte Variable |

---

## 🔴 KRITISCHE PROBLEME

### Problem 1: Stornierung - Variables werden nicht gesammelt

**Symptom**: Der Node "Stornierungsdaten sammeln" sammelt KEINE Daten!

**Root Cause**: Die Variables `cancel_datum` und `cancel_uhrzeit` werden:
- ❌ NICHT im global_prompt deklariert
- ❌ NICHT in der Node-Instruction erwähnt
- ❌ NIEMALS gesetzt (SET)
- ✅ Aber von Function Node erwartet (READ)

**Impact**:
```
User: "Ich möchte meinen Termin morgen um 14 Uhr stornieren"
→ Node sammelt KEINE Daten in Variables
→ func_cancel_appointment wird aufgerufen mit:
   {
     "call_id": "call_xyz",
     "datum": null,      // ❌ FEHLT!
     "uhrzeit": null     // ❌ FEHLT!
   }
→ Backend kann Termin nicht identifizieren
→ Stornierung schlägt fehl
```

**Betroffener Node**: `node_collect_cancel_info`

**Aktuelle Instruction**:
```
"Welchen Termin möchten Sie stornieren?

**Frage nach:**
- Datum (heute, morgen, oder DD.MM.YYYY) UND Uhrzeit (HH:MM)
- ODER zeige Liste und lass Kunden wählen

**Sobald identifiziert:** → func_cancel_appointment"
```

**PROBLEM**: Keine Variable-Zuweisung! Der Agent fragt nach Daten, speichert sie aber NICHT.

---

### Problem 2: Verschiebung - Variables werden nicht gesammelt

**Symptom**: Der Node "Verschiebungsdaten sammeln" sammelt KEINE Daten!

**Root Cause**: Die Variables `old_datum`, `old_uhrzeit`, `new_datum`, `new_uhrzeit` werden:
- ❌ NICHT im global_prompt deklariert
- ❌ NICHT in der Node-Instruction erwähnt
- ❌ NIEMALS gesetzt (SET)
- ✅ Aber von Function Node erwartet (READ)

**Impact**:
```
User: "Ich möchte meinen Termin morgen 14 Uhr auf Donnerstag 16 Uhr verschieben"
→ Node sammelt KEINE Daten in Variables
→ func_reschedule_appointment wird aufgerufen mit:
   {
     "call_id": "call_xyz",
     "old_datum": null,      // ❌ FEHLT!
     "old_uhrzeit": null,    // ❌ FEHLT!
     "new_datum": null,      // ❌ FEHLT!
     "new_uhrzeit": null     // ❌ FEHLT!
   }
→ Backend kann Termin nicht verschieben
→ Verschiebung schlägt fehl
```

**Betroffener Node**: `node_collect_reschedule_info`

**Aktuelle Instruction**:
```
"Welchen Termin möchten Sie verschieben, und auf wann?

**Sammle:**
1. Alter Termin: Datum (heute, morgen, oder DD.MM.YYYY) + Uhrzeit (HH:MM)
2. Neuer Wunschtermin: Datum (heute, morgen, oder DD.MM.YYYY) + Uhrzeit (HH:MM)

**WICHTIG:** Sammle BEIDE (alt + neu) komplett bevor du zur Function gehst"
```

**PROBLEM**: Instruction sagt "sammle" aber KEINE Variable-Zuweisung!

---

### Problem 3: Ungenutzte Variable

**Variable**: `booking_confirmed`

**Deklariert**: Ja (global_prompt: "Buchungsstatus")
**Verwendet**: NIEMALS (0x)

**Impact**: Minimal - nur overhead, keine Funktionalität betroffen

**Empfehlung**: Entfernen aus global_prompt

---

## ✅ WAS FUNKTIONIERT KORREKT

### Buchungs-Flow ✅

Der Hauptbuchungsprozess ist **perfekt konfiguriert**:

```
✅ Variables deklariert: customer_name, service_name, appointment_date, appointment_time
✅ State Management: Prüft bereits vorhandene Daten
✅ Skip-Logik: Fragt nicht doppelt
✅ Lifecycle: SET → READ Flow korrekt
✅ Parameter Mapping: {{call.call_id}} korrekt
```

**Instruction-Beispiel (RICHTIG)**:
```
"## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Name: {{customer_name}}
- Service: {{service_name}}
- Datum: {{appointment_date}}
- Uhrzeit: {{appointment_time}}

**Wenn Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable"
```

→ **Dieser Ansatz muss für Stornierung/Verschiebung übernommen werden!**

---

## 🔧 KONKRETE FIXES

### Fix 1: Global Prompt erweitern

**Datei**: Conversation Flow V14 → global_prompt

**Hinzufügen**:
```
## WICHTIG: State Management

**Du hast Zugriff auf Dynamic Variables:**
- {{customer_name}} - Name des Kunden
- {{service_name}} - Gewünschter Service
- {{appointment_date}} - Gewünschtes Datum
- {{appointment_time}} - Gewünschte Uhrzeit
+ {{cancel_datum}} - Datum für Stornierung
+ {{cancel_uhrzeit}} - Uhrzeit für Stornierung
+ {{old_datum}} - Alter Termin Datum für Verschiebung
+ {{old_uhrzeit}} - Alter Termin Uhrzeit für Verschiebung
+ {{new_datum}} - Neuer Termin Datum für Verschiebung
+ {{new_uhrzeit}} - Neuer Termin Uhrzeit für Verschiebung
- {{booking_confirmed}} - Buchungsstatus  // ❌ ENTFERNEN (ungenutzt)
```

---

### Fix 2: Stornierungsdaten sammeln Node

**Node ID**: `node_collect_cancel_info`

**NEUE Instruction** (nach Buchungs-Node Muster):
```
## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Datum für Stornierung: {{cancel_datum}}
- Uhrzeit für Stornierung: {{cancel_uhrzeit}}

**Deine Aufgabe:**
1. **ANALYSIERE den Transcript** - Welchen Termin möchte der Kunde stornieren?
2. **PRÜFE die Variablen** - Welche sind noch leer?
3. **FRAGE NUR** nach fehlenden Daten!

**Fehlende Daten erkennen:**
- Wenn {{cancel_datum}} leer → Frage: "Für welchen Tag möchten Sie stornieren?" (heute/morgen/DD.MM.YYYY)
- Wenn {{cancel_uhrzeit}} leer → Frage: "Um welche Uhrzeit war der Termin?" (HH:MM)

**WENN Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable

**Beispiel - User sagt alles:**
User: "Ich möchte meinen Termin morgen um 14 Uhr stornieren"
→ cancel_datum = "morgen"
→ cancel_uhrzeit = "14:00"
→ Antworte: "Verstanden. Einen Moment, ich storniere Ihren Termin..."
→ Transition zu func_cancel_appointment

**Transition:**
- Sobald BEIDE Variablen gefüllt → func_cancel_appointment
```

**Edge Condition UPDATE**:
```
OLD: "Appointment to cancel identified (either appointment_id OR datum+uhrzeit)"
NEW: "ALL variables filled: {{cancel_datum}} AND {{cancel_uhrzeit}}"
```

---

### Fix 3: Verschiebungsdaten sammeln Node

**Node ID**: `node_collect_reschedule_info`

**NEUE Instruction** (nach Buchungs-Node Muster):
```
## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Alter Termin Datum: {{old_datum}}
- Alter Termin Uhrzeit: {{old_uhrzeit}}
- Neuer Termin Datum: {{new_datum}}
- Neuer Termin Uhrzeit: {{new_uhrzeit}}

**Deine Aufgabe:**
1. **ANALYSIERE den Transcript** - Welchen Termin möchte der Kunde verschieben und auf wann?
2. **PRÜFE die Variablen** - Welche sind noch leer?
3. **FRAGE NUR** nach fehlenden Daten!

**Fehlende Daten erkennen:**
- Wenn {{old_datum}} leer → Frage: "Welcher Termin soll verschoben werden? An welchem Tag?" (heute/morgen/DD.MM.YYYY)
- Wenn {{old_uhrzeit}} leer → Frage: "Um welche Uhrzeit war der Termin?" (HH:MM)
- Wenn {{new_datum}} leer → Frage: "Auf welchen Tag möchten Sie verschieben?" (heute/morgen/DD.MM.YYYY)
- Wenn {{new_uhrzeit}} leer → Frage: "Um welche Uhrzeit?" (HH:MM)

**WENN Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable

**Beispiel - User sagt alles:**
User: "Ich möchte meinen Termin morgen 14 Uhr auf Donnerstag 16 Uhr verschieben"
→ old_datum = "morgen"
→ old_uhrzeit = "14:00"
→ new_datum = "Donnerstag"
→ new_uhrzeit = "16:00"
→ Antworte: "Perfekt! Einen Moment, ich verschiebe den Termin..."
→ Transition zu func_reschedule_appointment

**Transition:**
- Sobald ALLE 4 Variablen gefüllt → func_reschedule_appointment
```

**Edge Condition UPDATE**:
```
OLD: "All required data collected: old appointment identified AND new datum+uhrzeit collected"
NEW: "ALL variables filled: {{old_datum}} AND {{old_uhrzeit}} AND {{new_datum}} AND {{new_uhrzeit}}"
```

---

## 📊 VARIABLE CONSISTENCY SUMMARY

### Vor den Fixes

| Variable | Deklariert | Verwendet | Status |
|----------|------------|-----------|--------|
| customer_name | ✅ | 5x | ✅ OK |
| service_name | ✅ | 6x | ✅ OK |
| appointment_date | ✅ | 7x | ✅ OK |
| appointment_time | ✅ | 7x | ✅ OK |
| booking_confirmed | ✅ | 0x | ⚠️ UNUSED |
| call.call_id | ❌ | 6x | ⚠️ OK (System) |
| cancel_datum | ❌ | 1x | ❌ MISSING |
| cancel_uhrzeit | ❌ | 1x | ❌ MISSING |
| old_datum | ❌ | 1x | ❌ MISSING |
| old_uhrzeit | ❌ | 1x | ❌ MISSING |
| new_datum | ❌ | 2x | ❌ MISSING |
| new_uhrzeit | ❌ | 2x | ❌ MISSING |

### Nach den Fixes

| Variable | Deklariert | Verwendet | Status |
|----------|------------|-----------|--------|
| customer_name | ✅ | 5x | ✅ OK |
| service_name | ✅ | 6x | ✅ OK |
| appointment_date | ✅ | 7x | ✅ OK |
| appointment_time | ✅ | 7x | ✅ OK |
| call.call_id | System | 6x | ✅ OK |
| cancel_datum | ✅ | 1x | ✅ OK |
| cancel_uhrzeit | ✅ | 1x | ✅ OK |
| old_datum | ✅ | 1x | ✅ OK |
| old_uhrzeit | ✅ | 1x | ✅ OK |
| new_datum | ✅ | 2x | ✅ OK |
| new_uhrzeit | ✅ | 2x | ✅ OK |

---

## 🎯 IMPLEMENTATION PLAN

### Phase 1: Global Prompt Update (5 Minuten)
1. Öffne Retell Dashboard → Conversation Flow V14
2. Bearbeite global_prompt
3. Füge 6 neue Variable-Deklarationen hinzu
4. Entferne `booking_confirmed`
5. Speichern

### Phase 2: Stornierung Node Update (10 Minuten)
1. Öffne Node "Stornierungsdaten sammeln"
2. Ersetze Instruction mit neuem Text (siehe Fix 2)
3. Update Edge Condition
4. Speichern

### Phase 3: Verschiebung Node Update (10 Minuten)
1. Öffne Node "Verschiebungsdaten sammeln"
2. Ersetze Instruction mit neuem Text (siehe Fix 3)
3. Update Edge Condition
4. Speichern

### Phase 4: Publish & Test (15 Minuten)
1. Publish Conversation Flow → V15
2. Update Agent zu V15
3. Publish Agent
4. Test-Calls durchführen:
   - ✅ Buchung: "Herrenhaarschnitt morgen 16 Uhr, Hans Schuster"
   - 🧪 Stornierung: "Ich möchte meinen Termin morgen 14 Uhr stornieren"
   - 🧪 Verschiebung: "Ich möchte morgen 14 Uhr auf Donnerstag 16 Uhr verschieben"

**Geschätzte Gesamtzeit**: 40 Minuten

---

## 🧪 TEST SCENARIOS

### Test 1: Buchung (sollte bereits funktionieren)
```
User: "Herrenhaarschnitt morgen 16 Uhr, Hans Schuster"
Expected:
  ✅ customer_name = "Hans Schuster"
  ✅ service_name = "Herrenhaarschnitt"
  ✅ appointment_date = "morgen"
  ✅ appointment_time = "16:00"
  ✅ check_availability aufgerufen
  ✅ Verfügbarkeit geprüft
```

### Test 2: Stornierung (aktuell BROKEN, nach Fix OK)
```
User: "Ich möchte meinen Termin morgen um 14 Uhr stornieren"
Expected NACH Fix:
  ✅ cancel_datum = "morgen"
  ✅ cancel_uhrzeit = "14:00"
  ✅ cancel_appointment aufgerufen
  ✅ Termin storniert
```

### Test 3: Verschiebung (aktuell BROKEN, nach Fix OK)
```
User: "Ich möchte morgen 14 Uhr auf Donnerstag 16 Uhr verschieben"
Expected NACH Fix:
  ✅ old_datum = "morgen"
  ✅ old_uhrzeit = "14:00"
  ✅ new_datum = "Donnerstag"
  ✅ new_uhrzeit = "16:00"
  ✅ reschedule_appointment aufgerufen
  ✅ Termin verschoben
```

---

## 📝 AUTOMATED FIX SCRIPT

Ein Script zum automatischen Anwenden aller Fixes könnte erstellt werden, allerdings:

⚠️ **Retell API Limitation**: Die API unterstützt KEINE Node-Instruction-Updates via PATCH.

**Lösung**: Manuelle Anpassung im Dashboard (40 Min) ODER kompletter Flow-Export, lokale Bearbeitung, Re-Import.

---

## 🚦 RISK ASSESSMENT

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Buchungs-Flow bricht nach Update | LOW | HIGH | Sorgfältig testen, Backup V14 behalten |
| Variablen-Namen Typos | MEDIUM | MEDIUM | Copy-Paste aus diesem Report |
| Edge Conditions konfliktieren | LOW | MEDIUM | Systematisch testen |
| Flow V15 publish schlägt fehl | LOW | LOW | Retry, ggf. neue Version |

**Empfehlung**:
- ✅ Backup von V14 machen (bereits vorhanden)
- ✅ Änderungen in Staging testen (falls verfügbar)
- ✅ Schritt-für-Schritt vorgehen (nicht alles auf einmal)

---

## 🎯 PRIORITY

**P0 (CRITICAL)**:
- Fix 1 (Global Prompt) - Blockiert Stornierung/Verschiebung
- Fix 2 (Stornierung Node) - Funktionalität komplett broken
- Fix 3 (Verschiebung Node) - Funktionalität komplett broken

**P1 (LOW)**:
- Variable cleanup (booking_confirmed) - Nur overhead

---

**Report erstellt**: 2025-11-03 23:15
**Analysiert von**: Claude Code
**Nächster Schritt**: Fixes im Retell Dashboard implementieren
