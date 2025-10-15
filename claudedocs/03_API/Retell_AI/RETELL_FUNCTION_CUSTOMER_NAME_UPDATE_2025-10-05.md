# 🔧 RETELL FUNCTION UPDATE: customer_name Parameter

**Erstellt**: 2025-10-05 18:10 CEST
**Status**: 🔴 KRITISCH - SOFORT UMSETZEN
**Betrifft**: `cancel_appointment` und `reschedule_appointment` Funktionen

---

## 🚨 PROBLEM

Die Retell AI Funktionen `cancel_appointment` und `reschedule_appointment` senden **KEINEN** `customer_name` Parameter. Dies führt dazu, dass **anonyme Anrufer** (unterdrückte Nummer) ihre Termine nicht stornieren oder verschieben können.

### Beweis aus Call 666 (2025-10-05 17:45):
```json
{
  "call_id": "call_155d1ab2a720abfe2adc841861d",
  "appointment_date": "2025-10-06",
  "customer_name": null  // ❌ FEHLT!
}
```

**Ergebnis**: "Kein Termin gefunden" obwohl Termin existiert!

---

## 💡 LÖSUNG

Füge `customer_name` Parameter zu beiden Funktionen im **Retell Dashboard** hinzu.

---

## 📋 SCHRITT-FÜR-SCHRITT ANLEITUNG

### Schritt 1: Retell Dashboard öffnen

1. Gehe zu: https://app.retellai.com
2. Login mit deinen Credentials
3. Navigiere zu: **Agents** → Dein Agent (z.B. "Online: Assistent für Fabian Spitzer Rechtliches")

### Schritt 2: cancel_appointment aktualisieren

**AKTUELL** (ohne customer_name):
```json
{
  "name": "cancel_appointment",
  "description": "Storniert einen bestehenden Termin",
  "parameters": {
    "type": "object",
    "properties": {
      "appointment_date": {
        "type": "string",
        "description": "Datum des zu stornierenden Termins (z.B. '2025-10-06' oder '6. Oktober')"
      },
      "reason": {
        "type": "string",
        "description": "Grund für die Stornierung (optional)"
      },
      "call_id": {
        "type": "string",
        "description": "Die Retell Call ID"
      }
    },
    "required": ["appointment_date", "call_id"]
  }
}
```

**NEU** (mit customer_name) ✅:
```json
{
  "name": "cancel_appointment",
  "description": "Storniert einen bestehenden Termin",
  "parameters": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Name des Kunden dessen Termin storniert werden soll (z.B. 'Hans Schuster'). WICHTIG: Bei anonymen Anrufern ist dies der einzige Weg den Termin zu finden!"
      },
      "appointment_date": {
        "type": "string",
        "description": "Datum des zu stornierenden Termins (z.B. '2025-10-06' oder '6. Oktober')"
      },
      "reason": {
        "type": "string",
        "description": "Grund für die Stornierung (optional)"
      },
      "call_id": {
        "type": "string",
        "description": "Die Retell Call ID"
      }
    },
    "required": ["appointment_date", "call_id"]
  }
}
```

### Schritt 3: reschedule_appointment aktualisieren

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
      },
      "call_id": {
        "type": "string",
        "description": "Die Retell Call ID"
      }
    },
    "required": ["old_date", "new_date", "new_time", "call_id"]
  }
}
```

**NEU** (mit customer_name) ✅:
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
      },
      "call_id": {
        "type": "string",
        "description": "Die Retell Call ID"
      }
    },
    "required": ["old_date", "new_date", "new_time", "call_id"]
  }
}
```

---

## 🎯 WARUM IST DAS WICHTIG?

### Backend Search Strategies (bereits implementiert):

Das Backend hat **5 Such-Strategien** um Termine zu finden:

1. **Strategy 1**: Via `call_id` (gleicher Anruf)
2. **Strategy 2**: Via `customer_id` (bekannter Kunde)
3. **Strategy 3**: Via Telefonnummer (identifizierter Anrufer)
4. **Strategy 4**: Via `customer_name` ← **BRAUCHT DEN PARAMETER!** ⚠️
5. **Strategy 5**: Via `company_id` + Datum (Fallback)

### Ohne customer_name Parameter:
- ❌ Strategy 4 funktioniert NIE (Parameter fehlt)
- ⚠️ Strategy 5 wird verwendet (weniger präzise, kann falsche Termine finden bei mehreren Terminen am gleichen Tag)
- ❌ Anonyme Anrufer können Termine nicht zuverlässig stornieren/verschieben

### Mit customer_name Parameter:
- ✅ Strategy 4 funktioniert (Name-basierte Suche)
- ✅ Strategy 5 als sicherer Fallback
- ✅ Anonyme Anrufer können Termine zuverlässig stornieren/verschieben
- ✅ Höhere Genauigkeit und weniger Fehler

---

## 📊 VERGLEICH: VORHER vs. NACHHER

### VORHER (ohne customer_name):
```
User: "Ja, guten Tag. Mein Name ist Hans Schuster. Ich würde gern meinen Termin am 6. Oktober stornieren."

Retell AI sendet:
{
  "call_id": "call_xxx",
  "appointment_date": "2025-10-06",
  "customer_name": null  // ❌ FEHLT
}

Backend:
- Strategy 1: call_id ❌ (anderer Anruf)
- Strategy 2: customer_id ❌ (NULL bei anonym)
- Strategy 3: phone ❌ (anonymous)
- Strategy 4: customer_name ❌ (NULL - ÜBERSPRUNGEN!)
- Strategy 5: company_id + date ⚠️ (findet Termin, aber weniger präzise)

Result: Termin gefunden via Strategy 5 (aber unsicher bei mehreren Terminen)
```

### NACHHER (mit customer_name):
```
User: "Ja, guten Tag. Mein Name ist Hans Schuster. Ich würde gern meinen Termin am 6. Oktober stornieren."

Retell AI sendet:
{
  "call_id": "call_xxx",
  "appointment_date": "2025-10-06",
  "customer_name": "Hans Schuster"  // ✅ JETZT DA!
}

Backend:
- Strategy 1: call_id ❌ (anderer Anruf)
- Strategy 2: customer_id ❌ (NULL bei anonym)
- Strategy 3: phone ❌ (anonymous)
- Strategy 4: customer_name ✅ SUCCESS! → Findet Kunde 338 → Findet Termin #638
- Strategy 5: Nicht benötigt

Result: ✅ Termin präzise gefunden via Strategy 4!
```

---

## ⚡ WIE RETELL AI DEN NAMEN EXTRAHIERT

Retell AI kann Namen aus dem Gespräch extrahieren:

**Beispiel-Transkript**:
```
User: "Ja, guten Tag. Mein Name ist Hans Schuster. Ich würde gern meinen Termin stornieren."
```

Wenn `customer_name` als Parameter definiert ist:
1. ✅ Retell AI erkennt: "Mein Name ist Hans Schuster"
2. ✅ Extrahiert: "Hans Schuster"
3. ✅ Sendet: `{"customer_name": "Hans Schuster", ...}`

Wenn `customer_name` NICHT definiert ist:
1. ❌ Retell AI ignoriert den Namen komplett
2. ❌ Sendet: `{"customer_name": null, ...}`

---

## 🧪 TESTING

### Test-Szenario für cancel_appointment:
```
1. Anonymer Anruf (unterdrückte Nummer)
2. User sagt: "Guten Tag, mein Name ist Hans Schuster. Ich möchte meinen Termin am 6. Oktober stornieren."
3. Expected Result:
   - Retell sendet: {"customer_name": "Hans Schuster", "appointment_date": "2025-10-06"}
   - Backend findet Termin via Strategy 4 (name-based search)
   - Termin wird storniert ✅
```

### Test-Szenario für reschedule_appointment:
```
1. Anonymer Anruf
2. User sagt: "Hallo, Hans Schuster hier. Ich möchte meinen Termin vom 6. Oktober auf den 8. Oktober um 15 Uhr verschieben."
3. Expected Result:
   - Retell sendet: {"customer_name": "Hans Schuster", "old_date": "2025-10-06", "new_date": "2025-10-08", "new_time": "15:00"}
   - Backend findet Termin via Strategy 4
   - Termin wird verschoben ✅
```

---

## 📝 ZUSAMMENFASSUNG

### Was muss getan werden:

1. ✅ **Backend Code**: Bereits vollständig implementiert (Bugs #7a-7e alle gefixt)
2. ⏳ **Retell Dashboard**: `customer_name` Parameter zu beiden Funktionen hinzufügen
3. 🧪 **Testing**: Anonymen Testanruf durchführen

### Priorität:

🔴 **KRITISCH** - Ohne dieses Update funktioniert Termin-Stornierung/-Verschiebung für anonyme Anrufer nur via unsicheren Strategy 5 Fallback.

### Zeitaufwand:

- ⏱️ **5 Minuten** - Parameter im Retell Dashboard hinzufügen
- ⏱️ **2 Minuten** - Testanruf durchführen

---

## ✅ DEPLOYMENT CHECKLIST

- [x] Backend Code updated (Bug #7e fix)
- [x] PHP-FPM reloaded
- [x] Dokumentation erstellt
- [ ] **TODO**: Retell Dashboard - `cancel_appointment` aktualisieren
- [ ] **TODO**: Retell Dashboard - `reschedule_appointment` aktualisieren
- [ ] **TODO**: Testanruf mit anonymer Nummer
- [ ] **TODO**: Logs überprüfen auf "✅ Found customer via name search"

---

**Nächster Schritt**: Retell Dashboard öffnen und `customer_name` Parameter hinzufügen! 🚀
