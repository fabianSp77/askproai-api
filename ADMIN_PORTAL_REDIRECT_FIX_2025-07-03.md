# Admin Portal Redirect Fix - TenantScope Priority Issue

## Date: 2025-07-03

## Issue (GitHub #263, #264)
When clicking on "Astro AI" (Company ID 15) in the Business Portal Admin, the user was redirected to "Krückeberg" (Company ID 1) instead. This was happening because the TenantScope was using the admin user's company_id instead of the target company_id from the session.

## Root Cause
The TenantScope's `getCurrentCompanyId()` method was checking for authenticated user's company_id before checking the admin_impersonation session data. Since the admin user belongs to company_id 1, all queries were being filtered to show only data for company 1.

## Solution
Modified the TenantScope to prioritize admin impersonation session data over the authenticated user's company_id.

### Code Changes

#### /app/Scopes/TenantScope.php
Added admin impersonation check as the FIRST priority in `getCurrentCompanyId()`:

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
    
    // ... rest of the checks follow ...
}
```

## Flow Explanation
1. Admin clicks on "Astro AI" in Business Portal Admin
2. BusinessPortalAdmin generates a token with company_id = 15
3. Browser redirects to `/business/admin-access?token=...`
4. AdminAccessController validates token and sets session:
   - `admin_impersonation['company_id'] = 15`
   - `is_admin_viewing = true`
5. DashboardController loads company data
6. TenantScope now checks admin_impersonation FIRST and finds company_id = 15
7. All queries are now filtered by company_id = 15 (Astro AI)

## Testing
```bash
# Test script created: test-admin-impersonation.php
php test-admin-impersonation.php

# Output shows:
# TenantScope detected company ID: 15
```

## Result
Admins can now correctly view any company's portal without being redirected to their own company's data. The TenantScope respects the admin impersonation context throughout the session.

## Security Considerations
- Admin impersonation requires Super Admin role
- Session data is properly validated
- Original admin session is preserved
- Admin can exit impersonation mode to return to admin panel