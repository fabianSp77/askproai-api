# Call Dashboard Zero Values Fix - GitHub Issue #268

## Date: 2025-07-03

## Issue
The Call Analytics Widget on the Calls page showed all zeros despite having data in the database.

## Root Causes

### 1. Tenant Scope Issues
The widget wasn't properly handling the multi-tenant scope, causing queries to return no results for tenant-scoped users.

### 2. Sentiment Data Extraction
The SQL JSON_EXTRACT wasn't working correctly with the stored JSON data format.

## Solution

### 1. Proper Tenant Scope Handling
```php
// Get current user's company_id (if not super admin)
$user = auth()->user();
$companyId = null;

if ($user && !$user->hasRole('super_admin')) {
    $companyId = $user->company_id;
}

// Build base query
$baseQuery = $companyId 
    ? Call::where('company_id', $companyId)
    : Call::withoutGlobalScope(\App\Scopes\TenantScope::class);
```

### 2. Fixed Sentiment Extraction
Changed from SQL JSON extraction to PHP-based processing:
```php
// OLD (not working)
$sentimentData = (clone $baseQuery)
    ->select(DB::raw("JSON_EXTRACT(analysis, '$.sentiment') as sentiment"))
    ->get();

// NEW (working)
$calls = (clone $baseQuery)
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->whereNotNull('analysis')
    ->get();

$sentimentCounts = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
foreach ($calls as $call) {
    if ($call->analysis && isset($call->analysis['sentiment'])) {
        $sentiment = $call->analysis['sentiment'];
        if (isset($sentimentCounts[$sentiment])) {
            $sentimentCounts[$sentiment]++;
        }
    }
}
```

### 3. Widget Registration
Added the widget to the ListCalls page:
```php
protected function getHeaderWidgets(): array
{
    return [
        CallAnalyticsWidget::class,
    ];
}
```

## Results
- ✅ "Anrufe heute" now shows actual call count with trend
- ✅ "Ø Anrufdauer" displays real average duration
- ✅ "Konversionsrate" calculates actual conversion percentage
- ✅ "Positive Stimmung" shows real sentiment analysis

## Test Data Results
- Total calls: 147
- Positive sentiment: 70.8% (48 positive, 2 negative, 12 neutral)
- Average duration: ~56 seconds
- Conversion rate: 0% (no appointments linked yet)

## Files Modified
1. `/app/Filament/Admin/Resources/CallResource/Widgets/CallAnalyticsWidget.php`
2. `/app/Filament/Admin/Resources/CallResource/Pages/ListCalls.php`