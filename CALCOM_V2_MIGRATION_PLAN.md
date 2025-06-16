# Cal.com API v1 to v2 Migration Plan

## Executive Summary

The Cal.com API key only works with v2 API (Bearer authentication), while v1 API returns 403 errors. The system currently uses mostly v1 API endpoints. This migration plan ensures all functionality works properly through a hybrid approach.

## Current Situation Analysis

### API Version Differences

#### V1 API (Currently Used)
- **Authentication**: Query parameter `?apiKey=XXX`
- **Base URL**: `https://api.cal.com/v1`
- **Status**: Returns 403 Forbidden with current API key
- **Used for**:
  - Event types listing
  - Availability checking
  - Booking creation
  - User information

#### V2 API (New)
- **Authentication**: Bearer token in header `Authorization: Bearer cal_live_XXX`
- **Base URL**: `https://api.cal.com/v2`
- **Status**: Works with current API key
- **Required header**: `cal-api-version: 2024-08-13`
- **Different endpoints and response structures**

### Current Implementation Files

1. **Main Service**: `/app/Services/CalcomService.php` (v1 only)
2. **V2 Service**: `/app/Services/CalcomV2Service.php` (partial v2 implementation)
3. **Hybrid Controller**: `/app/Http/Controllers/HybridBookingController.php`
4. **API Controller**: `/app/Http/Controllers/API/CalComController.php`
5. **Routes**: `/routes/api.php`

## Migration Strategy

### Phase 1: Create Unified Service with Version Detection

```php
// /app/Services/CalcomUnifiedService.php
class CalcomUnifiedService {
    private $apiKey;
    private $v1BaseUrl = 'https://api.cal.com/v1';
    private $v2BaseUrl = 'https://api.cal.com/v2';
    
    public function __construct() {
        $this->apiKey = config('services.calcom.api_key');
    }
    
    // Auto-detect which API version to use based on endpoint
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        // Try v2 first with Bearer auth
        $response = $this->makeV2Request($endpoint, $method, $data);
        
        // If v2 fails with 404, try v1 endpoint mapping
        if ($response->status() === 404) {
            $v1Endpoint = $this->mapToV1Endpoint($endpoint);
            if ($v1Endpoint) {
                return $this->makeV1Request($v1Endpoint, $method, $data);
            }
        }
        
        return $response;
    }
}
```

### Phase 2: Endpoint Mapping and Implementation

#### Critical Endpoints for Booking Functionality

| Function | V1 Endpoint | V2 Endpoint | Priority |
|----------|-------------|-------------|----------|
| Get Event Types | `/event-types` | `/event-types` | HIGH |
| Check Availability | `/availability` | `/slots/available` | HIGH |
| Create Booking | `/bookings` | `/bookings` | HIGH |
| Get Booking | `/bookings/{id}` | `/bookings/{uid}` | MEDIUM |
| Cancel Booking | `/bookings/{id}/cancel` | `/bookings/{uid}/cancel` | MEDIUM |
| Get Users | `/users` | `/users` | LOW |

### Phase 3: Implementation Details

#### 1. Event Types (Both versions work similarly)
```php
// V1 & V2 compatible
public function getEventTypes() {
    return Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'cal-api-version' => '2024-08-13'
    ])->get($this->v2BaseUrl . '/event-types');
}
```

#### 2. Availability Checking (Different endpoints)
```php
// V1 approach (deprecated)
public function checkAvailabilityV1($eventTypeId, $dateFrom, $dateTo) {
    return Http::get($this->v1BaseUrl . '/availability', [
        'apiKey' => $this->apiKey,
        'eventTypeId' => $eventTypeId,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo
    ]);
}

// V2 approach (recommended)
public function checkAvailabilityV2($eventTypeId, $startTime, $endTime) {
    return Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'cal-api-version' => '2024-08-13'
    ])->get($this->v2BaseUrl . '/slots/available', [
        'eventTypeId' => $eventTypeId,
        'startTime' => $startTime,
        'endTime' => $endTime,
        'timeZone' => 'Europe/Berlin'
    ]);
}
```

#### 3. Booking Creation (Different request structure)
```php
// V2 Booking Structure
public function createBookingV2($eventTypeId, $start, $attendee) {
    $data = [
        'eventTypeId' => $eventTypeId,
        'start' => $start, // ISO 8601 format
        'attendee' => [
            'name' => $attendee['name'],
            'email' => $attendee['email'],
            'timeZone' => $attendee['timeZone'] ?? 'Europe/Berlin',
        ],
        'metadata' => [
            'source' => 'askproai',
            'via' => 'phone_ai'
        ],
        'language' => 'de'
    ];
    
    return Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'cal-api-version' => '2024-08-13',
        'Content-Type' => 'application/json'
    ])->post($this->v2BaseUrl . '/bookings', $data);
}
```

### Phase 4: Testing Strategy

#### 1. Unit Tests
```php
// tests/Feature/CalcomV2Test.php
class CalcomV2Test extends TestCase {
    public function test_can_fetch_event_types_v2() {
        $service = new CalcomUnifiedService();
        $eventTypes = $service->getEventTypes();
        
        $this->assertNotNull($eventTypes);
        $this->assertArrayHasKey('data', $eventTypes);
    }
    
    public function test_can_check_availability_v2() {
        $service = new CalcomUnifiedService();
        $slots = $service->checkAvailability(
            2026302, 
            now()->addDay()->toIso8601String(),
            now()->addDays(2)->toIso8601String()
        );
        
        $this->assertNotNull($slots);
    }
    
    public function test_can_create_booking_v2() {
        $service = new CalcomUnifiedService();
        $booking = $service->createBooking(2026302, [
            'start' => now()->addDay()->setHour(14)->toIso8601String(),
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertNotNull($booking);
        $this->assertArrayHasKey('data', $booking);
    }
}
```

#### 2. Integration Tests
- Test complete booking flow (availability → booking → confirmation)
- Test webhook handling for v2 bookings
- Test error handling and fallback mechanisms

#### 3. Manual Testing Checklist
- [ ] Fetch event types using v2 API
- [ ] Check availability for different dates
- [ ] Create test bookings
- [ ] Verify webhook notifications
- [ ] Test cancellation flow
- [ ] Verify data consistency in database

### Phase 5: Rollout Plan

#### Week 1: Development
1. Create `CalcomUnifiedService` class
2. Implement v2 endpoints with proper authentication
3. Create mapping layer for v1→v2 migration
4. Write comprehensive tests

#### Week 2: Testing
1. Run all unit tests
2. Perform integration testing
3. Test in staging environment
4. Fix any issues found

#### Week 3: Gradual Rollout
1. Deploy to production with feature flag
2. Monitor logs for errors
3. Gradually increase v2 usage percentage
4. Full switchover once stable

### Phase 6: Monitoring and Rollback

#### Monitoring Points
1. **API Response Times**: Compare v1 vs v2 performance
2. **Error Rates**: Track 403, 404, and 500 errors
3. **Booking Success Rate**: Ensure no drop in successful bookings
4. **Webhook Delivery**: Verify all webhooks are received

#### Rollback Strategy
```php
// config/services.php
'calcom' => [
    'api_key' => env('CALCOM_API_KEY'),
    'api_version' => env('CALCOM_API_VERSION', 'v2'), // Easy switch
    'fallback_to_v1' => env('CALCOM_FALLBACK_V1', false),
],
```

## Database Migrations Required

```php
// Add API version tracking to bookings
Schema::table('calcom_bookings', function (Blueprint $table) {
    $table->string('api_version')->default('v1')->after('booking_uid');
    $table->json('v2_response_data')->nullable();
});
```

## Configuration Updates

### Environment Variables
```env
CALCOM_API_KEY=cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da
CALCOM_API_VERSION=v2
CALCOM_V2_API_VERSION=2024-08-13
CALCOM_TEAM_SLUG=askproai
CALCOM_FALLBACK_V1=false
```

### Service Configuration
```php
// config/services.php
'calcom' => [
    'api_key' => env('CALCOM_API_KEY'),
    'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
    'team_slug' => env('CALCOM_TEAM_SLUG', 'askproai'),
    'api_version' => env('CALCOM_API_VERSION', 'v2'),
    'v2_api_version' => env('CALCOM_V2_API_VERSION', '2024-08-13'),
    'enable_fallback' => env('CALCOM_FALLBACK_V1', false),
],
```

## Risk Assessment and Mitigation

### Risks
1. **Breaking Changes**: V2 response structures differ from v1
2. **Missing Features**: Some v1 endpoints might not exist in v2
3. **Performance**: V2 might have different rate limits
4. **Webhooks**: Webhook payloads might differ

### Mitigation Strategies
1. **Response Normalization**: Create adapter layer to normalize responses
2. **Feature Parity Check**: Document all used v1 features and verify v2 equivalents
3. **Rate Limit Handling**: Implement proper retry logic with exponential backoff
4. **Webhook Versioning**: Handle both v1 and v2 webhook formats

## Success Criteria
1. All booking operations work with v2 API
2. No increase in error rates
3. Webhook processing continues without issues
4. Performance remains stable or improves
5. All tests pass with v2 implementation

## Timeline
- **Week 1**: Development and unit testing
- **Week 2**: Integration testing and bug fixes  
- **Week 3**: Staging deployment and monitoring
- **Week 4**: Production rollout with monitoring
- **Week 5**: Full migration completion

## Next Steps
1. Review and approve migration plan
2. Set up v2 development environment
3. Begin implementation of `CalcomUnifiedService`
4. Create comprehensive test suite
5. Schedule staging deployment