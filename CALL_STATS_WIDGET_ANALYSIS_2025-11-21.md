# CallStatsOverview Widget Analysis - 2025-11-21

## Executive Summary

Analysis of `/app/Filament/Resources/CallResource/Widgets/CallStatsOverview.php` reveals **7 critical issues** affecting statistics accuracy and display. The widget shows incorrect data due to NULL metadata handling, sentiment calculation errors, and cache key timezone issues.

---

## Database Reality Check (2025-11-21)

```
Total Calls (DB): 1,410
Today's Calls: 5
  - Status: 4 completed, 1 ongoing
  - Has Appointment: 0
  - Sentiment Data: ALL NULL (metadata column is NULL)
  - Duration: 50s, 80s, 92s, 54s (one has no duration)

Week Calls: 105 (Start: 2025-11-17, End: 2025-11-23)
  - Completed: 72
```

---

## Issue 1: Sentiment Calculation with NULL Metadata [CRITICAL]

**File**: `CallStatsOverview.php`
**Lines**: 79-80

### Problem
```php
SUM(CASE WHEN JSON_EXTRACT(metadata, "$.sentiment") = "positive" THEN 1 ELSE 0 END) as positive_count,
SUM(CASE WHEN JSON_EXTRACT(metadata, "$.sentiment") = "negative" THEN 1 ELSE 0 END) as negative_count
```

### Issue
- **Reality**: All today's calls have `metadata = NULL`
- **Result**: `JSON_EXTRACT(NULL, "$.sentiment")` returns `NULL`
- **Comparison**: `NULL = "positive"` evaluates to `NULL` (not FALSE)
- **Display**: Shows "😊 0 positiv / 😟 0 negativ" (technically correct but misleading)

### Impact
- Widget shows 0/0 sentiment when it should indicate "No sentiment data available"
- Users cannot distinguish between "analyzed as neutral" vs "no analysis yet"

### Fix
```php
SUM(CASE WHEN JSON_EXTRACT(metadata, "$.sentiment") = "positive" THEN 1
         WHEN metadata IS NULL THEN 0
         ELSE 0 END) as positive_count,
SUM(CASE WHEN JSON_EXTRACT(metadata, "$.sentiment") = "negative" THEN 1
         WHEN metadata IS NULL THEN 0
         ELSE 0 END) as negative_count,
SUM(CASE WHEN metadata IS NOT NULL THEN 1 ELSE 0 END) as analyzed_count
```

**Priority**: HIGH (Misleading UI but functionally correct for NULL data)

---

## Issue 2: Average Duration Calculation Excludes Zero Duration Calls [MEDIUM]

**File**: `CallStatsOverview.php`
**Line**: 78

### Problem
```php
AVG(CASE WHEN duration_sec > 0 THEN duration_sec ELSE NULL END) as avg_duration
```

### Issue
- **Excludes**: Ongoing calls (duration_sec = 0 or NULL)
- **Today's Data**: 1 ongoing call excluded from average
- **Result**: Average calculated from 4 calls instead of 5

### Impact
- Average duration: `(50 + 80 + 92 + 54) / 4 = 69s` (correct for completed)
- But widget says "5 Anrufe heute" while averaging only 4
- Inconsistent with displayed call count

### Rationale
- Current behavior is **intentionally correct** (averaging only completed calls)
- However, description is misleading

### Fix Option 1: Update Description
```php
Stat::make('⌀ Dauer', gmdate("i:s", $todayAvgDuration))
    ->description($todaySuccessful . ' abgeschlossen | Diese Woche: ' . $weekCount)
    //                           ^^^^ clarify "completed calls only"
```

### Fix Option 2: Show Both
```php
->description(
    'Ø aus ' . ($todayStats->duration_count ?? 0) . ' Anrufen | ' .
    'Woche: ' . $weekCount
)
```

**Priority**: MEDIUM (Correct calculation but unclear communication)

---

## Issue 3: Cache Key Timezone Sensitivity [HIGH]

**File**: `CallStatsOverview.php`
**Line**: 36-37

### Problem
```php
$cacheMinute = floor(now()->minute / 5) * 5;
return Cache::remember('call-stats-overview-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 60, function () {
```

### Issue
- **Current Time**: 2025-11-21 08:45:29
- **Cache Key**: `call-stats-overview-2025-11-21-08-45`
- **Problem**: Cache keys include timezone-dependent hour
- **Impact**: Multi-timezone deployments or server time changes break cache

### Example Scenario
```
User in Berlin (CET): now() = 2025-11-21 09:45
User in London (GMT): now() = 2025-11-21 08:45
→ Different cache keys for same 5-minute window
```

### Fix
```php
// Use UTC for cache keys, apply timezone for queries
$cacheMinute = floor(now('UTC')->minute / 5) * 5;
$cacheKey = 'call-stats-overview-' . now('UTC')->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

return Cache::remember($cacheKey, 60, function () {
    return $this->calculateStats();
});
```

**Priority**: HIGH (Multi-tenant SaaS with potential international users)

---

## Issue 4: Week Statistics Date Range Mismatch [MEDIUM]

**File**: `CallStatsOverview.php`
**Lines**: 93 (week stats) vs 195 (week chart)

### Problem
```php
// Week stats uses current week (Mon-Sun)
Line 93: Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])

// Week chart uses last 7 days (today - 6 days)
Line 195: Call::whereBetween('created_at', [today()->subDays(6)->startOfDay(), today()->endOfDay()])
```

### Issue
- **Week Stats**: 2025-11-17 00:00:00 to 2025-11-23 23:59:59 (7 days, Mon-Sun)
- **Week Chart**: 2025-11-15 00:00:00 to 2025-11-21 23:59:59 (7 days, rolling)
- **Mismatch**: Different date ranges for "week" concept

### Today's Impact (2025-11-21 Thursday)
```
Week Stats: Mon 17 → Sun 23 (includes future days!)
Week Chart: Thu 15 → Thu 21 (rolling 7 days back)
```

### Fix Option 1: Align Both to Rolling 7 Days
```php
// Line 93 - Match chart behavior
$weekStats = $this->applyRoleFilter(
    Call::whereBetween('created_at', [today()->subDays(6)->startOfDay(), today()->endOfDay()])
)
```

### Fix Option 2: Align Both to Calendar Week
```php
// Line 195 - Match stats behavior
$data = $this->applyRoleFilter(
    Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
)
```

**Priority**: MEDIUM (Causes confusion between "this week" vs "last 7 days")

---

## Issue 5: Week Duration Chart Mismatch [MEDIUM]

**File**: `CallStatsOverview.php`
**Lines**: 214-234

### Problem
```php
// Uses calendar week (Mon-Sun)
Line 216: Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])

// But loops through 7 days starting from Monday
Line 228-230: for ($i = 0; $i < 7; $i++) {
    $date = now()->startOfWeek()->addDays($i)->format('Y-m-d');
```

### Issue
- **Query**: Gets data for current calendar week (Mon 17 - Sun 23)
- **Loop**: Iterates Mon 17, Tue 18, Wed 19, Thu 20, Fri 21, Sat 22, Sun 23
- **Today**: Thursday Nov 21
- **Result**: Loop includes 2 future days (Sat 22, Sun 23) with 0 data

### Impact
- Chart shows 7 bars: 5 with data, 2 with zeros
- Not consistent with "this week so far" vs "full week forecast"

### Fix
```php
// Option 1: Only show days up to today
for ($i = 0; $i < 7; $i++) {
    $date = now()->startOfWeek()->addDays($i)->format('Y-m-d');
    if ($date > today()->format('Y-m-d')) break; // Stop at future dates
    $durations[] = $data[$date] ?? 0;
}

// Option 2: Match rolling 7-day pattern
$data = $this->applyRoleFilter(
    Call::whereBetween('created_at', [today()->subDays(6)->startOfDay(), today()->endOfDay()])
)
```

**Priority**: MEDIUM (Inconsistent with user expectations)

---

## Issue 6: Month Cost Chart Year Boundary Bug [LOW]

**File**: `CallStatsOverview.php`
**Lines**: 260-268

### Problem
```php
// Handle year boundary case
if ($endWeek < $currentWeek) {
    $endWeek += 52;
}

for ($week = $currentWeek; $week <= $endWeek; $week++) {
    $actualWeek = $week > 52 ? $week - 52 : $week;
    $costs[] = $data[$actualWeek] ?? 0;
}
```

### Issue
- **Leap Years**: Year 2024 has 53 weeks (ISO 8601)
- **Logic**: Assumes 52 weeks per year
- **Bug**: Week 53 wraps to week 1 incorrectly

### Example Scenario (December 2025)
```
Current Week: 53 (Dec 29, 2025 - Jan 4, 2026)
Start of Month: Week 49 (Dec 1, 2025)
End of Month: Week 1 (Dec 31, 2025 interpreted as week 1 of 2026)
→ endWeek < currentWeek → adds 52 → week 53
→ actualWeek = 53 > 52 → 53 - 52 = 1 (WRONG!)
```

### Fix
```php
use Carbon\Carbon;

$startOfMonth = now()->startOfMonth();
$endOfMonth = min(now(), now()->endOfMonth());

// Use ISO week with year for proper boundaries
$currentWeek = $startOfMonth->isoWeek();
$currentYear = $startOfMonth->isoWeekYear();
$endWeek = $endOfMonth->isoWeek();
$endYear = $endOfMonth->isoWeekYear();

// Query with proper ISO week year
$data = $this->applyRoleFilter(Call::whereBetween('created_at', [$startOfMonth, $endOfMonth]))
    ->selectRaw('
        YEARWEEK(created_at, 3) as iso_week,
        SUM(COALESCE(calculated_cost, 0)) / 100.0 as total_cost
    ')
    ->groupBy('iso_week')
    ->orderBy('iso_week')
    ->pluck('total_cost', 'iso_week')
    ->toArray();

// Build costs array with proper year handling
if ($endYear > $currentYear) {
    // Month spans year boundary
    $weeksInYear = Carbon::create($currentYear, 12, 28)->isoWeeksInYear();
    for ($week = $currentWeek; $week <= $weeksInYear; $week++) {
        $costs[] = $data[$currentYear . sprintf('%02d', $week)] ?? 0;
    }
    for ($week = 1; $week <= $endWeek; $week++) {
        $costs[] = $data[$endYear . sprintf('%02d', $week)] ?? 0;
    }
} else {
    // Normal case
    for ($week = $currentWeek; $week <= $endWeek; $week++) {
        $costs[] = $data[$currentYear . sprintf('%02d', $week)] ?? 0;
    }
}
```

**Priority**: LOW (Edge case, rare occurrence, minor visual impact)

---

## Issue 7: Success Rate Calculation with Zero Calls [LOW]

**File**: `CallStatsOverview.php`
**Lines**: 149-156

### Problem
```php
Stat::make('Erfolgsquote Heute', $todayCount > 0 ? round(($todaySuccessful / $todayCount) * 100, 1) . '%' : '0%')
    ->description('😊 ' . $positiveSentiment . ' positiv / 😟 ' . $negativeSentiment . ' negativ')
    ->chart($todayCount > 0 ? [
        $todaySuccessful,
        $todayCount - $todaySuccessful,
    ] : [0, 0])
```

### Issue
- **When**: `$todayCount = 0` (no calls today)
- **Chart**: `[0, 0]` creates empty/invisible chart
- **Description**: Shows "😊 0 positiv / 😟 0 negativ" (confusing)

### Impact
- Chart appears broken/missing on days with no calls
- Sentiment 0/0 is ambiguous (no data vs no positive/negative)

### Fix
```php
Stat::make('Erfolgsquote Heute', $todayCount > 0 ? round(($todaySuccessful / $todayCount) * 100, 1) . '%' : '-')
    ->description(
        $todayCount > 0
            ? '😊 ' . $positiveSentiment . ' positiv / 😟 ' . $negativeSentiment . ' negativ'
            : 'Keine Anrufe heute'
    )
    ->chart($todayCount > 0 ? [$todaySuccessful, $todayCount - $todaySuccessful] : [1])
```

**Priority**: LOW (Edge case, minor UX issue)

---

## Issue 8: Role-Based Filter Not Applied to Tabs [MEDIUM]

**File**: `ListCalls.php`
**Lines**: 51-59

### Problem
```php
$counts = \Illuminate\Support\Facades\Cache::remember($cacheKey . '-' . $cacheSegment, 300, function () {
    return [
        'all' => Call::count(),
        'completed' => Call::where('status', 'completed')->count(),
        'failed' => Call::where('status', 'failed')->count(),
        'today' => Call::whereDate('created_at', today())->count(),
        'with_appointments' => Call::where('has_appointment', true)->count(),
    ];
});
```

### Issue
- **Widget**: Applies `applyRoleFilter()` to respect company/reseller boundaries
- **Tabs**: Uses raw `Call::count()` without tenant filtering
- **Result**: Tab counts show global data, widget shows filtered data

### Example
```
Super Admin sees: Tab "Alle Anrufe (1410)" but widget "Anrufe Heute (5)"
Company Staff sees: Tab "Alle Anrufe (1410)" but should only see their company's calls
```

### Fix
```php
// Option 1: Create reusable scope in Call model
public function scopeForAuthUser($query)
{
    $user = auth()->user();
    if (!$user) return $query;

    if ($user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
        return $query->where('company_id', $user->company_id);
    }

    if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']) && $user->company) {
        return $query->whereHas('company', function ($q) use ($user) {
            $q->where('parent_company_id', $user->company_id);
        });
    }

    return $query; // Super-admin sees all
}

// Option 2: Use same logic in ListCalls
$counts = \Illuminate\Support\Facades\Cache::remember($cacheKey . '-' . $cacheSegment, 300, function () {
    $baseQuery = Call::forAuthUser(); // Use scope
    return [
        'all' => $baseQuery->count(),
        'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        'failed' => (clone $baseQuery)->where('status', 'failed')->count(),
        'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
        'with_appointments' => (clone $baseQuery)->where('has_appointment', true)->count(),
    ];
});
```

**Priority**: MEDIUM (Security/privacy concern for multi-tenant)

---

## Summary Table

| # | Issue | Location | Priority | Impact |
|---|-------|----------|----------|--------|
| 1 | NULL metadata sentiment | Line 79-80 | HIGH | Misleading 0/0 sentiment display |
| 2 | Duration excludes ongoing | Line 78, 158 | MEDIUM | Inconsistent count vs average |
| 3 | Cache key timezone | Line 36-37 | HIGH | Multi-timezone cache collision |
| 4 | Week stats vs chart mismatch | Line 93 vs 195 | MEDIUM | Conflicting "week" definition |
| 5 | Week duration future days | Line 216-230 | MEDIUM | Shows zeros for future dates |
| 6 | Year boundary week calc | Line 260-268 | LOW | Rare edge case (Dec/Jan) |
| 7 | Zero calls empty chart | Line 149-156 | LOW | UX issue on quiet days |
| 8 | Tab counts not filtered | ListCalls:51-59 | MEDIUM | Privacy leak in multi-tenant |

---

## Recommended Fix Priority

### Phase 1: Critical (Week 1)
1. **Issue 3**: Cache key timezone → UTC-based keys
2. **Issue 1**: Sentiment with NULL → Add NULL handling + "analyzed_count"
3. **Issue 8**: Tab counts filtering → Apply role filters

### Phase 2: Important (Week 2)
4. **Issue 4**: Align week definitions → Choose rolling 7-day OR calendar week
5. **Issue 5**: Week duration chart → Match chosen week definition
6. **Issue 2**: Duration description → Clarify "completed calls only"

### Phase 3: Polish (Week 3)
7. **Issue 7**: Zero calls UX → Better empty state
8. **Issue 6**: Year boundary → ISO week with year

---

## Testing Checklist

- [ ] Verify sentiment shows correctly when metadata is NULL
- [ ] Check cache invalidation at 5-minute boundaries
- [ ] Test with company_staff role (should see filtered data)
- [ ] Test with reseller role (should see customer calls)
- [ ] Test with super-admin (should see all calls)
- [ ] Verify week chart matches week stats date range
- [ ] Test on first day of week (Monday)
- [ ] Test on last day of week (Sunday)
- [ ] Test on first day of month with week boundary
- [ ] Test year boundary (Dec 31 / Jan 1)
- [ ] Test with zero calls today
- [ ] Test with all calls having NULL metadata

---

## Related Files

- Widget: `/app/Filament/Resources/CallResource/Widgets/CallStatsOverview.php`
- List Page: `/app/Filament/Resources/CallResource/Pages/ListCalls.php`
- Model: `/app/Models/Call.php`
- Cache: Redis (keys: `call-stats-overview-*`)

---

**Analyst**: Frontend Architect AI
**Date**: 2025-11-21
**Methodology**: Database verification, code analysis, cache inspection
