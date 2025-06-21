# Cal.com V1 to V2 API Migration Mapping

## Overview
This document provides a complete mapping of Cal.com V1 API methods to their V2 equivalents, including implementation status and migration notes.

## API Method Mapping

### 1. Check Availability

#### V1 Implementation
```php
// CalcomService.php
public function checkAvailability($eventTypeId, $dateFrom, $dateTo)
{
    $response = Http::get($this->baseUrl . '/availability', [
        'apiKey' => $this->apiKey,
        'eventTypeId' => $eventTypeId,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'teamSlug' => $this->teamSlug
    ]);
}
```

#### V2 Implementation
```php
// CalcomV2Service.php
public function getSlots(int $eventTypeId, string $startDate, string $endDate, string $timeZone = 'Europe/Berlin'): array
{
    $response = $this->client->get("/slots/available", [
        'eventTypeId' => $eventTypeId,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'timeZone' => $timeZone,
    ]);
}
```

**Migration Notes:**
- ✅ Already implemented in V2
- Parameter name changes: `dateFrom` → `startDate`, `dateTo` → `endDate`
- Added timezone parameter (defaults to Europe/Berlin)
- No longer needs teamSlug parameter

### 2. Book Appointment

#### V1 Implementation
```php
// CalcomService.php
public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
{
    $bookingData = [
        'eventTypeId' => $eventTypeId,
        'start' => $startTime,
        'end' => $endTime,
        'name' => $customerData['name'],
        'email' => $customerData['email'],
        'notes' => $notes,
        'metadata' => ['phone' => $customerData['phone']]
    ];
    
    $response = Http::post($this->baseUrl . '/bookings?apiKey=' . $this->apiKey, $bookingData);
}
```

#### V2 Implementation
```php
// CalcomV2Service.php
public function createBooking(array $bookingData): array
{
    $response = $this->client->post("/bookings", [
        'eventTypeId' => $bookingData['eventTypeId'],
        'start' => $bookingData['start'],
        'attendee' => [
            'name' => $bookingData['name'],
            'email' => $bookingData['email'],
            'timeZone' => $bookingData['timeZone'] ?? 'Europe/Berlin',
        ],
        'meetingUrl' => $bookingData['meetingUrl'] ?? null,
        'metadata' => $bookingData['metadata'] ?? [],
    ]);
}
```

**Migration Notes:**
- ✅ Already implemented in V2
- Structure change: customer data now under `attendee` object
- Phone number moved to notes or metadata
- End time calculated automatically from event type duration

### 3. Get Event Types

#### V1 Implementation
```php
// CalcomService.php
public function getEventTypes($companyId = null)
{
    $response = Http::get($this->baseUrl . '/event-types', [
        'apiKey' => $this->apiKey,
        'teamSlug' => $this->teamSlug
    ]);
}
```

#### V2 Implementation
```php
// CalcomV2Service.php
public function getEventTypes(?string $teamSlug = null): array
{
    $params = [];
    if ($teamSlug) {
        $params['teamSlug'] = $teamSlug;
    }
    
    $response = $this->client->get("/event-types", $params);
}
```

**Migration Notes:**
- ✅ Already implemented in V2
- Team slug is now optional parameter
- Returns more detailed event type information

### 4. Get Bookings

#### V1 Implementation
```php
// CalcomService.php
public function getBookings($params = [])
{
    $params['apiKey'] = $this->apiKey;
    $response = Http::get($this->baseUrl . '/bookings', $params);
}
```

#### V2 Implementation
```php
// CalcomV2Service.php
public function getBookings(array $filters = []): array
{
    $params = array_merge([
        'take' => 100,
        'status' => 'upcoming',
    ], $filters);
    
    $response = $this->client->get("/bookings", $params);
}
```

**Migration Notes:**
- ✅ Already implemented in V2
- Pagination parameters: `take` and `skip`
- Status filter options: upcoming, past, cancelled, unconfirmed

### 5. Cancel Booking

#### V1 Implementation
```php
// CalcomService.php
public function cancelBooking($bookingId, $reason = null)
{
    $response = Http::delete($this->baseUrl . '/bookings/' . $bookingId . '?apiKey=' . $this->apiKey, [
        'reason' => $reason
    ]);
}
```

#### V2 Implementation
```php
// CalcomV2Service.php
public function cancelBooking(int $bookingUid, string $reason = null): array
{
    $data = [];
    if ($reason) {
        $data['cancellationReason'] = $reason;
    }
    
    $response = $this->client->delete("/bookings/{$bookingUid}/cancel", $data);
}
```

**Migration Notes:**
- ✅ Already implemented in V2
- Endpoint change: `/bookings/{id}` → `/bookings/{uid}/cancel`
- Parameter name change: `reason` → `cancellationReason`

## Services Using Cal.com API

### Already Migrated to V2
- ✅ `CalcomV2Service` - Main V2 client
- ✅ `CalcomCalendarService` - Using V2 for most operations
- ✅ `AppointmentBookingService` - Using V2 for bookings
- ✅ `HybridBookingController` - Mixed V1/V2 (availability V1, booking V2)

### Still Using V1
- ⚠️ `CalcomEnhancedIntegration` - Uses V1 checkAvailability
- ⚠️ `ImportCalcomEventTypes` command - Uses V1 getEventTypes
- ⚠️ `OnboardingWizard` - Uses V1 getEventTypes

### Services Calling Non-Existent Methods
These services are calling methods not in CalcomService, likely already using V2:
- `CalendarProviders/CalcomProvider.php` - calls createBooking()
- `EnhancedBookingService.php` - calls createBooking()
- `AppointmentService.php` - calls rescheduleBooking()

## Migration Checklist

### Phase 1: Update Remaining V1 Usage
- [ ] Update `CalcomEnhancedIntegration` to use V2 availability check
- [ ] Update `ImportCalcomEventTypes` command to use V2
- [ ] Update `OnboardingWizard` to use V2 event types
- [ ] Update `HybridBookingController` to use V2 for availability

### Phase 2: Implement Backwards Compatibility
- [ ] Create `CalcomBackwardsCompatibility` trait
- [ ] Add deprecation warnings to V1 methods
- [ ] Log V1 usage for monitoring

### Phase 3: Remove V1 Code
- [ ] Remove CalcomService.php (already marked for deletion)
- [ ] Remove CalcomService_v1_only.php
- [ ] Update all imports to use CalcomV2Service

## Response Format Differences

### V1 Availability Response
```json
{
  "busy": [
    {
      "start": "2024-01-15T10:00:00Z",
      "end": "2024-01-15T11:00:00Z"
    }
  ],
  "timeZone": "Europe/Berlin"
}
```

### V2 Availability Response
```json
{
  "data": {
    "slots": [
      "2024-01-15T09:00:00Z",
      "2024-01-15T11:00:00Z",
      "2024-01-15T14:00:00Z"
    ]
  }
}
```

### V1 Booking Response
```json
{
  "id": 12345,
  "uid": "abc123",
  "title": "Meeting with John Doe",
  "startTime": "2024-01-15T10:00:00Z",
  "endTime": "2024-01-15T10:30:00Z"
}
```

### V2 Booking Response
```json
{
  "data": {
    "id": "12345",
    "uid": "abc123",
    "title": "Meeting with John Doe",
    "start": "2024-01-15T10:00:00Z",
    "end": "2024-01-15T10:30:00Z",
    "status": "accepted",
    "attendees": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "timeZone": "Europe/Berlin"
      }
    ]
  }
}
```

## Environment Variables

### V1 Configuration
```env
CALCOM_API_KEY=your_api_key
CALCOM_TEAM_SLUG=askproai
```

### V2 Configuration
```env
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxx
DEFAULT_CALCOM_TEAM_SLUG=askproai
CALCOM_API_VERSION=2024-08-13
```

## Testing Migration

### Unit Tests
```bash
php artisan test tests/Unit/CalcomV2ServiceTest.php
```

### Integration Tests
```bash
php artisan test tests/Feature/CalcomV2IntegrationTest.php
```

### Manual Testing
```bash
# Test V2 endpoints
php artisan tinker
>>> $service = new \App\Services\CalcomV2Service();
>>> $service->getEventTypes('askproai');
>>> $service->getSlots(1234, '2024-01-15', '2024-01-20');
```

---
*Last Updated: 2025-06-17*