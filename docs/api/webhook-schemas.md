# Webhook Payload Schema Documentation

*Last Updated: June 23, 2025*

## Overview

This document details the webhook payload schemas for all incoming webhooks to the AskProAI platform. Each webhook must be signed and verified before processing.

## Webhook Endpoints

| Provider | Endpoint | Signature Header | Verification Method |
|----------|----------|------------------|---------------------|
| Retell.ai | `/api/retell/webhook` | `X-Retell-Signature` | HMAC-SHA256 |
| Cal.com | `/api/calcom/webhook` | `X-Cal-Signature` | HMAC-SHA256 |
| Stripe | `/api/stripe/webhook` | `Stripe-Signature` | Stripe SDK |
| Unified | `/api/webhook` | Provider-specific | Auto-detected |

## Retell.ai Webhooks

### Common Headers
```http
X-Retell-Signature: sha256=f7bc83f430538424b13298e6aa6fb143ef4d59a14946175997479dbc2d1a3cd8
Content-Type: application/json
```

### Event: `call_started`
Fired when a phone call begins.

```json
{
  "event": "call_started",
  "call": {
    "call_id": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p",
    "from_number": "+4915123456789",
    "to_number": "+493083793369",
    "direction": "inbound",
    "agent_id": "agent_abc123def456",
    "start_timestamp": 1703244123456
  },
  "timestamp": 1703244123456
}
```

### Event: `call_ended`
Fired when a phone call completes.

```json
{
  "event": "call_ended",
  "call": {
    "call_id": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p",
    "from_number": "+4915123456789",
    "to_number": "+493083793369",
    "direction": "inbound",
    "agent_id": "agent_abc123def456",
    "start_timestamp": 1703244123456,
    "end_timestamp": 1703244423456,
    "duration": 300,
    "status": "completed",
    "end_reason": "agent_ended",
    "recording_url": "https://storage.retellai.com/recordings/call_1a2b3c4d.mp3",
    "transcript": "Agent: Guten Tag, AskProAI, wie kann ich Ihnen helfen?\nKunde: Hallo, ich möchte gerne einen Termin vereinbaren...",
    "summary": "Kunde möchte Termin für Haarschnitt am 15.04. um 14:30 Uhr",
    "custom_data": {
      "collect_appointment_data": {
        "datum": "15.04.2025",
        "uhrzeit": "14:30",
        "dienstleistung": "Herrenhaarschnitt",
        "name": "Max Mustermann",
        "telefonnummer": "+4915123456789",
        "email": "max@example.com",
        "mitarbeiter_wunsch": "Marie",
        "kundenpraeferenzen": "Kurzer Schnitt, moderne Frisur"
      }
    },
    "metadata": {
      "sentiment": "positive",
      "language": "de",
      "intent": "appointment_booking"
    }
  },
  "timestamp": 1703244423456
}
```

### Event: `call_analyzed`
Fired after call analysis is complete.

```json
{
  "event": "call_analyzed",
  "call": {
    "call_id": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p",
    "analysis": {
      "sentiment_score": 0.85,
      "keywords": ["termin", "haarschnitt", "morgen", "14:30"],
      "intents": ["book_appointment"],
      "entities": {
        "service": "Herrenhaarschnitt",
        "date": "2025-04-15",
        "time": "14:30",
        "staff_preference": "Marie"
      },
      "success": true,
      "appointment_booked": true
    }
  },
  "timestamp": 1703244523456
}
```

### Event: `function_call`
Real-time function calls during conversation.

```json
{
  "event": "function_call",
  "call_id": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p",
  "function_name": "check_availability",
  "arguments": {
    "date": "2025-04-15",
    "time": "14:30",
    "service": "Herrenhaarschnitt",
    "duration": 30,
    "staff": "Marie"
  },
  "request_id": "req_xyz789",
  "timestamp": 1703244223456
}
```

**Expected Response:**
```json
{
  "request_id": "req_xyz789",
  "result": {
    "available": true,
    "slots": [
      {
        "start": "2025-04-15T14:30:00+02:00",
        "end": "2025-04-15T15:00:00+02:00",
        "staff": "Marie"
      }
    ],
    "message": "Der Termin ist verfügbar"
  }
}
```

## Cal.com Webhooks

### Common Headers
```http
X-Cal-Signature: sha256=9b4e8f5c3a2d1e0f9876543210abcdef1234567890fedcba9876543210123456
X-Cal-Timestamp: 1703244123
Content-Type: application/json
```

### Event: `booking.created`
New booking created in Cal.com.

```json
{
  "triggerEvent": "booking.created",
  "createdAt": "2025-04-14T10:30:00.000Z",
  "payload": {
    "id": 2026361,
    "uid": "bkg_abc123def456ghi789",
    "title": "Herrenhaarschnitt mit Max Mustermann",
    "description": "Kurzer Schnitt, moderne Frisur",
    "startTime": "2025-04-15T12:30:00.000Z",
    "endTime": "2025-04-15T13:00:00.000Z",
    "organizer": {
      "id": 12345,
      "name": "Marie Schmidt",
      "email": "marie@salon.de",
      "timeZone": "Europe/Berlin"
    },
    "attendees": [
      {
        "email": "max@example.com",
        "name": "Max Mustermann",
        "timeZone": "Europe/Berlin",
        "locale": "de"
      }
    ],
    "location": {
      "type": "inPerson",
      "address": "Hauptstraße 123, 10115 Berlin"
    },
    "destinationCalendar": {
      "id": 54321,
      "integration": "google_calendar",
      "externalId": "marie@salon.de"
    },
    "eventType": {
      "id": 7890,
      "title": "Herrenhaarschnitt",
      "slug": "herrenhaarschnitt",
      "duration": 30,
      "price": 35
    },
    "metadata": {
      "source": "phone_ai",
      "call_id": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p",
      "customer_phone": "+4915123456789"
    },
    "status": "ACCEPTED",
    "responses": {
      "phone": "+4915123456789",
      "notes": "Kurzer Schnitt, moderne Frisur"
    }
  }
}
```

### Event: `booking.cancelled`
Booking cancelled in Cal.com.

```json
{
  "triggerEvent": "booking.cancelled",
  "createdAt": "2025-04-14T15:00:00.000Z",
  "payload": {
    "id": 2026361,
    "uid": "bkg_abc123def456ghi789",
    "title": "Herrenhaarschnitt mit Max Mustermann",
    "startTime": "2025-04-15T12:30:00.000Z",
    "endTime": "2025-04-15T13:00:00.000Z",
    "status": "CANCELLED",
    "cancellationReason": "Customer requested cancellation",
    "cancelledBy": "attendee",
    "organizer": {
      "id": 12345,
      "name": "Marie Schmidt",
      "email": "marie@salon.de"
    },
    "attendees": [
      {
        "email": "max@example.com",
        "name": "Max Mustermann"
      }
    ]
  }
}
```

### Event: `booking.rescheduled`
Booking time changed in Cal.com.

```json
{
  "triggerEvent": "booking.rescheduled",
  "createdAt": "2025-04-14T16:00:00.000Z",
  "payload": {
    "id": 2026361,
    "uid": "bkg_abc123def456ghi789",
    "title": "Herrenhaarschnitt mit Max Mustermann",
    "previousStartTime": "2025-04-15T12:30:00.000Z",
    "previousEndTime": "2025-04-15T13:00:00.000Z",
    "startTime": "2025-04-15T15:00:00.000Z",
    "endTime": "2025-04-15T15:30:00.000Z",
    "rescheduledBy": "organizer",
    "reschedulingReason": "Staff availability change",
    "organizer": {
      "id": 12345,
      "name": "Marie Schmidt",
      "email": "marie@salon.de"
    },
    "attendees": [
      {
        "email": "max@example.com",
        "name": "Max Mustermann"
      }
    ]
  }
}
```

### Event: `event_type.created`
New event type created in Cal.com.

```json
{
  "triggerEvent": "event_type.created",
  "createdAt": "2025-04-14T09:00:00.000Z",
  "payload": {
    "id": 7891,
    "title": "Premium Haarstyling",
    "slug": "premium-haarstyling",
    "description": "Inkl. Waschen, Schneiden, Föhnen und Styling",
    "duration": 60,
    "hidden": false,
    "price": 65,
    "currency": "EUR",
    "locations": [
      {
        "type": "inPerson",
        "address": "Hauptstraße 123, 10115 Berlin"
      }
    ],
    "teamId": 123,
    "userId": 12345,
    "scheduleId": 456,
    "metadata": {
      "category": "premium_services"
    }
  }
}
```

## Stripe Webhooks

### Common Headers
```http
Stripe-Signature: t=1703244123,v1=abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
Content-Type: application/json; charset=utf-8
```

### Event: `invoice.payment_succeeded`
Successful payment received.

```json
{
  "id": "evt_1234567890abcdef",
  "object": "event",
  "api_version": "2023-10-16",
  "created": 1703244123,
  "type": "invoice.payment_succeeded",
  "data": {
    "object": {
      "id": "in_1234567890abcdef",
      "object": "invoice",
      "amount_paid": 9900,
      "currency": "eur",
      "customer": "cus_1234567890abcdef",
      "customer_email": "kunde@example.com",
      "customer_name": "AskProAI Berlin",
      "lines": {
        "data": [
          {
            "description": "AskProAI Pro Plan - Monthly",
            "amount": 9900,
            "quantity": 1,
            "period": {
              "start": 1703244123,
              "end": 1705922523
            }
          }
        ]
      },
      "metadata": {
        "company_id": "uuid-1234-5678-90ab-cdef",
        "plan": "pro",
        "billing_period": "monthly"
      },
      "subscription": "sub_1234567890abcdef",
      "status": "paid",
      "paid_at": 1703244123
    }
  },
  "livemode": true,
  "pending_webhooks": 1,
  "request": {
    "id": null,
    "idempotency_key": null
  }
}
```

### Event: `customer.subscription.created`
New subscription created.

```json
{
  "id": "evt_2345678901bcdefg",
  "object": "event",
  "type": "customer.subscription.created",
  "created": 1703244123,
  "data": {
    "object": {
      "id": "sub_1234567890abcdef",
      "object": "subscription",
      "customer": "cus_1234567890abcdef",
      "status": "active",
      "current_period_start": 1703244123,
      "current_period_end": 1705922523,
      "items": {
        "data": [
          {
            "id": "si_1234567890abcdef",
            "price": {
              "id": "price_1234567890abcdef",
              "product": "prod_askproai_pro",
              "unit_amount": 9900,
              "currency": "eur",
              "recurring": {
                "interval": "month",
                "interval_count": 1
              }
            },
            "quantity": 1
          }
        ]
      },
      "metadata": {
        "company_id": "uuid-1234-5678-90ab-cdef",
        "features": "unlimited_calls,advanced_analytics,priority_support"
      },
      "trial_end": null,
      "cancel_at_period_end": false
    }
  }
}
```

### Event: `invoice.payment_failed`
Payment failure notification.

```json
{
  "id": "evt_3456789012cdefgh",
  "object": "event",
  "type": "invoice.payment_failed",
  "created": 1703244123,
  "data": {
    "object": {
      "id": "in_2345678901bcdefg",
      "object": "invoice",
      "amount_due": 9900,
      "currency": "eur",
      "customer": "cus_1234567890abcdef",
      "attempt_count": 1,
      "next_payment_attempt": 1703330523,
      "status": "open",
      "last_payment_error": {
        "code": "card_declined",
        "decline_code": "insufficient_funds",
        "message": "Your card has insufficient funds."
      },
      "metadata": {
        "company_id": "uuid-1234-5678-90ab-cdef"
      }
    }
  }
}
```

## Webhook Security

### Signature Verification

All webhooks must be verified before processing:

```php
// Retell.ai Signature Verification
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RETELL_SIGNATURE'];
$expected = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);

if (!hash_equals($expected, $signature)) {
    throw new WebhookSignatureException('Invalid signature');
}
```

### Idempotency

All webhook handlers must be idempotent:

```php
// Check if event was already processed
$event = WebhookEvent::where('event_id', $payload['id'])
    ->where('provider', 'retell')
    ->first();

if ($event && $event->processed_at) {
    return response()->json(['message' => 'Event already processed']);
}
```

### Retry Policy

Failed webhooks are retried with exponential backoff:

| Attempt | Delay | Total Time |
|---------|-------|------------|
| 1 | Immediate | 0s |
| 2 | 10s | 10s |
| 3 | 30s | 40s |
| 4 | 1m | 1m 40s |
| 5 | 5m | 6m 40s |
| 6 | 15m | 21m 40s |

### Error Responses

Webhook handlers should return appropriate HTTP status codes:

| Status | Meaning | Action |
|--------|---------|--------|
| 200 | Success | No retry |
| 202 | Accepted (processing) | No retry |
| 400 | Bad request | No retry |
| 401 | Unauthorized | No retry |
| 409 | Duplicate | No retry |
| 429 | Rate limited | Retry with backoff |
| 500 | Server error | Retry |
| 503 | Service unavailable | Retry |

## Testing Webhooks

### Test Endpoints

Development webhook testing endpoints:

```bash
# Test Retell webhook
curl -X POST http://localhost:8000/api/retell/webhook \
  -H "X-Retell-Signature: sha256=test_signature" \
  -H "Content-Type: application/json" \
  -d @test-webhook-retell.json

# Test Cal.com webhook
curl -X POST http://localhost:8000/api/calcom/webhook \
  -H "X-Cal-Signature: sha256=test_signature" \
  -H "Content-Type: application/json" \
  -d @test-webhook-calcom.json
```

### Webhook Simulators

Use these tools to simulate webhooks:
- Retell.ai: Dashboard → Webhooks → Test
- Cal.com: Settings → Webhooks → Send Test
- Stripe: Dashboard → Webhooks → Send test webhook

---

*For implementation details, see webhook controllers in `/app/Http/Controllers/`*