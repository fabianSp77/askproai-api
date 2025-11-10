# Agent V47 - Ready for Testing
## Zeit: 2025-11-05 19:58 Uhr

---

## ✅ Alle Fixes Applied

### Fix 1: Preise/Dauer aus Service-Disambiguierung entfernt

**Vorher (V46):**
```
Agent: "Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder Damenhaarschnitt (45€, 45 Min)?"
```

**Nachher (V47):**
```
Agent: "Möchten Sie einen Herrenhaarschnitt oder Damenhaarschnitt?"
```

**Zusätzlich hinzugefügt:**
```markdown
⚠️ WICHTIG: Preise und Dauer NUR auf explizite Nachfrage nennen!
- Kunde fragt: "Was kostet ein Herrenhaarschnitt?" → Dann nenne Preis (32€)
- Kunde fragt: "Wie lange dauert das?" → Dann nenne Dauer (55 Min)
- Sonst: NUR Service-Namen nennen!
```

---

### Fix 2: Beispielzeiten entfernt + Tool-Call Enforcement

**Vorher (V46):**
```markdown
**Schritt 3: Zeige verfügbare Zeiten**
- Liste 3-5 verfügbare Slots
- Natürliche Sprache: "um 14:00, 16:30 und 18:00 Uhr"
```

**Problem:** Agent kopierte "14:00, 16:30, 18:00" 1:1 aus dem Beispiel!

**Nachher (V47):**
```markdown
**Schritt 3: Zeige verfügbare Zeiten AUS DER TOOL RESPONSE**
- ⚠️ KRITISCH: Zeige NUR Zeiten die check_availability zurückgegeben hat!
- ❌ NIEMALS eigene Zeiten erfinden oder aus Beispielen kopieren!
- Liste 3-5 verfügbare Slots aus der Tool Response
- Natürliche Sprache: "um [Zeit1], [Zeit2] und [Zeit3] Uhr"
```

---

### Fix 3: Tool-Call Enforcement Sektion hinzugefügt

**Neu in V47:**
```markdown
## ⚠️ PFLICHT: Tool Calls für Verfügbarkeit

**NIEMALS Verfügbarkeit erfinden!**

Wenn Kunde nach freien Terminen fragt:
1. ✅ DU MUSST check_availability CALLEN
2. ✅ Auf Tool Response warten
3. ✅ NUR Zeiten aus Response nennen
4. ❌ NIEMALS eigene Zeiten erfinden
5. ❌ NIEMALS Beispielzeiten aus diesem Prompt verwenden

**Das Tool gibt dir die ECHTEN verfügbaren Zeiten zurück - verwende NUR diese!**

**Beispiel RICHTIGES Verhalten:**
User: "Was ist heute frei?"
→ Du callst: check_availability(service="Herrenhaarschnitt", datum="heute")
→ Tool antwortet: ["19:00", "19:30", "20:00"]
→ Du sagst: "Für Herrenhaarschnitt haben wir heute um 19:00, 19:30 und 20:00 Uhr frei."

**Beispiel FALSCHES Verhalten:**
User: "Was ist heute frei?"
→ Du sagst: "Um 14:00, 16:30 und 18:00 Uhr" ❌ OHNE Tool zu callen!
```

---

### Fix 4: Dialog-Beispiel mit Platzhaltern

**Vorher (V46):**
```
Agent: "Für Damenhaarschnitt haben wir heute noch um 14:00, 16:30 und 18:00 Uhr frei."
User: "16:30 passt"
Agent: [bucht 16:30]
```

**Nachher (V47):**
```
Agent: "Für Damenhaarschnitt haben wir heute noch um [Zeit1], [Zeit2] und [Zeit3] Uhr frei."
User: "[Zeit2] passt"
Agent: [bucht gewählte Zeit]
```

---

## 📊 Changes Summary

```
Original V46:  9,898 Zeichen
Updated V47:  11,191 Zeichen
Difference:   +1,293 Zeichen
```

**Änderungen:**
- ✅ Preise/Dauer aus Service-Beispiel entfernt
- ✅ Preis-Notice hinzugefügt (nur auf Nachfrage)
- ✅ Beispielzeiten (14:00, 16:30, 18:00) aus Dialog entfernt
- ✅ Tool-Call Enforcement Sektion hinzugefügt (+1,274 Zeichen)
- ✅ Dialog-Beispiele mit Platzhaltern

---

## 📞 Testing Plan

### Test Scenario A: Service-Disambiguierung ohne Preise

**User Input:**
```
"Ich möchte einen Haarschnitt buchen"
```

**Erwartetes Verhalten:**
```
Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt oder Damenhaarschnitt?"
```

**❌ NICHT erwünscht:**
```
Agent: "... Herrenhaarschnitt (32€, 55 Min) oder Damenhaarschnitt (45€, 45 Min)"
```

**Success Criteria:**
- ✅ Agent fragt nach Herren vs. Damen
- ✅ KEINE Preise genannt
- ✅ KEINE Dauer genannt

---

### Test Scenario B: Proaktive Terminvorschläge mit check_availability

**User Input:**
```
"Was haben Sie heute noch frei?"
```

**Erwartetes Verhalten:**
```
1. Agent callt: check_availability(service="...", datum="heute")
2. Agent wartet auf Tool Response
3. Agent zeigt verfügbare Zeiten AUS DER RESPONSE
4. Agent fragt: "Welche Zeit passt Ihnen?"
```

**❌ NICHT erwünscht:**
```
Agent: "Um 14:00, 16:30 und 18:00 Uhr haben wir frei"
  → OHNE check_availability Call
  → OHNE zu prüfen ob Zeiten in Vergangenheit
```

**Success Criteria:**
- ✅ Agent ruft check_availability auf (sichtbar in Logs)
- ✅ Agent nennt NUR Zeiten aus Tool Response
- ✅ KEINE Zeiten in der Vergangenheit
- ✅ Zeiten sind REAL verfügbar (nicht erfunden)

---

### Test Scenario C: Preis auf explizite Nachfrage

**User Input:**
```
"Was kostet ein Herrenhaarschnitt?"
```

**Erwartetes Verhalten:**
```
Agent: "Ein Herrenhaarschnitt kostet 32€ und dauert 55 Minuten"
```

**Success Criteria:**
- ✅ Agent nennt Preis (32€)
- ✅ Agent nennt Dauer (55 Min)
- ✅ NUR wenn explizit gefragt!

---

## 🚀 Deployment Schritte

### 1. Publish V47 in Retell Dashboard
- Dashboard öffnen
- Agent `agent_45daa54928c5768b52ba3db736` auswählen
- Draft V47 publishen

### 2. Test Calls durchführen
- Test A: Service-Disambiguierung
- Test B: Proaktive Terminvorschläge
- Test C: Preis auf Nachfrage

### 3. Monitoring
Nach jedem Test Call:
```bash
# Neuesten Call analysieren
php scripts/analyze_test_call_detailed.php

# Function Calls prüfen (check_availability wurde gecallt?)
# Transcript prüfen (Preise/Dauer genannt?)
# Zeiten prüfen (in Vergangenheit? erfunden?)
```

---

## 📋 Agent V47 Status

```
Agent ID:      agent_45daa54928c5768b52ba3db736
Version:       47 (Draft)
Last Modified: 19:58:27 Uhr
Published:     NO (needs manual publish)
Flow ID:       conversation_flow_a58405e3f67a
Prompt Length: 11,191 Zeichen
```

---

## 🎯 Expected Improvements

### Problem 1: Preise/Dauer bei Service-Frage
**V46:** Agent nannte automatisch Preise/Dauer
**V47:** Agent nennt NUR Service-Namen
**Impact:** Bessere UX, Kunde wird nicht überladen

### Problem 2: Termine in der Vergangenheit
**V46:** Agent erfand Zeiten (14:00, 16:30, 18:00) aus Prompt-Beispiel
**V47:** Agent MUSS check_availability callen, nutzt nur echte Zeiten
**Impact:** Keine unmöglichen Termine mehr, Tool wird korrekt verwendet

### Problem 3: Tool wird nicht gecallt
**V46:** Agent sagte "Ich prüfe..." aber callte kein Tool
**V47:** Explizite Anweisung "DU MUSST check_availability CALLEN"
**Impact:** Tools werden korrekt verwendet, echte Verfügbarkeit

---

## 📄 Documentation

**Root Cause Analysis:**
`/var/www/api-gateway/TESTCALL_V46_ROOT_CAUSE_ANALYSIS_2025-11-05.md`

**Test Call V46:**
Call ID: `call_4123069ebb02d1b83a088103583`

**Scripts verwendet:**
- `/var/www/api-gateway/scripts/fix_v47_prompt_issues.php`
- `/var/www/api-gateway/scripts/fix_v47_dialog_example.php`

---

**Created:** 2025-11-05 19:58 Uhr
**Agent Version:** V47 (Draft)
**Status:** ✅ Ready for Publishing & Testing
