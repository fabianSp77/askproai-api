# Business Portal Authentication Fix - July 29, 2025

## Problem Summary

The Business Portal authentication was completely broken, preventing any portal users from logging in. All login attempts were failing with "Invalid credentials" errors.

## Root Cause

The `PortalUser` model uses the `BelongsToCompany` trait, which applies the `CompanyScope` global scope. When Laravel's authentication system tried to find users by email during login:

1. The `CompanyScope` was applied to the query
2. Since no user was authenticated yet, `getCompanyId()` returned null
3. CompanyScope then added `WHERE 0 = 1` to prevent data access without company context
4. This made it impossible to find ANY portal users during the authentication process

## How It Was Fixed

### 1. Created Custom User Provider
Created `/app/Auth/PortalUserProvider.php` that extends Laravel's `EloquentUserProvider` and overrides two key methods:
- `retrieveByCredentials()` - Uses `withoutGlobalScopes()` when finding users by email
- `retrieveById()` - Uses `withoutGlobalScopes()` when loading authenticated users

### 2. Registered Custom Provider
Updated `/app/Providers/AuthServiceProvider.php` to register the custom provider:
```php
$this->app['auth']->provider('portal_eloquent', function ($app, array $config) {
    return new \App\Auth\PortalUserProvider($app['hash'], $config['model']);
});
```

### 3. Updated Auth Configuration
Modified `/config/auth.php` to use the custom provider:
```php
'portal_users' => [
    'driver' => 'portal_eloquent',  // Changed from 'eloquent'
    'model'  => App\Models\PortalUser::class,
],
```

## Files Modified

1. `/app/Auth/PortalUserProvider.php` - NEW FILE
2. `/app/Providers/AuthServiceProvider.php` - Added custom provider registration
3. `/config/auth.php` - Changed portal_users provider driver

## Testing Results

✅ Authentication now works correctly with proper credentials
✅ Company context is properly set after authentication
✅ The CompanyScope security remains intact for all other operations
✅ No security vulnerabilities introduced - scope bypass only happens during authentication

## Important Notes

1. **Security**: The scope bypass ONLY happens during authentication. Once authenticated, all queries respect the CompanyScope
2. **Passwords**: Many demo users have "password" as their password, not variations of their username
3. **No Changes to Controllers**: The LoginController and AjaxLoginController already had `withoutGlobalScopes()` but that wasn't enough - the issue was at the Auth provider level

## Recommendations

1. Clear config cache after deployment: `php artisan config:clear`
2. Monitor auth logs to ensure users can login successfully
3. Consider documenting the correct passwords for demo/test users
4. This fix should be applied to any other models that use BelongsToCompany trait and need authentication