# 🔧 RETELL AGENT UPDATE GUIDE - 2025-10-04

**Problem**: Retell Agent sendet KEINEN `customer_name` Parameter beim reschedule_appointment Call

**Impact**: Termin-Suche für anonyme Anrufer schlägt fehl, weil Strategy 4 (name-based search) den Parameter benötigt

---

## 📋 BENÖTIGTE ÄNDERUNG

Du musst im **Retell Dashboard** die Function Definition für `reschedule_appointment` aktualisieren.

---

## 🎯 SCHRITT-FÜR-SCHRITT ANLEITUNG

### Schritt 1: Retell Dashboard öffnen

1. Gehe zu: https://app.retellai.com (oder deine Retell Dashboard URL)
2. Login mit deinen Credentials
3. Navigiere zu: **Agents** → Dein Agent (z.B. "AskProAI Agent")

### Schritt 2: Function Definition finden

1. Im Agent Editor, gehe zu: **Functions** oder **Custom Functions**
2. Suche die Function: `reschedule_appointment`
3. Klicke auf **Edit** oder **Configure**

### Schritt 3: Parameter hinzufügen

**AKTUELL** (ohne customer_name):
```json
{
  "name": "reschedule_appointment",
  "description": "Verschiebt einen bestehenden Termin auf ein neues Datum/Uhrzeit",
  "parameters": {
    "type": "object",
    "properties": {
      "old_date": {
        "type": "string",
        "description": "Aktuelles Datum des Termins (z.B. '7. Oktober' oder 'siebter Oktober')"
      },
      "new_date": {
        "type": "string",
        "description": "Neues Datum für den Termin"
      },
      "new_time": {
        "type": "string",
        "description": "Neue Uhrzeit für den Termin (z.B. '16:30')"
      }
    },
    "required": ["old_date", "new_date", "new_time"]
  }
}
```

**NEU** (mit customer_name Parameter):
```json
{
  "name": "reschedule_appointment",
  "description": "Verschiebt einen bestehenden Termin auf ein neues Datum/Uhrzeit",
  "parameters": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Name des Kunden dessen Termin verschoben werden soll (z.B. 'Hans Schuster'). WICHTIG: Bei anonymen Anrufern ist dies der einzige Weg den Termin zu finden!"
      },
      "old_date": {
        "type": "string",
        "description": "Aktuelles Datum des Termins (z.B. '7. Oktober' oder 'siebter Oktober')"
      },
      "new_date": {
        "type": "string",
        "description": "Neues Datum für den Termin"
      },
      "new_time": {
        "type": "string",
        "description": "Neue Uhrzeit für den Termin (z.B. '16:30')"
      }
    },
    "required": ["old_date", "new_date", "new_time"]
  }
}
```

**HINZUGEFÜGT**: `customer_name` Parameter mit klarer Beschreibung

### Schritt 4: Agent Prompt aktualisieren (OPTIONAL aber EMPFOHLEN)

Füge dem Agent Prompt folgende Instruktion hinzu:

```
WICHTIG für Terminverschiebungen:
- Frage IMMER nach dem vollständigen Namen des Kunden
- Format: Vorname + Nachname (z.B. "Hans Schuster")
- Bei anonymen Anrufern ist der Name essentiell zur Termin-Identifikation

Beispiel Dialog:
User: "Ich möchte meinen Termin verschieben"
Agent: "Gerne! Könnten Sie mir bitte Ihren vollständigen Namen nennen?"
User: "Hans Schuster"
Agent: "Und an welchem Tag ist Ihr aktueller Termin?"
```

### Schritt 5: Speichern & Deployen

1. Klicke auf **Save** oder **Update**
2. Wenn erforderlich: **Deploy** oder **Activate** den Agent
3. Warte ca. 30 Sekunden bis Änderungen live sind

---

## ✅ VERIFICATION CHECKLIST

Nach dem Update:

- [ ] Function Definition enthält `customer_name` Parameter
- [ ] Parameter Beschreibung erklärt Wichtigkeit
- [ ] Agent Prompt wurde aktualisiert (optional)
- [ ] Änderungen gespeichert und deployed
- [ ] Test-Anruf mit unterdrückter Nummer funktioniert

---

## 🧪 TEST SZENARIO

**Nach dem Update testen:**

1. **Testanruf mit unterdrückter Nummer** (*67 oder Caller ID Block)
2. **Ruf an**: +493083793369 (oder deine Nummer)
3. **Sage**: "Mein Name ist Hans Schuster. Ich möchte meinen Termin am 5. Oktober verschieben auf 16 Uhr."

**Erwartetes Verhalten:**
```
Agent: "Gerne! Könnten Sie mir bitte Ihren vollständigen Namen nennen?"
User: "Hans Schuster"
Agent: "Und an welchem Tag ist Ihr aktueller Termin?"
User: "Am 5. Oktober um 14 Uhr"
Agent: "Auf welches Datum möchten Sie verschieben?"
User: "Gleicher Tag, aber 16 Uhr"
Agent: "✅ Ich habe Ihren Termin erfolgreich verschoben..."
```

**In den Logs solltest du sehen:**
```
[INFO] 📞 Anonymous caller detected - searching by name
[INFO] 🔍 Searching appointment by customer name (anonymous caller)
[INFO] ✅ Found appointment via customer name
```

---

## 🔍 DEBUGGING

**Falls es immer noch nicht funktioniert:**

1. **Check Retell Agent Logs**:
   - Gehe zu Retell Dashboard → Agent → Logs
   - Suche den letzten Test Call
   - Prüfe: Wird `customer_name` im Function Call übergeben?

2. **Check Laravel Logs**:
   ```bash
   tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(customer_name|reschedule|findAppointment)"
   ```

3. **Verify Function Call Payload**:
   - In Laravel Logs solltest du sehen:
   ```json
   "data": {
     "customer_name": "Hans Schuster",
     "old_date": "2025-10-05",
     "new_date": "2025-10-05",
     "new_time": "16:00"
   }
   ```

---

## 📊 WARUM IST DAS NÖTIG?

### Call 566 Analysis (fehlgeschlagener Test)

**User sagte**: "Mein Name ist Hans Schuster"

**Retell Agent verstand**: ✅ Name wurde erkannt

**Aber Function Call hatte**:
```json
{
  "arguments": {
    "old_date": "2025-10-05",
    "new_date": "2025-10-05",
    "new_time": "16:00"
  }
}
```
❌ **KEIN** `customer_name` Parameter!

**Backend konnte nicht suchen weil**:
- `from_number = "anonymous"` → Phone-Search unmöglich
- `customer_id = NULL` → Customer-Search unmöglich
- `customer_name = MISSING` → Name-Search unmöglich ❌

**Mit dem Update**:
```json
{
  "arguments": {
    "customer_name": "Hans Schuster", ✅
    "old_date": "2025-10-05",
    "new_date": "2025-10-05",
    "new_time": "16:00"
  }
}
```

**Backend kann jetzt suchen**:
- Strategy 4: Name-based search ✅
- Findet Customer "Hans Schuster" via fuzzy match
- Findet Appointment via customer_id + date
- Reschedule erfolgreich! ✅

---

## 🎯 ZUSAMMENFASSUNG

1. **Retell Agent** muss `customer_name` Parameter beim `reschedule_appointment` Call übergeben
2. **Backend Code** ist bereits vorbereitet für name-based search (Strategy 4)
3. **Nach Update**: Reschedule funktioniert für alle Anrufer, auch mit unterdrückter Nummer

---

**Erstellt**: 2025-10-04 19:15
**Status**: ⏳ WARTE AUF RETELL AGENT UPDATE
**Next**: User updated Retell Agent → Test Call → Success ✅
