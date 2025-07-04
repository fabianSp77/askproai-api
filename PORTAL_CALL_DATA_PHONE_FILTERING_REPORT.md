# Portal Call Data Phone Number Filtering Report

## Summary
This report identifies all controllers, services, and views in the portal that display or process call data and need phone number filtering to ensure only calls to company phone numbers are shown.

## Already Implemented ✅

### 1. **CallController** (`app/Http/Controllers/Portal/CallController.php`)
- ✅ `index()` method - Lines 39-46: Filters calls by company phone numbers
- ✅ `show()` method - Lines 185-192: Filters customer call history by company phone numbers  
- ✅ `exportCsv()` method - Lines 409-416: Filters export data by company phone numbers
- ✅ `getCallStatistics()` method - Lines 464-471: Filters statistics by company phone numbers

### 2. **AnalyticsController** (`app/Http/Controllers/Portal/AnalyticsController.php`)
- ✅ `getCallStatistics()` - Lines 92-107: Filters by company phone numbers with "All Numbers" option
- ✅ `getHourlyDistribution()` - Lines 171-187: Filters hourly data by company phone numbers
- ✅ `getTopCustomers()` - Lines 200-217: Filters top customers by company phone numbers

## Needs Implementation ❌

### 1. **DashboardController** (`app/Http/Controllers/Portal/DashboardController.php`)
- ❌ `getStatistics()` method - Lines 81-104: No phone number filtering on call counts
- ❌ `getRecentCalls()` method - Lines 134-158: No phone number filtering

### 2. **TeamController** (`app/Http/Controllers/Portal/TeamController.php`)
- ❌ `index()` method - Lines 62-72: Team member call statistics lack phone number filtering

### 3. **DashboardMetricsService** (`app/Services/Dashboard/DashboardMetricsService.php`)
- ❌ `calculateTotalCalls()` - No phone number filtering
- ❌ `calculateCallAvgDuration()` - No phone number filtering
- ❌ `calculateCallSuccessRate()` - No phone number filtering
- ❌ `getCallsTrend()` - No phone number filtering
- ❌ `calculateConversionRate()` - Lines 182-192: No phone number filtering

### 4. **PrepaidBillingService** (`app/Services/PrepaidBillingService.php`)
- ❌ `processUnchargedCalls()` - Lines 132-140: No phone number filtering (though this might be intentional for billing)

### 5. **Filament Admin Widgets**
Multiple widgets need phone number filtering:
- ❌ **InsightsActionsWidget** - Lines 48-56, 102-114, 137-140: Call queries lack phone filtering
- ❌ **CallLiveStatusWidget** - Needs investigation
- ❌ **LiveCallsWidget** - Needs investigation
- ❌ **CallAnalyticsWidget** - Needs investigation
- ❌ **CallKpiWidget** - Needs investigation
- ❌ **Other call-related widgets** - Need systematic review

### 6. **API Controllers** 
- ❌ **DashboardMetricsController** (`app/Http/Controllers/Api/DashboardMetricsController.php`) - Uses DashboardMetricsService which needs filtering
- ❌ **BillingUsageController** - Needs investigation for call-based usage reporting

## Views That Display Call Data

### Portal Views
1. **dashboard.blade.php** - Shows call statistics from DashboardController
2. **team/index.blade.php** - Shows team member call statistics
3. **billing/index.blade.php** - Shows billing statistics that may include call counts
4. **analytics/index.blade.php** - Already properly filtered

## Implementation Strategy

### 1. Create a Trait for Phone Number Filtering
```php
trait FiltersCallsByCompanyPhoneNumbers
{
    protected function getCompanyPhoneNumbers($companyId): array
    {
        return \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
    }
    
    protected function applyPhoneNumberFilter($query, $companyId, $phoneNumberField = 'to_number')
    {
        $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
        return $query->whereIn($phoneNumberField, $phoneNumbers);
    }
}
```

### 2. Update Each Controller/Service
- Add the trait to controllers and services
- Apply phone number filtering to all Call queries
- Ensure consistent filtering across all methods

### 3. Test Impact
- Monitor performance impact of additional WHERE IN clauses
- Consider caching company phone numbers if needed
- Verify that legitimate calls aren't being filtered out

## Security Consideration
This filtering is crucial for multi-tenant security to prevent companies from seeing calls to phone numbers they don't own, which could belong to other companies in the system.

## Performance Consideration
- The `whereIn` clause with phone numbers should use an index on the `to_number` column
- For companies with many phone numbers, consider pagination or limiting the number display

## Recommended Next Steps
1. Implement the trait for consistent filtering
2. Update DashboardController methods first (high visibility)
3. Update TeamController statistics
4. Update DashboardMetricsService for API consistency
5. Systematically review and update all Filament widgets
6. Add tests to ensure filtering works correctly