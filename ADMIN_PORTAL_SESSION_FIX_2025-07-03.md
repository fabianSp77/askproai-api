# Admin Portal Session Fix - Complete Solution

## Date: 2025-07-03

## Problem
Admin clicking on "Astro AI" in Business Portal Admin gets redirected to "Krückeberg" portal instead.

## Root Causes Found
1. TenantScope was using admin's company_id instead of target company_id
2. setTrustedCompanyContext() only works in console, not web requests  
3. Session data might not persist properly between redirects

## Complete Fix Applied

### 1. TenantScope Priority (app/Scopes/TenantScope.php)
```php
private function getCurrentCompanyId(): ?string
{
    // Check admin impersonation FIRST
    if (session()->has('admin_impersonation')) {
        $adminImpersonation = session('admin_impersonation');
        if (isset($adminImpersonation['company_id'])) {
            return $adminImpersonation['company_id'];
        }
    }
    // ... other checks follow ...
}
```

### 2. Middleware Fix (app/Http/Middleware/PortalAuthenticate.php)
```php
// For admin viewing - use container binding instead of setTrustedCompanyContext
if (session('is_admin_viewing') && session('admin_impersonation')) {
    $adminImpersonation = session('admin_impersonation');
    if (isset($adminImpersonation['company_id'])) {
        app()->instance('current_company_id', $adminImpersonation['company_id']);
    }
    return $next($request);
}
```

### 3. Session Persistence (app/Http/Controllers/Portal/AdminAccessController.php)
```php
// Force session save before redirect
$this->createAdminPortalSession($admin, $company);
Session::save(); // IMPORTANT: Save session before redirect

// Also in createAdminPortalSession method:
Session::save(); // Save after setting all session data
```

### 4. Company Loading Fix (app/Http/Controllers/Portal/AdminAccessController.php)
```php
// Use withoutGlobalScope when loading company
$company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($tokenData['company_id']);
```

### 5. Debug Logging Added
- AdminAccessController logs session data before redirect
- DashboardController logs what company is loaded
- Helps track if session is lost between requests

## Why Previous Attempts Failed
1. Session might not auto-save before redirect
2. setTrustedCompanyContext has security check preventing web usage
3. Session cookie settings (same_site=strict) might affect cross-path redirects

## Testing Steps
1. Clear browser cookies/cache
2. Login as admin
3. Go to Business Portal Admin
4. Click on "Astro AI" 
5. Should see "AskProAI - Business Portal" not "Krückeberg"

## If Still Not Working
Check:
- `/storage/logs/laravel.log` for debug output
- Session driver configuration
- Cookie domain settings
- Whether a portal user is already logged in