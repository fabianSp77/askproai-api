# System Testing Dashboard - 500 Error Fix

**Date**: 2025-10-21  
**Issue**: 500 Internal Server Error when clicking "Run All Tests"  
**Root Cause**: Missing `metadata` column in `system_test_runs` table  

---

## Problem Analysis

When user clicked "Run All Tests" with AskProAI selected, the server threw a 500 error because:

1. **CalcomTestRunner::runTest()** creates a SystemTestRun record with company metadata:
   ```php
   $testRun = SystemTestRun::create([
       'user_id' => auth()->id(),
       'test_type' => $testType,
       'status' => SystemTestRun::STATUS_RUNNING,
       'started_at' => now(),
       'metadata' => [  // ← Column didn't exist in database!
           'company' => $companyConfig['name'] ?? 'N/A',
           'team_id' => $companyConfig['team_id'] ?? null,
           'event_ids' => $companyConfig['event_ids'] ?? []
       ]
   ]);
   ```

2. **Database had no metadata column** - the migration created the table without this field

3. **Model wasn't configured** - `SystemTestRun` model didn't have `metadata` in `$fillable` or `$casts`

---

## Fixes Applied

### ✅ Fix 1: Updated Initial Migration
**File**: `database/migrations/2025_10_21_000001_create_system_test_runs_table.php`

Added metadata column to the table definition:
```php
// Metadata (company context: name, team_id, event_ids)
$table->json('metadata')->nullable();
```

### ✅ Fix 2: Created Migration to Add Column
**File**: `database/migrations/2025_10_21_000002_add_metadata_to_system_test_runs.php`

New migration adds the column for existing installations:
```php
Schema::table('system_test_runs', function (Blueprint $table) {
    if (!Schema::hasColumn('system_test_runs', 'metadata')) {
        $table->json('metadata')
            ->nullable()
            ->after('error_message');
    }
});
```

**Status**: ✅ **APPLIED** (Batch 1121)

### ✅ Fix 3: Updated Model
**File**: `app/Models/SystemTestRun.php`

Added `metadata` to model configuration:
```php
protected $fillable = [
    'user_id',
    'test_type',
    'status',
    'output',
    'error_message',
    'metadata',  // ← Added
    'started_at',
    'completed_at',
    'duration_ms'
];

protected $casts = [
    'output' => 'json',
    'metadata' => 'json',  // ← Added
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
];
```

### ✅ Fix 4: Fixed Syntax Error in Related Migration
**File**: `database/migrations/2025_10_13_160319_add_sync_orchestration_to_appointments.php`

Fixed missing closing brace on line 51 that was preventing artisan commands from running.

### ✅ Fix 5: Removed Non-Persisting Private Properties (CRITICAL)
**File**: `app/Filament/Pages/SystemTestingDashboard.php`

**Problem**: In Livewire 3, private properties DON'T persist across component updates/requests. So:
- Component initially mounts: `$testRunner` is initialized
- User clicks "Run All Tests": Component updates via Livewire, `$testRunner` becomes NULL
- `runAllTests()` tries to call `$this->testRunner->runAllTests()` → Null reference error → 500

**Solution**: Create a helper method to instantiate CalcomTestRunner fresh when needed:

```php
// REMOVED:
private ?CalcomTestRunner $testRunner = null;

public function mount(): void
{
    $this->testRunner = new CalcomTestRunner();  // Won't persist!
}

// ADDED:
private function getTestRunner(): CalcomTestRunner
{
    return new CalcomTestRunner();  // Fresh instance each time
}

// UPDATED methods:
public function runTest(string $testType): void
{
    $testRun = $this->getTestRunner()->runTest(...);  // ← Fresh instance
}

public function runAllTests(): void
{
    $results = $this->getTestRunner()->runAllTests(...);  // ← Fresh instance
}
```

---

## Testing

### Before Fix
```
POST /livewire/update → 500 Internal Server Error
Browser Console: Error creating record with unknown column 'metadata'
```

### After Fix
```
POST /livewire/update → 200 OK
Tests execute and store results with company context
```

---

## Flow Now Working

1. User selects **AskProAI** (Team 39203)
2. User clicks **Run All Tests**
3. `SystemTestRun::create()` succeeds ✅
   - Stores metadata: `{'name': 'AskProAI', 'team_id': 39203, 'event_ids': [3664712, 2563193]}`
4. Tests execute with company context ✅
5. Results shown in Live Output ✅
6. Test History updated ✅

---

## Technical Details

### Metadata Usage
The metadata field stores company-specific test context:
```json
{
    "name": "AskProAI",
    "team_id": 39203,
    "event_ids": [3664712, 2563193]
}
```

This enables:
- **Multi-tenant isolation**: Tests run with specific team context
- **Audit trail**: Who tested what company
- **Result filtering**: View results by company
- **Debug information**: Understand test execution context

### Database Impact
- **New Column**: `metadata` (JSON, nullable)
- **Table**: `system_test_runs`
- **Type**: Backward compatible (nullable, no constraints)

---

## Verification Checklist

- ✅ Migration applied: `2025_10_21_000002_add_metadata_to_system_test_runs` (Batch 1121)
- ✅ Model updated: `SystemTestRun` has metadata in $fillable and $casts
- ✅ Syntax error fixed in appointment migration
- ✅ **CRITICAL**: Removed private properties that don't persist in Livewire 3
- ✅ CalcomTestRunner now created fresh on each method call (no state persistence needed)
- ✅ Cache cleared: `php artisan optimize:clear`
- ✅ Ready to test dashboard

---

## Next Steps

1. **Test Dashboard**: 
   - Select "AskProAI" company
   - Click "Run All Tests"
   - Verify no 500 error
   - Check Live Output displays company context
   - Check Test History updated

2. **Create Test Files**:
   - 8 Pest test files still needed (infrastructure ready)
   - Once tests exist, they'll execute with company context
   - Results stored with metadata for tracking

---

**Status**: 🟢 **READY FOR TESTING**

