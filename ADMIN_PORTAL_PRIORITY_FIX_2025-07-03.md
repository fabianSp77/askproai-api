# Admin Portal Priority Fix - Final Solution

## Date: 2025-07-03

## Problem
Admin was still being redirected to wrong company because a portal user might be logged in with a "remember me" cookie.

## Solution: Admin Viewing Takes Priority

### Key Changes

1. **Logout existing portal user** (AdminAccessController)
```php
// IMPORTANT: Logout any existing portal user first
Auth::guard('portal')->logout();
```

2. **Check admin viewing FIRST** (DashboardController)
```php
public function index()
{
    // Check admin viewing FIRST - before checking portal user
    if (session('is_admin_viewing') && session('admin_impersonation')) {
        $adminImpersonation = session('admin_impersonation');
        if (isset($adminImpersonation['company_id'])) {
            // Admin is viewing - ignore any portal user login
            return $this->handleAdminViewing($adminImpersonation);
        }
    }
    
    // Normal portal user flow continues...
}
```

3. **Dedicated admin viewing handler**
```php
protected function handleAdminViewing($adminImpersonation)
{
    $company = Company::withoutGlobalScope(TenantScope::class)
        ->find($adminImpersonation['company_id']);
    
    // Create dummy user object with admin permissions
    // Load the correct company data
    // Return the view
}
```

## Why This Works

1. **Priority Order**: Admin viewing is checked BEFORE portal user authentication
2. **Force Logout**: Any existing portal user is logged out when admin accesses
3. **Session Persistence**: Explicit Session::save() ensures data persists
4. **No User Conflict**: Admin viewing doesn't rely on portal user authentication

## Complete Flow

1. Admin clicks "Portal öffnen" for Company 15 (Astro AI)
2. AdminAccessController:
   - Logs out any existing portal user
   - Sets admin_impersonation session with company_id = 15
   - Saves session explicitly
   - Redirects to dashboard
3. DashboardController:
   - Checks admin viewing FIRST
   - Loads Company 15 data
   - Shows correct portal

## Testing
Clear cookies and try again. The admin should now see the correct company portal.