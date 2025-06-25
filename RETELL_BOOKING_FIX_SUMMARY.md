# Retell Terminbuchung - Problem gefunden und Lösung

## 🔍 Problem-Analyse

### Was ist passiert?
Die Testanrufe wurden erfolgreich empfangen und verarbeitet, aber die Terminbuchung schlug fehl mit folgenden Fehlern:
- "The datum field is required"
- "The uhrzeit field is required"
- "The name field is required"
- "The dienstleistung field is required"

### Root Cause
**Der Retell Agent V33 hat KEINE Custom Function für die Terminbuchung konfiguriert!**

Die `collect_appointment_data` Function fehlt komplett in der Agent-Konfiguration. Deshalb:
1. Der Agent sammelt zwar die Informationen im Gespräch
2. Aber er ruft nie die Funktion auf, um die Daten zu speichern
3. Wenn der Anruf endet, sind keine Termindaten vorhanden
4. Die Terminbuchung schlägt fehl

## ✅ Lösung

### Schritt 1: Custom Function in Retell konfigurieren

1. Gehe zu https://app.retellai.com/
2. Finde den Agent "Assistent für Fabian Spitzer Rechtliches V33"
3. Klicke auf Edit/Configure
4. Füge eine Custom Function hinzu:

```json
{
  "name": "collect_appointment_data",
  "description": "Sammelt alle notwendigen Termindaten vom Anrufer",
  "type": "remote_tool",
  "method": "POST",
  "url": "https://api.askproai.de/api/retell/collect-appointment",
  "headers": {
    "Content-Type": "application/json"
  },
  "parameters": {
    "properties": {
      "datum": {
        "type": "string",
        "required": true,
        "description": "Das Datum des gewünschten Termins"
      },
      "uhrzeit": {
        "type": "string",
        "required": true,
        "description": "Die gewünschte Uhrzeit"
      },
      "name": {
        "type": "string",
        "required": true,
        "description": "Der vollständige Name des Kunden"
      },
      "telefonnummer": {
        "type": "string",
        "required": true,
        "description": "Die Telefonnummer (wird automatisch gefüllt)"
      },
      "dienstleistung": {
        "type": "string",
        "required": true,
        "description": "Die gewünschte Dienstleistung"
      },
      "email": {
        "type": "string",
        "required": false,
        "description": "E-Mail für Bestätigung (optional)"
      }
    },
    "required": ["datum", "uhrzeit", "name", "telefonnummer", "dienstleistung"]
  }
}
```

### Schritt 2: Agent Prompt erweitern

Füge zum Prompt hinzu:

```
TERMINBUCHUNG:
Wenn ein Kunde einen Termin buchen möchte:
1. Sammle ALLE erforderlichen Informationen:
   - Datum (frage nach dem gewünschten Tag)
   - Uhrzeit (frage nach der bevorzugten Zeit)
   - Name (frage nach dem vollständigen Namen)
   - Dienstleistung (was möchte der Kunde buchen?)
   
2. Die Telefonnummer wird automatisch erfasst - du musst nicht danach fragen.

3. Sobald du ALLE Pflichtinformationen hast, rufe die Funktion 'collect_appointment_data' auf.

4. Bestätige dem Kunden die erfolgreiche Terminbuchung mit der Referenznummer.
```

## 📊 Status Update

### Was funktioniert bereits:
- ✅ Webhook-Empfang und Verarbeitung
- ✅ Call History im Dashboard
- ✅ Endpoint für Terminbuchung (`/api/retell/collect-appointment`)
- ✅ Caching-System für Termindaten
- ✅ Appointment Booking Service

### Was muss noch gemacht werden:
- ⚠️ Custom Function im Retell Agent konfigurieren (MANUELL!)
- ⚠️ Agent Prompt anpassen
- ⚠️ Nach Konfiguration: Testanruf durchführen

## 🚀 Nächste Schritte

1. **Sofort**: Konfiguriere die Custom Function in Retell.ai
2. **Test**: Führe einen neuen Testanruf durch
3. **Verifizierung**: Prüfe im Dashboard ob der Termin erfasst wurde

## 📞 Test-Szenario

Nach der Konfiguration:
```
Anrufer: "Ich möchte einen Termin buchen"
Agent: "Gerne! Für wann möchten Sie den Termin?"
Anrufer: "Morgen um 10 Uhr"
Agent: "Und welche Dienstleistung wünschen Sie?"
Anrufer: "Eine Rechtsberatung"
Agent: "Darf ich Ihren Namen erfahren?"
Anrufer: "Max Mustermann"
Agent: [ruft collect_appointment_data auf]
Agent: "Perfekt! Ihr Termin wurde gebucht. Ihre Referenznummer ist REF-2024-XXXXX"
```

## 🛠️ Hilfs-Scripts

- `check-retell-custom-function.php` - Prüft ob Custom Functions konfiguriert sind
- `configure-retell-custom-function.php` - Zeigt die genaue Konfiguration an

## 📝 Zusammenfassung

Das Problem ist klar identifiziert: Der Retell Agent hat keine Custom Function konfiguriert. Sobald diese hinzugefügt wird, sollten Terminbuchungen funktionieren.

Die gesamte Infrastruktur (Endpoint, Webhook-Verarbeitung, Booking Service) ist bereits vorhanden und wartet nur darauf, dass der Agent die Funktion aufruft.