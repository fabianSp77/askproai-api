# Root Cause Analysis: Stuck Calls in Live Filter

**Date**: 2025-10-21
**Issue**: Call 562 (und 14 weitere) wurden fälschlicherweise als "live/laufend" angezeigt
**Status**: ✅ RESOLVED
**Severity**: Medium - User Experience Issue

---

## Problem Description

Calls wurden dauerhaft im "Live Calls" Filter angezeigt, obwohl sie längst beendet waren. Dies führte zu:
- Verwirrung bei der Übersicht laufender Anrufe
- Falsche Metriken im Dashboard
- Verminderte User Experience

### Betroffene Calls
- **Call 562**: 77 Stunden als "in_progress" steckengeblieben
- **14 weitere Calls**: 500+ Stunden alte Test-Calls
- **Alle**: Status `in_progress` oder `ongoing`, aber längst beendet

---

## Root Cause

### 1. Webhook Lifecycle Problem
```
Normaler Flow:
1. call_started   → status = 'ongoing'  ✓
2. call_ended     → status = 'completed' ✓

Problem:
1. call_started   → status = 'ongoing'  ✓
2. call_ended     → ❌ WEBHOOK FAILED/LOST
3. Status bleibt  → 'ongoing' FOREVER  ❌
```

### 2. Live Filter Implementation
**File**: `app/Filament/Resources/CallResource.php:720`
```php
Tables\Filters\Filter::make('live_calls')
    ->label('Laufende Anrufe (LIVE)')
    ->query(fn (Builder $query): Builder =>
        $query->whereIn('status', ['ongoing', 'in_progress', 'active', 'ringing'])
    )
```

Zeigt ALLE Calls mit diesen Status-Werten, unabhängig vom Alter!

### 3. Scope Bug in Call Model
**File**: `app/Models/Call.php:289`

**BEFORE (BUGGY)**:
```php
public function scopeStuck($query, int $hours = 2)
{
    return $query->whereIn('status', ['ongoing', 'in-progress', 'active'])
        ->where('created_at', '<', now()->subHours($hours));
}
```

**Problem**: `'in-progress'` (Bindestrich) ≠ `'in_progress'` (Unterstrich in DB)

**AFTER (FIXED)**:
```php
public function scopeStuck($query, int $hours = 2)
{
    return $query->whereIn('status', ['ongoing', 'in_progress', 'in-progress', 'active'])
        ->where('created_at', '<', now()->subHours($hours));
}
```

---

## Solution Implemented

### 1. Fixed Model Scope
**File**: `app/Models/Call.php:289-293`

Added both `'in_progress'` (DB format) and `'in-progress'` (legacy format) to ensure comprehensive matching.

### 2. Enhanced Cleanup Command
**File**: `app/Console/Commands/CleanupStuckCalls.php`

**Improvements**:
- ✅ Uses `created_at` instead of `updated_at` (more reliable)
- ✅ Configurable threshold: `--hours=X` (default: 2)
- ✅ Dry-run mode: `--dry-run` for safe testing
- ✅ Comprehensive logging
- ✅ Duration calculation with sanity checks
- ✅ Interactive confirmation (skipped in cron)

**Usage**:
```bash
# Preview without changes
php artisan calls:cleanup-stuck --dry-run

# Clean stuck calls older than 2 hours
php artisan calls:cleanup-stuck

# Clean stuck calls older than 4 hours
php artisan calls:cleanup-stuck --hours=4
```

### 3. Automated Scheduler
**File**: `app/Console/Kernel.php:56-62`

```php
// Runs every 10 minutes with 4-hour threshold
$schedule->command('calls:cleanup-stuck --hours=4')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/calls-cleanup.log'));
```

**Why 4 hours?**
- Prevents cleaning up legitimate long calls (Beratungsgespräche)
- Still catches stuck calls quickly enough
- Runs every 10 minutes = max 10 min delay

---

## Prevention Strategy

### Multi-Layer Defense

**Layer 1: Webhook Reliability**
- Retell.ai sends `call_ended` webhook
- RetellWebhookController processes it
- Status updated to `'completed'`

**Layer 2: Automated Cleanup (NEW)**
- Every 10 minutes: Check for calls stuck >4 hours
- Automatic completion with proper logging
- Logs: `storage/logs/calls-cleanup.log`

**Layer 3: Manual Recovery**
- Command available for immediate cleanup
- Dry-run mode for safe testing
- Interactive confirmation

---

## Verification

### Before Fix
```sql
SELECT id, status, created_at FROM calls WHERE id = 562;
-- Result: in_progress, 77 hours old
```

### After Fix
```sql
SELECT id, status, call_status, end_timestamp FROM calls WHERE id = 562;
-- Result:
-- status: completed
-- call_status: ended
-- end_timestamp: 2025-10-21 21:22:36
```

### Live Filter Test
```bash
# No more stuck calls in live filter
php artisan calls:cleanup-stuck --hours=4 --dry-run
# Output: ✅ No stuck calls found. System is healthy!
```

---

## Monitoring & Alerts

### Log Files
```bash
# Cleanup activity
tail -f storage/logs/calls-cleanup.log

# All Laravel logs (includes cleanup errors)
tail -f storage/logs/laravel.log
```

### Success Indicators
```
🧹 Cleaned up stuck call
    call_id: 562
    old_status: in_progress
    new_status: completed
    hours_stuck: 77
    source: cleanup_command
```

### Failure Indicators
```
❌ Failed to cleanup stuck call
    call_id: XXX
    error: [Error message]
```

---

## Technical Details

### Database Schema
```sql
-- Calls table relevant fields
status VARCHAR(50)           -- 'ongoing', 'in_progress', 'completed', etc.
call_status VARCHAR(50)      -- 'ongoing', 'ended', NULL
created_at TIMESTAMP         -- Used for stuck detection
start_timestamp TIMESTAMP    -- Optional, from webhook
end_timestamp TIMESTAMP      -- Set on completion
duration_sec INT             -- NULL for stuck calls
```

### Status Values in Production
```
'in_progress' (with underscore) - Active in database
'ongoing'                       - Active from webhook
'completed'                     - Finished successfully
'failed', 'missed', 'busy'      - Error states
```

---

## Related Files

### Modified
- `app/Models/Call.php` - Fixed scopeStuck()
- `app/Console/Commands/CleanupStuckCalls.php` - Enhanced command
- `app/Console/Kernel.php` - Updated scheduler threshold

### Referenced
- `app/Filament/Resources/CallResource.php` - Live filter implementation
- `app/Http/Controllers/RetellWebhookController.php` - Webhook processing

---

## Lessons Learned

1. **String Literal Matching**: Always verify exact DB values vs. code values
2. **Defensive Programming**: Implement cleanup even when webhooks should work
3. **Testing**: Use dry-run modes for destructive operations
4. **Logging**: Comprehensive logging for debugging stuck states
5. **Automation**: Don't rely solely on webhooks for critical state transitions

---

## Future Improvements

### Nice-to-Have
- [ ] Alert when >X calls get stuck (indicates webhook issues)
- [ ] Dashboard widget showing stuck call trends
- [ ] Automatic retry of failed webhooks
- [ ] Health check endpoint for webhook status

### Not Recommended
- ❌ Aggressive timeout (< 4 hours) - would catch legitimate long calls
- ❌ Auto-complete without logging - makes debugging impossible

---

## Sign-off

**Problem**: Calls stuck in "live" status
**Root Cause**: Missing/failed call_ended webhooks + scope typo
**Solution**: Fixed scope + enhanced cleanup + automated scheduler
**Status**: ✅ RESOLVED
**Testing**: 15 stuck calls successfully cleaned up
**Prevention**: Automated cleanup every 10 minutes

**Implemented by**: Claude Code
**Date**: 2025-10-21
**Review Status**: Production-Ready
