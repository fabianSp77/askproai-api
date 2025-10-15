# CompanyScope Optimization Quick Reference Guide

**Purpose**: Quick reference for developers working with CompanyScope in production

---

## Performance Best Practices

### ✅ DO: Always Eager Load Relationships

```php
// ✅ GOOD: Eager load to prevent N+1
$appointments = Appointment::with(['customer', 'service', 'staff'])->get();

// ❌ BAD: Lazy loading in loops causes N+1
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name;  // N queries!
}
```

### ✅ DO: Use Specific Columns When Possible

```php
// ✅ GOOD: Select only needed columns
$appointments = Appointment::select(['id', 'starts_at', 'status', 'customer_id'])->get();

// ❌ BAD: Loading unnecessary data
$appointments = Appointment::all();  // Loads all columns including large JSON fields
```

### ✅ DO: Add Indexes for Common Query Patterns

```php
// If you frequently query by status AND date:
Schema::table('appointments', function (Blueprint $table) {
    $table->index(['company_id', 'status', 'starts_at'], 'idx_company_status_date');
});
```

### ✅ DO: Use Chunking for Large Datasets

```php
// ✅ GOOD: Process in chunks to limit memory
Appointment::chunk(100, function ($appointments) {
    foreach ($appointments as $appointment) {
        // Process appointment
    }
});

// ❌ BAD: Loading all records at once
$appointments = Appointment::all();  // Could be 10,000+ records
```

### ❌ DON'T: Bypass Scope Without Audit Trail

```php
// ❌ DANGEROUS: No logging of scope bypass
$appointments = Appointment::withoutCompanyScope()->get();

// ✅ BETTER: Log scope bypass for security audit
$appointments = Appointment::withoutCompanyScope()->get();
Log::warning('CompanyScope bypassed', [
    'user' => auth()->id(),
    'reason' => 'Admin report generation',
    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
]);
```

### ❌ DON'T: Use Raw Queries Without Manual Scoping

```php
// ❌ DANGEROUS: Bypasses CompanyScope entirely
DB::select('SELECT * FROM appointments WHERE status = ?', ['scheduled']);

// ✅ SAFE: Add company_id filter manually
DB::select(
    'SELECT * FROM appointments WHERE company_id = ? AND status = ?',
    [auth()->user()->company_id, 'scheduled']
);
```

---

## Common Performance Patterns

### Pattern 1: Dashboard Statistics

```php
// ❌ SLOW: Multiple count queries
$stats = [
    'total' => Appointment::count(),
    'scheduled' => Appointment::where('status', 'scheduled')->count(),
    'completed' => Appointment::where('status', 'completed')->count(),
];

// ✅ FAST: Single query with grouping
$stats = Appointment::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status');
```

### Pattern 2: Recent Items with Relations

```php
// ❌ POTENTIAL N+1
$appointments = Appointment::latest()->take(10)->get();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name;  // N+1 if not eager loaded
}

// ✅ OPTIMIZED
$appointments = Appointment::with('customer')
    ->latest()
    ->take(10)
    ->get();
```

### Pattern 3: Filtered Counts

```php
// ❌ INEFFICIENT: Multiple database queries
$totalCustomers = Customer::count();
$activeCustomers = Customer::where('status', 'active')->count();
$vipCustomers = Customer::where('is_vip', true)->count();

// ✅ EFFICIENT: Single query with conditional aggregation
$stats = Customer::selectRaw("
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_vip = 1 THEN 1 ELSE 0 END) as vip
")->first();
```

### Pattern 4: Paginated Lists

```php
// ✅ GOOD: Use pagination instead of ->all()
$appointments = Appointment::with('customer', 'service')
    ->latest()
    ->paginate(25);

// ✅ BETTER: Add specific ordering for consistency
$appointments = Appointment::with('customer', 'service')
    ->orderBy('starts_at', 'desc')
    ->orderBy('id', 'desc')  // Tie-breaker for same start times
    ->paginate(25);
```

---

## Caching Strategies

### Cache Expensive Calculations

```php
// ❌ UNCACHED: Calculated on every request
public function getDashboardStats()
{
    return [
        'total_revenue' => Appointment::sum('price'),
        'avg_booking' => Appointment::avg('price'),
        'total_customers' => Customer::count(),
    ];
}

// ✅ CACHED: 5-minute TTL
public function getDashboardStats()
{
    return Cache::remember(
        "company.{$this->id}.dashboard_stats",
        now()->addMinutes(5),
        function () {
            return [
                'total_revenue' => Appointment::sum('price'),
                'avg_booking' => Appointment::avg('price'),
                'total_customers' => Customer::count(),
            ];
        }
    );
}
```

### Invalidate Cache on Data Change

```php
// In Appointment model
protected static function booted()
{
    static::saved(function ($appointment) {
        Cache::forget("company.{$appointment->company_id}.dashboard_stats");
    });

    static::deleted(function ($appointment) {
        Cache::forget("company.{$appointment->company_id}.dashboard_stats");
    });
}
```

---

## Query Optimization Checklist

Before deploying queries that operate on scoped models:

- [ ] Eager load all relationships that will be accessed
- [ ] Use specific column selection when possible
- [ ] Add composite indexes for common WHERE clauses
- [ ] Test with realistic data volumes (100+ records)
- [ ] Run EXPLAIN to verify index usage
- [ ] Check for N+1 queries with Laravel Telescope
- [ ] Add caching for frequently accessed data
- [ ] Use chunking for batch operations
- [ ] Log any scope bypasses with justification
- [ ] Test as both regular user and super_admin

---

## Debugging Performance Issues

### Enable Query Logging

```php
// In a controller or test
DB::enableQueryLog();

// Your code here
$appointments = Appointment::with('customer')->get();

// Check queries
dump(DB::getQueryLog());
```

### Measure Query Time

```php
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if ($query->time > 100) {  // Log queries over 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms'
        ]);
    }
});
```

### Profile with Laravel Telescope

```bash
# Install Telescope (dev only)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access at /telescope
# Check "Queries" tab for slow queries and N+1 detection
```

### Check Index Usage

```php
// In tinker or test
$explain = DB::select('EXPLAIN SELECT * FROM appointments WHERE company_id = 1');
dump($explain);

// Look for:
// - type: 'ref' (good - using index)
// - type: 'ALL' (bad - full table scan)
// - key: should contain 'company_id'
```

---

## Performance Monitoring

### Key Metrics to Track

```php
// Add to AppServiceProvider::boot()
DB::whenQueryingForLongerThan(100, function ($connection, $event) {
    Log::warning('Slow query', [
        'sql' => $event->sql,
        'time' => $event->time,
        'connection' => $connection->getName(),
    ]);
});
```

### Monitor Scope Usage

```php
// Track CompanyScope application count
if (app()->environment('local')) {
    Event::listen('eloquent.booted: App\Models\*', function ($model) {
        if (in_array(BelongsToCompany::class, class_uses($model))) {
            Cache::increment('debug.companyscope.applied');
        }
    });
}
```

---

## Common Pitfalls

### Pitfall 1: Forgetting Scope in Raw Queries

```php
// ❌ WRONG: Ignores CompanyScope
$count = DB::table('appointments')->count();

// ✅ RIGHT: Use Eloquent or add manual filter
$count = Appointment::count();
// OR
$count = DB::table('appointments')
    ->where('company_id', auth()->user()->company_id)
    ->count();
```

### Pitfall 2: Accessing Relationships in Loops

```php
// ❌ WRONG: N+1 queries
foreach (Appointment::all() as $appointment) {
    echo $appointment->customer->name;
}

// ✅ RIGHT: Eager load
foreach (Appointment::with('customer')->get() as $appointment) {
    echo $appointment->customer->name;
}
```

### Pitfall 3: Over-Eager Loading

```php
// ❌ INEFFICIENT: Loading unnecessary relations
$appointments = Appointment::with([
    'customer',
    'service',
    'staff',
    'branch',
    'call',
    'company'
])->get();

// ✅ EFFICIENT: Load only what you need
$appointments = Appointment::with(['customer', 'service'])->get();
```

### Pitfall 4: Not Using Pagination

```php
// ❌ DANGEROUS: Could load thousands of records
public function index()
{
    return view('appointments.index', [
        'appointments' => Appointment::all()
    ]);
}

// ✅ SAFE: Paginate results
public function index()
{
    return view('appointments.index', [
        'appointments' => Appointment::paginate(25)
    ]);
}
```

---

## Performance Testing Template

```php
namespace Tests\Performance;

use Tests\TestCase;
use App\Models\{Model};
use Illuminate\Support\Facades\DB;

class {Model}PerformanceTest extends TestCase
{
    public function test_query_performance()
    {
        // Arrange
        {Model}::factory()->count(100)->create([
            'company_id' => $this->company->id
        ]);

        // Act
        $start = microtime(true);
        DB::enableQueryLog();

        $results = {Model}::with(['relation1', 'relation2'])->take(20)->get();

        $duration = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();

        // Assert
        $this->assertLessThan(10, $duration, "Query took {$duration}ms");
        $this->assertLessThanOrEqual(3, count($queries), 'N+1 detected');

        dump("✅ {$duration}ms, " . count($queries) . " queries");
    }
}
```

---

## When to Optimize

### Optimize NOW if:
- Query takes >100ms in production
- N+1 queries detected in logs
- User complaints about slowness
- Dashboard takes >2s to load
- Memory usage >256MB per request

### Optimize LATER if:
- Query takes <50ms consistently
- No user complaints
- Data volume <1000 records per company
- No N+1 detected

### DON'T Optimize if:
- It's a one-time migration script
- Query runs <10 times per day
- Premature optimization without measurement

---

## Quick Reference Commands

```bash
# Run performance tests
php artisan test --filter=Performance

# Check slow queries in logs
tail -f storage/logs/laravel.log | grep "Slow query"

# Analyze with Telescope
php artisan telescope:clear
# Then navigate to /telescope/queries

# Check database indexes
php artisan tinker
DB::select("SHOW INDEX FROM appointments WHERE Column_name = 'company_id'");

# Profile a specific route
php artisan route:list | grep appointments
# Add ?XDEBUG_PROFILE=1 to URL (if Xdebug enabled)
```

---

**Last Updated**: 2025-10-02
**Maintained By**: Platform Engineering Team
**Questions?** See full analysis in `/claudedocs/companyscope_performance_analysis.md`
