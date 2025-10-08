# Retell AI Function Call Setup für Echtzeit-Terminbuchung

## 🎯 Übersicht

Dieses Dokument erklärt, wie Sie die Retell AI Functions konfigurieren müssen, damit die KI während eines Anrufs in Echtzeit Termine prüfen und Alternativen anbieten kann.

## 📞 Typische Kundenanfragen und KI-Antworten

### Beispiel 1: Direkter Terminwunsch
**Kunde:** "Ich hätte gerne einen Termin am Freitag um 16 Uhr"
**KI:** [Prüft Verfügbarkeit] "Am Freitag um 16 Uhr ist leider belegt, aber ich kann Ihnen 15:00 Uhr oder 17:30 Uhr anbieten. Was passt Ihnen besser?"

### Beispiel 2: Flexible Anfrage
**Kunde:** "Haben Sie diese Woche noch was frei?"
**KI:** [Sucht verfügbare Termine] "Ja, ich habe noch Termine am Mittwoch um 14:00 Uhr und Donnerstag um 10:30 Uhr frei. Welcher Tag würde Ihnen passen?"

### Beispiel 3: Alternative Suche
**Kunde:** "Der Termin passt nicht, was haben Sie sonst noch?"
**KI:** [Findet Alternativen] "Gerne schaue ich nach weiteren Möglichkeiten. Ich kann Ihnen nächste Woche Dienstag um 14:00 Uhr oder Mittwoch um 11:00 Uhr anbieten."

## 🔧 Retell AI Function Konfiguration

### 1. check_availability Function

```json
{
  "name": "check_availability",
  "description": "Prüft die Verfügbarkeit für einen gewünschten Termin",
  "webhook_url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "speak_during_execution_message": "Einen Moment, ich prüfe die Verfügbarkeit...",
  "parameters": {
    "type": "object",
    "properties": {
      "date": {
        "type": "string",
        "description": "Datum im Format YYYY-MM-DD"
      },
      "time": {
        "type": "string",
        "description": "Uhrzeit im Format HH:MM"
      },
      "relative_day": {
        "type": "string",
        "enum": ["heute", "morgen", "übermorgen", "montag", "dienstag", "mittwoch", "donnerstag", "freitag", "samstag", "sonntag"],
        "description": "Relativer Tag wie 'morgen' oder Wochentag"
      },
      "duration": {
        "type": "integer",
        "description": "Dauer in Minuten",
        "default": 60
      },
      "service_id": {
        "type": "integer",
        "description": "ID des gewünschten Service"
      }
    },
    "required": ["duration"]
  }
}
```

### 2. get_alternatives Function

```json
{
  "name": "get_alternatives",
  "description": "Findet alternative Termine wenn der gewünschte nicht verfügbar ist",
  "webhook_url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "speak_during_execution_message": "Ich suche nach alternativen Terminen für Sie...",
  "parameters": {
    "type": "object",
    "properties": {
      "date": {
        "type": "string",
        "description": "Ursprünglich gewünschtes Datum"
      },
      "time": {
        "type": "string",
        "description": "Ursprünglich gewünschte Zeit"
      },
      "duration": {
        "type": "integer",
        "description": "Dauer in Minuten",
        "default": 60
      },
      "max_alternatives": {
        "type": "integer",
        "description": "Maximale Anzahl Alternativen",
        "default": 3
      },
      "service_id": {
        "type": "integer",
        "description": "Service ID"
      }
    },
    "required": ["duration"]
  }
}
```

### 3. book_appointment Function

```json
{
  "name": "book_appointment",
  "description": "Bucht einen bestätigten Termin",
  "webhook_url": "https://api.askproai.de/api/webhooks/retell/function",
  "speak_during_execution": true,
  "speak_during_execution_message": "Ich buche jetzt Ihren Termin...",
  "speak_after_execution": true,
  "speak_after_execution_message": "{{message}}",
  "parameters": {
    "type": "object",
    "properties": {
      "date": {
        "type": "string",
        "description": "Datum des Termins"
      },
      "time": {
        "type": "string",
        "description": "Uhrzeit des Termins"
      },
      "datetime": {
        "type": "string",
        "description": "ISO 8601 datetime"
      },
      "duration": {
        "type": "integer",
        "description": "Dauer in Minuten"
      },
      "customer_name": {
        "type": "string",
        "description": "Name des Kunden"
      },
      "customer_email": {
        "type": "string",
        "description": "E-Mail des Kunden"
      },
      "customer_phone": {
        "type": "string",
        "description": "Telefonnummer des Kunden"
      },
      "service_id": {
        "type": "integer",
        "description": "Service ID"
      },
      "notes": {
        "type": "string",
        "description": "Zusätzliche Notizen"
      }
    },
    "required": ["customer_name", "duration"]
  }
}
```

### 4. list_services Function

```json
{
  "name": "list_services",
  "description": "Listet verfügbare Services auf",
  "webhook_url": "https://api.askproai.de/api/webhooks/retell/function",
  "parameters": {
    "type": "object",
    "properties": {}
  }
}
```

## 📊 Response Format

### Erfolgreiche Verfügbarkeitsprüfung:

```json
{
  "success": true,
  "data": {
    "available": false,
    "message": "Ich habe leider keinen Termin zu Ihrer gewünschten Zeit gefunden, aber ich kann Ihnen folgende Alternativen anbieten:\n\n1. am gleichen Tag, 15:00 Uhr\n2. am gleichen Tag, 17:30 Uhr\n\nWelcher Termin würde Ihnen besser passen?",
    "requested_time": "2025-09-27 16:00",
    "alternatives": [
      {
        "time": "2025-09-27 15:00",
        "spoken": "am gleichen Tag, 15:00 Uhr",
        "available": true,
        "type": "same_day_earlier"
      },
      {
        "time": "2025-09-27 17:30",
        "spoken": "am gleichen Tag, 17:30 Uhr",
        "available": true,
        "type": "same_day_later"
      }
    ]
  }
}
```

## 🎨 Prompt Engineering für Retell AI

Fügen Sie folgende Instruktionen zu Ihrem Retell Agent Prompt hinzu:

```
Du bist ein freundlicher Terminbuchungsassistent für [Ihr Unternehmen].

WICHTIGE REGELN für Terminbuchungen:

1. Wenn ein Kunde nach einem Termin fragt, verwende IMMER zuerst die "check_availability" Funktion.

2. Wenn der gewünschte Termin nicht verfügbar ist, biete die Alternativen aus der Response an. Frage NICHT "Wann hätten Sie denn Zeit?", sondern biete konkrete Alternativen.

3. Bestätige Termine nur nach expliziter Zustimmung des Kunden mit der "book_appointment" Funktion.

4. Beispiele für natürliche Konversation:
   - Kunde: "Freitag 16 Uhr" → Du: check_availability(date="2025-09-27", time="16:00")
   - Kunde: "Morgen nachmittag" → Du: check_availability(relative_day="morgen", time="14:00")
   - Kunde: "Nächste Woche Dienstag" → Du: check_availability(relative_day="dienstag")

5. Verwende die deutschen Beschreibungen aus den "spoken" Feldern der Alternativen.

6. Wenn keine Alternativen gefunden werden, frage nach einem anderen Zeitraum oder biete an, den Kunden zurückzurufen.
```

## 🚀 Aktivierung

1. **Retell Dashboard öffnen**
   - Gehen Sie zu Ihrem Retell AI Dashboard
   - Wählen Sie Ihren Agent aus

2. **Functions hinzufügen**
   - Klicken Sie auf "Functions" oder "Custom Functions"
   - Fügen Sie jede der oben genannten Functions hinzu
   - Setzen Sie die webhook_url auf Ihre Domain

3. **Agent Prompt anpassen**
   - Fügen Sie die Prompt-Instruktionen hinzu
   - Testen Sie mit verschiedenen Szenarien

4. **Webhook URL konfigurieren**
   - Stellen Sie sicher, dass die URL erreichbar ist
   - Format: `https://[ihre-domain]/api/webhooks/retell/function`

## 📝 Test-Szenarien

1. **Direkter Termin verfügbar:**
   - "Ich möchte einen Termin morgen um 10 Uhr"

2. **Termin nicht verfügbar:**
   - "Haben Sie am Samstag um 15 Uhr Zeit?"

3. **Flexible Anfrage:**
   - "Wann haben Sie diese Woche noch was frei?"

4. **Alternative akzeptieren:**
   - "Ja, dann nehme ich den Termin um 17:30 Uhr"

## 🔍 Debugging

Prüfen Sie die Logs:
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Function call"
```

Test-Endpoint:
```bash
curl -X POST https://api.askproai.de/api/webhooks/retell/function \
  -H "Content-Type: application/json" \
  -d '{
    "function_name": "check_availability",
    "parameters": {
      "date": "2025-09-27",
      "time": "16:00",
      "duration": 60
    },
    "call_id": "test_123"
  }'
```

## ⚠️ Wichtige Hinweise

1. **Cal.com Event Type ID:** Stellen Sie sicher, dass Ihre Services die richtige `calcom_event_type_id` haben
2. **Zeitzone:** Alle Zeiten werden in der Zeitzone Europe/Berlin verarbeitet
3. **Rate Limiting:** Die Function Call Endpoint hat ein Limit von 100 Requests pro Minute
4. **Fallback:** Wenn Cal.com keine Verfügbarkeiten hat, werden intelligente Fallback-Alternativen generiert

## 📞 Support

Bei Fragen oder Problemen:
- Überprüfen Sie die Diagnostic-Endpoint: `/api/webhooks/retell/diagnostic`
- Kontrollieren Sie die Laravel Logs
- Prüfen Sie die Cal.com Konfiguration