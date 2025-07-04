# Admin Portal TeamController Fix - Nullable User

## Date: 2025-07-03

## Issue
`Call to a member function hasPermission() on null` error in TeamController when accessing as admin.

## Root Cause
The TeamController was trying to call `hasPermission()` on a null user when admin is viewing.

## Solution
Made all permission checks nullable and added admin viewing checks throughout the controller:

### 1. Permission checks skip for admin viewing
```php
// Skip permission check for admin viewing
if (!session('is_admin_viewing')) {
    if (!$user || !$user->hasPermission('team.view')) {
        abort(403);
    }
}
```

### 2. Company ID determination
```php
// Ensure user is from same company
if (session('is_admin_viewing')) {
    $companyId = session('admin_impersonation.company_id');
} else {
    $companyId = $currentUser->company_id;
}
```

### 3. Made getAvailableRoles accept nullable user
```php
private function getAvailableRoles(?PortalUser $user): array
{
    // Admin viewing gets all roles
    if (session('is_admin_viewing') || !$user) {
        return PortalUser::ROLES;
    }
    // ... rest of logic
}
```

### 4. Updated view data
```php
'canManage' => session('is_admin_viewing') || ($user && $user->hasPermission('team.manage'))
```

## Result
Admin can now access the team management pages without authentication errors. All team-related functionality handles the null user case when admin is viewing.

## Methods Updated
- index()
- showInviteForm()
- sendInvite()
- updateUser()
- deactivateUser()
- reactivateUser()
- resetPassword()
- getAvailableRoles()