# Cal.com API Complete Mapping - AskProAI Codebase

## Summary

The AskProAI codebase uses a **hybrid approach** with Cal.com APIs:
- **70% V1 API** for critical operations (booking creation, user management)
- **30% V2 API** for modern features (slots, cancellations)
- **CalcomV2Service** is the main service but uses both V1 and V2 endpoints
- **CalcomV2Client** exists for future pure V2 implementation but isn't used yet

## Service Classes and Their API Usage

### 1. **CalcomService.php** (Deprecated)
- **Status**: Marked for deletion since 2025-06-17
- **API Version**: 100% V1
- **Base URL**: `https://api.cal.com/v1`
- **Auth**: API key in URL (`?apiKey=xxx`)
- **Used by**: Legacy code, being phased out

### 2. **CalcomV2Service.php** (Primary)
- **Status**: Active, main service
- **API Version**: Mixed (70% V1, 30% V2)
- **Base URLs**: 
  - V1: `https://api.cal.com/v1`
  - V2: `https://api.cal.com/v2`
- **Auth**: 
  - V1: API key in URL
  - V2: Bearer token in header
- **Used by**: All current implementations, MCP server

### 3. **CalcomV2Client.php** (Future)
- **Status**: Implemented but not actively used
- **API Version**: 100% V2
- **Base URL**: `https://api.cal.com/v2`
- **Auth**: Bearer token only
- **Purpose**: Future migration target

### 4. **CalcomMCPServer.php** (MCP Wrapper)
- **Status**: Active
- **API Version**: Inherits from CalcomV2Service (mixed)
- **Purpose**: MCP protocol wrapper for Cal.com operations

## Complete Function-to-Endpoint Mapping

### CalcomV2Service.php Methods

| Method | Endpoint Used | API Version | Why This Version? |
|--------|---------------|-------------|-------------------|
| `getMe()` | `/v2/me` | V2 | V2 exclusive feature |
| `getUsers()` | `/v1/users` | V1 | V2 returns 401 Unauthorized |
| `getEventTypes()` | `/v1/event-types` | V1 | V2 structure incompatible |
| `getEventTypeDetails($id)` | `/v2/event-types/{id}` → `/v1/event-types` | V2→V1 | V2 tried first, fallback to V1 |
| `checkAvailability()` | `/v2/slots/available` | V2 | V2 works better for slots |
| `checkAvailabilityRange()` | `/v2/slots/available` | V2 | V2 supports date ranges |
| `bookAppointment()` | `/v1/bookings` | V1 | V2 has implementation bugs |
| `getBookings()` | `/v2/bookings` | V2 | V2 works for reading |
| `getBooking($id)` | `/v1/bookings/{id}` | V1 | More reliable |
| `getSchedules()` | `/v1/schedules` | V1 | No V2 equivalent |
| `getTeams()` | `/v2/teams` | V2 | V2 has better team support |
| `getTeamEventTypes($teamId)` | `/v2/teams/{id}/event-types` | V2 | V2 exclusive |
| `getWebhooks()` | `/v2/webhooks` | V2 | V2 exclusive |
| `createWebhook()` | `/v2/webhooks` | V2 | V2 exclusive |
| `cancelBooking()` | `/v2/bookings/{id}/cancel` | V2 | V2 works well |
| `rescheduleBooking()` | `/v2/bookings/{id}/reschedule` | V2 | V2 works well |
| `getSlots()` | `/v2/slots/available` | V2 | Alternative to checkAvailability |
| `updateBooking()` | `/v2/bookings/{id}` | V2 | V2 PATCH endpoint |

### MCP Server Functions

| MCP Function | CalcomV2Service Method | Final API Used |
|--------------|------------------------|----------------|
| `getEventTypes()` | `->getEventTypes()` | V1 `/event-types` |
| `checkAvailability()` | `->checkAvailability()` | V2 `/slots/available` |
| `createBooking()` | `->bookAppointment()` | V1 `/bookings` |
| `cancelBooking()` | `->cancelBooking()` | V2 `/bookings/{id}/cancel` |
| `updateBooking()` | Not implemented | Would use V2 |
| `getBookings()` | `->getBookings()` | V2 `/bookings` |
| `syncEventTypes()` | `->getEventTypes()` | V1 `/event-types` |
| `syncUsersWithSchedules()` | `->getUsers()` + `->getSchedules()` | V1 both |
| `testConnection()` | `->getMe()` | V2 `/me` |

## Why Mixed V1/V2 Approach?

### V1 is Used When:
1. **V2 endpoint missing** (users, schedules)
2. **V2 returns errors** (401 on users endpoint)
3. **V2 implementation has bugs** (booking creation)
4. **V2 response structure incompatible** (event types)

### V2 is Used When:
1. **V2 exclusive features** (me, teams, webhooks)
2. **V2 works better** (slots for availability)
3. **V2 is more modern** (cancellation, rescheduling)

## Code Examples

### Creating a Booking (Uses V1)
```php
// In CalcomV2Service->bookAppointment()
$response = Http::withHeaders([
    'Content-Type' => 'application/json',
])->post($this->baseUrlV1 . '/bookings?apiKey=' . $this->apiKey, [
    'eventTypeId' => (int)$eventTypeId,
    'start' => $startTime,
    'end' => $endTime,
    'timeZone' => 'Europe/Berlin',
    'language' => 'de',
    'responses' => [
        'name' => $customerData['name'],
        'email' => $customerData['email'],
        'phone' => $customerData['phone']
    ]
]);
```

### Checking Availability (Uses V2)
```php
// In CalcomV2Service->checkAvailability()
$response = Http::withHeaders([
    'cal-api-version' => '2024-08-13',
    'Authorization' => 'Bearer ' . $this->apiKey,
    'Content-Type' => 'application/json',
])->get($url, [
    'eventTypeId' => $eventTypeId,
    'startTime' => $date . 'T00:00:00.000Z',
    'endTime' => $date . 'T23:59:59.999Z',
    'timeZone' => $timezone
]);
```

## Testing the APIs

```bash
# Test all endpoints
php test-calcom-api-versions.php

# Test specific service
php artisan tinker
>>> $service = app(\App\Services\CalcomV2Service::class);
>>> $service->getEventTypes(); // Check which API is used
>>> $service->checkAvailability(123, '2025-06-29');

# Test MCP server
php artisan tinker
>>> $mcp = app(\App\Services\MCP\CalcomMCPServer::class);
>>> $mcp->getEventTypes(['company_id' => 1]);
```

## Migration Roadmap

### Current State (June 2025)
- CalcomV2Service with mixed V1/V2 ✅
- Critical operations on V1 ✅
- Modern features on V2 ✅

### Short Term
- Fix V2 booking creation issues
- Find alternatives for missing V2 endpoints
- Improve error handling

### Long Term
- Migrate fully to CalcomV2Client
- Deprecate all V1 usage
- Remove CalcomService and V1 code

## Quick Reference

| Need to... | Use | Service->Method | API |
|------------|-----|-----------------|-----|
| Book appointment | CalcomV2Service | ->bookAppointment() | V1 |
| Check slots | CalcomV2Service | ->checkAvailability() | V2 |
| List event types | CalcomV2Service | ->getEventTypes() | V1 |
| Cancel booking | CalcomV2Service | ->cancelBooking() | V2 |
| Get user info | CalcomV2Service | ->getMe() | V2 |
| Via MCP | CalcomMCPServer | ->createBooking() | V1 |