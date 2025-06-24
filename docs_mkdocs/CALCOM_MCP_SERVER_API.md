# CalcomMCPServer API Documentation

The CalcomMCPServer provides a robust interface to Cal.com with built-in caching, circuit breaker protection, and retry logic.

## Features

- **Circuit Breaker Protection**: Prevents cascading failures when Cal.com is unavailable
- **Response Caching**: Reduces API calls and improves performance
- **Retry Logic**: Automatic retry with exponential backoff for transient failures
- **Idempotency**: Prevents duplicate bookings with idempotency keys
- **Alternative Slot Finder**: Intelligent algorithm to find alternative time slots

## Methods

### checkAvailability(array $params): array

Checks availability for a specific event type with caching.

**Parameters:**
- `company_id` (required): Company ID
- `event_type_id` (required): Cal.com event type ID
- `date_from` (optional): Start date (default: today)
- `date_to` (optional): End date (default: +7 days)
- `timezone` (optional): Timezone (default: Europe/Berlin)

**Response:**
```json
{
    "success": true,
    "available_slots": [...],
    "date_range": {
        "from": "2025-06-21",
        "to": "2025-06-28"
    },
    "event_type_id": 2026361,
    "timezone": "Europe/Berlin",
    "cached_until": "2025-06-21T10:35:00+00:00"
}
```

### createBooking(array $params): array

Creates a booking with retry logic and idempotency protection.

**Parameters:**
- `company_id` (required): Company ID
- `event_type_id` (required): Cal.com event type ID
- `start` (required): Start time (ISO 8601 format)
- `end` (optional): End time (calculated from event type if not provided)
- `name` (required): Customer name
- `email` (required): Customer email
- `phone` (optional): Customer phone
- `notes` (optional): Booking notes
- `timezone` (optional): Timezone (default: Europe/Berlin)
- `metadata` (optional): Additional metadata

**Response:**
```json
{
    "success": true,
    "booking": {
        "id": 12345,
        "uid": "abc123",
        "start": "2025-06-22T10:00:00Z",
        "end": "2025-06-22T10:30:00Z",
        "status": "ACCEPTED",
        "event_type_id": 2026361
    },
    "message": "Booking created successfully",
    "attempts": 1
}
```

### updateBooking(array $params): array

Updates an existing booking.

**Parameters:**
- `company_id` (required): Company ID
- `booking_id` (required): Booking ID
- `start` (optional): New start time
- `end` (optional): New end time
- `title` (optional): New title
- `description` (optional): New description
- `reschedule_reason` (optional): Reason for rescheduling

**Response:**
```json
{
    "success": true,
    "booking": {...},
    "message": "Booking updated successfully"
}
```

### cancelBooking(array $params): array

Cancels a booking.

**Parameters:**
- `company_id` (required): Company ID
- `booking_id` (required): Booking ID
- `cancellation_reason` (optional): Reason for cancellation

**Response:**
```json
{
    "success": true,
    "message": "Booking cancelled successfully",
    "booking_id": 12345,
    "cancelled_at": "2025-06-21T10:00:00+00:00"
}
```

### findAlternativeSlots(array $params): array

Finds alternative time slots when the preferred slot is not available.

**Parameters:**
- `company_id` (required): Company ID
- `event_type_id` (required): Cal.com event type ID
- `preferred_start` (required): Preferred start time
- `search_days` (optional): Days to search (default: 7)
- `max_alternatives` (optional): Maximum alternatives to return (default: 5)
- `timezone` (optional): Timezone (default: Europe/Berlin)

**Response:**
```json
{
    "success": true,
    "preferred_start": "2025-06-22T09:00:00+00:00",
    "alternatives": [
        {
            "start": "2025-06-22T10:00:00Z",
            "end": "2025-06-22T10:30:00Z",
            "date": "2025-06-22",
            "time": "10:00",
            "day_of_week": "Saturday",
            "days_from_preferred": 0
        }
    ],
    "search_period": {
        "from": "2025-06-22",
        "to": "2025-06-29"
    },
    "total_available": 15,
    "message": "Alternative slots found"
}
```

## Circuit Breaker States

The circuit breaker has three states:

1. **CLOSED**: Normal operation, all requests go through
2. **OPEN**: Service is down, requests fail fast with fallback
3. **HALF_OPEN**: Testing if service is back up

Configuration:
- Failure threshold: 5 failures
- Success threshold: 2 successes to close
- Timeout: 60 seconds
- Half-open requests: 3 test requests

## Caching Strategy

- Event types: Cached for 5 minutes
- Availability: Cached for 5 minutes with smart key generation
- Bookings: Idempotency cache for 24 hours
- Cache is automatically cleared on updates

## Error Handling

All methods return a consistent error format:

```json
{
    "success": false,
    "error": "error_type",
    "message": "Human readable error message",
    "circuit_breaker_open": true // When circuit is open
}
```

Error types:
- `validation_error`: Invalid input parameters
- `configuration_error`: Company or Cal.com not configured
- `service_unavailable`: Circuit breaker is open
- `booking_failed`: Booking creation failed
- `update_failed`: Update operation failed
- `cancellation_failed`: Cancellation failed
- `exception`: General exception occurred

## Usage Example

```php
use App\Services\MCP\CalcomMCPServer;

$mcpServer = new CalcomMCPServer();

// Check availability
$availability = $mcpServer->checkAvailability([
    'company_id' => 1,
    'event_type_id' => 2026361,
    'date_from' => '2025-06-21',
    'date_to' => '2025-06-28'
]);

// Create booking
if ($availability['success']) {
    $booking = $mcpServer->createBooking([
        'company_id' => 1,
        'event_type_id' => 2026361,
        'start' => '2025-06-22T10:00:00Z',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+49 30 12345678'
    ]);
}
```