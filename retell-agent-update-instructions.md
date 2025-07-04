# Retell Agent Editor - Update Instructions

## Zugriff auf den Agent Editor
1. Öffne: https://api.askproai.de/admin/retell-agent-editor?agent_id=agent_9a8202a740cd3120d96fcfda1e
2. Wähle die neueste Version aus dem Dropdown-Menü

## 1. Prompt Update
Füge diese Zeilen zum bestehenden Prompt hinzu (am besten nach "WICHTIGE ANWEISUNGEN:" oder "REGELN:"):

```
- NIEMALS nach der Telefonnummer fragen - die Telefonnummer ist bereits über {{caller_phone_number}} verfügbar oder verwende call_id in den Funktionen
- Bei allen Funktionsaufrufen IMMER die call_id mit {{call_id}} übergeben
```

## 2. Custom Functions Updates

### Function: collect_appointment_data
**Description ändern zu:**
```
Sammelt alle Termindaten vom Anrufer für die Weiterverarbeitung. NIEMALS nach der Telefonnummer fragen - verwende immer call_id!
```

**Parameters ändern zu:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "datum": {
      "type": "string",
      "description": "Datum des Termins (z.B. 'heute', 'morgen', 'übermorgen', '25.03.2024')"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Gewünschte Uhrzeit im 24-Stunden-Format (z.B. '09:00', '14:30', '17:45')"
    },
    "name": {
      "type": "string",
      "description": "Vollständiger Name des Kunden"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Gewünschte Dienstleistung oder Behandlung"
    },
    "email": {
      "type": "string",
      "description": "E-Mail-Adresse für Terminbestätigung (optional)"
    },
    "mitarbeiter_wunsch": {
      "type": "string",
      "description": "Bevorzugter Mitarbeiter (optional)"
    },
    "kundenpraeferenzen": {
      "type": "string",
      "description": "Zusätzliche Wünsche oder Anmerkungen (optional)"
    }
  },
  "required": ["call_id", "datum", "uhrzeit", "name", "dienstleistung"]
}
```

### Function: check_customer
**Description ändern zu:**
```
Prüfe ob ein Kunde im System existiert. IMMER zu Beginn des Gesprächs aufrufen mit call_id!
```

**Parameters ändern zu:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    }
  },
  "required": ["call_id"]
}
```

### Function: cancel_appointment
**Parameters ändern zu:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "appointment_date": {
      "type": "string",
      "description": "Datum des zu stornierenden Termins"
    }
  },
  "required": ["call_id", "appointment_date"]
}
```

### Function: reschedule_appointment
**Parameters ändern zu:**
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER {{call_id}} verwenden"
    },
    "new_date": {
      "type": "string",
      "description": "Neues Datum für den Termin"
    },
    "new_time": {
      "type": "string",
      "description": "Neue Uhrzeit für den Termin"
    }
  },
  "required": ["call_id", "new_date", "new_time"]
}
```

## 3. Speichern und Aktivieren
1. Klicke auf "Save Changes" oder "Update Agent"
2. Teste mit einem Anruf ob die Änderungen funktionieren

## Wichtige Hinweise:
- Die `telefonnummer` und `phone_number` Parameter wurden entfernt
- Stattdessen wird `call_id` verwendet mit dem Wert `{{call_id}}`
- Das System löst die Telefonnummer automatisch über die call_id auf
- Der Agent sollte nie mehr nach der Telefonnummer fragen