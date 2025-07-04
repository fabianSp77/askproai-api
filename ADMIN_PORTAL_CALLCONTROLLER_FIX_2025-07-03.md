# Admin Portal CallController Fix - Nullable User

## Date: 2025-07-03

## Issue
`TypeError: CallController::getCallStatistics(): Argument #1 ($user) must be of type PortalUser, null given`

## Root Cause
The CallController methods were expecting a PortalUser object, but when admin is viewing the portal, `Auth::guard('portal')->user()` returns null.

## Solution
Made all user-dependent code nullable and added checks for admin viewing:

### 1. Made getCallStatistics accept nullable user
```php
private function getCallStatistics(?PortalUser $user)
```

### 2. Added null checks before using $user
```php
// Filter by assignment
if ($request->assigned_to === 'me' && $user) {
    // Only filter if user exists
}

// Permission checks
if (!session('is_admin_viewing') && $user && !$user->hasPermission('calls.view_all')) {
    // Apply filtering
}

// Team members check
if (session('is_admin_viewing') || ($user && $user->hasPermission('calls.edit_all'))) {
    // Load team members
}
```

### 3. Skip authorization for admin viewing
```php
// Check permissions (skip for admin viewing)
if (!session('is_admin_viewing')) {
    $this->authorizeViewCall($call, $user);
}
```

## Result
Admin can now access the calls page without authentication errors. All user-dependent functionality gracefully handles the null user case when admin is viewing.

## Query Analysis
From the error page queries:
- Company 15 phone numbers are loaded correctly
- Calls are being filtered by the correct company_id
- The issue was only with the type hint and null checks