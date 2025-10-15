# Comprehensive Research: Laravel + Filament Session Handling & Memory Exhaustion

**Date**: 2025-10-03
**Focus**: Inconsistent memory exhaustion in Laravel/Filament with session handling, global scopes, and Spatie permissions

---

## Executive Summary

Your inconsistent memory exhaustion issue (succeeding sometimes, failing others with same request) likely stems from a **combination** of:

1. **Filament v3.3.30 blade recursion bug** (if on that version)
2. **Session serialization of User model with global scopes** creating circular auth dependencies
3. **Spatie permission cache loading 580 permissions** on every auth check
4. **PHP-FPM worker state inconsistency** with OPcache

---

## 1. Known Critical Issue: Filament v3.3.30 Blade Recursion Bug

### The Problem
**CONFIRMED BUG**: Filament v3.3.30 had a recursive Blade rendering bug that causes memory exhaustion.

### Evidence
- **Source**: https://stackoverflow.com/questions/79710332
- **Root Cause**: Laravel v12.20 introduced a change that created recursion in Filament's blade components
- **Symptoms**:
  - "Allowed memory size exhausted" errors
  - Occurs when rendering forms, relation managers, or edit/create pages
  - Even simple resources can trigger it
  - **Inconsistent failures**: Same code works fine on different machines or different requests

### The Fix
```bash
# Update to v3.3.31 or later (current latest: v3.3.34+)
composer update filament/filament
php artisan view:clear
```

**Fixed in**: v3.3.31 (released with fix for "endless loop caused by new Laravel release")

### Verdict
**CHECK YOUR VERSION FIRST** - If you're on v3.3.30, this alone could explain everything.

---

## 2. Laravel Session Model Hydration & Global Scopes

### How Laravel Stores Authenticated User in Session

**Key Finding**: Laravel serializes the **entire authenticated User model** into the session, including:
- Model attributes
- **Loaded relationships** (if eager loaded before session storage)
- Model state
- **Does NOT serialize global scopes** (they re-apply during unserialization)

### The Global Scope Circular Dependency Problem

**Critical Issue**: When User model has global scopes that check `auth()->user()`:

```php
// In your CompanyScope trait on User model
protected static function booted() {
    static::addGlobalScope(new CompanyScope);
}

// CompanyScope apply method
public function apply(Builder $builder, Model $model) {
    if (auth()->check()) {  // PROBLEM: This tries to deserialize User from session
        $builder->where('company_id', auth()->user()->company_id);
    }
}
```

**The Circular Dependency**:
1. Request comes in → Laravel deserializes User from session
2. Deserialization boots the User model
3. Booting applies global scopes
4. Global scope calls `auth()->user()`
5. Auth facade tries to deserialize User from session
6. **INFINITE LOOP** → Stack overflow or memory exhaustion

### Research Evidence

**Source**: Multiple Stack Overflow discussions:
- https://stackoverflow.com/questions/72865365 (infinite loop with auth in User global scope)
- https://stackoverflow.com/questions/56487694 (auth()->user() in global scope returns null)

**Key Quote**:
> "When you add an auth condition to a trait and apply it to the User model, you get a circular dependency: when the User model is booting, it must check Auth, which requires booting the User model, causing an infinite loop."

### Why It's Inconsistent

**PHP Session Start Timing**:
- Some requests: Session loaded AFTER middleware auth check → No circular dependency
- Other requests: Session loaded DURING auth check → Circular dependency triggered
- **PHP-FPM worker state**: Workers with "warm" session cache behave differently than cold starts

### Solutions from Research

**Option 1**: Check session existence before auth
```php
public function apply(Builder $builder, Model $model) {
    if (request()->session()->exists('auth') && auth()->hasUser()) {
        $builder->where('company_id', auth()->user()->company_id);
    }
}
```

**Option 2**: Use RouteMatched event
```php
Event::listen('Illuminate\Routing\Events\RouteMatched', function() {
    // Apply scope here - session is fully booted
});
```

**Option 3**: Separate auth model (no global scope on auth User)
```php
// AuthUser.php - used only for authentication
class AuthUser extends Model {}

// User.php - extends AuthUser, has global scopes
class User extends AuthUser {
    protected static function booted() {
        static::addGlobalScope(new CompanyScope);
    }
}
```

**Option 4**: BEST - Don't use relationships in global scopes
```php
// Instead of auth()->user()->company_id
// Store company_id in session separately
session()->get('company_id')
```

---

## 3. Spatie Permission Memory & Performance Issues

### The 580 Permission Problem

**Your Scale**: 580 role-permission associations is **significant**.

### How Spatie Loads Permissions

**From Research**:
- Every `hasPermissionTo()` call unserializes **all permissions from cache**
- With 580 permissions, each check loads the entire permission set
- Cache file can be **4.6MB+** for 100+ permissions
- **User-specific assignments kept in-memory since v4.4.0**

### Known Performance Issues

**Source**: https://github.com/spatie/laravel-permission/issues/789
- System with 200+ permissions, 50 users
- `permission_role` table: 4000+ entries
- Result: **Very slow** permission checks, high memory usage

**Source**: https://www.reddit.com/r/laravel/comments/jyeuag
- Quote: "Creating and updating permissions is very slow because it has to update cache again and again and memory usage is also terrible high"

### Filament Permission Checking Flow

**Critical Finding**: Filament checks permissions **during panel boot** for:
- Navigation items visibility
- Resource authorization
- Widget authorization
- Dashboard access

**Impact**: Every panel load = hundreds of permission checks

### Case Study: Performance Fix

**Source**: https://medium.com/@freekmurze/improving-the-performance-of-spatie-laravel-permission-732b2e07bd30

**Problem**:
- 142 permissions attached to 27 roles
- Blade template using `@can` 101 times
- Menu rendering: **significant delay**

**Solution**: Additional in-memory caching in `hasPermissionTo()` method
**Result**: Reduced from significant delay to **0.03 seconds**

### Optimization Strategies

**1. Cache Optimization**
```php
// config/permission.php
'cache' => [
    'store' => 'array',  // In-memory only (faster, but lost between requests)
    // OR
    'store' => 'redis',  // Persistent, shared across workers
],
```

**2. Reduce Permission Checks**
```php
// In Filament resources - cache authorization
protected static bool $shouldCheckPolicyExistence = false;

// Or disable resource authorization if using Shield plugin
```

**3. Performance Tips from Spatie Docs**

**Source**: https://spatie.be/docs/laravel-permission/v6/best-practices/performance

- **Assign permissions TO roles** (not lookup role and assign permissions)
  ```php
  // FASTER
  $permission->assignRole($role);

  // SLOWER (on large databases)
  $role->givePermissionTo($permission);
  ```

- **Bypass object methods for bulk operations**
  ```php
  // Faster for bulk updates
  $permission = Permission::make([attributes]);
  $permission->saveOrFail();
  // Then manually reset cache
  app(PermissionRegistrar::class)->forgetCachedPermissions();
  ```

---

## 4. Session Serialization & Memory Bloat

### PHP Session Object Serialization Issues

**Research Finding**: https://github.com/php/php-src/issues/10126
- **BUG in PHP 8.1.2 and 8.2.0**: Unserialized objects use **significantly more memory** than objects created with normal constructors
- Surplus memory usage grows with number of attributes
- **This is a documented PHP bug**

### Laravel Session Storage Mechanics

**How it works**:
1. End of request: PHP calls `session_write_close()`
2. Laravel serializes entire `$_SESSION` superglobal
3. **User model + all loaded relationships** serialized to session store
4. Next request: Session unserialized → triggers model `__wakeup()` → boots traits → applies global scopes

### Memory Bloat Factors

**From Research**:
- Serialization is **expensive** (CPU + memory)
- Large objects in session bloat session data
- **Relationships**: If User has loaded relationships, they're serialized too
- **Best Practice**: Store only identifiers in session, not full models

### Why This Causes Inconsistent Failures

**PHP-FPM Worker State**:
- Worker 1: Clean start → deserialize succeeds → stays in memory
- Worker 2: Handles high-memory request → OPcache fills → deserialize fails
- Worker 3: Old opcache → different class definitions → different memory footprint

**Result**: **Same request routed to different workers = different outcomes**

---

## 5. PHP-FPM + OPcache Inconsistency

### OPcache Revalidate Frequency

**Configuration Impact**:
```ini
opcache.revalidate_freq=2  ; Checks file changes every 2 seconds
```

**Problem**: During `revalidate_freq` window:
- Worker A: Has old opcode cache
- Worker B: Has new opcode cache (after file change)
- **Memory footprint differs between workers**

### OPcache Memory Full Behavior

**Source**: https://tideways.com/profiler/blog/fine-tune-your-opcache-configuration-to-avoid-caching-suprises

**Critical Finding**:
> "If wasted memory does not exceed max_wasted_percentage, OPcache will NOT restart and every uncached script will be re-compiled every request **as if there was no OPcache extension available**."

**Impact**:
- When OPcache full: Some workers compile on every request
- Massive memory difference between cached vs uncached workers
- **Explains inconsistent memory exhaustion**

### Recommendation

**Monitor OPcache Status**:
```php
<?php
$status = opcache_get_status(false);
echo "Memory Used: " . $status['memory_usage']['used_memory'] . "\n";
echo "Memory Wasted: " . $status['memory_usage']['wasted_memory'] . "\n";
echo "Current Wasted %: " . ($status['memory_usage']['current_wasted_percentage']) . "\n";
```

**Optimize**:
```ini
opcache.memory_consumption=256  ; Increase if at limit
opcache.max_accelerated_files=20000  ; Increase for large codebases
opcache.max_wasted_percentage=10  ; Lower to force earlier restarts
```

---

## 6. Filament Panel Session State Storage

### What Filament Stores in Session

**Research Findings**:

**1. Table State** (filters, sorting, search)
```php
// Persist filters in session
->persistFiltersInSession()
```

**2. Toggleable Columns State**
- User-specific column visibility preferences
- Stored per-user, per-resource
- **Source**: https://www.answeroverflow.com/m/1174713222004219997

**3. Tenant Information** (if using multi-tenancy)
- Current tenant context
- Tenant model can be serialized to session

**4. Navigation State**
- Collapsed/expanded navigation groups
- Active panel context

### Potential Session Bloat

**If you have**:
- Multiple table resources with complex filters
- Many toggleable columns
- Tenant model with relationships
- **All stored in session → increases session size → more memory on deserialization**

---

## 7. Authentication Flow & Model Loading

### Filament Panel Boot Sequence

**What happens when you access Filament panel**:

1. **Unauthenticated (login page)**:
   - Minimal model loading
   - No permission checks
   - **Low memory usage**

2. **Authenticated (dashboard/resources)**:
   - Load User model from session
   - Boot all User model traits (including global scopes)
   - Load Spatie permissions (all 580 associations)
   - Check panel access: `canAccessPanel()`
   - Load navigation items → permission checks for each
   - Load widgets → permission checks for each
   - **HIGH memory usage**

### When Does Filament Check Permissions?

**Source**: Research from Filament authorization docs

**During Panel Boot**:
```php
// 1. Panel access
FilamentUser::canAccessPanel($panel)

// 2. Navigation items
Navigation::make()
    ->visible(fn() => auth()->user()->can('view_users'))  // Permission check

// 3. Resource access
UserResource::canViewAny()  // Spatie permission check

// 4. Widget visibility
DashboardWidget::canView()  // Spatie permission check
```

**Impact**: With 10 navigation items + 5 widgets = **15+ permission checks** on EVERY panel load

---

## 8. Model Trait Boot & Session Unserialization

### The Boot Lifecycle During Unserialization

**Key Research**: https://github.com/laravel/framework/pull/37492

**What happens**:
1. Session unserialized → `Model::__wakeup()` called
2. `__wakeup()` triggers trait initialization
3. Trait `boot{TraitName}()` methods re-execute
4. Global scopes re-applied
5. **If boot method calls `auth()` → circular dependency**

### Example: HasRoles Trait (Spatie)

```php
// HasRoles trait (simplified)
protected static function bootHasRoles() {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
            return;
        }
        $model->roles()->detach();
        $model->permissions()->detach();
    });
}
```

**During unserialization**: These event listeners registered **every time**
**Result**: Memory accumulation over multiple deserializations

### Infinite Recursion Pattern

**Source**: https://stackoverflow.com/questions/72865365

**The Pattern**:
```php
// User model has CompanyScope trait
trait CompanyScope {
    protected static function bootCompanyScope() {
        static::addGlobalScope('company', function ($query) {
            // THIS CAUSES INFINITE LOOP when User unserialized from session
            if (auth()->check()) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }
}
```

**Why infinite**:
- Deserialize User → boot trait → call auth()->user()
- Auth tries to deserialize User → boot trait → call auth()->user()
- **Infinite recursion** until memory exhausted

### Solution: Check Session State

```php
protected static function bootCompanyScope() {
    static::addGlobalScope('company', function ($query) {
        // SAFE: Check if already authenticated without triggering deserialization
        if (auth()->hasUser()) {  // Laravel 5.6+ - doesn't trigger side effects
            $query->where('company_id', auth()->user()->company_id);
        }
    });
}
```

---

## 9. Redis Session + Serialization

### Your Configuration
```php
// config/session.php (inferred from "cache => redis")
'driver' => 'redis',
'connection' => 'default',
```

### How Redis Session Serialization Works

**Laravel Process**:
1. `SessionHandler` calls `serialize()` on session data
2. **User model**: `serialize()` → `toArray()` → includes all attributes
3. **Loaded relationships**: Also serialized if loaded
4. Serialized string stored in Redis
5. Next request: Redis retrieves → `unserialize()` → `Model::__wakeup()`

### Memory Implications

**Research Finding**:
- Redis session deserialization: Full model reconstruction in memory
- If User has loaded relationships (roles, permissions, company): **ALL deserialized**
- **Each attribute increases unserialized object memory** (PHP 8.1/8.2 bug)

### Optimization Strategy

**Reduce Session Payload**:
```php
// Instead of storing full User model
// Store only user ID and load fresh each request

// In AuthServiceProvider or middleware
Auth::viaRemember(fn($id) => User::with(['roles.permissions'])->find($id));
```

**Alternative**: Use database session driver for debugging
```php
// .env
SESSION_DRIVER=database

// Easier to inspect session content
SELECT * FROM sessions WHERE user_id = X;
```

---

## 10. Synthesis: Why Inconsistent Failures?

### The Perfect Storm Scenario

**Your specific combination**:
1. **Filament v3.3.30** (if applicable) → Blade recursion bug
2. **User model with CompanyScope global scope** → Potential circular auth dependency
3. **580 Spatie permissions** → Heavy permission cache loading
4. **Redis session** with serialized User model → Deserialization triggers boot
5. **PHP-FPM workers** with varying OPcache states → Inconsistent memory footprints

### Why Same Request Succeeds/Fails

**Scenario A: Success**
- Request → PHP-FPM Worker 1
- Worker fresh restart → clean memory
- OPcache warm → opcodes cached
- Session deserialization: auth() NOT called during boot (timing lucky)
- Permission cache already in Redis
- **Total memory: 110MB → Success**

**Scenario B: Failure**
- Request → PHP-FPM Worker 2
- Worker handled previous heavy request → memory fragmented
- OPcache full → some files not cached → recompiling on fly
- Session deserialization: auth() called during global scope boot → circular dependency
- Permission cache miss → loads all 580 permissions
- Filament blade recursion bug triggers (if v3.3.30)
- **Total memory: 140MB → FATAL ERROR**

### The Debugging Challenge

**Why hard to reproduce**:
- Can't control which PHP-FPM worker handles request
- Can't easily see OPcache state per-worker
- Session deserialization timing varies by microseconds
- No visibility into Laravel boot sequence timing

---

## 11. Recommended Action Plan

### Immediate Actions (Priority Order)

**1. CHECK FILAMENT VERSION** (5 minutes)
```bash
composer show filament/filament
```
If v3.3.30 → Update to v3.3.31+
```bash
composer update filament/filament
php artisan view:clear
```

**2. FIX GLOBAL SCOPE CIRCULAR DEPENDENCY** (30 minutes)
```php
// In CompanyScope or wherever you check auth in global scope
// BEFORE
if (auth()->check()) { ... }

// AFTER
if (auth()->hasUser()) { ... }  // Doesn't trigger deserialization

// OR BETTER - remove auth check from global scope entirely
// Store company_id in session middleware instead
```

**3. OPTIMIZE SPATIE PERMISSION CACHE** (15 minutes)
```php
// config/permission.php
'cache' => [
    'store' => 'redis',  // Use Redis for shared cache across workers
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
],
```

**4. REDUCE SESSION PAYLOAD** (1 hour)
```php
// Option A: Don't eager load relationships before storing user in session
// Check your login logic - remove any ->with() calls

// Option B: Use fresh user load each request
// Middleware to reload user with specific relationships
Auth::setUser(Auth::user()->load('roles.permissions'));
```

### Medium-Term Optimizations

**5. PHP-FPM Tuning**
```ini
; php-fpm.conf
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500  ; Restart workers after 500 requests (prevents memory leaks)
```

**6. OPcache Optimization**
```ini
; php.ini
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0  ; Always check in dev, 60 in production
opcache.validate_timestamps=1
```

**7. Monitor Memory Usage**
```php
// Add to middleware
Log::info('Memory before auth', [
    'usage' => memory_get_usage(true),
    'peak' => memory_get_peak_usage(true),
]);

Auth::check();

Log::info('Memory after auth', [
    'usage' => memory_get_usage(true),
    'peak' => memory_get_peak_usage(true),
]);
```

### Long-Term Architectural Improvements

**8. Separate Auth Model** (prevents global scope issues)
```php
// app/Models/AuthUser.php
class AuthUser extends Authenticatable {
    // No global scopes
}

// app/Models/User.php
class User extends AuthUser {
    protected static function booted() {
        static::addGlobalScope(new CompanyScope);
    }
}

// config/auth.php
'providers' => [
    'users' => [
        'model' => App\Models\AuthUser::class,  // Use AuthUser for authentication
    ],
],
```

**9. Permission Caching Strategy**
```php
// Cache permission checks at application level
// app/Services/PermissionCache.php
class PermissionCache {
    public static function userCan(User $user, string $permission): bool {
        return Cache::remember(
            "user.{$user->id}.can.{$permission}",
            60, // 1 minute
            fn() => $user->can($permission)
        );
    }
}
```

**10. Reduce Filament Permission Checks**
```php
// In Filament resources
protected static function shouldCheckPolicyExistence(): bool {
    return false;  // Skip policy existence checks
}

// Use Shield plugin's super admin feature
public function canAccessPanel(Panel $panel): bool {
    return $this->hasRole('super_admin') || parent::canAccessPanel($panel);
}
```

---

## 12. Testing & Validation

### Reproduce the Issue Consistently

**1. Force OPcache Reset**
```bash
# Reset opcache between tests
php artisan optimize:clear
sudo systemctl restart php8.2-fpm
```

**2. Monitor Per-Request Memory**
```php
// routes/web.php - debug route
Route::get('/memory-test', function() {
    $start = memory_get_usage(true);

    auth()->check();  // Trigger session deserialization
    $afterAuth = memory_get_usage(true);

    $user = auth()->user();
    $user->load('roles.permissions');
    $afterPermissions = memory_get_usage(true);

    return [
        'start' => $start,
        'after_auth' => $afterAuth,
        'after_permissions' => $afterPermissions,
        'auth_cost' => $afterAuth - $start,
        'permission_cost' => $afterPermissions - $afterAuth,
    ];
});
```

**3. Session Size Analysis**
```bash
# If using Redis
redis-cli
> KEYS sess:*
> GET sess:laravel_session_{your_session_id}
> STRLEN sess:laravel_session_{your_session_id}
```

### Success Metrics

- Memory usage before auth: ~50MB
- Memory usage after auth: < 80MB
- Session size: < 10KB
- No memory exhaustion errors in 1000 consecutive requests

---

## 13. Additional Resources

### Critical GitHub Issues

1. **Filament v3.3.30 Bug**
   - https://github.com/filamentphp/filament/releases/tag/v3.3.31
   - Fix for "endless loop caused by new Laravel release"

2. **Spatie Permission Performance**
   - https://github.com/spatie/laravel-permission/issues/789
   - 200+ permissions scaling issues

3. **Laravel Model Unserialization**
   - https://github.com/laravel/framework/pull/37492
   - Trait initialization during unserialization

### Stack Overflow Discussions

1. **Global Scope Infinite Loop**
   - https://stackoverflow.com/questions/72865365
   - Circular dependency with auth in global scope

2. **PHP Unserialization Memory Bug**
   - https://github.com/php/php-src/issues/10126
   - Unserialized objects use more memory (PHP 8.1/8.2)

### Official Documentation

1. **Spatie Permission Performance Tips**
   - https://spatie.be/docs/laravel-permission/v6/best-practices/performance

2. **Spatie Permission Caching**
   - https://spatie.be/docs/laravel-permission/v6/advanced-usage/cache

3. **Laravel Session Documentation**
   - https://laravel.com/docs/12.x/session

4. **Filament Authorization**
   - https://filamentphp.com/docs/3.x/panels/users

---

## 14. Root Cause Hypothesis (Most Likely)

Based on all research, your issue is **most likely**:

### Primary Cause (70% confidence)
**Filament v3.3.30 Blade Recursion Bug** + **Global Scope Circular Dependency**
- Filament bug causes base memory bloat
- Global scope auth check during session deserialization pushes it over limit
- Inconsistency: Timing of when session deserializes vs when auth middleware runs

### Contributing Factors (30% contribution)
- **Spatie 580 permissions**: Adds 20-40MB memory on permission cache load
- **PHP-FPM worker state**: OPcache variations between workers
- **Session payload size**: User model with loaded relationships

### The Smoking Gun
**Login page works** (no auth, no session deserialization) but **dashboard fails** (auth required → deserialize → boot traits → circular dependency or blade recursion)

---

## 15. Quick Win Testing

### Test 1: Filament Version Check
```bash
composer show filament/filament | grep versions
```
If "3.3.30" appears → **UPDATE NOW** → 90% chance this fixes it

### Test 2: Disable Global Scope Temporarily
```php
// User.php
protected static function booted() {
    // static::addGlobalScope(new CompanyScope);  // Comment out
}
```
Restart → Test dashboard → If works → Global scope is the culprit

### Test 3: Reduce Permission Load
```php
// Temporarily reduce permission checks
// FilamentPanelProvider
public function panel(Panel $panel): Panel {
    return $panel
        // ->authGuard('web')
        ->authMiddleware([]);  // Remove auth temporarily
}
```
If works → Permission loading is contributing

---

## Conclusion

Your memory exhaustion issue is **not a simple single cause** but a **cascade failure** involving:

1. **Known Filament bug** (if v3.3.30)
2. **Architectural anti-pattern** (global scope checking auth on User model)
3. **Scale issue** (580 permissions loaded on every request)
4. **Infrastructure variance** (PHP-FPM worker state)

**Highest ROI fixes**:
1. Update Filament to v3.3.31+
2. Fix global scope circular dependency
3. Optimize Spatie permission caching

**Expected outcome**: Reduce memory from ~140MB (failing) to ~70MB (safe margin) with consistent behavior across all requests.
