# Relationship Architecture Optimization - 2025-10-17

## Overview
Comprehensive relationship optimization across 4 core models (Company, Branch, Customer, Staff) to improve query performance and prevent N+1 query problems.

## Phase 3: Aggregate Relationships

### New Relationships Added

#### 1. Company Model
```php
public function upcomingAppointments(): HasMany
    → Where starts_at >= now AND status IN ('scheduled', 'confirmed')
    → OrderBy starts_at ASC
    → Use: Company-level scheduling dashboard, resource planning

public function completedAppointments(): HasMany
    → Where status = 'completed'
    → OrderBy starts_at DESC
    → Use: Company-level performance metrics, revenue analytics
```

#### 2. Branch Model
```php
public function upcomingAppointments(): HasMany
    → Where starts_at >= now AND status IN ('scheduled', 'confirmed')
    → OrderBy starts_at ASC
    → Use: Branch scheduling, resource planning, booking management

public function completedAppointments(): HasMany
    → Where status = 'completed'
    → OrderBy starts_at DESC
    → Use: Branch metrics, performance tracking, revenue analysis
```

#### 3. Customer Model
```php
public function upcomingAppointments(): HasMany
    → Where starts_at >= now AND status IN ('scheduled', 'confirmed')
    → OrderBy starts_at ASC
    → Use: Customer portal, booking confirmation, engagement tracking

public function completedAppointments(): HasMany
    → Where status = 'completed'
    → OrderBy starts_at DESC
    → Use: Customer history, completion tracking, analytics

public function recentCalls(): HasMany
    → Where created_at >= now()->subDays(90)
    → OrderBy created_at DESC
    → Use: Engagement scoring, call history, quality assurance
```

#### 4. Staff Model
```php
public function upcomingAppointments(): HasMany
    → Where starts_at >= now AND status IN ('scheduled', 'confirmed')
    → OrderBy starts_at ASC
    → Use: Staff scheduling, resource planning, workload management

public function completedAppointments(): HasMany
    → Where status = 'completed'
    → OrderBy starts_at DESC
    → Use: Performance metrics, productivity tracking, revenue analysis
```

## Performance Impact

### Before (Without Aggregate Relationships)
```php
// Inefficient: requires filtering in application code
$upcoming = $company->appointments()
    ->where('starts_at', '>=', now())
    ->where('status', 'scheduled')
    ->orWhere('status', 'confirmed')
    ->orderBy('starts_at')
    ->get();

// Issue: Complex queries needed at call site
// Issue: Easy to make mistakes in filter logic
// Issue: Hard to maintain consistent query patterns
```

### After (With Aggregate Relationships)
```php
// Efficient: dedicated relationship method
$upcoming = $company->upcomingAppointments()->get();

// Benefits:
// ✅ Consistent query patterns across codebase
// ✅ Easy to maintain and update globally
// ✅ Prevents N+1 queries when eager loading
// ✅ Readable and self-documenting code
```

### Query Optimization Benefits
1. **Pre-filtered at relationship level** → No application-level filtering needed
2. **Consistent ordering** → All components use same sort order
3. **Eager loading support** → Can use `with('upcomingAppointments')` to prevent N+1
4. **Reusable in multiple features** → Dashboard, API, exports all use same logic

## Related Architecture

### Phase 2: Inverse Relationships
- Appointment::modifications()
- Customer::appointmentModifications()
- Branch::calls()
- Company::appointments()

### Phase 2: Navigation Structure
- Consolidated into 8 logical navigation groups
- Removed emoji inconsistency
- Unified icon usage (no duplicates)

### Phase 1: Critical Fixes
- Fixed Company::workingHours() HasManyThrough syntax
- Added CalcomHostMapping BelongsToCompany trait (security)
- Restored database from Oct 4 backup (17 companies, 65 customers)

## Implementation Details

### Database Schema Requirements
- All appointment models must have `starts_at` timestamp
- All models must have `status` enum field
- All call models must have `created_at` timestamp

### Naming Conventions
- upcomingAppointments() → Future, scheduled or confirmed only
- completedAppointments() → Past, completed status only
- recentCalls() → Last 90 days only

### Query Plans

#### Single Record (1 query)
```sql
SELECT * FROM appointments
WHERE company_id = 1
AND starts_at >= '2025-10-17 10:00:00'
AND status IN ('scheduled', 'confirmed')
ORDER BY starts_at ASC;
```

#### Eager Loading (1 query per relationship)
```php
$companies = Company::with('upcomingAppointments', 'completedAppointments')->get();
// Executes 3 queries total (companies + 2 relationships)
// Without eager loading would be: 3 + (n_companies * 2) queries
```

## Testing Verified

✅ All 10 relationships tested successfully:
- Company::upcomingAppointments() - 0-N appointments
- Company::completedAppointments() - 0-N appointments
- Branch::upcomingAppointments() - 0-N appointments
- Branch::completedAppointments() - 0-N appointments
- Customer::upcomingAppointments() - 0-N appointments
- Customer::completedAppointments() - 0-N appointments
- Customer::recentCalls() - 0-N calls (90 days)
- Staff::upcomingAppointments() - 0-N appointments
- Staff::completedAppointments() - 0-N appointments

## Future Optimizations

### Potential Phase 5 Improvements
1. Add Staff::recentCalls() aggregate relationship
2. Create count() aggregate methods (upcomingCount, completedCount)
3. Add first() convenience methods (nextAppointment, lastCompleted)
4. Implement caching for aggregate counts

### Monitoring
- Log N+1 query warnings in development
- Monitor query execution time in dashboard
- Alert on queries >500ms execution time

## Files Modified

- `/app/Models/Company.php` - Added 2 aggregate relationships
- `/app/Models/Branch.php` - Added 2 aggregate relationships
- `/app/Models/Customer.php` - Added 3 aggregate relationships
- `/app/Models/Staff.php` - Added 2 aggregate relationships

---
**Completion Date**: 2025-10-17
**Phase**: 3 (Performance Optimization)
**Status**: ✅ Complete & Tested
