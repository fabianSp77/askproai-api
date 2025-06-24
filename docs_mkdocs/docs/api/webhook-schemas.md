# Webhook Schemas

## Overview

This document defines the webhook schemas for all external integrations in AskProAI. Each webhook includes signature verification for security.

## Retell.ai Webhooks

### Webhook Configuration
- **URL**: `https://api.askproai.de/api/retell/webhook`
- **Method**: `POST`
- **Authentication**: HMAC-SHA256 signature in `x-retell-signature` header

### call_started Event
```json
{
  "event_type": "call_started",
  "call_id": "call_abc123def456",
  "agent_id": "agent_789ghi012jkl",
  "from_number": "+49301234567",
  "to_number": "+49309876543",
  "direction": "inbound",
  "metadata": {
    "custom_field": "value"
  },
  "timestamp": "2025-06-23T10:30:00Z"
}
```

### call_ended Event
```json
{
  "event_type": "call_ended",
  "call_id": "call_abc123def456",
  "agent_id": "agent_789ghi012jkl",
  "from_number": "+49301234567",
  "to_number": "+49309876543",
  "direction": "inbound",
  "duration": 180,
  "end_reason": "user_hangup",
  "metadata": {
    "custom_field": "value"
  },
  "timestamp": "2025-06-23T10:33:00Z"
}
```

### call_analyzed Event
```json
{
  "event_type": "call_analyzed",
  "call_id": "call_abc123def456",
  "agent_id": "agent_789ghi012jkl",
  "transcript": "Agent: Guten Tag, wie kann ich Ihnen helfen?\nCaller: Ich möchte einen Termin buchen...",
  "recording_url": "https://storage.retellai.com/recordings/call_abc123def456.mp3",
  "call_summary": "Customer called to book an appointment for next Tuesday at 2 PM",
  "user_sentiment": "positive",
  "detected_language": "de-DE",
  "function_calls": [
    {
      "name": "book_appointment",
      "arguments": {
        "customer_name": "Max Mustermann",
        "phone_number": "+49301234567",
        "service_type": "Consultation",
        "preferred_date": "2025-06-30",
        "preferred_time": "14:00"
      },
      "result": "success"
    }
  ],
  "custom_analysis": {
    "intent": "appointment_booking",
    "urgency": "normal",
    "customer_type": "new"
  },
  "timestamp": "2025-06-23T10:35:00Z"
}
```

### Signature Verification
```php
// PHP Example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RETELL_SIGNATURE'];
$secret = env('RETELL_WEBHOOK_SECRET');

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

## Cal.com Webhooks

### Webhook Configuration
- **URL**: `https://api.askproai.de/api/webhooks/calcom`
- **Method**: `POST`
- **Authentication**: HMAC-SHA256 signature in `X-Cal-Signature-256` header

### BOOKING_CREATED Event
```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2025-06-23T10:00:00Z",
  "payload": {
    "id": 123456,
    "uid": "abc123def456ghi789",
    "idempotencyKey": "unique-key-123",
    "eventTypeId": 2026361,
    "title": "30 Min Consultation",
    "description": "Initial consultation appointment",
    "startTime": "2025-06-30T14:00:00Z",
    "endTime": "2025-06-30T14:30:00Z",
    "attendees": [
      {
        "email": "patient@example.com",
        "name": "Max Mustermann",
        "timeZone": "Europe/Berlin",
        "locale": "de"
      }
    ],
    "organizer": {
      "id": 789,
      "name": "Dr. Schmidt",
      "email": "dr.schmidt@clinic.de",
      "timeZone": "Europe/Berlin"
    },
    "location": {
      "type": "inPerson",
      "value": "Beispielstraße 123, 10115 Berlin"
    },
    "responses": {
      "name": "Max Mustermann",
      "email": "patient@example.com",
      "phone": "+49301234567",
      "notes": "First time patient, referred by Dr. Mueller"
    },
    "metadata": {
      "askproai_appointment_id": "456",
      "branch_id": "123",
      "source": "phone_booking"
    },
    "status": "ACCEPTED",
    "rescheduled": false,
    "cancellationReason": null
  }
}
```

### BOOKING_CANCELLED Event
```json
{
  "triggerEvent": "BOOKING_CANCELLED",
  "createdAt": "2025-06-23T11:00:00Z",
  "payload": {
    "id": 123456,
    "uid": "abc123def456ghi789",
    "title": "30 Min Consultation",
    "startTime": "2025-06-30T14:00:00Z",
    "endTime": "2025-06-30T14:30:00Z",
    "organizer": {
      "id": 789,
      "name": "Dr. Schmidt",
      "email": "dr.schmidt@clinic.de"
    },
    "attendees": [
      {
        "email": "patient@example.com",
        "name": "Max Mustermann"
      }
    ],
    "cancellationReason": "Patient requested cancellation",
    "cancelledBy": "attendee",
    "metadata": {
      "askproai_appointment_id": "456"
    }
  }
}
```

### BOOKING_RESCHEDULED Event
```json
{
  "triggerEvent": "BOOKING_RESCHEDULED",
  "createdAt": "2025-06-23T12:00:00Z",
  "payload": {
    "id": 123457,
    "uid": "new123def456ghi789",
    "previousUid": "abc123def456ghi789",
    "title": "30 Min Consultation",
    "startTime": "2025-07-01T15:00:00Z",
    "endTime": "2025-07-01T15:30:00Z",
    "previousStartTime": "2025-06-30T14:00:00Z",
    "previousEndTime": "2025-06-30T14:30:00Z",
    "organizer": {
      "id": 789,
      "name": "Dr. Schmidt",
      "email": "dr.schmidt@clinic.de"
    },
    "attendees": [
      {
        "email": "patient@example.com",
        "name": "Max Mustermann"
      }
    ],
    "rescheduledBy": "organizer",
    "rescheduledReason": "Doctor unavailable at original time",
    "metadata": {
      "askproai_appointment_id": "456"
    }
  }
}
```

### BOOKING_REQUESTED Event
```json
{
  "triggerEvent": "BOOKING_REQUESTED",
  "createdAt": "2025-06-23T13:00:00Z",
  "payload": {
    "id": 123458,
    "eventTypeId": 2026361,
    "title": "30 Min Consultation",
    "startTime": "2025-06-30T14:00:00Z",
    "endTime": "2025-06-30T14:30:00Z",
    "responses": {
      "name": "Max Mustermann",
      "email": "patient@example.com",
      "phone": "+49301234567"
    },
    "status": "PENDING",
    "requiresConfirmation": true
  }
}
```

## Stripe Webhooks

### Webhook Configuration
- **URL**: `https://api.askproai.de/stripe/webhook`
- **Method**: `POST`
- **Authentication**: Stripe signature in `stripe-signature` header

### customer.subscription.created
```json
{
  "id": "evt_1234567890",
  "object": "event",
  "api_version": "2023-10-16",
  "created": 1719140400,
  "data": {
    "object": {
      "id": "sub_1234567890",
      "object": "subscription",
      "customer": "cus_1234567890",
      "status": "active",
      "current_period_start": 1719140400,
      "current_period_end": 1721732400,
      "items": {
        "data": [
          {
            "id": "si_1234567890",
            "price": {
              "id": "price_1234567890",
              "product": "prod_1234567890",
              "unit_amount": 9900,
              "currency": "eur"
            },
            "quantity": 1
          }
        ]
      },
      "metadata": {
        "company_id": "123",
        "plan": "professional"
      }
    }
  },
  "type": "customer.subscription.created"
}
```

### invoice.payment_succeeded
```json
{
  "id": "evt_1234567890",
  "object": "event",
  "api_version": "2023-10-16",
  "created": 1719140400,
  "data": {
    "object": {
      "id": "in_1234567890",
      "object": "invoice",
      "amount_paid": 9900,
      "currency": "eur",
      "customer": "cus_1234567890",
      "subscription": "sub_1234567890",
      "status": "paid",
      "lines": {
        "data": [
          {
            "amount": 9900,
            "description": "Professional Plan",
            "period": {
              "start": 1719140400,
              "end": 1721732400
            }
          }
        ]
      },
      "metadata": {
        "company_id": "123"
      }
    }
  },
  "type": "invoice.payment_succeeded"
}
```

## WhatsApp Webhooks (Planned)

### Webhook Configuration
- **URL**: `https://api.askproai.de/api/webhooks/whatsapp`
- **Method**: `POST`
- **Authentication**: Facebook webhook verification

### Message Received
```json
{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "metadata": {
              "display_phone_number": "+49301234567",
              "phone_number_id": "PHONE_NUMBER_ID"
            },
            "contacts": [
              {
                "profile": {
                  "name": "Max Mustermann"
                },
                "wa_id": "491234567890"
              }
            ],
            "messages": [
              {
                "from": "491234567890",
                "id": "wamid.1234567890",
                "timestamp": "1719140400",
                "text": {
                  "body": "Ich möchte meinen Termin verschieben"
                },
                "type": "text"
              }
            ]
          },
          "field": "messages"
        }
      ]
    }
  ]
}
```

## SMS Webhooks

##***REMOVED*** Status Callback
```json
{
  "MessageSid": "SM1234567890abcdef",
  "MessageStatus": "delivered",
  "To": "+49301234567",
  "From": "+49309876543",
  "ApiVersion": "2010-04-01",
  "SmsSid": "SM1234567890abcdef",
  "SmsStatus": "delivered",
  "Body": "Appointment reminder for tomorrow at 2 PM",
  "NumSegments": "1",
  "Direction": "outbound-api",
  "Price": "-0.075",
  "PriceUnit": "EUR",
  "ErrorCode": null,
  "ErrorMessage": null
}
```

## Webhook Security Best Practices

### 1. Signature Verification
Always verify webhook signatures before processing:
```php
// Generic signature verification
function verifyWebhookSignature($payload, $signature, $secret) {
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}
```

### 2. Idempotency
Handle duplicate webhooks gracefully:
```php
// Store processed webhook IDs
$webhookId = $request->input('id') ?? $request->input('idempotencyKey');
if (WebhookEvent::where('external_id', $webhookId)->exists()) {
    return response()->json(['status' => 'already_processed']);
}
```

### 3. Timeout Handling
Respond quickly to avoid timeouts:
```php
// Queue processing for long operations
ProcessWebhook::dispatch($webhookData);
return response()->json(['status' => 'accepted'], 202);
```

### 4. Error Handling
Return appropriate status codes:
- `200 OK` - Successfully processed
- `202 Accepted` - Received and queued for processing
- `400 Bad Request` - Invalid payload
- `401 Unauthorized` - Invalid signature
- `500 Internal Server Error` - Processing failed

## Testing Webhooks

### Local Development
```bash
# Use ngrok for local webhook testing
ngrok http 8000

# Update webhook URL to ngrok URL
https://abc123.ngrok.io/api/webhooks/retell
```

### Webhook Testing Tools
```bash
# Test Retell webhook
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "x-retell-signature: $(echo -n '$BODY' | openssl dgst -sha256 -hmac '$SECRET' -binary | base64)" \
  -d '{
    "event_type": "call_ended",
    "call_id": "test_call_123"
  }'
```

## Related Documentation
- [Webhook Processing](webhooks.md)
- [API Authentication](authentication.md)
- [Integration Guides](../integrations/)