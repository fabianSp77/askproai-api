# Retell Agent Fix - Schritt-für-Schritt Anleitung
**Datum:** 2025-11-05 07:30
**Problem:** Conversation Flow Loop Bug + Call Context Error

---

## 🔴 KRITISCHE ÄNDERUNG: Loop Bug beheben

### Was ist das Problem?

**Aktuell:**
```
"Alternative bestätigen" → "Verfügbarkeit prüfen" (LOOP!)
```

**Richtig:**
```
"Alternative bestätigen" → "Termin buchen" ✅
```

---

## ⚡ SCHNELLSTE LÖSUNG: Manuelle Änderung im Dashboard (2 Minuten)

### Schritt 1: Retell Dashboard öffnen
1. Gehe zu https://app.retellai.com/
2. Login
3. Wähle Agent: **"Friseur1 Fixed V2 (parameter_mapping)"**

### Schritt 2: Conversation Flow Editor öffnen
1. Klicke auf Tab **"Conversation Flow"**
2. Klicke **"Edit Flow"**

### Schritt 3: Node "Alternative bestätigen" finden
1. Suche Node mit Namen: **"Alternative bestätigen"**
2. Oder suche nach ID: `node_confirm_alternative`
3. Klicke auf den Node

### Schritt 4: Edge/Transition ändern
1. Siehst du die **Verbindungslinie (Edge)** vom Node?
2. **Aktuell zeigt sie zu:** "Verfügbarkeit prüfen" ❌
3. **Lösche diese Edge:**
   - Klicke auf Edge/Line
   - Drücke Delete oder finde "Delete Edge" Button
4. **Erstelle neue Edge:**
   - Klicke auf Node "Alternative bestätigen"
   - Ziehe neue Verbindung zu Node **"Termin buchen"** ✅
   - Transition Condition: "Alternative confirmed"

### Schritt 5: Instruction Text anpassen (Optional aber empfohlen)
1. Node "Alternative bestätigen" auswählen
2. Instruction ändern von:
   ```
   ❌ ALT: "Perfekt! Einen Moment, ich prüfe die Verfügbarkeit für {{selected_alternative_time}} Uhr..."
   ```
   zu:
   ```
   ✅ NEU: "Perfekt! Ich buche den Termin für {{selected_alternative_time}} Uhr..."
   ```

### Schritt 6: Timeout erhöhen (Empfohlen)
1. Gehe zu **Tools** Section
2. Für ALLE Tools (check_availability_v17, book_appointment_v17, etc.):
   - Ändere Timeout von **10000ms** → **15000ms**
3. Das gibt Backend mehr Zeit zu antworten

### Schritt 7: Speichern & Publish
1. Klicke **"Save"**
2. Klicke **"Publish"**
3. Wähle Version: **V32**
4. Fertig! ✅

---

## 🔄 ALTERNATIVE: JSON Import (Falls verfügbar)

Falls Retell Dashboard ein "Import JSON" Feature hat:

1. **Lade korrigierte Datei:**
   ```bash
   /var/www/api-gateway/retell_agent_fixed_2025-11-05.json
   ```

2. **Öffne Retell Dashboard**
   - Agent "Friseur1 Fixed V2" auswählen
   - Suche nach "Import" oder "Upload JSON" Button

3. **Import durchführen**
   - Wähle die Datei aus
   - Bestätige Import
   - Publish als neue Version

---

## ✅ VERIFIKATION: Teste den Fix

### Test Case 1: Alternative Auswahl
```
1. Starte Test Call in Retell Dashboard
2. Sage: "Ich möchte einen Herrenhaarschnitt für morgen 10 Uhr"
3. Gebe Name an: "Hans Schuster"
4. Warte auf Alternativen (z.B. 09:00, 11:00, 12:30)
5. Sage: "Ich nehme 11:00 Uhr"
6. ✅ ERWARTUNG: Agent bucht DIREKT den Termin!
7. ❌ VORHER: Agent fragt nochmal nach Alternativen → Loop → Abbruch
```

**Erfolg wenn:**
- ✅ Agent sagt: "Perfekt! Ich buche den Termin..."
- ✅ Tool Invocation: book_appointment_v17 mit uhrzeit="11:00"
- ✅ KEIN zweiter check_availability_v17 Call
- ✅ KEIN Loop-Fehler

---

## 🔍 WAS WURDE GEÄNDERT?

### Änderung 1: Loop Bug Fix
**Datei:** Node "Alternative bestätigen" (node_confirm_alternative)

**ALT (V31):**
```json
{
  "edges": [
    {
      "destination_node_id": "func_check_availability",  // ❌ FALSCH
      "id": "edge_confirm_to_check"
    }
  ]
}
```

**NEU (V32):**
```json
{
  "edges": [
    {
      "destination_node_id": "func_book_appointment",  // ✅ RICHTIG
      "id": "edge_confirm_to_book"
    }
  ]
}
```

### Änderung 2: Timeout Erhöhung
**Alle Tools:**
- ❌ ALT: "timeout_ms": 10000 (10 Sekunden)
- ✅ NEU: "timeout_ms": 15000 (15 Sekunden)

**Grund:** Backend benötigt manchmal >10s für Cal.com API Calls

### Änderung 3: Instruction Update
**Node "Alternative bestätigen":**
- ❌ ALT: "...ich prüfe die Verfügbarkeit..."
- ✅ NEU: "...ich buche den Termin..."

**Grund:** Klarheit - Agent prüft nicht nochmal, sondern bucht direkt

---

## 📊 ERWARTETE VERBESSERUNGEN

### Vor dem Fix:
```
User: "11:00 Uhr"
  ↓
Agent: "Einen Moment, ich prüfe..."  ← check_availability NOCHMAL
  ↓
Agent: "Leider nicht verfügbar, Alternativen: ..."  ← LOOP!
  ↓
🚨 Retell: "Loop detected" → Call abgebrochen
```

### Nach dem Fix:
```
User: "11:00 Uhr"
  ↓
Agent: "Perfekt! Ich buche den Termin..."  ← book_appointment DIREKT
  ↓
Agent: "Ihr Termin ist gebucht!"  ← ERFOLG!
  ↓
✅ Call erfolgreich beendet
```

---

## ⚠️ BEKANNTES PROBLEM: "Call context not available"

**Symptom:** Im Test Mode erscheint Error "Call context not available"

**Grund:** Test Mode Calls werden nicht in unsere Datenbank synchronisiert

**Lösung:**
1. **Quick Fix:** Fallback Code implementieren (Option A)
2. **Proper Fix:** Webhook Debugging (siehe separate Anleitung)

**Status:** Nicht kritisch für Production, nur Test Mode betroffen

---

## 🎯 NÄCHSTE SCHRITTE

1. ✅ **SOFORT:** Loop Bug fixen (2 Minuten)
2. ✅ **SOFORT:** Timeout erhöhen (1 Minute)
3. ✅ **DANN:** Test durchführen (5 Minuten)
4. 🟡 **SPÄTER:** Call Context Fallback implementieren (30 Minuten)

---

## 📞 SUPPORT

**Bei Problemen:**
1. Prüfe Retell Dashboard Logs
2. Prüfe Backend Logs: `tail -f /var/www/api-gateway/storage/logs/laravel.log`
3. Lese Dokumentation: `/var/www/api-gateway/CONVERSATION_FLOW_LOOP_BUG_2025-11-05.md`

**Dateien:**
- ✅ Korrigierter Agent JSON: `retell_agent_fixed_2025-11-05.json`
- ✅ Detaillierte Analyse: `CONVERSATION_FLOW_LOOP_BUG_2025-11-05.md`
- ✅ Diese Anleitung: `RETELL_FIX_ANLEITUNG_2025-11-05.md`

---

**Status:** 🔴 CRITICAL BUG - FIX REQUIRED
**Dauer:** 2-3 Minuten
**Schwierigkeit:** Einfach (nur 1 Edge ändern)
**Impact:** 🎯 100% - Ohne Fix funktionieren KEINE Alternative-Buchungen!
