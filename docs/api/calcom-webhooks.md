# Cal.com Webhook API

AskPro API Gateway receives webhooks from Cal.com for booking events.

## Webhook Endpoint

```
POST /webhooks/calcom
```

## Authentication

Cal.com webhooks include an HMAC signature:

```
X-Cal-Signature-256: sha256=abc123...
```

### Verification

```php
$signature = $request->header('X-Cal-Signature-256');
$payload = $request->getContent();
$secret = config('services.calcom.webhook_secret');

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}
```

## Supported Events

| Event | Trigger |
|-------|---------|
| `BOOKING_CREATED` | New booking made |
| `BOOKING_RESCHEDULED` | Booking time changed |
| `BOOKING_CANCELLED` | Booking cancelled |
| `BOOKING_CONFIRMED` | Booking confirmed |
| `BOOKING_REJECTED` | Booking rejected by host |
| `BOOKING_REQUESTED` | Approval request (for approval-required events) |

## BOOKING_CREATED

Triggered when a new booking is created.

### Payload

```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2024-01-10T14:30:00.000Z",
  "payload": {
    "uid": "abc123-def456",
    "eventTypeId": 12345,
    "title": "30 Minute Meeting",
    "startTime": "2024-01-15T10:00:00.000Z",
    "endTime": "2024-01-15T10:30:00.000Z",
    "status": "ACCEPTED",
    "attendees": [
      {
        "email": "customer@example.com",
        "name": "Max Mustermann",
        "timeZone": "Europe/Berlin"
      }
    ],
    "organizer": {
      "email": "staff@company.com",
      "name": "Anna Schmidt",
      "timeZone": "Europe/Berlin"
    },
    "responses": {
      "name": "Max Mustermann",
      "email": "customer@example.com",
      "phone": "+491234567890",
      "notes": "Erstkontakt"
    },
    "metadata": {
      "askpro_appointment_id": null,
      "source": "calcom_direct"
    },
    "location": "Google Meet",
    "destinationCalendar": {
      "integration": "google_calendar",
      "externalId": "calendar123"
    }
  }
}
```

### Response

```json
{
  "status": "processed",
  "appointment_id": 789,
  "action": "created"
}
```

## BOOKING_RESCHEDULED

Triggered when a booking is rescheduled.

### Payload

```json
{
  "triggerEvent": "BOOKING_RESCHEDULED",
  "createdAt": "2024-01-11T09:00:00.000Z",
  "payload": {
    "uid": "abc123-def456",
    "rescheduleUid": "new-abc123",
    "previousStartTime": "2024-01-15T10:00:00.000Z",
    "previousEndTime": "2024-01-15T10:30:00.000Z",
    "startTime": "2024-01-16T14:00:00.000Z",
    "endTime": "2024-01-16T14:30:00.000Z",
    "rescheduleReason": "Konflikt mit anderem Termin",
    "rescheduledBy": "organizer"
  }
}
```

### Response

```json
{
  "status": "processed",
  "appointment_id": 789,
  "action": "rescheduled"
}
```

## BOOKING_CANCELLED

Triggered when a booking is cancelled.

### Payload

```json
{
  "triggerEvent": "BOOKING_CANCELLED",
  "createdAt": "2024-01-12T11:00:00.000Z",
  "payload": {
    "uid": "abc123-def456",
    "status": "CANCELLED",
    "cancellationReason": "Kunde hat abgesagt",
    "cancelledBy": "attendee"
  }
}
```

### Response

```json
{
  "status": "processed",
  "appointment_id": 789,
  "action": "cancelled"
}
```

## BOOKING_CONFIRMED

Triggered when a pending booking is confirmed.

### Payload

```json
{
  "triggerEvent": "BOOKING_CONFIRMED",
  "createdAt": "2024-01-10T15:00:00.000Z",
  "payload": {
    "uid": "abc123-def456",
    "status": "ACCEPTED",
    "confirmedAt": "2024-01-10T15:00:00.000Z"
  }
}
```

## Handling Logic

### External vs Internal Bookings

```php
public function handleBookingCreated(array $payload): JsonResponse
{
    // Check if this booking originated from AskPro
    if (isset($payload['metadata']['askpro_appointment_id'])) {
        // Internal booking - already exists in our system
        return response()->json([
            'status' => 'skipped',
            'reason' => 'Internal booking already synced'
        ]);
    }

    // External booking - create in AskPro
    $appointment = $this->createFromCalcom($payload);

    return response()->json([
        'status' => 'created',
        'appointment_id' => $appointment->id
    ]);
}
```

### Conflict Resolution

```php
public function handleReschedule(array $payload): JsonResponse
{
    $appointment = Appointment::where('calcom_booking_uid', $payload['uid'])->first();

    if (!$appointment) {
        return response()->json(['status' => 'not_found'], 404);
    }

    // Check for conflicts
    $conflict = Appointment::where('staff_id', $appointment->staff_id)
        ->where('id', '!=', $appointment->id)
        ->where('start_time', Carbon::parse($payload['startTime']))
        ->exists();

    if ($conflict) {
        Log::warning('Cal.com reschedule conflict', [
            'appointment_id' => $appointment->id,
            'new_time' => $payload['startTime']
        ]);
    }

    $appointment->update([
        'start_time' => Carbon::parse($payload['startTime']),
        'end_time' => Carbon::parse($payload['endTime']),
        'reschedule_reason' => $payload['rescheduleReason'] ?? null,
    ]);

    return response()->json([
        'status' => 'processed',
        'action' => 'rescheduled'
    ]);
}
```

## Metadata Handling

### Setting Metadata

When creating bookings from AskPro:

```php
$response = Http::withToken($apiKey)
    ->post('https://api.cal.com/v2/bookings', [
        'eventTypeId' => $eventTypeId,
        'start' => $startTime,
        'responses' => [...],
        'metadata' => [
            'askpro_appointment_id' => $appointment->id,
            'source' => 'retell_ai',
            'company_id' => $appointment->company_id,
        ],
    ]);
```

### Reading Metadata

```php
// In webhook handler
$askproId = $payload['metadata']['askpro_appointment_id'] ?? null;
$source = $payload['metadata']['source'] ?? 'calcom_direct';
```

## Error Handling

### Common Scenarios

| Scenario | Response | Action |
|----------|----------|--------|
| Booking not found | 404 | Log warning, return not_found |
| Invalid signature | 401 | Reject request |
| Validation error | 422 | Return validation errors |
| Processing error | 500 | Log error, retry later |

### Error Response Format

```json
{
  "status": "error",
  "error": {
    "code": "booking_not_found",
    "message": "No appointment found with Cal.com UID: abc123"
  }
}
```

## Testing

### Webhook Simulator

Cal.com provides a webhook simulator in the dashboard:

1. Go to Settings → Webhooks
2. Select your webhook
3. Click "Test Webhook"
4. Choose event type
5. Send test payload

### Manual Testing

```bash
curl -X POST "https://api.askproai.de/webhooks/calcom" \
  -H "Content-Type: application/json" \
  -H "X-Cal-Signature-256: sha256=test" \
  -d '{
    "triggerEvent": "BOOKING_CREATED",
    "payload": {
      "uid": "test-123",
      "title": "Test Booking",
      "startTime": "2024-01-15T10:00:00.000Z",
      "endTime": "2024-01-15T10:30:00.000Z"
    }
  }'
```

## Webhook Configuration

### Setting Up in Cal.com

1. Go to Settings → Developer → Webhooks
2. Click "New Webhook"
3. Enter URL: `https://api.askproai.de/webhooks/calcom`
4. Select events to subscribe
5. Copy webhook secret
6. Add secret to AskPro configuration

### Recommended Events

- ✅ BOOKING_CREATED
- ✅ BOOKING_RESCHEDULED
- ✅ BOOKING_CANCELLED
- ✅ BOOKING_CONFIRMED
- ⬜ BOOKING_REJECTED (optional)
- ⬜ BOOKING_REQUESTED (for approval workflows)

## Retry Policy

Cal.com retries failed webhooks:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 5 minutes |
| 3 | 30 minutes |
| 4 | 2 hours |
| 5 | 24 hours |

Return HTTP 200-299 to acknowledge. Other status codes trigger retry.
