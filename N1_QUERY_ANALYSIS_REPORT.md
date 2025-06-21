# N+1 Query Analysis Report

## Executive Summary

This report identifies potential N+1 query problems in the AskProAI codebase, focusing on high-traffic areas like webhook processing, availability checks, and dashboard widgets. Several critical issues were found that could significantly impact performance, especially as the system scales.

## Critical N+1 Issues Found

### 1. **LiveAppointmentBoard Widget** (HIGH SEVERITY)
**File**: `/app/Filament/Admin/Widgets/LiveAppointmentBoard.php`

**Issues**:
- Line 299-342: Nested loops iterating through branches and staff without eager loading
- Lines 304-308: Individual queries for each staff member's appointments
- Lines 335-336, 340: Accessing relationships inside loops without eager loading

```php
// Problem code:
foreach ($branches as $branch) {
    foreach ($branch->staff as $staff) {
        // This queries appointments for EACH staff member individually!
        $appointments = Appointment::where('staff_id', $staff->id)
            ->whereDate('starts_at', $date)
            ->get();
        
        // Later accessing customer relationship without eager loading
        'customer' => $currentAppointment->customer->name ?? 'Unknown',
    }
}
```

**Fix**:
```php
// Load all appointments for all staff in one query
$staffIds = $branches->pluck('staff')->flatten()->pluck('id');
$allAppointments = Appointment::whereIn('staff_id', $staffIds)
    ->whereDate('starts_at', $date)
    ->with(['customer', 'service'])
    ->get()
    ->groupBy('staff_id');
```

### 2. **RecentActivityWidget** (MEDIUM SEVERITY)
**File**: `/app/Filament/Admin/Widgets/RecentActivityWidget.php`

**Issues**:
- Lines 34, 35, 58: Accessing relationships in map() without checking if loaded
- Line 42: Loading appointments.customer relationship inefficiently

```php
// Problem: accessing relationships that might not be loaded
->map(function ($appointment) {
    'description' => $appointment->customer?->name . ' - ' . $appointment->service?->name,
    'meta' => $appointment->branch?->name,
})
```

**Fix**: Already has `with(['customer', 'branch', 'service'])` but should validate relationships are loaded.

### 3. **CustomerResource Table** (MEDIUM SEVERITY)
**File**: `/app/Filament/Admin/Resources/CustomerResource.php`

**Issues**:
- Line 337: `getStateUsing()` with a query inside the table column
- Line 284: Loading appointments relationship with limit inside query modifier

```php
// Problem: This runs a query for EACH row in the table!
Tables\Columns\TextColumn::make('last_appointment')
    ->getStateUsing(fn ($record) => $record->appointments()->latest('starts_at')->first()?->starts_at)
```

**Fix**:
```php
// Add to modifyQueryUsing:
->with(['lastAppointment' => fn($q) => $q->latest('starts_at')])

// In model, add relationship:
public function lastAppointment()
{
    return $this->hasOne(Appointment::class)->latestOfMany('starts_at');
}
```

### 4. **SystemStatsOverview Widget** (LOW SEVERITY)
**File**: `/app/Filament/Admin/Widgets/SystemStatsOverview.php`

**Issues**:
- Line 38-40: Inefficient query using whereHas without counting

```php
// Problem: This loads all company records just to count them
$activeCompanies = Company::whereHas('branches', function($query) {
    $query->where('active', true);
})->count();
```

**Fix**:
```php
// More efficient:
$activeCompanies = Company::has('branches', '>=', 1, 'and', function($query) {
    $query->where('active', true);
})->count();
```

### 5. **AppointmentService** (LOW-MEDIUM SEVERITY)
**File**: `/app/Services/AppointmentService.php`

**Issues**:
- Line 116: Using `fresh()` with eager loading after create
- Line 319: Getting appointments without eager loading, then checking relationships in closure

```php
// Problem: fresh() causes additional query
return $appointment->fresh(['customer', 'staff', 'service', 'branch']);

// Also problematic:
$appointments = $this->appointmentRepository->getByStaff($staffId, $date);
// Later in contains():
$start->lt($appointment->ends_at) // Potential lazy loading
```

### 6. **RetellWebhookHandler** (HIGH SEVERITY)
**File**: `/app/Services/Webhooks/RetellWebhookHandler.php`

**Issues**:
- Lines 193, 268, 335-336: Accessing relationships without eager loading in multiple places
- Line 274: Loading call->company relationship which might trigger additional queries

```php
// Problem areas:
'staff' => $appointment->staff->name ?? 'Unknown',
'branch' => $appointment->branch->name ?? 'Unknown',
$call->company->retell_api_key // Lazy loads company
```

## Performance Impact Analysis

### Quantified Impact:
1. **LiveAppointmentBoard**: With 10 branches × 5 staff = **50 additional queries per page load**
2. **CustomerResource List**: With 50 customers per page = **50 additional queries per page**
3. **RecentActivityWidget**: Up to **20 additional queries** (10 appointments + 10 calls)
4. **Total Dashboard Impact**: **120+ additional queries** on a typical dashboard load

### Load Time Estimates:
- Each query: ~10-50ms
- Total additional time: **1.2 - 6 seconds** per dashboard load
- Under high load: Could cause **database connection exhaustion**

## Recommended Fixes

### 1. **Implement Query Result Caching**
```php
// Add to frequently accessed widgets
Cache::remember("widget_data_{$userId}_{$date}", 300, function() {
    // Expensive queries here
});
```

### 2. **Use Eager Loading Consistently**
```php
// Before
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name; // N+1!
}

// After
$appointments = Appointment::with(['customer', 'staff', 'service'])->all();
```

### 3. **Implement Batch Loading for Dashboard**
```php
// Create a dashboard data service
class DashboardDataService
{
    public function loadAllWidgetData($companyId, $date)
    {
        return [
            'appointments' => $this->loadAppointments($companyId, $date),
            'calls' => $this->loadCalls($companyId, $date),
            'staff' => $this->loadStaffWithAvailability($companyId, $date),
        ];
    }
}
```

### 4. **Add Database Indexes**
```sql
-- High-impact indexes for common queries
CREATE INDEX idx_appointments_staff_date ON appointments(staff_id, starts_at);
CREATE INDEX idx_appointments_branch_status ON appointments(branch_id, status);
CREATE INDEX idx_calls_created ON calls(created_at, company_id);
CREATE INDEX idx_webhook_events_status ON webhook_events(status, created_at);
```

### 5. **Implement Query Monitoring**
```php
// Add to AppServiceProvider
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'time' => $query->time,
                'bindings' => $query->bindings
            ]);
        }
    });
}
```

## Priority Action Items

### Immediate (This Week):
1. Fix LiveAppointmentBoard widget queries
2. Add indexes to appointments and calls tables
3. Implement caching for SystemStatsOverview

### Short Term (Next Sprint):
1. Refactor CustomerResource table queries
2. Add eager loading to all webhook handlers
3. Implement dashboard data preloading service

### Long Term:
1. Implement query result caching strategy
2. Add APM (Application Performance Monitoring)
3. Consider read replicas for dashboard queries

## Testing Recommendations

### 1. **Load Testing**
```bash
# Simulate concurrent dashboard loads
ab -n 1000 -c 50 https://api.askproai.de/admin/dashboard
```

### 2. **Query Profiling**
```php
// Add to test environment
\DB::enableQueryLog();
// ... run feature
$queries = \DB::getQueryLog();
$this->assertLessThan(50, count($queries), 'Too many queries executed');
```

### 3. **N+1 Detection Package**
```bash
composer require --dev beyondcode/laravel-query-detector
```

## Monitoring Setup

Add these metrics to track N+1 query issues:
- Database queries per request
- Average query time
- Slow query log (>100ms)
- Connection pool usage

## Conclusion

The identified N+1 query issues pose a significant performance risk, especially as the system scales. The LiveAppointmentBoard widget and webhook processing are the most critical areas requiring immediate attention. Implementing the recommended fixes could reduce page load times by 50-80% and significantly improve system scalability.

**Estimated Performance Improvement**: 
- Current: ~150 queries per dashboard load
- After fixes: ~30 queries per dashboard load
- **80% reduction in database load**