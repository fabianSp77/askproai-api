# Middleware & Datenfluss Verbesserungs-Plan

## 🎯 Ziel
Sicherstellen, dass die Retell.ai Integration sauber funktioniert mit korrekter Datenübertragung, Speicherung und Weitergabe für Kalenderabfragen und Verfügbarkeitsprüfungen.

## 🔍 Aktuelle Situation

### Kritische Sicherheitslücken
1. **Webhook-Routen sind NICHT geschützt** - Signature-Middleware existiert, wird aber nicht angewendet
2. **Keine Input-Validierung** - Rohdaten werden direkt verarbeitet
3. **SQL-Injection Risiko** - Phone Numbers werden unvalidiert in Queries verwendet
4. **Race Conditions** - Zwischen Redis und Database Checks

### Datenfluss-Probleme
1. **Synchrone Verarbeitung** - `call_inbound` Events blockieren
2. **Fehlende Verfügbarkeitsprüfung** - Termine werden ohne Validierung gebucht
3. **Unklare Tenant-Zuordnung** - Fallback auf "erste Company" ist problematisch

## 📋 Implementierungs-Plan

### Phase 1: Kritische Sicherheits-Fixes (Sofort)
**Zeitrahmen**: 2-3 Stunden

1. **Webhook-Routen absichern**
   - Signature-Middleware auf alle Webhook-Routen anwenden
   - Tests für Middleware-Schutz schreiben

2. **Input-Validierung implementieren**
   - FormRequest-Klassen für alle Webhook-Types erstellen
   - Validierungsregeln für alle Felder definieren
   - Phone Number Validation mit libphonenumber

3. **SQL-Injection Prevention**
   - Alle direkten Queries auf prepared statements umstellen
   - Phone Number normalisierung vor DB-Queries

### Phase 2: Datenfluss-Optimierung (Heute)
**Zeitrahmen**: 3-4 Stunden

1. **Asynchrone Webhook-Verarbeitung**
   - `call_inbound` auf Queue umstellen
   - Schnelle Response mit Default-Agent
   - Verfügbarkeitsprüfung im Hintergrund

2. **Race Condition beheben**
   - Single Source of Truth (Redis) implementieren
   - Atomic Operations für Deduplication
   - Fallback-Mechanismen

3. **Verbesserte Phone-Resolution**
   - Multi-Level Lookup (exact → normalized → partial)
   - Explizite Tenant-Zuordnung
   - Error-Handling für unbekannte Nummern

### Phase 3: Cal.com Integration (Morgen)
**Zeitrahmen**: 4-5 Stunden

1. **Verfügbarkeitsprüfung implementieren**
   - Real-time availability check für `call_inbound`
   - Cached availability für Performance
   - Fallback auf Business Hours

2. **Booking-Validierung**
   - Pre-Booking availability check
   - Double-Booking prevention
   - Konflikt-Resolution

3. **Event-Type Mapping**
   - Service → Cal.com Event Type
   - Staff → Cal.com User
   - Branch → Cal.com Location

### Phase 4: Testing & Monitoring (Übermorgen)
**Zeitrahmen**: 3-4 Stunden

1. **Comprehensive Test Suite**
   - Unit Tests für alle Components
   - Integration Tests für Webhook-Flow
   - E2E Tests für kompletten Booking-Flow

2. **Monitoring & Alerting**
   - Webhook Success/Failure Metrics
   - Response Time Tracking
   - Error Rate Monitoring

3. **Documentation**
   - API Documentation Update
   - Troubleshooting Guide
   - Deployment Checklist

## 🛠️ Technische Details

### Middleware-Konfiguration
```php
// routes/api.php
Route::middleware(['verify.retell.signature', 'validate.webhook'])
    ->group(function () {
        Route::post('/retell/webhook', [RetellWebhookController::class, 'processWebhook']);
        Route::post('/retell/function-call', [RetellRealtimeController::class, 'handleFunctionCall']);
    });
```

### Validierungs-Struktur
```php
class RetellWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event' => 'required|in:call_started,call_ended,call_analyzed,call_inbound',
            'call_id' => 'required|uuid',
            'data' => 'required|array',
            // Specific rules per event type
        ];
    }
}
```

### Queue-Struktur
```php
// Synchronous response for real-time
if ($event === 'call_inbound') {
    $defaultResponse = $this->getQuickResponse($request);
    
    ProcessCallInboundJob::dispatch($request->validated())
        ->onQueue('high-priority');
        
    return response()->json($defaultResponse);
}
```

## 📊 Erfolgs-Metriken

1. **Security**
   - 100% der Webhooks mit Signature-Verification
   - 0 SQL-Injection Vulnerabilities
   - 100% Input Validation Coverage

2. **Performance**
   - < 100ms Response Time für `call_inbound`
   - < 500ms für Availability Checks
   - 0 Timeouts bei Webhook-Processing

3. **Reliability**
   - 0 Duplicate Bookings
   - 99.9% Webhook Processing Success
   - 100% Data Integrity

## 🚨 Risiken & Mitigationen

1. **Breaking Changes**
   - Risiko: Bestehende Webhooks könnten fehlschlagen
   - Mitigation: Schrittweise Migration mit Feature-Flags

2. **Performance Impact**
   - Risiko: Zusätzliche Validierung könnte Performance beeinträchtigen
   - Mitigation: Caching und Async-Processing

3. **Cal.com API Limits**
   - Risiko: Rate Limiting bei vielen Availability-Checks
   - Mitigation: Intelligent Caching und Batch-Requests

## ✅ Definition of Done

- [ ] Alle Webhook-Routen sind mit Middleware geschützt
- [ ] Input-Validierung für alle Webhook-Types implementiert
- [ ] Phone Number Validation funktioniert korrekt
- [ ] Race Conditions sind behoben
- [ ] Asynchrone Verarbeitung für `call_inbound`
- [ ] Verfügbarkeitsprüfung vor Buchung
- [ ] Comprehensive Test Coverage (>90%)
- [ ] Monitoring Dashboard eingerichtet
- [ ] Dokumentation aktualisiert