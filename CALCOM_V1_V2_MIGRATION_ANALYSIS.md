# Cal.com V1 to V2 API Migration Analysis

**Date:** 2025-06-28  
**Author:** Claude Code  
**Status:** 🔴 Mixed V1/V2 Usage - Migration Required

## Executive Summary

The AskProAI codebase currently uses a **mixed approach** with both Cal.com V1 and V2 APIs. While the main service class is named `CalcomV2Service`, it actually uses **V1 endpoints for critical operations** like booking creation. This creates confusion and prevents the system from leveraging V2 API improvements.

## Current State Analysis

### 1. Services Using Cal.com APIs

#### Primary Services:
- **CalcomService.php** - Marked for deletion, uses V1 API exclusively
- **CalcomV2Service.php** - Main service, but **mixes V1 and V2 endpoints**
- **CalcomV2MigrationService.php** - Dedicated V2 implementation (not widely used)
- **CalcomSyncService.php** - Uses both V1 and V2 endpoints
- **CalcomEventTypeSyncService.php** - Marked for deletion, uses both APIs

#### MCP Servers:
- **CalcomMCPServer.php** - Uses CalcomV2Service (mixed API usage)

### 2. V1 vs V2 Endpoint Usage in CalcomV2Service

| Method | Current API | Endpoint | Status |
|--------|------------|----------|---------|
| `getUsers()` | V1 | `/v1/users` | ⚠️ Needs migration |
| `getEventTypes()` | V1 | `/v1/event-types` | ⚠️ Needs migration |
| `bookAppointment()` | V1 | `/v1/bookings` | 🔴 **Critical** - Main booking flow |
| `getBooking()` | V1 | `/v1/bookings/{id}` | ⚠️ Needs migration |
| `getSchedules()` | V1 | `/v1/schedules` | ⚠️ Needs migration |
| `getMe()` | V2 | `/v2/me` | ✅ Already V2 |
| `checkAvailability()` | V2 | `/v2/slots/available` | ✅ Already V2 |
| `getBookings()` | V2 | `/v2/bookings` | ✅ Already V2 |
| `getTeams()` | V2 | `/v2/teams` | ✅ Already V2 |
| `getSlots()` | V2 | `/v2/slots/available` | ✅ Already V2 |
| `cancelBooking()` | V2 | `/v2/bookings/{id}/cancel` | ✅ Already V2 |
| `rescheduleBooking()` | V2 | `/v2/bookings/{id}/reschedule` | ✅ Already V2 |

### 3. Critical Issues

1. **Booking Creation Still Uses V1**: The most critical operation (`bookAppointment`) uses V1 API, which may have different behavior and features than V2.

2. **Inconsistent API Headers**: V1 uses query parameters (`?apiKey=`), while V2 uses Bearer tokens and version headers.

3. **Response Format Differences**: V1 and V2 have different response structures, requiring normalization.

4. **Feature Parity**: Some V1 endpoints don't have direct V2 equivalents (e.g., schedules).

## Migration Plan

### Phase 1: Preparation (1-2 days)

1. **Create Comprehensive V2 Client**
   - Implement all V2 endpoints in a new `CalcomV2Client` class
   - Ensure proper error handling and response normalization
   - Add comprehensive logging for debugging

2. **Update Configuration**
   - Ensure all V2 configuration values are properly set
   - Add feature flags for gradual migration

3. **Create Test Suite**
   - Unit tests for all V2 methods
   - Integration tests with mock responses
   - E2E tests for critical flows

### Phase 2: Non-Critical Migrations (2-3 days)

Migrate endpoints with low impact first:

1. **Event Types** (`getEventTypes`)
   - V1: `/v1/event-types`
   - V2: `/v2/event-types`
   - Impact: Low - Used for configuration

2. **Users** (`getUsers`)
   - V1: `/v1/users`
   - V2: Use `/v2/me` or team members
   - Impact: Low - Used for sync operations

3. **Schedules** (`getSchedules`)
   - V1: `/v1/schedules`
   - V2: Part of event type configuration
   - Impact: Medium - May need refactoring

### Phase 3: Critical Migration - Booking Creation (3-5 days)

1. **Implement V2 Booking Creation**
   ```php
   public function bookAppointmentV2($eventTypeId, $startTime, $attendee, $metadata = [])
   {
       // V2 booking structure is different
       $data = [
           'eventTypeId' => $eventTypeId,
           'start' => $startTime,
           'attendee' => [
               'name' => $attendee['name'],
               'email' => $attendee['email'],
               'timeZone' => $attendee['timeZone'] ?? 'Europe/Berlin',
           ],
           'metadata' => $metadata,
           'language' => 'de',
       ];
       
       // Use V2 endpoint with proper headers
       return $this->postV2('/bookings', $data);
   }
   ```

2. **Add Backward Compatibility Layer**
   - Transform V1 request format to V2
   - Normalize V2 responses to match V1 format
   - Ensure no breaking changes for consumers

3. **Implement Feature Toggle**
   ```php
   if (config('calcom.use_v2_booking', false)) {
       return $this->bookAppointmentV2(...);
   }
   return $this->bookAppointmentV1(...);
   ```

### Phase 4: Testing & Validation (2-3 days)

1. **A/B Testing**
   - Run both V1 and V2 in parallel for comparison
   - Log differences in responses
   - Monitor success rates

2. **Load Testing**
   - Ensure V2 performs as well as V1
   - Check rate limits and quotas

3. **Integration Testing**
   - Test with real Cal.com sandbox
   - Verify webhook compatibility
   - Check all edge cases

### Phase 5: Rollout (1-2 weeks)

1. **Gradual Rollout**
   - 10% → 25% → 50% → 100% traffic
   - Monitor error rates and performance
   - Have rollback plan ready

2. **Clean Up**
   - Remove V1 code
   - Update documentation
   - Archive old services

## Implementation Strategy

### 1. Create New V2-Only Service

```php
<?php

namespace App\Services\Calcom;

class CalcomV2Client
{
    private string $apiKey;
    private string $baseUrl = 'https://api.cal.com/v2';
    private string $apiVersion = '2024-08-13';
    
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'cal-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
        ])->$method($this->baseUrl . $endpoint, $data);
        
        if (!$response->successful()) {
            throw new CalcomApiException(
                "Cal.com API error: {$response->body()}",
                $response->status()
            );
        }
        
        return $response->json();
    }
    
    // Implement all V2 methods...
}
```

### 2. Update Dependency Injection

```php
// AppServiceProvider.php
$this->app->bind(CalcomV2Service::class, function ($app) {
    if (config('calcom.use_pure_v2', false)) {
        return new CalcomV2Client(config('services.calcom.api_key'));
    }
    return new CalcomV2Service(); // Current mixed implementation
});
```

### 3. Add Monitoring

```php
// Add metrics for API version usage
Event::listen('calcom.api.called', function ($version, $endpoint, $duration) {
    Metrics::increment("calcom.api.{$version}.{$endpoint}.calls");
    Metrics::histogram("calcom.api.{$version}.{$endpoint}.duration", $duration);
});
```

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| V2 API differences break existing flows | High | Comprehensive testing, gradual rollout |
| Rate limits differ between V1/V2 | Medium | Monitor usage, implement backoff |
| Response format changes | High | Response normalization layer |
| Missing V2 endpoints | Medium | Keep V1 fallbacks temporarily |
| Webhook format differences | High | Test webhook compatibility thoroughly |

## Recommendations

1. **Immediate Actions**:
   - Stop mixing V1/V2 in the same service
   - Create dedicated V2-only implementation
   - Add comprehensive logging for debugging

2. **Short Term** (1-2 weeks):
   - Migrate non-critical endpoints to V2
   - Set up A/B testing infrastructure
   - Create migration feature flags

3. **Medium Term** (1 month):
   - Complete booking flow migration
   - Remove V1 dependencies
   - Update all documentation

4. **Long Term**:
   - Monitor V2 API updates
   - Implement new V2 features
   - Optimize for V2 performance

## Conclusion

The current mixed V1/V2 implementation creates technical debt and confusion. A clean migration to V2-only will:
- Improve maintainability
- Enable new Cal.com features
- Provide better performance
- Reduce complexity

The migration should be done carefully with proper testing and gradual rollout to minimize risk to the critical booking flow.