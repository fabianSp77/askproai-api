# Admin Access Fix - Multi-Tenant Context Issue

## Date: 2025-07-03

## Issue
Admin users were getting a "RuntimeException: Attempted to create record for different company. Access denied." error when trying to access the business portal for a specific company.

## Root Cause
The multi-tenant security system has multiple layers:
1. `TenantScope` - Global scope that filters queries by company_id
2. `BelongsToCompany` trait - Model events that prevent cross-tenant data creation

Even when using `withoutGlobalScope()`, the tenant scope was still being applied in the SQL query, and the BelongsToCompany trait was blocking record creation.

## Error Details
```
RuntimeException
Attempted to create record for different company. Access denied.
at app/Traits/BelongsToCompany.php:44
```

The error occurred in `createAdminPortalSession` when trying to create a PortalUser record for admin access.

## Solution (Final)
After trying to bypass the scopes and traits, the most reliable solution was to use raw database queries that completely bypass the Eloquent model system:

1. **Use raw DB queries**: Bypass all Eloquent features including scopes, traits, and model events
2. **Direct table operations**: Use `DB::table()` for inserts and updates
3. **Manual JSON encoding**: Encode the permissions array manually since we're not using model casts

## Code Changes

### /app/Http/Controllers/Portal/AdminAccessController.php

```php
// Use raw DB queries to bypass all scopes and model events
$email = 'admin+' . $company->id . '@askproai.de';

// Check if portal user exists
$existingUser = \DB::table('portal_users')
    ->where('email', $email)
    ->where('company_id', $company->id)
    ->first();

if (!$existingUser) {
    // Create new portal user using raw insert
    $userId = \DB::table('portal_users')->insertGetId([
        'email' => $email,
        'company_id' => $company->id,
        'name' => 'Admin Access',
        'password' => bcrypt(bin2hex(random_bytes(32))),
        'role' => 'admin',
        'permissions' => json_encode([
            'full_access' => true,
            // ... other permissions
        ]),
        'is_active' => true,
        'is_admin_access' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $portalUser = (object) ['id' => $userId];
} else {
    $portalUser = $existingUser;
}

// Update last login using raw query
\DB::table('portal_users')
    ->where('id', $portalUser->id)
    ->update(['last_login_at' => now()]);
```

## Why Previous Solutions Failed
1. **`withoutGlobalScope()`** - The scope was still being applied in the query
2. **Temporary company context** - The BelongsToCompany trait still blocked the operation
3. **Model events** - Even with bypassed scopes, the creating/updating events still fired

## Security Considerations
- Raw queries bypass all security checks, so we must be careful
- Only used for admin access which requires super admin privileges
- The created portal user has `is_admin_access` flag for audit purposes
- All operations are still logged in the database query log

## Testing
After this fix, admins should be able to:
1. Access the Business Portal Admin page in Filament
2. Click on any company to view their portal
3. Successfully create an admin session without errors
4. View the company's portal data with proper filtering