# Retell Webhook Fix Summary - 2025-07-02

## Problem
- Test calls weren't showing live during calls
- Duplicate calls were being created (3 calls for 1 actual call)
- Calls stayed in "in_progress" status and never changed to "ended"
- Phone numbers were showing as "unknown"

## Root Cause
The Call model uses TWO global scopes:
1. `TenantScope` - which was being removed with `withoutGlobalScope(TenantScope::class)`
2. `CompanyScope` - from the `BelongsToCompany` trait - which was NOT being removed

This caused the webhook controller to not find existing calls during `call_ended` events, leading to:
- Attempting to create duplicate calls
- Calls never being updated from "in_progress" to "ended"
- Missing call data (transcript, duration, etc.)

## Solution
Updated `RetellWebhookWorkingController.php` to remove BOTH global scopes when looking up calls:

```php
$existingCall = Call::withoutGlobalScope(TenantScope::class)
    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->where('call_id', $callId)
    ->first();
```

## Files Modified
- `/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookWorkingController.php`

## Test Results
✅ `call_started` creates new calls correctly
✅ `call_ended` updates existing calls (no duplicates)
✅ Phone numbers are captured properly
✅ Call status transitions from "in_progress" to "ended"
✅ Duration and transcript are saved correctly
✅ No duplicate calls are created

## Webhook Configuration
The working webhook endpoint is: `https://api.askproai.de/api/retell/webhook-simple`

## Next Steps
1. Monitor the next real test call to ensure live display works
2. Verify no duplicate calls are created in production
3. Consider consolidating the two global scopes into one for simplicity