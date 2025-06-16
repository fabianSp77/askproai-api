# CallResource Redirect Issue - FIXED

## Problem
Users were being redirected to the dashboard when clicking on filters, pagination, or any interaction within the Calls page.

## Root Cause
The CallResource table had columns with URLs pointing to non-existent routes:
- `customer.name` column was linking to `CustomerResource::getUrl('view', ...)`
- `appointment.starts_at` column was linking to `AppointmentResource::getUrl('view', ...)`

However, AppointmentResource doesn't have a 'view' page defined in its `getPages()` method - it only has 'index', 'create', and 'edit' pages.

When Filament tried to generate these URLs during table rendering, it threw a `RouteNotFoundException`, which caused the framework to redirect to the dashboard as a fallback behavior.

## Solution
Changed all URL references from 'view' to 'edit':
- `CustomerResource::getUrl('view', ...)` → `CustomerResource::getUrl('edit', ...)`
- `AppointmentResource::getUrl('view', ...)` → `AppointmentResource::getUrl('edit', ...)`

This was done in both:
1. Table columns (lines 67, 155)
2. Infolist entries (lines 488, 495)

## Files Modified
- `/var/www/api-gateway/app/Filament/Admin/Resources/CallResource.php`

## Verification
After making these changes:
1. Clear all caches: `php artisan optimize:clear`
2. Reload PHP-FPM: `service php8.2-fpm reload`
3. Test the Calls page - filters, pagination, and column interactions should work without redirects

## Prevention
When adding URL columns to Filament tables, always verify that the target resource has the corresponding page defined in its `getPages()` method.