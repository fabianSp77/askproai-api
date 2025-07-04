# Retell Telefonfunktion Test-Anleitung

## 🚀 Übersicht

Diese Test-Suite ermöglicht es, die komplette Retell-Telefonfunktion zu testen, von eingehenden Webhooks bis zur Terminbuchung über Cal.com.

## 📋 Voraussetzungen

1. **Retell API konfiguriert** in `.env`:
   ```
   RETELL_TOKEN=key_xxx
   RETELL_WEBHOOK_SECRET=key_xxx
   RETELL_AGENT_ID=agent_xxx
   ```

2. **Cal.com API konfiguriert** für mindestens eine Company
3. **Horizon läuft** für Queue-Verarbeitung: `php artisan horizon`
4. **Test-Routes aktiviert** (automatisch in development/local environment)

## 🧪 Test-Komponenten

### 1. **Live Webhook Monitoring**
```bash
./monitor-retell-webhooks.php
```
- Zeigt eingehende Webhooks in Echtzeit
- Farbcodierte Events (call_started, call_ended, function_call)
- Statistiken alle 30 Sekunden
- Speichert Webhooks in `storage/app/retell-test-webhooks/`

### 2. **Test Dashboard**
```bash
./retell-test-dashboard.php
```
- Übersicht über heutige Aktivitäten
- Letzte Anrufe, Termine und Webhooks
- System-Health-Check
- Verfügbare Test-Befehle

### 3. **Call Started Event Test**
```bash
./test-retell-webhook-call-started.php
```
- Simuliert eingehenden Anruf
- Testet Webhook-Signatur-Verifizierung
- Erstellt Call-Record in Datenbank

### 4. **Call Ended Event Test**
```bash
./test-retell-webhook-call-ended.php
```
- Simuliert beendeten Anruf mit Termindaten
- Testet verschiedene Datenformate (retell_llm_dynamic_variables, custom_analysis_data)
- Löst Terminbuchungsprozess aus
- Erstellt Customer und Appointment Records

### 5. **Custom Function Test**
```bash
./test-retell-function-call.php
```
Testet Retell Custom Functions:
- `collect_appointment` - Sammelt Termindaten
- `check_availability` - Prüft Verfügbarkeit
- `check_customer` - Prüft Kundenexistenz
- `book_appointment` - Bucht Termin

### 6. **Cal.com Integration Test**
```bash
./test-calcom-integration.php
```
- Testet Cal.com Verbindung
- Holt Event-Types
- Prüft Verfügbarkeit
- Simuliert Terminbuchung

## 📖 Test-Ablauf für End-to-End Test

### Schritt 1: Monitoring starten
```bash
# Terminal 1
./monitor-retell-webhooks.php
```

### Schritt 2: Dashboard öffnen
```bash
# Terminal 2
./retell-test-dashboard.php
```

### Schritt 3: Call Started simulieren
```bash
# Terminal 3
./test-retell-webhook-call-started.php
```
- Wähle "N" für Production-Test (erstmal nur Test-Endpoint)
- Prüfe im Monitor ob Webhook empfangen wurde

### Schritt 4: Function Calls testen
```bash
./test-retell-function-call.php
```
- Teste Option 2 (check_availability)
- Teste Option 1 (collect_appointment)
- Prüfe Responses

### Schritt 5: Call Ended mit Termindaten
```bash
./test-retell-webhook-call-ended.php
```
- Teste erst Test-Endpoint
- Bei Erfolg: "Y" für Production-Test
- Prüfe Dashboard für neuen Termin

### Schritt 6: Cal.com Integration prüfen
```bash
./test-calcom-integration.php
```
- Verifiziert Cal.com Verbindung
- Zeigt verfügbare Slots
- Simuliert Buchungsablauf

## 🔍 Debugging

### Logs prüfen
```bash
# Retell-specific logs
tail -f storage/logs/retell.log

# Laravel logs
tail -f storage/logs/laravel.log

# Webhook files
ls -la storage/app/retell-test-webhooks/
```

### Datenbank prüfen
```bash
php artisan tinker
>>> App\Models\Call::latest()->first();
>>> App\Models\Appointment::where('source', 'phone')->latest()->first();
>>> App\Models\WebhookEvent::where('source', 'retell')->latest()->first();
```

### Häufige Probleme

1. **"Signature validation failed"**
   - Prüfe RETELL_WEBHOOK_SECRET in .env
   - Stelle sicher, dass Timestamp nicht älter als 5 Minuten

2. **"No appointments created"**
   - Prüfe ob Horizon läuft
   - Prüfe Company/Branch Zuordnung
   - Prüfe Service-Matching

3. **"Cal.com connection failed"**
   - Prüfe Cal.com API Key
   - Prüfe Netzwerkverbindung

## 🚦 Test-Endpoints

### Test-Endpoints (nur Development)
- `POST /api/retell/test-webhook` - Webhook mit Logging
- `POST /api/retell/test-signature` - Signatur-Validierung
- `POST /api/retell/test-function` - Function Call Test
- `GET /api/retell/test-status` - Test-Status

### Production-Endpoints
- `POST /api/retell/webhook` - Haupt-Webhook
- `POST /api/retell/function-call` - Custom Functions
- `POST /api/retell/realtime/webhook` - Realtime Events

## ✅ Erfolgreiche Integration verifizieren

Ein erfolgreicher End-to-End Test zeigt:

1. ✅ Webhook wird empfangen und verifiziert
2. ✅ Call Record wird erstellt
3. ✅ Custom Functions werden ausgeführt
4. ✅ Termindaten werden extrahiert
5. ✅ Customer wird gefunden/erstellt
6. ✅ Verfügbarkeit wird geprüft
7. ✅ Appointment wird erstellt
8. ✅ Cal.com Buchung erfolgt
9. ✅ Bestätigungs-Email wird gesendet

## 🎯 Nächste Schritte

Nach erfolgreichem Test:

1. **Echten Anruf testen** mit konfigurierter Retell-Nummer
2. **Webhook-URL in Retell.ai** Dashboard eintragen
3. **Agent-Prompts** für Terminbuchung optimieren
4. **Custom Functions** im Agent konfigurieren
5. **Monitoring** für Production einrichten