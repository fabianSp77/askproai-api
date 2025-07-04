# Retell Agent Custom Functions Update - Die richtige Lösung

## Problem
- Retell.ai löst `{{caller_phone_number}}` nicht auf
- Custom Functions erhalten nur "+49" oder den Variablennamen als Text

## Lösung
Die Custom Functions so anpassen, dass sie die `call_id` verwenden, um die Telefonnummer aus unserer Datenbank zu holen.

## Wichtig: Bestehenden Prompt BEHALTEN!
Der umfangreiche bestehende Prompt mit allen Beispielen und Anweisungen muss erhalten bleiben. Wir fügen nur folgende Zeile am Anfang der "WICHTIGE ANWEISUNGEN" hinzu:

```
- NIEMALS nach der Telefonnummer fragen - verwende immer {{caller_phone_number}} oder die call_id
```

## Neue Custom Functions für general_tools

```json
[
  {
    "name": "end_call",
    "description": "Das Gespräch höflich beenden",
    "url": "https://api.askproai.de/api/retell/end-call",
    "speak_during_execution": true,
    "speak_after_execution": false,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "reason": {
            "type": "string",
            "description": "Grund für Beendigung"
          }
        }
      }
    }
  },
  {
    "name": "transfer_call",
    "description": "Anruf an Mitarbeiter weiterleiten",
    "url": "https://api.askproai.de/api/retell/transfer-call",
    "speak_during_execution": true,
    "speak_after_execution": false,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "number": {
            "type": "string",
            "description": "Telefonnummer für Weiterleitung"
          }
        },
        "required": ["number"]
      }
    }
  },
  {
    "name": "current_time_berlin",
    "description": "Aktuelle Zeit in Berlin abrufen",
    "url": "https://api.askproai.de/api/retell/current-time-berlin",
    "speak_during_execution": false,
    "speak_after_execution": false,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {}
      }
    }
  },
  {
    "name": "check_customer",
    "description": "Prüfe ob Kunde existiert. IMMER am Gesprächsanfang aufrufen.",
    "url": "https://api.askproai.de/api/retell/check-customer",
    "speak_during_execution": false,
    "speak_after_execution": false,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "call_id": {
            "type": "string",
            "description": "Call ID (immer {{call_id}} verwenden)"
          }
        },
        "required": ["call_id"]
      }
    }
  },
  {
    "name": "check_availability",
    "description": "Verfügbare Termine für ein Datum prüfen",
    "url": "https://api.askproai.de/api/retell/check-availability",
    "speak_during_execution": true,
    "speak_after_execution": true,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "date": {
            "type": "string",
            "description": "Datum (z.B. 'heute', 'morgen', '25.06.2025')"
          },
          "time": {
            "type": "string",
            "description": "Gewünschte Uhrzeit (optional, z.B. '09:00', '14:30')"
          }
        },
        "required": ["date"]
      }
    }
  },
  {
    "name": "collect_appointment_data",
    "description": "Termin buchen mit allen gesammelten Daten. NIEMALS nach Telefonnummer fragen!",
    "url": "https://api.askproai.de/api/retell/collect-appointment",
    "speak_during_execution": true,
    "speak_after_execution": true,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "call_id": {
            "type": "string",
            "description": "Call ID (immer {{call_id}} verwenden)"
          },
          "name": {
            "type": "string",
            "description": "Name des Kunden"
          },
          "datum": {
            "type": "string",
            "description": "Datum (z.B. 'heute', 'morgen', '25.06.2025')"
          },
          "uhrzeit": {
            "type": "string",
            "description": "Uhrzeit im 24h Format (z.B. '14:00', '09:30')"
          },
          "dienstleistung": {
            "type": "string",
            "description": "Gewünschte Dienstleistung"
          },
          "email": {
            "type": "string",
            "description": "E-Mail für Bestätigung (optional)"
          }
        },
        "required": ["call_id", "name", "datum", "uhrzeit"]
      }
    }
  },
  {
    "name": "cancel_appointment",
    "description": "Termin stornieren",
    "url": "https://api.askproai.de/api/retell/cancel-appointment",
    "speak_during_execution": true,
    "speak_after_execution": true,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "call_id": {
            "type": "string",
            "description": "Call ID (immer {{call_id}} verwenden)"
          },
          "appointment_date": {
            "type": "string",
            "description": "Datum des zu stornierenden Termins"
          }
        },
        "required": ["call_id", "appointment_date"]
      }
    }
  },
  {
    "name": "reschedule_appointment",
    "description": "Termin verschieben",
    "url": "https://api.askproai.de/api/retell/reschedule-appointment",
    "speak_during_execution": true,
    "speak_after_execution": true,
    "properties": {
      "parameters": {
        "type": "object",
        "properties": {
          "call_id": {
            "type": "string",
            "description": "Call ID (immer {{call_id}} verwenden)"
          },
          "old_date": {
            "type": "string",
            "description": "Aktuelles Datum des Termins"
          },
          "new_date": {
            "type": "string",
            "description": "Neues gewünschtes Datum"
          },
          "new_time": {
            "type": "string",
            "description": "Neue gewünschte Uhrzeit"
          }
        },
        "required": ["call_id", "old_date", "new_date", "new_time"]
      }
    }
  }
]
```

## Anleitung für den Retell Agent Editor

1. Öffnen Sie: https://api.askproai.de/admin/retell-agent-editor?agent_id=agent_9a8202a740cd3120d96fcfda1e
2. Scrollen Sie zu "Edit Functions (JSON)"
3. Ersetzen Sie den Inhalt des "Custom Functions JSON" Textfelds mit dem obigen JSON
4. Im System Prompt: Fügen Sie nur diese eine Zeile bei den "WICHTIGE ANWEISUNGEN" hinzu:
   ```
   - NIEMALS nach der Telefonnummer fragen - verwende immer {{caller_phone_number}} oder die call_id
   ```
5. Klicken Sie auf "Save Version"
6. Klicken Sie auf "Publish Version" für die neue Version

## Technische Details

- Alle Functions bekommen jetzt `call_id` als Parameter
- Backend löst die Telefonnummer über: `Call::where('call_id', $callId)->first()`
- Der bestehende umfangreiche Prompt bleibt erhalten
- Nur die Custom Functions werden aktualisiert