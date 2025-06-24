# Cal.com Integration

## Overview

Cal.com integration enables AskProAI to check availability and book appointments in real-time. The system supports both Cal.com v1 and v2 APIs, with v2 being the recommended version for new implementations.

## Configuration

### Environment Variables
```bash
# Cal.com API Configuration
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx
DEFAULT_CALCOM_TEAM_SLUG=your-team-slug
CALCOM_API_BASE_URL=https://api.cal.com/v2
CALCOM_WEBHOOK_SECRET=your-webhook-secret
```

### Company-Level Configuration
```php
// Set Cal.com credentials for a company
$company->update([
    'calcom_api_key' => 'cal_live_xxxxxxxxxxxxxx',
    'calcom_team_id' => 12345,
    'calcom_organization_id' => 67890
]);
```

## Event Types

### Syncing Event Types
```bash
# Sync all event types from Cal.com
php artisan calcom:sync-event-types --company=1

# Sync specific branch
php artisan calcom:sync-event-types --branch=5
```

### Event Type Mapping
```php
// Map Cal.com event type to branch
$branch->calcom_event_types()->create([
    'calcom_event_type_id' => 2026361,
    'title' => '30 Min Consultation',
    'slug' => '30min-consultation',
    'description' => 'Initial consultation',
    'length' => 30,
    'locations' => ['inPerson'],
    'is_active' => true
]);
```

## Booking Flow

### 1. Check Availability
```php
use App\Services\CalcomV2Service;

$calcomService = app(CalcomV2Service::class);

// Get available slots
$slots = $calcomService->getAvailability(
    eventTypeId: 2026361,
    startDate: '2025-06-25',
    endDate: '2025-06-25'
);
```

### 2. Create Booking
```php
// Book appointment
$booking = $calcomService->createBooking([
    'eventTypeId' => 2026361,
    'start' => '2025-06-25T10:00:00Z',
    'responses' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+49 30 123456',
        'notes' => 'First time patient'
    ],
    'metadata' => [
        'askproai_appointment_id' => $appointment->id,
        'branch_id' => $branch->id
    ]
]);
```

### 3. Handle Response
```php
// Store Cal.com booking reference
$appointment->update([
    'calcom_booking_id' => $booking['id'],
    'calcom_booking_uid' => $booking['uid'],
    'calcom_reschedule_uid' => $booking['rescheduleUid'],
    'calcom_cancel_uid' => $booking['cancelUid']
]);
```

## Webhook Integration

### Webhook Endpoints
```php
// routes/api.php
Route::post('/webhooks/calcom', [CalcomWebhookController::class, 'handle'])
    ->middleware(VerifyCalcomSignature::class);
```

### Webhook Events
```php
// Handle Cal.com webhook events
class CalcomWebhookController
{
    public function handle(Request $request)
    {
        $event = $request->input('triggerEvent');
        
        switch ($event) {
            case 'BOOKING_CREATED':
                $this->handleBookingCreated($request->payload);
                break;
                
            case 'BOOKING_RESCHEDULED':
                $this->handleBookingRescheduled($request->payload);
                break;
                
            case 'BOOKING_CANCELLED':
                $this->handleBookingCancelled($request->payload);
                break;
        }
    }
}
```

### Webhook Security
```php
// Verify Cal.com webhook signature
class VerifyCalcomSignature
{
    public function handle($request, $next)
    {
        $signature = $request->header('X-Cal-Signature-256');
        $payload = $request->getContent();
        $secret = config('services.calcom.webhook_secret');
        
        $expected = hash_hmac('sha256', $payload, $secret);
        
        if (!hash_equals($expected, $signature)) {
            abort(401, 'Invalid signature');
        }
        
        return $next($request);
    }
}
```

## API v2 Migration

### Service Comparison
```php
// Legacy v1 Service
$calcomV1 = app(CalcomService::class);
$slots = $calcomV1->getAvailableSlots($eventTypeId, $date);

// New v2 Service (Recommended)
$calcomV2 = app(CalcomV2Service::class);
$slots = $calcomV2->getAvailability(
    eventTypeId: $eventTypeId,
    startDate: $date,
    endDate: $date
);
```

### v2 Advantages
- Better error handling
- Improved performance
- More detailed responses
- Extended metadata support
- Webhook reliability

## Advanced Features

### Recurring Appointments
```php
// Book recurring appointment
$booking = $calcomService->createRecurringBooking([
    'eventTypeId' => $eventTypeId,
    'recurringCount' => 4,
    'recurringInterval' => 'weekly',
    'start' => '2025-06-25T10:00:00Z',
    'responses' => $customerData
]);
```

### Team Scheduling
```php
// Get team availability
$teamSlots = $calcomService->getTeamAvailability(
    teamId: $company->calcom_team_id,
    eventTypeId: $eventTypeId,
    date: '2025-06-25'
);
```

### Custom Fields
```php
// Event type with custom fields
$eventType = [
    'customInputs' => [
        [
            'label' => 'Insurance Provider',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'e.g., AOK, TK'
        ]
    ]
];
```

## Troubleshooting

### Common Issues

#### "Event Type Not Found"
```bash
# Re-sync event types
php artisan calcom:sync-event-types --force

# Check event type status
php artisan tinker
>>> CalcomEventType::where('calcom_event_type_id', 2026361)->first()
```

#### "No Available Slots"
```php
// Debug availability
$debug = $calcomService->debugAvailability($eventTypeId, $date);
Log::info('Availability debug:', $debug);
```

#### "Booking Failed"
```php
// Check Cal.com API status
$health = $calcomService->checkHealth();
if (!$health['operational']) {
    // Fall back to manual booking
    $appointment->update(['requires_manual_booking' => true]);
}
```

### Debug Mode
```php
// Enable Cal.com debug logging
config(['services.calcom.debug' => true]);

// View debug logs
tail -f storage/logs/calcom.log
```

## Performance Optimization

### Caching
```php
// Cache availability for 5 minutes
$slots = Cache::remember(
    "calcom:availability:{$eventTypeId}:{$date}",
    300,
    fn() => $calcomService->getAvailability($eventTypeId, $date)
);
```

### Batch Operations
```php
// Sync multiple event types efficiently
$calcomService->batchSyncEventTypes($eventTypeIds);
```

## Related Documentation
- [Webhook Configuration](../api/webhooks.md)
- [Service Configuration](../configuration/services.md)
- [Cal.com v2 Migration](../migration/calcom-v2.md)