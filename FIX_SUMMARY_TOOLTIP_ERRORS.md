# Fix Summary: SystemMonitoringDashboard Tooltip Error

## Problem
**Error**: `Filament\Actions\StaticAction::tooltip(): Argument #1 ($tooltip) must be of type Closure|string|null, array given`

### Root Cause:
The `HasTooltips` trait's `tooltip()` method returns an array:
```php
protected static function tooltip(string $text): array
{
    return [
        "tooltip" => $text,
    ];
}
```

But Filament's Action `tooltip()` method expects a string, closure, or null.

## Solution
Removed the `static::tooltip()` wrapper and passed strings directly:

### Before:
```php
->tooltip(static::tooltip('refresh_data'))
```

### After:
```php
->tooltip('Daten aktualisieren')
```

## Changes Made:
1. Removed `static::applyFormActionTooltips()` wrapper
2. Changed all tooltip calls to pass strings directly
3. Added German translations for the tooltips

## Result:
✅ SystemMonitoringDashboard page now loads successfully  
✅ No more type errors  
✅ Tooltips work correctly  

## Technical Note:
The `HasTooltips` trait appears to be designed for a different tooltip system that expects arrays. Filament's native tooltip system expects strings directly.

---
*Fixed on: 2025-08-05*