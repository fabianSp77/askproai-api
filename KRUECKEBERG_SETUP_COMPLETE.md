# Krückeberg Servicegruppe - Setup Abgeschlossen ✅

## Was wurde eingerichtet?

### 1. Company
- **Name**: Krückeberg Servicegruppe
- **ID**: 1
- **Email**: fabian@askproai.de
- **Status**: trial
- **Retell API Key**: key_6ff998ba48e842092e04a5455d19
- **needs_appointment_booking**: FALSE ✅ (keine Terminbuchung)

### 2. Branch
- **Name**: Krückeberg Servicegruppe Zentrale
- **ID**: 9f4d5ace-85ea-481a-800f-4010c8424b2a
- **Adresse**: Oppelner Straße 16, 53119 Bonn
- **Email**: fabian@askproai.de
- **Retell Agent ID**: agent_b36ecd3927a81834b6d56ab07b

### 3. Phone Number
- **Nummer**: +493033081738
- **ID**: cec62518-3f1a-4f84-80f9-f918afd95548
- **Type**: hotline
- **Active**: JA ✅
- **Retell Agent ID**: agent_b36ecd3927a81834b6d56ab07b

### 4. API Endpoint
- **URL**: https://api.askproai.de/api/retell/collect-data
- **Method**: POST
- **Status**: AKTIV ✅

## WICHTIG: Nächste Schritte im Retell.ai Dashboard

### 1. Agent Konfiguration anpassen

Öffne Agent `agent_b36ecd3927a81834b6d56ab07b` und:

#### a) ENTFERNE das `collect_appointment_data` Tool

#### b) FÜGE das neue `collect_customer_data` Tool hinzu:
```json
{
  "name": "collect_customer_data",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/collect-data",
  "method": "POST",
  "description": "Sammelt Kundendaten zur Weitergabe ohne Terminbuchung",
  "parameters": {
    "type": "object",
    "properties": {
      "call_id": {
        "type": "string",
        "description": "Die Call ID - IMMER {{call_id}} verwenden"
      },
      "full_name": {
        "type": "string",
        "description": "Vollständiger Name des Anrufers"
      },
      "company": {
        "type": "string",
        "description": "Firma des Anrufers"
      },
      "customer_number": {
        "type": "string",
        "description": "Kundennummer falls vorhanden"
      },
      "phone_primary": {
        "type": "string",
        "description": "Primäre Rückrufnummer"
      },
      "phone_secondary": {
        "type": "string",
        "description": "Alternative Rückrufnummer (optional)"
      },
      "email": {
        "type": "string",
        "description": "E-Mail-Adresse (optional)"
      },
      "request": {
        "type": "string",
        "description": "Detaillierte Beschreibung des Anliegens"
      },
      "notes": {
        "type": "string",
        "description": "Zusätzliche Notizen (optional)"
      },
      "consent": {
        "type": "boolean",
        "description": "Einverständnis zur Datenspeicherung"
      }
    },
    "required": ["call_id", "full_name", "request", "consent"]
  }
}
```

### 2. Phone Number verknüpfen
- Stelle sicher, dass die Nummer **+493033081738** mit dem Agent verknüpft ist
- Webhook URL muss sein: `https://api.askproai.de/api/retell/webhook-simple`

### 3. Post Call Analysis anpassen
Entferne alle appointment-bezogenen Felder und füge stattdessen hinzu:
- customer_request
- company_name
- callback_required
- priority_level

## Test-Ablauf

1. **Anruf auf +493033081738**
2. **Clara nimmt den Anruf entgegen** und sammelt:
   - Name
   - Firma
   - Telefonnummer(n)
   - E-Mail
   - Anliegen
   - Weitere Notizen
3. **Daten werden gespeichert** im Call-Record
4. **E-Mail geht raus** an fabian@askproai.de
5. **KEINE Terminbuchung** erfolgt

## Technische Details

### Webhook-Flow
1. Anruf → Retell.ai
2. Webhook → `/api/retell/webhook-simple` (UNVERÄNDERT!)
3. System erkennt `needs_appointment_booking = false`
4. Nur Call-Record wird erstellt
5. Custom Function `collect_customer_data` wird aufgerufen
6. Daten werden gespeichert und E-Mail versendet

### Was wurde NICHT geändert?
- ❌ RetellWebhookHandler
- ❌ webhook-simple Endpoint
- ❌ Webhook-Signatur-Verifizierung
- ❌ Bestehende Retell-Integration

## Support
Bei Fragen oder Problemen:
- Check Logs: `tail -f storage/logs/laravel.log | grep -i retell`
- Test Endpoint: `curl -X POST https://api.askproai.de/api/retell/collect-data -d '{"call_id":"test"}'`