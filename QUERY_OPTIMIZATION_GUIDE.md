# Query Optimization Guide for AskProAI

## Overview

This guide documents the comprehensive query optimization implementation for the AskProAI platform, designed to improve database performance and reduce query execution times.

## Components

### 1. QueryOptimizer Service

The `QueryOptimizer` service provides methods to optimize database queries:

```php
use App\Services\QueryOptimizer;

$optimizer = app(QueryOptimizer::class);

// Optimize appointment queries
$query = Appointment::query();
$optimizer->optimizeAppointmentQuery($query);

// Force index usage
$optimizer->forceIndex($query, 'appointments', 'idx_appointments_dates');

// Add query hints
$optimizer->addQueryHint($query, 'big_result');
```

### 2. QueryMonitor Service

Monitors queries and logs slow queries:

```php
use App\Services\QueryMonitor;

$monitor = app(QueryMonitor::class);

// Enable monitoring
$monitor->setSlowQueryThreshold(1000); // 1 second
$monitor->enable();

// Get statistics
$stats = $monitor->getStats();
$slowQueries = $monitor->getSlowQueries();
```

### 3. QueryCache Service

Caches complex aggregations and statistics:

```php
use App\Services\QueryCache;

$cache = app(QueryCache::class);

// Get cached statistics
$appointmentStats = $cache->getAppointmentStats($companyId, 'month');
$customerMetrics = $cache->getCustomerMetrics($companyId);
$callStats = $cache->getCallStats($companyId);

// Clear cache
$cache->clearCompanyCache($companyId);
```

## Database Indexes

The following indexes have been added to optimize query performance:

### Appointments Table
- `idx_appointments_company_status_date`: (company_id, status, starts_at)
- `idx_appointments_branch_date`: (branch_id, starts_at)
- `idx_appointments_staff_date_status`: (staff_id, starts_at, status)
- `idx_appointments_customer_status`: (customer_id, status)
- `idx_appointments_dates`: (starts_at, ends_at)
- `idx_appointments_status`: (status)

### Customers Table
- `idx_customers_company`: (company_id)
- `idx_customers_phone`: (phone)
- `idx_customers_email`: (email)
- `idx_customers_company_phone`: (company_id, phone)

### Calls Table
- `idx_calls_company`: (company_id)
- `idx_calls_from_number`: (from_number)
- `idx_calls_status`: (status)
- `idx_calls_created_at`: (created_at)
- `idx_calls_company_date`: (company_id, created_at)
- `idx_calls_customer`: (customer_id)

### Staff Table
- `idx_staff_company`: (company_id)
- `idx_staff_branch`: (branch_id)
- `idx_staff_home_branch`: (home_branch_id)
- `idx_staff_company_active`: (company_id, active)
- `idx_staff_bookable_active`: (is_bookable, active)

## Query Scopes

Common query patterns have been implemented as scopes:

### Appointment Scopes
```php
// Get upcoming appointments
Appointment::upcoming()->get();

// Get today's appointments
Appointment::today()->get();

// Get appointments in date range
Appointment::dateRange($start, $end)->get();

// Get appointments by status
Appointment::byStatus(['scheduled', 'confirmed'])->get();

// Get appointments with relations
Appointment::withRelations()->get();
```

### Customer Scopes
```php
// Get active customers
Customer::active()->get();

// Search customers by phone
Customer::byPhone('+49123456789')->get();

// Get customers with appointment count
Customer::withAppointmentCount()->get();

// Search customers
Customer::search('John')->get();
```

### Call Scopes
```php
// Get recent calls
Call::recent(7)->get(); // Last 7 days

// Get successful calls
Call::successful()->get();

// Get calls by phone number
Call::fromNumber('+49123456789')->get();
```

### Staff Scopes
```php
// Get available staff
Staff::available()->get();

// Get staff for branch
Staff::forBranch($branchId)->get();

// Get staff with services
Staff::withServices([$serviceId1, $serviceId2])->get();
```

## Query Builder Macros

The system adds useful macros to the query builder:

```php
// Automatically optimize queries
$appointments = DB::table('appointments')->optimized()->get();

// Cached count
$count = Customer::where('company_id', $companyId)->cachedCount(5); // Cache for 5 minutes

// Cached sum
$revenue = Appointment::completed()->cachedSum('price', 10); // Cache for 10 minutes

// Force index on Eloquent queries
$appointments = Appointment::forceIndex('idx_appointments_dates')->get();

// Add query hints
$customers = Customer::hint('no_cache')->search($term)->get();
```

## Console Commands

### Analyze Query Performance
```bash
# Analyze all queries
php artisan query:analyze

# Analyze specific table
php artisan query:analyze --table=appointments

# Clear statistics after analysis
php artisan query:analyze --clear
```

### Enable Query Monitoring
```bash
# Enable with default threshold (1000ms)
php artisan query:monitor

# Enable with custom threshold
php artisan query:monitor --threshold=500
```

## Best Practices

### 1. Use Eager Loading
```php
// Bad - N+1 queries
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name;
}

// Good - 2 queries total
$appointments = Appointment::with('customer')->get();
```

### 2. Use Query Scopes
```php
// Bad - Repeating query logic
$appointments = Appointment::where('starts_at', '>', now())
    ->where('status', '!=', 'cancelled')
    ->orderBy('starts_at')
    ->get();

// Good - Using scope
$appointments = Appointment::upcoming()->get();
```

### 3. Cache Aggregations
```php
// Bad - Expensive calculation every time
$stats = DB::table('appointments')
    ->where('company_id', $companyId)
    ->groupBy('status')
    ->selectRaw('status, COUNT(*) as count')
    ->get();

// Good - Cached result
$stats = $queryCache->getAppointmentStats($companyId);
```

### 4. Use Appropriate Indexes
```php
// Force index when beneficial
$appointments = Appointment::dateRange($start, $end)
    ->forceIndex('idx_appointments_dates')
    ->get();
```

### 5. Monitor Performance
```php
// In development, check slow query log
tail -f storage/logs/slow_queries.log

// Analyze query patterns regularly
php artisan query:analyze
```

## Troubleshooting

### Slow Queries
1. Check if appropriate indexes exist
2. Use `EXPLAIN` to analyze query execution plan
3. Consider query caching for complex aggregations
4. Check for N+1 query problems

### High Memory Usage
1. Use cursor pagination for large datasets
2. Select only needed columns
3. Process data in chunks

### Cache Issues
1. Clear specific company cache: `$queryCache->clearCompanyCache($companyId)`
2. Clear all caches: `$queryCache->clearAllCaches()`
3. Check cache statistics: `$queryCache->getCacheStats()`

## Migration Guide

To apply the query optimizations:

1. Run the index migration:
```bash
php artisan migrate --path=database/migrations/2025_06_13_134021_add_query_optimization_indexes.php
```

2. Run the slow query log migration:
```bash
php artisan migrate --path=database/migrations/2025_06_13_134431_create_slow_query_log_table.php
```

3. Update your queries to use the new scopes and optimization features.

4. Monitor performance improvements using:
```bash
php artisan query:analyze
```

## Performance Metrics

After implementing these optimizations, you should see:
- 50-80% reduction in query execution time for indexed queries
- 90%+ cache hit rate for dashboard statistics
- Significant reduction in database load during peak hours
- Better response times for API endpoints

Monitor these metrics using the query analysis tools provided.