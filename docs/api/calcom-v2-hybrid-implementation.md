# Cal.com V2 Hybrid Implementation Guide

## Overview

This document describes the hybrid Cal.com API implementation that intelligently routes between V1 and V2 APIs during the migration period. This approach ensures continuity of service while gradually transitioning to V2 before the V1 sunset date (December 31, 2025).

## Architecture

### Three-Service Pattern

```
┌─────────────────────────────────────────────────────────┐
│                    CalcomHybridService                   │
│                  (Intelligent Router)                    │
│                                                          │
│  - Routes V2-compatible operations to V2                │
│  - Falls back to V1 for unsupported features           │
│  - Tracks metrics and deprecation warnings             │
└────────────────┬───────────────────┬────────────────────┘
                 │                   │
     ┌───────────▼──────────┐   ┌───▼──────────────┐
     │   CalcomV2Service    │   │ CalcomService(V1) │
     │                      │   │                   │
     │ - Bookings (CRUD)    │   │ - Event Types     │
     │ - Cancellations      │   │ - Availability    │
     │ - Rescheduling       │   │ - Legacy Support  │
     └──────────────────────┘   └──────────────────┘
```

## Service Responsibilities

### CalcomHybridService
**Location:** `/app/Services/CalcomHybridService.php`

Primary orchestrator that:
- Routes requests to appropriate API version
- Provides fallback mechanisms
- Tracks usage metrics
- Logs deprecation warnings

### CalcomV2Service
**Location:** `/app/Services/CalcomV2Service.php`

Handles V2-specific operations:
- Booking creation/retrieval
- Booking cancellation
- Booking rescheduling
- Uses Bearer token authentication
- Requires `cal-api-version: 2024-08-13` header

### CalcomService (V1)
**Location:** `/app/Services/CalcomService.php`

Maintains V1 compatibility for:
- Event type management
- Availability checking
- Legacy booking formats
- Uses API key query parameter authentication

## Configuration

### Environment Variables

```env
# Base Configuration
CALCOM_API_KEY=cal_live_bd7aedbdf12085c5312c79ba73585920
CALCOM_USERNAME=askproai
CALCOM_ORGANIZATION_ID=77594

# V1 Configuration (Legacy)
CALCOM_BASE_URL=https://api.cal.com/v1

# V2 Configuration
CALCOM_V2_BASE_URL=https://api.cal.com/v2
CALCOM_V2_API_VERSION=2024-08-13

# Hybrid Mode Control
CALCOM_HYBRID_MODE=true  # Enable intelligent routing
```

### Service Configuration
**Location:** `/config/services.php`

```php
'calcom' => [
    'api_key'        => env('CALCOM_API_KEY'),
    'base_url'       => env('CALCOM_BASE_URL', 'https://api.cal.com/v1'),
    'v2_base_url'    => env('CALCOM_V2_BASE_URL', 'https://api.cal.com/v2'),
    'username'       => env('CALCOM_USERNAME', 'askproai'),
    'organization_id' => env('CALCOM_ORGANIZATION_ID', 77594),
    'v2_api_version' => env('CALCOM_V2_API_VERSION', '2024-08-13'),
    'hybrid_mode'    => env('CALCOM_HYBRID_MODE', true),
],
```

## API Usage Patterns

### Creating a Booking

```php
use App\Services\CalcomHybridService;

$service = new CalcomHybridService();

$bookingData = [
    'eventTypeId' => 2026979,
    'start' => '2025-09-15T14:00:00.000Z',
    'attendee' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de'
    ],
    'metadata' => ['source' => 'website'],
    'location' => ['type' => 'address', 'address' => 'Office']
];

// Automatically uses V2 with V1 fallback
$booking = $service->createBooking($bookingData);
```

### Checking Availability

```php
// Uses V1 API (V2 requires different structure)
$availability = $service->checkAvailability([
    'eventTypeId' => 2026979,
    'dateFrom' => '2025-09-15',
    'dateTo' => '2025-09-22',
    'timeZone' => 'Europe/Berlin'
]);
```

### Getting Event Types

```php
// Uses V1 API (V2 requires platform subscription)
$eventType = $service->getEventType(2026979);
```

## Migration Path

### Current State (September 2025)
- **V1 Operations:** Event types, availability
- **V2 Operations:** Bookings, cancellations, rescheduling
- **Hybrid Mode:** Enabled by default

### Migration Timeline

1. **Phase 1 (Complete):** Implement hybrid service architecture
2. **Phase 2 (Current):** Route booking operations to V2
3. **Phase 3 (Q4 2025):** Evaluate platform subscription for full V2
4. **Phase 4 (Before Dec 31, 2025):** Complete V2 migration

### Known Limitations

1. **V2 Event Type Access:** Requires platform subscription ($299/month)
2. **V2 Slots Endpoint:** Returns 404 without platform access
3. **V1 Deprecation:** Hard deadline December 31, 2025

## Monitoring & Metrics

### Usage Tracking

The hybrid service tracks API usage:

```php
$metrics = $service->getMetrics();
// Returns:
// [
//     'total_calls' => 100,
//     'v1_calls' => 30,
//     'v2_calls' => 70,
//     'v1_percentage' => 30.0,
//     'v2_percentage' => 70.0,
//     'errors' => 2,
//     'hybrid_mode' => true,
//     'deprecation_date' => '2025-12-31'
// ]
```

### Deprecation Warnings

The service logs warnings when V1 usage exceeds 50%:

```
[CalcomHybridService] ⚠️ High V1 API usage detected. Migration to V2 required before 2025-12-31
```

### Testing

Run the test suite to verify the implementation:

```bash
php scripts/test-calcom-hybrid.php
```

## Error Handling

### Authentication Errors

- **V1:** Returns 401 with invalid API key in query parameter
- **V2:** Returns 401 with invalid Bearer token

### Rate Limiting

Both V1 and V2 implement rate limiting:
- Standard: 100 requests per minute
- Implement exponential backoff on 429 responses

### Fallback Strategy

When V2 fails, the hybrid service:
1. Logs the V2 failure
2. Attempts V1 API call
3. Transforms response to V2 format
4. Returns unified response

## Security Considerations

1. **API Key Storage:** Store in `.env`, never commit
2. **Bearer Token:** Use for V2, include in Authorization header
3. **Version Header:** Always include `cal-api-version` for V2
4. **HTTPS Only:** Both V1 and V2 require HTTPS

## Troubleshooting

### Common Issues

1. **"Forbidden" errors on V1:**
   - Check API key validity
   - Verify username parameter for user-specific endpoints

2. **404 on V2 endpoints:**
   - Verify platform subscription status
   - Check organization/team context

3. **Authentication failures:**
   - V1: Check `apiKey` query parameter
   - V2: Check Bearer token format

### Debug Mode

Enable detailed logging:

```php
Log::channel('calcom')->debug('Detailed message');
```

## Support & Resources

- **Cal.com V2 Docs:** https://cal.com/docs/api-reference/v2
- **V1 Sunset Info:** https://cal.com/blog/v1-sunset
- **Platform Pricing:** https://cal.com/pricing

## Conclusion

The hybrid implementation provides a robust migration path from V1 to V2 while maintaining service continuity. Monitor V1 usage percentages and plan for complete V2 migration before the December 31, 2025 deadline.