# Admin Portal Access Complete Fix - Summary

## Date: 2025-07-03

## Original Issue
When clicking on "Astro AI" (Company ID 15) in the Business Portal Admin, the user was redirected to "Krückeberg" (Company ID 1) instead. This occurred because the TenantScope was using the admin user's company_id instead of the target company_id from the session.

## Root Causes
1. TenantScope was not prioritizing admin impersonation session data
2. Portal controllers assumed an authenticated portal user always exists
3. Views directly accessed Auth::guard('portal')->user() without null checks
4. No session persistence after setting admin impersonation data

## Complete List of Fixes

### 1. TenantScope Priority Fix
**File**: `/app/Scopes/TenantScope.php`
- Added priority check for admin impersonation FIRST in getCurrentCompanyId()
- Session admin_impersonation.company_id now takes precedence over all other methods

### 2. PortalAuthenticate Middleware
**File**: `/app/Http/Middleware/PortalAuthenticate.php`
- Fixed "Cannot set company context from web request" error
- Changed from setTrustedCompanyContext() to app()->instance('current_company_id', $companyId)
- Added check for admin viewing to bypass normal authentication

### 3. AdminAccessController
**File**: `/app/Http/Controllers/Portal/AdminAccessController.php`
- Added Auth::guard('portal')->logout() before admin access
- Added Session::save() to persist session data before redirect
- Ensures clean state when admin views company portal

### 4. DashboardController
**File**: `/app/Http/Controllers/Portal/DashboardController.php`
- Added handleAdminViewing() method with anonymous class for dummy user
- Anonymous class implements hasPermission() method returning true for admin
- Check admin viewing FIRST before checking portal user

### 5. Layout Template
**File**: `/resources/views/portal/layouts/app.blade.php`
- Fixed "Attempt to read property 'name' on null" error
- Added conditional check for admin viewing vs portal user
- Shows "Admin Zugriff" when admin is viewing

### 6. CallController
**File**: `/app/Http/Controllers/Portal/CallController.php`
- Made getCallStatistics() accept nullable PortalUser
- Added admin viewing checks to skip permission validations
- Handle null user throughout the controller

### 7. BillingController
**File**: `/app/Http/Controllers/Portal/BillingController.php`
- Added company determination logic for admin viewing
- Pass company object to all views (index, topup, usage)
- Fixed prepareChartData() to handle null user
- Fixed downloadInvoice() to handle admin viewing
- Added admin warning in processTopup() preventing payment processing

### 8. TeamController
**File**: `/app/Http/Controllers/Portal/TeamController.php`
- Made all methods handle nullable user
- Added admin viewing checks throughout
- Fixed sendInvite() to use correct company_id
- Made getAvailableRoles() accept nullable user
- Fixed all self-edit/role-change validations for admin viewing

### 9. Billing Topup View
**File**: `/resources/views/portal/billing/topup.blade.php`
- Changed from Auth::guard('portal')->user()->company to $company
- Added admin warning message about payment restrictions

## Key Implementation Patterns

### 1. Company ID Determination
```php
if (session('is_admin_viewing')) {
    $companyId = session('admin_impersonation.company_id');
    $company = Company::findOrFail($companyId);
} else {
    $company = $user->company;
}
```

### 2. Permission Checks
```php
// Skip permission check for admin viewing
if (!session('is_admin_viewing')) {
    if (!$user || !$user->hasPermission('permission.name')) {
        abort(403);
    }
}
```

### 3. Nullable User Methods
```php
private function someMethod(?PortalUser $user)
{
    // Handle null user case
}
```

### 4. View Data
```php
'canManage' => session('is_admin_viewing') || ($user && $user->hasPermission('team.manage'))
```

## Session Structure
```php
session('is_admin_viewing') => true
session('admin_impersonation') => [
    'company_id' => 15,
    'company_name' => 'Astro AI'
]
session('admin_viewing_company') => 'Astro AI'
```

## Result
Admin can now successfully:
- Click on any company in Business Portal Admin
- View the correct company's portal (no more redirects to wrong company)
- Navigate through all portal pages without authentication errors
- View but not perform sensitive actions (payments, etc.)

## Security Considerations
- Admin can view all data but cannot process payments
- Admin viewing mode clearly indicated in UI
- Original portal user logged out before admin access
- All permission bypasses only apply when is_admin_viewing is true