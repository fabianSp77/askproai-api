# Vollständige Webhook-System Analyse

## Übersicht der Webhook-Implementierungen

### 1. **Aktive Webhook-Endpoints**

#### Retell.ai Webhooks
- **Primärer Endpoint**: `/api/retell/webhook` (POST)
  - Controller: `RetellWebhookController@processWebhook`
  - Middleware: `verify.retell.signature` (VerifyRetellSignatureTemporary)
  - Status: ✅ AKTIV (mit Signatur-Problemen)

- **Debug Endpoint**: `/api/retell/debug-webhook` (POST)
  - Controller: `RetellDebugController@debugWebhook`
  - Middleware: KEINE (für Testing)
  - Status: ✅ AKTIV

- **Enhanced Endpoint**: `/api/retell/enhanced-webhook` (POST)
  - Controller: `RetellEnhancedWebhookController@handle`
  - Middleware: KEINE
  - Status: ✅ AKTIV

- **Function Call Handler**: `/api/retell/function-call` (POST)
  - Controller: `RetellRealtimeController@handleFunctionCall`
  - Middleware: `verify.retell.signature`
  - Status: ✅ AKTIV

#### Cal.com Webhooks
- **Hauptendpoint**: `/api/calcom/webhook` (POST)
  - Controller: `CalcomWebhookController@handle`
  - Middleware: `calcom.signature`
  - Status: ✅ AKTIV

- **Ping Endpoint**: `/api/calcom/webhook` (GET)
  - Controller: `CalcomWebhookController@ping`
  - Middleware: KEINE
  - Status: ✅ AKTIV

#### Stripe Webhooks
- **Hauptendpoint**: `/api/stripe/webhook` (POST)
  - Controller: `StripeWebhookController@handle`
  - Middleware: `verify.stripe.signature`, `webhook.replay.protection`
  - Status: ✅ AKTIV

- **Billing Endpoint**: `/api/billing/webhook` (POST)
  - Controller: `BillingController@webhook`
  - Middleware: KEINE
  - Status: ✅ AKTIV

#### Unified Webhook Handler
- **Universal Endpoint**: `/api/webhook` (POST)
  - Controller: `UnifiedWebhookController@handle`
  - Middleware: KEINE (Auto-Detection)
  - Status: ✅ AKTIV

- **Health Check**: `/api/webhook/health` (GET)
  - Controller: `UnifiedWebhookController@health`
  - Status: ✅ AKTIV

#### Test Webhooks
- **Test Endpoint**: `/api/test/webhook` (POST)
  - Controller: `TestWebhookController@test`
  - Middleware: KEINE
  - Status: ✅ AKTIV (nur für Development)

### 2. **Webhook-Verarbeitung Status**

#### Datenbank-Analyse (Stand: 19.06.2025)

**webhook_logs Tabelle:**
- Gesamt: 12 Webhooks empfangen
- Erfolgreich: 9 (75%)
- Fehler: 3 (25%)
- Alle von: Retell
- Event Type: call_ended

**webhook_events Tabelle:**
- 1 Event gespeichert
- Provider: retell
- Status: failed
- Event Type: call_ended

**retell_webhooks Tabelle:**
- 18 Events gespeichert
- Alle: call_ended
- Status: Alle "pending" (nicht verarbeitet)
- Problem: call_id ist NULL bei allen Einträgen

### 3. **Identifizierte Probleme**

#### A. Signatur-Verifikation
- Viele Webhooks schlagen mit "Invalid webhook signature" fehl
- Inkonsistente Signatur-Verifikation zwischen Endpoints
- Mehrere Signatur-Middleware-Versionen:
  - `VerifyRetellSignature` (strict)
  - `VerifyRetellSignatureTemporary` (weniger strict)
  - `VerifyRetellSignatureBypass` (deaktiviert)

#### B. Webhook-Verarbeitung
- Retell Webhooks werden empfangen aber nicht vollständig verarbeitet
- call_id ist NULL in retell_webhooks Tabelle
- Keine Cal.com oder Stripe Webhooks in den Logs

#### C. Multi-Tenancy Issues
- Webhook-Verarbeitung hat Probleme mit Tenant-Zuordnung
- Company/Branch Zuordnung funktioniert nicht zuverlässig

### 4. **Webhook Handler Architektur**

```
Request → Middleware (Signature) → Controller → WebhookProcessor → Handler → Job Queue
                                                        ↓
                                              WebhookHandlerInterface
                                                        ↓
                                         ┌──────────────┼──────────────┐
                                         │              │              │
                                  RetellHandler  CalcomHandler  StripeHandler
```

### 5. **Unterstützte Event Types**

#### Retell Events:
- call_started
- call_ended ✅ (hauptsächlich verwendet)
- call_analyzed
- call_inbound
- call_outbound

#### Cal.com Events:
- BOOKING_CREATED
- BOOKING_RESCHEDULED
- BOOKING_CANCELLED
- BOOKING_CONFIRMED
- BOOKING_REJECTED
- BOOKING_REQUESTED
- BOOKING_PAYMENT_INITIATED
- FORM_SUBMITTED
- MEETING_ENDED
- RECORDING_READY

#### Stripe Events:
- checkout.session.completed
- customer.*
- customer.subscription.*
- invoice.*
- payment_intent.*
- payment_method.*

### 6. **Test-Scripts Verfügbar**

1. **test-webhook-flow.php** - Simuliert kompletten Webhook-Flow
2. **test-webhook-signature.php** - Testet Signatur-Verifikation
3. **analyze-retell-webhook-signature.php** - Analysiert Signatur-Probleme
4. **check-recent-webhooks.php** - Zeigt letzte Webhooks
5. **monitor-webhooks.sh** - Live-Monitoring Script

### 7. **Funktionsfähigkeit**

✅ **Funktioniert:**
- Webhook-Endpoints sind erreichbar
- Test-Webhook Controller funktioniert
- Grundlegende Webhook-Empfang funktioniert
- Logging funktioniert

❌ **Funktioniert NICHT:**
- Signatur-Verifikation bei Retell (teilweise)
- Vollständige Webhook-Verarbeitung für Retell
- Automatische Appointment-Erstellung aus Webhooks
- Cal.com Webhook-Integration (keine Events)
- Stripe Webhook-Integration (keine Events)

### 8. **Empfohlene Sofortmaßnahmen**

1. **Retell Webhook Secret prüfen:**
   ```bash
   php artisan tinker
   >>> config('services.retell.secret')
   >>> config('services.retell.webhook_secret')
   ```

2. **Webhook URL in Retell.ai Dashboard verifizieren:**
   - URL: https://api.askproai.de/api/retell/webhook
   - Events: call_ended aktivieren

3. **Test-Webhook senden:**
   ```bash
   php test-webhook-flow.php
   ```

4. **Monitoring aktivieren:**
   ```bash
   ./monitor-webhooks.sh
   ```

5. **Debug-Endpoint temporär nutzen:**
   - Retell Webhook URL ändern zu: `/api/retell/debug-webhook`
   - Oder Enhanced Endpoint: `/api/retell/enhanced-webhook`

### 9. **Logs und Debugging**

**Laravel Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i webhook
```

**Nginx Access Logs:**
```bash
tail -f /var/log/nginx/access.log | grep webhook
```

**Datenbank-Monitoring:**
```sql
-- Letzte Webhooks
SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 10;

-- Webhook-Statistiken
SELECT provider, status, COUNT(*) FROM webhook_logs GROUP BY provider, status;

-- Pending Webhooks
SELECT * FROM retell_webhooks WHERE status = 'pending';
```