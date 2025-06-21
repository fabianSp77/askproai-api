# MCP API Kommunikation - Referenzdokumentation

## 1. Cal.com API Kommunikation

### 1.1 FUNKTIONIERENDE API Calls ✅

#### Event Types abrufen
```bash
GET https://api.cal.com/v1/event-types?apiKey={API_KEY}
Status: 200
Response: {
    "event_types": [
        {
            "id": 2563193,
            "title": "AskProAI + aus Berlin + Beratung",
            "slug": "askproai-aus-berlin-beratung",
            "team": {
                "id": 39203,
                "name": "AskProAI-Teamname"
            }
        }
    ]
}
```

#### Verfügbarkeit prüfen (Team Event)
```bash
GET https://api.cal.com/v1/availability?apiKey={API_KEY}&eventTypeId=2563193&teamId=39203&dateFrom=2025-06-23&dateTo=2025-06-30
Status: 200
Response: {
    "busy": [...],
    "timeZone": "Europe/Berlin",
    "slots": {
        "2025-06-23": ["07:00", "07:30", "08:00", ...]
    }
}
```

#### Termin buchen (Team Event)
```bash
POST https://api.cal.com/v1/bookings?apiKey={API_KEY}
Headers: Content-Type: application/json
Body: {
    "eventTypeId": 2563193,
    "start": "2025-07-02T11:00:00+02:00",
    "end": "2025-07-02T11:30:00+02:00",
    "timeZone": "Europe/Berlin",
    "language": "de",
    "teamId": 39203,  // KRITISCH!
    "responses": {
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+491234567890",
        "notes": "Terminnotiz"
    },
    "metadata": {
        "call_id": "123",  // MUSS STRING SEIN!
        "source": "mcp_webhook"
    }
}
Status: 200
Response: {
    "id": 8727139,
    "uid": "sFJvi8wZwD4mEYFsezvEWD",
    "status": "ACCEPTED",
    ...
}
```

### 1.2 FEHLGESCHLAGENE API Calls ❌

#### Ohne teamId
```bash
POST https://api.cal.com/v1/bookings?apiKey={API_KEY}
Body: {
    "eventTypeId": 2563193,
    // FEHLT: "teamId": 39203
    ...
}
Status: 400
Error: "no_available_users_found_error"
```

#### Mit Number in Metadata
```bash
POST https://api.cal.com/v1/bookings?apiKey={API_KEY}
Body: {
    "metadata": {
        "call_id": 123  // FALSCH: Number statt String
    }
}
Status: 400
Error: "invalid_type in 'metadata,call_id': Expected string, received number"
```

#### V2 API (existiert nicht)
```bash
GET https://api.cal.com/v2/event-types
Status: 404
Error: "Not Found"
```

## 2. Retell API Kommunikation

### 2.1 FUNKTIONIERENDE API Calls ✅

#### Agenten auflisten
```bash
GET https://api.retellai.com/list-agents
Headers: Authorization: Bearer key_37da113d063ce12a93a9daf9eb97
Status: 200
Response: [
    {
        "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
        "agent_name": "askproAi Telesales Agent DE",
        "voice_id": "eleven_multilingual_v2",
        "language": "de",
        "voice_temperature": 0.1,
        "voice_speed": 1.1
    }
]
```

#### Phone Numbers auflisten
```bash
GET https://api.retellai.com/list-phone-numbers
Headers: Authorization: Bearer key_37da113d063ce12a93a9daf9eb97
Status: 200
Response: [
    {
        "phone_number": "+493083793369",
        "phone_number_id": "36c40dd0-76c9-44f7-88b8-72f92f3cf4f5",
        "nickname": "askproai",
        "inbound_agent_id": "agent_9a8202a740cd3120d96fcfda1e"
    }
]
```

#### Agent aktualisieren
```bash
PATCH https://api.retellai.com/update-agent/{agent_id}
Headers: Authorization: Bearer key_37da113d063ce12a93a9daf9eb97
Body: {
    "webhook_url": "https://api.askproai.de/api/mcp/retell/webhook"
}
Status: 200
```

### 2.2 FEHLGESCHLAGENE API Calls ❌

#### Falscher API Key
```bash
GET https://api.retellai.com/list-agents
Headers: Authorization: Bearer 2f2b17d7268[...]  // Zweiter Key aus .env
Status: 500
Error: "Internal Server Error"
```

#### V2 Endpoints (existieren nicht)
```bash
GET https://api.retellai.com/v2/list-agents
Status: 404
Error: "Not Found"
```

## 3. Webhook Kommunikation

### 3.1 FUNKTIONIERENDER Webhook Payload ✅

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
        "transcript": "Kundenkonversation...",
        "summary": "Kunde möchte Termin buchen",
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
            "dienstleistung": "Beratung"
        }
    }
}
```

### 3.2 Webhook Response Format

```json
{
    "success": true,
    "message": "Webhook processed successfully",
    "call_id": 12,
    "customer_id": 1,
    "appointment_created": true,
    "appointment_data": {
        "id": 5,
        "calcom_booking_id": 8727139,
        "starts_at": "2025-07-02 11:00:00"
    },
    "processed": true
}
```

## 4. MCP Server Interne Kommunikation

### 4.1 WebhookMCPServer → CalcomMCPServer

```php
// Request
$bookingData = [
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => '2025-07-02T11:00:00+02:00',
    'end' => '2025-07-02T11:30:00+02:00',
    'name' => 'Max Mustermann',
    'email' => 'max@example.com',
    'phone' => '+491234567890',
    'notes' => 'Via MCP',
    'metadata' => [
        'call_id' => '12',
        'source' => 'mcp_webhook'
    ]
];

// Response
[
    'success' => true,
    'booking' => [
        'id' => 8727139,
        'uid' => 'sFJvi8wZwD4mEYFsezvEWD',
        'start' => '2025-07-02T11:00:00+02:00',
        'end' => '2025-07-02T11:30:00+02:00',
        'status' => 'ACCEPTED',
        'event_type_id' => 2563193
    ],
    'message' => 'Booking created successfully',
    'attempts' => 1
]
```

### 4.2 Phone Resolution

```php
// Input: Phone Number
$phoneNumber = '+493083793369';

// Resolution Process
$phoneRecord = PhoneNumber::where('phone_number', $phoneNumber)->first();

// Result
[
    'company_id' => 1,
    'branch_id' => '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793',
    'calcom_event_type_id' => 2563193
]
```

## 5. HTTP Headers & Authentication

### 5.1 Cal.com Headers
```bash
# V1 API
GET/POST https://api.cal.com/v1/{endpoint}?apiKey={API_KEY}
Content-Type: application/json

# V2 API (wenn verfügbar)
Authorization: Bearer {API_KEY}
cal-api-version: 2024-08-13
Content-Type: application/json
```

### 5.2 Retell Headers
```bash
Authorization: Bearer {RETELL_TOKEN}
Content-Type: application/json
```

### 5.3 Webhook Signature (Production)
```bash
X-Retell-Signature: {HMAC-SHA256 signature}
Content-Type: application/json
```

## 6. Error Codes & Handling

### 6.1 Cal.com Errors
- `400` - Bad Request (fehlende Parameter)
- `401` - Unauthorized (falscher API Key)
- `404` - Event Type nicht gefunden
- `429` - Rate Limit exceeded

### 6.2 Retell Errors
- `401` - Invalid API Key
- `404` - Agent/Phone nicht gefunden
- `500` - Server Error (oft falscher API Key)

### 6.3 MCP Circuit Breaker States
- `CLOSED` - Normal operation
- `OPEN` - Service down (fail fast)
- `HALF_OPEN` - Testing recovery

## 7. Rate Limits & Caching

### 7.1 Cal.com
- Rate Limit: Unbekannt (keine 429 Errors erhalten)
- Cache TTL: 5 Minuten für Event Types & Availability

### 7.2 Retell
- Rate Limit: Unbekannt
- Cache TTL: 10 Minuten für Agents

### 7.3 MCP Internal
- Webhook Deduplication: 24 Stunden
- Circuit Breaker Timeout: 60 Sekunden
- Retry Attempts: 3 mit exponential backoff

## Zusammenfassung

Diese Dokumentation zeigt alle funktionierenden und nicht funktionierenden API-Kommunikationsmuster. Die wichtigsten Erkenntnisse:

1. **Immer v1 APIs verwenden** (keine v2)
2. **Team Events brauchen teamId Parameter**
3. **Metadata nur als Strings**
4. **Webhook Signature für Production erforderlich**
5. **Circuit Breaker schützt vor Ausfällen**