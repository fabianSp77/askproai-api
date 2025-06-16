# Cal.com V2 API Test Report
## Comprehensive Analysis and Comparison

Date: June 12, 2025  
Company: 85 (API Key: cal_live_bd7aedbdf12085c5312c79ba73585920)

## Executive Summary

This report documents comprehensive testing of Cal.com's v2 API to verify support for all required functionality. The testing revealed that while v2 API provides some improvements, it has significant limitations that would require substantial changes to the current implementation.

## Test Results Overview

### Working Features in V2

1. **Availability Checking** ✅
   - Endpoint: `GET /v2/slots/available`
   - Works perfectly with proper parameters
   - Returns structured slot data by date

2. **Webhook Management** ✅
   - Endpoint: `GET /v2/webhooks`
   - Lists all configured webhooks
   - Shows webhook configuration details

3. **Booking Listing** ✅
   - Endpoint: `GET /v2/bookings`
   - Returns booking data (with proper permissions)

4. **Teams Information** ✅
   - Endpoint: `GET /v2/teams`
   - Returns team/organization data

### Non-Working/Missing Features in V2

1. **Event Types Listing** ❌
   - Endpoint: `GET /v2/event-types` returns 404
   - Critical for dynamic event type discovery
   - No alternative endpoint found

2. **User Profile** ❌
   - Endpoint: `GET /v2/users/me` returns 404
   - Cannot retrieve current user information

3. **Booking Creation** ⚠️
   - Endpoint: `POST /v2/bookings` exists but:
   - Rejects "notes" field (v1 allows it)
   - Returns "eventTypeUser.notFound" error
   - Requires different data structure

4. **Organizations/Schedules** ❌
   - Both endpoints return 404
   - No access to organizational data

## Detailed Endpoint Comparison

### 1. Event Types

| Feature | V1 API | V2 API |
|---------|--------|--------|
| List Event Types | `GET /v1/event-types?apiKey={key}` | Not Available |
| Response | Returns event_types array | 404 Error |
| Required For | Dynamic service mapping | - |

**Impact**: Cannot dynamically discover available event types in V2

### 2. Availability Checking

| Feature | V1 API | V2 API |
|---------|--------|--------|
| Check Slots | `GET /v1/availability` | `GET /v2/slots/available` |
| Parameters | eventTypeId, dateFrom, dateTo | eventTypeId, startTime, endTime, timeZone |
| Response Format | Flat slot array | Grouped by date |
| Performance | Standard | Better structure |

**V2 Example Response**:
```json
{
  "data": {
    "slots": {
      "2025-06-13": [
        {"time": "2025-06-13T09:00:00.000+02:00"},
        {"time": "2025-06-13T09:30:00.000+02:00"}
      ]
    }
  }
}
```

### 3. Booking Creation

| Feature | V1 API | V2 API |
|---------|--------|--------|
| Create Booking | `POST /v1/bookings` | `POST /v2/bookings` |
| Notes Field | Supported in responses | Not allowed |
| Customer Data | responses object | attendee object |
| Error Handling | Works with test data | eventTypeUser.notFound |

**V1 Format (Working)**:
```json
{
  "eventTypeId": 2563193,
  "start": "2025-06-14T14:00:00.000Z",
  "responses": {
    "name": "Customer Name",
    "email": "customer@example.com",
    "notes": "Additional information"
  }
}
```

**V2 Format (Not Working)**:
```json
{
  "eventTypeId": 2563193,
  "start": "2025-06-14T14:00:00.000Z",
  "attendee": {
    "name": "Customer Name",
    "email": "customer@example.com"
  }
  // notes field causes error
}
```

### 4. Webhook Handling

| Feature | V1 API | V2 API |
|---------|--------|--------|
| Webhook Events | Same events | Same events |
| Signature Method | X-Cal-Signature-256 | X-Cal-Signature-256 |
| Payload Format | Standard | Standard |
| Implementation | No changes needed | No changes needed |

## Current Implementation Analysis

### CalcomService.php Usage
- Uses V1 endpoints exclusively
- Implements event types, availability, and booking
- Would require significant refactoring for V2

### Database Schema
- 15 Cal.com related migrations
- Fields mapped to V1 response structure
- Would need updates for V2 data format

### Webhook Controller
- Basic implementation exists
- No signature verification implemented
- Needs enhancement regardless of API version

## Recommendations

### 1. **Continue Using V1 API** (Recommended)
**Reasons**:
- All required features work in V1
- No immediate deprecation announced
- Minimal code changes needed

**Actions Required**:
- Fix authorization errors (incorrect API key usage)
- Implement proper error handling
- Add retry mechanisms

### 2. **Hybrid Approach** (Alternative)
**Implementation**:
- Use V2 for `/slots/available` (better structure)
- Use V1 for event types and bookings
- Gradual migration as V2 matures

**Code Example**:
```php
class CalcomHybridService {
    public function getAvailability($eventTypeId, $date) {
        // Use V2 for availability
        return $this->v2Api->get('/slots/available', [...]);
    }
    
    public function createBooking($data) {
        // Use V1 for bookings
        return $this->v1Api->post('/bookings', [...]);
    }
}
```

### 3. **Full V2 Migration** (Not Recommended Yet)
**Challenges**:
- Missing event types endpoint
- Booking creation issues
- Requires database schema changes
- Risk of breaking changes

## Implementation Priority

1. **Immediate Actions**:
   - Fix V1 API authorization (use query parameter, not header)
   - Implement webhook signature verification
   - Add comprehensive error logging

2. **Short-term (1-2 weeks)**:
   - Create fallback mechanisms
   - Implement retry logic
   - Add monitoring for API health

3. **Long-term (1-3 months)**:
   - Monitor V2 API development
   - Plan gradual migration strategy
   - Maintain compatibility layer

## Test Files Created

1. `test-calcom-v2-comprehensive.php` - Full feature comparison
2. `test-calcom-v2-detailed.php` - Detailed endpoint testing
3. `test-calcom-v2-booking.php` - Booking format testing
4. `test-calcom-webhook-simple.php` - Webhook analysis

## Conclusion

Cal.com V2 API is not yet feature-complete for the application's requirements. The missing event types endpoint and booking creation issues make full migration impractical. Continue using V1 API with proper authorization fixes while monitoring V2 development for future migration opportunities.