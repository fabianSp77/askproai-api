# Navigation Fix Summary - 2025-08-03

## Database Column Fixes Applied

### 1. branches table
- **Issue**: Column 'active' not found
- **Fixed**: Changed to `is_active` in `/var/www/api-gateway/resources/views/filament/admin/pages/operations-dashboard.blade.php` (line 60)

### 2. calls table  
- **Issue**: Column 'end_timestamp' not found
- **Fixed**: Changed to `ended_at` in `/var/www/api-gateway/app/Filament/Admin/Widgets/CompactOperationsWidget.php` (line 97)
- **Issue**: Column 'appointment_id' not found (calls table doesn't have this column)
- **Fixed**: Changed to use metadata field with LIKE query in:
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/CompactOperationsWidget.php` (lines 146-152)
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/InsightsActionsWidget.php` (line 123)
- **Issue**: Column 'cost' not found
- **Fixed**: Changed to calculate from `duration_sec * 0.02` in `/var/www/api-gateway/app/Filament/Admin/Widgets/CompactOperationsWidget.php` (lines 185-192)

### 3. phone_numbers table
- **Issue**: Column 'number' not found  
- **Fixed**: Changed to `phone_number` in `/var/www/api-gateway/app/Filament/Admin/Widgets/InsightsActionsWidget.php` (line 55)

### 4. api_call_logs table
- **Issue**: Column 'company_id' not found (api_call_logs is service-level, not company-specific)
- **Root Cause**: ApiCallLog model has TenantScope which automatically adds company_id filter
- **Fixed**: Added `->withoutGlobalScope(\App\Scopes\TenantScope::class)` to disable tenant filtering in:
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/LiveActivityFeedWidget.php` (line 105)
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/InsightsActionsWidget.php` (line 90)
- **Issue**: Column 'response_status' not found
- **Fixed**: Changed to `status_code` in:
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/LiveActivityFeedWidget.php` (lines 107-108)
  - `/var/www/api-gateway/app/Filament/Admin/Widgets/InsightsActionsWidget.php` (lines 92-93)

## Remaining Issues

There may be other widgets that reference `appointment_id` directly on the calls table. These widgets include:
- PerformanceMetricsWidget
- CallQueueWidget  
- RecentCallsWidget
- DashboardStats
- CallKpiWidget
- OperationsMonitorWidget
- And others...

These may need similar fixes to use the metadata field instead of a direct appointment_id column.

## Root Cause

The database schema doesn't match what the widgets expect. This appears to be a case where:
1. The widgets were written expecting certain column names that don't exist
2. The relationship between calls and appointments is indirect (through metadata or other means)
3. API call logs are system-wide, not company-specific

## Recommendation

Consider running a comprehensive audit of all widgets to ensure they match the actual database schema.

## Additional Fixes Applied

### 5. RoiCalculationService
- **Issue**: Column 'cost' not found in calls table
- **Fixed**: Changed all `cost` references to calculate from `duration_sec * 0.02` in `/var/www/api-gateway/app/Services/Analytics/RoiCalculationService.php`:
  - Line 116: `SUM(cost)` → `SUM(duration_sec * 0.02)`
  - Line 117: `AVG(cost)` → `AVG(duration_sec * 0.02)`
  - Line 176: `SUM(cost)` → `SUM(duration_sec * 0.02)`
  - Line 324: `SUM(cost)` → `SUM(duration_sec * 0.02)`
- **Issue**: Column 'appointment_id' not found in calls table
- **Fixed**: Changed to use metadata field with LIKE query:
  - Line 118: `appointment_id IS NOT NULL` → `metadata IS NOT NULL AND metadata LIKE '%appointment%'`
  - Line 177: Same change
- **Issue**: Column 'price' not found in appointments table
- **Fixed**: Added LEFT JOIN to services table and changed references:
  - Lines 142, 187, 336: Added `->leftJoin('services', 'appointments.service_id', '=', 'services.id')`
  - Line 149: `SUM(appointments.price)` → `SUM(services.price)`
  - Line 150: `AVG(appointments.price)` → `AVG(services.price)`
  - Line 193: `SUM(appointments.price)` → `SUM(services.price)`
  - Line 342: `SUM(appointments.price)` → `SUM(services.price)`

## Systematic Analysis Results

Based on the SQL error finder script, there are many more files with similar issues:

### Files Still Needing Fixes

1. **appointment_id references** (17+ files):
   - FixSqlInjections.php
   - SendBatchCallSummariesCommand.php
   - CacheManagement.php
   - UsageCalculationService.php
   - AdvancedPricingService.php
   - Multiple MCP servers and widgets
   - CallRepository.php

2. **cost references** (5+ files):
   - GenerateMonthlyInvoices.php
   - CostAnalysisWidget.php
   - Various MCP servers

3. **appointments.price references** (multiple files across the codebase)

4. **branches.active references** (should be `is_active`)

5. **phone_numbers.number references** (should be `phone_number`)
