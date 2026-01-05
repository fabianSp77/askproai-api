# Retell.ai Integration

AskPro API Gateway integrates with Retell.ai for AI-powered voice assistants that handle appointment booking and customer service.

## Overview

Retell.ai provides:
- Natural language voice conversations
- Real-time speech-to-text and text-to-speech
- Custom function calling for business logic
- Multi-language support (German primary)

## Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Phone Call    │────▶│   Retell.ai     │────▶│  AskPro API     │
│   (Customer)    │     │   (Voice AI)    │     │  (Webhooks)     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                               │                        │
                               │ Function Calls         │
                               ▼                        ▼
                        ┌─────────────────┐     ┌─────────────────┐
                        │ collect_info    │     │ Cal.com API     │
                        │ check_avail     │     │ (Scheduling)    │
                        │ book_appt       │     └─────────────────┘
                        └─────────────────┘
```

## Configuration

### Environment Variables

```env
RETELL_API_KEY=your_retell_api_key
RETELL_WEBHOOK_SECRET=your_webhook_secret
RETELL_AGENT_ID=agent_xxxxx
```

### Company Settings

Each company can have custom Retell configuration:

| Setting | Description |
|---------|-------------|
| `retell_agent_id` | Company-specific agent |
| `retell_llm_id` | Custom LLM configuration |
| `voice_language` | Primary language (de-DE) |
| `fallback_behavior` | What to do on AI failure |

## Webhooks

### Call Events

```php
// routes/api.php
Route::prefix('webhooks/retell')->group(function () {
    Route::post('/call-started', [RetellWebhookController::class, 'callStarted']);
    Route::post('/call-ended', [RetellWebhookController::class, 'callEnded']);
    Route::post('/call-analyzed', [RetellWebhookController::class, 'callAnalyzed']);
});
```

### Webhook Payload

```json
{
  "event": "call_ended",
  "call": {
    "call_id": "call_xxxxx",
    "agent_id": "agent_xxxxx",
    "call_status": "ended",
    "start_timestamp": 1699900000000,
    "end_timestamp": 1699900300000,
    "transcript": "..."
  }
}
```

### Signature Verification

```php
// app/Http/Middleware/VerifyRetellWebhookSignature.php
public function handle($request, $next)
{
    $signature = $request->header('X-Retell-Signature');
    $payload = $request->getContent();

    $expected = hash_hmac('sha256', $payload, config('services.retell.webhook_secret'));

    if (!hash_equals($expected, $signature)) {
        abort(401, 'Invalid signature');
    }

    return $next($request);
}
```

## Function Calls

### Available Functions

| Function | Purpose |
|----------|---------|
| `collect_appointment_info` | Gather customer details |
| `check_availability` | Query Cal.com for slots |
| `book_appointment` | Create the booking |
| `reschedule_appointment` | Modify existing booking |
| `cancel_appointment` | Cancel booking |

### Function Call Handler

```php
// app/Http/Controllers/RetellFunctionCallHandler.php
public function handle(Request $request)
{
    $functionName = $request->input('function_name');
    $arguments = $request->input('arguments');

    return match($functionName) {
        'check_availability' => $this->checkAvailability($arguments),
        'book_appointment' => $this->bookAppointment($arguments),
        default => ['error' => 'Unknown function'],
    };
}
```

### Check Availability

```php
private function checkAvailability(array $args): array
{
    $service = app(CalcomService::class);

    $slots = $service->getAvailableSlots(
        staffId: $args['staff_id'],
        date: Carbon::parse($args['date']),
        duration: $args['duration'] ?? 30
    );

    return [
        'available_slots' => $slots->map(fn($s) => [
            'time' => $s->format('H:i'),
            'display' => $s->format('H:i') . ' Uhr',
        ])->toArray(),
    ];
}
```

### Book Appointment

```php
private function bookAppointment(array $args): array
{
    $appointment = Appointment::create([
        'customer_name' => $args['customer_name'],
        'customer_phone' => $args['customer_phone'],
        'customer_email' => $args['customer_email'] ?? null,
        'service_id' => $args['service_id'],
        'staff_id' => $args['staff_id'],
        'start_time' => Carbon::parse($args['start_time']),
        'duration' => $args['duration'],
        'source' => 'retell_ai',
    ]);

    // Sync to Cal.com
    dispatch(new SyncAppointmentToCalcomJob($appointment));

    return [
        'success' => true,
        'appointment_id' => $appointment->id,
        'confirmation' => "Termin gebucht für {$appointment->start_time->format('d.m.Y H:i')} Uhr",
    ];
}
```

## Agent Prompt

The AI agent uses a carefully crafted prompt:

```
Du bist ein freundlicher Telefonassistent für {company_name}.
Deine Aufgabe ist es, Termine zu vereinbaren.

Verfügbare Dienstleistungen:
{services_list}

Verhaltensregeln:
1. Begrüße den Anrufer freundlich
2. Frage nach dem gewünschten Service
3. Ermittle den bevorzugten Termin
4. Prüfe die Verfügbarkeit
5. Bestätige den Termin

Bei Problemen:
- Biete alternative Termine an
- Leite an einen Mitarbeiter weiter wenn nötig
```

## Error Handling

### Retry Logic

```php
// Exponential backoff for API calls
$response = retry(3, function () use ($payload) {
    return Http::timeout(10)
        ->withHeaders(['Authorization' => 'Bearer ' . config('services.retell.api_key')])
        ->post('https://api.retellai.com/v2/create-call', $payload);
}, 1000);
```

### Fallback Behavior

```php
if ($retellUnavailable) {
    // Queue for manual callback
    CallbackRequest::create([
        'phone' => $callerPhone,
        'reason' => 'AI system unavailable',
        'priority' => 'high',
    ]);

    // Notify staff
    Notification::send($staff, new CallbackNeeded($callerPhone));
}
```

## Monitoring

### Call Metrics

| Metric | Description |
|--------|-------------|
| `retell_calls_total` | Total calls processed |
| `retell_calls_success` | Successfully completed |
| `retell_calls_failed` | Failed or abandoned |
| `retell_latency_avg` | Average response time |

### Dashboard Widget

The admin dashboard shows real-time Retell statistics including:
- Active calls
- Today's call volume
- Success rate
- Average call duration

## Testing

### Local Development

```bash
# Use ngrok for webhook testing
ngrok http 8000

# Update Retell dashboard with ngrok URL
# https://xxxxx.ngrok.io/webhooks/retell/call-ended
```

### Test Calls

```php
// tests/Feature/RetellWebhookTest.php
public function test_call_ended_webhook_creates_record()
{
    $payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'call_test123',
            'call_status' => 'ended',
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), config('services.retell.webhook_secret'));

    $this->postJson('/webhooks/retell/call-ended', $payload, [
        'X-Retell-Signature' => $signature,
    ])->assertOk();

    $this->assertDatabaseHas('calls', ['retell_call_id' => 'call_test123']);
}
```
