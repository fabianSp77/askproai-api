# Cal.com V2 API Integration Documentation

## Overview

This document describes the production-ready Cal.com V2 API integration for AskProAI. The integration provides a robust, scalable, and fault-tolerant solution for managing calendar bookings through Cal.com's latest API version.

## Key Features

- ✅ **Full V2 API Support**: All major endpoints implemented
- ✅ **Circuit Breaker Pattern**: Automatic fault tolerance
- ✅ **Retry Logic**: Exponential backoff for transient failures
- ✅ **Response Caching**: Redis-based caching for performance
- ✅ **Type Safety**: DTOs for all API responses
- ✅ **Structured Logging**: Comprehensive request/response logging
- ✅ **Error Handling**: Custom exceptions for different error types
- ✅ **Health Monitoring**: Built-in health check endpoint
- ✅ **Metrics Collection**: Performance and reliability metrics

## Architecture

### Core Components

1. **CalcomV2Client**: Low-level API client with all HTTP operations
2. **CalcomV2Service**: High-level service integrating with domain models
3. **DTOs**: Type-safe data transfer objects
4. **Exceptions**: Specific exception classes for error handling
5. **Controllers**: HTTP endpoints for health checks

### Class Structure

```
app/Services/Calcom/
├── CalcomV2Client.php          # Core API client
├── CalcomV2Service.php         # Business logic service
├── DTOs/
│   ├── BaseDTO.php            # Base DTO class
│   ├── EventTypeDTO.php       # Event type data
│   ├── SlotDTO.php            # Available slot data
│   ├── BookingDTO.php         # Booking data
│   ├── AttendeeDTO.php        # Attendee data
│   └── ScheduleDTO.php        # Schedule data
└── Exceptions/
    ├── CalcomApiException.php          # Base exception
    ├── CalcomAuthenticationException.php
    ├── CalcomRateLimitException.php
    └── CalcomValidationException.php
```

## API Endpoints

### Event Types

```php
// Get all event types
$client->getEventTypes(array $filters = []): array

// Filters:
// - userId: Filter by user ID
// - teamId: Filter by team ID
// - active: Filter active/inactive
```

### Schedules

```php
// Get all schedules
$client->getSchedules(array $filters = []): array
```

### Available Slots

```php
// Get available time slots
$client->getAvailableSlots(array $params): array

// Required params:
// - startTime: ISO 8601 datetime
// - endTime: ISO 8601 datetime
// - eventTypeId OR eventTypeSlug
// 
// Optional params:
// - timeZone: Timezone for slots (default: UTC)
// - duration: Override event type duration
```

### Bookings

```php
// Create a new booking
$client->createBooking(array $data): BookingDTO

// Required data:
// - start: ISO 8601 datetime
// - eventTypeId: Event type ID
// - responses: Array with name and email
// - metadata: Additional metadata
//
// Optional data:
// - timeZone: Attendee timezone
// - language: Preferred language
// - location: Meeting location

// Get all bookings
$client->getBookings(array $filters = []): array

// Get single booking
$client->getBooking(string $uid): BookingDTO

// Reschedule booking
$client->rescheduleBooking(string $uid, array $data): BookingDTO

// Cancel booking
$client->cancelBooking(string $uid, array $data = []): array
```

## Usage Examples

### Basic Usage

```php
use App\Services\Calcom\CalcomV2Client;

// Initialize client
$client = new CalcomV2Client('your-api-key');

// Get event types
$eventTypes = $client->getEventTypes();

// Get available slots
$slots = $client->getAvailableSlots([
    'startTime' => '2024-01-15T00:00:00Z',
    'endTime' => '2024-01-16T00:00:00Z',
    'eventTypeId' => 123
]);

// Create booking
$booking = $client->createBooking([
    'start' => '2024-01-15T10:00:00Z',
    'eventTypeId' => 123,
    'responses' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+1234567890'
    ],
    'metadata' => [
        'source' => 'askproai'
    ]
]);
```

### Service Layer Usage

```php
use App\Services\Calcom\CalcomV2Service;
use App\Models\Company;

// Initialize service with company
$company = Company::find(1);
$service = new CalcomV2Service($company);

// Get event types as DTOs
$eventTypes = $service->getEventTypes();

// Check availability
$isAvailable = $service->checkAvailability(
    eventTypeId: 123,
    requestedTime: now()->addDay()->setTime(10, 0),
    staffId: 456
);

// Create booking from appointment
$booking = $service->createBookingFromAppointment($appointment);

// Sync bookings from Cal.com
$syncedCount = $service->syncBookings(
    from: now()->subDays(7),
    to: now()->addDays(30)
);
```

## Error Handling

### Exception Types

1. **CalcomApiException**: Base exception for all API errors
2. **CalcomAuthenticationException**: 401 authentication failures
3. **CalcomRateLimitException**: 429 rate limit errors
4. **CalcomValidationException**: 422 validation errors

### Error Handling Example

```php
try {
    $booking = $client->createBooking($data);
} catch (CalcomAuthenticationException $e) {
    // Handle authentication error
    Log::error('Cal.com authentication failed: ' . $e->getMessage());
} catch (CalcomValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrorMessages();
    Log::error('Validation failed: ' . implode(', ', $errors));
} catch (CalcomRateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter();
    Log::warning("Rate limited. Retry after {$retryAfter} seconds");
} catch (CalcomApiException $e) {
    // Handle other API errors
    Log::error('Cal.com API error: ' . $e->getMessage());
}
```

## Caching

### Cache Configuration

- **Event Types**: 5 minutes TTL
- **Schedules**: 5 minutes TTL
- **Available Slots**: 1 minute TTL
- **Bookings**: No caching (always fresh)

### Cache Management

```php
// Cache is automatically managed by the client
// Manual cache clearing:
Cache::tags(['calcom_slots'])->flush();

// Cache warmup command:
php artisan calcom:cache-warmup
```

## Circuit Breaker

The circuit breaker protects against cascading failures:

- **Closed State**: Normal operation
- **Open State**: Fails fast after threshold exceeded
- **Half-Open State**: Limited requests to test recovery

### Configuration

```php
// Default thresholds
const FAILURE_THRESHOLD = 5;     // Failures before opening
const SUCCESS_THRESHOLD = 2;     // Successes to close
const TIMEOUT = 60;              // Seconds before half-open
```

## Health Monitoring

### Health Check Endpoint

```bash
GET /api/health/calcom
```

Response:
```json
{
    "service": "cal.com",
    "status": "healthy",
    "timestamp": "2024-01-15T10:00:00Z",
    "details": {
        "response_time_ms": 245,
        "circuit_breaker": {
            "state": "closed",
            "failure_count": 0,
            "success_count": 150
        },
        "api_version": "2024-08-13",
        "cache_ttls": {
            "event_types_ttl": 300,
            "schedules_ttl": 300,
            "slots_ttl": 60
        }
    }
}
```

### Metrics

```php
// Get client metrics
$metrics = $client->getMetrics();

// Returns:
[
    'circuit_breaker' => [
        'state' => 'closed',
        'failure_count' => 0,
        'success_count' => 1000,
        'last_failure_time' => null
    ],
    'cache' => [
        'event_types_ttl' => 300,
        'schedules_ttl' => 300,
        'slots_ttl' => 60
    ],
    'api_version' => '2024-08-13'
]
```

## Testing

### Unit Tests

```bash
# Run Cal.com V2 client tests
php artisan test --filter CalcomV2ClientTest
```

### Integration Tests

```bash
# Run integration tests
php artisan test --filter CalcomV2ClientIntegrationTest --group integration
```

### Test Coverage

- ✅ All API endpoints
- ✅ Error handling scenarios
- ✅ Circuit breaker behavior
- ✅ Caching functionality
- ✅ Concurrent booking handling
- ✅ Timezone conversions
- ✅ Pagination support

## Migration from V1

### Key Differences

1. **API Version**: Uses V2 endpoints exclusively
2. **Response Format**: Responses wrapped in `data` key
3. **Booking Creation**: Different parameter structure
4. **Error Responses**: Standardized error format

### Migration Steps

1. Update service initialization:
```php
// Old
$service = new CalcomService();

// New
$service = new CalcomV2Service();
```

2. Update booking creation:
```php
// Old
$service->createBooking($eventTypeId, $start, $end, $attendee);

// New
$service->createBookingFromAppointment($appointment);
```

3. Update error handling to use new exception types

## Performance Optimization

### Best Practices

1. **Use Caching**: Leverage built-in caching for repeated queries
2. **Batch Operations**: Group related API calls
3. **Async Processing**: Use queues for non-critical operations
4. **Monitor Metrics**: Track circuit breaker state and response times

### Cache Warmup

```bash
# Warm up caches for all companies
php artisan calcom:cache-warmup

# Warm up specific company
php artisan calcom:cache-warmup --company=1
```

## Security

### API Key Management

- Store API keys encrypted in database
- Use environment variables for defaults
- Rotate keys regularly
- Monitor for authentication failures

### Request Sanitization

Sensitive data is automatically redacted in logs:
- Email addresses
- Phone numbers
- Personal notes

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Check API key validity
   - Verify Cal.com account status
   - Check rate limits

2. **Booking Failures**
   - Validate required fields
   - Check slot availability
   - Verify event type configuration

3. **Circuit Breaker Open**
   - Check Cal.com API status
   - Review error logs
   - Wait for timeout period

### Debug Mode

Enable detailed logging:
```php
// In CalcomV2Client
$this->logger->setLevel('debug');
```

## Support

For issues or questions:
1. Check error logs in `storage/logs/laravel.log`
2. Review health check endpoint
3. Monitor metrics dashboard
4. Contact system administrator