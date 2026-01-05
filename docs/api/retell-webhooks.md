# Retell.ai Webhook API

AskPro API Gateway receives webhooks from Retell.ai for voice call events.

## Webhook Endpoints

| Event | Endpoint | Method |
|-------|----------|--------|
| Call Started | `/webhooks/retell/call-started` | POST |
| Call Ended | `/webhooks/retell/call-ended` | POST |
| Call Analyzed | `/webhooks/retell/call-analyzed` | POST |
| Function Call | `/webhooks/retell/function-call` | POST |

## Authentication

All Retell webhooks include an HMAC signature:

```
X-Retell-Signature: sha256=abc123...
```

### Verification

```php
$signature = $request->header('X-Retell-Signature');
$payload = $request->getContent();
$secret = config('services.retell.webhook_secret');

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}
```

## Call Started Event

Triggered when a new voice call begins.

### Payload

```json
{
  "event": "call_started",
  "call": {
    "call_id": "call_abc123",
    "agent_id": "agent_xyz789",
    "call_type": "inbound",
    "from_number": "+491234567890",
    "to_number": "+490987654321",
    "start_timestamp": 1699900000000,
    "metadata": {
      "company_id": "1"
    }
  }
}
```

### Response

```json
{
  "status": "received",
  "session_id": "sess_123"
}
```

## Call Ended Event

Triggered when a voice call completes.

### Payload

```json
{
  "event": "call_ended",
  "call": {
    "call_id": "call_abc123",
    "agent_id": "agent_xyz789",
    "call_status": "ended",
    "disconnection_reason": "user_hangup",
    "start_timestamp": 1699900000000,
    "end_timestamp": 1699900300000,
    "duration_ms": 300000,
    "transcript": "Guten Tag, ich möchte einen Termin...",
    "recording_url": "https://storage.retellai.com/recordings/abc123.mp3",
    "call_analysis": {
      "call_successful": true,
      "appointment_booked": true,
      "customer_sentiment": "positive"
    }
  }
}
```

### Response

```json
{
  "status": "processed",
  "call_record_id": 456
}
```

## Call Analyzed Event

Triggered when post-call analysis is complete (async).

### Payload

```json
{
  "event": "call_analyzed",
  "call": {
    "call_id": "call_abc123",
    "analysis": {
      "summary": "Customer called to book an appointment for next Tuesday.",
      "action_items": [
        "Appointment booked for 2024-01-15 10:00"
      ],
      "customer_intent": "appointment_booking",
      "sentiment_score": 0.85,
      "topics": ["appointment", "availability", "confirmation"]
    }
  }
}
```

## Function Call Event

Triggered when the AI agent needs to execute a function.

### Payload

```json
{
  "event": "function_call",
  "call_id": "call_abc123",
  "function_name": "check_availability",
  "arguments": {
    "staff_id": 1,
    "date": "2024-01-15",
    "duration": 30
  }
}
```

### Response

Return the function result:

```json
{
  "result": {
    "available_slots": [
      {"time": "09:00", "display": "09:00 Uhr"},
      {"time": "10:00", "display": "10:00 Uhr"},
      {"time": "14:00", "display": "14:00 Uhr"}
    ]
  }
}
```

## Available Functions

### check_availability

Check available appointment slots.

**Arguments:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| staff_id | integer | Yes | Staff member ID |
| date | string | Yes | Date (YYYY-MM-DD) |
| duration | integer | No | Duration in minutes (default: 30) |

**Response:**
```json
{
  "available_slots": [
    {"time": "09:00", "display": "09:00 Uhr"}
  ]
}
```

### collect_appointment_info

Collect and validate customer information.

**Arguments:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| customer_name | string | Yes | Full name |
| customer_phone | string | Yes | Phone number |
| customer_email | string | No | Email address |
| service_id | integer | No | Requested service |

**Response:**
```json
{
  "valid": true,
  "customer_id": 123,
  "message": "Information erfasst"
}
```

### book_appointment

Create a new appointment.

**Arguments:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| customer_id | integer | Yes | Customer ID |
| staff_id | integer | Yes | Staff member ID |
| service_id | integer | Yes | Service ID |
| start_time | string | Yes | ISO 8601 datetime |

**Response:**
```json
{
  "success": true,
  "appointment_id": 456,
  "confirmation": "Termin gebucht für 15.01.2024 um 10:00 Uhr"
}
```

### reschedule_appointment

Reschedule an existing appointment.

**Arguments:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| appointment_id | integer | Yes | Appointment ID |
| new_start_time | string | Yes | New ISO 8601 datetime |
| reason | string | No | Reschedule reason |

**Response:**
```json
{
  "success": true,
  "message": "Termin verschoben auf 16.01.2024 um 14:00 Uhr"
}
```

### cancel_appointment

Cancel an appointment.

**Arguments:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| appointment_id | integer | Yes | Appointment ID |
| reason | string | No | Cancellation reason |

**Response:**
```json
{
  "success": true,
  "message": "Termin wurde storniert"
}
```

## Error Handling

### Function Errors

Return errors in a structured format:

```json
{
  "error": true,
  "error_code": "slot_unavailable",
  "message": "Der gewünschte Termin ist leider nicht mehr verfügbar."
}
```

### Common Error Codes

| Code | Description |
|------|-------------|
| `slot_unavailable` | Requested time slot is taken |
| `invalid_date` | Date format invalid or in past |
| `staff_not_found` | Staff member doesn't exist |
| `service_not_found` | Service doesn't exist |
| `customer_not_found` | Customer not found |
| `validation_error` | Input validation failed |

## Testing

### Local Development

Use ngrok for local webhook testing:

```bash
ngrok http 8000
# Update Retell dashboard with ngrok URL
```

### Test Webhook

```bash
curl -X POST "https://api.askproai.de/webhooks/retell/call-ended" \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: sha256=test" \
  -d '{
    "event": "call_ended",
    "call": {
      "call_id": "test_123",
      "call_status": "ended"
    }
  }'
```

## Retry Policy

Retell retries failed webhooks:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 5 seconds |
| 3 | 30 seconds |
| 4 | 2 minutes |
| 5 | 10 minutes |

Return HTTP 200 to acknowledge receipt. Any other status triggers retry.
