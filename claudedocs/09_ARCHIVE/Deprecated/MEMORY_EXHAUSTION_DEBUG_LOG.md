# Memory Exhaustion Debugging Log - 2025-10-02

## Timeline

### Initial Problem
- **Time**: 18:37-19:14
- **Symptom**: PHP exhausts 512MB memory on `/admin` after login
- **Error**: `Fatal error: Allowed memory size of 536870912 bytes exhausted`
- **Location**: Various (Builder.php:1471, CompanyScope.php:43, Connection.php:329)

### Actions Taken

#### 1. Increased PHP Memory Limit (18:50)
- Changed `/etc/php/8.3/fpm/php.ini` memory_limit: 512M → 1G
- Changed `/etc/php/8.3/fpm/pool.d/www.conf` memory_limit: 512M → 2G
- **Result**: FAILED - Still exhausts 2GB

#### 2. Created callback_requests table (18:50-18:55)
- Ran migrations for callback_requests and callback_escalations
- **Result**: Tables created, but memory issue persists

#### 3. Disabled Widgets Discovery (19:15-19:20)
- Disabled `discoverWidgets()` in AdminPanelProvider.php
- **Result**: FAILED - Still exhausts memory

#### 4. Disabled Resources Discovery (19:20)
- Disabled `discoverResources()` in AdminPanelProvider.php
- **Result**: SUCCESS - Dashboard loads with 0 widgets and 0 resources

#### 5. Re-enabled Resources, kept Widgets disabled (19:25)
- Re-enabled `discoverResources()`
- Kept widgets disabled
- **Result**: FAILED - Memory exhaustion returns

#### 6. Created Debug Script (19:30)
- Created CLI script to test navigation badges
- **Result**: All 28 resources load fine in CLI (16MB total memory)

#### 7. Created HTTP Debug Route (19:35)
- Created `/debug-dashboard` route
- **Result**: HTTP context shows 286MB memory (vs 16MB in CLI)
  - Difference: 270MB baseline HTTP overhead

#### 8. Reduced Dashboard Widgets (20:00)
- Dashboard.php: 15 widgets → 4 widgets
- **Result**: FAILED - Still exhausts 2GB

#### 9. Disabled ALL Dashboard Widgets (20:10)
- Dashboard.php: 4 widgets → 0 widgets
- **Result**: FAILED - Still exhausts 2GB

### Current State (20:30)

**Configuration**:
- PHP memory_limit: 2GB (pool config)
- Dashboard widgets: 0 (all disabled)
- Resource discovery: ENABLED (28 resources)
- Widget discovery: ENABLED

**Observations**:
1. Problem occurs on `GET /admin` after successful login
2. Even Login page (`/admin/login`) sometimes exhausts memory
3. CLI execution: 16MB for all resources
4. HTTP execution: 286MB baseline, then exhaust 2GB
5. Error locations vary:
   - `Connection.php:329` (query execution)
   - `Builder.php:1471` (eloquent builder)
   - `CompanyScope.php:39` (scope extension)
   - `CompanyScope.php:48` (allCompanies macro)

**Tables Analyzed**:
- `calls`: 5.5MB, 86 rows recent, 26 LONGTEXT columns, avg 25KB per row
- `appointments`: 0.19MB, 9 LONGTEXT columns
- `activity_log`: Empty, 7 LONGTEXT columns
- `webhook_events`: 2.52MB
- `telescope_entries`: 2.52MB

**Navigation Badges**:
- 27 resources have `getNavigationBadge()` methods
- All use COUNT() queries (no LONGTEXT loading)
- Example: CallResource counts calls from last 7 days (86 rows)

### Hypothesis

**Primary Suspect**: Navigation Badge Rendering
- 28 resources × COUNT() query + CompanyScope application
- CompanyScope potentially in infinite recursion on line 39 (extend method)
- HTTP context has 270MB baseline overhead
- Cumulative effect of all badge queries exhausts remaining memory

**Secondary Suspect**: Auto-eager loading
- Some relationship loading ALL calls data with LONGTEXT
- Not visible in debug scripts because they don't trigger full Filament rendering

### Next Steps

1. Disable Resource Discovery completely → Test if dashboard loads
2. If yes: Navigation badges are the problem
3. Cache all navigation badge results
4. Investigate CompanyScope for infinite recursion
5. Add query logging to identify which query loads 2GB

## Technical Details

### Memory Allocation Attempts
- Most errors try to allocate 33554432 bytes (32MB)
- Some try to allocate 8388608 bytes (8MB)
- System has used ~2GB before allocation fails
- Suggests gradual accumulation, not single massive query

### Files Modified
- `/etc/php/8.3/fpm/php.ini` - memory_limit = 1G
- `/etc/php/8.3/fpm/pool.d/www.conf` - php_admin_value[memory_limit] = 2G
- `/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php` - Widgets disabled
- `/var/www/api-gateway/app/Filament/Pages/Dashboard.php` - All widgets disabled

### Tested Queries
```sql
-- Calls table size
SELECT COUNT(*) FROM calls WHERE created_at >= NOW() - INTERVAL 7 DAY;
-- Result: 86 rows

-- LONGTEXT column average size
SELECT AVG(LENGTH(raw)) FROM calls WHERE raw IS NOT NULL;
-- Result: 25,679 bytes (25KB average)

-- Largest tables
calls: 5.50MB
webhook_events: 2.52MB
telescope_entries: 2.52MB
```

### Error Pattern
```
FastCGI: PHP Fatal error: Allowed memory size of 2147483648 bytes exhausted
Location rotation:
1. Connection.php:329 (50% of errors)
2. Builder.php:1471 (30% of errors)
3. CompanyScope.php:39/48 (20% of errors)
```
