# Portal Team View Null User Fix

## Date: 2025-07-03

## Issue
The portal team view was throwing an error "Attempt to read property 'id' on null" when accessing `/business/team`.

## Root Cause
When an admin is viewing the portal (impersonating), the `Auth::guard('portal')->user()` returns null because the admin is not authenticated with the portal guard. However, the view was trying to access `$currentUser->id` without checking if `$currentUser` is null.

## Solution

### 1. Updated Team View
Modified `/resources/views/portal/team/index.blade.php` line 100:

```php
// OLD
@if($canManage && $member->id !== $currentUser->id)

// NEW  
@if($canManage && (!$currentUser || $member->id !== $currentUser->id))
```

This change adds a null check for `$currentUser` before trying to access its `id` property.

## How Admin Viewing Works
1. Admin users can impersonate portal users via session flags
2. When `session('is_admin_viewing')` is true, the admin is viewing as a company
3. The portal guard doesn't authenticate the admin, so `Auth::guard('portal')->user()` returns null
4. Controllers handle this by checking the session flag and using `session('admin_impersonation.company_id')`

## Files Modified
- `/resources/views/portal/team/index.blade.php`

## Result
The team view now properly handles the case where an admin is viewing the portal, preventing the null pointer error.