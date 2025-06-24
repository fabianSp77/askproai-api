# Deleted Tables Fix Summary

## Date: 2025-06-23

### Issue
After database consolidation (92 → 33 tables), production error occurred:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'askproai_db.api_call_logs' doesn't exist
```

### Tables Deleted
59 empty tables were removed, including:
- `api_call_logs` - API call tracking
- `service_usage_logs` - Service usage metrics  
- `notification_log` - Notification history
- `appointment_locks` - Time slot locking
- `invoice_items` - Invoice line items
- `invoice_items_flexible` - Flexible invoice items

### Fixes Applied

#### 1. SystemHealthMonitor Widget
- **File**: `app/Filament/Admin/Widgets/SystemHealthMonitor.php`
- **Change**: Updated to use `mcp_metrics` table instead of `api_call_logs`
- **Lines**: 380-418

#### 2. ServiceUsageTracker
- **File**: `app/Services/Monitoring/ServiceUsageTracker.php`
- **Changes**:
  - `flush()` method: Write to `mcp_metrics` instead of `service_usage_logs`
  - `getUsageStats()` method: Read from `mcp_metrics` with proper metadata parsing
  - Added `getDeprecatedUsageCount()` helper method

#### 3. NotificationService  
- **File**: `app/Services/NotificationService.php`
- **Change**: SMS limit check now uses `webhook_events` table with JSON queries
- **Line**: 312

#### 4. CleanupOldNotifications Command
- **File**: `app/Console/Commands/CleanupOldNotifications.php`
- **Change**: Cleanup uses `webhook_events` table filtered by provider='notification'
- **Line**: 37

#### 5. TimeSlotLockManager
- **File**: `app/Services/Locking/TimeSlotLockManager.php`
- **Major refactor**: Completely replaced database-based locking with Redis cache
- **Key changes**:
  - `acquireLock()`: Uses Cache::add() with TTL
  - `releaseLock()`: Searches cache keys and removes
  - `extendLock()`: Updates cache TTL
  - All AppointmentLock model references removed
  - Added `getLockKey()` helper method

#### 6. DatabaseMCPServer
- **File**: `app/Services/MCP/DatabaseMCPServer.php`
- **Change**: Updated `allowedTables` array to remove deleted tables
- **Line**: 33

### Migration Strategy Used
1. **Data Migration**: `kunden` → `customers` table with foreign key handling
2. **Empty Table Removal**: 59 tables with 0 records safely dropped
3. **Code Updates**: All references to deleted tables updated to use alternatives:
   - Metrics: Use `mcp_metrics` table
   - Notifications: Use `webhook_events` table  
   - Locking: Use Redis cache
   - Invoices: Currently no invoice functionality (tables removed)

### Remaining Considerations
- **InvoiceItem Model**: References non-existent `invoice_items` table but appears unused
- **Invoice functionality**: All invoice tables removed - may need reimplementation if required
- **Performance**: Cache-based locking is faster but requires Redis availability
- **mcp_metrics tenant_id**: Column is bigint(20), ensure company_id values are numeric

### Additional Fixes Applied
- **SystemHealthMonitor**: Added missing Schema facade import
- **mcp_metrics usage**: Updated to use correct column names (service, operation, success, duration_ms)
- **ServiceUsageTracker**: Fixed tenant_id to handle numeric values only

### Verification
Run `php test-sql-injection-fixes.php` and `php test-table-fixes.php` to verify:
- ✅ Database reduced to 33 tables
- ✅ All critical tables preserved
- ✅ SQL injection fixes working
- ✅ No references to deleted tables causing errors
- ✅ Dashboard and widgets now functional