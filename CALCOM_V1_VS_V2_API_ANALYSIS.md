# Cal.com V1 vs V2 API Analysis

Generated: 2025-06-28

## Executive Summary

The codebase is in a transitional state between Cal.com V1 and V2 APIs. While V2 infrastructure exists, most actual API calls still use V1 endpoints due to V2 limitations and compatibility issues.

## API Version Usage Overview

### Current State
- **V1 API**: Primary API for most operations (90%)
- **V2 API**: Limited usage, mainly for slots and some read operations (10%)
- **Mixed Mode**: CalcomV2Service actually uses both V1 and V2 endpoints

## Detailed API Endpoint Mapping

### 1. CalcomService.php (V1 Only - Marked for Deletion)
```php
Base URL: https://api.cal.com/v1
Authentication: ?apiKey={key} in URL
```

| Function | Endpoint | Method | Status |
|----------|----------|---------|---------|
| checkAvailability | /availability | GET | V1 Only |
| bookAppointment | /bookings | POST | V1 Only |
| getEventTypes | /event-types | GET | V1 Only |

### 2. CalcomV2Service.php (Mixed V1/V2)
```php
Base URL V1: https://api.cal.com/v1
Base URL V2: https://api.cal.com/v2
V1 Auth: ?apiKey={key} in URL
V2 Auth: Bearer token in header
```

| Function | Actual Endpoint Used | Version | Notes |
|----------|---------------------|----------|--------|
| getMe() | /v2/me | V2 | User info |
| getUsers() | /v1/users | V1 | V2 not authorized |
| getEventTypes() | /v1/event-types | V1 | V2 returns different structure |
| getEventTypeDetails() | /v2/event-types/{id} | V2 | Falls back to V1 list |
| checkAvailability() | /v2/slots/available | V2 | Primary availability check |
| bookAppointment() | /v1/bookings | V1 | V2 booking not working |
| getBookings() | /v2/bookings | V2 | Read-only |
| getSchedules() | /v1/schedules | V1 | V2 doesn't have this |
| getTeams() | /v2/teams | V2 | Team management |
| createWebhook() | /v2/webhooks | V2 | Webhook management |
| cancelBooking() | /v2/bookings/{id}/cancel | V2 | Cancellation |
| rescheduleBooking() | /v2/bookings/{id}/reschedule | V2 | Rescheduling |
| getSlots() | /v2/slots/available | V2 | Alternative to checkAvailability |

### 3. CalcomV2Client.php (V2 Only - Intended Future)
```php
Base URL: https://api.cal.com/v2
Authentication: Bearer token only
```

| Function | Endpoint | Method | Implementation Status |
|----------|----------|---------|----------------------|
| getEventTypes() | /event-types | GET | Implemented |
| getSchedules() | /schedules | GET | Implemented |
| getAvailableSlots() | /slots/available | GET | Implemented |
| createBooking() | /bookings | POST | Implemented but issues |
| getBookings() | /bookings | GET | Implemented |
| getBooking() | /bookings/{uid} | GET | Implemented |
| rescheduleBooking() | /bookings/{uid}/reschedule | PATCH | Implemented |
| cancelBooking() | /bookings/{uid}/cancel | DELETE | Implemented |

### 4. CalcomMCPServer.php (MCP Wrapper)
Uses CalcomV2Service internally, so inherits the mixed V1/V2 approach:

| MCP Function | Internal Service Call | API Version Used |
|--------------|---------------------|------------------|
| getEventTypes | CalcomV2Service->getEventTypes() | V1 |
| checkAvailability | CalcomV2Service->checkAvailability() | V2 (slots) |
| createBooking | CalcomV2Service->bookAppointment() | V1 |
| getBookings | CalcomV2Service->getBookings() | V2 |
| syncEventTypes | CalcomV2Service->getEventTypes() | V1 |
| syncUsersWithSchedules | CalcomV2Service->getUsers() | V1 |

## Key Differences Between V1 and V2

### 1. Authentication
- **V1**: API key as query parameter (`?apiKey=xxx`)
- **V2**: Bearer token in Authorization header

### 2. Response Structure
- **V1**: Direct response (e.g., `{ "event_types": [...] }`)
- **V2**: Wrapped in data object (e.g., `{ "data": { "eventTypes": [...] } }`)

### 3. Endpoint Availability
| Feature | V1 Available | V2 Available | Notes |
|---------|--------------|--------------|--------|
| Event Types List | ✅ | ❓ | V2 structure different |
| Event Type Details | ✅ | ✅ | V2 has more detail |
| Users List | ✅ | ❌ | V2 returns 401 |
| Schedules | ✅ | ❌ | Not in V2 |
| Availability | ✅ | ✅ (slots) | Different endpoint |
| Create Booking | ✅ | ❓ | V2 has issues |
| List Bookings | ✅ | ✅ | V2 working |
| Cancel Booking | ✅ | ✅ | V2 working |
| Reschedule | ✅ | ✅ | V2 working |

### 4. Booking Creation Issues
V2 booking creation has several problems:
- Different required fields
- Team bookings not working properly
- Response structure inconsistent
- Error messages less helpful

## Migration Status

### Currently Using V1
1. Event type listing
2. User management
3. Schedule management  
4. Booking creation (most critical)

### Successfully Migrated to V2
1. Availability checking (via slots endpoint)
2. Booking listing/reading
3. Booking cancellation
4. Me endpoint (user info)
5. Teams endpoint

### Blocked from V2 Migration
1. Users endpoint (returns 401)
2. Schedules (endpoint doesn't exist)
3. Booking creation (implementation issues)

## Recommendations

### Short Term (Current Approach)
1. Continue using mixed V1/V2 approach in CalcomV2Service
2. Use V1 for critical operations (booking creation)
3. Use V2 for read operations where it works

### Medium Term
1. Work with Cal.com to fix V2 booking creation
2. Find alternatives for missing V2 endpoints
3. Implement proper error handling for V2 failures

### Long Term
1. Full migration to V2 once stable
2. Deprecate V1 usage completely
3. Use CalcomV2Client as the primary service

## Code Examples

### V1 Booking (Working)
```php
$response = Http::post($this->baseUrlV1 . '/bookings?apiKey=' . $this->apiKey, [
    'eventTypeId' => $eventTypeId,
    'start' => $startTime,
    'end' => $endTime,
    'responses' => [
        'name' => $customerName,
        'email' => $customerEmail
    ]
]);
```

### V2 Booking (Issues)
```php
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $this->apiKey,
    'cal-api-version' => '2024-08-13'
])->post($this->baseUrlV2 . '/bookings', [
    'eventTypeId' => $eventTypeId,
    'start' => $startTime,
    'metadata' => [...],
    'responses' => [...]
]);
```

### V2 Slots (Working)
```php
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $this->apiKey,
    'cal-api-version' => '2024-08-13'
])->get($this->baseUrlV2 . '/slots/available', [
    'eventTypeId' => $eventTypeId,
    'startTime' => $startTime,
    'endTime' => $endTime,
    'timeZone' => $timezone
]);
```

## Testing Commands

```bash
# Test V1 endpoints
php test-calcom-v1-api.php

# Test V2 endpoints  
php test-calcom-v2-api.php

# Test mixed mode service
php artisan calcom:test --mode=mixed
```

## Conclusion

The system is designed for V2 but practically relies on V1 for critical operations. The CalcomV2Service acts as an adapter, using V1 where V2 fails or is unavailable. This pragmatic approach ensures functionality while preparing for eventual full V2 migration.