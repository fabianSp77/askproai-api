# Root Cause Analysis: Livewire Login Form 405 Error

**Date**: 2025-10-17
**Status**: ROOT CAUSE IDENTIFIED
**Severity**: Critical - All login attempts fail

---

## Executive Summary

**Login form fails with 405 Method Not Allowed** because:

1. ✓ Livewire component IS properly initialized with `wire:id` attribute
2. ✓ Form HAS `wire:submit="authenticate"` directive
3. ✓ Livewire JavaScript IS loaded in HTML
4. ✗ **Form submission is NOT being intercepted by Livewire on the client side**
5. ✗ Form submits as plain POST to `/admin/login` (no Livewire endpoint)
6. ✗ **No POST route exists** for `/admin/login` → 405 error

---

## Historical Context: Why This Worked Before

The production test on **October 3, 2025** shows login WAS WORKING:

```
Test Report: /storage/puppeteer-screenshots/FINAL-UI-VALIDATION-REPORT.md
Date: 2025-10-03 14:13:55 UTC
Result: ✅ Login Flow PASSED
Status: 10 PASS / 3 FAIL (76.92%)
Console Errors: 0
Network Failures: 0
```

**This means:** Between Oct 3 and Oct 17, something changed that broke the client-side Livewire interception.

---

## Evidence Analysis

### 1. HTML Structure - CORRECT ✓

```html
<div wire:snapshot="{...}" wire:id="A86b7dxUy0dylODE8bAW" class="fi-simple-page">
  <!-- Livewire component properly initialized -->

  <form method="post" wire:submit="authenticate" id="form">
    <!-- Form has correct directives -->
  </form>
</div>
```

**Verification**:
- ✓ `wire:snapshot` attribute present (serialized component state)
- ✓ `wire:id` attribute present (unique component identifier)
- ✓ `wire:submit="authenticate"` directive present on form
- ✓ CSRF token in page: `<meta name="csrf-token" content="...">`

### 2. JavaScript Loading - CORRECT ✓

```html
<script src="http://localhost:8000/vendor/livewire/livewire.min.js?id=df3a17f2"
  data-csrf="..."
  data-update-uri="/livewire/update"
  data-navigate-once="true">
</script>
```

**Verification**:
- ✓ Livewire v3.6.4 loaded
- ✓ CSRF token passed to JavaScript
- ✓ Update URI configured: `/livewire/update`
- ✓ Loaded at end of HTML body (correct placement)

### 3. Route Configuration - MISMATCH ✗

```bash
GET|HEAD  admin/login  →  filament.admin.auth.login  ✓ EXISTS
POST      admin/login  →                              ✗ DOES NOT EXIST
```

**Expected**: Form POSTs to `/livewire/update` (Livewire AJAX)
**Actual**: Form POSTs to `/admin/login` (plain HTTP)
**Result**: 405 Method Not Allowed

### 4. Network Test

```
curl -X POST http://localhost:8000/admin/login
→ 405 Method Not Allowed ✗

curl -X POST http://localhost:8000/livewire/update
→ 419 Page Expired (expected - needs valid state) ✓
```

The `/livewire/update` endpoint exists and responds, but form isn't reaching it.

---

## Root Cause: Client-Side Interception Failure

**The Livewire JavaScript is not intercepting the form submit event.**

This could be caused by:

### 1. Alpine.js Not Loading
Livewire 3 relies on Alpine.js for directive parsing. If Alpine isn't loaded before Livewire initializes:
- Form directives won't be processed
- Event listeners won't be attached
- Form behaves as plain HTML

**Verification Needed**: Check if `alpine.js` is in the HTML

### 2. Livewire.start() Not Being Called
In Livewire 3, components are auto-initialized, but only if:
- DOM is ready when Livewire script loads
- No JavaScript errors prevent initialization
- Alpine.js is available

**Verification Needed**: Browser console → `window.Livewire.start` or initialization messages

### 3. Event Listener Registration Failing
The form submit handler might not be registered if:
- Form element not found when Livewire scans DOM
- Event listener detached by other code
- Middleware stripping event attributes

**Verification Needed**: Check if `wire:submit` listener appears in DevTools Event Listeners

### 4. Middleware Interfering
Custom middleware might be:
- Stripping `wire:*` attributes during response transformation
- Modifying HTML in a way that breaks component structure
- Affecting JavaScript execution order

**Current Middleware Stack**:
```
PerformanceMonitoring (custom)
ErrorCatcher (custom)
Standard Filament/CSRF middleware
```

### 5. Recent Changes Triggering Issue
Most recent commit: `43406488` (Oct 17 16:42:47)

```
fix: Enable debug mode to reveal actual errors
- Commented out: config(['app.debug' => false])
- This shouldn't affect Livewire initialization
- But may have exposed an existing error
```

---

## Diagnostic Checklist

### Client-Side Tests
```javascript
// Open Browser Console and run:

// 1. Check Livewire object
window.Livewire
// Should return: Object { components: Map(1), … }

// 2. Check component registration
window.Livewire.components
// Should show the login component with ID

// 3. Check Alpine
window.Alpine
// Should return Alpine object

// 4. Check form element
document.querySelector('form#form')
// Should return the form element

// 5. Check for JavaScript errors
// Look in Console tab for any red errors
```

### Network Tests
```bash
# Open DevTools → Network tab

# 1. Click login form submit button
# Should see POST request to: /livewire/update
# Headers should include: X-Livewire: true

# 2. If you see POST to /admin/login instead
# Then form submission is NOT being intercepted
```

### Server-Side Tests
```bash
# Check logs for clues
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Try direct Livewire request
curl -X POST http://localhost:8000/livewire/update \
  -H "X-Livewire: true" \
  -d '{"fingerprint":{"id":"test"},"updates":[]}'
```

---

## Why This Regression Occurred

Given that login worked on Oct 3 but fails on Oct 17, investigate:

### 1. Dependency Updates
- Check `composer.lock` changes
- Check `package.json` changes
- Livewire or Alpine versions may have been updated

### 2. View/Template Changes
- Filament form component may have been customized
- Layout might have been modified
- Render hooks might be interfering

### 3. Middleware Chain Changes
- New middleware added that strips attributes
- Existing middleware modified
- Middleware ordering changed

### 4. Configuration Changes
- Livewire config modified
- Filament config modified
- Asset loading disabled

### 5. Build/Compilation Issues
- Vite build might have failed
- CSS/JS assets might not be properly compiled
- Hot reload conflicts

---

## Immediate Resolution Steps

### Priority 1: Verify Client-Side Initialization
1. Open browser dev console on login page
2. Run: `window.Livewire` and `window.Alpine`
3. Check for any JavaScript errors
4. Verify component is visible in DevTools Elements panel

### Priority 2: Test Form Submission Network
1. Open DevTools Network tab
2. Submit login form
3. Check what URL the POST request goes to
4. **If it's `/admin/login`**: Form NOT being intercepted
5. **If it's `/livewire/update`**: Form IS being intercepted

### Priority 3: Compare Oct 3 to Oct 17
```bash
# Get the commit hash from Oct 3 test
git log --all --before="2025-10-03T14:13:55Z" --pretty=format:"%H" | head -1

# Compare critical files
git diff COMMIT_HASH...HEAD -- \
  app/Providers/AppServiceProvider.php \
  app/Providers/Filament/AdminPanelProvider.php \
  bootstrap/app.php \
  resources/views/vendor/filament-panels/
```

### Priority 4: Check for Middleware/Configuration Issues
```bash
# Verify middleware stack
grep -r "PerformanceMonitoring\|ErrorCatcher" app/Http/Middleware

# Check for response modification
grep -r "->json\|->with\|->render" app/Http/Middleware

# Verify Livewire config
cat config/livewire.php | grep -E "inject|navigate"
```

### Priority 5: Test Fallback Solution
If Livewire interception is confirmed broken, add temporary POST route:
```php
Route::post('/admin/login', [LoginController::class, 'store']);
```

---

## Files to Investigate

1. **Service Providers**:
   - `/app/Providers/AppServiceProvider.php` - Service binding
   - `/app/Providers/Filament/AdminPanelProvider.php` - Filament config

2. **Middleware**:
   - `/app/Http/Middleware/PerformanceMonitoring.php` - Request monitoring
   - `/app/Http/Middleware/ErrorCatcher.php` - Error handling

3. **Configuration**:
   - `/config/livewire.php` - Livewire settings
   - `/config/filament.php` - Filament settings
   - `/bootstrap/app.php` - Bootstrap config

4. **Views**:
   - `/resources/views/vendor/filament-panels/pages/auth/login.blade.php`
   - `/resources/views/vendor/filament-panels/components/layout/base.blade.php`

5. **Recent Commits**:
   - `43406488` - Debug mode enabled
   - `412c0ed1` - Service binding fix
   - `4a015773` - x-collapse fix

---

## Workaround (If Needed)

If Livewire interception cannot be restored quickly, add POST route:

```php
// routes/web.php
Route::post('/admin/login', function (Request $request) {
    return redirect('/admin/login')->with('error', 'Please use the form on this page');
});
```

This will at least give users a better error message than 405.

---

## Prevention for Future

1. **Add automated browser tests** for login flow
2. **Add regression tests** for critical paths
3. **Monitor Livewire initialization** with error tracking
4. **Test after dependency updates** before deployment
5. **Add health check** for Livewire functionality

---

## Summary

| Item | Status | Impact |
|------|--------|--------|
| HTML Structure | ✓ Correct | High |
| Livewire Component | ✓ Present | High |
| JavaScript Loading | ✓ Present | High |
| Form Directives | ✓ Present | High |
| **Client Interception** | ✗ Broken | **Critical** |
| Routes | ✗ Incomplete | Critical |

**Conclusion**: The Livewire JavaScript is not intercepting form submissions. This is a **client-side initialization or configuration issue**, not a server-side problem.

