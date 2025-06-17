# StructuredLogger Usage Guide

## Overview

The StructuredLogger service provides a comprehensive logging solution for AskProAI that:
- Automatically includes correlation IDs for request tracing
- Logs API calls to the ApiCallLog model for monitoring
- Provides structured logging for booking flows
- Includes user, IP, and request context automatically
- Supports different log channels for different purposes
- Integrates seamlessly with the existing logging infrastructure

## Quick Start

### 1. Using the Facade

```php
use App\Facades\StructuredLog;

// Simple logging
StructuredLog::info('Processing appointment request');
StructuredLog::success('Appointment created successfully');
StructuredLog::failure('Failed to create appointment');
StructuredLog::warning('Slot almost fully booked');

// Log with context
StructuredLog::info('Customer lookup', [
    'phone' => '+49123456789',
    'method' => 'phone_number'
]);
```

### 2. Using the Trait in Services

```php
use App\Services\Traits\LogsStructured;

class MyService
{
    use LogsStructured;
    
    public function processRequest()
    {
        $this->logInfo('Starting request processing');
        
        try {
            // Your logic here
            $this->logSuccess('Request processed successfully');
        } catch (\Exception $e) {
            $this->logError($e, ['context' => 'request_processing']);
        }
    }
}
```

## Core Features

### 1. Correlation IDs

Every request automatically gets a correlation ID that's included in all logs:

```php
// Get current correlation ID
$correlationId = StructuredLog::getCorrelationId();

// Set custom correlation ID (usually not needed)
StructuredLog::setCorrelationId('custom-id-123');
```

### 2. Booking Flow Logging

Track every step of the booking process:

```php
// Log booking steps
StructuredLog::logBookingFlow('booking_initiated', [
    'customer_id' => $customerId,
    'service_id' => $serviceId,
    'requested_time' => $requestedTime,
]);

StructuredLog::logBookingFlow('availability_checked', [
    'available' => true,
    'slots_found' => 5,
]);

StructuredLog::logBookingFlow('appointment_created', [
    'appointment_id' => $appointment->id,
    'confirmation_number' => $appointment->confirmation_number,
]);
```

### 3. API Call Logging

Automatically log all external API calls with timing:

```php
// Using the trait
$apiLogger = $this->logApiCall('/v2/bookings', 'POST', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'eventTypeId' => 123,
]);

try {
    $response = Http::post('https://api.cal.com/v2/bookings', $data);
    
    if ($response->successful()) {
        $apiLogger->success($response);
    } else {
        $apiLogger->failure($response, 'API returned error');
    }
} catch (\Exception $e) {
    $apiLogger->exception($e);
}
```

### 4. Webhook Logging

Log incoming webhooks with automatic context:

```php
StructuredLog::logWebhook('retell', 'call_ended', $payload, [
    'signature_valid' => true,
    'call_duration' => 120,
]);
```

### 5. Performance Logging

Track performance metrics:

```php
$startTime = microtime(true);

// Do some work...

$duration = microtime(true) - $startTime;

StructuredLog::logPerformance('data_import', $duration, [
    'records_processed' => 1000,
    'records_per_second' => 1000 / $duration,
]);
```

### 6. Security Event Logging

Log security-related events:

```php
StructuredLog::logSecurity('invalid_api_key', 'warning', [
    'api_key_prefix' => substr($apiKey, 0, 8) . '...',
    'ip_address' => request()->ip(),
]);

StructuredLog::logSecurity('multiple_failed_logins', 'critical', [
    'user_email' => $email,
    'attempts' => 5,
]);
```

## Log Channels

The following channels are configured:

- `booking_flow` - Booking process steps
- `api` - General API calls
- `calcom` - Cal.com specific logs
- `retell` - Retell.ai specific logs
- `webhooks` - Incoming webhooks
- `critical` - Critical errors
- `slow_queries` - Performance issues

## Database Storage

### ApiCallLog Model

All API calls are automatically stored in the `api_call_logs` table:

```php
// Query API call logs
use App\Models\ApiCallLog;

// Get recent Cal.com errors
$errors = ApiCallLog::forService('calcom')
    ->failed()
    ->latest()
    ->take(10)
    ->get();

// Get statistics
$stats = ApiCallLog::getServiceStats('calcom', now()->subDay(), now());
```

### BookingFlowLog Table

Booking flow steps are stored in `booking_flow_logs`:

```sql
SELECT step, COUNT(*) as count, AVG(JSON_EXTRACT(context, '$.duration_ms')) as avg_duration
FROM booking_flow_logs
WHERE company_id = ?
  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY step;
```

## Middleware Setup

The `CorrelationIdMiddleware` is automatically applied to all routes. To add it manually:

```php
// In routes/api.php or specific route group
Route::middleware(['correlation.id'])->group(function () {
    // Your routes
});
```

## Best Practices

### 1. Always Include Context

```php
// Bad
StructuredLog::info('User logged in');

// Good
StructuredLog::info('User logged in', [
    'user_id' => $user->id,
    'login_method' => 'email',
    'ip_address' => request()->ip(),
]);
```

### 2. Use Appropriate Log Levels

- `debug` - Detailed debugging information
- `info` - Interesting events (user logs in, appointment created)
- `warning` - Exceptional occurrences that are not errors
- `error` - Runtime errors that need attention
- `critical` - Critical problems that need immediate action

### 3. Sensitive Data Protection

The logger automatically masks sensitive fields:
- password
- api_key
- token
- secret
- credit_card
- authorization
- bearer

### 4. Performance Considerations

```php
// For high-frequency operations, consider sampling
if (rand(1, 100) === 1) { // Log 1% of requests
    StructuredLog::logPerformance('high_frequency_operation', $duration);
}
```

## Monitoring and Debugging

### Finding Related Logs

```php
// Get all logs for a correlation ID
$correlationId = 'abc-123-def';

// In application
$logs = DB::table('booking_flow_logs')
    ->where('correlation_id', $correlationId)
    ->orderBy('created_at')
    ->get();

// In log files
grep "abc-123-def" storage/logs/*.log
```

### Creating Dashboards

```sql
-- Booking funnel analysis
SELECT 
    step,
    COUNT(*) as total,
    COUNT(DISTINCT correlation_id) as unique_sessions,
    AVG(TIMESTAMPDIFF(SECOND, created_at, 
        LEAD(created_at) OVER (PARTITION BY correlation_id ORDER BY created_at)
    )) as avg_seconds_to_next_step
FROM booking_flow_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY step
ORDER BY total DESC;
```

## Migration Guide

### Updating Existing Services

1. Add the trait:
```php
use App\Services\Traits\LogsStructured;

class YourService
{
    use LogsStructured;
```

2. Replace Log::info() calls:
```php
// Before
Log::info('API call to Cal.com', ['endpoint' => '/bookings']);

// After
$this->logInfo('API call to Cal.com', ['endpoint' => '/bookings']);
```

3. Add API call logging:
```php
// Wrap API calls
$apiLogger = $this->logApiCall('/bookings', 'POST', $data);
try {
    $response = $this->makeApiCall($data);
    $apiLogger->success($response);
} catch (\Exception $e) {
    $apiLogger->exception($e);
}
```

## Troubleshooting

### Logs not appearing

1. Check the channel configuration in `config/logging.php`
2. Verify permissions on log files
3. Check if the channel exists: `config('logging.channels.your_channel')`

### Performance impact

The logger is designed to be lightweight, but for high-traffic endpoints:

1. Use async logging for non-critical logs
2. Consider batching API call logs
3. Implement log rotation policies

### Missing correlation IDs

Ensure the `CorrelationIdMiddleware` is registered:

```php
// In app/Http/Kernel.php or bootstrap/app.php
'api' => [
    \App\Http\Middleware\CorrelationIdMiddleware::class,
    // other middleware
],
```