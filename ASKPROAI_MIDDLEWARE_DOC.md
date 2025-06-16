# AskProAI Middleware Dokumentation

## System-Übersicht
Die AskProAI Middleware verbindet Retell.ai (Telefon-KI) mit Cal.com (Kalendersystem).

### Datenfluss:
1. **Eingehender Anruf** → Retell.ai Agent
2. **Retell.ai** → Sendet Webhook an `/api/retell/webhook`
3. **RetellWebhookController** → Verarbeitet Anrufdaten
4. **CalcomService** → Prüft Verfügbarkeit bei Cal.com
5. **Cal.com API** → Bucht Termin wenn verfügbar
6. **Response** → Zurück an Retell.ai

### Wichtige Komponenten:

#### Controllers:
- `RetellWebhookController` - Hauptverarbeitung der Anrufdaten
- `CalcomController` - Cal.com API Interaktionen
- `CalcomWebhookController` - Empfängt Updates von Cal.com

#### Services:
- `CalcomService` - Cal.com API Wrapper
- `RetellAIService` - Retell.ai API Wrapper

#### Models:
- `Call` - Speichert Anrufdaten
- `Appointment` - Speichert Termine
- `Customer` - Kundendaten
- `CalcomBooking` - Cal.com spezifische Buchungen

### API-Endpunkte:
- POST `/api/retell/webhook` - Empfängt Retell.ai Webhooks
- POST `/api/calcom/webhook` - Empfängt Cal.com Webhooks
- GET `/api/calcom/webhook` - Health Check für Cal.com

### Umgebungsvariablen:
- `RETELL_TOKEN` - API Key für Retell.ai
- `CALCOM_API_KEY` - API Key für Cal.com
- `CALCOM_EVENT_TYPE_ID` - Standard Event-Type für Buchungen
- `CALCOM_WEBHOOK_SECRET` - Webhook Signatur-Verifizierung

### Datenbank-Statistiken:
- Calls: 120 Einträge
- Appointments: 520 Einträge
- Customers: Verknüpft über customer_id

## Konfiguration Status
- Retell.ai: ✅ Funktioniert
- Cal.com: ✅ Funktioniert
- Webhook-Endpunkte: ✅ Konfiguriert
- Multi-Tenant: ✅ Vorbereitet

## Nächste Schritte:
1. Webhook-Signatur-Verifizierung aktivieren
2. Error-Handling verbessern
3. Logging erweitern
4. Test-Suite implementieren
