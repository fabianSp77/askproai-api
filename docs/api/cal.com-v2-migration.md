# Cal.com API v1 → v2 Migration Guide

## Overview
This guide documents the migration from Cal.com API v1 to v2, including authentication changes and header requirements.

## Key Changes

### 1. Authentication
- **v1:** Query parameter authentication (`?apiKey=cal_live_xyz`)
- **v2:** Bearer token authentication (`Authorization: Bearer cal_live_xyz`)

### 2. API Version Header
All v2 API calls require the `cal-api-version` header:
```
cal-api-version: 2025-01-07
```

### 3. Base URL Update
- **v1:** `https://api.cal.com/v1/`
- **v2:** `https://api.cal.com/v2/`

## Environment Configuration

### Updated .env Variables
```env
# Cal.com Integration - API v2 (Bearer Auth)
CALCOM_API_KEY=cal_live_your_api_key_here
CALCOM_BASE_URL=https://api.cal.com/v2
```

## Code Examples

### v2 Booking Request (cURL)
```bash
curl -X POST "https://api.cal.com/v2/bookings" \
  -H "Content-Type: application/json" \
  -H "cal-api-version: 2025-01-07" \
  -H "Authorization: Bearer cal_live_your_api_key_here" \
  -d '{
    "eventTypeId": 123456,
    "start": "2025-08-14T10:00:00.000Z",
    "end": "2025-08-14T11:00:00.000Z",
    "timeZone": "Europe/Berlin",
    "responses": {
      "name": "John Doe",
      "email": "john@example.com"
    }
  }'
```

### v2 PHP Implementation
```php
$apiKey = config('services.calcom.api_key');
$ch = curl_init("https://api.cal.com/v2/bookings");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'cal-api-version: 2025-01-07',
    'Authorization: Bearer ' . $apiKey
]);
$response = curl_exec($ch);
```

## Migration Checklist

- [x] Update CalComController.php to use v2 endpoint
- [x] Add Bearer token authentication
- [x] Add cal-api-version header
- [x] Update .env.example files
- [x] Update configuration documentation
- [ ] Test v2 API calls with staging environment
- [ ] Update any additional API integrations

## Security Improvements

### v2 Benefits
- Bearer token authentication follows OAuth 2.0 standards
- No API key exposure in URL parameters
- Enhanced security through HTTP headers
- Better audit logging capabilities

## Testing v2 Migration

```bash
# Test booking endpoint
curl -X POST "https://api.cal.com/v2/bookings" \
  -H "Content-Type: application/json" \
  -H "cal-api-version: 2025-01-07" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -d @test-booking.json

# Expected Response: 200 OK with booking details
```

## Rollback Plan
If issues arise, revert by:
1. Change base URL back to v1 in .env
2. Remove Bearer authentication headers
3. Add apiKey query parameter back to requests

## Support Resources
- Cal.com API v2 Documentation: https://cal.com/docs/api-reference
- Migration Support: https://cal.com/docs/api-reference/migration-guide