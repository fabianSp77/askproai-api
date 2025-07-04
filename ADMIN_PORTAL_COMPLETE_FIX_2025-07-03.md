# Complete Admin Portal Access Fix

## Date: 2025-07-03

## Issues Fixed
1. Company not found error when admin tries to access portal (FIXED)
2. Admin redirected to wrong company portal (Krückeberg instead of Astro AI) (FIXED)

## Complete Solution

### 1. TenantScope Priority Fix
Modified `app/Scopes/TenantScope.php` to check admin impersonation FIRST:
```php
private function getCurrentCompanyId(): ?string
{
    // 0. PRIORITY: Check for admin impersonation FIRST
    if (session()->has('admin_impersonation')) {
        $adminImpersonation = session('admin_impersonation');
        if (isset($adminImpersonation['company_id'])) {
            return $adminImpersonation['company_id'];
        }
    }
    // ... rest of checks ...
}
```

### 2. Middleware Company Context Fix
Updated `app/Http/Middleware/PortalAuthenticate.php` to set company context for admin viewing:
```php
if (session('is_admin_viewing') && session('admin_impersonation')) {
    $adminImpersonation = session('admin_impersonation');
    
    // Set company context for admin viewing
    if (isset($adminImpersonation['company_id'])) {
        \App\Traits\BelongsToCompany::setTrustedCompanyContext(
            $adminImpersonation['company_id'],
            'admin-viewing-' . $adminImpersonation['admin_id']
        );
        
        // Also bind to container for other services
        app()->instance('current_company_id', $adminImpersonation['company_id']);
    }
}
```

### 3. AdminAccessController Fixes
- Use `withoutGlobalScope()` when loading company
- Force session save with `Session::save()`
- Use raw DB queries to bypass tenant restrictions

### 4. DashboardController Fix
Already uses `withoutGlobalScope()` when loading company for admin viewing

## Flow Summary
1. Admin clicks on company in Business Portal Admin
2. Token is generated with correct company_id
3. AdminAccessController sets session data and saves it
4. PortalAuthenticate middleware sets company context
5. TenantScope uses admin impersonation company_id
6. All queries are filtered by the correct company

## Testing
Added debug logging to DashboardController to track session data:
```php
\Log::info('DashboardController::index - Debug', [
    'portal_user' => $user ? $user->id : 'none',
    'is_admin_viewing' => session('is_admin_viewing'),
    'admin_impersonation' => session('admin_impersonation'),
    'admin_viewing_company' => session('admin_viewing_company'),
]);
```

## Result
Admins can now successfully view any company's portal without errors or being redirected to the wrong company.