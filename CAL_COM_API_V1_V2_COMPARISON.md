# Cal.com API v1 vs v2 Comprehensive Comparison

## Executive Summary

This document provides a detailed comparison between Cal.com API v1 and v2, based on the analysis of the askproai codebase and the existing migration plan. The system currently uses a hybrid approach due to authentication issues with v1 API.

## 1. Authentication Methods

### API v1
- **Method**: Query parameter authentication
- **Format**: `?apiKey=XXX` appended to URLs
- **Example**: `GET https://api.cal.com/v1/event-types?apiKey=cal_live_xxx`
- **Status**: Returns 403 Forbidden with current API key
- **Headers**: 
  ```
  Content-Type: application/json
  ```

### API v2
- **Method**: Bearer token authentication
- **Format**: Authorization header with Bearer token
- **Example**: `Authorization: Bearer cal_live_xxx`
- **Required Header**: `cal-api-version: 2024-08-13`
- **Status**: Works correctly with current API key
- **Headers**:
  ```
  Authorization: Bearer cal_live_xxx
  cal-api-version: 2024-08-13
  Content-Type: application/json
  ```

## 2. Endpoint Differences

### 2.1 Event Types Listing

#### V1 API
```
GET /v1/event-types?apiKey={key}
```
**Response Structure**:
```json
{
  "event_types": [
    {
      "id": 123,
      "title": "Service Name",
      "slug": "service-slug",
      "length": 30,
      "description": "Description"
    }
  ]
}
```

#### V2 API
```
GET /v2/event-types
Headers: Authorization: Bearer {key}, cal-api-version: 2024-08-13
```
**Response Structure**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 123,
      "title": "Service Name",
      "slug": "service-slug",
      "duration": 30,
      "description": "Description",
      "metadata": {}
    }
  ]
}
```

**Key Differences**:
- V2 wraps response in `data` array with `status` field
- V2 uses `duration` instead of `length`
- V2 includes additional `metadata` field

### 2.2 Availability Checking

#### V1 API
```
GET /v1/availability
Parameters:
  - apiKey: {key}
  - eventTypeId: {id}
  - dateFrom: YYYY-MM-DD
  - dateTo: YYYY-MM-DD
  - teamSlug: {slug} (optional)
```
**Response Structure**:
```json
{
  "busy": [],
  "slots": [
    {
      "time": "2024-01-10T09:00:00.000Z",
      "users": ["user-id"]
    }
  ]
}
```

#### V2 API
```
GET /v2/slots/available
Headers: Authorization: Bearer {key}, cal-api-version: 2024-08-13
Parameters:
  - eventTypeId: {id}
  - startTime: ISO8601 (e.g., 2024-01-10T00:00:00.000Z)
  - endTime: ISO8601 (e.g., 2024-01-10T23:59:59.999Z)
  - timeZone: {timezone} (e.g., Europe/Berlin)
```
**Response Structure**:
```json
{
  "status": "success",
  "data": {
    "slots": [
      {
        "start": "2024-01-10T09:00:00.000Z",
        "end": "2024-01-10T09:30:00.000Z",
        "available": true
      }
    ]
  }
}
```

**Key Differences**:
- V1 uses `dateFrom/dateTo` with date format, V2 uses `startTime/endTime` with ISO8601
- V2 requires explicit timezone parameter
- V2 response includes `start/end` times instead of single `time` field
- V2 has nested structure with `data.slots`

### 2.3 Booking Creation

#### V1 API
```
POST /v1/bookings?apiKey={key}
Body:
{
  "eventTypeId": 123,
  "start": "2024-01-10T09:00:00.000Z",
  "timeZone": "Europe/Berlin",
  "language": "de",
  "metadata": {
    "source": "askproai",
    "via": "phone_ai"
  },
  "responses": {
    "name": "Customer Name",
    "email": "customer@example.com",
    "location": "phone",
    "notes": "Additional notes"
  }
}
```

#### V2 API
```
POST /v2/bookings
Headers: Authorization: Bearer {key}, cal-api-version: 2024-08-13
Body:
{
  "eventTypeId": 123,
  "start": "2024-01-10T09:00:00.000Z",
  "attendee": {
    "name": "Customer Name",
    "email": "customer@example.com",
    "timeZone": "Europe/Berlin",
    "language": "de"
  },
  "metadata": {
    "source": "askproai",
    "via": "phone_ai"
  },
  "notes": "Additional notes"
}
```

**Key Differences**:
- V2 uses `attendee` object instead of `responses`
- V2 moves `timeZone` and `language` into `attendee` object
- V2 has `notes` at root level instead of in `responses`
- V2 doesn't have `location` field in same structure

### 2.4 Webhook Handling

#### V1 Webhooks
**Headers**:
- `X-Cal-Signature-256` or `Cal-Signature-256`
- Signature format: HMAC-SHA256 of raw body

**Payload Structure**:
```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2024-01-10T12:00:00.000Z",
  "payload": {
    "id": 123,
    "uid": "booking-uid",
    "eventTypeId": 456,
    "title": "Service Name",
    "startTime": "2024-01-10T09:00:00.000Z",
    "endTime": "2024-01-10T09:30:00.000Z",
    "attendees": [{
      "email": "customer@example.com",
      "name": "Customer Name"
    }]
  }
}
```

#### V2 Webhooks
**Headers**:
- Same signature headers as V1
- Additional: `cal-api-version: 2024-08-13`

**Payload Structure**:
```json
{
  "event": "booking.created",
  "createdAt": "2024-01-10T12:00:00.000Z",
  "data": {
    "id": 123,
    "uid": "booking-uid",
    "eventType": {
      "id": 456,
      "title": "Service Name"
    },
    "start": "2024-01-10T09:00:00.000Z",
    "end": "2024-01-10T09:30:00.000Z",
    "attendees": [{
      "email": "customer@example.com",
      "name": "Customer Name",
      "timeZone": "Europe/Berlin"
    }]
  }
}
```

**Key Differences**:
- V2 uses `event` instead of `triggerEvent` with dot notation
- V2 wraps payload in `data` object
- V2 uses `start/end` instead of `startTime/endTime`
- V2 includes more structured `eventType` object

## 3. Response Format Differences

### General Patterns

#### V1 Responses
- Direct data without wrapper
- Inconsistent error formats
- Status indicated by HTTP code only

#### V2 Responses
- Consistent wrapper structure:
  ```json
  {
    "status": "success|error",
    "data": {...} // or array
  }
  ```
- Standardized error format:
  ```json
  {
    "status": "error",
    "error": {
      "code": "ERROR_CODE",
      "message": "Human readable message"
    }
  }
  ```

## 4. Required Parameters

### Event Types
- **V1**: Only requires `apiKey`
- **V2**: Requires Bearer auth + `cal-api-version` header

### Availability
- **V1**: 
  - `eventTypeId` (required)
  - `dateFrom`, `dateTo` (required, date format)
  - `teamSlug` (optional)
- **V2**:
  - `eventTypeId` (required)
  - `startTime`, `endTime` (required, ISO8601)
  - `timeZone` (required)

### Booking
- **V1**:
  - `eventTypeId` (required)
  - `start` (required)
  - `responses.name`, `responses.email` (required)
  - `timeZone`, `language` (recommended)
- **V2**:
  - `eventTypeId` (required)
  - `start` (required)
  - `attendee.name`, `attendee.email` (required)
  - `attendee.timeZone` (required)

## 5. Known Issues and Limitations

### V1 API Issues
1. **Authentication**: Returns 403 with current API key
2. **Documentation**: Less comprehensive documentation
3. **Deprecation**: Being phased out by Cal.com
4. **Rate Limits**: Stricter rate limiting

### V2 API Issues
1. **Breaking Changes**: Not backward compatible with V1
2. **Documentation**: Still evolving, some endpoints undocumented
3. **Feature Parity**: Some V1 features may not have V2 equivalents
4. **Header Requirements**: Strict header requirements can cause issues

### Current Implementation Issues
1. **Hybrid Approach**: Using both V1 and V2 causes complexity
2. **Caching**: Different cache keys needed for V1 vs V2 responses
3. **Error Handling**: Different error formats require separate handlers
4. **Testing**: Need duplicate tests for both API versions

## 6. Migration Requirements

### Code Changes Required

1. **Authentication Update**:
   ```php
   // From V1:
   $url = $baseUrl . '/endpoint?apiKey=' . $apiKey;
   
   // To V2:
   Http::withHeaders([
       'Authorization' => 'Bearer ' . $apiKey,
       'cal-api-version' => '2024-08-13'
   ])->get($baseUrl . '/endpoint');
   ```

2. **Response Handling**:
   ```php
   // V1 response handling:
   $eventTypes = $response['event_types'];
   
   // V2 response handling:
   $eventTypes = $response['data'];
   ```

3. **Date Format Changes**:
   ```php
   // V1 date parameters:
   'dateFrom' => '2024-01-10',
   'dateTo' => '2024-01-17'
   
   // V2 date parameters:
   'startTime' => '2024-01-10T00:00:00.000Z',
   'endTime' => '2024-01-17T23:59:59.999Z'
   ```

4. **Booking Structure**:
   ```php
   // V1 booking:
   'responses' => [
       'name' => $name,
       'email' => $email
   ]
   
   // V2 booking:
   'attendee' => [
       'name' => $name,
       'email' => $email,
       'timeZone' => 'Europe/Berlin'
   ]
   ```

### Database Changes
1. Add `api_version` column to track which API version was used
2. Update webhook payload storage to handle both formats
3. Migration scripts to transform V1 data to V2 format

### Configuration Updates
1. Add V2-specific configuration values
2. Feature flags for gradual migration
3. Separate cache keys for V1/V2 responses

## 7. Recommendations

1. **Complete V2 Migration**: Move entirely to V2 API to avoid hybrid complexity
2. **Unified Service Layer**: Create abstraction layer to handle V1/V2 differences
3. **Comprehensive Testing**: Implement full test coverage for V2 endpoints
4. **Monitoring**: Add detailed logging for API version usage
5. **Gradual Rollout**: Use feature flags for safe migration
6. **Documentation**: Update all internal docs to reflect V2 usage

## 8. Timeline Estimate

Based on the current codebase analysis:
- **Week 1-2**: Complete V2 service implementation
- **Week 3**: Testing and bug fixes
- **Week 4**: Staging deployment and monitoring
- **Week 5**: Production rollout
- **Week 6**: Deprecate V1 code and cleanup

## Conclusion

The migration from Cal.com API v1 to v2 is necessary due to authentication issues, but requires significant changes in request structure, response handling, and authentication methods. The current hybrid approach works but adds complexity. A complete migration to V2 is recommended for long-term maintainability.