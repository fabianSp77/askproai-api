# Enhanced Booking Service Documentation

## Overview

The `EnhancedBookingService` is a robust, production-ready booking system that implements the patterns specified in TECHNICAL_SPECIFICATION_V2_FINAL.md. It provides:

- **Race condition prevention** using TimeSlotLockManager
- **Circuit breaker pattern** for external API calls
- **Comprehensive logging** with correlation IDs
- **Async processing** for notifications and calendar sync
- **Graceful degradation** when external services fail
- **Structured result objects** for consistent API responses

## Key Features

### 1. Time Slot Locking
Prevents double bookings by acquiring exclusive locks on time slots before creating appointments.

### 2. Circuit Breaker Protection
Protects against cascading failures when Cal.com or other external services are down.

### 3. Structured Logging
Every operation is logged with correlation IDs for complete traceability.

### 4. Async Processing
- Calendar sync happens asynchronously with retry logic
- Notifications are sent via queued jobs
- Failed operations are automatically retried

### 5. Transaction Safety
All database operations are wrapped in transactions with proper rollback handling.

## Usage

### Basic Appointment Creation

```php
use App\Services\Booking\EnhancedBookingService;

$bookingService = app(EnhancedBookingService::class);

$result = $bookingService->createAppointment([
    'staff_id' => 'uuid-here',
    'service_id' => 123,
    'start_time' => '2025-06-20 10:00:00',
    'customer' => [
        'name' => 'John Doe',
        'phone' => '+491234567890',
        'email' => 'john@example.com',
        'company_id' => 'company-uuid',
    ],
    'source' => 'phone',
    'notes' => 'Customer requested morning appointment',
]);

if ($result->isSuccess()) {
    $appointment = $result->getAppointment();
    echo "Appointment booked: " . $appointment->id;
} else {
    echo "Booking failed: " . $result->getMessage();
    echo "Error code: " . $result->getErrorCode();
}
```

### Booking from Phone Call

```php
$result = $bookingService->bookFromPhoneCall([
    'datum' => '20.06.2025',
    'uhrzeit' => '14:30',
    'name' => 'Hans Müller',
    'telefonnummer' => '+491234567890',
    'email' => 'hans@mueller.de',
    'dienstleistung' => 'Haarschnitt',
    'mitarbeiter_wunsch' => 'Maria',
]);

// Check for warnings (e.g., calendar sync pending)
if ($result->hasWarnings()) {
    foreach ($result->getWarnings() as $warning) {
        echo "Warning: " . $warning;
    }
}
```

### API Controller Example

```php
class BookingController extends Controller
{
    public function store(Request $request, EnhancedBookingService $bookingService)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|date|after:now',
            'customer.name' => 'required|string',
            'customer.phone' => 'required|string',
        ]);

        $result = $bookingService->createAppointment($validated);

        return response()->json(
            $result->toArray(),
            $result->getStatusCode()
        );
    }
}
```

## Configuration

### Environment Variables

```env
# Circuit Breaker Settings
CIRCUIT_BREAKER_FAILURE_THRESHOLD=5
CIRCUIT_BREAKER_SUCCESS_THRESHOLD=2
CIRCUIT_BREAKER_TIMEOUT=60

# Service-specific Circuit Breakers
CALCOM_CIRCUIT_BREAKER_THRESHOLD=3
CALCOM_CIRCUIT_BREAKER_TIMEOUT=30
```

### Service Provider Registration

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\EnhancedBookingServiceProvider::class,
],
```

## Database Migrations

Run the following migrations:

```bash
php artisan migrate --path=database/migrations/2025_06_17_create_booking_flow_logs_table.php
php artisan migrate --path=database/migrations/2025_06_17_create_notification_logs_table.php
php artisan migrate --path=database/migrations/2025_06_17_create_circuit_breaker_metrics_table.php
```

## Queue Configuration

The service uses different queues for optimal processing:

- `calendar-sync` - For Cal.com synchronization
- `notifications` - For sending emails/SMS
- `webhooks` - For webhook processing

Configure in `config/queue.php` or use Horizon.

## Monitoring

### Circuit Breaker Status

```php
use App\Services\CircuitBreaker\CircuitBreaker;

$status = CircuitBreaker::getStatus();
// Returns:
// [
//     'calcom' => ['state' => 'closed', 'failures' => 0],
//     'retell' => ['state' => 'open', 'failures' => 5],
// ]
```

### Booking Flow Logs

Query the `booking_flow_logs` table to trace any booking:

```sql
SELECT * FROM booking_flow_logs 
WHERE correlation_id = 'uuid-here'
ORDER BY created_at;
```

### Failed Appointments

```sql
SELECT a.*, blf.context
FROM appointments a
JOIN booking_flow_logs blf ON a.id = blf.appointment_id
WHERE a.metadata->>'calendar_sync_failed' = 'true'
ORDER BY a.created_at DESC;
```

## Error Handling

The service returns structured `AppointmentResult` objects with specific error codes:

- `slot_unavailable` - Time slot is already booked
- `invalid_data` - Validation error
- `missing_required_field` - Required data missing
- `service_unavailable` - External service down
- `general_error` - Unexpected error

## Testing

Run the test suite:

```bash
php artisan test --filter EnhancedBookingServiceTest
```

## Troubleshooting

### Cal.com Sync Failures

1. Check circuit breaker status
2. Review `api_call_logs` table for error details
3. Check `appointments` metadata for sync status
4. Monitor the `calendar-sync` queue

### Race Conditions

1. Check `appointment_locks` table for active locks
2. Review correlation IDs in logs
3. Ensure lock timeout is appropriate for your use case

### Performance Issues

1. Monitor queue processing times
2. Check database query performance
3. Review circuit breaker metrics
4. Ensure proper indexes exist