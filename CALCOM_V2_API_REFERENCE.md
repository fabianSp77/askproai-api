# Cal.com V2 API Reference

## Overview

This document provides a complete reference for the Cal.com V2 API integration in AskProAI. All endpoints, request/response formats, and error codes are documented.

## Authentication

All API requests require Bearer token authentication:

```http
Authorization: Bearer cal_live_xxxxxxxxxxxxxx
cal-api-version: 2024-08-13
Content-Type: application/json
```

## Base Configuration

```php
// config/calcom-v2.php
return [
    'api_url' => 'https://api.cal.com/v2',
    'api_version' => '2024-08-13',
    'timeout' => [
        'connect' => 10,
        'request' => 30,
    ],
    'retry' => [
        'times' => 3,
        'delay' => 100, // milliseconds
    ],
];
```

## Service Classes

### CalcomV2Client

Low-level API client handling HTTP requests:

```php
use App\Services\Calcom\CalcomV2Client;

$client = new CalcomV2Client($apiKey);
```

### CalcomV2Service

High-level service with business logic:

```php
use App\Services\Calcom\CalcomV2Service;

$service = new CalcomV2Service($company);
```

## API Endpoints

### 1. Authentication & User

#### Get Current User
```php
$user = $client->getMe();
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 12345,
        "email": "user@example.com",
        "username": "johndoe",
        "timeZone": "Europe/Berlin",
        "weekStart": "Monday",
        "createdDate": "2024-01-01T00:00:00Z",
        "organizationId": 67890
    }
}
```

### 2. Event Types

#### List Event Types
```php
$eventTypes = $client->getEventTypes([
    'teamId' => 123,      // Optional: Filter by team
    'userId' => 456,      // Optional: Filter by user
    'active' => true      // Optional: Only active types
]);
```

**Response Structure:**
```json
{
    "status": "success",
    "data": {
        "eventTypeGroups": [
            {
                "teamId": null,
                "profile": {
                    "name": "John Doe",
                    "image": "https://..."
                },
                "eventTypes": [
                    {
                        "id": 2026361,
                        "slug": "30min",
                        "title": "30 Min Meeting",
                        "description": "Quick consultation",
                        "length": 30,
                        "locations": [
                            {
                                "type": "inPerson",
                                "address": "123 Main St"
                            }
                        ],
                        "price": 0,
                        "currency": "EUR",
                        "requiresConfirmation": false,
                        "recurringEvent": null
                    }
                ]
            }
        ]
    }
}
```

#### Get Single Event Type
```php
$eventType = $client->getEventType($eventTypeId);
```

### 3. Schedules

#### List Schedules
```php
$schedules = $client->getSchedules();
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 789,
            "name": "Working Hours",
            "timeZone": "Europe/Berlin",
            "isDefault": true,
            "schedule": [
                {
                    "days": [1, 2, 3, 4, 5],
                    "startTime": "09:00",
                    "endTime": "17:00"
                }
            ]
        }
    ]
}
```

### 4. Availability

#### Get Available Slots
```php
$slots = $client->getAvailableSlots([
    'eventTypeId' => 2026361,
    'startTime' => '2025-01-15T00:00:00Z',
    'endTime' => '2025-01-16T00:00:00Z',
    'timeZone' => 'Europe/Berlin',
    'duration' => 30  // Optional: Override default
]);
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "slots": {
            "2025-01-15": [
                {
                    "time": "2025-01-15T09:00:00Z",
                    "attendees": 0,
                    "bookingUid": null,
                    "users": ["johndoe"]
                },
                {
                    "time": "2025-01-15T09:30:00Z",
                    "attendees": 0,
                    "bookingUid": null,
                    "users": ["johndoe"]
                }
            ]
        }
    }
}
```

### 5. Bookings

#### Create Booking
```php
$booking = $client->createBooking([
    'start' => '2025-01-15T10:00:00Z',
    'eventTypeId' => 2026361,
    'responses' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+49 30 123456',
        'notes' => 'First time patient',
        'guests' => [] // Additional email addresses
    ],
    'metadata' => [
        'askproai_appointment_id' => 12345,
        'branch_id' => 67,
        'source' => 'phone_ai'
    ],
    'timeZone' => 'Europe/Berlin',
    'language' => 'de',
    'rescheduleUid' => null // For rescheduling
]);
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 987654,
        "uid": "abc-def-ghi",
        "title": "30 Min Meeting with John Doe",
        "description": "Quick consultation",
        "startTime": "2025-01-15T10:00:00Z",
        "endTime": "2025-01-15T10:30:00Z",
        "attendees": [
            {
                "email": "john@example.com",
                "name": "John Doe",
                "timeZone": "Europe/Berlin"
            }
        ],
        "user": {
            "email": "host@example.com",
            "name": "Dr. Smith",
            "timeZone": "Europe/Berlin"
        },
        "location": {
            "type": "inPerson",
            "address": "123 Main St"
        },
        "status": "ACCEPTED",
        "rescheduleUid": "rst-uvw-xyz",
        "cancellationReason": null,
        "rejectionReason": null,
        "metadata": {
            "askproai_appointment_id": "12345"
        }
    }
}
```

#### List Bookings
```php
$bookings = $client->getBookings([
    'startTime' => '2025-01-01T00:00:00Z',
    'endTime' => '2025-01-31T23:59:59Z',
    'status' => 'ACCEPTED', // ACCEPTED, PENDING, CANCELLED, REJECTED
    'eventTypeId' => 2026361,
    'teamId' => 123,
    'userId' => 456
]);
```

#### Get Single Booking
```php
$booking = $client->getBooking($bookingUid);
```

#### Update/Reschedule Booking
```php
$updatedBooking = $client->rescheduleBooking($bookingUid, [
    'start' => '2025-01-16T11:00:00Z',
    'rescheduleReason' => 'Patient requested different time'
]);
```

#### Cancel Booking
```php
$result = $client->cancelBooking($bookingUid, [
    'cancellationReason' => 'Patient cancelled'
]);
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "message": "Booking cancelled successfully",
        "bookingId": 987654
    }
}
```

### 6. Webhooks

#### Webhook Payload Structure

**BOOKING_CREATED:**
```json
{
    "triggerEvent": "BOOKING_CREATED",
    "createdAt": "2025-01-15T10:00:00Z",
    "payload": {
        "bookingId": 987654,
        "rescheduleUid": "rst-uvw-xyz",
        "eventTypeId": 2026361,
        "title": "30 Min Meeting",
        "startTime": "2025-01-15T10:00:00Z",
        "endTime": "2025-01-15T10:30:00Z",
        "attendees": [...],
        "metadata": {...}
    }
}
```

**Webhook Signature Verification:**
```php
$signature = hash_hmac('sha256', $rawPayload, $webhookSecret);
$isValid = hash_equals($signature, $providedSignature);
```

## Data Transfer Objects (DTOs)

### EventTypeDTO
```php
class EventTypeDTO extends BaseDTO
{
    public int $id;
    public string $slug;
    public string $title;
    public ?string $description;
    public int $length;
    public array $locations;
    public float $price;
    public string $currency;
    public bool $requiresConfirmation;
    public ?array $recurringEvent;
    public ?array $customInputs;
}
```

### BookingDTO
```php
class BookingDTO extends BaseDTO
{
    public int $id;
    public string $uid;
    public string $title;
    public ?string $description;
    public string $startTime;
    public string $endTime;
    public array $attendees;
    public array $user;
    public ?array $location;
    public string $status;
    public ?string $rescheduleUid;
    public ?string $cancellationReason;
    public ?array $metadata;
}
```

### SlotDTO
```php
class SlotDTO extends BaseDTO
{
    public string $time;
    public int $attendees;
    public ?string $bookingUid;
    public array $users;
}
```

## Error Handling

### Exception Types

```php
use App\Services\Calcom\Exceptions\{
    CalcomApiException,
    CalcomAuthenticationException,
    CalcomRateLimitException,
    CalcomValidationException
};
```

### Error Response Format
```json
{
    "status": "error",
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid request parameters",
        "details": [
            {
                "field": "startTime",
                "message": "Must be in the future"
            }
        ]
    }
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 401 | Invalid or missing API key |
| `FORBIDDEN` | 403 | Access denied to resource |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 422 | Invalid request data |
| `RATE_LIMITED` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

### Error Handling Examples

```php
try {
    $booking = $client->createBooking($data);
} catch (CalcomAuthenticationException $e) {
    // Handle auth error - check API key
    Log::error('Cal.com auth failed: ' . $e->getMessage());
} catch (CalcomValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrors();
    foreach ($errors as $error) {
        Log::error("Field {$error['field']}: {$error['message']}");
    }
} catch (CalcomRateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter();
    Log::warning("Rate limited, retry after {$retryAfter} seconds");
} catch (CalcomApiException $e) {
    // Handle other API errors
    Log::error('Cal.com API error: ' . $e->getMessage());
}
```

## Circuit Breaker

### Configuration
```php
// config/calcom-v2.php
'circuit_breaker' => [
    'enabled' => true,
    'failure_threshold' => 5,
    'success_threshold' => 2,
    'timeout' => 60, // seconds
    'redis_key' => 'calcom:circuit:state',
]
```

### States
- **CLOSED**: Normal operation
- **OPEN**: All requests fail immediately
- **HALF_OPEN**: Limited requests to test recovery

### Usage
```php
// Check circuit state
$state = $client->getCircuitBreakerState();

// Force reset (admin only)
$client->resetCircuitBreaker();
```

## Caching

### Cache Keys
```php
// Event types
"calcom:event_types:{$teamId}"  // TTL: 300s

// Available slots
"calcom:slots:{$eventTypeId}:{$date}"  // TTL: 60s

// User data
"calcom:user:{$userId}"  // TTL: 600s

// Schedules
"calcom:schedules:{$userId}"  // TTL: 300s
```

### Cache Management
```php
// Clear specific cache
Cache::forget("calcom:event_types:{$teamId}");

// Clear all Cal.com cache
Cache::tags(['calcom'])->flush();
```

## Performance Metrics

### Response Times
- Event Types: < 200ms (cached)
- Available Slots: < 500ms
- Create Booking: < 1000ms
- Cancel Booking: < 300ms

### Rate Limits
- Default: 100 requests/minute
- Burst: 150 requests/minute
- Per-endpoint limits may apply

## Testing

### Mock Responses
```php
// In tests
Http::fake([
    'api.cal.com/v2/event-types*' => Http::response([
        'status' => 'success',
        'data' => ['eventTypeGroups' => [...]]
    ]),
]);
```

### Test Helpers
```php
// Create test booking data
$testData = CalcomV2Service::generateTestBookingData();

// Validate webhook signature
$isValid = CalcomV2Service::validateWebhookSignature($payload, $signature);
```

## Debugging

### Enable Debug Mode
```php
// In .env
CALCOM_V2_DEBUG=true
CALCOM_V2_LOG_REQUESTS=true
CALCOM_V2_LOG_RESPONSES=true
```

### Debug Output
```php
// Get last request/response
$debug = $client->getLastRequestDebug();
```

## Migration Notes

### V1 to V2 Mapping

| V1 Endpoint | V2 Endpoint | Notes |
|-------------|-------------|-------|
| `/v1/event-types` | `/v2/event-types` | Response structure changed |
| `/v1/availability` | `/v2/slots/available` | New parameter format |
| `/v1/bookings` | `/v2/bookings` | Additional metadata support |

### Breaking Changes
1. Authentication method (query → header)
2. Response format (direct → wrapped in data/status)
3. Error format (message → structured errors)
4. Date format (various → ISO 8601)

## Support

- **API Documentation**: https://cal.com/docs/api-reference/v2
- **API Status**: https://status.cal.com
- **Support**: api-support@cal.com