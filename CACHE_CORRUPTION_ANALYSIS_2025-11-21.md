# Cache Corruption & Multi-Tenant Security Analysis
**Date**: 2025-11-21
**Component**: CallStatsOverview Widget
**Severity**: 🔴 CRITICAL - Multi-Tenant Data Leakage
**Status**: Active Security Vulnerability

---

## Executive Summary

The `CallStatsOverview` widget contains **critical cache architecture flaws** that violate multi-tenant isolation, causing:

1. **Data Leakage**: Users see other companies' statistics
2. **Role Confusion**: First user's role determines what all subsequent users see
3. **Incorrect Metrics**: Statistics show wrong counts based on cache race conditions
4. **Security Violation**: Company-scoped data accessible cross-tenant

**Root Cause**: Role-based filtering and company scoping happen INSIDE cached callbacks, causing the first user to "poison" the cache for all subsequent users.

---

## Critical Vulnerabilities

### 🚨 Vulnerability 1: Stats Cache Missing Multi-Tenant Scope

**File**: `/var/www/api-gateway/app/Filament/Widgets/CallStatsOverview.php`
**Lines**: 35-39

```php
// CURRENT (INSECURE):
protected function getStats(): array
{
    $cacheKey = 'call-stats-overview-' . (auth()->user()->company_id ?? 'global');
    $cacheMinutes = floor(now()->format('i') / 2);
    $fullCacheKey = $cacheKey . '-' . now()->format('Y-m-d-H') . '-' . $cacheMinutes;

    return Cache::remember($fullCacheKey, 120, function () {
        // Lines 48-56: Role filtering INSIDE cache
        if ($user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
            $query->where('company_id', $user->company_id);
        } elseif ($user->hasRole(['reseller_admin', ...])) {
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super-admin sees all calls
    });
}
```

**Problem**:
- Cache key includes `company_id` but NOT role
- Role filtering happens INSIDE the cache callback
- Result: Super-admin hits cache first → ALL users (including company-scoped) see ALL calls for 120 seconds

**Attack Scenario**:
```
Time 0:00 - Super-admin loads widget
          → Cache key: call-stats-overview-1-2025-11-21-16-28
          → Query: ALL calls (no company filter)
          → Cache stores: {total: 100, appointments: 20}

Time 0:30 - Company-admin (company_id=5) loads widget
          → Cache key: call-stats-overview-5-2025-11-21-16-28
          → Query: Only company 5 calls
          → Cache stores: {total: 5, appointments: 1}

Time 0:45 - Company-admin (company_id=1) loads widget
          → Cache key: call-stats-overview-1-2025-11-21-16-28 (COLLISION!)
          → Cache HIT: Returns super-admin's data (100 calls)
          → Company sees ALL companies' data!
```

---

### 🚨 Vulnerability 2: Hourly Chart Cache - NO Company Scoping

**File**: `/var/www/api-gateway/app/Filament/Widgets/CallStatsOverview.php`
**Lines**: 154-174

```php
// CURRENT (COMPLETELY BROKEN):
protected function getHourlyCallData(): array
{
    $cacheKey = 'call-hourly-data-' . now()->format('Y-m-d');
    $cacheMinutes = floor(now()->format('i') / 5);

    return Cache::remember($cacheKey . '-' . $cacheMinutes, 300, function () {
        // NO company_id in cache key!
        // NO role filtering!
        $hourlyData = Call::whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    });
}
```

**Problem**:
- Cache key: `call-hourly-data-2025-11-21-28` (GLOBAL for all companies!)
- Query has NO `where('company_id', ...)` filter
- ALL companies share the same chart data

**Impact**: Company A sees hourly call volume for ALL companies combined

---

### 🚨 Vulnerability 3: Profit Chart Cache - Role Confusion

**File**: `/var/www/api-gateway/app/Filament/Widgets/CallStatsOverview.php`
**Lines**: 177-217

```php
// CURRENT (ROLE-CONFUSED):
protected function getHourlyProfitData(): array
{
    $cacheKey = 'profit-hourly-data-' . now()->format('Y-m-d');
    $cacheMinutes = floor(now()->format('i') / 5);

    return Cache::remember($cacheKey . '-' . $cacheMinutes, 300, function () {
        $user = auth()->user(); // ⚠️ User captured at cache creation time!
        if (!$user) return array_fill(0, 24, 0);

        // Query ALL calls
        $calls = Call::whereDate('created_at', today())->get();

        $costCalculator = new CostCalculator();
        foreach ($calls as $call) {
            $profitData = $costCalculator->getDisplayProfit($call, $user); // Role-based
            // ...
        }
    });
}
```

**Problem**:
- Cache key has NO user/role identifier
- `auth()->user()` called INSIDE cache → frozen at first access
- Role-based profit calculation returns different results per role

**Race Condition**:
```
Scenario 1: Reseller loads first
→ Cache stores profit based on reseller's view (reseller_profit field)
→ Super-admin loads → sees reseller's limited profit view (WRONG!)

Scenario 2: Super-admin loads first
→ Cache stores profit based on super-admin view (total_profit)
→ Reseller loads → sees total profit including platform profit (SECURITY LEAK!)
```

---

## Performance Analysis

### Query Performance Benchmark

```
=== Actual Performance Test Results ===
Query Time: 74.43 ms (aggregation + count)
Calls Today: 5
Cache Store: Redis (phpredis)
Cache Overhead: ~2-5ms (serialization + network)

Analysis:
- Query is reasonably fast (<100ms)
- Caching saves ~70ms per request
- Widget polls every 60s
- Cache TTL: 120s
- Cache key changes: Every 2 minutes

Theoretical Load:
- 10 concurrent users polling every 60s
- Without cache: 10 queries/min × 74ms = 740ms DB time/min
- With cache: 1 query/2min × 74ms = 0.6ms DB time/min
- Savings: ~99% reduction in DB load
```

**Verdict**: Caching IS beneficial for performance, but current implementation is fatally flawed.

---

## Cache Key Architecture Issues

### Current Cache Keys

| Component | Cache Key Pattern | Issues |
|-----------|-------------------|---------|
| **Main Stats** | `call-stats-overview-{company_id}-{date}-{hour}-{2min}` | ✅ Has company_id<br>❌ Missing role<br>❌ Role filter inside cache |
| **Hourly Chart** | `call-hourly-data-{date}-{5min}` | ❌ NO company_id<br>❌ NO role<br>❌ Query unfiltered |
| **Profit Chart** | `profit-hourly-data-{date}-{5min}` | ❌ NO company_id<br>❌ NO role<br>❌ Role logic frozen |

### Cache Key Collision Scenarios

#### Scenario A: Role-Based Collision
```php
// Super-admin (company_id = 1)
$key = 'call-stats-overview-1-2025-11-21-16-28';
Cache::remember($key, 120, fn() => getAllCalls()); // No filter

// Company-admin (company_id = 1)
$key = 'call-stats-overview-1-2025-11-21-16-28'; // SAME KEY!
Cache::remember($key, 120, fn() => getCompanyCalls(1)); // Filtered

Result: Whoever loads first determines data for both users
```

#### Scenario B: Chart Data Shared Globally
```php
// Company A user
$key = 'call-hourly-data-2025-11-21-28';
Cache::remember($key, 300, fn() => Call::whereDate(...)->get()); // ALL companies

// Company B user
$key = 'call-hourly-data-2025-11-21-28'; // EXACT SAME KEY!
Cache::get($key); // Returns Company A + B + C + ... combined data

Result: All companies see aggregated data across ALL tenants
```

---

## Cache TTL vs Polling Interval Mismatch

**Current Configuration**:
- Widget polling: 60 seconds (`protected static ?string $pollingInterval = '60s'`)
- Main stats cache: 120 seconds TTL
- Chart cache: 300 seconds TTL
- Cache key granularity: 2 minutes (stats), 5 minutes (charts)

**Problem**:
```
Timeline (Main Stats):
00:00 - User loads → cache miss → query DB → cache for 120s (key: ...-16-0)
01:00 - User polls → cache hit (same 2-min bucket)
02:00 - Cache key changes (new bucket: ...-16-2) → cache miss → query DB
03:00 - User polls → cache hit
04:00 - Cache key changes → query DB
...

Result: Cache invalidated by key rotation BEFORE TTL expires
        Widget polls MORE FREQUENTLY than cache provides benefit
```

**Recommendation**: Align polling interval with cache granularity OR extend cache key stability.

---

## Cost Calculator Integration Issues

### Role-Based Cost Display

**File**: `/var/www/api-gateway/app/Services/CostCalculator.php`
**Lines**: 378-414

```php
public function getDisplayCost(Call $call, ?User $user): int
{
    if (!$user) return $call->base_cost ?? 0;

    // Super admin sees customer_cost
    if ($user->hasRole(['super-admin', ...])) {
        return $call->customer_cost ?? $call->base_cost;
    }

    // Reseller sees reseller_cost
    if ($user->hasRole(['reseller_admin', ...])) {
        return $call->reseller_cost;
    }

    // Customer sees customer_cost
    return $call->customer_cost;
}
```

**Problem**: This is called INSIDE the cached callback (line 78 of CallStatsOverview):
```php
return Cache::remember($fullCacheKey, 120, function () {
    // ...
    foreach ($calls as $call) {
        $todayCost += $costCalculator->getDisplayCost($call, $user); // Role-dependent!
    }
});
```

**Result**: Cost calculations frozen at first user's role perspective.

---

## Security Impact Assessment

### CVSS-like Scoring

| Factor | Value | Justification |
|--------|-------|---------------|
| **Attack Vector** | Network | Accessible via authenticated web interface |
| **Attack Complexity** | Low | Passive - just load the widget |
| **Privileges Required** | Low | Any authenticated user |
| **User Interaction** | None | Automatic cache collision |
| **Scope** | Changed | Affects other tenants |
| **Confidentiality** | High | Access to other companies' data |
| **Integrity** | Low | Display only, no data modification |
| **Availability** | Low | No denial of service |

**Overall Severity**: 🔴 **HIGH (7.5/10)**

### Business Impact

- **Compliance**: GDPR violation (cross-tenant data exposure)
- **Trust**: Customer confidence erosion if discovered
- **Legal**: Potential breach notification requirements
- **Competitive**: Business intelligence leakage (call volumes, success rates)

---

## Root Cause Analysis

### Design Flaws

1. **Premature Optimization**: Caching added without considering multi-tenant architecture
2. **Insufficient Key Design**: Cache keys don't capture all state variables (role, company)
3. **Layering Violation**: Business logic (role filtering) mixed with caching layer
4. **Closure Capture**: User/role captured in cache closure, frozen for TTL duration

### Why It Happened

Looking at git history:
```bash
# Original widget had NO caching
# Caching added to reduce DB load
# Company_id added to cache key (partial fix)
# Role filtering NOT considered in cache design
```

**Pattern**: Incremental fixes without holistic security review.

---

## Recommended Solutions

### 🟢 Solution 1: Multi-Dimensional Cache Keys (RECOMMENDED)

**Approach**: Include ALL state variables in cache key

```php
protected function getStats(): array
{
    $user = auth()->user();
    $roleKey = $this->getRoleKey($user);
    $companyKey = $user->company_id ?? 'global';

    // Unique key per company + role combination
    $cacheKey = sprintf(
        'call-stats-overview-%s-%s-%s',
        $companyKey,
        $roleKey,
        now()->format('Y-m-d-H-i')
    );

    return Cache::remember($cacheKey, 120, function () use ($user) {
        // Query with role filter
        return $this->calculateStats($user);
    });
}

private function getRoleKey(?User $user): string
{
    if (!$user) return 'guest';
    if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) return 'superadmin';
    if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) return 'reseller';
    return 'company';
}
```

**Pros**:
- ✅ Complete isolation per company + role
- ✅ No cache collision risk
- ✅ Minimal code changes

**Cons**:
- ❌ Higher cache memory usage (3-5x keys)
- ❌ Cache fragmentation (lower hit rate)

**Memory Impact**:
```
Current: 1 key per company per 2-min window
         10 companies × 30 buckets/hour = 300 keys/hour

Proposed: 1 key per company per role per 2-min window
          10 companies × 3 roles × 30 buckets = 900 keys/hour
          Estimated cache size: ~50KB per key × 900 = 45MB/hour
```

---

### 🟢 Solution 2: Filter-Outside-Cache Pattern (BEST PRACTICE)

**Approach**: Cache raw data, apply filters after retrieval

```php
protected function getStats(): array
{
    $user = auth()->user();

    // Cache global data (no filters)
    $allStats = Cache::remember('call-stats-global-' . now()->format('Y-m-d-H-i'), 120, function () {
        return $this->calculateGlobalStats();
    });

    // Apply role/company filters OUTSIDE cache
    return $this->filterStatsForUser($allStats, $user);
}

private function calculateGlobalStats(): array
{
    // Pre-aggregate stats by company
    return Call::whereDate('created_at', today())
        ->selectRaw('
            company_id,
            COUNT(*) as total_count,
            SUM(duration_sec) as total_duration,
            SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count
        ')
        ->groupBy('company_id')
        ->get()
        ->keyBy('company_id')
        ->toArray();
}

private function filterStatsForUser(array $allStats, ?User $user): array
{
    if (!$user) return $this->emptyStats();

    // Super-admin sees all
    if ($user->hasRole(['super-admin', ...])) {
        return $this->aggregateStats($allStats);
    }

    // Company-admin sees only their company
    if ($user->hasRole(['company_admin', ...])) {
        return $allStats[$user->company_id] ?? $this->emptyStats();
    }

    // Reseller sees their customers
    if ($user->hasRole(['reseller_admin', ...])) {
        $customerIds = Company::where('parent_company_id', $user->company_id)->pluck('id');
        return $this->aggregateStats(array_intersect_key($allStats, $customerIds->flip()->toArray()));
    }

    return $this->emptyStats();
}
```

**Pros**:
- ✅ Single cached dataset for all users
- ✅ Highest cache hit rate
- ✅ Security: filtering outside cache
- ✅ Testable: clear separation of concerns

**Cons**:
- ❌ Returns ALL companies' data (security: depends on filter logic)
- ❌ Slightly higher memory (stores full dataset)

**Security Note**: Must ensure filter logic is bulletproof. Consider adding audit logging.

---

### 🟢 Solution 3: Disable Caching (SAFEST, SHORT-TERM)

**Approach**: Remove caching until proper multi-tenant architecture is implemented

```php
protected function getStats(): array
{
    $user = auth()->user();
    return $this->calculateStats($user); // Direct query, no cache
}

protected function getHourlyCallData(): array
{
    $user = auth()->user();
    return $this->calculateHourlyData($user); // Direct query, no cache
}

protected function getHourlyProfitData(): array
{
    $user = auth()->user();
    return $this->calculateHourlyProfit($user); // Direct query, no cache
}
```

**Performance Impact**:
```
Current (cached): ~5ms per widget load (cache hit)
Proposed (uncached): ~75ms per widget load (query + aggregation)

Load scenario (10 users, 60s polling):
- Queries per minute: 10
- DB time per minute: 750ms
- Total overhead: <1 second/minute

Verdict: Acceptable for current load (5 calls/day)
         Revisit if load exceeds 100 calls/day or 100+ concurrent users
```

**Pros**:
- ✅ Immediate security fix
- ✅ Zero cache complexity
- ✅ No collision risk
- ✅ Simple rollback if issues

**Cons**:
- ❌ Higher DB load
- ❌ Doesn't scale long-term

---

### 🟡 Solution 4: Cached Queries with Laravel Query Scopes

**Approach**: Use Eloquent query scopes to ensure filters are always applied

```php
// app/Models/Call.php
public function scopeForUser($query, User $user)
{
    if ($user->hasRole(['super-admin', ...])) {
        return $query; // No filter
    }

    if ($user->hasRole(['company_admin', ...])) {
        return $query->where('company_id', $user->company_id);
    }

    if ($user->hasRole(['reseller_admin', ...])) {
        $customerIds = Company::where('parent_company_id', $user->company_id)->pluck('id');
        return $query->whereIn('company_id', $customerIds);
    }

    return $query->whereRaw('1 = 0'); // No access
}

// CallStatsOverview.php
protected function getStats(): array
{
    $user = auth()->user();
    $roleKey = $this->getRoleKey($user);
    $cacheKey = "call-stats-{$roleKey}-" . now()->format('Y-m-d-H-i');

    return Cache::remember($cacheKey, 120, function () use ($user) {
        $stats = Call::whereDate('created_at', today())
            ->forUser($user) // Scope applied
            ->selectRaw('COUNT(*) as total_count, ...')
            ->first();

        return $this->formatStats($stats, $user);
    });
}
```

**Pros**:
- ✅ Reusable filtering logic
- ✅ Type-safe (query builder)
- ✅ Testable scopes

**Cons**:
- ❌ Still requires role-based cache keys
- ❌ Complex scope logic

---

## Implementation Roadmap

### Phase 1: Immediate Security Fix (1 hour)

**Priority**: 🔴 CRITICAL

1. **Disable caching** in `CallStatsOverview.php`
   - Remove `Cache::remember()` wrappers
   - Direct query execution
   - Test with multiple roles/companies

2. **Deploy to production** ASAP
   - Low risk (removes complexity)
   - Performance acceptable for current load

**Files to modify**:
- `/var/www/api-gateway/app/Filament/Widgets/CallStatsOverview.php`

**Testing**:
```bash
# Test as super-admin
php artisan tinker --execute="auth()->loginUsingId(1); app(App\Filament\Widgets\CallStatsOverview::class)->getStats();"

# Test as company-admin
php artisan tinker --execute="auth()->loginUsingId(5); app(App\Filament\Widgets\CallStatsOverview::class)->getStats();"

# Verify data isolation
```

---

### Phase 2: Implement Secure Caching (1-2 days)

**Priority**: 🟡 HIGH (Performance optimization)

**Approach**: Solution 2 (Filter-Outside-Cache)

1. **Refactor stats calculation**
   - `calculateGlobalStats()`: Cache aggregated by company_id
   - `filterStatsForUser()`: Apply role filters post-cache
   - `calculateHourlyDataByCompany()`: Pre-aggregate charts

2. **Update cache keys**
   - Global stats: `call-stats-global-{date}-{hour}-{5min}`
   - Per-company hourly: `call-hourly-{company_id}-{date}-{5min}`

3. **Add cache invalidation**
   - Listen to `CallCreated`, `CallUpdated` events
   - Invalidate relevant cache keys
   - Selective invalidation (not global flush)

4. **Comprehensive testing**
   - Unit tests: Cache key generation
   - Integration tests: Multi-tenant isolation
   - Load tests: Performance validation

**Files to create/modify**:
- `/var/www/api-gateway/app/Filament/Widgets/CallStatsOverview.php` (refactor)
- `/var/www/api-gateway/app/Listeners/InvalidateCallStatsCache.php` (new)
- `/var/www/api-gateway/tests/Feature/CallStatsWidgetCacheTest.php` (new)

---

### Phase 3: Cache Architecture Review (1 week)

**Priority**: 🟢 MEDIUM (System-wide)

**Scope**: Review ALL widgets and resources for similar issues

**Audit targets** (from grep results):
- ✅ `CallbackRequestResource` - has proper invalidation
- ⚠️ `BalanceTopupStats.php` - check company scoping
- ⚠️ `CustomerNoteStats.php` - check tenant isolation
- ⚠️ `ProfitDashboard.php` - critical: profit data!
- ⚠️ `CallPerformanceDashboard.php` - check filtering
- ⚠️ `CustomerStatsOverview.php` - verify company filter

**Checklist per widget**:
```
□ Cache key includes company_id (if tenant-scoped)?
□ Cache key includes role (if role-dependent)?
□ Filters applied OUTSIDE cache OR in cache key?
□ Cache invalidation on data changes?
□ TTL appropriate for data staleness tolerance?
□ Performance benchmark (cached vs uncached)?
□ Security review (data leakage vectors)?
```

**Deliverable**:
- Audit report with risk scores
- Remediation plan
- Cache architecture guidelines document

---

## Testing Strategy

### Unit Tests

```php
// tests/Feature/CallStatsWidgetCacheTest.php
class CallStatsWidgetCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_all_companies_stats()
    {
        $superAdmin = User::factory()->create()->assignRole('super-admin');
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        Call::factory()->count(5)->create(['company_id' => $company1->id]);
        Call::factory()->count(3)->create(['company_id' => $company2->id]);

        $this->actingAs($superAdmin);
        $stats = app(CallStatsOverview::class)->getStats();

        $this->assertEquals(8, $stats[0]->getValue()); // Total calls
    }

    public function test_company_admin_sees_only_own_company_stats()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company1->id])
            ->assignRole('company_admin');

        Call::factory()->count(5)->create(['company_id' => $company1->id]);
        Call::factory()->count(3)->create(['company_id' => $company2->id]);

        $this->actingAs($admin);
        $stats = app(CallStatsOverview::class)->getStats();

        $this->assertEquals(5, $stats[0]->getValue()); // Only company1 calls
    }

    public function test_cache_isolation_between_roles()
    {
        $superAdmin = User::factory()->create()->assignRole('super-admin');
        $companyAdmin = User::factory()->create(['company_id' => 1])
            ->assignRole('company_admin');

        Call::factory()->count(10)->create(['company_id' => 1]);
        Call::factory()->count(5)->create(['company_id' => 2]);

        // Super-admin loads first
        $this->actingAs($superAdmin);
        $superStats = app(CallStatsOverview::class)->getStats();

        // Company-admin loads second (should NOT see super-admin's cached data)
        $this->actingAs($companyAdmin);
        $companyStats = app(CallStatsOverview::class)->getStats();

        $this->assertEquals(15, $superStats[0]->getValue()); // All calls
        $this->assertEquals(10, $companyStats[0]->getValue()); // Only company1
        $this->assertNotEquals($superStats[0]->getValue(), $companyStats[0]->getValue());
    }

    public function test_hourly_chart_data_scoped_to_company()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company1->id])
            ->assignRole('company_admin');

        // Create calls at different hours
        Call::factory()->create([
            'company_id' => $company1->id,
            'created_at' => now()->setHour(10)
        ]);
        Call::factory()->create([
            'company_id' => $company2->id,
            'created_at' => now()->setHour(10)
        ]);

        $this->actingAs($admin);
        $widget = app(CallStatsOverview::class);
        $hourlyData = $this->invokePrivateMethod($widget, 'getHourlyCallData');

        // Should only show 1 call at hour 10 (company1's call)
        $this->assertEquals(1, $hourlyData[10]);
    }

    private function invokePrivateMethod($object, $method, ...$args)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invoke($object, ...$args);
    }
}
```

### Manual Testing Scenarios

#### Scenario 1: Role-Based Cache Isolation
```
Steps:
1. Open two browsers (Chrome + Firefox)
2. Chrome: Login as super-admin
3. Firefox: Login as company-admin (company_id=1)
4. Chrome: Load dashboard → note "Anrufe heute" count (should be ALL calls)
5. Firefox: Load dashboard → note "Anrufe heute" count (should be company1 only)
6. Verify counts are DIFFERENT
7. Wait 2 minutes (cache key rotation)
8. Refresh both browsers
9. Verify counts remain correctly scoped

Expected: Different counts based on role
Bug: Same count if cache collision occurs
```

#### Scenario 2: Hourly Chart Cross-Company Data
```
Steps:
1. Create test calls for company A at 10:00 (count: 5)
2. Create test calls for company B at 10:00 (count: 3)
3. Login as company A admin
4. Check hourly chart at 10:00 hour

Expected: Chart shows 5 calls at 10:00
Bug: Chart shows 8 calls (A + B combined)
```

#### Scenario 3: Profit Data Leakage
```
Steps:
1. Login as reseller-admin
2. Load dashboard → note profit amount
3. Logout → login as super-admin
4. Load dashboard → note profit amount

Expected: Different amounts (reseller vs total profit)
Bug: Same amount if cache hit occurs
```

---

## Cache Invalidation Strategy

### Event-Driven Invalidation

**Create listener**:
```php
// app/Listeners/InvalidateCallStatsCache.php
namespace App\Listeners;

use App\Events\CallCreated;
use App\Events\CallUpdated;
use Illuminate\Support\Facades\Cache;

class InvalidateCallStatsCache
{
    public function handle(CallCreated|CallUpdated $event)
    {
        $call = $event->call;
        $date = $call->created_at->format('Y-m-d');

        // Invalidate main stats cache (all roles for this company)
        $roles = ['superadmin', 'reseller', 'company'];
        foreach ($roles as $role) {
            Cache::forget("call-stats-overview-{$call->company_id}-{$role}-{$date}-*");
        }

        // Invalidate hourly charts
        Cache::forget("call-hourly-{$call->company_id}-{$date}-*");
        Cache::forget("profit-hourly-{$call->company_id}-{$date}-*");

        // If super-admin cache exists, invalidate global stats
        Cache::forget("call-stats-global-{$date}-*");

        \Log::info('Invalidated call stats cache', [
            'call_id' => $call->id,
            'company_id' => $call->company_id,
            'date' => $date
        ]);
    }
}
```

**Register listener**:
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    CallCreated::class => [
        InvalidateCallStatsCache::class,
    ],
    CallUpdated::class => [
        InvalidateCallStatsCache::class,
    ],
];
```

**Note**: Wildcard cache deletion not supported by all drivers. May need to maintain cache key registry.

---

## Monitoring & Alerting

### Cache Hit Rate Metrics

```php
// Add to CallStatsOverview.php
protected function getStats(): array
{
    $cacheKey = $this->buildCacheKey();
    $cacheHit = Cache::has($cacheKey);

    if ($cacheHit) {
        \Log::debug('Cache HIT', ['key' => $cacheKey, 'widget' => 'CallStatsOverview']);
    } else {
        \Log::debug('Cache MISS', ['key' => $cacheKey, 'widget' => 'CallStatsOverview']);
    }

    return Cache::remember($cacheKey, 120, function () {
        // ...
    });
}
```

### Anomaly Detection

```php
// Monitor for suspicious cache patterns
if ($cacheHit && $this->isDataAnomalous($stats)) {
    \Log::warning('Cache data anomaly detected', [
        'cache_key' => $cacheKey,
        'user_id' => auth()->id(),
        'company_id' => auth()->user()->company_id,
        'stats' => $stats,
        'expected_company_id' => auth()->user()->company_id,
        'possible_cache_collision' => true
    ]);
}
```

---

## Configuration Recommendations

### Optimal Cache Settings

```php
// config/cache.php
return [
    'default' => env('CACHE_STORE', 'redis'), // ✅ Redis preferred
    'prefix' => env('CACHE_PREFIX', 'askpro_cache_'), // ✅ Namespaced

    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',

            // Multi-tenant recommendations:
            'options' => [
                'cluster' => 'redis',
                'prefix' => env('CACHE_PREFIX', 'askpro_cache_'),
            ],
        ],
    ],
];

// .env
CACHE_STORE=redis
CACHE_PREFIX=askpro_${APP_ENV}_
REDIS_CLIENT=phpredis
```

### Widget Polling Recommendations

```php
// app/Filament/Widgets/CallStatsOverview.php
class CallStatsOverview extends BaseWidget
{
    // Current: 60s polling, 120s cache
    protected static ?string $pollingInterval = '60s';

    // Recommended: Align with cache granularity
    protected static ?string $pollingInterval = '120s'; // Match cache TTL

    // Or: Disable polling, use cache invalidation + Livewire events
    protected static ?string $pollingInterval = null;
}
```

---

## Conclusion

The `CallStatsOverview` widget contains **critical multi-tenant security vulnerabilities** stemming from improper cache key design. The issues are:

1. **Role-based filtering inside cache callbacks** → cache poisoning
2. **Missing company_id in chart cache keys** → cross-tenant data leakage
3. **No cache invalidation** → stale data persists

**Immediate Action Required**:
- 🔴 Disable caching (Phase 1) - deploy within 1 hour
- 🟡 Implement secure caching (Phase 2) - complete within 1 week
- 🟢 System-wide cache audit (Phase 3) - complete within 1 month

**Recommended Solution**: Solution 2 (Filter-Outside-Cache) provides the best balance of security, performance, and maintainability.

---

## Appendix: Code Examples

### A. Secure Cache Key Generator

```php
// app/Services/CacheKeyService.php
namespace App\Services;

use App\Models\User;

class CacheKeyService
{
    public function buildWidgetKey(string $widget, ?User $user, array $params = []): string
    {
        $segments = [
            'widget',
            $widget,
            $this->getUserSegment($user),
            $this->getTimeSegment($params['granularity'] ?? '5min'),
        ];

        if (isset($params['extra'])) {
            $segments = array_merge($segments, $params['extra']);
        }

        return implode('-', array_filter($segments));
    }

    private function getUserSegment(?User $user): string
    {
        if (!$user) return 'guest';

        $role = $this->getUserRole($user);
        $companyId = $user->company_id ?? 'global';

        return "{$role}-{$companyId}";
    }

    private function getUserRole(User $user): string
    {
        if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) return 'superadmin';
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) return 'reseller';
        return 'company';
    }

    private function getTimeSegment(string $granularity): string
    {
        $now = now();

        return match($granularity) {
            '1min' => $now->format('Y-m-d-H-i'),
            '2min' => $now->format('Y-m-d-H') . '-' . floor($now->minute / 2) * 2,
            '5min' => $now->format('Y-m-d-H') . '-' . floor($now->minute / 5) * 5,
            '15min' => $now->format('Y-m-d-H') . '-' . floor($now->minute / 15) * 15,
            'hour' => $now->format('Y-m-d-H'),
            'day' => $now->format('Y-m-d'),
            default => $now->format('Y-m-d-H-i'),
        };
    }
}

// Usage:
$keyService = app(CacheKeyService::class);
$cacheKey = $keyService->buildWidgetKey('call-stats', auth()->user(), ['granularity' => '2min']);
// Result: widget-call-stats-company-1-2025-11-21-16-28
```

### B. Cached Query with Scopes

```php
// app/Models/Call.php
public function scopeVisibleToUser($query, User $user)
{
    if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
        return $query; // No filter
    }

    if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
        $customerIds = Company::where('parent_company_id', $user->company_id)->pluck('id');
        return $query->whereIn('company_id', $customerIds);
    }

    return $query->where('company_id', $user->company_id);
}

// app/Filament/Widgets/CallStatsOverview.php
protected function getStats(): array
{
    $user = auth()->user();
    $keyService = app(CacheKeyService::class);
    $cacheKey = $keyService->buildWidgetKey('call-stats', $user, ['granularity' => '2min']);

    return Cache::remember($cacheKey, 120, function () use ($user) {
        $stats = Call::whereDate('created_at', today())
            ->visibleToUser($user) // Scope applied
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(duration_sec) as total_duration,
                SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count
            ')
            ->first();

        return $this->formatStats($stats, $user);
    });
}
```

---

**Document Version**: 1.0
**Last Updated**: 2025-11-21
**Author**: Performance Engineer (SuperClaude Framework)
**Classification**: INTERNAL - SECURITY SENSITIVE
