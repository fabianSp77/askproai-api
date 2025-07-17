# Billing API User Type Fix

**Date**: 2025-07-16
**Issue**: TypeError in BillingApiController when admin users access topup endpoint
**Status**: ✅ FIXED

## Problem Description

When admin users accessed the billing topup endpoint through the business portal, a TypeError occurred:
```
TypeError: App\Services\StripeTopupService::createCheckoutSession(): Argument #3 ($user) must be of type App\Models\PortalUser, App\Models\User given
```

This happened because:
1. The `getCurrentUser()` method in `BaseApiController` can return either a `PortalUser` (portal guard) or a `User` (web guard for admins)
2. The `createCheckoutSession()` method in `StripeTopupService` strictly expects a `PortalUser` type
3. When admins view the business portal, they authenticate via the web guard, returning a `User` instance

## Solution Implemented

### 1. Type Checking in `topup()` method
```php
// Handle different user types
if ($user instanceof \App\Models\User && session('is_admin_viewing')) {
    // Admin is viewing - use the company's first active portal user
    $portalUser = PortalUser::where('company_id', $company->id)
        ->where('is_active', true)
        ->first();
        
    if (!$portalUser) {
        throw new \Exception('No active portal user found for this company');
    }
    
    $user = $portalUser;
} elseif (!($user instanceof PortalUser)) {
    throw new \Exception('Invalid user type for topup');
}
```

### 2. Similar fix in `updateAutoTopupSettings()` method
- Added `$activityUser` variable to handle admin viewing scenarios
- Ensures activity logs use appropriate user instance

### 3. Enhanced Error Logging
```php
\Log::error('Topup checkout session failed', [
    'user_id' => isset($user) && $user ? $user->id : null,
    'user_type' => isset($user) ? get_class($user) : 'null',
    'company_id' => $company->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

## Files Modified

1. `/app/Http/Controllers/Portal/Api/BillingApiController.php`
   - Modified `topup()` method (lines 294-351)
   - Modified `updateAutoTopupSettings()` method (lines 414-505)
   - Added proper user type handling for both methods

## Testing

To verify the fix works:

1. **As Portal User**:
   - Login to business portal as regular user
   - Navigate to billing section
   - Click "Guthaben aufladen"
   - Should redirect to Stripe checkout

2. **As Admin**:
   - Login to admin panel
   - Navigate to business portal admin view
   - Select a company to view
   - Click "Guthaben aufladen" 
   - Should redirect to Stripe checkout using company's portal user

## Impact

- Resolves immediate error for admin users accessing billing
- Maintains backward compatibility for portal users
- Provides better error messages for debugging
- Ensures activity logs work correctly for both user types

## Future Considerations

1. Consider refactoring `StripeTopupService::createCheckoutSession()` to accept a more generic user interface
2. Add unit tests for both user type scenarios
3. Review other methods that might have similar type restrictions