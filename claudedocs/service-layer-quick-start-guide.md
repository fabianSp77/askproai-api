# Service Layer Quick Start Guide
## Implementation Examples and Best Practices

**Companion to**: Service Layer Architecture Design
**Date**: 2025-10-04

---

## 1. QUICK START: 30-Minute Setup

### Step 1: Create Directory Structure (5 minutes)

```bash
# From project root: /var/www/api-gateway
mkdir -p app/Services/{Contracts,Analytics,QueryBuilders}
mkdir -p tests/Unit/Services
mkdir -p tests/Feature/Widgets
```

### Step 2: Create Base Contract (5 minutes)

Create `/app/Services/Contracts/AnalyticsServiceContract.php`:

```php
<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface AnalyticsServiceContract
{
    public function getScopedQuery(int $companyId): Builder;
    public function getMetrics(int $companyId, array $options = []): array;
    public function getChartData(int $companyId, int $days = 7): array;
    public function clearCache(int $companyId): void;
}
```

### Step 3: Copy Abstract Service (10 minutes)

Copy the complete `AbstractAnalyticsService` from the main design document to:
`/app/Services/Analytics/AbstractAnalyticsService.php`

### Step 4: Register Services in Container (5 minutes)

Add to `/app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    // Register analytics services
    $this->app->bind(
        \App\Services\Contracts\AnalyticsServiceContract::class,
        \App\Services\Analytics\PolicyAnalyticsService::class
    );

    // Singleton registration for performance
    $this->app->singleton(\App\Services\Analytics\PolicyAnalyticsService::class);
    $this->app->singleton(\App\Services\Analytics\NotificationAnalyticsService::class);
    $this->app->singleton(\App\Services\Analytics\CustomerComplianceService::class);
}
```

### Step 5: First Service Implementation (5 minutes)

Create a simple service to verify setup:

```php
<?php

namespace App\Services\Analytics;

use App\Services\Contracts\AnalyticsServiceContract;
use Illuminate\Database\Eloquent\Builder;

class TestAnalyticsService extends AbstractAnalyticsService implements AnalyticsServiceContract
{
    public function getScopedQuery(int $companyId): Builder
    {
        return $this->scopeToCompany(\App\Models\PolicyConfiguration::query(), $companyId);
    }

    public function getMetrics(int $companyId, array $options = []): array
    {
        return [
            'test' => 'Service layer working!',
            'company_id' => $companyId,
        ];
    }

    public function getChartData(int $companyId, int $days = 7): array
    {
        return [1, 2, 3, 4, 5, 6, 7];
    }
}
```

### Step 6: Verify Setup with Tinker

```bash
php artisan tinker

# Test service instantiation
$service = app(\App\Services\Analytics\TestAnalyticsService::class);

# Test methods
$service->getMetrics(1);
// Should output: ["test" => "Service layer working!", "company_id" => 1]
```

---

## 2. IMPLEMENTATION PATTERNS

### Pattern 1: Simple Metric Calculation

**Use Case**: Count active policies for a company

```php
// In service class
public function getActivePolicyCount(int $companyId): int
{
    return $this->remember($companyId, 'active_count', function () use ($companyId) {
        return $this->getScopedQuery($companyId)
            ->where('is_active', true)
            ->count();
    });
}

// In widget
protected function getStats(): array
{
    $companyId = auth()->user()->company_id;
    $count = $this->service->getActivePolicyCount($companyId);

    return [
        Stat::make('Active Policies', $count)
            ->description('Currently active')
            ->color('success'),
    ];
}
```

### Pattern 2: Complex Aggregation with Date Range

**Use Case**: Calculate compliance rate over time period

```php
// In service class
public function getComplianceRate(int $companyId, int $days = 30): float
{
    return $this->remember($companyId, 'compliance_rate', function () use ($companyId, $days) {
        $dateRange = $this->getDateRange($days);

        $violations = $this->getViolationQueryBuilder($companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('count');

        $totalAppointments = \App\Models\Appointment::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return $this->calculateRate($totalAppointments - $violations, $totalAppointments);
    }, ['days' => $days]);
}

// Helper method in same service
protected function getViolationQueryBuilder(int $companyId): Builder
{
    return \App\Models\AppointmentModificationStat::query()
        ->whereHas('customer', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->where('stat_type', 'violation');
}
```

### Pattern 3: Polymorphic Relationship Scoping

**Use Case**: Query notifications across polymorphic entities

```php
// In service class
public function getTotalSent(int $companyId, int $days = 30): int
{
    return $this->remember($companyId, 'total_sent', function () use ($companyId, $days) {
        $dateRange = $this->getDateRange($days);

        // Use inherited buildPolymorphicCompanyScope method
        return $this->buildPolymorphicCompanyScope(
                \App\Models\NotificationQueue::query(),
                $companyId,
                'notificationConfiguration.configurable'
            )
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereIn('status', ['sent', 'delivered'])
            ->count();
    }, ['days' => $days]);
}
```

### Pattern 4: Chart Data Generation

**Use Case**: Generate 7-day trend data for visualization

```php
// In service class
public function getViolationsChartData(int $companyId, int $days = 7): array
{
    return $this->remember($companyId, 'chart_violations', function () use ($companyId, $days) {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = $this->getViolationQueryBuilder($companyId)
                ->whereDate('created_at', $date)
                ->sum('count');

            $data[] = (int) $count;
        }

        return $data;
    }, ['days' => $days]);
}

// In widget
protected function getStats(): array
{
    return [
        Stat::make('Violations', $this->service->getViolationCount($companyId, 30))
            ->chart($this->service->getViolationsChartData($companyId, 7))
            ->color('danger'),
    ];
}
```

### Pattern 5: Metric Aggregation

**Use Case**: Return all metrics in single method for efficiency

```php
// In service class
public function getMetrics(int $companyId, array $options = []): array
{
    $days = $options['days'] ?? 30;

    return $this->remember($companyId, 'metrics', function () use ($companyId, $days) {
        // Single cached result contains all metrics
        return [
            'active_policies' => $this->getActivePolicyCount($companyId),
            'violations_30d' => $this->getViolationCount($companyId, $days),
            'compliance_rate' => $this->getComplianceRate($companyId, $days),
            'most_violated_policy' => $this->getMostViolatedPolicy($companyId),
        ];
    }, ['days' => $days]);
}

// In widget - single call gets all metrics
protected function getStats(): array
{
    $metrics = $this->service->getMetrics($companyId);

    return [
        Stat::make('Active Policies', $metrics['active_policies']),
        Stat::make('Violations', $metrics['violations_30d']),
        Stat::make('Compliance', $metrics['compliance_rate'] . '%'),
    ];
}
```

---

## 3. TESTING PATTERNS

### Pattern 1: Basic Service Test

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Analytics\PolicyAnalyticsService;
use App\Models\{Company, PolicyConfiguration};
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolicyAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PolicyAnalyticsService $service;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PolicyAnalyticsService();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function it_counts_active_policies()
    {
        PolicyConfiguration::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $count = $this->service->getActivePolicyCount($this->company->id);

        $this->assertEquals(5, $count);
    }
}
```

### Pattern 2: Multi-Tenant Isolation Test

```php
/** @test */
public function it_enforces_tenant_isolation()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    PolicyConfiguration::factory()->count(5)->create(['company_id' => $company1->id]);
    PolicyConfiguration::factory()->count(10)->create(['company_id' => $company2->id]);

    $count = $this->service->getActivePolicyCount($company1->id);

    $this->assertEquals(5, $count, 'Should only see company 1 data');
}
```

### Pattern 3: Cache Behavior Test

```php
/** @test */
public function it_caches_expensive_queries()
{
    PolicyConfiguration::factory()->count(5)->create([
        'company_id' => $this->company->id,
    ]);

    $metrics1 = $this->service->getMetrics($this->company->id);

    // Add more data
    PolicyConfiguration::factory()->count(10)->create([
        'company_id' => $this->company->id,
    ]);

    // Should still return cached result
    $metrics2 = $this->service->getMetrics($this->company->id);

    $this->assertEquals(5, $metrics2['total_configurations']);
}

/** @test */
public function it_clears_cache_correctly()
{
    PolicyConfiguration::factory()->count(5)->create([
        'company_id' => $this->company->id,
    ]);

    $this->service->getMetrics($this->company->id);

    PolicyConfiguration::factory()->count(10)->create([
        'company_id' => $this->company->id,
    ]);

    $this->service->clearCache($this->company->id);

    $metrics = $this->service->getMetrics($this->company->id);

    $this->assertEquals(15, $metrics['total_configurations']);
}
```

### Pattern 4: Edge Case Testing

```php
/** @test */
public function it_handles_zero_data_gracefully()
{
    $rate = $this->service->getComplianceRate($this->company->id, 30);

    $this->assertEquals(100.0, $rate, 'Should return 100% when no data');
}

/** @test */
public function it_handles_null_values_correctly()
{
    $avgTime = $this->service->getAverageDeliveryTime($this->company->id, 30);

    $this->assertNull($avgTime, 'Should return null when no delivery data');
}
```

---

## 4. COMMON GOTCHAS & SOLUTIONS

### Gotcha 1: Forgetting to Clear Cache on Data Changes

**Problem**:
```php
// User updates a policy
$policy->update(['is_active' => false]);

// Widget still shows old data from cache!
$metrics = $service->getMetrics($companyId);
```

**Solution**: Clear cache in model observers or controllers

```php
// In PolicyConfiguration model
protected static function boot()
{
    parent::boot();

    static::saved(function ($model) {
        app(PolicyAnalyticsService::class)->clearCache($model->company_id);
    });

    static::deleted(function ($model) {
        app(PolicyAnalyticsService::class)->clearCache($model->company_id);
    });
}

// Or in controller
public function update(Request $request, PolicyConfiguration $policy)
{
    $policy->update($request->validated());

    // Clear relevant caches
    app(PolicyAnalyticsService::class)->clearCache($policy->company_id);

    return redirect()->back();
}
```

### Gotcha 2: N+1 Query Problems in Service Methods

**Problem**:
```php
// This will cause N+1 queries!
public function getCustomerMetrics(int $companyId): array
{
    $customers = Customer::where('company_id', $companyId)->get();

    return $customers->map(function ($customer) {
        return [
            'name' => $customer->name,
            'violations' => $customer->appointmentModificationStats()->count(), // N+1!
        ];
    })->toArray();
}
```

**Solution**: Use eager loading

```php
public function getCustomerMetrics(int $companyId): array
{
    $customers = Customer::where('company_id', $companyId)
        ->withCount('appointmentModificationStats') // Eager load count
        ->get();

    return $customers->map(function ($customer) {
        return [
            'name' => $customer->name,
            'violations' => $customer->appointment_modification_stats_count, // No extra query
        ];
    })->toArray();
}
```

### Gotcha 3: Polymorphic Scoping Without Company Relationship

**Problem**:
```php
// Some polymorphic models don't have company relationship
whereHas('company', function ($q) use ($companyId) {
    $q->where('id', $companyId); // Fails for Company model itself!
});
```

**Solution**: Use conditional logic in scope builder

```php
protected function buildPolymorphicCompanyScope(
    Builder $query,
    int $companyId,
    string $relationship
): Builder {
    return $query->whereHas($relationship, function ($q) use ($companyId) {
        $q->where(function ($subQuery) use ($companyId) {
            // Direct company_id (works for all models)
            $subQuery->where('company_id', $companyId)
                // Or has company relationship (for nested models)
                ->orWhereHas('company', function ($companyQuery) use ($companyId) {
                    $companyQuery->where('id', $companyId);
                });
        });
    });
}
```

### Gotcha 4: Cache Key Collisions

**Problem**:
```php
// Two different methods use same cache key!
$this->remember($companyId, 'count', ...); // In method A
$this->remember($companyId, 'count', ...); // In method B - collision!
```

**Solution**: Use descriptive metric names

```php
$this->remember($companyId, 'active_policy_count', ...);
$this->remember($companyId, 'total_violation_count', ...);
```

### Gotcha 5: Forgetting Multi-Tenant Scoping

**Problem**:
```php
// Leaks data across tenants!
public function getAllViolations(): int
{
    return AppointmentModificationStat::where('stat_type', 'violation')->count();
}
```

**Solution**: Always require company_id parameter

```php
// Force tenant scoping at method signature level
public function getAllViolations(int $companyId): int
{
    return AppointmentModificationStat::query()
        ->whereHas('customer', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('stat_type', 'violation')
        ->count();
}
```

---

## 5. PERFORMANCE OPTIMIZATION TIPS

### Tip 1: Batch Chart Data Queries

**Instead of**:
```php
for ($i = 6; $i >= 0; $i--) {
    $data[] = Model::whereDate('created_at', now()->subDays($i))->count(); // 7 queries!
}
```

**Use**:
```php
$results = Model::whereBetween('created_at', [now()->subDays(6), now()])
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
    ->groupBy('date')
    ->get()
    ->keyBy('date');

// Fill in missing dates with 0
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->format('Y-m-d');
    $data[] = $results->get($date)?->count ?? 0;
}
```

### Tip 2: Use Database-Level Calculations

**Instead of**:
```php
$customers = Customer::all();
$avg = $customers->avg(function ($c) {
    return ($c->total_appointments - $c->total_violations) / $c->total_appointments;
});
```

**Use**:
```php
$avg = Customer::select(
    DB::raw('AVG((total_appointments - total_violations) / total_appointments) as avg_rate')
)->value('avg_rate');
```

### Tip 3: Index Optimization

**Add database indexes for frequently queried columns**:

```php
// In migration
Schema::table('appointment_modification_stats', function (Blueprint $table) {
    $table->index(['stat_type', 'created_at']); // For violation queries with date range
    $table->index('customer_id'); // For customer relationship queries
});

Schema::table('notification_queues', function (Blueprint $table) {
    $table->index(['status', 'created_at']); // For delivery rate queries
    $table->index('notification_configuration_id');
});
```

### Tip 4: Smart Caching with Tags

**Use cache tags for efficient invalidation**:

```php
protected function remember(int $companyId, string $metricName, callable $callback, array $params = []): mixed
{
    $key = $this->getCacheKey($companyId, $metricName, $params);

    // Tag cache entries for bulk invalidation
    return Cache::tags(["analytics_{$companyId}", "analytics_all"])->remember(
        $key,
        $this->cacheTtl,
        $callback
    );
}

public function clearCache(int $companyId): void
{
    // Clear only this company's cache
    Cache::tags(["analytics_{$companyId}"])->flush();
}

public static function clearAllCache(): void
{
    // Clear all analytics cache across all companies
    Cache::tags(['analytics_all'])->flush();
}
```

---

## 6. WIDGET REFACTORING CHECKLIST

Use this checklist when refactoring each widget:

```
ANALYSIS PHASE
[ ] Identify all business logic in widget (queries, calculations, transformations)
[ ] List all queries and count total query calls
[ ] Identify code duplication patterns
[ ] Document current cyclomatic complexity (use PHPStan)
[ ] List presentation logic (colors, formatting, icons)

SERVICE DESIGN PHASE
[ ] Design service interface (what methods needed?)
[ ] Identify shared utilities from AbstractAnalyticsService
[ ] Plan caching strategy (which methods to cache, TTL)
[ ] Document expected return types for all methods
[ ] Design multi-tenant scoping approach

IMPLEMENTATION PHASE (TDD)
[ ] Write unit test for first service method (RED)
[ ] Implement service method (GREEN)
[ ] Refactor for clarity (REFACTOR)
[ ] Repeat for all service methods
[ ] Verify 95%+ code coverage

WIDGET REFACTORING PHASE
[ ] Add service dependency injection to widget
[ ] Replace business logic with service calls
[ ] Keep only presentation logic in widget
[ ] Update widget tests (if they exist)
[ ] Write new integration tests

TESTING PHASE
[ ] Run all unit tests (must pass)
[ ] Run integration tests (must pass)
[ ] Manual testing in local environment
[ ] Test with multiple companies (tenant isolation)
[ ] Performance testing (query count, response time)

DEPLOYMENT PHASE
[ ] Code review
[ ] Deploy to staging
[ ] Smoke test in staging
[ ] Monitor staging for 24 hours
[ ] Deploy to production
[ ] Monitor production for 48 hours

DOCUMENTATION PHASE
[ ] Update service documentation
[ ] Add PHPDoc comments
[ ] Document any gotchas or edge cases
[ ] Update README with new architecture
```

---

## 7. TROUBLESHOOTING GUIDE

### Issue: "Service not found" error in widget

**Error**: `Target [App\Services\Analytics\PolicyAnalyticsService] is not instantiable`

**Solution**: Register service in AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(\App\Services\Analytics\PolicyAnalyticsService::class);
}
```

### Issue: Cache not clearing

**Problem**: Updated data but widget shows old values

**Solution 1**: Check cache driver supports tags

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'), // Must support tags (redis, memcached)
```

**Solution 2**: Manual cache clear

```bash
php artisan cache:clear
```

**Solution 3**: Verify cache clearing code

```php
// In controller after data change
app(PolicyAnalyticsService::class)->clearCache($companyId);
```

### Issue: Multi-tenant data leakage

**Problem**: User sees data from other companies

**Solution**: Add test to verify tenant isolation

```php
/** @test */
public function it_never_returns_other_company_data()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    PolicyConfiguration::factory()->count(100)->create(['company_id' => $company2->id]);

    $count = $this->service->getActivePolicyCount($company1->id);

    $this->assertEquals(0, $count, 'Must not see other company data');
}
```

### Issue: Slow widget rendering

**Problem**: Widget takes >1 second to load

**Solution 1**: Check query count with debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

**Solution 2**: Add database query logging

```php
DB::enableQueryLog();
$metrics = $service->getMetrics($companyId);
dd(DB::getQueryLog()); // Check for N+1 queries
```

**Solution 3**: Verify caching is working

```php
// First call should hit database
$metrics1 = $service->getMetrics($companyId);

// Second call should be cached (much faster)
$metrics2 = $service->getMetrics($companyId);
```

---

## 8. NEXT STEPS

**After completing this quick start**:

1. Review full architecture design document
2. Implement your first service (recommend NotificationAnalyticsService for biggest impact)
3. Write comprehensive tests
4. Refactor corresponding widget
5. Deploy to staging and monitor
6. Repeat for remaining widgets

**Resources**:
- Main architecture document: `/var/www/api-gateway/claudedocs/service-layer-architecture-design.md`
- Laravel Service Container: https://laravel.com/docs/10.x/container
- Testing Guide: https://laravel.com/docs/10.x/testing

---

**Document Version**: 1.0
**Last Updated**: 2025-10-04
**Companion Document**: service-layer-architecture-design.md
