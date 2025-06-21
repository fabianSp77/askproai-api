# MCP (Model Context Protocol) - Technische Architektur Dokumentation

## 1. Übersicht der MCP Server

Das MCP System besteht aus 5 spezialisierten Servern, die zusammenarbeiten:

```
┌─────────────────────┐
│  WebhookMCPServer   │ ← Haupteingangspunkt für Webhooks
└──────────┬──────────┘
           │
    ┌──────┴──────┬─────────────┬─────────────┐
    ▼             ▼             ▼             ▼
┌────────┐  ┌────────┐  ┌──────────┐  ┌────────┐
│CalcomMCP│  │RetellMCP│  │DatabaseMCP│  │QueueMCP│
└────────┘  └────────┘  └──────────┘  └────────┘
```

## 2. WebhookMCPServer - Der Orchestrator

### Zweck
Zentrale Verarbeitung aller Retell.ai Webhooks mit Terminbuchungslogik.

### Constructor Dependencies
```php
public function __construct(
    CalcomMCPServer $calcomMCP,
    RetellMCPServer $retellMCP,
    DatabaseMCPServer $databaseMCP,
    QueueMCPServer $queueMCP
)
```

### Hauptmethoden

#### processRetellWebhook(array $webhookData): array
**Flow:**
1. Validiert Event Type (nur 'call_ended' wird verarbeitet)
2. Erstellt Customer Record (findOrCreate)
3. Erstellt Call Record
4. Prüft ob Termin erstellt werden soll (shouldCreateAppointment)
5. Erstellt Termin über CalcomMCPServer
6. Returned Erfolgs/Fehler Response

**Wichtige Checks:**
```php
// Event validation
if ($webhookData['event'] !== 'call_ended') {
    return ['success' => true, 'message' => 'Event skipped'];
}

// Phone resolution für Company/Branch Zuordnung
$phoneResolution = $this->resolvePhoneNumber($callData['to_number']);
```

#### shouldCreateAppointment(array $callData): bool
**Flexible Typ-Prüfung für booking_confirmed:**
```php
$bookingConfirmed = 
    $value === true || 
    $value === 'true' || 
    $value === '1' || 
    $value === 1 ||
    (is_string($value) && strtolower($value) === 'yes');
```

**Erforderliche Felder:**
- booking_confirmed = true (verschiedene Formate)
- datum (YYYY-MM-DD)
- uhrzeit (HH:MM)

#### createAppointmentViaMCP(Call $call, array $callData, array $phoneResolution): ?array
**Prozess:**
1. Prüft Cal.com Event Type in Branch
2. Parsed Datum/Zeit
3. Ruft CalcomMCPServer->createBooking()
4. Erstellt lokalen Appointment Record
5. Updated Call mit appointment_id

## 3. CalcomMCPServer - Cal.com Integration

### Features
- Circuit Breaker Pattern
- Response Caching (5 Min TTL)
- Idempotenz für Bookings (24h)
- Team Event Type Support

### Hauptmethoden

#### createBooking(array $params): array
**Erforderliche Parameter:**
```php
[
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => '2025-07-02T11:00:00+02:00',
    'end' => '2025-07-02T11:30:00+02:00',
    'name' => 'Customer Name',
    'email' => 'customer@example.com',
    'phone' => '+491234567890',
    'notes' => 'Optional notes',
    'metadata' => [
        'call_id' => '123',
        'source' => 'mcp_webhook'
    ]
]
```

**Team Event Handling:**
```php
// Hardcoded für Event Type 2563193
if ($eventTypeId == 2563193) {
    $bookingCustomerData['teamId'] = 39203;
}
```

**Circuit Breaker:**
- Failure Threshold: 5
- Success Threshold: 2
- Timeout: 60 Sekunden
- Max Retries: 3

#### checkAvailability(array $params): array
- Cached für 5 Minuten
- Flattened slot structure für v2 API
- Circuit Breaker geschützt

## 4. RetellMCPServer - Retell.ai Management

### Hauptfunktionen
- Agent Management
- Phone Number Sync
- Webhook Configuration
- Call Data Retrieval

### Wichtige Methoden

#### syncPhoneNumbers(array $params): array
- Lädt alle Phone Numbers von Retell
- Erstellt/Updated lokale phone_numbers Records
- Verknüpft mit Branches

#### validateWebhookConfiguration(array $params): array
**Prüft:**
- Webhook URL: `https://api.askproai.de/api/mcp/retell/webhook`
- Events: call_started, call_ended, call_analyzed

## 5. DatabaseMCPServer - Datenbank Operations

### Features
- Circuit Breaker für DB Queries
- Query Performance Monitoring
- Prepared Statements Support

### Methoden
```php
query(array $params): array
// params: ['query' => 'SELECT...', 'bindings' => [...]]

execute(array $params): array
// params: ['query' => 'INSERT...', 'bindings' => [...]]
```

## 6. QueueMCPServer - Job Queue Management

### Dependencies
```php
public function __construct(
    JobRepository $jobs,
    MetricsRepository $metrics,
    SupervisorRepository $supervisors
)
```

### Features
- Horizon Integration
- Job Monitoring
- Failed Job Management

## 7. Datenfluss: Anruf → Termin

```
1. Kunde ruft +493083793369 an
   ↓
2. Retell.ai Agent beantwortet
   ↓
3. Agent sammelt Termindaten:
   - booking_confirmed = true
   - datum = "2025-07-02"
   - uhrzeit = "11:00"
   - name = "Max Mustermann"
   ↓
4. Call endet → Webhook an /api/mcp/retell/webhook
   ↓
5. WebhookMCPServer::processRetellWebhook()
   ↓
6. Phone Resolution: +493083793369 → Branch ID
   ↓
7. Branch hat calcom_event_type_id = 2563193
   ↓
8. CalcomMCPServer::createBooking()
   - Fügt teamId = 39203 hinzu
   - Konvertiert metadata zu Strings
   ↓
9. Cal.com API POST /bookings
   ↓
10. Booking erstellt (ID: 8727139)
    ↓
11. Lokaler Appointment Record erstellt
    ↓
12. Call Record updated mit appointment_id
```

## 8. Fehlerbehandlung

### Circuit Breaker States
- **CLOSED**: Normal operation
- **OPEN**: Service down, requests fail fast
- **HALF_OPEN**: Testing if service recovered

### Retry Logic
```php
$maxRetries = 3;
$retryDelay = 1; // seconds
// Exponential backoff: 1s, 2s, 4s
```

### Idempotenz
```php
$idempotencyKey = md5(serialize($params));
$existingBookingKey = "mcp:calcom:booking:{$idempotencyKey}";
// Cached for 24 hours
```

## 9. Cache Strategy

### Cache Keys
```php
"mcp:calcom:event_types:{company_id}" // 5 min
"mcp:calcom:availability:{event_type}:{date}" // 5 min
"mcp:calcom:booking:{idempotency_key}" // 24h
"mcp:retell:agents:{company_id}" // 10 min
```

### Cache Invalidation
- Nach erfolgreicher Buchung
- Bei Config-Änderungen
- Manuell über Commands

## 10. Monitoring & Debugging

### Logging
```php
Log::info('MCP: Starting operation', [
    'correlation_id' => $correlationId,
    'operation' => 'createBooking',
    'params' => $params
]);
```

### Performance Tracking
- Query execution time
- API response time
- Circuit breaker state changes
- Cache hit/miss rates

### Debug Commands
```bash
# Test webhook processing
php test-mcp-server-direct.php

# Check Cal.com availability
php test-calcom-availability.php

# Direct booking test
php test-direct-mcp-booking.php
```

## 11. Security Considerations

### Webhook Verification
- Signature verification (temporär deaktiviert)
- IP Whitelisting Option
- Rate Limiting

### Data Validation
- Input sanitization
- Type checking
- Required field validation

### Multi-Tenancy
- Company isolation via scopes
- Branch-level permissions
- API key encryption

## 12. Known Limitations

### Hardcoded Values
- Team ID 39203 für Event Type 2563193
- Diese sollten in DB konfigurierbar sein

### Error Recovery
- Keine automatische Retry für fehlgeschlagene Bookings
- Manuelle Intervention erforderlich

### Scalability
- Single Redis instance für Cache
- Keine horizontale Skalierung getestet

## Zusammenfassung

Das MCP System bietet eine robuste, erweiterbare Architektur für die Integration von Telefon-KI mit Kalendersystemen. Durch die Verwendung von Design Patterns wie Circuit Breaker, Caching und Idempotenz wird eine hohe Verfügbarkeit und Zuverlässigkeit gewährleistet.

Die modulare Struktur ermöglicht es, einzelne Komponenten unabhängig zu testen, zu warten und zu erweitern, was die langfristige Wartbarkeit des Systems erhöht.