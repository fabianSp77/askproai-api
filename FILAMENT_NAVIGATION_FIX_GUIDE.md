# Filament Navigation Visibility - Quick Fix Guide
**Date**: 2025-11-14
**Estimated Time**: 30 minutes
**Difficulty**: Easy (Search & Replace)

---

## Problem Summary

34 out of 37 Filament resources are hidden from navigation due to **authentication guard mismatch**.

**Root Cause**: Resources check `auth()->guard('admin')` but Filament panel uses `authGuard('web')`.

---

## Quick Fix (3 Steps)

### Step 1: Find All Broken Resources (2 minutes)

```bash
cd /var/www/api-gateway
grep -r "auth()->guard('admin')" app/Filament/Resources/ --include="*.php"
```

**Expected Output**:
```
app/Filament/Resources/CompanyResource.php:51:        $user = auth()->guard('admin')->user();
app/Filament/Resources/CompanyResource.php:57:        $user = auth()->guard('admin')->user();
app/Filament/Resources/CompanyResource.php:63:        $user = auth()->guard('admin')->user();
...
```

---

### Step 2: Fix CompanyResource (10 minutes)

**File**: `/var/www/api-gateway/app/Filament/Resources/CompanyResource.php`

#### Option A: Quick Fix (Remove Custom Methods)
**Best Practice**: Let Filament use policies automatically.

**Search for** (Lines 49-101):
```php
public static function canViewAny(): bool
{
    $user = auth()->guard('admin')->user();
    return $user && $user->can('viewAny', static::getModel());
}

public static function canCreate(): bool
{
    $user = auth()->guard('admin')->user();
    return $user && $user->can('create', static::getModel());
}

// ... and all other can*() methods ...
```

**Delete All Custom Authorization Methods** (Lines 49-101):
- `canViewAny()`
- `canCreate()`
- `canEdit()`
- `canDelete()`
- `canDeleteAny()`
- `canForceDelete()`
- `canForceDeleteAny()`
- `canRestore()`
- `canRestoreAny()`

**Why**: Filament automatically uses policies when these methods don't exist.

#### Option B: Fix Guard References (If You Need Custom Logic)
**Only use this if you have custom logic beyond policy checks.**

**Find & Replace**:
```php
// BEFORE:
$user = auth()->guard('admin')->user();
return $user && $user->can('viewAny', static::getModel());

// AFTER:
return auth()->check() && auth()->user()->can('viewAny', static::getModel());
```

---

### Step 3: Apply Fix to All Resources (15 minutes)

**Option A: Automated Fix (Recommended)**
```bash
# Find all resources with auth()->guard('admin')
cd /var/www/api-gateway

# Search pattern
grep -rl "auth()->guard('admin')" app/Filament/Resources/ --include="*.php"

# For each file found, open and delete custom can*() methods
```

**Option B: Manual Search & Replace**
1. Open each file found in Step 1
2. Delete all custom `can*()` methods that use `auth()->guard('admin')`
3. Save file

---

### Step 4: Test (5 minutes)

```bash
# 1. Clear Filament cache
php artisan filament:cache-clear

# 2. Clear application cache
php artisan cache:clear

# 3. Login as super_admin and check navigation
```

**Expected Result**:
- ✅ CompanyResource visible
- ✅ CustomerResource visible
- ✅ StaffResource visible
- ✅ AppointmentResource visible
- ✅ ServiceResource visible
- ✅ CallResource visible
- ✅ UserResource visible
- ✅ PhoneNumberResource visible

---

## Verification Commands

```bash
# Check if guard references still exist
grep -r "auth()->guard('admin')" app/Filament/Resources/ --include="*.php"

# Should return EMPTY or only comments

# Check navigation count (after login)
php artisan tinker
>>> \App\Filament\Resources\CompanyResource::canViewAny()
# Should return: true (if logged in as super_admin)
```

---

## Rollback Plan

If something breaks:

```bash
# Restore from git
git checkout app/Filament/Resources/CompanyResource.php

# Or restore specific methods
# (Copy from FILAMENT_NAVIGATION_VISIBILITY_RCA_2025-11-14.md)
```

---

## Example: Before & After

### BEFORE (Broken)
```php
public static function canViewAny(): bool
{
    $user = auth()->guard('admin')->user(); // ❌ Returns NULL
    return $user && $user->can('viewAny', static::getModel());
}

public static function canCreate(): bool
{
    $user = auth()->guard('admin')->user();
    return $user && $user->can('create', static::getModel());
}

// ... 7 more methods ...
```

### AFTER (Fixed - Option A: Recommended)
```php
// ✅ DELETED - Filament uses CompanyPolicy automatically
// No custom methods needed
```

### AFTER (Fixed - Option B: Custom Logic)
```php
public static function canViewAny(): bool
{
    return auth()->check() && auth()->user()->can('viewAny', static::getModel());
}

public static function canCreate(): bool
{
    return auth()->check() && auth()->user()->can('create', static::getModel());
}

// ... fix all other methods ...
```

---

## Files to Fix

### Confirmed Broken
- ✅ `/app/Filament/Resources/CompanyResource.php` (Lines 49-101)

### Potentially Broken (Check with grep)
- ❓ Other resources using `auth()->guard('admin')`

---

## Testing Checklist

### After Fix
- [ ] Login as super_admin
- [ ] Navigate to `/admin`
- [ ] Verify "Companies" in navigation
- [ ] Click "Companies" → should show list
- [ ] Verify "Customers" in navigation
- [ ] Verify "Staff" in navigation
- [ ] Verify "Appointments" in navigation
- [ ] Verify "Services" in navigation
- [ ] Verify "Calls" in navigation
- [ ] Verify "Users" in navigation
- [ ] Verify "Phone Numbers" in navigation

### Role-Based Testing (Optional)
- [ ] Login as admin → verify appropriate access
- [ ] Login as manager → verify appropriate access
- [ ] Login as staff → verify appropriate access

---

## Common Issues & Solutions

### Issue 1: Still Can't See Resources
**Solution**: Clear all caches
```bash
php artisan filament:cache-clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue 2: Policy Not Found Error
**Solution**: Check policy exists and is registered
```bash
# Check policy exists
ls -la app/Policies/CompanyPolicy.php

# Register in AuthServiceProvider if needed
# app/Providers/AuthServiceProvider.php
protected $policies = [
    \App\Models\Company::class => \App\Models\Policies\CompanyPolicy::class,
];
```

### Issue 3: 403 Forbidden After Fix
**Solution**: Check policy returns true
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'admin@example.com')->first();
>>> $user->hasRole('super_admin');
# Should return: true

>>> app(\App\Policies\CompanyPolicy::class)->viewAny($user);
# Should return: true
```

---

## Prevention

### Code Review Checklist
- [ ] Never use `auth()->guard('admin')` in Filament resources
- [ ] Always use `auth()` or `auth()->guard('web')`
- [ ] Prefer deleting custom `can*()` methods (let Filament use policies)
- [ ] Document auth guard in project README

### CI/CD Check (Optional)
Add to CI pipeline:
```bash
# Fail build if auth()->guard('admin') found in Resources
! grep -r "auth()->guard('admin')" app/Filament/Resources/ --include="*.php"
```

---

## Need Help?

**Full Analysis**: See `FILAMENT_NAVIGATION_VISIBILITY_RCA_2025-11-14.md`

**Questions**:
1. Why is this happening? → See RCA document Section "Root Causes Identified"
2. Which files need fixing? → Run grep command in Step 1
3. Will this break anything? → No, it fixes broken authorization
4. Do I need to update policies? → No, policies are correct

---

**Document Version**: 1.0
**Last Updated**: 2025-11-14
**Status**: READY TO IMPLEMENT
