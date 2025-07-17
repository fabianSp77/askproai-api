# CSRF Token Mismatch Analysis Report

## Executive Summary

The CSRF token mismatch issue in the Laravel application is caused by conflicting middleware configurations and the interaction between Sanctum's stateful authentication and the custom middleware designed to bypass CSRF for admin API routes.

## Root Cause Analysis

### 1. **Sanctum's EnsureFrontendRequestsAreStateful Middleware**

Located in `bootstrap/app.php` (line 103), this middleware is applied to ALL API routes:

```php
$middleware->api(prepend: [
    'throttle:api',
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    // ...
]);
```

This middleware (found in `vendor/laravel/sanctum/src/Http/Middleware/EnsureFrontendRequestsAreStateful.php`) does the following:
- Checks if the request is from a "stateful" domain (lines 73-92)
- If yes, it applies session-based middleware including **VerifyCsrfToken** (line 54)
- The stateful domains are configured in `config/sanctum.php` and include `localhost`, `api.askproai.de`, etc.

### 2. **Middleware Execution Order Problem**

In `app/Http/Kernel.php`, there are multiple middleware attempting to bypass CSRF:

```php
protected $middleware = [
    \App\Http\Middleware\BypassSanctumForAdminAPI::class, // Line 16
    \App\Http\Middleware\DisableAllMiddlewareForAdminAPI::class, // Line 17
    \App\Http\Middleware\AdminTokenAuth::class, // Line 18
    // ...
];
```

However, these middleware run BEFORE the API middleware group is applied. When the request reaches the API routes, Sanctum's middleware re-adds CSRF verification.

### 3. **The VerifyCsrfToken Exception Not Working**

Although `app/Http/Middleware/VerifyCsrfToken.php` has exceptions for `api/admin/*`:

```php
protected $except = [
    'api/admin/*',
    // ...
];
```

This doesn't work because:
1. Sanctum uses Laravel's base `\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken` class (line 54 in EnsureFrontendRequestsAreStateful.php)
2. The custom VerifyCsrfToken class with exceptions is only used in the 'web' middleware group

### 4. **Configuration Conflict**

The Sanctum configuration in `config/sanctum.php` specifies:

```php
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
],
```

This hardcodes the use of Laravel's base CSRF validation class, not the custom one with exceptions.

## Why Current Bypass Attempts Fail

1. **DisableAllMiddlewareForAdminAPI** attempts to remove session and CSRF, but it runs too early
2. **BypassSanctumForAdminAPI** tries to clear stateful domains, but Sanctum's middleware has already been registered
3. The custom **VerifyCsrfToken** with exceptions is never used by Sanctum

## Solutions

### Solution 1: Update Sanctum Configuration (Recommended)

Update `config/sanctum.php` to use the custom VerifyCsrfToken:

```php
'middleware' => [
    'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
    'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class, // Use custom class
],
```

### Solution 2: Create Separate API Middleware Group

Create a new middleware group in `bootstrap/app.php` specifically for admin API:

```php
$middleware->group('admin-api', [
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    // Do NOT include EnsureFrontendRequestsAreStateful
]);
```

Then update `routes/api.php` to use this group:

```php
Route::prefix('admin')
    ->middleware('admin-api') // Use custom group
    ->group(base_path('routes/api-admin.php'));
```

### Solution 3: Override Sanctum's Middleware

Create a custom middleware that extends EnsureFrontendRequestsAreStateful and override the `fromFrontend` method to exclude admin API routes:

```php
class CustomEnsureFrontendRequestsAreStateful extends EnsureFrontendRequestsAreStateful
{
    public static function fromFrontend($request)
    {
        // Skip stateful check for admin API
        if ($request->is('api/admin/*')) {
            return false;
        }
        
        return parent::fromFrontend($request);
    }
}
```

### Solution 4: Use Token-Only Authentication

Ensure admin API requests don't send cookies or referrer headers that trigger stateful authentication:

```javascript
// In React admin app
fetch('/api/admin/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        // Don't send cookies
    },
    credentials: 'omit', // Important: don't include cookies
    body: JSON.stringify(data)
});
```

## Immediate Fix

The quickest fix is to update the Sanctum configuration:

```bash
# Edit config/sanctum.php
sed -i "s/'validate_csrf_token' => Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ValidateCsrfToken::class,/'validate_csrf_token' => App\\\\Http\\\\Middleware\\\\VerifyCsrfToken::class,/" config/sanctum.php

# Clear config cache
php artisan config:cache
```

## Testing

After implementing the fix, test with:

```bash
# Should work without CSRF token
curl -X POST https://api.askproai.de/api/admin/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@askproai.de","password":"password"}'
```

## Conclusion

The CSRF mismatch occurs because Sanctum's middleware applies its own CSRF validation that doesn't respect the custom exceptions. The issue can be resolved by either configuring Sanctum to use the custom VerifyCsrfToken class or by creating a separate middleware group for admin API routes that doesn't include Sanctum's stateful middleware.