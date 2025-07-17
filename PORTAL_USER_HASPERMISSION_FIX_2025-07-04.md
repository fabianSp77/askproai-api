# Portal User hasPermission Fix - 2025-07-04

## Problem
The error "Call to a member function hasPermission() on null" occurred when admins were viewing the business portal without being logged in as a portal user.

## Root Cause
When admins access the portal via the admin panel's "View Business Portal" feature:
1. The system sets `session('is_admin_viewing')` to true
2. No actual portal user is logged in (`Auth::guard('portal')->user()` returns null)
3. Various controllers and views were calling `hasPermission()` on the null user object

## Files Fixed

### 1. `/app/Http/Controllers/Portal/CallController.php`
Fixed multiple instances where `hasPermission()` was called without null checks:
- Line 248: Added null check for `$user->hasPermission('calls.edit_all')`
- Line 321: Added null check for `$user->hasPermission('calls.edit_all')`
- Line 449, 635: Added null check for `$user->hasPermission('calls.export')`
- Line 522: Added null check for `$user->hasPermission('calls.view_all')`
- Line 545: Added null check for `$user->hasPermission('billing.view')`
- Line 592: Added null check for `$user->hasPermission('calls.view_all')`
- Line 620: Added null check for `$user->hasPermission('calls.edit_all')`
- Line 672: Fixed direct `Auth::guard('portal')->user()->hasPermission()` call
- Line 721: Fixed direct `Auth::guard('portal')->user()->hasPermission()` call

### 2. `/app/Http/Controllers/Portal/BillingController.php`
- Line 105: Added null check for `$user->hasPermission('billing.pay')`

## Solution Pattern
Changed all occurrences from:
```php
if (!session('is_admin_viewing') && !$user->hasPermission('permission.name')) {
```

To:
```php
if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('permission.name'))) {
```

And from:
```php
if (session('is_admin_viewing') || $user->hasPermission('permission.name')) {
```

To:
```php
if (session('is_admin_viewing') || ($user && $user->hasPermission('permission.name'))) {
```

## Other Notes
- The views (Blade templates) were already correctly checking for user existence before calling `hasPermission()`
- The `DashboardController` creates a mock user object for admin viewing that includes the `hasPermission()` method
- The `PortalAuthenticate` middleware correctly allows admin viewing without a portal user
- The `PortalPermission` middleware correctly checks for user existence

## Testing
After applying these fixes:
1. Clear all caches: `php artisan optimize:clear`
2. Test admin viewing: Access portal from admin panel
3. Test normal portal user: Login directly to portal
4. Both scenarios should work without errors