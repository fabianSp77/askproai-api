# URGENT FIX: Restore Missing Filament Resources

**Status**: 🔴 CRITICAL - 35/36 Resources Missing
**Cause**: Emergency override never reverted
**Fix Time**: 2 minutes
**Risk**: Low

---

## The Problem

**Visible**: Only CompanyResource shows in /admin sidebar
**Hidden**: 35 other resources (Appointments, Staff, Services, Customers, etc.)

**Why**:
Emergency override in `AdminPanelProvider.php` disabled resource discovery and only manually registered CompanyResource. The override was never reverted.

---

## The Fix

### Quick Fix (2 minutes)

**File**: `app/Providers/Filament/AdminPanelProvider.php`

**Change this** (lines 53-57):
```php
// Temporarily disabled to prevent badge errors - will register manually
// ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
->resources([
    // Manually register only working resources
    \App\Filament\Resources\CompanyResource::class,
])
```

**To this**:
```php
->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
```

### Commands to Run

```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan filament:cache-components

# 2. Test in browser
# Visit: https://api.askproai.de/admin
# Expected: All resources visible in sidebar
```

---

## Secondary Issue: Auth Guard Mismatch

**Also found**: Admin panel uses 'admin' guard, but admin user has 'web' guard role

**Impact**: May cause auth issues after restoring resources

**Fix** (if auth issues appear):

```bash
php artisan tinker
```

```php
// Create admin guard role
$role = \Spatie\Permission\Models\Role::create([
    'name' => 'super_admin',
    'guard_name' => 'admin'
]);

// Assign to admin user
$admin = \App\Models\User::find(1);
$admin->assignRole($role);
```

**Or simpler**: Change panel back to 'web' guard:

```php
// In AdminPanelProvider.php line 34
// Remove this line:
->authGuard('admin')
```

---

## Verification Checklist

After applying fix:

- [ ] Edit `AdminPanelProvider.php` (uncomment discovery, remove manual array)
- [ ] Run `php artisan cache:clear`
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan filament:cache-components`
- [ ] Visit `/admin` in browser
- [ ] Verify all resources appear in sidebar:
  - [ ] Appointments
  - [ ] Staff
  - [ ] Services
  - [ ] Customers
  - [ ] Branches
  - [ ] Users
  - [ ] Roles
  - [ ] Calls
  - [ ] (and 27+ more)

---

## If Badge Errors Return

The original override was to "prevent badge errors". If errors return:

**Symptoms**:
- 500 errors on /admin load
- Errors mentioning "navigation badge"
- Memory exhaustion errors

**Quick Fix**:
```php
// In specific Resource files showing errors
public static function getNavigationBadge(): ?string
{
    return null; // Disable badge temporarily
}
```

**Proper Fix**:
- Use cached badge queries (many Resources already have this)
- Fix underlying query performance
- See: `app/Filament/Concerns/HasCachedNavigationBadge.php`

---

## Files to Edit

1. `app/Providers/Filament/AdminPanelProvider.php` - Uncomment discovery (line 53)

That's it. One file, one change.

---

## Why This Happened

1. Badge errors caused emergency override (unknown date)
2. Discovery disabled, manual registration added
3. Only CompanyResource tested and added to manual array
4. Other 35 resources never added back
5. No tracking ticket for temporary override
6. Override comment says "will register manually" but never completed

**Lesson**: Emergency overrides need tracking tickets with revert dates.

---

## Full Analysis

See: `RCA_MISSING_RESOURCES_AUTH_GUARD_MISMATCH.md`

**Generated**: 2025-10-27
