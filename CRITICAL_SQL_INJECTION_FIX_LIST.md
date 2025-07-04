# Critical SQL Injection Fix List - IMMEDIATE ACTION REQUIRED

## Files Requiring Immediate Fixes (Priority Order)

### 1. CRITICAL - Fix Within 2 Hours
**File**: `/var/www/api-gateway/app/Services/QueryOptimizer.php`
- **Line 46**: `$query->fromRaw("``{$table}`` USE INDEX (" . $this->sanitizeIndexList($indexList) . ")");`
- **Line 327**: `$query->fromRaw("``{$table}`` FORCE INDEX (" . $this->sanitizeIndexName($index) . ")");`
- **Impact**: Affects ALL database queries using this optimizer
- **Fix**: Implement strict table name whitelist

### 2. HIGH - Fix Within 24 Hours
**File**: `/var/www/api-gateway/app/Services/FeatureFlagService.php`
- **Line 264**: `DB::raw("JSON_SET(COALESCE(metadata, '{}'), '$.emergency_reason', " . DB::connection()->getPdo()->quote($reason) . ")")`
- **Impact**: Could compromise feature flag system
- **Fix**: Use PHP JSON manipulation instead of SQL

### 3. HIGH - Fix Within 48 Hours
**File**: `/var/www/api-gateway/app/Services/EventTypeMatchingService.php`
- **Lines 182-183**: `whereRaw('LOWER(name) LIKE ?', ['%' . $keyword . '%'])`
- **Lines 241-243**: `whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?'`
- **Impact**: Processes user input for service matching
- **Fix**: Use SafeQueryHelper consistently

**File**: `/var/www/api-gateway/app/Services/RealTime/IntelligentCallRouter.php`
- **Lines 158-159**: `whereRaw("JSON_EXTRACT(working_hours, '$.{$dayOfWeek}.start') <= ?")`
- **Impact**: Real-time call routing system
- **Fix**: Validate $dayOfWeek against whitelist

**File**: `/var/www/api-gateway/app/Services/RealTime/ConcurrentCallManager.php`
- **Lines 180-181**: Similar whereRaw() with $dayOfWeek
- **Impact**: Concurrent call management
- **Fix**: Same as above

### 4. MEDIUM - Fix Within 1 Week
**File**: `/var/www/api-gateway/app/Services/Customer/EnhancedCustomerService.php`
- **Lines 235, 255, 275, 477, 486**: Multiple `selectRaw()` calls
- **Impact**: Customer data queries
- **Fix**: Replace with query builder methods

**File**: `/var/www/api-gateway/app/Repositories/OptimizedAppointmentRepository.php`
- **Lines 106-112**: Multiple `DB::raw()` in aggregations
- **Impact**: Appointment statistics
- **Fix**: Use query builder aggregation methods

## Quick Fix Commands

```bash
# Find all potentially vulnerable files
grep -r "whereRaw\|selectRaw\|orderByRaw\|havingRaw\|fromRaw\|DB::raw" app/ --include="*.php" | grep -v "vendor" | grep -v "storage" > sql_injection_audit.txt

# Create backup before fixes
tar -czf sql_injection_backup_$(date +%Y%m%d_%H%M%S).tar.gz app/Services/QueryOptimizer.php app/Services/FeatureFlagService.php app/Services/EventTypeMatchingService.php

# Monitor for exploitation attempts
tail -f storage/logs/laravel.log | grep -i "sql\|injection\|union\|select.*from"
```

## Testing After Fixes

1. Run unit tests for affected services
2. Test query optimization functionality
3. Verify feature flag operations
4. Check event type matching
5. Test real-time call routing
6. Monitor query logs for errors

## Prevention Checklist

- [ ] Implement code review requirement for any `*Raw()` method usage
- [ ] Add PHPStan/Psalm rules to detect raw SQL
- [ ] Create developer guidelines for database queries
- [ ] Set up automated security scanning in CI/CD
- [ ] Regular security audits (monthly)
- [ ] Database query logging and monitoring
- [ ] Implement query whitelisting framework

## Emergency Contacts

If exploitation is detected:
1. Disable affected services immediately
2. Enable emergency maintenance mode
3. Review database logs for suspicious queries
4. Check for data exfiltration
5. Notify security team
6. Implement fixes and thoroughly test
7. Deploy with enhanced monitoring

Remember: The QueryOptimizer.php vulnerability is CRITICAL as it affects the entire application's database layer!