# Cal.com Integration

AskPro API Gateway integrates with Cal.com for scheduling and availability management.

## Overview

Cal.com provides:
- Real-time availability checking
- Booking management
- Calendar synchronization
- Team scheduling

## Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   AskPro API    │────▶│   Cal.com API   │────▶│   Calendars     │
│   Gateway       │     │   (v2)          │     │   (Google, etc) │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │                       │
        │ Webhooks              │ Events
        ◀───────────────────────┘
```

## Configuration

### Environment Variables

```env
CALCOM_API_KEY=cal_live_xxxxx
CALCOM_API_URL=https://api.cal.com/v2
CALCOM_WEBHOOK_SECRET=whsec_xxxxx
```

### Company Settings

| Setting | Description |
|---------|-------------|
| `calcom_api_key` | Company API key |
| `calcom_team_id` | Associated Cal.com team |
| `default_event_type` | Default booking type |
| `buffer_time` | Minutes between appointments |

## Staff Mapping

Each staff member is mapped to a Cal.com team member:

```php
// app/Models/CalcomHostMapping.php
Schema::create('calcom_host_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('staff_id')->constrained();
    $table->string('calcom_user_id');
    $table->string('calcom_team_id')->nullable();
    $table->timestamps();
});
```

## Availability

### Checking Available Slots

```php
// app/Services/CalcomService.php
public function getAvailableSlots(int $staffId, Carbon $date, int $duration = 30): Collection
{
    $mapping = CalcomHostMapping::where('staff_id', $staffId)->firstOrFail();

    $response = Http::withToken($this->apiKey)
        ->get("{$this->baseUrl}/availability", [
            'userId' => $mapping->calcom_user_id,
            'dateFrom' => $date->startOfDay()->toIso8601String(),
            'dateTo' => $date->endOfDay()->toIso8601String(),
            'duration' => $duration,
        ]);

    return collect($response->json('slots'))->map(fn($slot) =>
        Carbon::parse($slot['time'])
    );
}
```

### Caching Strategy

```php
// Cache availability for 5 minutes
public function getCachedAvailability(int $staffId, Carbon $date): Collection
{
    $cacheKey = "availability:{$staffId}:{$date->format('Y-m-d')}";

    return Cache::remember($cacheKey, 300, function () use ($staffId, $date) {
        return $this->getAvailableSlots($staffId, $date);
    });
}

// Invalidate on booking changes
public function invalidateCache(int $staffId, Carbon $date): void
{
    Cache::forget("availability:{$staffId}:{$date->format('Y-m-d')}");
}
```

## Booking Management

### Creating Bookings

```php
public function createBooking(Appointment $appointment): array
{
    $mapping = CalcomHostMapping::where('staff_id', $appointment->staff_id)->firstOrFail();

    $response = Http::withToken($this->apiKey)
        ->post("{$this->baseUrl}/bookings", [
            'eventTypeId' => $appointment->service->calcom_event_type_id,
            'start' => $appointment->start_time->toIso8601String(),
            'responses' => [
                'name' => $appointment->customer_name,
                'email' => $appointment->customer_email,
                'phone' => $appointment->customer_phone,
            ],
            'metadata' => [
                'askpro_appointment_id' => $appointment->id,
                'source' => $appointment->source,
            ],
        ]);

    return $response->json();
}
```

### Rescheduling

```php
public function rescheduleBooking(string $bookingUid, Carbon $newStart): array
{
    $response = Http::withToken($this->apiKey)
        ->patch("{$this->baseUrl}/bookings/{$bookingUid}/reschedule", [
            'start' => $newStart->toIso8601String(),
            'rescheduleReason' => 'Customer request',
        ]);

    return $response->json();
}
```

### Cancellation

```php
public function cancelBooking(string $bookingUid, string $reason = null): bool
{
    $response = Http::withToken($this->apiKey)
        ->delete("{$this->baseUrl}/bookings/{$bookingUid}", [
            'cancellationReason' => $reason ?? 'Cancelled via AskPro',
        ]);

    return $response->successful();
}
```

## Webhooks

### Supported Events

| Event | Description |
|-------|-------------|
| `BOOKING_CREATED` | New booking made |
| `BOOKING_RESCHEDULED` | Booking time changed |
| `BOOKING_CANCELLED` | Booking cancelled |
| `BOOKING_CONFIRMED` | Booking confirmed |

### Webhook Handler

```php
// app/Http/Controllers/CalcomWebhookController.php
public function handle(Request $request)
{
    $event = $request->input('triggerEvent');
    $payload = $request->input('payload');

    return match($event) {
        'BOOKING_CREATED' => $this->handleCreated($payload),
        'BOOKING_RESCHEDULED' => $this->handleRescheduled($payload),
        'BOOKING_CANCELLED' => $this->handleCancelled($payload),
        default => response()->json(['status' => 'ignored']),
    };
}

private function handleCreated(array $payload): JsonResponse
{
    // Check if this is from AskPro (has our metadata)
    if (isset($payload['metadata']['askpro_appointment_id'])) {
        // Already synced from our side
        return response()->json(['status' => 'already_synced']);
    }

    // External booking - create in AskPro
    $appointment = Appointment::create([
        'calcom_booking_uid' => $payload['uid'],
        'customer_name' => $payload['responses']['name'],
        'customer_email' => $payload['responses']['email'],
        'start_time' => Carbon::parse($payload['startTime']),
        'source' => 'calcom_direct',
    ]);

    return response()->json(['status' => 'created', 'id' => $appointment->id]);
}
```

### Webhook Signature Verification

```php
// app/Http/Middleware/VerifyCalcomWebhookSignature.php
public function handle($request, $next)
{
    $signature = $request->header('X-Cal-Signature-256');
    $payload = $request->getContent();

    $expected = hash_hmac('sha256', $payload, config('services.calcom.webhook_secret'));

    if (!hash_equals($expected, $signature)) {
        abort(401, 'Invalid Cal.com signature');
    }

    return $next($request);
}
```

## Bidirectional Sync

### AskPro → Cal.com

```php
// app/Jobs/SyncAppointmentToCalcomJob.php
public function handle()
{
    $calcomService = app(CalcomService::class);

    if ($this->appointment->calcom_booking_uid) {
        // Update existing
        $calcomService->rescheduleBooking(
            $this->appointment->calcom_booking_uid,
            $this->appointment->start_time
        );
    } else {
        // Create new
        $result = $calcomService->createBooking($this->appointment);
        $this->appointment->update(['calcom_booking_uid' => $result['uid']]);
    }
}
```

### Cal.com → AskPro

Handled via webhooks (see above).

## Event Types

### Mapping Services to Event Types

```php
// Each service maps to a Cal.com event type
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('duration'); // minutes
    $table->string('calcom_event_type_id')->nullable();
    // ...
});
```

### Creating Event Types

```php
public function createEventType(Service $service): array
{
    $response = Http::withToken($this->apiKey)
        ->post("{$this->baseUrl}/event-types", [
            'title' => $service->name,
            'slug' => Str::slug($service->name),
            'length' => $service->duration,
            'teamId' => $this->teamId,
        ]);

    $service->update(['calcom_event_type_id' => $response->json('eventType.id')]);

    return $response->json();
}
```

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `slot_unavailable` | Time already booked | Refresh availability |
| `user_not_found` | Invalid staff mapping | Check CalcomHostMapping |
| `rate_limit_exceeded` | Too many requests | Implement backoff |

### Retry Logic

```php
public function makeApiCall(string $method, string $endpoint, array $data = []): Response
{
    return retry(3, function () use ($method, $endpoint, $data) {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->{$method}("{$this->baseUrl}/{$endpoint}", $data);

        if ($response->status() === 429) {
            throw new RateLimitException();
        }

        return $response;
    }, function ($attempt) {
        return $attempt * 1000; // Exponential backoff
    });
}
```

## Testing

### Mock Cal.com API

```php
// tests/Feature/CalcomIntegrationTest.php
public function test_availability_check()
{
    Http::fake([
        'api.cal.com/v2/availability*' => Http::response([
            'slots' => [
                ['time' => '2024-01-15T09:00:00Z'],
                ['time' => '2024-01-15T10:00:00Z'],
            ],
        ]),
    ]);

    $service = app(CalcomService::class);
    $slots = $service->getAvailableSlots(1, now()->addDay());

    $this->assertCount(2, $slots);
}
```
