# Retell.ai Custom Functions Setup

## Übersicht
Dieses Dokument beschreibt die korrekte Konfiguration der Custom Functions in Retell.ai für AskProAI.

## 1. Function: `current_time_berlin`

Diese Funktion ruft die aktuelle Zeit in Berlin ab.

### Konfiguration in Retell.ai:
- **Name**: `current_time_berlin`
- **Description**: Gibt aktuelle Uhrzeit und Datum in Berlin zurück
- **URL**: `https://api.askproai.de/api/zeitinfo?locale=de`
- **Method**: `GET`
- **Headers**: Keine erforderlich

### Response Schema:
```json
{
  "type": "object",
  "properties": {
    "date": {
      "type": "string",
      "description": "Aktuelles Datum im Format TT.MM.JJJJ"
    },
    "weekday": {
      "type": "string", 
      "description": "Aktueller Wochentag ausgeschrieben"
    },
    "time": {
      "type": "string",
      "description": "Aktuelle Uhrzeit im Format HH:MM"
    }
  },
  "required": ["date", "time", "weekday"]
}
```

## 2. Function: `collect_appointment_data`

⚠️ **WICHTIG**: Diese Funktion ist KEINE externe API-Funktion! 

### Was ist `collect_appointment_data`?
- Es ist eine **interne Retell.ai Funktion**
- Sie sammelt Daten während des Gesprächs
- Die Daten werden am Ende des Anrufs im Webhook übertragen

### Konfiguration in Retell.ai:
- **Name**: `collect_appointment_data`
- **Type**: `Data Collection Function` (NICHT External API)
- **Method**: Keine URL erforderlich - es ist eine interne Funktion
- **URL**: LEER LASSEN oder "internal"

### Parameter Schema:
```json
{
  "type": "object",
  "properties": {
    "datum": {
      "type": "string",
      "description": "Gewünschtes Datum des Termins"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Gewünschte Uhrzeit"
    },
    "name": {
      "type": "string",
      "description": "Name des Kunden"
    },
    "telefonnummer": {
      "type": "string",
      "description": "Telefonnummer des Kunden"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Gewünschte Dienstleistung"
    },
    "email": {
      "type": "string",
      "description": "E-Mail-Adresse (optional)"
    },
    "mitarbeiter_wunsch": {
      "type": "string",
      "description": "Bevorzugter Mitarbeiter (optional)"
    },
    "notizen": {
      "type": "string",
      "description": "Zusätzliche Notizen (optional)"
    }
  },
  "required": ["datum", "uhrzeit", "name", "telefonnummer", "dienstleistung"]
}
```

## 3. Webhook-Konfiguration

Der Webhook empfängt die gesammelten Daten nach Anrufende.

### Webhook Setup:
- **URL**: `https://api.askproai.de/api/retell/webhook`
- **Method**: `POST`
- **Events**: 
  - ✅ `call_started`
  - ✅ `call_ended`
  - ✅ `call_analyzed`

### Datenfluss:
1. Agent sammelt Daten mit `collect_appointment_data`
2. Daten werden in `retell_llm_dynamic_variables` gespeichert
3. Bei `call_ended` Event werden die Daten an den Webhook gesendet
4. AskProAI verarbeitet die Daten und erstellt den Termin

## 4. Beispiel Webhook Payload

```json
{
  "event": "call_ended",
  "call": {
    "call_id": "abc123",
    "agent_id": "agent_xyz",
    "from_number": "+491234567890",
    "to_number": "+493083793369",
    "retell_llm_dynamic_variables": {
      "appointment_data": {
        "datum": "morgen",
        "uhrzeit": "15:00",
        "name": "Max Mustermann",
        "telefonnummer": "+491234567890",
        "dienstleistung": "Haarschnitt",
        "email": "max@example.com"
      }
    }
  }
}
```

## 5. Häufige Fehler

### ❌ FALSCH: collect_appointment_data als External API
```json
{
  "name": "collect_appointment_data",
  "url": "https://api.askproai.de/api/collect", // FALSCH!
  "method": "POST" // FALSCH!
}
```

### ✅ RICHTIG: collect_appointment_data als interne Funktion
```json
{
  "name": "collect_appointment_data",
  "type": "data_collection",
  "parameters": { ... } // Nur Parameter definieren
}
```

## 6. Test-Checkliste

- [ ] `current_time_berlin` Function konfiguriert mit korrekter URL
- [ ] `collect_appointment_data` als interne Funktion (KEINE URL)
- [ ] Webhook URL konfiguriert: `https://api.askproai.de/api/retell/webhook`
- [ ] Webhook Events aktiviert: call_started, call_ended, call_analyzed
- [ ] Agent Prompt nutzt beide Functions korrekt

## 7. Troubleshooting

### "collect_appointment_data nicht gefunden"
- Prüfen Sie, ob die Funktion als interne Funktion angelegt wurde
- Stellen Sie sicher, dass KEINE URL angegeben ist

### "Webhook empfängt keine Daten"
- Webhook URL prüfen
- Webhook Events aktiviert?
- Logs prüfen: `tail -f storage/logs/laravel.log | grep "RETELL WEBHOOK"`

### "Zeit-Funktion liefert falsche Zeit"
- URL muss genau sein: `https://api.askproai.de/api/zeitinfo?locale=de`
- Method muss GET sein