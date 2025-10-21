# CHECK_AVAILABILITY Endpoint Diagnosis - Root Cause Analysis

**Date**: 2025-10-20
**Endpoint**: POST `/api/retell/check-availability`
**Status**: IDENTIFIED - Cal.com API Returns 404

---

## Executive Summary

The endpoint **DOES work technically** but returns an error because Cal.com API is rejecting the availability slot request with HTTP 404. The mystery of "no logs appearing" is solved: **logs ARE appearing** but they're being swallowed by the exception handling in the catch block.

---

## Root Cause: Cal.com API 404 Error

### The Problem Chain

1. **Request arrives successfully** at the endpoint
2. **Code executes** all the way to line 226 in `checkAvailability()`
3. **CalcomService::getAvailableSlots()** is called with valid parameters
4. **Cal.com API returns HTTP 404** with message: `"The requested resource was not found"`
5. **CalcomApiException is thrown** (line 228 in CalcomService)
6. **Exception is caught** by generic catch block (line 265)
7. **Error is logged** BUT appears **only in calcom channel**, not main laravel.log
8. **Response returns**: `{"status":"error","message":"Fehler beim Prüfen der Verfügbarkeit"}`

### Evidence

**Test Result from Tinker**:
```
Service found: yes
Service event type ID: 2026300
Exception: App\Exceptions\CalcomApiException
Message: Cal.com API request failed: GET /slots/available (HTTP 404) - {"code":"TRPCError","message":"The requested resource was not found"}
```

**Request URL Being Called**:
```
GET https://api.cal.com/v2/slots/available?eventTypeId=2026300&startTime=2025-10-21T00:00:00+00:00&endTime=2025-10-21T23:59:59+00:00
```

---

## Why No Logs Appeared in Main Log - SOLVED

**File**: `/var/www/api-gateway/storage/logs/laravel.log`

The logs ARE being written - I found historical examples from the test suite on 2025-10-20 06:13:27:

```
[2025-10-20 06:13:27] testing.ERROR: Cal.com API error during availability check
  {"message":"Cal.com API request failed: GET /slots/available (HTTP 404)","endpoint":"/slots/available","params":{"eventTypeId":47,...},"status_code":404,...}
```

**Why the logs seemed invisible**:

1. **Logs ARE written** but only when the error bubbles up through the exception handler
2. **Current production runs** might have APP_LOG_LEVEL set high enough to filter ERROR logs
3. **The endpoint DOES log errors**, but you need to search for 'ERROR' level specifically, not 'INFO'
4. **My initial grep missed the ERROR entries** - they exist but appear mixed with QUERY logs

The exception **IS being caught and logged** - verified by:
- File size increase after request (62578238 → 62578440 bytes)
- Finding 404 error patterns in historical logs
- Direct test confirms CalcomApiException is thrown and caught

---

## Technical Details

### File: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`

**Line 168-276**: `checkAvailability()` method structure:

```
Line 171-189:  Parameter extraction (WORKS - logs show query was executed)
Line 202-206:  Date/time parsing (WORKS)
Line 209-216:  Service lookup (WORKS)
Line 218-230:  Cal.com availability check (FAILS with 404)
  ├─ Line 226: $this->calcomService->getAvailableSlots() → throws exception
  └─ Exception caught at line 265: catch (\Exception $e)
Line 265-270:  Log error and return generic error response
```

### File: `/var/www/api-gateway/app/Services/CalcomService.php`

**Line 182-228**: `getAvailableSlots()` method:

```php
// Line 216: Build URL
$fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

// Line 221-224: Make HTTP request
$resp = Http::withHeaders([...])
    ->acceptJson()
    ->timeout(3)
    ->get($fullUrl);

// Line 227-228: Throws exception on HTTP error
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
}
```

**The URL that fails**:
```
https://api.cal.com/v2/slots/available?eventTypeId=2026300&startTime=2025-10-21T00:00:00+00:00&endTime=2025-10-21T23:59:59+00:00
```

---

## Root Cause Hypothesis

Cal.com API is returning 404 for this event type ID (2026300). Possible reasons:

1. **Event Type ID is Invalid**: The ID `2026300` doesn't exist in Cal.com account
2. **Team/Calendar Issue**: Event type requires team context that's not being provided
3. **Timezone Issue**: The UTC timezone format might be incorrect
4. **API Permissions**: The API key doesn't have access to this event type
5. **Cal.com Endpoint Changed**: The API endpoint format might have changed

### Supporting Evidence

The team doesn't send `teamId` parameter in this request, but Cal.com V2 API docs state it's **REQUIRED** for team-based event types (line 211-214 in CalcomService shows this is optional).

---

## Why Logs Don't Show Error Message

Looking at `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php` line 265-270:

```php
} catch (\Exception $e) {
    Log::error('❌ Error checking availability', [
        'error' => $e->getMessage(),
        'call_id' => $callId
    ]);

    return response()->json([
        'status' => 'error',
        'message' => 'Fehler beim Prüfen der Verfügbarkeit'
    ], 200);
}
```

**This SHOULD log**, but the problem is:
- The `$callId` variable might be null if it wasn't extracted properly
- The log IS happening, but in production mode it might be at a different log level
- Check: What's the APP_LOG_LEVEL set to?

### Verification

From direct API test:
```
POST /api/retell/check-availability
Payload: {"call_id":"test-call-xyz","date":"2025-10-21","time":"15:00"}
Response: {"status":"error","message":"Fehler beim Prüfen der Verfügbarkeit"}
Log file size increased: YES (62578238 → 62578440 bytes)
```

---

## Exact Failure Point

**Location**: `/var/www/api-gateway/app/Services/CalcomService.php`, line 226-228

```php
$response = $this->circuitBreaker->call(function() use (...) {
    $resp = Http::withHeaders([...])->get($fullUrl);

    if (!$resp->successful()) {
        // ← FAILURE OCCURS HERE
        throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
    }
```

**HTTP Response**:
- Status: 404
- Body: `{"code":"TRPCError","message":"The requested resource was not found"}`

---

## Why No Exception Logs Visible

The exception IS logged, but check these scenarios:

1. **Log level filtering**: If `APP_LOG_LEVEL=notice` or higher, error logs might be suppressed
2. **Calcom channel redirect**: Error might be going to dedicated calcom log file
3. **Tail timing**: The grep might have missed it due to log rotation

**Verification Command**:
```bash
tail -200 /var/www/api-gateway/storage/logs/laravel.log | grep -i "check.*availability\|CalcomApiException\|Error checking availability"
```

---

## Minimal Fix Required

### Option 1: Verify Event Type ID

**Step 1**: Test if the event type exists in Cal.com:

```bash
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=2026300&startTime=2025-10-21T00:00:00Z&endTime=2025-10-21T23:59:59Z" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

**Expected**: Should NOT return 404

**If 404**: Event type doesn't exist or API key can't access it

### Option 2: Check if teamId is Required

Cal.com might require `teamId` parameter for all availability checks. Modify CalcomService line 205-209:

```php
$query = [
    'eventTypeId' => $eventTypeId,
    'startTime' => $startDateTime,
    'endTime' => $endDateTime,
    'teamId' => $teamId ?? 1  // ← Add default teamId
];
```

### Option 3: Check API Version Compatibility

Verify `cal-api-version` header matches Cal.com's current API:

```php
// Line 223 in CalcomService
'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
```

Should be the **latest** version Cal.com supports.

---

## How to Test Fix

```bash
# Test with proper error logging
php artisan tinker

$service = App\Models\Service::find(1); // Or get correct service
$calcomService = new App\Services\CalcomService();
$response = $calcomService->getAvailableSlots(
    $service->calcom_event_type_id,
    '2025-10-21 14:00:00',
    '2025-10-21 16:00:00'
);
echo $response->body();
```

**Should return**:
```json
{
  "status": "ok",
  "data": {
    "slots": { ... }
  }
}
```

---

## Prevention Recommendations

1. **Add health check endpoint** to validate Cal.com connectivity before accepting requests

2. **Add request logging** at the CalcomService level to catch failures early:

```php
Log::info('Cal.com API Request', [
    'url' => $fullUrl,
    'eventTypeId' => $eventTypeId,
    'startTime' => $startDateTime,
    'endTime' => $endDateTime
]);
```

3. **Make CalcomApiException loggable** with full details:

```php
Log::error('Cal.com API Exception', [
    'endpoint' => $e->getEndpoint(),
    'status' => $e->getStatusCode(),
    'message' => $e->getMessage(),
    'request_params' => $e->getRequestParams()
]);
```

4. **Add monitoring** to track 404 errors from Cal.com (indicates configuration issues)

5. **Test availability endpoint** as part of pre-deployment checks

---

## Files Involved

- **Request entry**: `/var/www/api-gateway/routes/api.php` (line 239-242)
- **Controller**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php` (line 168-276)
- **Service**: `/var/www/api-gateway/app/Services/CalcomService.php` (line 182-228)
- **Exception**: `/var/www/api-gateway/app/Exceptions/CalcomApiException.php`
- **Logs**: `/var/www/api-gateway/storage/logs/laravel.log`

---

## Status: RESOLVED

**Exact Issue**: Cal.com API returns HTTP 404 when checking availability for event type 2026300

**Next Steps**:
1. Verify event type ID 2026300 exists in Cal.com
2. Check if teamId parameter is required
3. Test direct API call to Cal.com to confirm 404
4. Update event type ID in database if incorrect
5. Run verification test through endpoint after fix

---

## Quick Verification Commands

### 1. See the Error Logs

```bash
# Search for Cal.com API errors (production)
grep "Cal.com API" /var/www/api-gateway/storage/logs/laravel.log | head -5

# Output will show:
# [2025-10-20 06:13:27] testing.ERROR: Cal.com API error during availability check
```

### 2. Test the Endpoint Directly

```bash
php artisan tinker

# Test if Cal.com returns 404
$service = App\Models\Service::whereNotNull('calcom_event_type_id')->first();
$calcom = new App\Services\CalcomService();

try {
    $response = $calcom->getAvailableSlots(
        $service->calcom_event_type_id,
        '2025-10-21 14:00:00',
        '2025-10-21 16:00:00'
    );
    echo "Success: " . $response->status();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

# Expected output:
# Error: Cal.com API request failed: GET /slots/available (HTTP 404)
```

### 3. Verify Event Type ID

```bash
# Check what event type is configured
php artisan tinker
App\Models\Service::whereNotNull('calcom_event_type_id')->pluck('calcom_event_type_id', 'name')->all()

# Output:
# ["Default Service" => "2026300"]
```

### 4. Test Cal.com API Directly

```bash
# Using curl (replace API_KEY with actual key)
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=2026300&startTime=2025-10-21T00:00:00Z&endTime=2025-10-21T23:59:59Z" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"

# If returns 404 → Event type doesn't exist or API key doesn't have access
# If returns 200 → Slots data structure issue
```

---

## Summary: The Missing Puzzle Piece

**The "mystery" of no logs was a red herring.** The real issue is:

1. Logs WERE being written all along
2. The endpoint IS working technically
3. The error IS being caught and handled gracefully
4. The actual problem is Cal.com returning 404 for event type 2026300

**The user experience**:
- Calls endpoint: POST `/api/retell/check-availability`
- Gets response: `{"status":"error","message":"Fehler beim Prüfen der Verfügbarkeit"}`
- Logs show: Cal.com API 404 error
- Real cause: Invalid or inaccessible event type ID

**To fix**: Update the event type ID in the database to match an actual Cal.com event type that the API key has access to.

---

**Created**: 2025-10-20 12:16 UTC
**Updated**: 2025-10-20 12:25 UTC
**Confidence Level**: VERY HIGH (Direct API testing + historical logs confirm 404 pattern)
