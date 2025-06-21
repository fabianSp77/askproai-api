# MCP Datenfluss - Vollständige Dokumentation mit Flowchart

## 1. Hauptdatenfluss: Anruf → Termin

```mermaid
flowchart TD
    Start([Kunde ruft an: +493083793369])
    
    Start --> Retell[Retell.ai Agent<br/>agent_9a8202a740cd3120d96fcfda1e]
    
    Retell --> |Audio Stream| RetellBackend[Retell Backend<br/>- Transcription<br/>- LLM Processing<br/>- Dynamic Variables]
    
    RetellBackend --> |Webhook: call_ended| WebhookEndpoint["/api/mcp/retell/webhook"<br/>POST Request]
    
    WebhookEndpoint --> SignatureCheck{Signature<br/>Verification}
    SignatureCheck -->|Invalid| Error1[401 Unauthorized]
    SignatureCheck -->|Valid| WebhookMCP[WebhookMCPServer::processRetellWebhook]
    
    WebhookMCP --> PhoneResolver[Phone Resolution<br/>+493083793369 → Branch]
    
    PhoneResolver --> |SQL Query| PhoneDB[(phone_numbers table<br/>phone_number<br/>branch_id<br/>company_id)]
    
    PhoneDB --> BranchDB[(branches table<br/>id<br/>calcom_event_type_id<br/>is_active)]
    
    BranchDB --> CreateCustomer[Find/Create Customer<br/>by phone number]
    
    CreateCustomer --> CustomerDB[(customers table<br/>id<br/>phone<br/>name<br/>email)]
    
    CustomerDB --> CreateCall[Create Call Record]
    
    CreateCall --> CallDB[(calls table<br/>call_id<br/>customer_id<br/>branch_id<br/>extracted_date<br/>extracted_time)]
    
    CallDB --> CheckBooking{booking_confirmed<br/>= true?}
    
    CheckBooking -->|No| EndNoBooking[End: No Appointment]
    CheckBooking -->|Yes| CalcomMCP[CalcomMCPServer::createBooking]
    
    CalcomMCP --> EventTypeDB[(calcom_event_types<br/>calcom_numeric_event_type_id<br/>team_id<br/>is_team_event)]
    
    EventTypeDB --> |Get Team ID| CalcomAPI[Cal.com API<br/>POST /bookings]
    
    CalcomAPI --> |Success| CreateAppointment[Create Appointment Record]
    CalcomAPI --> |Error| BookingError[Log Error & Retry]
    
    CreateAppointment --> AppointmentDB[(appointments table<br/>id<br/>call_id<br/>customer_id<br/>calcom_booking_id)]
    
    AppointmentDB --> UpdateCall[Update Call with<br/>appointment_id]
    
    UpdateCall --> Success[✅ Booking Complete]
```

## 2. MCP Server Kommunikation

```mermaid
flowchart LR
    subgraph MCP Servers
        WebhookMCP[WebhookMCPServer]
        CalcomMCP[CalcomMCPServer]
        RetellMCP[RetellMCPServer]
        DatabaseMCP[DatabaseMCPServer]
        QueueMCP[QueueMCPServer]
    end
    
    subgraph External APIs
        RetellAPI[Retell.ai API]
        CalcomAPI[Cal.com API]
    end
    
    subgraph Database
        DB[(MySQL Database)]
    end
    
    WebhookMCP --> CalcomMCP
    WebhookMCP --> RetellMCP
    WebhookMCP --> DatabaseMCP
    WebhookMCP --> QueueMCP
    
    RetellMCP <--> RetellAPI
    CalcomMCP <--> CalcomAPI
    DatabaseMCP <--> DB
```

## 3. Datenstrukturen & Übergaben

### 3.1 Retell Webhook → WebhookMCPServer

**Endpoint**: `POST /api/mcp/retell/webhook`

**Headers**:
```http
X-Retell-Signature: {HMAC-SHA256 signature}
X-Retell-Timestamp: {timestamp}
Content-Type: application/json
```

**Payload**:
```json
{
  "event": "call_ended",
  "call": {
    "call_id": "abc123",
    "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
    "from_number": "+491234567890",
    "to_number": "+493083793369",
    "direction": "inbound",
    "call_status": "ended",
    "start_timestamp": 1750540695000,
    "end_timestamp": 1750540995000,
    "duration_ms": 300000,
    "transcript": "Vollständiges Transkript...",
    "summary": "AI-generierte Zusammenfassung",
    "call_analysis": {
      "appointment_requested": true,
      "customer_name": "Max Mustermann",
      "sentiment": "positive"
    },
    "retell_llm_dynamic_variables": {
      "booking_confirmed": true,
      "name": "Max Mustermann",
      "datum": "2025-07-02",
      "uhrzeit": "11:00",
      "dienstleistung": "Beratung",
      "email": "max@example.com"
    }
  }
}
```

### 3.2 WebhookMCPServer → CalcomMCPServer

**Method Call**: `createBooking(array $params)`

**Parameters**:
```php
[
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => '2025-07-02T11:00:00+02:00',
    'end' => '2025-07-02T11:30:00+02:00',
    'name' => 'Max Mustermann',
    'email' => 'max@example.com',
    'phone' => '+491234567890',
    'notes' => 'Via Telefon gebucht',
    'metadata' => [
        'call_id' => '123',  // String!
        'source' => 'mcp_webhook'
    ]
]
```

### 3.3 CalcomMCPServer → Cal.com API

**Endpoint**: `POST https://api.cal.com/v1/bookings?apiKey={API_KEY}`

**Request Body**:
```json
{
  "eventTypeId": 2563193,
  "teamId": 39203,
  "start": "2025-07-02T11:00:00+02:00",
  "end": "2025-07-02T11:30:00+02:00",
  "timeZone": "Europe/Berlin",
  "language": "de",
  "responses": {
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+491234567890",
    "notes": "Via Telefon gebucht"
  },
  "metadata": {
    "call_id": "123",
    "source": "mcp_webhook"
  }
}
```

**Response**:
```json
{
  "id": 8727139,
  "uid": "sFJvi8wZwD4mEYFsezvEWD",
  "status": "ACCEPTED",
  "startTime": "2025-07-02T09:00:00.000Z",
  "endTime": "2025-07-02T09:30:00.000Z",
  "user": {
    "name": "Fabian Spitzer",
    "email": "fabian@askproai.de"
  }
}
```

## 4. Dashboard & Monitoring Datenabrufe

### 4.1 MCPDashboard

```mermaid
flowchart TD
    Dashboard[MCPDashboard Page]
    
    Dashboard --> Stats1[getWebhookStats]
    Dashboard --> Stats2[getBookingStats]
    Dashboard --> Stats3[getCallStats]
    Dashboard --> Stats4[getSystemHealth]
    
    Stats1 --> WebhookMCP[WebhookMCPServer::getWebhookStats]
    Stats2 --> CalcomMCP[CalcomMCPServer::getBookings]
    Stats3 --> DatabaseMCP[DatabaseMCPServer::query]
    Stats4 --> CircuitBreaker[Circuit Breaker Status]
    
    WebhookMCP --> Query1[SQL: COUNT webhooks<br/>GROUP BY status]
    CalcomMCP --> CalAPI[Cal.com API<br/>GET /bookings]
    DatabaseMCP --> Query2[SQL: Call statistics]
```

### 4.2 DataSync Page

```mermaid
flowchart LR
    DataSync[DataSync Page]
    
    DataSync --> SyncAgents[Sync Retell Agents]
    DataSync --> SyncPhones[Sync Phone Numbers]
    DataSync --> SyncEvents[Sync Event Types]
    
    SyncAgents --> RetellMCP[RetellMCPServer::getAgents]
    SyncPhones --> RetellMCP2[RetellMCPServer::syncPhoneNumbers]
    SyncEvents --> CalcomMCP[CalcomMCPServer::getEventTypes]
    
    RetellMCP --> RetellAPI[GET /list-agents]
    RetellMCP2 --> RetellAPI2[GET /list-phone-numbers]
    CalcomMCP --> CalAPI[GET /event-types]
```

### 4.3 WebhookMonitor

**Datenabruf**:
```sql
SELECT 
    event_type,
    COUNT(*) as count,
    AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_processing_time,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
FROM webhook_events
WHERE created_at > NOW() - INTERVAL 24 HOUR
GROUP BY event_type
```

## 5. API Verwendung nach Service

### 5.1 Retell.ai APIs

| Endpoint | Verwendung | MCP Server |
|----------|-----------|------------|
| GET /list-agents | Agent Liste abrufen | RetellMCPServer |
| PATCH /update-agent/{id} | Agent Prompt updaten | RetellMCPServer |
| GET /list-phone-numbers | Phone Numbers sync | RetellMCPServer |
| GET /list-calls | Call History | RetellMCPServer |
| GET /retrieve-call/{id} | Call Details | RetellMCPServer |

### 5.2 Cal.com APIs

| Endpoint | Verwendung | MCP Server |
|----------|-----------|------------|
| GET /event-types | Event Types abrufen | CalcomMCPServer |
| GET /availability | Verfügbarkeit prüfen | CalcomMCPServer |
| POST /bookings | Termin buchen | CalcomMCPServer |
| GET /bookings | Termine abrufen | CalcomMCPServer |
| POST /bookings/{id}/cancel | Termin stornieren | CalcomMCPServer |

## 6. Cache & Circuit Breaker Flow

```mermaid
flowchart TD
    Request[API Request]
    
    Request --> Cache{In Cache?}
    Cache -->|Yes| ReturnCache[Return Cached Data]
    Cache -->|No| CircuitBreaker{Circuit State?}
    
    CircuitBreaker -->|OPEN| Fallback[Return Fallback]
    CircuitBreaker -->|CLOSED| APICall[Make API Call]
    CircuitBreaker -->|HALF_OPEN| TestCall[Test API Call]
    
    APICall -->|Success| UpdateCache[Update Cache]
    APICall -->|Failure| IncrementFailure[Increment Failures]
    
    TestCall -->|Success| ResetCircuit[Reset to CLOSED]
    TestCall -->|Failure| KeepOpen[Keep OPEN]
    
    UpdateCache --> ReturnData[Return Fresh Data]
    IncrementFailure --> CheckThreshold{Threshold<br/>Reached?}
    CheckThreshold -->|Yes| OpenCircuit[Open Circuit]
    CheckThreshold -->|No| ReturnError[Return Error]
```

## 7. Monitoring & Fehlerbehandlung

### 7.1 Logging Flow

```mermaid
flowchart LR
    Event[System Event]
    
    Event --> Logger[Log::info/error]
    Logger --> LogFile[storage/logs/laravel.log]
    Logger --> Sentry[Sentry.io]
    
    LogFile --> LogViewer[Log Viewer Page]
    Sentry --> Alerts[Email/Slack Alerts]
```

### 7.2 Metriken

**Prometheus Metrics** (Endpoint: `/api/metrics`):
- `http_request_duration_seconds` - Request Latency
- `mcp_webhook_total` - Webhook Counter
- `mcp_booking_success_total` - Erfolgreiche Buchungen
- `mcp_booking_failure_total` - Fehlgeschlagene Buchungen
- `circuit_breaker_state` - Circuit Breaker Status

## 8. Datenbank Schema Übersicht

```mermaid
erDiagram
    companies ||--o{ branches : has
    companies ||--o{ customers : has
    companies ||--o{ users : has
    
    branches ||--o{ phone_numbers : has
    branches ||--o{ appointments : has
    branches ||--o{ calls : has
    branches ||--|| calcom_event_types : uses
    
    customers ||--o{ appointments : books
    customers ||--o{ calls : makes
    
    calls ||--o| appointments : creates
    
    calcom_event_types {
        int id PK
        int company_id FK
        int calcom_numeric_event_type_id
        int team_id
        boolean is_team_event
        string title
        int duration_minutes
    }
    
    phone_numbers {
        int id PK
        string phone_number
        string branch_id FK
        int company_id FK
        string retell_phone_number_id
    }
    
    webhook_events {
        int id PK
        string provider
        string event_type
        json payload
        string status
        timestamp processed_at
    }
```

## Zusammenfassung

Diese Dokumentation zeigt den vollständigen Datenfluss vom Anruf bis zur Terminbuchung, einschließlich aller API-Calls, Datenstrukturen und Monitoring-Komponenten. Das MCP System orchestriert alle Komponenten und sorgt für Fehlerbehandlung, Caching und Monitoring.