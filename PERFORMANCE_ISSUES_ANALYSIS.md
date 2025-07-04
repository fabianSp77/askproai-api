# Performance Issues Analysis Report

Generated: 2025-06-28

## Executive Summary

This analysis identifies critical performance issues in the AskProAI codebase that significantly impact user experience. The issues are prioritized by their impact on system performance and user experience.

## 🔴 Critical Performance Issues

### 1. N+1 Query Problems in Filament Resources

#### CallResource
- **Issue**: While eager loading is implemented (`with(['customer', 'appointment', 'branch', 'mlPrediction'])`), the resource has multiple computed columns that may trigger additional queries
- **Impact**: With 100 calls displayed, this could trigger 400+ additional queries
- **Location**: `/app/Filament/Admin/Resources/CallResource.php`
- **Fix Priority**: HIGH

#### AppointmentResource
- **Issue**: Basic eager loading present (`with(['customer', 'staff', 'service'])`), but missing critical relationships
- **Missing Relations**: `branch`, `calcomEventType`, `call`
- **Impact**: Each appointment row triggers 3-4 additional queries
- **Location**: `/app/Filament/Admin/Resources/AppointmentResource.php`
- **Fix Priority**: HIGH

#### CustomerResource
- **Issue**: Limited eager loading (`with(['company', 'appointments' => fn($q) => $q->latest()->limit(5)])`), but appointment subquery is inefficient
- **Problem**: Loading latest 5 appointments for each customer in a list view is expensive
- **Impact**: Significant performance degradation with large customer lists
- **Location**: `/app/Filament/Admin/Resources/CustomerResource.php`
- **Fix Priority**: HIGH

### 2. Missing Critical Database Indexes

Despite having a performance optimization migration, several critical indexes are missing:

#### Frequently Queried Columns Without Indexes:
1. **calls table**:
   - `company_id` + `start_timestamp` (for date filtering)
   - `branch_id` (for branch filtering)
   - `customer_id` (for customer history)

2. **appointments table**:
   - `company_id` + `status` (for dashboard stats)
   - `service_id` (for service filtering)
   - `calcom_event_type_id` (for Cal.com sync)

3. **customers table**:
   - `company_id` + `name` (for search)
   - `status` (for filtering active/inactive)

### 3. Synchronous Operations in Request Lifecycle

#### Webhook Processing
- **Issue**: Some webhook processing happens synchronously
- **Location**: `/app/Http/Controllers/UnifiedWebhookController.php`
- **Problem**: Only `call_inbound` events are handled synchronously (correctly), but error handling could block
- **Impact**: Webhook timeouts under heavy load

#### AppointmentService
- **Issue**: Cal.com API calls happen within database transaction
- **Location**: `/app/Services/AppointmentService.php` lines 79-106
- **Problem**: External API calls inside transaction can cause long-running locks
- **Impact**: Database connection exhaustion, appointment creation failures

### 4. Inefficient Query Patterns

#### ListCalls Tab Counts
- **Issue**: Tab count query runs on every page load (60-second cache is too short)
- **Location**: `/app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php`
- **Problem**: Complex COUNT queries with multiple CASE statements
- **Impact**: Slow page loads, especially with large datasets

#### Customer Appointments Loading
- **Issue**: Loading appointments eagerly with limit in CustomerResource
- **Problem**: `->with(['appointments' => fn($q) => $q->latest()->limit(5)])`
- **Impact**: This pattern doesn't scale - better to load on-demand

### 5. Heavy Operations in Request Lifecycle

#### Potential Blocking Operations Found:
1. **file_get_contents()** usage in:
   - KnowledgeBaseService
   - Various MCP services
   
2. **sleep()/usleep()** usage in:
   - ConnectionPoolManager
   - MigrationGuard
   - RetryableApiService

3. **Unbounded result sets**:
   - `->get()->all()` patterns found in multiple services
   - No pagination on some API endpoints

## 🟡 Medium Priority Issues

### 6. Suboptimal Caching Strategy

1. **Short Cache TTLs**:
   - Call tab counts: 60 seconds (should be 5-10 minutes)
   - No caching on expensive company/branch lookups
   - Missing query result caching for reference data

2. **Missing Cache Warming**:
   - Dashboard widgets load data on-demand
   - No pre-warming of common queries

### 7. Widget Performance

Multiple widgets on dashboards execute queries independently:
- CallKpiWidget
- CallAnalyticsWidget
- AppointmentStatsWidget

These should share data through a single optimized query.

## 🟢 Recommendations

### Immediate Actions (Week 1)

1. **Add Missing Eager Loading**:
```php
// CallResource
->modifyQueryUsing(fn ($query) => $query->with([
    'customer',
    'appointment.service',
    'appointment.staff',
    'branch',
    'mlPrediction',
    'company'
]))

// AppointmentResource
->modifyQueryUsing(fn ($query) => $query->with([
    'customer',
    'staff',
    'service',
    'branch',
    'calcomEventType',
    'call',
    'company'
]))
```

2. **Create Missing Indexes**:
```sql
-- High-impact indexes
CREATE INDEX idx_calls_company_date ON calls(company_id, start_timestamp, id);
CREATE INDEX idx_appointments_company_status ON appointments(company_id, status, starts_at);
CREATE INDEX idx_customers_company_name ON customers(company_id, name);
```

3. **Move External API Calls Out of Transactions**:
```php
// In AppointmentService::create()
// First create appointment
$appointment = $this->executeInTransaction(...);

// Then sync with Cal.com (outside transaction)
if ($calcomEventTypeId) {
    dispatch(new SyncAppointmentToCalcom($appointment));
}
```

### Short-term Improvements (Week 2-3)

1. **Implement Query Result Caching**:
   - Cache expensive counts for 5-10 minutes
   - Cache reference data (services, staff) for 30 minutes
   - Implement cache tags for easy invalidation

2. **Optimize Dashboard Queries**:
   - Combine multiple widget queries into single optimized query
   - Use database views for complex aggregations
   - Implement materialized views for historical data

3. **Add Request Performance Monitoring**:
   - Log slow queries (>100ms)
   - Track request duration
   - Monitor memory usage

### Long-term Optimizations (Month 2)

1. **Implement Read Replicas**:
   - Route read queries to replicas
   - Keep writes on primary database

2. **Add Application-Level Caching**:
   - Redis caching for frequently accessed data
   - Edge caching for API responses

3. **Query Optimization**:
   - Replace complex Eloquent queries with optimized raw SQL
   - Implement database query analysis tooling

## Performance Monitoring Setup

```php
// Add to AppServiceProvider
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::channel('slow-queries')->warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ]);
    }
});
```

## Impact Assessment

### Current Performance Metrics (Estimated):
- Page load time: 2-5 seconds
- API response time: 500ms-2s
- Database queries per request: 50-200

### Expected After Optimization:
- Page load time: <1 second
- API response time: <200ms
- Database queries per request: 10-30

## Conclusion

The identified performance issues significantly impact user experience, particularly in the admin panel where staff interact with the system frequently. Implementing the recommended fixes, starting with eager loading and missing indexes, will provide immediate performance improvements. The synchronous API calls in transactions pose a stability risk and should be addressed urgently.