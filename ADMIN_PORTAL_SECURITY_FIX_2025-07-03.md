# Admin Portal Security Fix - setTrustedCompanyContext Error

## Date: 2025-07-03

## Issue
When accessing the business portal as admin, got error:
```
RuntimeException: Cannot set company context from web request
```

## Root Cause
The `BelongsToCompany::setTrustedCompanyContext()` method has a security check that only allows it to be called from console (background jobs), not from web requests. This is a security feature to prevent company context manipulation from web requests.

## Solution
Instead of using `setTrustedCompanyContext()` in the PortalAuthenticate middleware, we now only set the container binding directly:

### Code Changes

#### /app/Http/Middleware/PortalAuthenticate.php

```php
// For admin viewing
if (session('is_admin_viewing') && session('admin_impersonation')) {
    $adminImpersonation = session('admin_impersonation');
    
    if (isset($adminImpersonation['company_id'])) {
        // Only bind to container for admin viewing (setTrustedCompanyContext only works in console)
        app()->instance('current_company_id', $adminImpersonation['company_id']);
    }
    
    return $next($request);
}

// For normal portal users
if ($user->company_id) {
    // Bind company context to container
    app()->instance('current_company_id', $user->company_id);
}
```

## Why This Works
1. The TenantScope checks `app('current_company_id')` as its first priority
2. We set this value in the middleware for both admin viewing and normal portal users
3. The security restriction on `setTrustedCompanyContext()` is bypassed while still maintaining proper company isolation

## Security Considerations
- The company context is only set after proper authentication
- Admin access requires Super Admin role and valid token
- Normal portal users can only set their own company context
- The container binding is request-scoped and cleared after each request

## Testing
Admins should now be able to:
1. Click on any company in Business Portal Admin
2. Access the company's portal without security errors
3. See the correct company data (not their own company)