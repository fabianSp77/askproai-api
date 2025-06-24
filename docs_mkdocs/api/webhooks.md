# Webhooks

## Overview

Webhooks enable real-time notifications when events occur in the AskProAI system. Instead of polling for updates, your application receives HTTP POST requests with event data.

## Webhook Security

### Signature Verification

All webhook requests include a signature header for verification:

```http
POST /your-webhook-endpoint
X-Webhook-Signature: sha256=3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c
Content-Type: application/json
```

#### Verifying Signatures (PHP)

```php
function verifyWebhookSignature($payload, $signature, $secret) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

// In your webhook handler
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = env('WEBHOOK_SECRET');

if (!verifyWebhookSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

#### Verifying Signatures (Node.js)

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
    const expected = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    return crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expected)
    );
}
```

## Event Types

### Appointment Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `appointment.created` | New appointment booked | Via API, phone, or web |
| `appointment.updated` | Appointment details changed | Time, service, or staff change |
| `appointment.confirmed` | Customer confirmed appointment | Confirmation received |
| `appointment.cancelled` | Appointment cancelled | By customer or staff |
| `appointment.completed` | Appointment marked complete | Staff action |
| `appointment.no_show` | Customer didn't attend | Marked by staff |
| `appointment.reminder_sent` | Reminder notification sent | Automated reminder |

### Call Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `call.started` | Phone call initiated | Incoming call |
| `call.ended` | Phone call completed | Call hangup |
| `call.analyzed` | AI analysis complete | Post-call processing |
| `call.appointment_created` | Appointment booked via phone | Successful booking |
| `call.failed` | Call failed to connect | Technical issue |

### Customer Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `customer.created` | New customer added | First appointment |
| `customer.updated` | Customer info updated | Profile change |
| `customer.merged` | Duplicate customers merged | Deduplication |

### Payment Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `payment.succeeded` | Payment processed | Successful charge |
| `payment.failed` | Payment failed | Declined card |
| `payment.refunded` | Payment refunded | Refund processed |

## Webhook Payload Structure

### Standard Payload Format

```json
{
  "id": "evt_1234567890",
  "type": "appointment.created",
  "created": "2025-06-23T10:30:00Z",
  "data": {
    // Event-specific data
  },
  "metadata": {
    "company_id": 1,
    "branch_id": 3,
    "api_version": "v2",
    "idempotency_key": "550e8400-e29b-41d4"
  }
}
```

### Example: Appointment Created

```json
{
  "id": "evt_abc123",
  "type": "appointment.created",
  "created": "2025-06-23T10:30:00Z",
  "data": {
    "appointment": {
      "id": 12345,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "status": "confirmed",
      "start_time": "2025-06-24T14:00:00Z",
      "end_time": "2025-06-24T14:30:00Z",
      "service": {
        "id": 1,
        "name": "Haircut",
        "duration": 30,
        "price": 35.00
      },
      "staff": {
        "id": 5,
        "name": "John Doe"
      },
      "customer": {
        "id": 789,
        "name": "Jane Smith",
        "phone": "+49 30 123456",
        "email": "jane@example.com"
      },
      "branch": {
        "id": 3,
        "name": "Berlin Central"
      },
      "source": "phone",
      "notes": "Prefers short haircut"
    }
  },
  "metadata": {
    "company_id": 1,
    "branch_id": 3,
    "api_version": "v2",
    "idempotency_key": "550e8400-e29b-41d4"
  }
}
```

### Example: Call Ended

```json
{
  "id": "evt_xyz789",
  "type": "call.ended",
  "created": "2025-06-23T10:35:00Z",
  "data": {
    "call": {
      "id": "call_123456",
      "phone_number": "+49 30 987654",
      "duration": 245,
      "status": "completed",
      "direction": "inbound",
      "ai_agent": "agent_abc123",
      "recording_url": "https://secure.retellai.com/recordings/...",
      "transcript_url": "https://secure.retellai.com/transcripts/...",
      "summary": "Customer wanted to book appointment for next week",
      "appointment_created": true,
      "appointment_id": 12345,
      "sentiment": "positive",
      "keywords": ["haircut", "tuesday", "morning"]
    }
  },
  "metadata": {
    "company_id": 1,
    "api_version": "v2"
  }
}
```

## Webhook Configuration

### Registering a Webhook Endpoint

```http
POST /api/v2/webhooks
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "url": "https://your-app.com/webhooks/askproai",
  "events": [
    "appointment.created",
    "appointment.cancelled",
    "call.ended"
  ],
  "description": "Production webhook",
  "active": true
}
```

### Listing Webhook Endpoints

```http
GET /api/v2/webhooks
```

### Updating a Webhook

```http
PUT /api/v2/webhooks/{id}
{
  "events": [
    "appointment.created",
    "appointment.updated",
    "appointment.cancelled"
  ]
}
```

### Deleting a Webhook

```http
DELETE /api/v2/webhooks/{id}
```

## Webhook Delivery

### Retry Policy

Failed webhook deliveries are retried with exponential backoff:

1. Immediate
2. 1 minute
3. 5 minutes
4. 30 minutes
5. 2 hours
6. 6 hours
7. 24 hours

After 7 failed attempts, the webhook is marked as failed.

### Timeout

Webhook requests timeout after 30 seconds. Your endpoint should:
1. Respond quickly (< 5 seconds)
2. Process asynchronously if needed
3. Return 2xx status code

### Expected Response

Your webhook endpoint should return:

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "received": true
}
```

## Idempotency

Webhooks may be delivered multiple times. Use the `idempotency_key` to handle duplicates:

```php
function handleWebhook($payload) {
    $key = $payload['metadata']['idempotency_key'];
    
    if (Cache::has("webhook_processed_{$key}")) {
        return response()->json(['received' => true]);
    }
    
    // Process webhook
    processWebhookData($payload);
    
    // Mark as processed (cache for 24 hours)
    Cache::put("webhook_processed_{$key}", true, 86400);
    
    return response()->json(['received' => true]);
}
```

## Testing Webhooks

### Test Endpoint

Send test webhooks to verify your implementation:

```http
POST /api/v2/webhooks/test
{
  "url": "https://your-app.com/webhooks/test",
  "event": "appointment.created",
  "sample_data": true
}
```

### Using ngrok for Local Development

```bash
# Start ngrok
ngrok http 3000

# Register the ngrok URL
POST /api/v2/webhooks
{
  "url": "https://abc123.ngrok.io/webhooks",
  "events": ["appointment.created"]
}
```

### Webhook Logs

View webhook delivery logs:

```http
GET /api/v2/webhooks/{id}/logs
```

Response:
```json
{
  "logs": [
    {
      "id": "log_123",
      "delivered_at": "2025-06-23T10:30:15Z",
      "status": "success",
      "status_code": 200,
      "response_time": 245,
      "attempts": 1
    }
  ]
}
```

## Best Practices

### 1. Verify Signatures Always

```php
// Bad
$data = json_decode(file_get_contents('php://input'), true);
processWebhook($data);

// Good
$payload = file_get_contents('php://input');
if (!verifySignature($payload, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])) {
    http_response_code(401);
    exit();
}
$data = json_decode($payload, true);
processWebhook($data);
```

### 2. Respond Quickly

```php
// Bad - Slow processing
function handleWebhook($data) {
    // Long running process
    sendEmailToCustomer($data);
    updateInventory($data);
    generateReport($data);
    
    return response()->json(['received' => true]);
}

// Good - Queue for processing
function handleWebhook($data) {
    // Quick validation
    if (!isValidPayload($data)) {
        return response()->json(['error' => 'Invalid'], 400);
    }
    
    // Queue for async processing
    ProcessWebhookJob::dispatch($data);
    
    return response()->json(['received' => true]);
}
```

### 3. Handle Failures Gracefully

```javascript
app.post('/webhook', async (req, res) => {
  try {
    await processWebhook(req.body);
    res.json({ received: true });
  } catch (error) {
    console.error('Webhook processing failed:', error);
    
    // Still return 200 to prevent retries if it's our fault
    res.json({ received: true, error: error.message });
    
    // Queue for manual review
    await queueFailedWebhook(req.body, error);
  }
});
```

### 4. Monitor Webhook Health

Set up monitoring for:
- Failed deliveries
- High latency responses
- Signature verification failures
- Missing webhook events

## Troubleshooting

### Webhooks Not Being Received

1. Verify endpoint is accessible
2. Check firewall rules
3. Verify SSL certificate
4. Check webhook logs in admin panel

### Signature Verification Failing

1. Ensure using raw payload (not parsed JSON)
2. Check secret key is correct
3. Verify encoding matches

### Duplicate Webhooks

1. Implement idempotency handling
2. Check for network issues causing retries
3. Verify response is 2xx status

### Missing Events

1. Check webhook subscription includes event type
2. Verify webhook is active
3. Check for delivery failures in logs