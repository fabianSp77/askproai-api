# 🛠️ ALLE BENÖTIGTEN RETELL TOOLS - VOLLSTÄNDIGE LISTE

**Agent:** Conversation Flow Agent Friseur 1  
**Flow Version:** V35/V37  
**Datum:** 24.10.2025

---

## 📊 ÜBERSICHT

**Total Tool IDs im Flow:** 7  
**Davon funktionieren bereits:** 1 (initialize_call - ist hardcoded im PHP)  
**Müssen erstellt werden:** 6 Tools

**Priorität:**
- 🔴 P0 (CRITICAL): 2 Tools - Ohne diese funktioniert NICHTS
- 🟠 P1 (HIGH): 2 Tools - Wichtig für vollständige Funktionalität
- 🟡 P2 (MEDIUM): 2 Tools - Nice-to-have Features

---

## 🔴 P0 - CRITICAL TOOLS (SOFORT ERSTELLEN!)

### 1. check_availability_v17

**Retell Tool Name:** `check_availability_v17`  
**PHP Function Name:** `check_availability` (Version wird auto-entfernt)  
**Flow Tool ID:** `tool-v17-check-availability`

**Warum critical:** BLOCKIERT ALLE BUCHUNGEN!  
Der Agent kann KEINE Verfügbarkeit prüfen ohne dieses Tool.

**Was passiert ohne:** 17-Sekunden Pausen, Agent sagt immer wieder "Einen Moment bitte..."

**Configuration:**
```json
{
  "name": "check_availability_v17",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "description": "Prüft die Verfügbarkeit für einen bestimmten Termin",
  "parameters": {
    "type": "object",
    "properties": {
      "datum": {
        "type": "string",
        "description": "Datum im Format TT.MM.JJJJ (z.B. 24.10.2025)"
      },
      "uhrzeit": {
        "type": "string",
        "description": "Uhrzeit im Format HH:MM (z.B. 11:00)"
      },
      "dienstleistung": {
        "type": "string",
        "description": "Name der Dienstleistung (z.B. Herrenhaarschnitt)"
      }
    },
    "required": ["datum", "uhrzeit", "dienstleistung"]
  }
}
```

---

### 2. book_appointment_v17

**Retell Tool Name:** `book_appointment_v17`  
**PHP Function Name:** `book_appointment`  
**Flow Tool ID:** `tool-v17-book-appointment`

**Warum critical:** Ohne dieses Tool kann der Agent KEINE Termine buchen!

**Configuration:**
```json
{
  "name": "book_appointment_v17",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "description": "Bucht einen bestätigten Termin für den Kunden",
  "parameters": {
    "type": "object",
    "properties": {
      "name": {
        "type": "string",
        "description": "Vollständiger Name des Kunden"
      },
      "datum": {
        "type": "string",
        "description": "Datum im Format TT.MM.JJJJ"
      },
      "uhrzeit": {
        "type": "string",
        "description": "Uhrzeit im Format HH:MM"
      },
      "dienstleistung": {
        "type": "string",
        "description": "Name der Dienstleistung"
      },
      "telefonnummer": {
        "type": "string",
        "description": "Telefonnummer des Kunden"
      },
      "bestaetigung": {
        "type": "boolean",
        "description": "Ob der Kunde die Buchung bestätigt hat"
      }
    },
    "required": ["name", "datum", "uhrzeit", "dienstleistung"]
  }
}
```

---

## 🟠 P1 - HIGH PRIORITY TOOLS

### 3. get_alternatives

**Retell Tool Name:** `get_alternatives`  
**PHP Function Name:** `get_alternatives` (handled by find_next_available)  
**Flow Tool ID:** Nicht direkt im Flow, wird von check_availability aufgerufen

**Warum important:** Bietet alternative Termine an wenn Wunschtermin nicht verfügbar

**Configuration:**
```json
{
  "name": "get_alternatives",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": false,
  "description": "Findet alternative Termine wenn der Wunschtermin nicht verfügbar ist",
  "parameters": {
    "type": "object",
    "properties": {
      "datum": {
        "type": "string",
        "description": "Gewünschtes Datum im Format TT.MM.JJJJ"
      },
      "dienstleistung": {
        "type": "string",
        "description": "Name der Dienstleistung"
      }
    },
    "required": ["datum", "dienstleistung"]
  }
}
```

---

### 4. get_appointments

**Retell Tool Name:** `get_appointments`  
**PHP Function Name:** `query_appointment`  
**Flow Tool ID:** `tool-get-appointments`

**Warum important:** Kunden können ihre Termine abfragen

**Configuration:**
```json
{
  "name": "get_appointments",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": false,
  "description": "Ruft die Termine eines Kunden ab",
  "parameters": {
    "type": "object",
    "properties": {
      "telefonnummer": {
        "type": "string",
        "description": "Telefonnummer des Kunden"
      }
    }
  }
}
```

---

## 🟡 P2 - MEDIUM PRIORITY TOOLS

### 5. reschedule_appointment

**Retell Tool Name:** `reschedule_appointment`  
**PHP Function Name:** `reschedule_appointment` (mit handleRescheduleAttempt)  
**Flow Tool ID:** `tool-reschedule-appointment`

**Warum medium:** Kunden können Termine verschieben (nice-to-have)

**Configuration:**
```json
{
  "name": "reschedule_appointment",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "description": "Verschiebt einen existierenden Termin auf ein neues Datum/Uhrzeit",
  "parameters": {
    "type": "object",
    "properties": {
      "appointment_id": {
        "type": "string",
        "description": "ID des zu verschiebenden Termins"
      },
      "datum": {
        "type": "string",
        "description": "Altes Datum im Format TT.MM.JJJJ"
      },
      "neue_datum": {
        "type": "string",
        "description": "Neues Datum im Format TT.MM.JJJJ"
      },
      "neue_uhrzeit": {
        "type": "string",
        "description": "Neue Uhrzeit im Format HH:MM"
      },
      "bestaetigung": {
        "type": "boolean",
        "description": "Ob der Kunde die Verschiebung bestätigt hat"
      }
    },
    "required": ["neue_datum", "neue_uhrzeit"]
  }
}
```

---

### 6. cancel_appointment

**Retell Tool Name:** `cancel_appointment`  
**PHP Function Name:** `cancel_appointment` (mit handleCancellationAttempt)  
**Flow Tool ID:** `tool-cancel-appointment`

**Warum medium:** Kunden können Termine stornieren (nice-to-have)

**Configuration:**
```json
{
  "name": "cancel_appointment",
  "type": "Custom Function",
  "url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "description": "Storniert einen existierenden Termin",
  "parameters": {
    "type": "object",
    "properties": {
      "appointment_id": {
        "type": "string",
        "description": "ID des zu stornierenden Termins"
      },
      "datum": {
        "type": "string",
        "description": "Datum des Termins im Format TT.MM.JJJJ"
      },
      "grund": {
        "type": "string",
        "description": "Grund für die Stornierung (optional)"
      }
    }
  }
}
```

---

## ✅ BEREITS FUNKTIONIEREND

### initialize_call (KEIN TOOL NÖTIG!)

**PHP Function Name:** `initialize_call`  
**Flow Tool ID:** `tool-initialize-call`

**Status:** ✅ FUNKTIONIERT BEREITS!

**Warum:** Wird NICHT über Retell Tools aufgerufen, sondern ist direkt im `RetellFunctionCallHandler.php` hardcoded.

**Evidence vom Testcall:**
```json
{
  "role": "tool_call_invocation",
  "name": "initialize_call",
  "time_sec": 0.528,
  "successful": true,
  "latency_ms": 17.36
}
```

**Kein Tool erstellen für initialize_call nötig!**

---

## 🎯 EMPFOHLENE ERSTELLUNGS-REIHENFOLGE

### Phase 1 (SOFORT - 5 Minuten):
1. ✅ check_availability_v17
2. ✅ book_appointment_v17

→ **Nach Phase 1 kann der Agent Termine prüfen und buchen!**

### Phase 2 (Wichtig - 5 Minuten):
3. ✅ get_alternatives
4. ✅ get_appointments

→ **Nach Phase 2 kann der Agent Alternativen anbieten und Termine abfragen!**

### Phase 3 (Optional - 10 Minuten):
5. ✅ reschedule_appointment
6. ✅ cancel_appointment

→ **Nach Phase 3 volle Funktionalität: Verschieben & Stornieren!**

---

## 🔄 TOOL NAME MAPPING

**WICHTIG:** Retell Dashboard Tool Name ≠ PHP Function Name!

| Retell Dashboard | PHP Handler | Grund |
|-----------------|-------------|-------|
| check_availability_v17 | check_availability | _v17 wird auto-entfernt |
| book_appointment_v17 | book_appointment | _v17 wird auto-entfernt |
| get_alternatives | find_next_available | Alias |
| get_appointments | query_appointment | Alias |
| reschedule_appointment | handleRescheduleAttempt | Policy Check |
| cancel_appointment | handleCancellationAttempt | Policy Check |

**Code Reference:**
```php
// RetellFunctionCallHandler.php:190
$baseFunctionName = preg_replace('/_v\d+$/', '', $functionName);
// Entfernt _v17, _v18, etc. automatisch!
```

---

## 📝 CHECKLISTE

**Für jedes Tool:**
- [ ] Name EXAKT kopiert (mit Unterstrichen `_`, nicht `-`)
- [ ] Type: Custom Function
- [ ] URL: https://api.askproai.de/api/webhooks/retell/function
- [ ] Description ausgefüllt
- [ ] Parameters KOMPLETT kopiert (JSON)
- [ ] Speak During Execution richtig gesetzt (Ja bei check/book, Nein bei get)
- [ ] Saved/Created
- [ ] Dem Agent zugewiesen

---

## ⚠️ HÄUFIGE FEHLER

❌ **FALSCH:**
- Tool Name: `check-availability-v17` (Bindestriche)
- URL: `https://api.askproai.de/webhooks/retell` (falsche URL)
- Parameters: Nur teilweise kopiert

✅ **RICHTIG:**
- Tool Name: `check_availability_v17` (Unterstriche)
- URL: `https://api.askproai.de/api/webhooks/retell/function` (komplette URL)
- Parameters: KOMPLETTEN JSON Block kopieren

---

**Erstellt:** 2025-10-24 07:15  
**Quelle:** Flow Analysis + RetellFunctionCallHandler.php  
**Status:** Production Ready
