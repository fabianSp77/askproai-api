# Retell.ai Telefonnummer-Problem GELÖST ✅

## Problem
- Retell.ai löst System-Variablen wie `{{caller_phone_number}}` nicht auf
- Agent fragt wiederholt nach Telefonnummer, obwohl diese bereits vorhanden ist
- Custom Functions erhalten nur "+49" oder "{{caller_phone_number}}" als Text statt der echten Nummer
- 500 Server Error bei Terminbuchung wegen fehlender Telefonnummer

## Ursache
Retell.ai sendet die System-Variablen nicht aufgelöst an unsere Custom Functions. Stattdessen sendet es:
- `telefonnummer: "+49"` (unvollständig)
- `telefonnummer: "{{caller_phone_number}}"` (nicht aufgelöst)

## Lösung
Wir nutzen jetzt die `call_id` um die Telefonnummer aus unserer Datenbank zu holen:

### 1. **Code-Änderungen**
- `RetellCustomFunctionsController::collectAppointment()` - Holt Telefonnummer via call_id
- `RetellCustomFunctionsController::checkCustomer()` - Holt Telefonnummer via call_id
- Fallback auf Request-Parameter wenn call_id nicht funktioniert
- Bessere Fehlerbehandlung bei fehlender Telefonnummer

### 2. **Neue Custom Functions Konfiguration**
Alle Functions erwarten jetzt `call_id` als Parameter:
```json
{
  "name": "collect_appointment_data",
  "parameters": {
    "call_id": "{{call_id}}",  // NEU: Wird von Retell bereitgestellt
    "name": "...",
    "datum": "...",
    "uhrzeit": "..."
  }
}
```

### 3. **Neuer System Prompt**
- Explizite Anweisung: **NIEMALS nach Telefonnummer fragen**
- Verwendet `{{call_id}}` bei allen Funktionsaufrufen
- Klarer linearer Ablauf ohne Schleifen

## Installation

### 1. Retell.ai Dashboard öffnen
1. Navigieren Sie zu Ihrem Agent
2. Öffnen Sie die Agent-Einstellungen

### 2. System Prompt aktualisieren
Ersetzen Sie den kompletten System Prompt mit dem Inhalt aus:
```
/var/www/api-gateway/retell-system-prompt-v2.txt
```

### 3. Custom Functions aktualisieren
Ersetzen Sie alle Custom Functions mit dem Inhalt aus:
```
/var/www/api-gateway/retell-custom-functions-v2.json
```

### 4. Dynamic Variables prüfen
Stellen Sie sicher, dass diese Variables aktiviert sind:
- `{{call_id}}` ✅ (WICHTIG!)
- `{{caller_phone_number}}`
- `{{company_name}}`
- `{{current_date}}`
- `{{current_time}}`

## Technische Details

### Workflow
1. Retell.ai ruft Custom Function mit `call_id` auf
2. Unser Backend sucht Call-Record in DB: `Call::where('call_id', $callId)`
3. Telefonnummer wird aus `call->from_number` geholt
4. Terminbuchung erfolgt mit korrekter Telefonnummer

### Vorteile
- Keine wiederholten Fragen nach Telefonnummer
- Robuste Lösung unabhängig von Retell's Variable-Resolution
- Funktioniert auch wenn Retell die Variablen ändert
- Bessere Fehlerbehandlung

## Status: FERTIG ✅

Nach der Aktualisierung im Retell Dashboard sollte die Terminbuchung reibungslos funktionieren!