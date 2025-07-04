# Admin Portal Layout Fix - Auth Guard Error

## Date: 2025-07-03

## Issue
`Attempt to read property "name" on null` error in portal layout when admin accesses portal, because `Auth::guard('portal')->user()` returns null.

## Root Cause
The portal layout template was trying to access properties of `Auth::guard('portal')->user()` without checking if a portal user is actually logged in. When admin is viewing, there is no portal user authenticated.

## Solution
Updated the portal layout (`resources/views/portal/layouts/app.blade.php`) to check for admin viewing mode:

### Desktop Menu
```blade
@if(session('is_admin_viewing'))
    Admin Access
@elseif(Auth::guard('portal')->check())
    {{ Auth::guard('portal')->user()->name }}
@else
    Guest
@endif
```

### Mobile Menu
```blade
@if(session('is_admin_viewing'))
    <div class="font-medium text-base text-gray-800">Admin Zugriff</div>
    <div class="font-medium text-sm text-gray-500">{{ session('admin_viewing_company') }}</div>
@elseif(Auth::guard('portal')->check())
    <div class="font-medium text-base text-gray-800">{{ Auth::guard('portal')->user()->name }}</div>
    <div class="font-medium text-sm text-gray-500">{{ Auth::guard('portal')->user()->email }}</div>
@endif
```

### Logout/Exit Options
- For admin viewing: Shows "Admin-Zugriff beenden" link
- For portal users: Shows normal logout form
- Settings link hidden during admin viewing

## Query Analysis
From the error page, I can confirm:
- Company 15 (AskProAI) is being loaded correctly
- Phone numbers for company 15 are fetched
- Statistics are calculated for company 15
- The issue was only with the template trying to access non-existent user

## Result
The portal layout now properly handles:
1. Admin viewing mode (no portal user)
2. Regular portal user mode
3. Guest mode (no authentication)

Admin can now successfully view the correct company portal without authentication errors.