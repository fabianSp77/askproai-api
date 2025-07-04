# Retell API 500 Error Fix

**Date**: 2025-06-26  
**Issue**: HTTP 500 errors when fetching calls from Retell API  
**Status**: ✅ FIXED

## Root Cause
The RetellV2Service was using incorrect API endpoints:
- ❌ Was using: `/list-calls` (v1 endpoint with GET)
- ❌ Also tried: `/v2/list-calls` (incorrect v2 prefix in path)
- ✅ Correct: `/v2/list-calls` with POST request

## Changes Made

### 1. Fixed API Endpoints in RetellV2Service.php
- `listCalls`: Changed from GET `/list-calls` to POST `/v2/list-calls`
- `createPhoneCall`: Fixed from `/v2/create-phone-call` to `/create-phone-call`
- `getCall`: Fixed from `/v2/get-call/{id}` to `/get-call/{id}`
- `getPhoneNumber`: Fixed from `/v2/get-phone-number/{id}` to `/get-phone-number/{id}`

### 2. Normalized Response Structure
The Retell API v2 returns calls in a `results` array, not `calls`. Updated to normalize:
```php
if (isset($data['results'])) {
    return ['calls' => $data['results']];
}
```

### 3. Fixed FetchRetellCallsJob
Updated to properly extract calls from the normalized response:
```php
$result = $retellService->listCalls(100);
$calls = $result['calls'] ?? [];
```

## Verification
Test script confirmed the fix works:
- ✅ API returns 200 status
- ✅ Calls are successfully retrieved
- ✅ No more 500 errors

## Impact
- Users can now successfully fetch calls from Retell
- The "Anrufe abrufen" button in admin panel works
- Background jobs to sync calls are operational