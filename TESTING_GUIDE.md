# 🧪 Testing Guide für AskProAI mit Cal.com V2

## Übersicht der Test-Möglichkeiten

Das System bietet verschiedene Test-Interfaces und API-Endpoints zum Testen der Cal.com und Retell.ai Integration.

## 1. Filament Admin Panel Test-Seiten

### 📊 Operations Dashboard
**URL**: `/admin`
- Zeigt Live-Daten von Anrufen und Terminen
- Kompakte Filter für Zeitraum, Unternehmen und Filialen
- Performance-Metriken und KPIs

### 🔧 Cal.com API Test
**URL**: `/admin/calcom-api-test`
- Testet sowohl V1 als auch V2 API
- Prüft Authentifizierung
- Zeigt verfügbare Event-Typen
- Testet Booking-Funktionalität

### 📅 Cal.com Live Test
**URL**: `/admin/calcom-live-test`
- Live-Buchungstest mit echten Daten
- Verfügbarkeits-Prüfung
- Termin-Erstellung
- Webhook-Simulation

### 🚀 Quick Setup Wizard
**URL**: `/admin/quick-setup-wizard`
- Schritt-für-Schritt Einrichtung
- Service-Erstellung
- Event-Type Mapping
- Retell Agent Konfiguration

### 📈 Event Analytics Dashboard
**URL**: `/admin/event-analytics-dashboard`
- Detaillierte Analyse von Terminen
- Conversion-Raten
- No-Show Statistiken
- Revenue-Tracking

## 2. API Test-Endpoints

### Cal.com V2 Test Routes

#### Event Types abrufen
```bash
GET /api/test/calcom-v2/event-types
```

#### Verfügbare Slots prüfen
```bash
GET /api/test/calcom-v2/slots?eventTypeId=123&start=2025-06-19&end=2025-06-26
```

#### Test-Buchung erstellen
```bash
POST /api/test/calcom-v2/book
{
    "eventTypeId": 123,
    "start": "2025-06-19T14:00:00Z",
    "name": "Test Kunde",
    "email": "test@example.com"
}
```

### Retell.ai Test

#### Webhook simulieren
```bash
POST /api/retell/webhook
{
    "event": "call_ended",
    "call_id": "test-123",
    "transcript": "Ich möchte einen Termin buchen",
    "custom_analysis_data": {
        "appointment_requested": true,
        "preferred_date": "morgen",
        "preferred_time": "14:00"
    }
}
```

## 3. Test-Szenarien

### Szenario 1: Kompletter Booking Flow
1. **Anruf simulieren** über Retell Webhook
2. **Verfügbarkeit prüfen** über Cal.com V2
3. **Termin buchen** mit Kundendaten
4. **Bestätigung** per E-Mail

### Szenario 2: Multi-Branch Test
1. Verschiedene Filialen anlegen
2. Unterschiedliche Services zuweisen
3. Verfügbarkeit pro Filiale testen
4. Cross-Branch Reporting prüfen

### Szenario 3: Performance Test
```bash
# Concurrent Booking Test
php artisan test tests/E2E/ConcurrentBookingStressTest.php
```

## 4. Wichtige Test-Daten

### Test Event-Type IDs (Beispiele)
- **Standard-Termin**: 2026361
- **Beratungsgespräch**: 2031093
- **Ersttermin**: 2026302

### Test-Telefonnummern
- **Berlin**: +49 30 837 93 369
- **München**: +49 89 123 45 678
- **Hamburg**: +49 40 987 65 432

## 5. Debugging & Logs

### Logs überprüfen
```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Cal.com spezifische Logs
tail -f storage/logs/calcom.log | grep -i error

# Webhook Logs
tail -f storage/logs/laravel.log | grep -i webhook
```

### Debug-Modus aktivieren
```env
# In .env
BOOKING_DEBUG=true
CALCOM_V2_LOGGING_ENABLED=true
CALCOM_V2_LOG_REQUESTS=true
CALCOM_V2_LOG_RESPONSES=true
```

## 6. Häufige Test-Probleme

### Problem: "No availability"
**Lösung**: 
- Event-Type ID prüfen
- Zeitzone kontrollieren (Europe/Berlin)
- Cal.com Kalender-Verfügbarkeit checken

### Problem: "Authentication failed"
**Lösung**:
- API Key in Company Settings prüfen
- Bearer Token Format beachten
- API Version Header setzen

### Problem: "Webhook not processing"
**Lösung**:
- Signature Verification prüfen
- Queue Worker läuft? (`php artisan horizon`)
- Webhook Secret korrekt?

## 7. Test-Commands

```bash
# Alle Tests ausführen
php artisan test

# Nur E2E Tests
php artisan test --testsuite=E2E

# Spezifischer Test
php artisan test --filter=BookingFlowCalcomV2E2ETest

# Mit Coverage
php artisan test --coverage
```

## 8. Postman Collection

Eine Postman Collection ist verfügbar unter:
`/docs/postman/AskProAI_API_Collection.json`

Wichtige Variablen:
- `{{base_url}}`: https://api.askproai.de
- `{{api_token}}`: Bearer Token
- `{{calcom_api_key}}`: Cal.com API Key

## 9. Health Checks

### System Health
```bash
GET /api/health
```

### Cal.com Health
```bash
GET /api/health/calcom
```

### Database Health
```bash
GET /api/health/database
```

## 10. Performance Monitoring

### Dashboard
```bash
php artisan askproai:performance-monitor --live
```

### Metrics API
```bash
GET /api/metrics
```

## Zusammenfassung

Das System bietet umfassende Test-Möglichkeiten:
- ✅ Admin Panel mit visuellen Test-Tools
- ✅ API Endpoints für automatisierte Tests
- ✅ E2E Test Suite für CI/CD
- ✅ Performance Monitoring
- ✅ Health Checks
- ✅ Debug Logging

Alle kritischen Flows können getestet werden, sowohl manuell über das Admin Panel als auch automatisiert über die API.