# Cal.com Webhook Configuration Guide

## Overview

This guide covers the complete setup, configuration, and handling of Cal.com webhooks in the AskProAI platform. Webhooks enable real-time synchronization of booking events between Cal.com and our system.

## Webhook Events

### Supported Events

| Event | Description | Priority |
|-------|-------------|----------|
| `BOOKING_CREATED` | New booking created | High |
| `BOOKING_RESCHEDULED` | Existing booking rescheduled | High |
| `BOOKING_CANCELLED` | Booking cancelled | High |
| `BOOKING_CONFIRMED` | Booking confirmed (if requires confirmation) | Medium |
| `BOOKING_REJECTED` | Booking rejected | Medium |
| `BOOKING_REQUESTED` | Booking requested (pending confirmation) | Medium |
| `BOOKING_PAYMENT_INITIATED` | Payment started | Low |
| `FORM_SUBMITTED` | Booking form submitted | Low |

## Setup Instructions

### 1. Configure Webhook URL in Cal.com

1. Log into Cal.com dashboard
2. Navigate to **Settings** → **Webhooks**
3. Click **New Webhook**
4. Enter the following details:

```
Subscriber URL: https://api.askproai.de/api/webhooks/calcom
Secret: [Generate a secure secret]
Events: Select all booking-related events
Active: Yes
```

### 2. Configure Environment Variables

Add to your `.env` file:

```bash
# Cal.com Webhook Configuration
CALCOM_WEBHOOK_SECRET=your-generated-secret-here
CALCOM_WEBHOOK_VERIFY_SIGNATURE=true
CALCOM_WEBHOOK_LOG_PAYLOADS=true
CALCOM_WEBHOOK_QUEUE=webhooks
CALCOM_WEBHOOK_RETRY_ATTEMPTS=3
```

### 3. Register Webhook Routes

The webhook endpoint is registered in `routes/api.php`:

```php
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Middleware\VerifyCalcomSignature;

Route::post('/webhooks/calcom', [CalcomWebhookController::class, 'handle'])
    ->middleware([
        'throttle:webhook',
        VerifyCalcomSignature::class
    ])
    ->name('webhooks.calcom');
```

## Webhook Security

### Signature Verification

All webhooks are verified using HMAC-SHA256 signatures:

```php
// app/Http/Middleware/VerifyCalcomSignature.php
class VerifyCalcomSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.calcom.webhook_secret');
        $payload = $request->getContent();
        
        // Cal.com sends signature in multiple possible headers
        $signature = $request->header('X-Cal-Signature-256')
            ?? $request->header('Cal-Signature-256')
            ?? $request->header('X-Cal-Signature')
            ?? $request->header('Cal-Signature');
        
        if (!$signature) {
            Log::warning('Cal.com webhook received without signature');
            return response('Missing signature', 401);
        }
        
        // Calculate expected signatures (with and without newline)
        $expectedSignatures = [
            hash_hmac('sha256', $payload, $secret),
            hash_hmac('sha256', rtrim($payload, "\r\n"), $secret),
            'sha256=' . hash_hmac('sha256', $payload, $secret),
            'sha256=' . hash_hmac('sha256', rtrim($payload, "\r\n"), $secret),
        ];
        
        if (!in_array($signature, $expectedSignatures, true)) {
            Log::error('Cal.com webhook signature verification failed', [
                'provided' => $signature,
                'expected' => $expectedSignatures[0]
            ]);
            return response('Invalid signature', 401);
        }
        
        return $next($request);
    }
}
```

### Security Best Practices

1. **Always verify signatures** - Never disable signature verification in production
2. **Use HTTPS only** - Webhooks must be sent over encrypted connections
3. **Implement idempotency** - Handle duplicate webhook deliveries gracefully
4. **Set reasonable timeouts** - Cal.com expects response within 30 seconds
5. **Log security events** - Track failed verifications for monitoring

## Webhook Payload Structure

### BOOKING_CREATED Payload

```json
{
    "triggerEvent": "BOOKING_CREATED",
    "createdAt": "2025-01-15T10:00:00Z",
    "payload": {
        "type": "30min-consultation",
        "title": "30 Min Consultation with John Doe",
        "description": "",
        "additionalNotes": "First time patient",
        "customInputs": {},
        "startTime": "2025-01-20T14:00:00Z",
        "endTime": "2025-01-20T14:30:00Z",
        "organizer": {
            "id": 12345,
            "name": "Dr. Smith",
            "email": "dr.smith@clinic.com",
            "username": "drsmith",
            "timeZone": "Europe/Berlin",
            "language": {
                "locale": "de"
            }
        },
        "attendees": [
            {
                "email": "john.doe@example.com",
                "name": "John Doe",
                "timeZone": "Europe/Berlin",
                "language": {
                    "locale": "de"
                }
            }
        ],
        "location": "123 Main Street, Berlin",
        "destinationCalendar": {
            "id": 67890,
            "integration": "google_calendar",
            "externalId": "primary"
        },
        "hideCalendarNotes": false,
        "requiresConfirmation": false,
        "eventTypeId": 2026361,
        "seatsShowAttendees": false,
        "seatsPerTimeSlot": null,
        "uid": "abc-def-ghi-jkl",
        "conferenceCredentialId": null,
        "hostedBy": null,
        "metadata": {
            "askproai_appointment_id": "12345",
            "branch_id": "67",
            "source": "phone_ai"
        },
        "calcomBookingId": 987654,
        "rescheduleUid": "rst-uvw-xyz",
        "from": "2025-01-19T14:00:00Z",
        "to": "2025-01-19T14:30:00Z"
    }
}
```

### BOOKING_RESCHEDULED Payload

```json
{
    "triggerEvent": "BOOKING_RESCHEDULED",
    "createdAt": "2025-01-16T10:00:00Z",
    "payload": {
        "type": "30min-consultation",
        "title": "30 Min Consultation with John Doe",
        "startTime": "2025-01-22T15:00:00Z",
        "endTime": "2025-01-22T15:30:00Z",
        "uid": "abc-def-ghi-jkl",
        "rescheduleUid": "new-rst-uvw-xyz",
        "calcomBookingId": 987654,
        "previousStartTime": "2025-01-20T14:00:00Z",
        "previousEndTime": "2025-01-20T14:30:00Z",
        "metadata": {
            "askproai_appointment_id": "12345",
            "reschedule_reason": "Patient requested different time"
        }
    }
}
```

### BOOKING_CANCELLED Payload

```json
{
    "triggerEvent": "BOOKING_CANCELLED",
    "createdAt": "2025-01-17T10:00:00Z",
    "payload": {
        "type": "30min-consultation",
        "title": "30 Min Consultation with John Doe",
        "startTime": "2025-01-20T14:00:00Z",
        "endTime": "2025-01-20T14:30:00Z",
        "uid": "abc-def-ghi-jkl",
        "calcomBookingId": 987654,
        "cancellationReason": "Patient cancelled due to illness",
        "metadata": {
            "askproai_appointment_id": "12345",
            "cancelled_by": "patient"
        }
    }
}
```

## Webhook Processing

### Controller Implementation

```php
// app/Http/Controllers/CalcomWebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessCalcomWebhook;
use Illuminate\Support\Facades\Log;

class CalcomWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('triggerEvent');
        $payload = $request->input('payload');
        
        // Log webhook receipt
        Log::info('Cal.com webhook received', [
            'event' => $event,
            'booking_id' => $payload['calcomBookingId'] ?? null,
            'uid' => $payload['uid'] ?? null,
        ]);
        
        // Validate required fields
        if (!$event || !$payload) {
            Log::error('Invalid Cal.com webhook payload', $request->all());
            return response()->json(['error' => 'Invalid payload'], 400);
        }
        
        // Queue for async processing
        ProcessCalcomWebhook::dispatch($event, $payload)
            ->onQueue(config('services.calcom.webhook_queue', 'webhooks'));
        
        // Return immediate response
        return response()->json(['status' => 'accepted'], 202);
    }
}
```

### Job Implementation

```php
// app/Jobs/ProcessCalcomWebhook.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\CalcomWebhookHandler;

class ProcessCalcomWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function __construct(
        public string $event,
        public array $payload
    ) {}
    
    public function handle(CalcomWebhookHandler $handler)
    {
        $handler->processWebhook($this->event, $this->payload);
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Cal.com webhook processing failed', [
            'event' => $this->event,
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);
        
        // Notify administrators
        // Mail::to(config('mail.admin'))->send(new WebhookFailedNotification(...));
    }
}
```

### Webhook Handler Service

```php
// app/Services/CalcomWebhookHandler.php
namespace App\Services;

use App\Models\Appointment;
use App\Models\CalcomBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalcomWebhookHandler
{
    public function processWebhook(string $event, array $payload)
    {
        Log::info("Processing Cal.com webhook: {$event}");
        
        DB::transaction(function () use ($event, $payload) {
            switch ($event) {
                case 'BOOKING_CREATED':
                    $this->handleBookingCreated($payload);
                    break;
                    
                case 'BOOKING_RESCHEDULED':
                    $this->handleBookingRescheduled($payload);
                    break;
                    
                case 'BOOKING_CANCELLED':
                    $this->handleBookingCancelled($payload);
                    break;
                    
                case 'BOOKING_CONFIRMED':
                    $this->handleBookingConfirmed($payload);
                    break;
                    
                case 'BOOKING_REJECTED':
                    $this->handleBookingRejected($payload);
                    break;
                    
                default:
                    Log::warning("Unhandled Cal.com webhook event: {$event}");
            }
        });
    }
    
    protected function handleBookingCreated(array $payload)
    {
        // Extract metadata
        $metadata = $payload['metadata'] ?? [];
        $appointmentId = $metadata['askproai_appointment_id'] ?? null;
        
        // If this booking was created by AskProAI, update our record
        if ($appointmentId) {
            $appointment = Appointment::find($appointmentId);
            if ($appointment) {
                $appointment->update([
                    'calcom_booking_id' => $payload['calcomBookingId'],
                    'calcom_booking_uid' => $payload['uid'],
                    'calcom_reschedule_uid' => $payload['rescheduleUid'] ?? null,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
                
                Log::info('Appointment confirmed via webhook', [
                    'appointment_id' => $appointmentId,
                    'calcom_booking_id' => $payload['calcomBookingId'],
                ]);
            }
        } else {
            // This is a booking created outside AskProAI - create record
            $this->createAppointmentFromWebhook($payload);
        }
    }
    
    protected function handleBookingRescheduled(array $payload)
    {
        $appointment = $this->findAppointmentByCalcomId($payload);
        
        if ($appointment) {
            $appointment->update([
                'start_time' => $payload['startTime'],
                'end_time' => $payload['endTime'],
                'calcom_reschedule_uid' => $payload['rescheduleUid'] ?? null,
                'rescheduled_at' => now(),
                'rescheduled_from' => $payload['previousStartTime'] ?? null,
            ]);
            
            // Notify customer of reschedule
            // dispatch(new SendRescheduleNotification($appointment));
        }
    }
    
    protected function handleBookingCancelled(array $payload)
    {
        $appointment = $this->findAppointmentByCalcomId($payload);
        
        if ($appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $payload['cancellationReason'] ?? 'Cal.com booking cancelled',
            ]);
            
            // Free up the time slot
            // dispatch(new ReleaseTimeSlot($appointment));
        }
    }
}
```

## Error Handling

### Common Webhook Errors

#### 1. Signature Verification Failures

**Symptoms:**
- 401 responses in webhook logs
- "Invalid signature" errors

**Solutions:**
```bash
# Verify webhook secret matches
php artisan tinker
>>> config('services.calcom.webhook_secret')

# Test signature generation
php artisan calcom:test-webhook-signature --payload='{"test":true}'
```

#### 2. Duplicate Event Processing

**Symptoms:**
- Same booking processed multiple times
- Duplicate records in database

**Solution:**
Implement idempotency using unique webhook event IDs:

```php
// Store processed webhook IDs
Schema::create('webhook_events', function (Blueprint $table) {
    $table->id();
    $table->string('webhook_id')->unique();
    $table->string('event_type');
    $table->json('payload');
    $table->timestamps();
});

// Check before processing
if (WebhookEvent::where('webhook_id', $webhookId)->exists()) {
    Log::info('Duplicate webhook ignored', ['id' => $webhookId]);
    return;
}
```

#### 3. Timeout Errors

**Symptoms:**
- Cal.com reports webhook failures
- Slow response times

**Solution:**
Always queue webhook processing:

```php
// Return immediately, process async
ProcessCalcomWebhook::dispatch($event, $payload);
return response()->json(['status' => 'accepted'], 202);
```

## Monitoring & Debugging

### Webhook Logs

Monitor webhook activity:

```bash
# Watch webhook logs in real-time
tail -f storage/logs/webhooks.log

# Search for specific booking
grep "calcomBookingId: 987654" storage/logs/webhooks.log

# Count webhook events by type
grep "triggerEvent" storage/logs/webhooks.log | sort | uniq -c
```

### Debug Commands

```bash
# Test webhook endpoint
curl -X POST https://api.askproai.de/api/webhooks/calcom \
  -H "Content-Type: application/json" \
  -H "X-Cal-Signature-256: test-signature" \
  -d '{"triggerEvent":"BOOKING_CREATED","payload":{}}'

# Replay webhook from logs
php artisan calcom:replay-webhook --id=123

# Verify webhook configuration
php artisan calcom:verify-webhooks
```

### Monitoring Dashboard

Add webhook metrics to your monitoring:

```php
// Track webhook processing
Redis::hincrby('webhooks:calcom:stats', $event, 1);
Redis::hincrby('webhooks:calcom:stats', 'total', 1);

// Track processing time
$start = microtime(true);
$this->processWebhook($event, $payload);
$duration = microtime(true) - $start;
Redis::hset('webhooks:calcom:durations', $event, $duration);
```

## Testing Webhooks

### Local Development

Use ngrok or similar for local webhook testing:

```bash
# Start local tunnel
ngrok http 8000

# Update Cal.com webhook URL to ngrok URL
https://abc123.ngrok.io/api/webhooks/calcom
```

### Unit Tests

```php
// tests/Feature/CalcomWebhookTest.php
public function test_webhook_signature_verification()
{
    $payload = json_encode(['triggerEvent' => 'BOOKING_CREATED']);
    $secret = 'test-secret';
    $signature = hash_hmac('sha256', $payload, $secret);
    
    $response = $this->postJson('/api/webhooks/calcom', json_decode($payload, true), [
        'X-Cal-Signature-256' => $signature
    ]);
    
    $response->assertStatus(202);
}

public function test_booking_created_webhook_processing()
{
    Queue::fake();
    
    $payload = [
        'triggerEvent' => 'BOOKING_CREATED',
        'payload' => [
            'calcomBookingId' => 12345,
            'uid' => 'test-uid',
            'startTime' => '2025-01-20T14:00:00Z',
            'metadata' => [
                'askproai_appointment_id' => '999'
            ]
        ]
    ];
    
    $response = $this->postJson('/api/webhooks/calcom', $payload);
    
    Queue::assertPushed(ProcessCalcomWebhook::class, function ($job) {
        return $job->event === 'BOOKING_CREATED';
    });
}
```

## Best Practices

1. **Always process webhooks asynchronously** - Use queued jobs
2. **Implement retry logic** - Handle transient failures
3. **Log extensively** - Track all webhook events
4. **Monitor webhook health** - Set up alerts for failures
5. **Handle all event types** - Even if just logging unknown events
6. **Validate payloads** - Don't trust webhook data blindly
7. **Use database transactions** - Ensure data consistency
8. **Test thoroughly** - Include webhook tests in CI/CD

## Troubleshooting Checklist

- [ ] Webhook URL correctly configured in Cal.com?
- [ ] Webhook secret matches in both systems?
- [ ] HTTPS certificate valid and not self-signed?
- [ ] Signature verification middleware active?
- [ ] Queue workers running for webhook queue?
- [ ] Database migrations run for webhook tables?
- [ ] Logs show webhook receipt?
- [ ] Response returned within 30 seconds?
- [ ] Idempotency handling implemented?
- [ ] Error notifications configured?

## Support

- **Cal.com Webhook Docs**: https://cal.com/docs/webhooks
- **Debug Webhook Issues**: Use `/admin/webhooks` dashboard
- **Contact Support**: For persistent webhook issues